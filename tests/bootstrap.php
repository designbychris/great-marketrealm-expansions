<?php
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/wordpress/');
}

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_readable($autoload)) {
    require_once $autoload;
    return;
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'GreatMarketrealmExpansions\\';
    if (!str_starts_with($class, $prefix)) { return; }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $path = dirname(__DIR__) . '/src/' . $relative . '.php';
    if (is_readable($path)) { require_once $path; }
});
require_once dirname(__DIR__) . '/src/functions.php';
