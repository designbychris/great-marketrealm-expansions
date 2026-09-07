<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\Schema\Constraints\EncounterStructureConstraint;

final class EncounterSchemaFactory
{
    public static function encounter(): ContentSchema
    {
        return CoreSchemaFactory::make('encounter', [
            new FieldDefinition('kind', FieldDefinition::STRING, false),
            new FieldDefinition('setup', FieldDefinition::STRING, false),
            new FieldDefinition('participants', FieldDefinition::ARRAY, false),
            new FieldDefinition('waves', FieldDefinition::ARRAY, false),
            new FieldDefinition('environment', FieldDefinition::MAP, false),
            new FieldDefinition('objectives', FieldDefinition::ARRAY, false),
            new FieldDefinition('difficulty', FieldDefinition::MAP, false),
            new FieldDefinition('rewards', FieldDefinition::ARRAY, false),
            new FieldDefinition('triggers', FieldDefinition::ARRAY, false),
            new FieldDefinition('references', FieldDefinition::ARRAY, false),
            new FieldDefinition('keeper_notes', FieldDefinition::ARRAY, false),
        ], [new EncounterStructureConstraint()]);
    }
}
