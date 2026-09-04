<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Integration;

use GreatMarketrealmExpansions\Integration\Consumer;
use GreatMarketrealmExpansions\Integration\ConsumerRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ConsumerRegistryTest extends TestCase
{
    public function test_registration_is_idempotent_for_identical_consumer(): void
    {
        $registry = new ConsumerRegistry();
        $consumer = new Consumer('companion', 'Companion', '1.0.0');
        $registry->register($consumer); $registry->register($consumer);
        self::assertCount(1, $registry->all());
        self::assertSame($consumer, $registry->get('companion'));
    }

    public function test_conflicting_registration_is_rejected(): void
    {
        $registry = new ConsumerRegistry();
        $registry->register(new Consumer('companion', 'Companion', '1.0.0'));
        $this->expectException(InvalidArgumentException::class);
        $registry->register(new Consumer('companion', 'Companion', '2.0.0'));
    }
}
