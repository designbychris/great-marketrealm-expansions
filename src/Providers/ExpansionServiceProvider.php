<?php
namespace GreatMarketrealmExpansions\Providers;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Application\Container;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use GreatMarketrealmExpansions\Expansions\Loading\ExpansionFileLoader;

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
        $this->container->singleton(ExpansionFileLoader::class, static fn (Container $container): ExpansionFileLoader => new ExpansionFileLoader(
            $container->get(ExpansionRegistry::class),
            $container->get(ContentRegistry::class),
            $container->get(ContentValidator::class)
        ));
    }
}
