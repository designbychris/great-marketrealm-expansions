<?php
namespace GreatMarketrealmExpansions\Frontend\ReadingRoom;

defined('ABSPATH') || exit;

final class ReadingRoomAccess
{
    public const ALLOWED = 'allowed';
    public const LOGIN_REQUIRED = 'login_required';
    public const FORBIDDEN = 'forbidden';

    public function state(bool $loggedIn, bool $canManageExpansions): string
    {
        if (!$loggedIn) {
            return self::LOGIN_REQUIRED;
        }

        return $canManageExpansions ? self::ALLOWED : self::FORBIDDEN;
    }

    public function allowed(bool $loggedIn, bool $canManageExpansions): bool
    {
        return $this->state($loggedIn, $canManageExpansions) === self::ALLOWED;
    }
}
