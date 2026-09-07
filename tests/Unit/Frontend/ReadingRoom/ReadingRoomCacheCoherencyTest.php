<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Frontend\ReadingRoom;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Expansions\ExpansionPack;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use GreatMarketrealmExpansions\Frontend\ReadingRoom\ReadingRoomAccess;
use GreatMarketrealmExpansions\Frontend\ReadingRoom\ReadingRoomNavigation;
use GreatMarketrealmExpansions\Frontend\ReadingRoom\ReadingRoomPage;
use GreatMarketrealmExpansions\Library\InMemoryActivationStore;
use GreatMarketrealmExpansions\Library\Library;
use PHPUnit\Framework\TestCase;

final class ReadingRoomCacheCoherencyTest extends TestCase
{
    private function page(): ReadingRoomPage
    {
        $expansions = new ExpansionRegistry();
        $content = new ContentRegistry();
        $expansions->add(new ExpansionPack('fixture-book', 'Fixture Book'));

        $catalogue = new Catalogue($expansions, $content);
        $library = new Library($catalogue, new InMemoryActivationStore());

        return new ReadingRoomPage(
            $catalogue,
            $library,
            new ReadingRoomAccess(),
            new ReadingRoomNavigation()
        );
    }

    public function test_shortcode_marks_reading_room_as_non_cacheable(): void
    {
        $this->page()->shortcode([]);

        self::assertTrue(defined('DONOTCACHEPAGE'));
        self::assertTrue(DONOTCACHEPAGE);
    }

    public function test_dynamic_marker_is_idempotent(): void
    {
        $page = $this->page();

        $page->markResponseDynamic();
        $page->markResponseDynamic();

        self::assertTrue(defined('DONOTCACHEPAGE'));
        self::assertTrue(DONOTCACHEPAGE);
    }

    public function test_remembered_host_marker_is_safe_without_wordpress_query_functions(): void
    {
        $page = $this->page();

        $page->markRememberedHostPageDynamic();

        self::assertTrue(true);
    }

    public function test_cache_coherency_does_not_change_route_contract(): void
    {
        self::assertSame('1.0.0', ReadingRoomPage::ROUTE_VERSION);
    }

    public function test_cache_coherency_does_not_change_shortcode_contract(): void
    {
        self::assertSame('great_marketrealm_expansions', ReadingRoomPage::SHORTCODE);
    }

    public function test_cache_coherency_uses_existing_host_page_option(): void
    {
        self::assertSame(
            'gmrexp_reading_room_host_page_id',
            ReadingRoomPage::HOST_PAGE_OPTION
        );
    }
}
