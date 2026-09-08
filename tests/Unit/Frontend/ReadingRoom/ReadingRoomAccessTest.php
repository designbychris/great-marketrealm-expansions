<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Frontend\ReadingRoom;

use GreatMarketrealmExpansions\Frontend\ReadingRoom\ReadingRoomAccess;
use PHPUnit\Framework\TestCase;

final class ReadingRoomAccessTest extends TestCase
{
    public function test_guest_requires_login(): void
    {
        $access = new ReadingRoomAccess();

        self::assertSame(ReadingRoomAccess::LOGIN_REQUIRED, $access->state(false, false));
        self::assertFalse($access->allowed(false, false));
    }

    public function test_logged_in_user_without_keeper_capability_is_forbidden(): void
    {
        $access = new ReadingRoomAccess();

        self::assertSame(ReadingRoomAccess::FORBIDDEN, $access->state(true, false));
        self::assertFalse($access->allowed(true, false));
    }

    public function test_keeper_is_allowed(): void
    {
        $access = new ReadingRoomAccess();

        self::assertSame(ReadingRoomAccess::ALLOWED, $access->state(true, true));
        self::assertTrue($access->allowed(true, true));
    }

    public function test_capability_does_not_bypass_login_boundary(): void
    {
        self::assertSame(
            ReadingRoomAccess::LOGIN_REQUIRED,
            (new ReadingRoomAccess())->state(false, true)
        );
    }

    public function test_library_and_browse_are_available_to_signed_in_users_without_admin_capability(): void
    {
        $access = new ReadingRoomAccess();

        self::assertSame(ReadingRoomAccess::ALLOWED, $access->sectionState('library', true, false));
        self::assertSame(ReadingRoomAccess::ALLOWED, $access->sectionState('browse', true, false));
    }

    public function test_import_and_review_require_admin_capability(): void
    {
        $access = new ReadingRoomAccess();

        self::assertSame(ReadingRoomAccess::FORBIDDEN, $access->sectionState('import', true, false));
        self::assertSame(ReadingRoomAccess::FORBIDDEN, $access->sectionState('review', true, false));
        self::assertFalse($access->sectionAllowed('import', true, false));
        self::assertFalse($access->sectionAllowed('review', true, false));
    }

    public function test_admin_can_access_import_and_review_desks(): void
    {
        $access = new ReadingRoomAccess();

        self::assertSame(ReadingRoomAccess::ALLOWED, $access->sectionState('import', true, true));
        self::assertSame(ReadingRoomAccess::ALLOWED, $access->sectionState('review', true, true));
    }

    public function test_guest_cannot_bypass_admin_only_desk_boundary(): void
    {
        $access = new ReadingRoomAccess();

        self::assertSame(ReadingRoomAccess::LOGIN_REQUIRED, $access->sectionState('import', false, true));
        self::assertSame(ReadingRoomAccess::LOGIN_REQUIRED, $access->sectionState('review', false, true));
    }

    public function test_only_import_and_review_are_marked_administrator_only(): void
    {
        $access = new ReadingRoomAccess();

        self::assertFalse($access->administratorOnly('library'));
        self::assertFalse($access->administratorOnly('browse'));
        self::assertTrue($access->administratorOnly('import'));
        self::assertTrue($access->administratorOnly('review'));
    }

}
