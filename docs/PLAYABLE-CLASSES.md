# Playable Classes & Subclasses — Phase II.2

Phase II.2 gives `class` and `subclass` content definitions a canonical structure suitable for expansion-supplied character options.

## Design principle

Feature definitions are separated from level progression. A feature is defined once with a canonical key; progression rows grant those keys at particular levels. This avoids duplicated prose and gives future importers a stable target for sourcebook feature sections and progression tables.

## Class shape

A class requires:

- `name`
- `hit_die`
- `max_level`
- `saving_throw_proficiencies`
- `proficiencies`
- `features`
- `progression`

Optional structured areas include `primary_abilities`, `starting_equipment`, `resources`, `spellcasting`, `subclass_selection`, and `choices`.

A complete class progression must define every level from 1 through `max_level`. Progression rows may reference only feature keys declared by that class.

```php
return [
    'type' => 'class',
    'key' => 'example-calling',
    'data' => [
        'name' => 'Example Calling',
        'hit_die' => 8,
        'max_level' => 20,
        'saving_throw_proficiencies' => ['wisdom', 'charisma'],
        'proficiencies' => [
            'armour' => ['light'],
            'weapons' => ['simple'],
        ],
        'features' => [
            [
                'key' => 'example-feature',
                'name' => 'Example Feature',
                'description' => 'Sourcebook feature text.',
                'rules' => [],
            ],
        ],
        'progression' => [
            ['level' => 1, 'features' => ['example-feature']],
            // ... one row through max_level
        ],
    ],
];
```

## Subclass shape

A subclass requires:

- `name`
- `parent_class`
- `entry_level`
- `features`
- `progression`

Subclass progression is intentionally sparse: it contains only the levels relevant to that subclass and cannot grant a feature before its `entry_level`.

Optional areas include `prerequisites`, `resources`, `spellcasting`, and `choices`.

## Resources and spellcasting

Resources are named, canonical class resources and may include recharge metadata. Per-level values can live in progression rows under `resources`.

`spellcasting` is metadata describing the class/subclass spellcasting model. Phase II.2 validates its structural vocabulary; detailed spell definitions and spell-list mechanics belong to Phase II.5 and executable grants/modifiers belong to Phase II.7.

## Canonical content

Phase II.2 ships the model, not invented Great MarketRealm class mechanics. PHPUnit uses synthetic fixtures. Real classes/subclasses should be populated from approved source material and pass through the same Almanac validation pipeline.

## Future Google Docs import

This schema is intentionally importer-friendly. A future source importer can map sourcebook feature headings to `features`, class progression tables to `progression`, and structured sections to proficiencies/resources/spellcasting. Google Docs should remain an authoring/source surface; reviewed Almanac definitions remain the deterministic runtime artefact.
