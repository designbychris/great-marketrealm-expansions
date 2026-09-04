<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Integration;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use GreatMarketrealmExpansions\Integration\Bridge;
use GreatMarketrealmExpansions\Integration\Consumer;
use GreatMarketrealmExpansions\Integration\ConsumerRegistry;
use PHPUnit\Framework\TestCase;

final class BridgeTest extends TestCase
{
    private function bridge(): Bridge
    {
        return new Bridge(
            new Catalogue(new ExpansionRegistry(), new ContentRegistry()),
            new ConsumerRegistry()
        );
    }

    public function test_bridge_exposes_its_api_and_catalogue_capabilities(): void
    {
        $bridge = $this->bridge();
        self::assertSame('1.0.0', $bridge->apiVersion());
        self::assertTrue($bridge->supports('bridge.connect'));
        self::assertTrue($bridge->supports('catalogue.query.tag'));
        self::assertFalse($bridge->supports('catalogue.write'));
    }

    public function test_compatible_consumer_connects_and_receives_catalogue(): void
    {
        $bridge = $this->bridge();
        $connection = $bridge->connect(new Consumer(
            'great-marketrealm-companion',
            'Great MarketRealm Companion',
            '1.0.0',
            '1.0.0',
            '1.0.0',
            ['catalogue.content'],
            ['catalogue.query.tag']
        ));
        self::assertTrue($connection->connected());
        self::assertNotNull($connection->catalogue());
        self::assertTrue($connection->supports('catalogue.query.tag'));
        self::assertSame(['catalogue.content', 'catalogue.query.tag'], $connection->negotiatedCapabilities());
        self::assertSame([], $connection->issues());
    }

    public function test_missing_optional_capability_degrades_gracefully(): void
    {
        $connection = $this->bridge()->connect(new Consumer(
            'tabletop', 'Tabletop', '1.0.0', '1.0.0', '1.0.0',
            ['catalogue.content'], ['catalogue.telepathy']
        ));
        self::assertTrue($connection->connected());
        self::assertSame(['catalogue.telepathy'], $connection->missingOptionalCapabilities());
        self::assertSame([], $connection->issues());
        self::assertFalse($connection->supports('catalogue.telepathy'));
    }

    public function test_missing_required_capability_refuses_connection(): void
    {
        $connection = $this->bridge()->connect(new Consumer(
            'tabletop', 'Tabletop', '1.0.0', '1.0.0', '1.0.0', ['catalogue.write']
        ));
        self::assertFalse($connection->connected());
        self::assertNull($connection->catalogue());
        self::assertSame(['catalogue.write'], $connection->missingRequiredCapabilities());
        self::assertSame('required_capability_missing', $connection->issues()[0]->code());
    }

    public function test_incompatible_bridge_api_refuses_connection(): void
    {
        $connection = $this->bridge()->connect(new Consumer(
            'future-client', 'Future Client', '1.0.0', '2.0.0'
        ));
        self::assertFalse($connection->connected());
        self::assertSame('bridge_api_incompatible', $connection->issues()[0]->code());
    }

    public function test_incompatible_catalogue_api_refuses_connection(): void
    {
        $connection = $this->bridge()->connect(new Consumer(
            'future-client', 'Future Client', '1.0.0', '1.0.0', '2.0.0'
        ));
        self::assertFalse($connection->connected());
        self::assertSame('catalogue_api_incompatible', $connection->issues()[0]->code());
    }

    public function test_consumer_conflict_is_reported_without_throwing(): void
    {
        $bridge = $this->bridge();
        self::assertTrue($bridge->connect(new Consumer('companion', 'Companion', '1.0.0'))->connected());
        $connection = $bridge->connect(new Consumer('companion', 'Companion', '2.0.0'));
        self::assertFalse($connection->connected());
        self::assertSame('consumer_conflict', $connection->issues()[0]->code());
    }

    public function test_connection_serialises_negotiation_state(): void
    {
        $connection = $this->bridge()->connect(new Consumer(
            'companion', 'Companion', '1.0.0', '1.0.0', '1.0.0', [], ['catalogue.query.tag', 'missing.optional']
        ));
        $data = $connection->toArray();
        self::assertTrue($data['connected']);
        self::assertSame('1.0.0', $data['bridge_api_version']);
        self::assertContains('catalogue.query.tag', $data['negotiated_capabilities']);
        self::assertSame(['missing.optional'], $data['missing_optional_capabilities']);
    }
}
