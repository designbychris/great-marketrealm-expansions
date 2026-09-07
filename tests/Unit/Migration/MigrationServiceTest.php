<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Migration;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use GreatMarketrealmExpansions\Migration\MigrationException;
use GreatMarketrealmExpansions\Migration\MigrationRegistry;
use GreatMarketrealmExpansions\Migration\MigrationService;
use GreatMarketrealmExpansions\Migration\MigrationStep;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MigrationServiceTest extends TestCase
{
    private function service(): MigrationService
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) {
            $types->add($type);
        }

        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);

        return new MigrationService(
            new MigrationRegistry(),
            new ContentValidator($schemas)
        );
    }

    private function source(array $data = []): ContentDefinition
    {
        return new ContentDefinition('feat', 'fixture-feat', $data + [
            'name' => 'Fixture Feat',
            'legacy_note' => 'Old wording.',
            'provenance' => [
                'sourcebook' => 'Fixture Book',
                'import_source_id' => 'fixture-doc',
            ],
        ]);
    }

    private function registerTwoStepChain(MigrationService $service): void
    {
        $service->register(new MigrationStep(
            'feat-1-to-2',
            'feat',
            '1.0.0',
            '2.0.0',
            static function (array $data): array {
                $data['description'] = $data['legacy_note'] ?? '';
                unset($data['legacy_note']);
                return $data;
            }
        ));

        $service->register(new MigrationStep(
            'feat-2-to-3',
            'feat',
            '2.0.0',
            '3.0.0',
            static function (array $data): array {
                $data['tags'] = ['migrated-fixture'];
                return $data;
            }
        ));
    }

    public function test_migration_api_version_and_capabilities_are_stable(): void
    {
        $service = $this->service();

        self::assertSame('1.0.0', $service->apiVersion());
        self::assertTrue($service->supports('migration.register'));
        self::assertTrue($service->supports('migration.plan'));
        self::assertTrue($service->supports('migration.apply'));
        self::assertTrue($service->supports('migration.batch'));
        self::assertTrue($service->supports('migration.validate'));
        self::assertTrue($service->supports('migration.identity-preservation'));
        self::assertTrue($service->supports('migration.provenance'));
        self::assertTrue($service->supports('migration.atomic-output'));
        self::assertTrue($service->supports('migration.no-catalogue-mutation'));
        self::assertFalse($service->supports('migration.publish'));
    }

    public function test_step_requires_non_empty_identity_and_versions(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new MigrationStep('', 'feat', '1.0.0', '2.0.0', static fn (array $data): array => $data);
    }

    public function test_step_must_move_strictly_forward(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('strictly forward');

        new MigrationStep('bad-step', 'feat', '2.0.0', '1.0.0', static fn (array $data): array => $data);
    }

    public function test_step_cannot_be_same_version(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new MigrationStep('same-step', 'feat', '1.0.0', '1.0.0', static fn (array $data): array => $data);
    }

    public function test_registry_rejects_duplicate_step_id(): void
    {
        $service = $this->service();
        $step = new MigrationStep('fixture-step', 'feat', '1.0.0', '2.0.0', static fn (array $data): array => $data);
        $service->register($step);

        $this->expectException(InvalidArgumentException::class);
        $service->register(new MigrationStep('fixture-step', 'feat', '2.0.0', '3.0.0', static fn (array $data): array => $data));
    }

    public function test_registry_rejects_duplicate_semantic_route(): void
    {
        $service = $this->service();
        $service->register(new MigrationStep('route-a', 'feat', '1.0', '2.0', static fn (array $data): array => $data));

        $this->expectException(InvalidArgumentException::class);
        $service->register(new MigrationStep('route-b', 'feat', '1.0.0', '2.0.0', static fn (array $data): array => $data));
    }

    public function test_registry_lists_steps_deterministically(): void
    {
        $service = $this->service();
        $service->register(new MigrationStep('z-step', 'feat', '2.0.0', '3.0.0', static fn (array $data): array => $data));
        $service->register(new MigrationStep('a-step', 'monster', '1.0.0', '2.0.0', static fn (array $data): array => $data));

        self::assertSame(['a-step', 'z-step'], array_map(
            static fn (MigrationStep $step): string => $step->id(),
            $service->registry()->all()
        ));
    }

    public function test_plan_builds_exact_forward_chain(): void
    {
        $service = $this->service();
        $this->registerTwoStepChain($service);

        $plan = $service->plan('feat', '1.0.0', '3.0.0');

        self::assertSame(['feat-1-to-2', 'feat-2-to-3'], $plan->stepIds());
        self::assertFalse($plan->empty());
        self::assertSame('feat', $plan->contentType());
    }

    public function test_plan_accepts_semantically_equivalent_version_strings(): void
    {
        $service = $this->service();
        $service->register(new MigrationStep(
            'feat-up',
            'feat',
            '1.0.0',
            '2.0.0',
            static fn (array $data): array => $data
        ));

        self::assertSame(['feat-up'], $service->plan('feat', '1.0', '2.0')->stepIds());
    }

    public function test_same_version_plan_is_empty(): void
    {
        $plan = $this->service()->plan('feat', '1.0', '1.0.0');

        self::assertTrue($plan->empty());
        self::assertSame([], $plan->stepIds());
    }

    public function test_downgrade_plan_is_refused(): void
    {
        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('forward-only');

        $this->service()->plan('feat', '3.0.0', '2.0.0');
    }

    public function test_missing_path_is_refused(): void
    {
        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('No migration path');

        $this->service()->plan('feat', '1.0.0', '2.0.0');
    }

    public function test_ambiguous_path_is_refused_instead_of_guessed(): void
    {
        $service = $this->service();

        $service->register(new MigrationStep('a-1-2', 'feat', '1.0.0', '2.0.0', static fn (array $data): array => $data));
        $service->register(new MigrationStep('a-2-3', 'feat', '2.0.0', '3.0.0', static fn (array $data): array => $data));
        $service->register(new MigrationStep('b-1-25', 'feat', '1.0.0', '2.5.0', static fn (array $data): array => $data));
        $service->register(new MigrationStep('b-25-3', 'feat', '2.5.0', '3.0.0', static fn (array $data): array => $data));

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('ambiguous');

        $service->plan('feat', '1.0.0', '3.0.0');
    }

    public function test_successful_migration_applies_chain_in_order(): void
    {
        $service = $this->service();
        $this->registerTwoStepChain($service);

        $result = $service->migrate($this->source(), '1.0.0', '3.0.0');

        self::assertTrue($result->successful());
        self::assertSame(['feat-1-to-2', 'feat-2-to-3'], $result->appliedStepIds());
        self::assertSame('Old wording.', $result->definition()?->value('description'));
        self::assertSame(['migrated-fixture'], $result->definition()?->value('tags'));
        self::assertNull($result->definition()?->value('legacy_note'));
    }

    public function test_migration_preserves_canonical_type_and_key(): void
    {
        $service = $this->service();
        $this->registerTwoStepChain($service);

        $result = $service->migrate($this->source(), '1.0.0', '3.0.0');

        self::assertSame('feat', $result->definition()?->type());
        self::assertSame('fixture-feat', $result->definition()?->key());
    }

    public function test_migration_does_not_mutate_source_definition(): void
    {
        $service = $this->service();
        $this->registerTwoStepChain($service);
        $source = $this->source();
        $before = $source->data();

        $service->migrate($source, '1.0.0', '3.0.0');

        self::assertSame($before, $source->data());
        self::assertSame('Old wording.', $source->value('legacy_note'));
    }

    public function test_migration_preserves_existing_provenance(): void
    {
        $service = $this->service();
        $this->registerTwoStepChain($service);

        $provenance = $service->migrate($this->source(), '1.0.0', '3.0.0')
            ->definition()?->provenance() ?? [];

        self::assertSame('Fixture Book', $provenance['sourcebook'] ?? null);
        self::assertSame('fixture-doc', $provenance['import_source_id'] ?? null);
    }

    public function test_migration_stamps_ordered_history(): void
    {
        $service = $this->service();
        $this->registerTwoStepChain($service);

        $history = $service->migrate($this->source(), '1.0.0', '3.0.0')
            ->definition()?->provenance()['migration_history'] ?? [];

        self::assertSame('feat-1-to-2', $history[0]['step_id'] ?? null);
        self::assertSame('1.0.0', $history[0]['from_version'] ?? null);
        self::assertSame('2.0.0', $history[0]['to_version'] ?? null);
        self::assertSame('feat-2-to-3', $history[1]['step_id'] ?? null);
    }

    public function test_migration_appends_to_existing_history(): void
    {
        $service = $this->service();
        $service->register(new MigrationStep('feat-2-to-3', 'feat', '2.0.0', '3.0.0', static fn (array $data): array => $data));

        $source = $this->source([
            'provenance' => [
                'migration_history' => [
                    ['step_id' => 'feat-1-to-2', 'from_version' => '1.0.0', 'to_version' => '2.0.0'],
                ],
            ],
        ]);

        $history = $service->migrate($source, '2.0.0', '3.0.0')
            ->definition()?->provenance()['migration_history'] ?? [];

        self::assertCount(2, $history);
        self::assertSame('feat-1-to-2', $history[0]['step_id']);
        self::assertSame('feat-2-to-3', $history[1]['step_id']);
    }

    public function test_transformer_cannot_spoof_protected_existing_provenance_or_history(): void
    {
        $service = $this->service();
        $service->register(new MigrationStep(
            'spoof-attempt',
            'feat',
            '1.0.0',
            '2.0.0',
            static function (array $data): array {
                $data['provenance'] = [
                    'import_source_id' => 'spoofed',
                    'migration_history' => [['step_id' => 'fake']],
                    'extra' => 'allowed',
                ];
                return $data;
            }
        ));

        $provenance = $service->migrate($this->source(), '1.0.0', '2.0.0')
            ->definition()?->provenance() ?? [];

        self::assertSame('fixture-doc', $provenance['import_source_id'] ?? null);
        self::assertSame('spoof-attempt', $provenance['migration_history'][0]['step_id'] ?? null);
        self::assertSame('allowed', $provenance['extra'] ?? null);
    }

    public function test_final_output_must_pass_canonical_validation(): void
    {
        $service = $this->service();
        $service->register(new MigrationStep(
            'remove-name',
            'feat',
            '1.0.0',
            '2.0.0',
            static function (array $data): array {
                unset($data['name']);
                return $data;
            }
        ));

        $result = $service->migrate($this->source(), '1.0.0', '2.0.0');

        self::assertFalse($result->successful());
        self::assertNull($result->definition());
        self::assertSame('migration_validation_failed', $result->issues()[0]->code());
        self::assertSame('name', $result->issues()[0]->field());
    }

    public function test_step_exception_becomes_structured_failure(): void
    {
        $service = $this->service();
        $service->register(new MigrationStep(
            'explodes',
            'feat',
            '1.0.0',
            '2.0.0',
            static function (array $data): array {
                throw new RuntimeException('Fixture explosion.');
            }
        ));

        $result = $service->migrate($this->source(), '1.0.0', '2.0.0');

        self::assertFalse($result->successful());
        self::assertSame('migration_step_failed', $result->issues()[0]->code());
        self::assertSame('explodes', $result->issues()[0]->stepId());
    }

    public function test_list_output_from_step_is_rejected_as_invalid_map(): void
    {
        $service = $this->service();
        $service->register(new MigrationStep(
            'bad-output',
            'feat',
            '1.0.0',
            '2.0.0',
            static fn (array $data): array => ['not', 'a', 'map']
        ));

        $result = $service->migrate($this->source(), '1.0.0', '2.0.0');

        self::assertFalse($result->successful());
        self::assertSame('migration_step_invalid_output', $result->issues()[0]->code());
    }

    public function test_missing_path_becomes_structured_migration_result_issue(): void
    {
        $result = $this->service()->migrate($this->source(), '1.0.0', '2.0.0');

        self::assertFalse($result->successful());
        self::assertSame('migration_path_missing', $result->issues()[0]->code());
    }

    public function test_downgrade_becomes_structured_migration_result_issue(): void
    {
        $result = $this->service()->migrate($this->source(), '3.0.0', '2.0.0');

        self::assertFalse($result->successful());
        self::assertSame('migration_direction_invalid', $result->issues()[0]->code());
    }

    public function test_ambiguous_route_becomes_structured_migration_result_issue(): void
    {
        $service = $this->service();
        $service->register(new MigrationStep('a-1-2', 'feat', '1.0.0', '2.0.0', static fn (array $data): array => $data));
        $service->register(new MigrationStep('a-2-3', 'feat', '2.0.0', '3.0.0', static fn (array $data): array => $data));
        $service->register(new MigrationStep('b-1-25', 'feat', '1.0.0', '2.5.0', static fn (array $data): array => $data));
        $service->register(new MigrationStep('b-25-3', 'feat', '2.5.0', '3.0.0', static fn (array $data): array => $data));

        $result = $service->migrate($this->source(), '1.0.0', '3.0.0');

        self::assertFalse($result->successful());
        self::assertSame('migration_path_ambiguous', $result->issues()[0]->code());
    }

    public function test_same_version_migration_is_successful_no_op_without_data_mutation(): void
    {
        $source = $this->source();
        $result = $this->service()->migrate($source, '1.0', '1.0.0');

        self::assertTrue($result->successful());
        self::assertSame([], $result->appliedStepIds());
        self::assertSame($source->data(), $result->definition()?->data());
    }

    public function test_same_version_migration_still_rejects_invalid_current_content(): void
    {
        $invalid = new ContentDefinition('feat', 'invalid-feat', []);

        $result = $this->service()->migrate($invalid, '1.0.0', '1.0.0');

        self::assertFalse($result->successful());
        self::assertSame('migration_validation_failed', $result->issues()[0]->code());
    }

    public function test_batch_exposes_all_outputs_when_every_migration_succeeds(): void
    {
        $service = $this->service();
        $this->registerTwoStepChain($service);

        $batch = $service->migrateBatch([
            $this->source(),
            new ContentDefinition('feat', 'second-feat', ['name' => 'Second', 'legacy_note' => 'Second note']),
        ], '1.0.0', '3.0.0');

        self::assertTrue($batch->successful());
        self::assertCount(2, $batch->definitions());
        self::assertSame(['fixture-feat', 'second-feat'], array_map(
            static fn (ContentDefinition $definition): string => $definition->key(),
            $batch->definitions()
        ));
    }

    public function test_batch_atomic_output_is_empty_when_any_item_fails(): void
    {
        $service = $this->service();
        $this->registerTwoStepChain($service);

        $batch = $service->migrateBatch([
            $this->source(),
            new ContentDefinition('monster', 'unroutable-monster', ['name' => 'Fixture Monster']),
        ], '1.0.0', '3.0.0');

        self::assertFalse($batch->successful());
        self::assertSame([], $batch->definitions());
        self::assertTrue($batch->results()[0]->successful());
        self::assertFalse($batch->results()[1]->successful());
    }

    public function test_migration_result_serialises_auditable_transition(): void
    {
        $service = $this->service();
        $this->registerTwoStepChain($service);

        $data = $service->migrate($this->source(), '1.0.0', '3.0.0')->toArray();

        self::assertSame('feat', $data['type']);
        self::assertSame('fixture-feat', $data['key']);
        self::assertSame('1.0.0', $data['from_version']);
        self::assertSame('3.0.0', $data['to_version']);
        self::assertTrue($data['successful']);
        self::assertSame(['feat-1-to-2', 'feat-2-to-3'], $data['applied_step_ids']);
        self::assertSame('Old wording.', $data['data']['description']);
    }

    public function test_migration_is_deterministic_for_same_source_and_registry(): void
    {
        $service = $this->service();
        $this->registerTwoStepChain($service);
        $source = $this->source();

        self::assertSame(
            $service->migrate($source, '1.0.0', '3.0.0')->toArray(),
            $service->migrate($source, '1.0.0', '3.0.0')->toArray()
        );
    }
}
