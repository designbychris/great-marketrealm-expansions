<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\Schema\Constraints\MonsterStructureConstraint;

final class MonsterSchemaFactory
{
    public static function monster(): ContentSchema
    {
        return CoreSchemaFactory::make('monster', [
            new FieldDefinition('size', FieldDefinition::STRING, false),
            new FieldDefinition('creature_type', FieldDefinition::STRING, false),
            new FieldDefinition('alignment', FieldDefinition::STRING, false),
            new FieldDefinition('armour_class', FieldDefinition::MAP, false),
            new FieldDefinition('hit_points', FieldDefinition::MAP, false),
            new FieldDefinition('speed', FieldDefinition::MAP, false),
            new FieldDefinition('abilities', FieldDefinition::MAP, false),
            new FieldDefinition('saving_throws', FieldDefinition::MAP, false),
            new FieldDefinition('skills', FieldDefinition::MAP, false),
            new FieldDefinition('damage_vulnerabilities', FieldDefinition::ARRAY, false),
            new FieldDefinition('damage_resistances', FieldDefinition::ARRAY, false),
            new FieldDefinition('damage_immunities', FieldDefinition::ARRAY, false),
            new FieldDefinition('condition_immunities', FieldDefinition::ARRAY, false),
            new FieldDefinition('senses', FieldDefinition::MAP, false),
            new FieldDefinition('languages', FieldDefinition::ARRAY, false),
            new FieldDefinition('challenge', FieldDefinition::MAP, false),
            new FieldDefinition('proficiency_bonus', FieldDefinition::INTEGER, false),
            new FieldDefinition('traits', FieldDefinition::ARRAY, false),
            new FieldDefinition('actions', FieldDefinition::ARRAY, false),
            new FieldDefinition('bonus_actions', FieldDefinition::ARRAY, false),
            new FieldDefinition('reactions', FieldDefinition::ARRAY, false),
            new FieldDefinition('legendary_actions', FieldDefinition::ARRAY, false),
            new FieldDefinition('mythic_actions', FieldDefinition::ARRAY, false),
            new FieldDefinition('lair_actions', FieldDefinition::ARRAY, false),
            new FieldDefinition('spellcasting', FieldDefinition::MAP, false),
            new FieldDefinition('player_description', FieldDefinition::STRING, false),
            new FieldDefinition('field_guide_visible', FieldDefinition::BOOLEAN, false),
            new FieldDefinition('notes', FieldDefinition::STRING, false),
        ], [new MonsterStructureConstraint()]);
    }
}
