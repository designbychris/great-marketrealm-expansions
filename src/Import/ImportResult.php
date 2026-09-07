<?php
namespace GreatMarketrealmExpansions\Import;

defined('ABSPATH') || exit;

final class ImportResult
{
    /**
     * @param list<StagedDefinition> $definitions
     * @param list<ImportIssue> $issues
     */
    public function __construct(
        private SourceDocument $source,
        private array $definitions,
        private array $issues = []
    ) {}

    public function source(): SourceDocument { return $this->source; }
    /** @return list<StagedDefinition> */
    public function definitions(): array { return $this->definitions; }
    /** @return list<ImportIssue> */
    public function issues(): array { return $this->issues; }

    public function validCount(): int
    {
        return count(array_filter($this->definitions, static fn (StagedDefinition $item): bool => $item->valid()));
    }

    public function reviewCount(): int
    {
        return count(array_filter($this->definitions, static fn (StagedDefinition $item): bool => $item->requiresReview()));
    }

    public function hasErrors(): bool
    {
        foreach ($this->issues as $issue) {
            if ($issue->error()) {
                return true;
            }
        }
        foreach ($this->definitions as $definition) {
            if (!$definition->valid()) {
                return true;
            }
        }
        return false;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'source' => $this->source->toArray(),
            'definition_count' => count($this->definitions),
            'valid_count' => $this->validCount(),
            'review_count' => $this->reviewCount(),
            'has_errors' => $this->hasErrors(),
            'issues' => array_map(static fn (ImportIssue $issue): array => $issue->toArray(), $this->issues),
            'definitions' => array_map(static fn (StagedDefinition $item): array => $item->toArray(), $this->definitions),
        ];
    }
}
