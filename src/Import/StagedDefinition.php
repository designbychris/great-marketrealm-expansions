<?php
namespace GreatMarketrealmExpansions\Import;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;

final class StagedDefinition
{
    /**
     * @param list<ImportIssue> $issues
     * @param array<string,mixed> $sourceContext
     */
    public function __construct(
        private string $recordId,
        private ?ContentDefinition $definition,
        private array $issues = [],
        private array $sourceContext = [],
        private array $sourceData = []
    ) {}

    public function recordId(): string { return $this->recordId; }
    public function definition(): ?ContentDefinition { return $this->definition; }
    /** @return list<ImportIssue> */
    public function issues(): array { return $this->issues; }
    /** @return array<string,mixed> */
    public function sourceContext(): array { return $this->sourceContext; }
    /** @return array<string,mixed> */
    public function sourceData(): array { return $this->sourceData; }

    public function valid(): bool
    {
        if ($this->definition === null) {
            return false;
        }
        foreach ($this->issues as $issue) {
            if ($issue->error()) {
                return false;
            }
        }
        return true;
    }

    public function requiresReview(): bool
    {
        if (!$this->valid()) {
            return true;
        }
        return $this->issues !== [];
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'record_id' => $this->recordId,
            'type' => $this->definition?->type(),
            'key' => $this->definition?->key(),
            'valid' => $this->valid(),
            'requires_review' => $this->requiresReview(),
            'source_context' => $this->sourceContext,
            'source_data' => $this->sourceData,
            'issues' => array_map(static fn (ImportIssue $issue): array => $issue->toArray(), $this->issues),
        ];
    }
}
