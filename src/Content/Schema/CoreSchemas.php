<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;

final class CoreSchemas
{
    public static function register(SchemaRegistry $schemas, ContentTypeCatalogue $types): void
    {
        foreach ($types->all() as $type) {
            $schema = match ($type->key()) {
                'race' => PlayableRaceSchemaFactory::race(),
                'subrace' => PlayableRaceSchemaFactory::subrace(),
                'subclass' => CoreSchemaFactory::make('subclass', [
                    new FieldDefinition('parent_class', FieldDefinition::STRING, true),
                ]),
                default => CoreSchemaFactory::make($type->key()),
            };
            $schemas->add($schema);
        }
    }
}
