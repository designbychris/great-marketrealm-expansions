<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\Schema\Constraints\AdventurersCupboardStructureConstraint;

final class AdventurersCupboardSchemaFactory
{
    public static function weapon(): ContentSchema
    {
        return CoreSchemaFactory::make('weapon', [
            new FieldDefinition('category', FieldDefinition::STRING, true),
            new FieldDefinition('damage', FieldDefinition::MAP, true),
            new FieldDefinition('properties', FieldDefinition::ARRAY, false),
            new FieldDefinition('range', FieldDefinition::MAP, false),
            new FieldDefinition('proficiency', FieldDefinition::STRING, false),
            new FieldDefinition('weight', FieldDefinition::NUMBER, false),
            new FieldDefinition('cost', FieldDefinition::MAP, false),
            new FieldDefinition('effects', FieldDefinition::ARRAY, false),
        ], [new AdventurersCupboardStructureConstraint()]);
    }

    public static function armour(): ContentSchema
    {
        return CoreSchemaFactory::make('armour', [
            new FieldDefinition('category', FieldDefinition::STRING, true),
            new FieldDefinition('armour_class', FieldDefinition::MAP, true),
            new FieldDefinition('strength_requirement', FieldDefinition::INTEGER, false),
            new FieldDefinition('stealth_disadvantage', FieldDefinition::BOOLEAN, false),
            new FieldDefinition('properties', FieldDefinition::ARRAY, false),
            new FieldDefinition('weight', FieldDefinition::NUMBER, false),
            new FieldDefinition('cost', FieldDefinition::MAP, false),
            new FieldDefinition('effects', FieldDefinition::ARRAY, false),
        ], [new AdventurersCupboardStructureConstraint()]);
    }

    public static function equipment(): ContentSchema
    {
        return CoreSchemaFactory::make('equipment', [
            new FieldDefinition('category', FieldDefinition::STRING, true),
            new FieldDefinition('quantity', FieldDefinition::INTEGER, false),
            new FieldDefinition('consumable', FieldDefinition::BOOLEAN, false),
            new FieldDefinition('charges', FieldDefinition::MAP, false),
            new FieldDefinition('properties', FieldDefinition::ARRAY, false),
            new FieldDefinition('weight', FieldDefinition::NUMBER, false),
            new FieldDefinition('cost', FieldDefinition::MAP, false),
            new FieldDefinition('effects', FieldDefinition::ARRAY, false),
        ], [new AdventurersCupboardStructureConstraint()]);
    }

    public static function magicItem(): ContentSchema
    {
        return CoreSchemaFactory::make('magic-item', [
            new FieldDefinition('category', FieldDefinition::STRING, true),
            new FieldDefinition('rarity', FieldDefinition::STRING, true),
            new FieldDefinition('attunement', FieldDefinition::MAP, false),
            new FieldDefinition('consumable', FieldDefinition::BOOLEAN, false),
            new FieldDefinition('charges', FieldDefinition::MAP, false),
            new FieldDefinition('properties', FieldDefinition::ARRAY, false),
            new FieldDefinition('weight', FieldDefinition::NUMBER, false),
            new FieldDefinition('effects', FieldDefinition::ARRAY, false),
            new FieldDefinition('modifiers', FieldDefinition::ARRAY, false),
            new FieldDefinition('choices', FieldDefinition::ARRAY, false),
        ], [new AdventurersCupboardStructureConstraint()]);
    }
}
