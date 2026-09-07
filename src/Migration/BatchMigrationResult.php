<?php
namespace GreatMarketrealmExpansions\Migration;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;

final class BatchMigrationResult
{
    /** @param list<MigrationResult> $results */
    public function __construct(private array $results) {}

    /** @return list<MigrationResult> */
    public function results(): array { return $this->results; }

    public function successful(): bool
    {
        foreach ($this->results as $result) {
            if (!$result->successful()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Atomic output view: no migrated definitions are exposed unless the whole batch succeeded.
     *
     * @return list<ContentDefinition>
     */
    public function definitions(): array
    {
        if (!$this->successful()) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (MigrationResult $result): ?ContentDefinition => $result->definition(),
            $this->results
        )));
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'successful' => $this->successful(),
            'definition_count' => count($this->definitions()),
            'results' => array_map(static fn (MigrationResult $result): array => $result->toArray(), $this->results),
        ];
    }
}
