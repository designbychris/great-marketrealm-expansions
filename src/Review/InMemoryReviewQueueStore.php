<?php
namespace GreatMarketrealmExpansions\Review;

defined('ABSPATH') || exit;

final class InMemoryReviewQueueStore implements ReviewQueueStore
{
    /** @param array<string,mixed>|null $state */
    public function __construct(private ?array $state = null) {}

    public function load(): ?array { return $this->state; }
    public function save(array $state): void { $this->state = $state; }
    public function clear(): void { $this->state = null; }
}
