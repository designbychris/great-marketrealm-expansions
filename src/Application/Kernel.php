<?php
namespace GreatMarketrealmExpansions\Application;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Admin\CatalogueAdminPage;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use GreatMarketrealmExpansions\Expansions\Loading\ExpansionFileLoader;
use GreatMarketrealmExpansions\Integration\Bridge;
use GreatMarketrealmExpansions\Providers\ExpansionServiceProvider;
use GreatMarketrealmExpansions\Providers\ServiceProvider;

final class Kernel
{
    private static ?self $instance = null;
    private Container $container;
    /** @var list<ServiceProvider> */
    private array $providers = [];
    private bool $booted = false;

    private function __construct()
    {
        $this->container = new Container();
        $this->container->instance(self::class, $this);
        $this->container->instance(Container::class, $this->container);
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->providers = [new ExpansionServiceProvider($this->container)];
        foreach ($this->providers as $provider) { $provider->register(); }
        foreach ($this->providers as $provider) { $provider->boot(); }
        $this->booted = true;

        if (function_exists('do_action')) {
            do_action('gmrexp/booted', $this);
        }
    }

    public function container(): Container { return $this->container; }
    public function expansions(): ExpansionRegistry { return $this->container->get(ExpansionRegistry::class); }
    public function content(): ContentRegistry { return $this->container->get(ContentRegistry::class); }
    public function contentTypes(): ContentTypeCatalogue { return $this->container->get(ContentTypeCatalogue::class); }
    public function schemas(): SchemaRegistry { return $this->container->get(SchemaRegistry::class); }
    public function catalogue(): Catalogue { return $this->container->get(Catalogue::class); }
    public function loader(): ExpansionFileLoader { return $this->container->get(ExpansionFileLoader::class); }
    public function bridge(): Bridge { return $this->container->get(Bridge::class); }
    public function adminPage(): CatalogueAdminPage { return $this->container->get(CatalogueAdminPage::class); }
    public function isBooted(): bool { return $this->booted; }
}
