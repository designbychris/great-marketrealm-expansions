<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Integration;

use GreatMarketrealmExpansions\Integration\Consumer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ConsumerTest extends TestCase
{
    public function test_consumer_normalises_identity_and_capabilities(): void
    {
        $consumer = new Consumer(
            ' Great MarketRealm Companion ',
            'Great MarketRealm Companion',
            '1.2.3-alpha1',
            '1.0.0',
            '1.0.0',
            ['catalogue.content', 'catalogue.content'],
            ['catalogue.query.tag', 'catalogue.content']
        );
        self::assertSame('great-marketrealm-companion', $consumer->key());
        self::assertSame(['catalogue.content'], $consumer->requiredCapabilities());
        self::assertSame(['catalogue.query.tag'], $consumer->optionalCapabilities());
    }

    public function test_consumer_requires_semantic_api_versions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Consumer('companion', 'Companion', '1.0', 'next');
    }

    public function test_consumer_serialises_contract(): void
    {
        $consumer = new Consumer('tabletop', 'Tabletop', '2.0.0');
        self::assertSame('tabletop', $consumer->toArray()['key']);
        self::assertSame('1.0.0', $consumer->toArray()['minimum_bridge_api_version']);
    }
}
