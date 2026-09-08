<?php
namespace GreatMarketrealmExpansions\Frontend\ReadingRoom;

defined('ABSPATH') || exit;

final class ReadingRoomAccess
{
    public const ALLOWED = 'allowed';
    public const LOGIN_REQUIRED = 'login_required';
    public const FORBIDDEN = 'forbidden';

    /**
     * Backwards-compatible Keeper-level access check.
     *
     * Use sectionState() for Reading Room page access.
     */
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

    public function sectionState(string $section, bool $loggedIn, bool $canManageExpansions): string
    {
        if (!$loggedIn) {
            return self::LOGIN_REQUIRED;
        }

        if ($this->administratorOnly($section) && !$canManageExpansions) {
            return self::FORBIDDEN;
        }

        return self::ALLOWED;
    }

    public function sectionAllowed(string $section, bool $loggedIn, bool $canManageExpansions): bool
    {
        return $this->sectionState($section, $loggedIn, $canManageExpansions) === self::ALLOWED;
    }

    public function administratorOnly(string $section): bool
    {
        return in_array(strtolower(trim($section)), ['import', 'review'], true);
    }
}
