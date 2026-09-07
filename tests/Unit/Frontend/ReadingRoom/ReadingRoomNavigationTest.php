<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Frontend\ReadingRoom;

use GreatMarketrealmExpansions\Frontend\ReadingRoom\ReadingRoomNavigation;
use PHPUnit\Framework\TestCase;

final class ReadingRoomNavigationTest extends TestCase
{
    public function test_root_path_is_stable(): void
    {
        self::assertSame('marketrealm-expansions', ReadingRoomNavigation::ROOT);
        self::assertSame('marketrealm-expansions', (new ReadingRoomNavigation())->path());
    }

    public function test_navigation_exposes_the_four_phase_v_desks_in_stable_order(): void
    {
        self::assertSame(
            ['library', 'browse', 'import', 'review'],
            array_keys((new ReadingRoomNavigation())->items())
        );
    }

    public function test_library_is_open_in_v1_while_future_desks_are_reserved(): void
    {
        $items = (new ReadingRoomNavigation())->items();

        self::assertTrue($items['library']['available']);
        self::assertFalse($items['browse']['available']);
        self::assertFalse($items['import']['available']);
        self::assertFalse($items['review']['available']);
    }

    public function test_stable_future_paths_are_declared_now(): void
    {
        $navigation = new ReadingRoomNavigation();

        self::assertSame('marketrealm-expansions/browse', $navigation->path('browse'));
        self::assertSame('marketrealm-expansions/import', $navigation->path('import'));
        self::assertSame('marketrealm-expansions/review', $navigation->path('review'));
    }

    public function test_unknown_section_normalizes_to_library(): void
    {
        self::assertSame('library', (new ReadingRoomNavigation())->normalizeSection('mystery-desk'));
    }

    public function test_section_normalization_is_case_and_whitespace_tolerant(): void
    {
        self::assertSame('review', (new ReadingRoomNavigation())->normalizeSection('  REVIEW '));
    }

    public function test_request_path_maps_back_to_section(): void
    {
        $navigation = new ReadingRoomNavigation();

        self::assertSame('library', $navigation->sectionForRequestPath('/marketrealm-expansions/'));
        self::assertSame('browse', $navigation->sectionForRequestPath('/marketrealm-expansions/browse/'));
        self::assertSame('import', $navigation->sectionForRequestPath('/marketrealm-expansions/import/'));
        self::assertSame('review', $navigation->sectionForRequestPath('/marketrealm-expansions/review/'));
    }

    public function test_unowned_request_path_is_not_claimed(): void
    {
        self::assertNull((new ReadingRoomNavigation())->sectionForRequestPath('/somewhere-else/'));
    }
}
