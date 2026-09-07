<?php
namespace GreatMarketrealmExpansions\Migration;

defined('ABSPATH') || exit;

final class MigrationPlan
{
    /** @param list<MigrationStep> $steps */
    public function __construct(
        private string $contentType,
        private string $fromVersion,
        private string $toVersion,
        private array $steps
    ) {}

    public function contentType(): string { return $this->contentType; }
    public function fromVersion(): string { return $this->fromVersion; }
    public function toVersion(): string { return $this->toVersion; }
    /** @return list<MigrationStep> */
    public function steps(): array { return $this->steps; }
    public function empty(): bool { return $this->steps === []; }

    /** @return list<string> */
    public function stepIds(): array
    {
        return array_map(static fn (MigrationStep $step): string => $step->id(), $this->steps);
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'content_type' => $this->contentType,
            'from_version' => $this->fromVersion,
            'to_version' => $this->toVersion,
            'step_ids' => $this->stepIds(),
        ];
    }
}
