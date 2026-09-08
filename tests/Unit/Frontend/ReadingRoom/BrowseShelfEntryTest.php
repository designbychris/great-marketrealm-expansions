<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Frontend\ReadingRoom;

use GreatMarketrealmExpansions\Frontend\ReadingRoom\BrowseShelfEntry;
use PHPUnit\Framework\TestCase;

final class BrowseShelfEntryTest extends TestCase
{
    public function test_entry_exposes_read_only_browse_fields(): void
    {
        $entry = new BrowseShelfEntry(
            'synthetic-book',
            'Synthetic Book',
            '2.3.4',
            'A fixture.',
            true,
            'ready',
            3,
            ['feat' => 2, 'monster' => 1]
        );

        self::assertSame('synthetic-book', $entry->key());
        self::assertSame('Synthetic Book', $entry->name());
        self::assertSame('2.3.4', $entry->version());
        self::assertSame('A fixture.', $entry->description());
        self::assertTrue($entry->active());
        self::assertSame('ready', $entry->compatibilityStatus());
        self::assertSame(3, $entry->entryCount());
        self::assertSame(['feat' => 2, 'monster' => 1], $entry->contentTypes());
    }

    public function test_entry_serialises_without_domain_objects(): void
    {
        $entry = new BrowseShelfEntry(
            'fixture',
            'Fixture',
            '1.0.0',
            '',
            false,
            'degraded',
            0,
            []
        );

        self::assertSame([
            'key' => 'fixture',
            'name' => 'Fixture',
            'version' => '1.0.0',
            'description' => '',
            'active' => false,
            'compatibility_status' => 'degraded',
            'entry_count' => 0,
            'content_types' => [],
            'metadata' => [],
        ], $entry->toArray());
    }

    public function test_metadata_defaults_empty_for_backwards_compatible_construction(): void
    {
        $entry = new BrowseShelfEntry(
            'fixture-book',
            'Fixture Book',
            '1.0.0',
            'Fixture.',
            true,
            'ready',
            0,
            []
        );

        self::assertSame([], $entry->metadata());
        self::assertNull($entry->meta('artwork'));
    }

}
