<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Admin;

use GreatMarketrealmExpansions\Admin\CatalogueAdminPage;
use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use GreatMarketrealmExpansions\Expansions\ExpansionPack;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use GreatMarketrealmExpansions\Integration\Bridge;
use GreatMarketrealmExpansions\Integration\ConsumerRegistry;
use GreatMarketrealmExpansions\Import\ImportService;
use GreatMarketrealmExpansions\Library\InMemoryActivationStore;
use GreatMarketrealmExpansions\Library\Library;
use GreatMarketrealmExpansions\Review\ReviewService;
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
        self::assertSame('1.0.0', $summary['rules_api_version']);
        self::assertSame(1, $summary['expansion_count']);
        self::assertSame(2, $summary['content_count']);
        self::assertSame(['feat' => 1, 'monster' => 1], $summary['content_types']);
        self::assertCount(2, $catalogue->allContent());
    }


    public function test_summary_reports_library_api_and_active_pack_count(): void
    {
        $expansions = new ExpansionRegistry();
        $expansions->add(new ExpansionPack('alpha-pack', 'Alpha Pack'));
        $expansions->add(new ExpansionPack('beta-pack', 'Beta Pack'));
        $catalogue = new Catalogue($expansions, new ContentRegistry());
        $library = new Library($catalogue, new InMemoryActivationStore(['beta-pack' => false]));
        $page = new CatalogueAdminPage(
            $catalogue,
            new Bridge($catalogue, new ConsumerRegistry(), null, $library),
            null,
            $library
        );

        $summary = $page->summary();

        self::assertSame('1.0.0', $summary['library_api_version']);
        self::assertSame(2, $summary['expansion_count']);
        self::assertSame(1, $summary['active_expansion_count']);
    }


    public function test_summary_reports_ready_degraded_and_blocked_compatibility_counts(): void
    {
        $expansions = new ExpansionRegistry();
        $expansions->add(new ExpansionPack('ready-pack', 'Ready Pack'));
        $expansions->add(new ExpansionPack('degraded-pack', 'Degraded Pack', '1.0.0', '', [
            'dependencies' => [
                ['key' => 'optional-missing', 'required' => false],
            ],
        ]));
        $expansions->add(new ExpansionPack('blocked-pack', 'Blocked Pack', '1.0.0', '', [
            'dependencies' => [
                ['key' => 'required-missing'],
            ],
        ]));

        $catalogue = new Catalogue($expansions, new ContentRegistry());
        $library = new Library($catalogue, new InMemoryActivationStore());
        $page = new CatalogueAdminPage(
            $catalogue,
            new Bridge($catalogue, new ConsumerRegistry(), null, $library),
            null,
            $library
        );

        self::assertSame(
            ['ready' => 1, 'degraded' => 1, 'blocked' => 1],
            $page->summary()['compatibility']
        );
    }


    public function test_summary_reports_import_and_review_api_versions_when_services_are_available(): void
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) {
            $types->add($type);
        }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        $validator = new ContentValidator($schemas);

        $catalogue = new Catalogue(new ExpansionRegistry(), new ContentRegistry());
        $page = new CatalogueAdminPage(
            $catalogue,
            new Bridge($catalogue, new ConsumerRegistry()),
            null,
            null,
            new ImportService($validator),
            new ReviewService($validator)
        );

        $summary = $page->summary();

        self::assertSame('1.0.0', $summary['import_api_version']);
        self::assertSame('1.0.0', $summary['review_api_version']);
    }

    public function test_menu_slug_is_stable(): void
    {
        self::assertSame('great-marketrealm-expansions', CatalogueAdminPage::MENU_SLUG);
    }
}
