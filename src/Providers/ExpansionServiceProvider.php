<?php
namespace GreatMarketrealmExpansions\Providers;

use GreatMarketrealmExpansions\Almanac\AlmanacProposalService;
use GreatMarketrealmExpansions\Almanac\AlmanacPublicationService;
use GreatMarketrealmExpansions\Almanac\AlmanacMetadataService;
use GreatMarketrealmExpansions\Almanac\AlmanacStorage;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Admin\CatalogueAdminPage;
use GreatMarketrealmExpansions\Application\Container;
use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Catalogue\Rest\CatalogueRestApi;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use GreatMarketrealmExpansions\Expansions\Loading\ExpansionFileLoader;
use GreatMarketrealmExpansions\Integration\Bridge;
use GreatMarketrealmExpansions\Integration\ConsumerRegistry;
use GreatMarketrealmExpansions\Import\ImportService;
use GreatMarketrealmExpansions\Import\GoogleDocsSourceAdapter;
use GreatMarketrealmExpansions\Library\ActivationStore;
use GreatMarketrealmExpansions\Library\Library;
use GreatMarketrealmExpansions\Library\WordPressOptionActivationStore;
use GreatMarketrealmExpansions\Migration\MigrationRegistry;
use GreatMarketrealmExpansions\Migration\MigrationService;
use GreatMarketrealmExpansions\Review\ReviewService;
use GreatMarketrealmExpansions\Review\ReviewQueueStore;
use GreatMarketrealmExpansions\Review\WordPressUserReviewQueueStore;
use GreatMarketrealmExpansions\Frontend\ReadingRoom\ReadingRoomAccess;
use GreatMarketrealmExpansions\Frontend\ReadingRoom\ReadingRoomNavigation;
use GreatMarketrealmExpansions\Frontend\ReadingRoom\ReadingRoomPage;
use GreatMarketrealmExpansions\Rules\RuleEngine;

