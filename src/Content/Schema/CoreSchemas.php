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
                'class' => PlayableClassSchemaFactory::classDefinition(),
                'subclass' => PlayableClassSchemaFactory::subclass(),
                'background' => BackgroundSchemaFactory::background(),
                'feat' => FeatSchemaFactory::feat(),
                'spell' => SpellSchemaFactory::spell(),
                'weapon' => AdventurersCupboardSchemaFactory::weapon(),
                'armour' => AdventurersCupboardSchemaFactory::armour(),
                'equipment' => AdventurersCupboardSchemaFactory::equipment(),
                'magic-item' => AdventurersCupboardSchemaFactory::magicItem(),
                default => CoreSchemaFactory::make($type->key()),
            };
            $schemas->add($schema);
        }
    }
}
