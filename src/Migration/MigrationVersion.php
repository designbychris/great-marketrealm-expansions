<?php
namespace GreatMarketrealmExpansions\Migration;

defined('ABSPATH') || exit;

final class MigrationVersion
{
    public static function compare(string $left, string $right): int
    {
        return version_compare(
            self::comparable($left),
            self::comparable($right)
        );
    }

    public static function equal(string $left, string $right): bool
    {
        return self::compare($left, $right) === 0;
    }

    private static function comparable(string $version): string
    {
        $version = trim($version);

        if (!preg_match('/^(\d+(?:\.\d+)*)(.*)$/', $version, $matches)) {
            return $version;
        }

        $segments = explode('.', $matches[1]);

        while (count($segments) > 1 && end($segments) === '0') {
            array_pop($segments);
        }

        return implode('.', $segments) . $matches[2];
    }
}
