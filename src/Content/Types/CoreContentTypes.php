<?php
namespace GreatMarketrealmExpansions\Content\Types;

defined('ABSPATH') || exit;

final class CoreContentTypes
{
    /** @return list<ContentType> */
    public static function all(): array
    {
        return [
            new ContentType('race', 'Race', 'Playable ancestry or people.'),
            new ContentType('subrace', 'Subrace', 'A variant belonging to a parent race.'),
            new ContentType('class', 'Class', 'A playable character calling.'),
            new ContentType('subclass', 'Subclass', 'A specialisation belonging to a parent class.'),
            new ContentType('background', 'Background', 'A character background or origin.'),
            new ContentType('feat', 'Feat', 'A selectable character feat.'),
            new ContentType('spell', 'Spell', 'A spell or magical working.'),
            new ContentType('weapon', 'Weapon', 'A weapon definition.'),
            new ContentType('armour', 'Armour', 'An armour definition.'),
            new ContentType('equipment', 'Equipment', 'General adventuring equipment.'),
            new ContentType('magic-item', 'Magic Item', 'A magical item or artefact.'),
            new ContentType('monster', 'Monster', 'A creature intended primarily for encounters.'),
            new ContentType('npc', 'NPC', 'A named or reusable non-player character.'),
            new ContentType('rule', 'Optional Rule', 'An expansion or optional rules module.'),
            new ContentType('condition', 'Condition', 'A rules condition or status.'),
            new ContentType('adventure', 'Adventure', 'An adventure or adventure module.'),
            new ContentType('encounter', 'Encounter', 'A reusable encounter definition.'),
            new ContentType('hazard', 'Hazard', 'A trap, environmental danger, or hazard.'),
            new ContentType('treasure', 'Treasure', 'A treasure table, parcel, or reward definition.'),
            new ContentType('language', 'Language', 'A language available in the setting.'),
        ];
    }
}
