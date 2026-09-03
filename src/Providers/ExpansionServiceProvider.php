<?php
namespace GreatMarketrealmExpansions\Providers;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Application\Container;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;

final class ExpansionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(ExpansionRegistry::class, static fn (Container $container): ExpansionRegistry => new ExpansionRegistry());
        $this->container->singleton(ContentRegistry::class, static fn (Container $container): ContentRegistry => new ContentRegistry());
    }
}
