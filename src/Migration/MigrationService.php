<?php
namespace GreatMarketrealmExpansions\Migration;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use Throwable;

final class MigrationService
{
    public const API_VERSION = '1.0.0';

    /** @var list<string> */
    private const CAPABILITIES = [
        'migration.register',
        'migration.plan',
        'migration.apply',
        'migration.batch',
        'migration.validate',
        'migration.identity-preservation',
        'migration.provenance',
        'migration.atomic-output',
        'migration.no-catalogue-mutation',
    ];

    public function __construct(
        private MigrationRegistry $registry,
        private ContentValidator $validator
    ) {}

    public function apiVersion(): string { return self::API_VERSION; }
    public function registry(): MigrationRegistry { return $this->registry; }

    /** @return list<string> */
    public function capabilities(): array { return self::CAPABILITIES; }

    public function supports(string $capability): bool
    {
        return in_array(strtolower(trim($capability)), self::CAPABILITIES, true);
    }

    public function register(MigrationStep $step): void
    {
        $this->registry->add($step);
    }

    public function plan(string $contentType, string $fromVersion, string $toVersion): MigrationPlan
    {
        $contentType = $this->canonical($contentType);
        $fromVersion = trim($fromVersion);
        $toVersion = trim($toVersion);

        if ($contentType === '' || $fromVersion === '' || $toVersion === '') {
            throw new MigrationException('Migration planning requires content type, from version, and to version.');
        }

        if (version_compare($toVersion, $fromVersion, '<')) {
            throw new MigrationException('Migration planning is forward-only; downgrade paths are not supported.');
        }

        if (version_compare($fromVersion, $toVersion, '==')) {
            return new MigrationPlan($contentType, $fromVersion, $toVersion, []);
        }

        $paths = [];
        $this->findPaths($contentType, $fromVersion, $toVersion, [], [], $paths);

        if ($paths === []) {
            throw new MigrationException(sprintf(
                'No migration path exists for "%s" from %s to %s.',
                $contentType,
                $fromVersion,
                $toVersion
            ));
        }

        if (count($paths) > 1) {
            throw new MigrationException(sprintf(
                'More than one migration path exists for "%s" from %s to %s; the route is ambiguous.',
                $contentType,
                $fromVersion,
                $toVersion
            ));
        }

        return new MigrationPlan($contentType, $fromVersion, $toVersion, $paths[0]);
    }

    public function migrate(ContentDefinition $definition, string $fromVersion, string $toVersion): MigrationResult
    {
        try {
            $plan = $this->plan($definition->type(), $fromVersion, $toVersion);
        } catch (MigrationException $exception) {
            $code = str_contains($exception->getMessage(), 'forward-only')
                ? 'migration_direction_invalid'
                : (str_contains($exception->getMessage(), 'ambiguous')
                    ? 'migration_path_ambiguous'
                    : 'migration_path_missing');

            return new MigrationResult(
                $definition,
                $fromVersion,
                $toVersion,
                null,
                [],
                [new MigrationIssue($code, $exception->getMessage())]
            );
        }

        if ($plan->empty()) {
            $validation = $this->validator->validate($definition);
            if (!$validation->valid()) {
                $issues = array_map(
                    static fn ($error): MigrationIssue => new MigrationIssue(
                        'migration_validation_failed',
                        $error->message(),
                        null,
                        $error->field()
                    ),
                    $validation->errors()
                );
                return new MigrationResult($definition, $fromVersion, $toVersion, null, [], $issues);
            }

            return new MigrationResult($definition, $fromVersion, $toVersion, $definition);
        }

        $data = $definition->data();
        $applied = [];

        foreach ($plan->steps() as $step) {
            try {
                $transformed = $step->transform($data);
            } catch (Throwable $exception) {
                return new MigrationResult(
                    $definition,
                    $fromVersion,
                    $toVersion,
                    null,
                    $applied,
                    [new MigrationIssue(
                        'migration_step_failed',
                        sprintf('Migration step "%s" failed: %s', $step->id(), $exception->getMessage()),
                        $step->id()
                    )]
                );
            }

            if (array_is_list($transformed) && $transformed !== []) {
                return new MigrationResult(
                    $definition,
                    $fromVersion,
                    $toVersion,
                    null,
                    $applied,
                    [new MigrationIssue(
                        'migration_step_invalid_output',
                        sprintf('Migration step "%s" must return a content data map.', $step->id()),
                        $step->id()
                    )]
                );
            }

            $data = $transformed;
            $applied[] = $step->id();
        }

        $data = $this->stampProvenance($definition, $data, $plan);
        $migrated = new ContentDefinition($definition->type(), $definition->key(), $data);
        $validation = $this->validator->validate($migrated);

        if (!$validation->valid()) {
            $issues = array_map(
                static fn ($error): MigrationIssue => new MigrationIssue(
                    'migration_validation_failed',
                    $error->message(),
                    null,
                    $error->field()
                ),
                $validation->errors()
            );

            return new MigrationResult(
                $definition,
                $fromVersion,
                $toVersion,
                null,
                $applied,
                $issues
            );
        }

        return new MigrationResult(
            $definition,
            $fromVersion,
            $toVersion,
            $migrated,
            $applied
        );
    }

    /**
     * @param list<ContentDefinition> $definitions
     */
    public function migrateBatch(array $definitions, string $fromVersion, string $toVersion): BatchMigrationResult
    {
        $results = [];
        foreach ($definitions as $definition) {
            $results[] = $this->migrate($definition, $fromVersion, $toVersion);
        }
        return new BatchMigrationResult($results);
    }

    /**
     * @param list<MigrationStep> $path
     * @param array<string,true> $visited
     * @param list<list<MigrationStep>> $paths
     */
    private function findPaths(
        string $contentType,
        string $currentVersion,
        string $targetVersion,
        array $path,
        array $visited,
        array &$paths
    ): void {
        if (count($paths) > 1) {
            return;
        }

        $visitKey = $contentType . '@' . $currentVersion;
        if (isset($visited[$visitKey])) {
            return;
        }
        $visited[$visitKey] = true;

        foreach ($this->registry->forType($contentType) as $step) {
            if (!version_compare($step->fromVersion(), $currentVersion, '==')) {
                continue;
            }

            if (version_compare($step->toVersion(), $targetVersion, '>')) {
                continue;
            }

            $nextPath = [...$path, $step];
            if (version_compare($step->toVersion(), $targetVersion, '==')) {
                $paths[] = $nextPath;
                continue;
            }

            $this->findPaths(
                $contentType,
                $step->toVersion(),
                $targetVersion,
                $nextPath,
                $visited,
                $paths
            );
        }
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function stampProvenance(
        ContentDefinition $source,
        array $data,
        MigrationPlan $plan
    ): array {
        $original = $source->provenance();
        $transformed = isset($data['provenance']) && is_array($data['provenance'])
            ? $data['provenance']
            : [];

        $history = isset($original['migration_history']) && is_array($original['migration_history'])
            ? $original['migration_history']
            : [];

        foreach ($plan->steps() as $step) {
            $history[] = [
                'step_id' => $step->id(),
                'from_version' => $step->fromVersion(),
                'to_version' => $step->toVersion(),
            ];
        }

        $protected = ['migration_history' => $history];
        $data['provenance'] = $protected + $original + $transformed;
        return $data;
    }

    private function canonical(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_\-]+/', '-', $value) ?? '';
        return trim(preg_replace('/-+/', '-', $value) ?? '', '-');
    }
}
