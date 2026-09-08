<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Review;

use GreatMarketrealmExpansions\Review\InMemoryReviewQueueStore;
use PHPUnit\Framework\TestCase;

final class ReviewQueueStoreTest extends TestCase
{
    public function test_queue_starts_empty(): void
    {
        self::assertNull((new InMemoryReviewQueueStore())->load());
    }

    public function test_queue_persists_staged_json_and_decisions(): void
    {
        $store = new InMemoryReviewQueueStore();
        $state = ['json' => '{"source":{}}', 'decisions' => ['record-1' => ['action' => 'reject']]];
        $store->save($state);
        self::assertSame($state, $store->load());
    }

    public function test_queue_can_be_cleared_without_touching_other_systems(): void
    {
        $store = new InMemoryReviewQueueStore(['json' => '{}', 'decisions' => []]);
        $store->clear();
        self::assertNull($store->load());
    }
}
