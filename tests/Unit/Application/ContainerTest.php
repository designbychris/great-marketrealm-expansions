<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Application;

use GreatMarketrealmExpansions\Application\Container;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ContainerTest extends TestCase
{
    public function test_it_stores_instances(): void
    {
        $c = new Container(); $object = new \stdClass(); $c->instance('thing', $object);
        self::assertTrue($c->has('thing')); self::assertSame($object, $c->get('thing'));
    }

    public function test_singletons_are_resolved_once(): void
    {
        $c = new Container(); $calls = 0;
        $c->singleton('thing', static function () use (&$calls): \stdClass { $calls++; return new \stdClass(); });
        self::assertSame($c->get('thing'), $c->get('thing')); self::assertSame(1, $calls);
    }

    public function test_unknown_services_throw(): void
    {
        $this->expectException(InvalidArgumentException::class); (new Container())->get('missing');
    }
}
