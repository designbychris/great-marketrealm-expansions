# The Encounter Ledger

Phase III.3 gives expansion packs a canonical structure for encounters: who is present, when reinforcements arrive, what the environment is doing, what success means, and what the Keeper may award afterwards.

An encounter is **content**, not live combat state. GMREXP describes the canonical encounter. Tabletop may instantiate that encounter into tokens, initiative, rounds, fog, doors, and all the other things Pippin would rather not be standing near.

`name` remains the only universally required field. This allows social encounters, exploration scenes, partial sourcebook imports, and lightweight narrative encounter entries without forcing every encounter to be combat.

## Participants

Participants reference canonical content rather than embedding copies:

```php
[
    'ref' => 'first-almanac:monster:milk-carton-mimic',
    'quantity' => 3,
    'role' => 'ambusher',
    'disposition' => 'hostile',
]
```

The reference may point to a monster, NPC, or future encounter-capable content type. Optional placement, variant notes, and Rules Engine statements can describe encounter-specific behaviour without changing the canonical source creature.

## Waves

Waves provide stable keyed groups of participants that enter later:

```php
[
    'key' => 'reinforcements',
    'name' => 'Reinforcements',
    'trigger' => 'At the end of round two.',
    'participants' => [
        ['ref' => 'first-almanac:monster:fixture-reinforcement', 'quantity' => 2],
    ],
]
```

This is descriptive encounter structure. Tabletop remains responsible for deciding how and when a live wave is spawned.

## Environment

Environment metadata can include canonical locations and hazards, terrain keys, lighting, weather, Keeper terrain notes, and neutral `rules[]`.

```php
'environment' => [
    'locations' => ['first-almanac:location:cold-aisle'],
    'terrain' => ['slippery-floor'],
    'hazards' => ['first-almanac:hazard:leaking-freezer'],
    'lighting' => 'dim',
    'rules' => [
        ['kind' => 'effect', 'type' => 'difficult-terrain'],
    ],
]
```

## Objectives

Objectives are stable keyed records with a display name and optional type, description, success/failure text, and Rules Engine statements.

This supports combat goals, rescues, escapes, negotiations, investigations, survival scenes, and stranger MarketRealm problems without reducing every encounter to “defeat all enemies.”

## Difficulty

Encounter difficulty may include:

- an open canonical or numeric rating
- a non-negative XP budget
- optional suggested party size and level

The rating vocabulary remains open. GMREXP does not hard-code a single rules edition or difficulty ladder.

## Rewards

Rewards may be canonical references:

```php
'first-almanac:treasure:fixture-cache'
```

or lightweight structured grants:

```php
[
    'type' => 'currency',
    'quantity' => 3,
    'currency' => 'fixture-coin',
]
```

Phase III.5 — The Keeper's Strongbox will give reusable treasure its own richer canonical structure.

## Triggers and references

Stable keyed triggers can describe important encounter beats and may carry Rules Engine statements. General `references[]` can connect the encounter to adventures, locations, NPCs, hazards, or other sourcebook material.

## Rules Engine integration

Participant, environment, objective, trigger, reward, and other nested `rules[]` structures are automatically validated by Rules API `1.0.0`.

GMREXP therefore owns the neutral mechanical meaning while Tabletop owns the live state produced from it.

## Architectural boundary

A canonical encounter never stores initiative order, current HP, token positions, current round, fog state, or whether the Keeper has already shouted “SURPRISE, THE MILK IS ALIVE.”

Those belong to Tabletop.

The Encounter Ledger is the recipe. The live encounter is the meal.
