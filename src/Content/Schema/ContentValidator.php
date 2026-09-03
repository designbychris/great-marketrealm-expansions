<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;

final class ContentValidator
{
    public function __construct(private SchemaRegistry $schemas) {}

    public function validate(ContentDefinition $definition): ValidationResult
    {
        $schema = $this->schemas->get($definition->type());
        if ($schema === null) {
            return new ValidationResult([new ValidationError('type', sprintf('Unknown content type "%s".', $definition->type()))]);
        }
        return $schema->validate($definition);
    }

    public function assertValid(ContentDefinition $definition): void
    {
        $result = $this->validate($definition);
        if (!$result->valid()) { throw new ContentValidationException($result); }
    }
}
