<?php
namespace GreatMarketrealmExpansions\Review;

defined('ABSPATH') || exit;

final class WordPressUserReviewQueueStore implements ReviewQueueStore
{
    public const META_KEY = '_gmrexp_review_desk_queue';

    public function load(): ?array
    {
        if (!function_exists('get_current_user_id') || !function_exists('get_user_meta')) { return null; }
        $userId = (int) get_current_user_id();
        if ($userId <= 0) { return null; }
        $state = get_user_meta($userId, self::META_KEY, true);
        return is_array($state) ? $state : null;
    }

    public function save(array $state): void
    {
        if (!function_exists('get_current_user_id') || !function_exists('update_user_meta')) { return; }
        $userId = (int) get_current_user_id();
        if ($userId > 0) { update_user_meta($userId, self::META_KEY, $state); }
    }

    public function clear(): void
    {
        if (!function_exists('get_current_user_id') || !function_exists('delete_user_meta')) { return; }
        $userId = (int) get_current_user_id();
        if ($userId > 0) { delete_user_meta($userId, self::META_KEY); }
    }
}
