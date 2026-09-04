<?php
namespace GreatMarketrealmExpansions\Providers;

defined('ABSPATH') || exit;

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

        $this->container->singleton(ContentValidator::class, static fn (Container $container): ContentValidator => new ContentValidator($container->get(SchemaRegistry::class)));
        $this->container->singleton(ContentRegistry::class, static fn (Container $container): ContentRegistry => new ContentRegistry($container->get(ContentValidator::class)));
        $this->container->singleton(Catalogue::class, static fn (Container $container): Catalogue => new Catalogue(
            $container->get(ExpansionRegistry::class),
            $container->get(ContentRegistry::class)
        ));
        $this->container->singleton(CatalogueRestApi::class, static fn (Container $container): CatalogueRestApi => new CatalogueRestApi(
            $container->get(Catalogue::class)
        ));
        $this->container->singleton(ConsumerRegistry::class, static fn (Container $container): ConsumerRegistry => new ConsumerRegistry());
        $this->container->singleton(Bridge::class, static fn (Container $container): Bridge => new Bridge(
            $container->get(Catalogue::class),
            $container->get(ConsumerRegistry::class)
        ));
        $this->container->singleton(ExpansionFileLoader::class, static fn (Container $container): ExpansionFileLoader => new ExpansionFileLoader(
            $container->get(ExpansionRegistry::class),
            $container->get(ContentRegistry::class),
            $container->get(ContentValidator::class)
        ));
    }

    public function boot(): void
    {
        if (function_exists('add_action')) {
            $api = $this->container->get(CatalogueRestApi::class);
            add_action('rest_api_init', static function () use ($api): void { $api->registerRoutes(); });
        }
    }
}
