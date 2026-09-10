<?php
/**
 * Plugin Name: Great MarketRealm Expansions
 * Description: Expansion rules, data, and content packs for The Great MarketRealm.
 * Version: 0.5.0-alpha11
 * Requires PHP: 8.1
 * Text Domain: great-marketrealm-expansions
 */

defined('ABSPATH') || exit;

define('GMREXP_VERSION', '0.5.0-alpha11');
define('GMREXP_FILE', __FILE__);
define('GMREXP_PATH', plugin_dir_path(__FILE__));

$autoload = GMREXP_PATH . 'vendor/autoload.php';
if (is_readable($autoload)) {
    require_once $autoload;
}

use GreatMarketrealmExpansions\Application\Kernel;
use GreatMarketrealmExpansions\Expansions\Loading\ExpansionLoadException;
use GreatMarketrealmExpansions\Almanac\AlmanacStorage;

add_action('plugins_loaded', static function (): void {
    if (!class_exists(Kernel::class)) {
        return;
    }

    $kernel = Kernel::instance();
    $kernel->boot();

    try {
        $storage = $kernel->container()->get(AlmanacStorage::class);
        $results = $kernel->loader()->loadAll($storage->bundledRoot());
        $keeperRoot = $storage->keeperRoot();
        if (is_dir($keeperRoot)) {
            $results = array_merge($results, $kernel->loader()->loadAll($keeperRoot));
        }
        if (function_exists('do_action')) {
            do_action('gmrexp/expansions_loaded', $results, $kernel);
        }
    } catch (ExpansionLoadException $exception) {
        if (function_exists('do_action')) {
            do_action('gmrexp/expansion_load_failed', $exception, $kernel);
        }
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            error_log('[Great MarketRealm Expansions] ' . $exception->getMessage());
        }
    }
}, 5);
