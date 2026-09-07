<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Library;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Expansions\ExpansionPack;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use GreatMarketrealmExpansions\Library\InMemoryActivationStore;
use GreatMarketrealmExpansions\Library\Library;
use GreatMarketrealmExpansions\Library\LibraryExpansion;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LibraryTest extends TestCase
{
    /** @return array{Library,InMemoryActivationStore} */
    private function library(array $states = []): array
    {
        $expansions = new ExpansionRegistry();
        $content = new ContentRegistry();

        $expansions->add(new ExpansionPack('alpha-pack', 'Alpha Pack', '1.0.0'));
        $expansions->add(new ExpansionPack('beta-pack', 'Beta Pack', '2.0.0'));

        $content->add('alpha-pack', new ContentDefinition('feat', 'alpha-feat', ['name' => 'Alpha Feat']));
        $content->add('alpha-pack', new ContentDefinition('monster', 'alpha-monster', ['name' => 'Alpha Monster']));
        $content->add('beta-pack', new ContentDefinition('monster', 'beta-monster', ['name' => 'Beta Monster']));

        $store = new InMemoryActivationStore($states);
        return [new Library(new Catalogue($expansions, $content), $store), $store];
    }

    public function test_library_exposes_stable_api_and_capabilities(): void
    {
        [$library] = $this->library();

        self::assertSame('1.0.0', $library->apiVersion());
        self::assertTrue($library->supports('library.expansions'));
        self::assertTrue($library->supports('library.activation.read'));
        self::assertTrue($library->supports('library.activation.write'));
        self::assertTrue($library->supports('library.content.active'));
        self::assertFalse($library->supports('library.telepathy'));
    }

    public function test_installed_expansions_default_to_active_for_backwards_compatibility(): void
    {
        [$library] = $this->library();

        self::assertTrue($library->isActive('alpha-pack'));
        self::assertTrue($library->isActive('beta-pack'));
    }

    public function test_missing_expansion_is_neither_installed_nor_active(): void
    {
        [$library] = $this->library();

        self::assertFalse($library->isInstalled('missing-pack'));
        self::assertFalse($library->isActive('missing-pack'));
    }

    public function test_expansions_are_returned_as_library_views(): void
    {
        [$library] = $this->library(['beta-pack' => false]);
        $expansions = $library->expansions();

        self::assertSame(['alpha-pack', 'beta-pack'], array_keys($expansions));
        self::assertInstanceOf(LibraryExpansion::class, $expansions['alpha-pack']);
        self::assertTrue($expansions['alpha-pack']->active());
        self::assertFalse($expansions['beta-pack']->active());
        self::assertSame('Beta Pack', $expansions['beta-pack']->name());
    }

    public function test_active_and_inactive_views_are_separated(): void
    {
        [$library] = $this->library(['beta-pack' => false]);

        self::assertSame(['alpha-pack'], array_keys($library->activeExpansions()));
        self::assertSame(['beta-pack'], array_keys($library->inactiveExpansions()));
    }

    public function test_activation_can_be_changed_without_mutating_catalogue(): void
    {
        [$library] = $this->library();

        $library->setActive('alpha-pack', false);

        self::assertFalse($library->isActive('alpha-pack'));
        self::assertTrue($library->isInstalled('alpha-pack'));
        self::assertNotNull($library->expansions()['alpha-pack']->expansion());
    }

    public function test_reactivation_is_supported(): void
    {
        [$library] = $this->library(['alpha-pack' => false]);

        $library->setActive('alpha-pack', true);

        self::assertTrue($library->isActive('alpha-pack'));
    }

    public function test_setting_unknown_expansion_activation_is_rejected(): void
    {
        [$library] = $this->library();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not installed');
        $library->setActive('missing-pack', false);
    }

    public function test_active_content_only_contains_active_expansions(): void
    {
        [$library] = $this->library(['beta-pack' => false]);

        $ids = array_map(static fn ($entry): string => $entry->id(), $library->activeContent());

        self::assertSame([
            'alpha-pack:feat:alpha-feat',
            'alpha-pack:monster:alpha-monster',
        ], $ids);
    }

    public function test_reactivated_pack_content_returns_to_active_content(): void
    {
        [$library] = $this->library(['beta-pack' => false]);
        $library->setActive('beta-pack', true);

        self::assertCount(3, $library->activeContent());
        self::assertSame('beta-pack:monster:beta-monster', $library->activeContent()[2]->id());
    }

    public function test_activation_store_normalises_keys(): void
    {
        $store = new InMemoryActivationStore();
        $store->set('  Alpha Pack  ', false);

        self::assertFalse($store->state('alpha-pack'));
        self::assertSame(['alpha-pack' => false], $store->all());
    }

    public function test_library_expansion_serialises_activation_state(): void
    {
        [$library] = $this->library(['beta-pack' => false]);

        $data = $library->expansions()['beta-pack']->toArray();

        self::assertSame('beta-pack', $data['key']);
        self::assertSame('2.0.0', $data['version']);
        self::assertFalse($data['active']);
    }
}