final class ExpansionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(ExpansionRegistry::class, static fn (Container $container): ExpansionRegistry => new ExpansionRegistry());

        $this->container->singleton(ContentTypeCatalogue::class, static function (Container $container): ContentTypeCatalogue {
            $catalogue = new ContentTypeCatalogue();
            foreach (CoreContentTypes::all() as $type) { $catalogue->add($type); }
            return $catalogue;
        });

        $this->container->singleton(SchemaRegistry::class, static function (Container $container): SchemaRegistry {
            $schemas = new SchemaRegistry();
            CoreSchemas::register($schemas, $container->get(ContentTypeCatalogue::class));
            return $schemas;
        });

        $this->container->singleton(RuleEngine::class, static fn (Container $container): RuleEngine => new RuleEngine());
        $this->container->singleton(ContentValidator::class, static fn (Container $container): ContentValidator => new ContentValidator($container->get(SchemaRegistry::class)));
        $this->container->singleton(ImportService::class, static fn (Container $container): ImportService => new ImportService(
            $container->get(ContentValidator::class)
        ));
        $this->container->singleton(GoogleDocsSourceAdapter::class, static fn (Container $container): GoogleDocsSourceAdapter => new GoogleDocsSourceAdapter());
        $this->container->singleton(ReviewService::class, static fn (Container $container): ReviewService => new ReviewService(
            $container->get(ContentValidator::class)
        ));
        $this->container->singleton(ReviewQueueStore::class, static fn (Container $container): ReviewQueueStore => new WordPressUserReviewQueueStore());
        $this->container->singleton(AlmanacProposalService::class, static fn (Container $container): AlmanacProposalService => new AlmanacProposalService());
        $this->container->singleton(AlmanacPublicationService::class, static fn (Container $container): AlmanacPublicationService => new AlmanacPublicationService(
            $container->get(ContentValidator::class),
            $container->get(ExpansionFileLoader::class)
        ));
        $this->container->singleton(AlmanacMetadataService::class, static fn (Container $container): AlmanacMetadataService => new AlmanacMetadataService());
        $this->container->singleton(AlmanacStorage::class, static fn (Container $container): AlmanacStorage => new AlmanacStorage());
        $this->container->singleton(MigrationRegistry::class, static fn (Container $container): MigrationRegistry => new MigrationRegistry());
        $this->container->singleton(MigrationService::class, static fn (Container $container): MigrationService => new MigrationService(
            $container->get(MigrationRegistry::class),
            $container->get(ContentValidator::class)
        ));
        $this->container->singleton(ContentRegistry::class, static fn (Container $container): ContentRegistry => new ContentRegistry($container->get(ContentValidator::class)));
        $this->container->singleton(Catalogue::class, static fn (Container $container): Catalogue => new Catalogue(
            $container->get(ExpansionRegistry::class),
            $container->get(ContentRegistry::class)
        ));
        $this->container->singleton(CatalogueRestApi::class, static fn (Container $container): CatalogueRestApi => new CatalogueRestApi(
            $container->get(Catalogue::class)
        ));
        $this->container->singleton(ConsumerRegistry::class, static fn (Container $container): ConsumerRegistry => new ConsumerRegistry());
        $this->container->singleton(ActivationStore::class, static fn (Container $container): ActivationStore => new WordPressOptionActivationStore());
        $this->container->singleton(Library::class, static fn (Container $container): Library => new Library(
            $container->get(Catalogue::class),
            $container->get(ActivationStore::class)
        ));
        $this->container->singleton(Bridge::class, static fn (Container $container): Bridge => new Bridge(
            $container->get(Catalogue::class),
            $container->get(ConsumerRegistry::class),
            $container->get(RuleEngine::class),
            $container->get(Library::class)
        ));
        $this->container->singleton(ExpansionFileLoader::class, static fn (Container $container): ExpansionFileLoader => new ExpansionFileLoader(
            $container->get(ExpansionRegistry::class),
            $container->get(ContentRegistry::class),
            $container->get(ContentValidator::class)
        ));
        $this->container->singleton(ReadingRoomAccess::class, static fn (Container $container): ReadingRoomAccess => new ReadingRoomAccess());
        $this->container->singleton(ReadingRoomNavigation::class, static fn (Container $container): ReadingRoomNavigation => new ReadingRoomNavigation());
        $this->container->singleton(ReadingRoomPage::class, static fn (Container $container): ReadingRoomPage => new ReadingRoomPage(
            $container->get(Catalogue::class),
            $container->get(Library::class),
            $container->get(ReadingRoomAccess::class),
            $container->get(ReadingRoomNavigation::class),
            $container->get(ImportService::class),
            $container->get(GoogleDocsSourceAdapter::class),
            $container->get(ReviewService::class),
            $container->get(ReviewQueueStore::class),
            $container->get(AlmanacProposalService::class),
            $container->get(SchemaRegistry::class),
            $container->get(AlmanacPublicationService::class),
            $container->get(AlmanacMetadataService::class),
            $container->get(AlmanacStorage::class)
        ));
        $this->container->singleton(CatalogueAdminPage::class, static fn (Container $container): CatalogueAdminPage => new CatalogueAdminPage(
            $container->get(Catalogue::class),
            $container->get(Bridge::class),
            $container->get(RuleEngine::class),
            $container->get(Library::class),
            $container->get(ImportService::class),
            $container->get(ReviewService::class),
            $container->get(MigrationService::class)
        ));
    }

    public function boot(): void
    {
        if (function_exists('add_action')) {
            $api = $this->container->get(CatalogueRestApi::class);
            add_action('rest_api_init', static function () use ($api): void { $api->registerRoutes(); });

            $readingRoom = $this->container->get(ReadingRoomPage::class);
            $readingRoom->register();

            $admin = $this->container->get(CatalogueAdminPage::class);
            add_action('admin_menu', static function () use ($admin): void { $admin->registerMenu(); });
            add_action('admin_post_gmrexp_set_expansion_activation', static function () use ($admin): void { $admin->handleActivation(); });
        }
    }
}
