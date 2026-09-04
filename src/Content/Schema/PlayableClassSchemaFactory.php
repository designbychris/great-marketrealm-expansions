<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\Schema\Constraints\PlayableClassStructureConstraint;

final class PlayableClassSchemaFactory
{
    public static function classDefinition(): ContentSchema
    {
        return CoreSchemaFactory::make('class', [
            new FieldDefinition('hit_die', FieldDefinition::INTEGER, true),
            new FieldDefinition('max_level', FieldDefinition::INTEGER, true),
            new FieldDefinition('saving_throw_proficiencies', FieldDefinition::ARRAY, true),
            new FieldDefinition('proficiencies', FieldDefinition::MAP, true),
            new FieldDefinition('features', FieldDefinition::ARRAY, true),
            new FieldDefinition('progression', FieldDefinition::ARRAY, true),
            new FieldDefinition('primary_abilities', FieldDefinition::ARRAY, false),
            new FieldDefinition('starting_equipment', FieldDefinition::ARRAY, false),
            new FieldDefinition('resources', FieldDefinition::ARRAY, false),
            new FieldDefinition('spellcasting', FieldDefinition::MAP, false),
            new FieldDefinition('subclass_selection', FieldDefinition::MAP, false),
            new FieldDefinition('choices', FieldDefinition::ARRAY, false),
        ], [new PlayableClassStructureConstraint()]);
    }

    public static function subclass(): ContentSchema
    {
        return CoreSchemaFactory::make('subclass', [
            new FieldDefinition('parent_class', FieldDefinition::STRING, true),
            new FieldDefinition('entry_level', FieldDefinition::INTEGER, true),
            new FieldDefinition('features', FieldDefinition::ARRAY, true),
            new FieldDefinition('progression', FieldDefinition::ARRAY, true),
            new FieldDefinition('prerequisites', FieldDefinition::ARRAY, false),
            new FieldDefinition('resources', FieldDefinition::ARRAY, false),
            new FieldDefinition('spellcasting', FieldDefinition::MAP, false),
            new FieldDefinition('choices', FieldDefinition::ARRAY, false),
        ], [new PlayableClassStructureConstraint()]);
    }
}
