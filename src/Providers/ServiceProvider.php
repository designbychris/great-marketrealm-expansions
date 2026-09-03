<?php
namespace GreatMarketrealmExpansions\Providers;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Application\Container;

abstract class ServiceProvider
{
    public function __construct(protected Container $container)
    {
    }

    abstract public function register(): void;

    public function boot(): void
    {
    }
}
