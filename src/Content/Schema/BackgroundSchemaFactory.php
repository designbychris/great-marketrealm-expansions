<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\Schema\Constraints\BackgroundStructureConstraint;

final class BackgroundSchemaFactory
{
    public static function background(): ContentSchema
    {
        return CoreSchemaFactory::make('background', [
            new FieldDefinition('proficiencies', FieldDefinition::MAP, true),
            new FieldDefinition('starting_equipment', FieldDefinition::ARRAY, true),
            new FieldDefinition('features', FieldDefinition::ARRAY, true),
            new FieldDefinition('languages', FieldDefinition::ARRAY, false),
            new FieldDefinition('language_choices', FieldDefinition::ARRAY, false),
            new FieldDefinition('equipment_choices', FieldDefinition::ARRAY, false),
            new FieldDefinition('ability_score_rules', FieldDefinition::ARRAY, false),
            new FieldDefinition('feats', FieldDefinition::ARRAY, false),
            new FieldDefinition('characteristics', FieldDefinition::MAP, false),
            new FieldDefinition('choices', FieldDefinition::ARRAY, false),
        ], [new BackgroundStructureConstraint()]);
    }
}
