<?php
namespace GreatMarketrealmExpansions\Review;

defined('ABSPATH') || exit;

interface ReviewQueueStore
{
    /** @return array<string,mixed>|null */
    public function load(): ?array;

    /** @param array<string,mixed> $state */
    public function save(array $state): void;

    public function clear(): void;
}
