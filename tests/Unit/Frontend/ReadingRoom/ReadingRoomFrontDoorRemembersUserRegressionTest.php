<?php

declare(strict_types=1);

namespace GreatMarketrealmExpansions\Tests\Unit\Frontend\ReadingRoom;

use PHPUnit\Framework\TestCase;

final class ReadingRoomFrontDoorRemembersUserRegressionTest extends TestCase
{
    public function test_v12b4_versions_reading_room_assets_from_their_file_modification_time(): void
    {
        $root = dirname(__DIR__, 4);
        $page = file_get_contents($root . '/src/Frontend/ReadingRoom/ReadingRoomPage.php');

        self::assertIsString($page);
        self::assertStringContainsString("assetVersion('assets/css/reading-room.css')", $page);
        self::assertStringContainsString("assetVersion('assets/js/reading-room-review.js')", $page);
        self::assertStringContainsString('filemtime($path)', $page);
        self::assertStringContainsString('$styleVersion', $page);
        self::assertStringContainsString('$scriptVersion', $page);
    }

    public function test_v12b4_prefers_companion_guild_gate_and_keeps_wordpress_login_as_safe_fallback(): void
    {
        $root = dirname(__DIR__, 4);
        $page = file_get_contents($root . '/src/Frontend/ReadingRoom/ReadingRoomPage.php');

        self::assertIsString($page);
        self::assertStringContainsString('companionLoginUrl($returnUrl)', $page);
        self::assertStringContainsString("apply_filters('gmrexp/companion_login_url'", $page);
        self::assertStringContainsString("'gmrc_login_url'", $page);
        self::assertStringContainsString('get_page_by_path($path)', $page);
        self::assertStringContainsString("home_url('/companion/')", $page);
        self::assertStringContainsString("add_query_arg('redirect_to', $returnUrl, $guildGateUrl)", $page);
        self::assertStringContainsString('wp_login_url($returnUrl)', $page);
    }

    public function test_v12b4_preserves_reading_room_section_and_deeper_browse_destination(): void
    {
        $root = dirname(__DIR__, 4);
        $page = file_get_contents($root . '/src/Frontend/ReadingRoom/ReadingRoomPage.php');

        self::assertIsString($page);
        self::assertStringContainsString('renderLoginRequired($section, $baseUrl)', $page);
        self::assertStringContainsString('self::EXPANSION_QUERY_ARG, self::CONTENT_TYPE_QUERY_ARG', $page);
        self::assertStringContainsString('$this->navigationUrl($section, $baseUrl ?? $this->currentHostUrl())', $page);
    }

    public function test_v12b4_repairs_v12b3_css_into_real_rules_instead_of_escaped_text(): void
    {
        $root = dirname(__DIR__, 4);
        $css = file_get_contents($root . '/assets/css/reading-room.css');

        self::assertIsString($css);
        self::assertStringContainsString('Phase V.12B.3 — The Signposts Settle Into Place', $css);
        self::assertStringContainsString('grid-template-columns:repeat(4,minmax(0,1fr))', $css);
        self::assertStringContainsString('.gmrexp-reading-room__nav-link--keeper', $css);
        self::assertStringContainsString('content:"\\2620"', $css);
        self::assertStringNotContainsString('\\n\\n/* Phase V.12B.3', $css);
    }
}
