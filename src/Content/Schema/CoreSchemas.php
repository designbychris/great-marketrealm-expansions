<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;

final class CoreSchemas
{
    public static function register(SchemaRegistry $schemas, ContentTypeCatalogue $types): void
    {
        foreach ($types->all() as $type) {
            $extra = match ($type->key()) {
                'subrace' => [new FieldDefinition('parent_race', FieldDefinition::STRING, true)],
                'subclass' => [new FieldDefinition('parent_class', FieldDefinition::STRING, true)],
                default => [],
            };
            $schemas->add(CoreSchemaFactory::make($type->key(), $extra));
        }
    }
}
