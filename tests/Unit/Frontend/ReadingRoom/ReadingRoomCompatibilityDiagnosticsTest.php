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

final class ReadingRoomCompatibilityDiagnosticsTest extends TestCase
{
    /** @param array<string,mixed> $metadata @param array<string,bool> $states */
    private function page(array $metadata = [], array $states = []): array
    {
        $expansions = new ExpansionRegistry();
        $content = new ContentRegistry();
        $expansions->add(new ExpansionPack('fixture-book', 'Fixture Book', '1.2.3', '', $metadata));
        $expansions->add(new ExpansionPack('dependency-book', 'Dependency Book', '1.0.0'));
        $catalogue = new Catalogue($expansions, $content);
        $library = new Library($catalogue, new InMemoryActivationStore($states));
        return [new ReadingRoomPage($catalogue, $library, new ReadingRoomAccess(), new ReadingRoomNavigation()), $library];
    }

    private function renderDetail(ReadingRoomPage $page): string
    {
        $old = $_GET;
        $_GET[ReadingRoomPage::EXPANSION_QUERY_ARG] = 'fixture-book';
        unset($_GET[ReadingRoomPage::CONTENT_TYPE_QUERY_ARG]);
        try {
            return $page->render('browse');
        } finally {
            $_GET = $old;
        }
    }

    public function test_ready_badge_links_to_compatibility_explanation(): void
    {
        [$page] = $this->page();
        $html = $this->renderDetail($page);
        self::assertStringContainsString('href="#gmrexp-compatibility-heading"', $html);
        self::assertStringContainsString('>Ready</a>', $html);
    }

    public function test_ready_detail_explains_zero_issues(): void
    {
        [$page] = $this->page();
        $html = $this->renderDetail($page);
        self::assertStringContainsString('The Librarian is satisfied.', $html);
        self::assertStringContainsString('Nothing further to report.', $html);
        self::assertStringContainsString('No compatibility issues were reported', $html);
    }

    public function test_optional_missing_dependency_renders_degraded_warning(): void
    {
        [$page] = $this->page([
            'dependencies' => [['key' => 'missing-optional', 'required' => false]],
        ]);
        $html = $this->renderDetail($page);
        self::assertStringContainsString('The Librarian has raised an eyebrow.', $html);
        self::assertStringContainsString('optional_dependency_missing', $html);
        self::assertStringContainsString('missing-optional', $html);
    }

    public function test_required_missing_dependency_renders_blocking_issue(): void
    {
        [$page] = $this->page([
            'dependencies' => [['key' => 'missing-required']],
        ]);
        $html = $this->renderDetail($page);
        self::assertStringContainsString('The Librarian has stopped this book at the desk.', $html);
        self::assertStringContainsString('required_dependency_missing', $html);
        self::assertStringContainsString('Blocking', $html);
    }

    public function test_inactive_required_dependency_explains_existing_engine_issue(): void
    {
        [$page] = $this->page([
            'dependencies' => [['key' => 'dependency-book']],
        ], ['dependency-book' => false]);
        self::assertStringContainsString('required_dependency_inactive', $this->renderDetail($page));
    }

    public function test_conflict_explains_existing_engine_issue(): void
    {
        [$page] = $this->page(['conflicts' => ['dependency-book']]);
        self::assertStringContainsString('conflicting_expansion_present', $this->renderDetail($page));
    }

    public function test_consumer_unknown_warning_is_exposed_without_guessing_version(): void
    {
        [$page] = $this->page([
            'compatibility' => ['consumers' => ['great-marketrealm-companion' => '>=1.0.0']],
        ]);
        $html = $this->renderDetail($page);
        self::assertStringContainsString('consumer_version_unknown', $html);
        self::assertStringContainsString('great-marketrealm-companion', $html);
    }

    public function test_rendering_diagnostics_does_not_mutate_activation(): void
    {
        [$page, $library] = $this->page([
            'dependencies' => [['key' => 'missing-required']],
        ], ['fixture-book' => true]);
        $this->renderDetail($page);
        self::assertTrue($library->isActive('fixture-book'));
    }

    public function test_browse_badge_links_to_same_almanac_diagnostics(): void
    {
        [$page] = $this->page();
        $html = $page->render('browse');
        self::assertStringContainsString('gmrexp_expansion=fixture-book#gmrexp-compatibility-heading', html_entity_decode($html));
    }

    public function test_compatibility_url_preserves_existing_reading_room_route_contract(): void
    {
        [$page] = $this->page();
        self::assertSame(
            '/marketrealm-expansions/browse/?gmrexp_expansion=fixture-book#gmrexp-compatibility-heading',
            $page->compatibilityUrl(null, 'fixture-book')
        );
        self::assertSame('1.0.0', ReadingRoomPage::ROUTE_VERSION);
    }
}
