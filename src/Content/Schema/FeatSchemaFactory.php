<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\Schema\Constraints\FeatStructureConstraint;

final class FeatSchemaFactory
{
    public static function feat(): ContentSchema
    {
        return CoreSchemaFactory::make('feat', [
            new FieldDefinition('prerequisites', FieldDefinition::ARRAY, false),
            new FieldDefinition('repeatable', FieldDefinition::BOOLEAN, false),
            new FieldDefinition('max_selections', FieldDefinition::INTEGER, false),
            new FieldDefinition('grants', FieldDefinition::ARRAY, false),
            new FieldDefinition('choices', FieldDefinition::ARRAY, false),
            new FieldDefinition('modifiers', FieldDefinition::ARRAY, false),
            new FieldDefinition('ability_score_rules', FieldDefinition::ARRAY, false),
        ], [new FeatStructureConstraint()]);
    }
}
