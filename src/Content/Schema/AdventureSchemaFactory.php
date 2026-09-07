<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\Schema\Constraints\AdventureStructureConstraint;

final class AdventureSchemaFactory
{
    public static function adventure(): ContentSchema
    {
        return CoreSchemaFactory::make('adventure', [
            new FieldDefinition('kind', FieldDefinition::STRING, false),
            new FieldDefinition('synopsis', FieldDefinition::STRING, false),
            new FieldDefinition('level_range', FieldDefinition::MAP, false),
            new FieldDefinition('entry_points', FieldDefinition::ARRAY, false),
            new FieldDefinition('prerequisites', FieldDefinition::ARRAY, false),
            new FieldDefinition('chapters', FieldDefinition::ARRAY, false),
            new FieldDefinition('appendices', FieldDefinition::ARRAY, false),
            new FieldDefinition('progression', FieldDefinition::MAP, false),
            new FieldDefinition('references', FieldDefinition::ARRAY, false),
            new FieldDefinition('keeper_notes', FieldDefinition::ARRAY, false),
        ], [new AdventureStructureConstraint()]);
    }
}
