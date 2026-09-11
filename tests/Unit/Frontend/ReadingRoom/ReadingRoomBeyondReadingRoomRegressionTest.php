<?php

declare(strict_types=1);

namespace GreatMarketrealmExpansions\Tests\Unit\Frontend\ReadingRoom;

use PHPUnit\Framework\TestCase;

final class ReadingRoomBeyondReadingRoomRegressionTest extends TestCase
{
    public function test_v12b_replaces_literal_shelves_with_featured_expansion_and_gallery_surfaces(): void
    {
        $root = dirname(__DIR__, 4);
        $page = file_get_contents($root . '/src/Frontend/ReadingRoom/ReadingRoomPage.php');
        self::assertIsString($page);
        self::assertStringContainsString('gmrexp-reading-room__feature', $page);
        self::assertStringContainsString('gmrexp-reading-room__active-list', $page);
        self::assertStringContainsString('gmrexp-reading-room__discover-grid', $page);
        self::assertStringContainsString('More worlds are waiting', $page);
    }

    public function test_v12b_keeps_expansion_personality_and_content_discovery_visible(): void
    {
        $root = dirname(__DIR__, 4);
        $page = file_get_contents($root . '/src/Frontend/ReadingRoom/ReadingRoomPage.php');
        $css = file_get_contents($root . '/assets/css/reading-room.css');
        self::assertIsString($page);
        self::assertIsString($css);
        self::assertStringContainsString('renderContentTypeChips', $page);
        self::assertStringContainsString('Includes Pizza Rat. Obviously.', $page);
        self::assertStringContainsString('gmrexp-reading-room__feature--midnight-menu', $css);
    }

    public function test_v12b_moves_technical_identity_behind_keeper_information_and_preserves_accessibility(): void
    {
        $root = dirname(__DIR__, 4);
        $page = file_get_contents($root . '/src/Frontend/ReadingRoom/ReadingRoomPage.php');
        $css = file_get_contents($root . '/assets/css/reading-room.css');
        self::assertIsString($page);
        self::assertIsString($css);
        self::assertStringContainsString('Keeper information', $page);
        self::assertStringContainsString('Canonical key', $page);
        self::assertStringContainsString('@media (prefers-reduced-motion:reduce)', $css);
        self::assertStringContainsString('@media (forced-colors:active)', $css);
    }
}
