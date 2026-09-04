<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\Schema\Constraints\SpellStructureConstraint;

final class SpellSchemaFactory
{
    public static function spell(): ContentSchema
    {
        return CoreSchemaFactory::make('spell', [
            new FieldDefinition('level', FieldDefinition::INTEGER, true),
            new FieldDefinition('school', FieldDefinition::STRING, true),
            new FieldDefinition('casting_time', FieldDefinition::MAP, true),
            new FieldDefinition('range', FieldDefinition::MAP, true),
            new FieldDefinition('components', FieldDefinition::MAP, true),
            new FieldDefinition('duration', FieldDefinition::MAP, true),
            new FieldDefinition('ritual', FieldDefinition::BOOLEAN, false),
            new FieldDefinition('concentration', FieldDefinition::BOOLEAN, false),
            new FieldDefinition('spell_lists', FieldDefinition::ARRAY, false),
            new FieldDefinition('targeting', FieldDefinition::MAP, false),
            new FieldDefinition('attack', FieldDefinition::MAP, false),
            new FieldDefinition('saving_throw', FieldDefinition::MAP, false),
            new FieldDefinition('effects', FieldDefinition::ARRAY, false),
            new FieldDefinition('scaling', FieldDefinition::ARRAY, false),
        ], [new SpellStructureConstraint()]);
    }
}
