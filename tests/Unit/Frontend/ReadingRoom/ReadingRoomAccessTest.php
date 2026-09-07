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
}
