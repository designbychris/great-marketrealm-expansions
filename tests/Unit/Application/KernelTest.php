<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Application;

use GreatMarketrealmExpansions\Admin\CatalogueAdminPage;
use GreatMarketrealmExpansions\Application\Container;
use GreatMarketrealmExpansions\Application\Kernel;
use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use GreatMarketrealmExpansions\Expansions\Loading\ExpansionFileLoader;
use GreatMarketrealmExpansions\Integration\Bridge;
use GreatMarketrealmExpansions\Rules\RuleEngine;
use PHPUnit\Framework\TestCase;

final class KernelTest extends TestCase
{
    public function test_kernel_is_a_singleton(): void { self::assertSame(Kernel::instance(), Kernel::instance()); }

    public function test_boot_registers_core_registries(): void
    {
        $k = Kernel::instance(); $k->boot();
        self::assertTrue($k->isBooted()); self::assertInstanceOf(Container::class, $k->container());
        self::assertInstanceOf(ExpansionRegistry::class, $k->expansions()); self::assertInstanceOf(ContentRegistry::class, $k->content());
        self::assertInstanceOf(ContentTypeCatalogue::class, $k->contentTypes()); self::assertInstanceOf(SchemaRegistry::class, $k->schemas());
        self::assertInstanceOf(ExpansionFileLoader::class, $k->loader()); self::assertInstanceOf(Catalogue::class, $k->catalogue()); self::assertInstanceOf(Bridge::class, $k->bridge()); self::assertInstanceOf(RuleEngine::class, $k->rules());
        self::assertInstanceOf(CatalogueAdminPage::class, $k->adminPage());
        self::assertCount(20, $k->contentTypes()->all()); self::assertCount(20, $k->schemas()->all());
    }

    public function test_public_catalogue_helper_returns_kernel_catalogue(): void
    {
        $k = Kernel::instance(); $k->boot();
        self::assertSame($k->catalogue(), \GreatMarketrealmExpansions\catalogue());
    }


    public function test_public_bridge_helper_returns_kernel_bridge(): void
    {
        $k = Kernel::instance(); $k->boot();
        self::assertSame($k->bridge(), \GreatMarketrealmExpansions\bridge());
    }


    public function test_public_rules_helper_returns_kernel_rules_engine(): void
    {
        $k = Kernel::instance(); $k->boot();
        self::assertSame($k->rules(), \GreatMarketrealmExpansions\rules());
    }

    public function test_boot_is_idempotent(): void
    {
        $k = Kernel::instance(); $k->boot(); $first = $k->expansions(); $k->boot(); self::assertSame($first, $k->expansions());
    }
}
