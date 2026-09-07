<?php
namespace GreatMarketrealmExpansions\Migration;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;

final class MigrationResult
{
    /**
     * @param list<string> $appliedStepIds
     * @param list<MigrationIssue> $issues
     */
    public function __construct(
        private ContentDefinition $source,
        private string $fromVersion,
        private string $toVersion,
        private ?ContentDefinition $definition,
        private array $appliedStepIds = [],
        private array $issues = []
    ) {}

    public function source(): ContentDefinition { return $this->source; }
    public function fromVersion(): string { return $this->fromVersion; }
    public function toVersion(): string { return $this->toVersion; }
    public function definition(): ?ContentDefinition { return $this->definition; }
    /** @return list<string> */
    public function appliedStepIds(): array { return $this->appliedStepIds; }
    /** @return list<MigrationIssue> */
    public function issues(): array { return $this->issues; }
    public function successful(): bool { return $this->definition !== null && $this->issues === []; }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->source->type(),
            'key' => $this->source->key(),
            'from_version' => $this->fromVersion,
            'to_version' => $this->toVersion,
            'successful' => $this->successful(),
            'applied_step_ids' => $this->appliedStepIds,
            'issues' => array_map(static fn (MigrationIssue $issue): array => $issue->toArray(), $this->issues),
            'data' => $this->definition?->data(),
        ];
    }
}
