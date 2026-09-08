<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Frontend\ReadingRoom;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Expansions\ExpansionPack;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use GreatMarketrealmExpansions\Frontend\ReadingRoom\BrowseShelf;
use GreatMarketrealmExpansions\Library\InMemoryActivationStore;
use GreatMarketrealmExpansions\Library\Library;
use PHPUnit\Framework\TestCase;

final class BrowseShelfTest extends TestCase
{
    private function shelf(): BrowseShelf
    {
        $expansions = new ExpansionRegistry();
        $content = new ContentRegistry();

        $expansions->add(new ExpansionPack('zeta-book', 'Zeta Book', '1.0.0', 'Last alphabetically.'));
        $expansions->add(new ExpansionPack('alpha-book', 'Alpha Book', '2.0.0', 'First alphabetically.', ['artwork' => 'assets/alpha-cover.jpg']));

        $content->add('alpha-book', new ContentDefinition('monster', 'm-1', ['name' => 'Monster One']));
        $content->add('alpha-book', new ContentDefinition('feat', 'f-1', ['name' => 'Feat One']));
        $content->add('alpha-book', new ContentDefinition('feat', 'f-2', ['name' => 'Feat Two']));
        $content->add('zeta-book', new ContentDefinition('spell', 's-1', ['name' => 'Spell One']));

        $catalogue = new Catalogue($expansions, $content);
        $library = new Library(
            $catalogue,
            new InMemoryActivationStore(['zeta-book' => false])
        );

        return new BrowseShelf($catalogue, $library);
    }

    public function test_entries_are_sorted_by_name(): void
    {
        $entries = $this->shelf()->entries();

        self::assertSame(['Alpha Book', 'Zeta Book'], array_map(
            static fn ($entry): string => $entry->name(),
            $entries
        ));
    }

    public function test_entries_preserve_canonical_pack_identity(): void
    {
        $entry = $this->shelf()->entries()[0];

        self::assertSame('alpha-book', $entry->key());
        self::assertSame('2.0.0', $entry->version());
        self::assertSame('First alphabetically.', $entry->description());
    }

    public function test_content_types_are_counted_and_sorted(): void
    {
        $entry = $this->shelf()->entries()[0];

        self::assertSame(['feat' => 2, 'monster' => 1], $entry->contentTypes());
        self::assertSame(3, $entry->entryCount());
    }

    public function test_activation_state_is_read_from_living_library(): void
    {
        $entries = $this->shelf()->entries();

        self::assertTrue($entries[0]->active());
        self::assertFalse($entries[1]->active());
    }

    public function test_compatibility_status_is_read_from_living_library(): void
    {
        foreach ($this->shelf()->entries() as $entry) {
            self::assertSame('ready', $entry->compatibilityStatus());
        }
    }

    public function test_summary_counts_are_derived_from_browse_entries(): void
    {
        $shelf = $this->shelf();

        self::assertSame(2, $shelf->count());
        self::assertSame(1, $shelf->activeCount());
        self::assertSame(4, $shelf->contentCount());
    }

    public function test_empty_catalogue_returns_empty_shelf(): void
    {
        $catalogue = new Catalogue(new ExpansionRegistry(), new ContentRegistry());
        $library = new Library($catalogue, new InMemoryActivationStore());
        $shelf = new BrowseShelf($catalogue, $library);

        self::assertSame([], $shelf->entries());
        self::assertSame(0, $shelf->count());
        self::assertSame(0, $shelf->activeCount());
        self::assertSame(0, $shelf->contentCount());
    }

    public function test_browsing_does_not_change_activation_state(): void
    {
        $expansions = new ExpansionRegistry();
        $content = new ContentRegistry();
        $expansions->add(new ExpansionPack('quiet-book', 'Quiet Book'));
        $catalogue = new Catalogue($expansions, $content);
        $store = new InMemoryActivationStore(['quiet-book' => false]);
        $library = new Library($catalogue, $store);

        (new BrowseShelf($catalogue, $library))->entries();

        self::assertFalse($library->isActive('quiet-book'));
    }

    public function test_pack_metadata_is_preserved_for_browse_presentation(): void
    {
        $entry = $this->shelf()->entries()[0];

        self::assertSame('assets/alpha-cover.jpg', $entry->meta('artwork'));
        self::assertSame(['artwork' => 'assets/alpha-cover.jpg'], $entry->metadata());
        self::assertSame('assets/alpha-cover.jpg', $entry->toArray()['metadata']['artwork']);
    }

}
