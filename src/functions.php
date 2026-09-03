<?php
namespace GreatMarketrealmExpansions;


use GreatMarketrealmExpansions\Application\Kernel;
use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use GreatMarketrealmExpansions\Expansions\Loading\ExpansionFileLoader;

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

function content_types(): ContentTypeCatalogue
{
    $kernel = Kernel::instance();
    $kernel->boot();
    return $kernel->contentTypes();
}

function schemas(): SchemaRegistry
{
    $kernel = Kernel::instance();
    $kernel->boot();
    return $kernel->schemas();
}

function loader(): ExpansionFileLoader
{
    $kernel = Kernel::instance();
    $kernel->boot();
    return $kernel->loader();
}

function catalogue(): Catalogue
{
    $kernel = Kernel::instance();
    $kernel->boot();
    return $kernel->catalogue();
}
