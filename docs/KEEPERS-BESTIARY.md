# The Keeper Opens the Bestiary

Phase III.1 gives expansion-provided monsters a dedicated canonical stat-block structure while preserving compatibility with the lightweight First Almanac proving fixture.

The goal is to make monster content predictable enough for future Bestiary browsing, encounter building, sourcebook import, and Tabletop consumption without hard-coding individual creatures into PHP.

## Compatibility

`name` remains the only universally required monster field. This is intentional: older proving fixtures, lore-only entries, partially imported content, and future review workflows can remain valid while richer stat blocks opt into structured mechanics.

## Structured monster fields

A monster may define:

- size, creature type, and alignment
- armour class and hit points
- walking, climbing, swimming, flying, burrowing, or custom movement modes
- the six core ability scores
- saving throws and skills
- damage vulnerabilities, resistances, and immunities
- condition immunities
- senses and passive perception
- languages
- challenge rating and XP
- proficiency bonus
- traits
- actions
- bonus actions
- reactions
- legendary actions
- lair actions
- spellcasting

Creature types, alignments, movement modes, damage types, conditions, and similar identifiers remain open canonical vocabularies. The Great MarketRealm is therefore free to invent creatures that ordinary bestiaries had the good sense not to anticipate.

## Traits and actions

Traits and action-like entries use stable keys and display names:

```php
[
    'key' => 'sticky-carton',
    'name' => 'Sticky Carton',
    'description' => '...',
    'rules' => [
        [
            'kind' => 'effect',
            'type' => 'restrained',
        ],
    ],
]
```

Keys must be unique within their container.

## Rules Engine integration

Nested `rules[]` payloads are automatically validated by Rules API `1.0.0`. This lets monsters use the same neutral grant, choice, modifier, effect, and requirement vocabulary already used by player-facing content.

GMREXP validates the rule meaning. Tabletop remains responsible for applying it during live play.

## Import direction

A future sourcebook importer can map conventional stat-block headings directly into these fields, preserve prose that cannot yet be transformed, and surface ambiguous mechanics for Keeper review before Almanac publication.
