<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Integration;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Expansions\ExpansionPack;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use GreatMarketrealmExpansions\Integration\ActiveContentCatalogue;
use GreatMarketrealmExpansions\Integration\Bridge;
use GreatMarketrealmExpansions\Integration\Consumer;
use GreatMarketrealmExpansions\Integration\ConsumerRegistry;
use GreatMarketrealmExpansions\Library\InMemoryActivationStore;
use GreatMarketrealmExpansions\Library\Library;
use PHPUnit\Framework\TestCase;

final class ActiveContentCatalogueTest extends TestCase
{
    /** @return array{0:ActiveContentCatalogue,1:Library,2:Catalogue} */
    private function fixture(): array
    {
        $expansions = new ExpansionRegistry();
        $content = new ContentRegistry();
        foreach ([['active-book', 'Active Book'], ['sleeping-book', 'Sleeping Book']] as [$key, $name]) {
            $expansions->add(new ExpansionPack($key, $name, '1.0.0'));
        }
        $content->add('active-book', new ContentDefinition('monster', 'shared-beast', ['name' => 'Active Beast', 'provenance' => ['source' => 'fixture']]));
        $content->add('active-book', new ContentDefinition('race', 'traveller', ['name' => 'Traveller']));
        $content->add('sleeping-book', new ContentDefinition('monster', 'shared-beast', ['name' => 'Sleeping Beast']));

        $catalogue = new Catalogue($expansions, $content);
        $activation = new InMemoryActivationStore();
        $library = new Library($catalogue, $activation);
        $library->setActive('active-book', true);
        $library->setActive('sleeping-book', false);

        return [new ActiveContentCatalogue($library), $library, $catalogue];
    }

    public function test_active_view_exposes_only_content_from_active_almanacs(): void
    {
        [$view] = $this->fixture();
        self::assertCount(2, $view->all());
        self::assertSame(['active-book:monster:shared-beast', 'active-book:race:traveller'], array_map(static fn ($entry): string => $entry->id(), $view->all()));
    }

    public function test_consumers_can_request_active_content_by_type(): void
    {
        [$view] = $this->fixture();
        $monsters = $view->ofType('monster');
        self::assertCount(1, $monsters);
        self::assertSame('active-book:monster:shared-beast', $monsters[0]->id());
    }

    public function test_inactive_expansion_is_invisible_even_when_canonical_content_exists(): void
    {
        [$view] = $this->fixture();
        self::assertSame([], $view->fromExpansion('sleeping-book'));
        self::assertNull($view->find('sleeping-book', 'monster', 'shared-beast'));
    }

    public function test_lookup_preserves_fully_qualified_canonical_identity_and_provenance(): void
    {
        [$view] = $this->fixture();
        $entry = $view->find('active-book', 'monster', 'shared-beast');
        self::assertNotNull($entry);
        self::assertSame('active-book:monster:shared-beast', $entry->id());
        self::assertSame(['source' => 'fixture'], $entry->provenance());
    }

    public function test_activation_changes_are_reflected_without_copying_content(): void
    {
        [$view, $library] = $this->fixture();
        self::assertCount(1, $view->ofType('monster'));
        $library->setActive('sleeping-book', true);
        self::assertCount(2, $view->ofType('monster'));
        $library->setActive('active-book', false);
        self::assertSame('sleeping-book:monster:shared-beast', $view->ofType('monster')[0]->id());
    }

    public function test_bridge_negotiates_active_content_capability_for_companion_and_tabletop_style_consumers(): void
    {
        [$view, $library, $catalogue] = $this->fixture();
        $bridge = new Bridge($catalogue, new ConsumerRegistry(), null, $library, $view);
        $connection = $bridge->connect(new Consumer(
            'great-marketrealm-companion', 'Great MarketRealm Companion', '1.0.0', '1.0.0', '1.0.0', ['consumer-content.active']
        ));
        self::assertTrue($connection->connected());
        self::assertTrue($connection->supports('consumer-content.active'));
        self::assertSame($view, $connection->activeContent());
        self::assertSame('1.0.0', $connection->toArray()['active_content_api_version']);
    }

    public function test_legacy_bridge_without_library_exposes_no_active_content_service(): void
    {
        [, , $catalogue] = $this->fixture();
        $bridge = new Bridge($catalogue, new ConsumerRegistry());
        self::assertFalse($bridge->supports('consumer-content.active'));
        self::assertNull($bridge->connect(new Consumer('legacy', 'Legacy', '1.0.0'))->activeContent());
    }
}
