<?php

declare(strict_types=1);

namespace GreatMarketrealmExpansions\Tests\Unit\Frontend\ReadingRoom;

use PHPUnit\Framework\TestCase;

final class ReadingRoomBookshelfRegressionTest extends TestCase
{
    public function test_v12a_turns_library_and_browse_into_face_out_sourcebook_shelves(): void
    {
        $root = dirname(__DIR__, 4);
        $css = file_get_contents($root . '/assets/css/reading-room.css');
        self::assertIsString($css);
        self::assertStringContainsString('Phase V.12A — The Librarian Discovers Books', $css);
        self::assertStringContainsString('content:"ACTIVE ALMANACS"', $css);
        self::assertStringContainsString('content:"THE CATALOGUE SHELVES"', $css);
        self::assertStringContainsString('grid-template-columns:repeat(auto-fill,minmax(250px,300px))', $css);
        self::assertStringContainsString('aspect-ratio:3/2', $css);
    }

    public function test_v12a_makes_cover_art_the_open_almanac_discovery_surface(): void
    {
        $root = dirname(__DIR__, 4);
        $page = file_get_contents($root . '/src/Frontend/ReadingRoom/ReadingRoomPage.php');
        self::assertIsString($page);
        self::assertStringContainsString('gmrexp-reading-room__cover-link', $page);
        self::assertStringContainsString("'Open Almanac: ' . $catalogueExpansion->name()", $page);
        self::assertStringContainsString("'Open Almanac: ' . $entry->name()", $page);
        self::assertStringContainsString('gmrexp-reading-room__cover-placeholder', $page);
    }

    public function test_v12a_keeps_accessibility_and_motion_fallbacks_for_the_physical_book_effect(): void
    {
        $root = dirname(__DIR__, 4);
        $css = file_get_contents($root . '/assets/css/reading-room.css');
        self::assertIsString($css);
        self::assertStringContainsString('@media (prefers-reduced-motion:reduce)', $css);
        self::assertStringContainsString('@media (forced-colors:active)', $css);
        self::assertStringContainsString('.gmrexp-reading-room__cover-link:focus-visible', $css);
    }
}
