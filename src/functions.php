<?php
namespace GreatMarketrealmExpansions;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Application\Kernel;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;

function expansions(): ExpansionRegistry
{
    $kernel = Kernel::instance();
    $kernel->boot();
    return $kernel->expansions();
}

function content(): ContentRegistry
{
    $kernel = Kernel::instance();
    $kernel->boot();
    return $kernel->content();
}
