<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\Schema\Constraints\TreasureStructureConstraint;

final class TreasureSchemaFactory
{
    public static function treasure(): ContentSchema
    {
        return CoreSchemaFactory::make('treasure', [
            new FieldDefinition('kind', FieldDefinition::STRING, false),
            new FieldDefinition('currency', FieldDefinition::MAP, false),
            new FieldDefinition('items', FieldDefinition::ARRAY, false),
            new FieldDefinition('nested_treasure', FieldDefinition::ARRAY, false),
            new FieldDefinition('tables', FieldDefinition::ARRAY, false),
            new FieldDefinition('selections', FieldDefinition::ARRAY, false),
            new FieldDefinition('grants', FieldDefinition::ARRAY, false),
            new FieldDefinition('value', FieldDefinition::MAP, false),
            new FieldDefinition('distribution', FieldDefinition::STRING, false),
            new FieldDefinition('references', FieldDefinition::ARRAY, false),
            new FieldDefinition('keeper_notes', FieldDefinition::ARRAY, false),
        ], [new TreasureStructureConstraint()]);
    }
}
