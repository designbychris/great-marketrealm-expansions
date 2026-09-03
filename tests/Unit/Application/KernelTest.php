<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Application;

use GreatMarketrealmExpansions\Application\Container;
use GreatMarketrealmExpansions\Application\Kernel;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use PHPUnit\Framework\TestCase;

final class KernelTest extends TestCase
{
    public function test_kernel_is_a_singleton(): void { self::assertSame(Kernel::instance(), Kernel::instance()); }

    public function test_boot_registers_core_registries(): void
    {
        $k = Kernel::instance(); $k->boot();
        self::assertTrue($k->isBooted()); self::assertInstanceOf(Container::class, $k->container());
        self::assertInstanceOf(ExpansionRegistry::class, $k->expansions()); self::assertInstanceOf(ContentRegistry::class, $k->content());
    }

    public function test_boot_is_idempotent(): void
    {
        $k = Kernel::instance(); $k->boot(); $first = $k->expansions(); $k->boot(); self::assertSame($first, $k->expansions());
    }
}
