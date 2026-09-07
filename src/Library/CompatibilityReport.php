<?php
namespace GreatMarketrealmExpansions\Library;

defined('ABSPATH') || exit;

final class CompatibilityReport
{
    public const READY = 'ready';
    public const DEGRADED = 'degraded';
    public const BLOCKED = 'blocked';

    /** @param list<CompatibilityIssue> $issues */
    public function __construct(
        private string $expansionKey,
        private bool $installed,
        private bool $active,
        private array $issues
    ) {}

    public function expansionKey(): string { return $this->expansionKey; }
    public function installed(): bool { return $this->installed; }
    public function active(): bool { return $this->active; }

    /** @return list<CompatibilityIssue> */
    public function issues(): array { return $this->issues; }

    public function status(): string
    {
        foreach ($this->issues as $issue) {
            if ($issue->blocking()) {
                return self::BLOCKED;
            }
        }

        return $this->issues === [] ? self::READY : self::DEGRADED;
    }

    public function ready(): bool { return $this->status() === self::READY; }
    public function degraded(): bool { return $this->status() === self::DEGRADED; }
    public function blocked(): bool { return $this->status() === self::BLOCKED; }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'expansion' => $this->expansionKey,
            'installed' => $this->installed,
            'active' => $this->active,
            'status' => $this->status(),
            'issues' => array_map(static fn (CompatibilityIssue $issue): array => $issue->toArray(), $this->issues),
        ];
    }
}
