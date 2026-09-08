<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Review;

use GreatMarketrealmExpansions\Review\WordPressUserReviewQueueStore;
use PHPUnit\Framework\TestCase;

final class WordPressUserReviewQueueStoreTest extends TestCase
{
    public function test_meta_key_is_stable(): void
    {
        self::assertSame('_gmrexp_review_desk_queue', WordPressUserReviewQueueStore::META_KEY);
    }

    public function test_store_pre_slashes_queue_before_wordpress_metadata_write(): void
    {
        $source = file_get_contents(dirname(__DIR__, 3) . '/src/Review/WordPressUserReviewQueueStore.php');

        self::assertIsString($source);
        self::assertStringContainsString("function_exists('wp_slash')", $source);
        self::assertStringContainsString('wp_slash($state)', $source);
    }
}
