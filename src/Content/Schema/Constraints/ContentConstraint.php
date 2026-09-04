<?php
namespace GreatMarketrealmExpansions\Content\Schema\Constraints;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ValidationError;

interface ContentConstraint
{
    /** @return list<ValidationError> */
    public function validate(ContentDefinition $definition): array;
}
