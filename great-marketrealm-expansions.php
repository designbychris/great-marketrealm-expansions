<?php
/**
 * Plugin Name: Great MarketRealm Expansions
 * Description: Expansion rules, data, and content packs for The Great MarketRealm.
 * Version: 0.2.0-alpha3
 * Requires PHP: 8.1
 * Text Domain: great-marketrealm-expansions
 */

defined('ABSPATH') || exit;

define('GMREXP_VERSION', '0.2.0-alpha3');
define('GMREXP_FILE', __FILE__);
define('GMREXP_PATH', plugin_dir_path(__FILE__));

$autoload = GMREXP_PATH . 'vendor/autoload.php';
if (is_readable($autoload)) {
    require_once $autoload;
}

use GreatMarketrealmExpansions\Application\Kernel;
use GreatMarketrealmExpansions\Expansions\Loading\ExpansionLoadException;

add_action('plugins_loaded', static function (): void {
    if (!class_exists(Kernel::class)) {
        return;
    }

    $kernel = Kernel::instance();
    $kernel->boot();

    try {
        $results = $kernel->loader()->loadAll(GMREXP_PATH . 'content/expansions');
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
