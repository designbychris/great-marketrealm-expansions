<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Frontend\ReadingRoom;

use PHPUnit\Framework\TestCase;

final class ReadingRoomCoverStandardTest extends TestCase
{
    public function test_reading_room_uses_three_by_two_landscape_cover_frame(): void
    {
        $root = dirname(__DIR__, 4);
        $css = file_get_contents($root . '/assets/css/reading-room.css');

        self::assertIsString($css);
        self::assertStringContainsString('aspect-ratio:3/2', $css);
        self::assertStringContainsString('object-fit:cover', $css);
    }

    public function test_cover_standard_documents_reusable_artwork_contract_and_shelf_boundary(): void
    {
        $root = dirname(__DIR__, 4);
        $document = file_get_contents($root . '/docs/ALMANAC-COVER-STANDARD.md');

        self::assertIsString($document);
        self::assertStringContainsString('1800 × 1200 px', $document);
        self::assertStringContainsString('Your Library = active Almanacs. Browse = every available installed Almanac.', $document);
    }
}
