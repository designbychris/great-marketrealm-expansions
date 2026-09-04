<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Admin;

use GreatMarketrealmExpansions\Admin\CatalogueAdminPage;
use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Expansions\ExpansionPack;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use GreatMarketrealmExpansions\Integration\Bridge;
use GreatMarketrealmExpansions\Integration\ConsumerRegistry;
use PHPUnit\Framework\TestCase;

final class CatalogueAdminPageTest extends TestCase
{
    public function test_summary_reports_loaded_library_without_mutating_it(): void
    {
        $expansions = new ExpansionRegistry();
        $content = new ContentRegistry();
        $expansions->add(new ExpansionPack('fixture-book', 'Fixture Book', '1.0.0'));
        $content->add('fixture-book', new ContentDefinition('feat', 'fixture-feat', ['name' => 'Fixture Feat']));
        $content->add('fixture-book', new ContentDefinition('monster', 'fixture-monster', ['name' => 'Fixture Monster']));
        $catalogue = new Catalogue($expansions, $content);
        $page = new CatalogueAdminPage($catalogue, new Bridge($catalogue, new ConsumerRegistry()));

        $summary = $page->summary();
        self::assertSame('1.0.0', $summary['catalogue_api_version']);
        self::assertSame('1.0.0', $summary['bridge_api_version']);
        self::assertSame(1, $summary['expansion_count']);
        self::assertSame(2, $summary['content_count']);
        self::assertSame(['feat' => 1, 'monster' => 1], $summary['content_types']);
        self::assertCount(2, $catalogue->allContent());
    }

    public function test_menu_slug_is_stable(): void
    {
        self::assertSame('great-marketrealm-expansions', CatalogueAdminPage::MENU_SLUG);
    }
}
