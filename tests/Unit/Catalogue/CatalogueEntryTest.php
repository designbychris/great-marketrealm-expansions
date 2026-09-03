<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Catalogue;

use GreatMarketrealmExpansions\Catalogue\CatalogueEntry;
use GreatMarketrealmExpansions\Content\ContentDefinition;
use PHPUnit\Framework\TestCase;

final class CatalogueEntryTest extends TestCase
{
    public function test_entry_exposes_snapshot_data_and_canonical_identity(): void
    {
        $entry = CatalogueEntry::fromDefinition('book', new ContentDefinition('spell', 'soup-bolt', [
            'name' => 'Soup Bolt',
            'tags' => ['arcane', 123],
            'provenance' => ['page' => 12],
            'compatibility' => ['ruleset' => 'great-marketrealm'],
        ]));

        self::assertSame('book:spell:soup-bolt', $entry->id());
        self::assertSame(['arcane'], $entry->tags());
        self::assertSame(['page' => 12], $entry->provenance());
        self::assertSame('great-marketrealm', $entry->compatibility()['ruleset']);
        self::assertSame('book', $entry->toArray()['expansion']);
        self::assertFalse(method_exists($entry, 'setData'));
    }
}
