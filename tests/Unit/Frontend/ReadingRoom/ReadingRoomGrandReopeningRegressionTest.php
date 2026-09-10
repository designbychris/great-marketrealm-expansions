<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Frontend\ReadingRoom;

use PHPUnit\Framework\TestCase;

final class ReadingRoomGrandReopeningRegressionTest extends TestCase
{
    public function test_reading_room_uses_great_marketrealm_family_visual_tokens(): void
    {
        $root = dirname(__DIR__, 4);
        $css = file_get_contents($root . '/assets/css/reading-room.css');
        self::assertIsString($css);
        self::assertStringContainsString('Phase V.12 — The Reading Room Gets Its Grand Reopening', $css);
        self::assertStringContainsString('--gmrexp-purple: #603773', $css);
        self::assertStringContainsString('--gmrexp-gold: #b88a37', $css);
        self::assertStringContainsString('--gmrexp-leather: #68412d', $css);
        self::assertStringContainsString('"Cinzel Decorative"', $css);
        self::assertStringContainsString('"Cormorant Garamond"', $css);
    }

    public function test_grand_reopening_preserves_accessibility_fallbacks_and_cover_contract(): void
    {
        $root = dirname(__DIR__, 4);
        $css = file_get_contents($root . '/assets/css/reading-room.css');
        self::assertIsString($css);
        self::assertStringContainsString('@media (prefers-reduced-motion:reduce)', $css);
        self::assertStringContainsString('@media (forced-colors:active)', $css);
        self::assertStringContainsString('aspect-ratio:3/2', $css);
        self::assertStringContainsString('object-fit:cover', $css);
    }

    public function test_library_copy_explains_the_current_desks_instead_of_future_placeholders(): void
    {
        $root = dirname(__DIR__, 4);
        $page = file_get_contents($root . '/src/Frontend/ReadingRoom/ReadingRoomPage.php');
        self::assertIsString($page);
        self::assertStringContainsString('One library, several desks', $page);
        self::assertStringContainsString('Your Library</strong> is your active shelf', $page);
        self::assertStringNotContainsString('Doors prepared for later phases', $page);
    }
}
