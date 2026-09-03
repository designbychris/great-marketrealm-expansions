<?php
namespace GreatMarketrealmExpansions\Contracts;

defined('ABSPATH') || exit;

interface Bootable
{
    public function boot(): void;
}
