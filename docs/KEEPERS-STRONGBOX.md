# The Keeper's Strongbox

Phase III.5 gives expansion packs a canonical structure for treasure parcels, reusable rewards, currency bundles, item references, selections, and random/weighted treasure tables.

A treasure definition is reusable **content**. It describes what a reward parcel means. Companion and Tabletop remain responsible for actually changing a character's coin purse, inventory, Fellowship Treasury, or live session state.

`name` remains the only universally required field so lightweight treasure entries and partial sourcebook imports remain valid.

## Currency

Currency is represented as an open map of canonical currency keys to non-negative numeric amounts:

```php
'currency' => [
    'fixture-coin' => 25,
]
```

GMREXP does not hard-code the Great MarketRealm's eventual canonical currencies here. Sourcebook content will provide those values when imported or curated.

## Canonical item references

Items reference existing Catalogue records rather than duplicating their mechanics:

```php
'items' => [
    [
        'ref' => 'expansion:magic-item:fixture-spoon',
        'quantity' => 1,
        'chance' => 75,
    ],
]
```

Optional weight, chance, variant notes, and nested `rules[]` allow encounter- or treasure-specific behaviour without mutating the canonical item.

## Nested treasure

A parcel can include other treasure definitions using `nested_treasure[]`.

This lets a reusable pouch, cache, strongbox, hoard, reward bundle, or sourcebook parcel be composed from smaller canonical pieces without copying them.

## Treasure tables

Tables are stable keyed structures with an optional die expression, roll count, and entries.

Entries may use an integer roll range:

```php
[
    'min' => 1,
    'max' => 3,
    'reward' => [
        'type' => 'item',
        'ref' => 'expansion:equipment:fixture-ration',
        'quantity' => 1,
    ],
]
```

or a positive relative weight:

```php
[
    'weight' => 2,
    'reward' => [
        'type' => 'currency',
        'currency' => 'fixture-coin',
        'quantity' => 5,
    ],
]
```

GMREXP validates the structure but does not perform live random rolls. A consumer may resolve a table when a reward is awarded.

## Selections

Selections describe player/Keeper choices between canonical references or structured reward maps:

```php
'selections' => [
    [
        'key' => 'keeper-choice',
        'name' => 'Keeper Choice',
        'count' => 1,
        'options' => [
            'expansion:magic-item:fixture-spoon',
            [
                'type' => 'currency',
                'currency' => 'fixture-coin',
                'quantity' => 10,
            ],
        ],
    ],
]
```

## Rules Engine grants

Top-level `grants[]` use Rules API `1.0.0`, allowing neutral reward meaning to be shared with consumers without content-specific PHP.

Nested `rules[]` inside item or structured reward records are likewise validated recursively.

## Estimated value

A treasure definition may carry an optional nominal `value` map with amount and canonical currency key. It is metadata, not an economy engine.

## Distribution

`distribution` is deliberately open vocabulary. A sourcebook might describe a parcel as party-wide, individual, Keeper-assigned, winner-takes-all, or something much stranger.

## Cross-content references

`references[]` can connect treasure to encounters, adventures, NPCs, locations, or other sourcebook content.

Phase III.3 already allows encounters to reward canonical treasure references, so the Encounter Ledger and Strongbox now join cleanly:

```text
Encounter
   ↓ rewards[]
Treasure
   ├─ currency
   ├─ canonical items
   ├─ nested treasure
   ├─ tables
   └─ selections
```

## Architectural boundary

A treasure definition does not store:

- whether this parcel has already been claimed
- which character owns an awarded item
- a character's current coin balance
- Fellowship Treasury balances
- which random table result was rolled in a particular session
- whether the chest token on a Tabletop scene has already been opened

Those are workflow or live-state concerns.

GMREXP owns **what is in the Strongbox**.

The consumer owns **who was reckless enough to open it**.
