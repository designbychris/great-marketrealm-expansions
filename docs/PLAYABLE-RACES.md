# Playable Races and Subraces

Phase II.1 defines the first complete player-option content family. The goal is to represent a playable race as canonical expansion data rather than race-specific PHP.

## Race fields

A `race` requires:

- `name` — display name.
- `creature_type` — canonical creature type key.
- `size` — a map containing either a fixed `value` or a non-empty `options` list.
- `speed` — a map with a positive integer `walk` speed in feet; optional `swim`, `climb`, `fly`, `burrow` and boolean `hover`.
- `languages` — a list of fixed language keys.
- `traits` — a list of trait maps. Every trait requires a canonical `key` and display `name`; `description` and a future-facing `rules` list are optional.

Optional race fields include `ability_score_rules`, `language_choices`, `proficiencies`, `resistances`, `senses` and generic generation `choices`. Choice/rule entries are maps so later phases can refine their semantics without changing the identity of the race.

Example shape:

```php
return [
    'type' => 'race',
    'key' => 'example-folk',
    'data' => [
        'name' => 'Example Folk',
        'creature_type' => 'humanoid',
        'size' => ['options' => ['small', 'medium']],
        'speed' => ['walk' => 30],
        'languages' => ['common'],
        'traits' => [
            [
                'key' => 'example-trait',
                'name' => 'Example Trait',
                'description' => 'Canonical trait text belongs here.',
            ],
        ],
    ],
];
```

The example above documents the format only. Phase II.1 does not introduce invented production race mechanics.

## Subraces

A `subrace` requires `name`, `parent_race` and its own non-empty `traits` list. Creature type, size, movement, languages, ability rules, proficiencies, resistances, senses and choices are optional overrides/extensions inherited conceptually from the parent.

Parent keys remain canonical references. Resolution/merging semantics belong to later consumer/rules phases; GMREXP does not silently copy the parent into the child.

## Validation philosophy

Top-level field types are enforced by `ContentSchema`. Nested meaning is enforced by reusable `ContentConstraint` objects. This allows future classes, spells, equipment and Keeper content to gain their own structural validators without turning the generic registry into a giant type switch.
