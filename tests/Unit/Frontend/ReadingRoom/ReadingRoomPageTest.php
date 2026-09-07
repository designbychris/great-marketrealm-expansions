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

final class ReadingRoomPageTest extends TestCase
{
    private function page(bool $withExpansion = true): ReadingRoomPage
    {
        $expansions = new ExpansionRegistry();
        $content = new ContentRegistry();

        if ($withExpansion) {
            $expansions->add(new ExpansionPack(
                'fixture-book',
                'Fixture Book',
                '1.2.3',
                'A synthetic Almanac used only by PHPUnit.'
            ));
            $content->add(
                'fixture-book',
                new ContentDefinition('feat', 'fixture-feat', ['name' => 'Fixture Feat'])
            );
        }

        $catalogue = new Catalogue($expansions, $content);
        $library = new Library($catalogue, new InMemoryActivationStore());

        return new ReadingRoomPage(
            $catalogue,
            $library,
            new ReadingRoomAccess(),
            new ReadingRoomNavigation()
        );
    }

    public function test_public_shortcode_name_is_stable(): void
    {
        self::assertSame('great_marketrealm_expansions', ReadingRoomPage::SHORTCODE);
    }

    public function test_query_var_is_stable(): void
    {
        self::assertSame('gmrexp_reading_room', ReadingRoomPage::QUERY_VAR);
    }

    public function test_route_version_is_independent_from_plugin_release_version(): void
    {
        self::assertSame('1.0.0', ReadingRoomPage::ROUTE_VERSION);
    }

    public function test_query_var_is_added_once(): void
    {
        $page = $this->page();

        self::assertSame(
            ['existing', ReadingRoomPage::QUERY_VAR],
            $page->queryVars(['existing'])
        );
        self::assertSame(
            [ReadingRoomPage::QUERY_VAR],
            $page->queryVars([ReadingRoomPage::QUERY_VAR])
        );
    }

    public function test_library_render_contains_reading_room_shell_and_summary(): void
    {
        $html = $this->page()->render('library');

        self::assertStringContainsString('The Reading Room', $html);
        self::assertStringContainsString('Your Library', $html);
        self::assertStringContainsString('Installed Almanacs', $html);
        self::assertStringContainsString('Catalogue Entries', $html);
    }

    public function test_library_render_reads_real_catalogue_pack_identity(): void
    {
        $html = $this->page()->render('library');

        self::assertStringContainsString('Fixture Book', $html);
        self::assertStringContainsString('fixture-book', $html);
        self::assertStringContainsString('Version 1.2.3', $html);
        self::assertStringContainsString('A synthetic Almanac used only by PHPUnit.', $html);
    }

    public function test_library_render_contains_stable_future_navigation(): void
    {
        $html = $this->page()->render('library');

        self::assertStringContainsString('/marketrealm-expansions/browse/', $html);
        self::assertStringContainsString('/marketrealm-expansions/import/', $html);
        self::assertStringContainsString('/marketrealm-expansions/review/', $html);
        self::assertStringContainsString('Coming later', $html);
        self::assertStringNotContainsString('Browse</span><small>Coming later', $html);
    }


    public function test_browse_route_renders_installed_expansion_shelf(): void
    {
        $html = $this->page()->render('browse');

        self::assertStringContainsString('data-section="browse"', $html);
        self::assertStringContainsString('Browse Installed Expansions', $html);
        self::assertStringContainsString('Fixture Book', $html);
        self::assertStringContainsString('fixture-book', $html);
    }

    public function test_browse_route_shows_content_family_counts_and_v4_open_link(): void
    {
        $html = $this->page()->render('browse');

        self::assertStringContainsString('Contents', $html);
        self::assertStringContainsString('Feat', $html);
        self::assertStringContainsString('Open Almanac', $html);
        self::assertStringContainsString('gmrexp_expansion=fixture-book', $html);
    }

    public function test_browse_route_exposes_read_only_state_labels(): void
    {
        $html = $this->page()->render('browse');

        self::assertStringContainsString('Library state', $html);
        self::assertStringContainsString('Compatibility', $html);
        self::assertStringContainsString('Active', $html);
        self::assertStringContainsString('Ready', $html);
    }

    public function test_browse_route_has_graceful_empty_state(): void
    {
        $html = $this->page(false)->render('browse');

        self::assertStringContainsString('Not a book in sight.', $html);
        self::assertStringContainsString('Browse shelf has nothing to display yet', $html);
    }

    public function test_browse_is_no_longer_a_reserved_placeholder(): void
    {
        $html = $this->page()->render('browse');

        self::assertStringNotContainsString('Reserved desk', $html);
        self::assertStringNotContainsString('No placeholder action mutates', $html);
    }

    public function test_future_route_renders_non_mutating_placeholder(): void
    {
        $html = $this->page()->render('import');

        self::assertStringContainsString('Reserved desk', $html);
        self::assertStringContainsString('Import Desk', $html);
        self::assertStringContainsString('No placeholder action mutates', $html);
        self::assertStringNotContainsString('Fixture Book', $html);
    }

    public function test_unknown_section_falls_back_to_library(): void
    {
        $html = $this->page()->render('totally-unknown');

        self::assertStringContainsString('data-section="library"', $html);
        self::assertStringContainsString('Fixture Book', $html);
    }

    public function test_empty_library_has_graceful_empty_state(): void
    {
        $html = $this->page(false)->render('library');

        self::assertStringContainsString('The shelves are waiting.', $html);
        self::assertStringContainsString('No expansion packs are currently loaded', $html);
    }

    public function test_render_is_read_only_for_library_activation_state(): void
    {
        $expansions = new ExpansionRegistry();
        $content = new ContentRegistry();
        $expansions->add(new ExpansionPack('inactive-book', 'Inactive Book'));
        $catalogue = new Catalogue($expansions, $content);
        $store = new InMemoryActivationStore(['inactive-book' => false]);
        $library = new Library($catalogue, $store);

        $page = new ReadingRoomPage(
            $catalogue,
            $library,
            new ReadingRoomAccess(),
            new ReadingRoomNavigation()
        );

        $page->render('library');

        self::assertFalse($library->isActive('inactive-book'));
    }

    public function test_shortcode_defaults_to_library(): void
    {
        $html = $this->page()->shortcode([]);

        self::assertStringContainsString('data-section="library"', $html);
    }

    public function test_shortcode_can_target_reserved_section(): void
    {
        $html = $this->page()->shortcode(['section' => 'review']);

        self::assertStringContainsString('data-section="review"', $html);
        self::assertStringContainsString('Review Desk', $html);
    }
}
