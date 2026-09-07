<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Frontend\ReadingRoom;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Expansions\ExpansionPack;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use GreatMarketrealmExpansions\Frontend\ReadingRoom\ReadingRoomAccess;
use GreatMarketrealmExpansions\Frontend\ReadingRoom\ReadingRoomNavigation;
use GreatMarketrealmExpansions\Frontend\ReadingRoom\ReadingRoomPage;
use GreatMarketrealmExpansions\Library\InMemoryActivationStore;
use GreatMarketrealmExpansions\Library\Library;
use PHPUnit\Framework\TestCase;

final class ReadingRoomExpansionDetailTest extends TestCase
{
    private ReadingRoomPage $page;

    protected function setUp(): void
    {
        $_GET = [];
        $packs = new ExpansionRegistry(); $content = new ContentRegistry();
        $packs->add(new ExpansionPack('fixture-book', 'Fixture Book', '1.2.3', 'Synthetic detail fixture.'));
        $content->add('fixture-book', new ContentDefinition('feat', 'iron-fork', ['name' => 'Iron Fork', 'description' => 'Synthetic feat.', 'tags' => ['fixture']]));
        $content->add('fixture-book', new ContentDefinition('monster', 'tin-beast', ['name' => 'Tin Beast', 'description' => 'Synthetic monster.']));
        $catalogue = new Catalogue($packs, $content); $library = new Library($catalogue, new InMemoryActivationStore());
        $this->page = new ReadingRoomPage($catalogue, $library, new ReadingRoomAccess(), new ReadingRoomNavigation());
    }
    protected function tearDown(): void { $_GET = []; }

    public function test_expansion_query_argument_is_stable(): void { self::assertSame('gmrexp_expansion', ReadingRoomPage::EXPANSION_QUERY_ARG); }
    public function test_content_type_query_argument_is_stable(): void { self::assertSame('gmrexp_type', ReadingRoomPage::CONTENT_TYPE_QUERY_ARG); }
    public function test_expansion_url_stays_inside_shortcode_host(): void
    {
        self::assertSame('https://example.test/expansions/?gmrexp_section=browse&gmrexp_expansion=fixture-book', $this->page->expansionUrl('https://example.test/expansions/', 'fixture-book'));
    }
    public function test_family_url_adds_canonical_type_filter(): void
    {
        self::assertSame('https://example.test/expansions/?gmrexp_section=browse&gmrexp_expansion=fixture-book&gmrexp_type=monster', $this->page->expansionUrl('https://example.test/expansions/', 'fixture-book', 'monster'));
    }
    public function test_browse_card_links_to_open_almanac(): void
    {
        $html = $this->page->render('browse', 'https://example.test/expansions/'); self::assertStringContainsString('Open Almanac', $html); self::assertStringContainsString('gmrexp_expansion=fixture-book', $html);
    }
    public function test_detail_renders_expansion_identity_and_all_families(): void
    {
        $_GET[ReadingRoomPage::EXPANSION_QUERY_ARG] = 'fixture-book'; $html = $this->page->render('browse', 'https://example.test/expansions/');
        self::assertStringContainsString('Open Almanac', $html); self::assertStringContainsString('Fixture Book', $html); self::assertStringContainsString('Iron Fork', $html); self::assertStringContainsString('Tin Beast', $html);
    }
    public function test_detail_renders_canonical_content_id(): void
    {
        $_GET[ReadingRoomPage::EXPANSION_QUERY_ARG] = 'fixture-book'; $html = $this->page->render('browse'); self::assertStringContainsString('fixture-book:feat:iron-fork', $html);
    }
    public function test_family_filter_shows_only_selected_family(): void
    {
        $_GET[ReadingRoomPage::EXPANSION_QUERY_ARG] = 'fixture-book'; $_GET[ReadingRoomPage::CONTENT_TYPE_QUERY_ARG] = 'feat'; $html = $this->page->render('browse');
        self::assertStringContainsString('Iron Fork', $html); self::assertStringNotContainsString('Tin Beast', $html);
    }
    public function test_unknown_family_filter_falls_back_to_all_content(): void
    {
        $_GET[ReadingRoomPage::EXPANSION_QUERY_ARG] = 'fixture-book'; $_GET[ReadingRoomPage::CONTENT_TYPE_QUERY_ARG] = 'banana'; $html = $this->page->render('browse');
        self::assertStringContainsString('Iron Fork', $html); self::assertStringContainsString('Tin Beast', $html);
    }
    public function test_missing_expansion_has_safe_not_found_view(): void
    {
        $_GET[ReadingRoomPage::EXPANSION_QUERY_ARG] = 'missing-book'; $html = $this->page->render('browse', 'https://example.test/expansions/'); self::assertStringContainsString('That Almanac is not on the shelf.', $html); self::assertStringContainsString('Return to Browse', $html);
    }
    public function test_detail_is_read_only_and_contains_no_activation_form(): void
    {
        $_GET[ReadingRoomPage::EXPANSION_QUERY_ARG] = 'fixture-book'; $html = $this->page->render('browse'); self::assertStringNotContainsString('gmrexp_reading_room_activation', $html);
    }
    public function test_route_contract_remains_unchanged(): void { self::assertSame('1.0.0', ReadingRoomPage::ROUTE_VERSION); }
}
