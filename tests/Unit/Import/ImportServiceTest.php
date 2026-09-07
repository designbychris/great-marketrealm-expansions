<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Import;

use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use GreatMarketrealmExpansions\Import\ImportIssue;
use GreatMarketrealmExpansions\Import\ImportService;
use PHPUnit\Framework\TestCase;

final class ImportServiceTest extends TestCase
{
    private function importer(): ImportService
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) {
            $types->add($type);
        }

        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);

        return new ImportService(new ContentValidator($schemas));
    }

    /** @return array<string,mixed> */
    private function document(): array
    {
        return [
            'source' => [
                'type' => 'google-doc',
                'id' => 'doc-fixture-123',
                'title' => 'Fixture Sourcebook',
                'version' => 'draft-7',
                'metadata' => [
                    'canonical_source' => false,
                ],
            ],
            'records' => [
                [
                    'id' => 'heading-12',
                    'type' => 'feat',
                    'key' => 'fixture-knack',
                    'source' => [
                        'heading' => 'Fixture Knack',
                        'section' => 'Chapter Two',
                    ],
                    'data' => [
                        'name' => 'Fixture Knack',
                        'description' => 'Synthetic import proving content.',
                    ],
                ],
            ],
        ];
    }

    public function test_import_api_version_and_capabilities_are_stable(): void
    {
        $importer = $this->importer();

        self::assertSame('1.0.0', $importer->apiVersion());
        self::assertTrue($importer->supports('import.stage'));
        self::assertTrue($importer->supports('import.validate'));
        self::assertTrue($importer->supports('import.provenance'));
        self::assertTrue($importer->supports('import.review-flags'));
        self::assertTrue($importer->supports('import.structured-document'));
        self::assertTrue($importer->supports('import.json'));
        self::assertFalse($importer->supports('import.publish'));
    }

    public function test_valid_structured_document_stages_definition_without_errors(): void
    {
        $result = $this->importer()->stage($this->document());

        self::assertFalse($result->hasErrors());
        self::assertSame(1, $result->validCount());
        self::assertSame(0, $result->reviewCount());
        self::assertCount(1, $result->definitions());
        self::assertTrue($result->definitions()[0]->valid());
    }

    public function test_source_identity_is_preserved(): void
    {
        $source = $this->importer()->stage($this->document())->source();

        self::assertSame('google-doc', $source->type());
        self::assertSame('doc-fixture-123', $source->id());
        self::assertSame('Fixture Sourcebook', $source->title());
        self::assertSame('draft-7', $source->version());
        self::assertSame(['canonical_source' => false], $source->metadata());
    }

    public function test_importer_stamps_protected_source_provenance(): void
    {
        $definition = $this->importer()->stage($this->document())->definitions()[0]->definition();
        $provenance = $definition?->provenance();

        self::assertSame('google-doc', $provenance['import_source_type'] ?? null);
        self::assertSame('doc-fixture-123', $provenance['import_source_id'] ?? null);
        self::assertSame('Fixture Sourcebook', $provenance['import_source_title'] ?? null);
        self::assertSame('draft-7', $provenance['import_source_version'] ?? null);
        self::assertSame('heading-12', $provenance['import_record_id'] ?? null);
    }

    public function test_importer_preserves_nonprotected_existing_provenance(): void
    {
        $document = $this->document();
        $document['records'][0]['data']['provenance'] = [
            'chapter' => 'Two',
            'page' => 14,
        ];

        $provenance = $this->importer()->stage($document)->definitions()[0]->definition()?->provenance();

        self::assertSame('Two', $provenance['chapter'] ?? null);
        self::assertSame(14, $provenance['page'] ?? null);
    }

    public function test_importer_does_not_allow_source_to_spoof_protected_provenance(): void
    {
        $document = $this->document();
        $document['records'][0]['data']['provenance'] = [
            'import_source_id' => 'spoofed',
        ];

        $provenance = $this->importer()->stage($document)->definitions()[0]->definition()?->provenance();

        self::assertSame('doc-fixture-123', $provenance['import_source_id'] ?? null);
    }

    public function test_record_source_context_is_preserved_for_review(): void
    {
        $staged = $this->importer()->stage($this->document())->definitions()[0];

        self::assertSame(
            ['heading' => 'Fixture Knack', 'section' => 'Chapter Two'],
            $staged->sourceContext()
        );
        self::assertSame('Chapter Two', $staged->definition()?->provenance()['import_context']['section'] ?? null);
    }

    public function test_explicit_review_flag_produces_warning_without_invalidating_definition(): void
    {
        $document = $this->document();
        $document['records'][0]['review_required'] = true;

        $staged = $this->importer()->stage($document)->definitions()[0];

        self::assertTrue($staged->valid());
        self::assertTrue($staged->requiresReview());
        self::assertSame('keeper_review_requested', $staged->issues()[0]->code());
        self::assertSame(ImportIssue::WARNING, $staged->issues()[0]->severity());
    }

    public function test_ambiguous_candidate_types_are_surfaced_for_review(): void
    {
        $document = $this->document();
        unset($document['records'][0]['type']);
        $document['records'][0]['candidate_types'] = ['class', 'subclass'];

        $staged = $this->importer()->stage($document)->definitions()[0];
        $codes = array_map(static fn ($issue): string => $issue->code(), $staged->issues());

        self::assertFalse($staged->valid());
        self::assertContains('source_ambiguity', $codes);
        self::assertContains('content_type_unresolved', $codes);
    }

    public function test_missing_type_is_not_guessed(): void
    {
        $document = $this->document();
        unset($document['records'][0]['type']);

        $staged = $this->importer()->stage($document)->definitions()[0];

        self::assertFalse($staged->valid());
        self::assertNull($staged->definition());
        self::assertSame('content_type_unresolved', $staged->issues()[0]->code());
    }

    public function test_missing_key_is_not_generated_from_title(): void
    {
        $document = $this->document();
        unset($document['records'][0]['key']);

        $staged = $this->importer()->stage($document)->definitions()[0];

        self::assertFalse($staged->valid());
        self::assertNull($staged->definition());
        self::assertSame('content_key_unresolved', $staged->issues()[0]->code());
    }

    public function test_invalid_data_shape_is_rejected(): void
    {
        $document = $this->document();
        $document['records'][0]['data'] = ['not', 'a', 'map'];

        $staged = $this->importer()->stage($document)->definitions()[0];

        self::assertFalse($staged->valid());
        self::assertSame('content_data_invalid', $staged->issues()[0]->code());
    }

    public function test_schema_validation_errors_are_kept_on_staged_definition(): void
    {
        $document = $this->document();
        $document['records'][0]['data'] = [];

        $staged = $this->importer()->stage($document)->definitions()[0];

        self::assertFalse($staged->valid());
        self::assertSame('schema_validation_failed', $staged->issues()[0]->code());
        self::assertSame('name', $staged->issues()[0]->field());
    }

    public function test_unknown_content_type_is_a_schema_validation_error(): void
    {
        $document = $this->document();
        $document['records'][0]['type'] = 'future-unknown-thing';

        $staged = $this->importer()->stage($document)->definitions()[0];

        self::assertFalse($staged->valid());
        self::assertSame('schema_validation_failed', $staged->issues()[0]->code());
        self::assertSame('type', $staged->issues()[0]->field());
    }

    public function test_duplicate_staged_identity_is_reported(): void
    {
        $document = $this->document();
        $duplicate = $document['records'][0];
        $duplicate['id'] = 'heading-13';
        $document['records'][] = $duplicate;

        $result = $this->importer()->stage($document);

        self::assertTrue($result->definitions()[0]->valid());
        self::assertFalse($result->definitions()[1]->valid());
        self::assertSame('duplicate_staged_identity', $result->definitions()[1]->issues()[0]->code());
    }

    public function test_records_must_be_a_list(): void
    {
        $document = $this->document();
        $document['records'] = ['first' => $document['records'][0]];

        $result = $this->importer()->stage($document);

        self::assertTrue($result->hasErrors());
        self::assertSame('records_invalid', $result->issues()[0]->code());
        self::assertSame([], $result->definitions());
    }

    public function test_non_map_record_is_staged_as_reviewable_error(): void
    {
        $document = $this->document();
        $document['records'] = ['bad-record'];

        $staged = $this->importer()->stage($document)->definitions()[0];

        self::assertFalse($staged->valid());
        self::assertTrue($staged->requiresReview());
        self::assertSame('record_invalid', $staged->issues()[0]->code());
    }

    public function test_missing_source_fields_are_reported_without_crashing_staging(): void
    {
        $document = $this->document();
        $document['source'] = [];

        $result = $this->importer()->stage($document);

        self::assertTrue($result->hasErrors());
        self::assertCount(3, $result->issues());
        self::assertSame('source_field_missing', $result->issues()[0]->code());
        self::assertSame('unknown-source', $result->source()->type());
    }

    public function test_source_must_be_a_map(): void
    {
        $document = $this->document();
        $document['source'] = ['bad', 'source'];

        $result = $this->importer()->stage($document);

        self::assertTrue($result->hasErrors());
        self::assertSame('source_invalid', $result->issues()[0]->code());
    }

    public function test_valid_json_document_can_be_staged_without_executing_source_code(): void
    {
        $json = json_encode($this->document(), JSON_THROW_ON_ERROR);

        $result = $this->importer()->stageJson($json);

        self::assertFalse($result->hasErrors());
        self::assertSame('fixture-knack', $result->definitions()[0]->definition()?->key());
    }

    public function test_invalid_json_returns_structured_error(): void
    {
        $result = $this->importer()->stageJson('{broken-json');

        self::assertTrue($result->hasErrors());
        self::assertSame('json_invalid', $result->issues()[0]->code());
        self::assertSame(0, $result->validCount());
    }

    public function test_json_root_must_be_an_object_map(): void
    {
        $result = $this->importer()->stageJson('["not","an","object"]');

        self::assertTrue($result->hasErrors());
        self::assertSame('document_invalid', $result->issues()[0]->code());
    }

    public function test_result_serialises_reviewable_staging_summary(): void
    {
        $document = $this->document();
        $document['records'][0]['review_required'] = true;

        $data = $this->importer()->stage($document)->toArray();

        self::assertSame('google-doc', $data['source']['type']);
        self::assertSame(1, $data['definition_count']);
        self::assertSame(1, $data['valid_count']);
        self::assertSame(1, $data['review_count']);
        self::assertFalse($data['has_errors']);
        self::assertTrue($data['definitions'][0]['requires_review']);
    }

    public function test_staging_is_deterministic_for_same_document(): void
    {
        $document = $this->document();

        self::assertSame(
            $this->importer()->stage($document)->toArray(),
            $this->importer()->stage($document)->toArray()
        );
    }
}
