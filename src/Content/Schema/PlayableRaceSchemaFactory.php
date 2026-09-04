<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\Schema\Constraints\PlayableRaceStructureConstraint;

final class PlayableRaceSchemaFactory
{
    public static function race(): ContentSchema
    {
        return CoreSchemaFactory::make('race', [
            new FieldDefinition('creature_type', FieldDefinition::STRING, true),
            new FieldDefinition('size', FieldDefinition::MAP, true),
            new FieldDefinition('speed', FieldDefinition::MAP, true),
            new FieldDefinition('languages', FieldDefinition::ARRAY, true),
            new FieldDefinition('traits', FieldDefinition::ARRAY, true),
            new FieldDefinition('ability_score_rules', FieldDefinition::ARRAY, false),
            new FieldDefinition('language_choices', FieldDefinition::ARRAY, false),
            new FieldDefinition('proficiencies', FieldDefinition::MAP, false),
            new FieldDefinition('resistances', FieldDefinition::ARRAY, false),
            new FieldDefinition('senses', FieldDefinition::MAP, false),
            new FieldDefinition('choices', FieldDefinition::ARRAY, false),
        ], [new PlayableRaceStructureConstraint()]);
    }

    public static function subrace(): ContentSchema
    {
        return CoreSchemaFactory::make('subrace', [
            new FieldDefinition('parent_race', FieldDefinition::STRING, true),
            new FieldDefinition('traits', FieldDefinition::ARRAY, true),
            new FieldDefinition('creature_type', FieldDefinition::STRING, false),
            new FieldDefinition('size', FieldDefinition::MAP, false),
            new FieldDefinition('speed', FieldDefinition::MAP, false),
            new FieldDefinition('languages', FieldDefinition::ARRAY, false),
            new FieldDefinition('ability_score_rules', FieldDefinition::ARRAY, false),
            new FieldDefinition('language_choices', FieldDefinition::ARRAY, false),
            new FieldDefinition('proficiencies', FieldDefinition::MAP, false),
            new FieldDefinition('resistances', FieldDefinition::ARRAY, false),
            new FieldDefinition('senses', FieldDefinition::MAP, false),
            new FieldDefinition('choices', FieldDefinition::ARRAY, false),
        ], [new PlayableRaceStructureConstraint()]);
    }
}
