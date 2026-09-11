<?php

declare(strict_types=1);

namespace GreatMarketrealmExpansions\Tests\Unit\Frontend\ReadingRoom;

use PHPUnit\Framework\TestCase;

final class ReadingRoomFrontDoorRegressionTest extends TestCase
{
    public function test_v12b1_uses_transparent_wide_host_page_shell_and_simplified_masthead(): void
    {
        $root = dirname(__DIR__, 4);
        $css = file_get_contents($root . '/assets/css/reading-room.css');
        self::assertIsString($css);
        self::assertStringContainsString('max-width: 1480px', $css);
        self::assertStringContainsString('background: transparent', $css);
        self::assertStringContainsString('.gmrexp-reading-room__masthead::before,', $css);
        self::assertStringContainsString('color:#fff', $css);
    }

    public function test_v12b1_navigation_separates_player_destinations_from_keeper_tools(): void
    {
        $root = dirname(__DIR__, 4);
        $page = file_get_contents($root . '/src/Frontend/ReadingRoom/ReadingRoomPage.php');
        self::assertIsString($page);
        self::assertStringContainsString('gmrexp-reading-room__nav-primary', $page);
        self::assertStringContainsString('gmrexp-reading-room__nav-keeper', $page);
        self::assertStringContainsString('Keeper tools', $page);
        self::assertStringContainsString('Your active adventures', $page);
        self::assertStringContainsString('Explore every available expansion', $page);
    }

    public function test_v12b2_featured_expansion_uses_a_quieter_sourcebook_action_without_a_mascot_cameo(): void
    {
        $root = dirname(__DIR__, 4);
        $page = file_get_contents($root . '/src/Frontend/ReadingRoom/ReadingRoomPage.php');
        $css = file_get_contents($root . '/assets/css/reading-room.css');
        self::assertIsString($page);
        self::assertIsString($css);
        self::assertStringContainsString('gmrexp-reading-room__button--feature', $page);
        self::assertStringNotContainsString('gmrexp-reading-room__pizza-rat', $page);
        self::assertStringNotContainsString('pizza-rat-pixel.png', $page);
        self::assertStringContainsString('Phase V.12B.2 — The Featured Expansion Finds Its Frame', $css);
    }
}
