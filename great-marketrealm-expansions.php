<?php
/**
 * Plugin Name: Great MarketRealm Expansions
 * Description: Expansion rules, data, and content packs for The Great MarketRealm.
 * Version: 0.1.0-alpha2
 * Requires PHP: 8.1
 * Text Domain: great-marketrealm-expansions
 */

defined('ABSPATH') || exit;

define('GMREXP_VERSION', '0.1.0-alpha2');
define('GMREXP_FILE', __FILE__);
define('GMREXP_PATH', plugin_dir_path(__FILE__));

$autoload = GMREXP_PATH . 'vendor/autoload.php';
if (is_readable($autoload)) {
    require_once $autoload;
}

use GreatMarketrealmExpansions\Application\Kernel;

add_action('plugins_loaded', static function (): void {
    if (!class_exists(Kernel::class)) {
        return;
    }

    Kernel::instance()->boot();
}, 5);
