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

final class ReadingRoomHostRoutingTest extends TestCase
{
    private function page(): ReadingRoomPage
    {
        $expansions = new ExpansionRegistry();
        $content = new ContentRegistry();

        $expansions->add(new ExpansionPack(
            'fixture-book',
            'Fixture Book',
            '1.0.0',
            'Synthetic host-routing fixture.'
        ));
        $content->add(
            'fixture-book',
            new ContentDefinition('feat', 'fixture-feat', ['name' => 'Fixture Feat'])
        );

        $catalogue = new Catalogue($expansions, $content);
        $library = new Library($catalogue, new InMemoryActivationStore());

        return new ReadingRoomPage(
            $catalogue,
            $library,
            new ReadingRoomAccess(),
            new ReadingRoomNavigation()
        );
    }

    protected function tearDown(): void
    {
        unset($_GET[ReadingRoomPage::SECTION_QUERY_ARG]);
    }

    public function test_section_query_argument_name_is_stable(): void
    {
        self::assertSame('gmrexp_section', ReadingRoomPage::SECTION_QUERY_ARG);
    }

    public function test_host_page_option_name_is_stable(): void
    {
        self::assertSame('gmrexp_reading_room_host_page_id', ReadingRoomPage::HOST_PAGE_OPTION);
    }

    public function test_library_section_uses_clean_host_page_url(): void
    {
        self::assertSame(
            'https://example.test/expansions/',
            $this->page()->sectionUrl(
                'https://example.test/expansions/?gmrexp_section=browse',
                'library'
            )
        );
    }

    public function test_browse_section_stays_inside_shortcode_host_page(): void
    {
        self::assertSame(
            'https://example.test/expansions/?gmrexp_section=browse',
            $this->page()->sectionUrl('https://example.test/expansions/', 'browse')
        );
    }

    public function test_section_url_preserves_unrelated_query_arguments(): void
    {
        self::assertSame(
            'https://example.test/expansions/?preview=1&gmrexp_section=review',
            $this->page()->sectionUrl(
                'https://example.test/expansions/?preview=1&gmrexp_section=browse',
                'review'
            )
        );
    }

    public function test_rendered_navigation_uses_shortcode_host_page_when_supplied(): void
    {
        $html = $this->page()->render('library', 'https://example.test/expansions/');

        self::assertStringContainsString(
            'href="https://example.test/expansions/?gmrexp_section=browse"',
            $html
        );
        self::assertStringContainsString(
            'href="https://example.test/expansions/?gmrexp_section=import"',
            $html
        );
        self::assertStringNotContainsString(
            'href="/marketrealm-expansions/browse/"',
            $html
        );
    }

    public function test_library_navigation_link_returns_to_plain_host_page(): void
    {
        $html = $this->page()->render(
            'browse',
            'https://example.test/expansions/?gmrexp_section=browse'
        );

        self::assertStringContainsString(
            'href="https://example.test/expansions/"',
            $html
        );
    }

    public function test_reserved_desk_back_link_returns_to_shortcode_host_page(): void
    {
        $html = $this->page()->render('review', 'https://example.test/expansions/');

        self::assertStringContainsString(
            'href="https://example.test/expansions/">Return to Your Library</a>',
            $html
        );
    }

    public function test_shortcode_request_query_selects_browse_desk(): void
    {
        $_GET[ReadingRoomPage::SECTION_QUERY_ARG] = 'browse';

        $html = $this->page()->shortcode([]);

        self::assertStringContainsString('data-section="browse"', $html);
        self::assertStringContainsString('Browse Installed Expansions', $html);
    }

    public function test_explicit_shortcode_section_overrides_request_query(): void
    {
        $_GET[ReadingRoomPage::SECTION_QUERY_ARG] = 'browse';

        $html = $this->page()->shortcode(['section' => 'review']);

        self::assertStringContainsString('data-section="review"', $html);
        self::assertStringContainsString('Review Desk', $html);
    }

    public function test_unknown_request_section_falls_back_to_library(): void
    {
        $_GET[ReadingRoomPage::SECTION_QUERY_ARG] = 'pippins-secret-door';

        $html = $this->page()->shortcode([]);

        self::assertStringContainsString('data-section="library"', $html);
        self::assertStringContainsString('Your Library', $html);
    }

    public function test_legacy_route_contract_remains_version_one(): void
    {
        self::assertSame('1.0.0', ReadingRoomPage::ROUTE_VERSION);
    }
}
