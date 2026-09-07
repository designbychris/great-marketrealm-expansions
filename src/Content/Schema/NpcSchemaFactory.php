<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\Schema\Constraints\NpcStructureConstraint;

final class NpcSchemaFactory
{
    public static function npc(): ContentSchema
    {
        return CoreSchemaFactory::make('npc', [
            new FieldDefinition('identity', FieldDefinition::MAP, false),
            new FieldDefinition('roles', FieldDefinition::ARRAY, false),
            new FieldDefinition('affiliations', FieldDefinition::ARRAY, false),
            new FieldDefinition('locations', FieldDefinition::ARRAY, false),
            new FieldDefinition('relationships', FieldDefinition::ARRAY, false),
            new FieldDefinition('appearance', FieldDefinition::STRING, false),
            new FieldDefinition('personality', FieldDefinition::ARRAY, false),
            new FieldDefinition('goals', FieldDefinition::ARRAY, false),
            new FieldDefinition('secrets', FieldDefinition::ARRAY, false),
            new FieldDefinition('mannerisms', FieldDefinition::ARRAY, false),
            new FieldDefinition('dialogue', FieldDefinition::ARRAY, false),
            new FieldDefinition('lore_hooks', FieldDefinition::ARRAY, false),
            new FieldDefinition('combat', FieldDefinition::MAP, false),
        ], [new NpcStructureConstraint()]);
    }
}
