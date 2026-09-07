# Things That Bite Back

Phase III.4 gives expansion packs a canonical structure for hazards, traps, environmental dangers, and other pieces of scenery that have decided to become personally involved.

A hazard is reusable **content**, not live state. GMREXP describes what the hazard is, how it can be noticed, what triggers it, how it may be avoided or neutralised, and what its consequences mean. Tabletop decides whether a particular live instance has fired, reset, been disabled, or is currently making Pippin reconsider his career choices.

`name` remains the only universally required field. This keeps lightweight narrative hazards, partial sourcebook imports, and unusual non-mechanical dangers valid.

## Severity

Hazards may carry an open severity rating and optional minimum/maximum level guidance:

```php
'severity' => [
    'rating' => 'moderate',
    'minimum_level' => 1,
    'maximum_level' => 5,
]
```

Severity vocabulary remains open rather than tied to one edition or rules ladder.

## Trigger

Triggers have a canonical type plus optional descriptive condition and Rules Engine statements:

```php
'trigger' => [
    'type' => 'enter-area',
    'description' => 'A creature steps onto the leaking floor.',
    'condition' => 'The floor has not been dried.',
]
```

## Detection and disarming

Detection and disarming use reusable check-style maps with optional check key, numeric or open difficulty, description, success/failure prose, passive threshold, and rules.

This lets a sourcebook preserve both conventional checks and stranger MarketRealm procedures without requiring content-specific PHP.

## Avoidance

A hazard may expose several stable keyed avoidance methods:

```php
'avoidance' => [
    [
        'key' => 'walk-around',
        'name' => 'Walk Around It',
        'check' => 'acrobatics',
        'difficulty' => 10,
    ],
]
```

These may be tactical, social, magical, culinary, bureaucratic, or otherwise regrettably necessary.

## Area and duration

Area can describe an open shape, canonical units, and non-negative dimensions such as radius, diameter, length, width, height, and depth.

Duration describes an open type with optional round count or `until` prose.

Neither structure assumes that every hazard belongs to combat.

## Effects

Top-level `effects[]` use the shared Rules Engine effect vocabulary directly:

```php
'effects' => [
    [
        'type' => 'fall-prone',
        'save' => 'dexterity',
        'difficulty' => 11,
    ],
]
```

The Rules Engine validates neutral meaning. Tabletop remains responsible for applying that meaning to live actors.

## Consequences and escalation

Hazards can carry stable keyed consequence and escalation entries, each with optional descriptions, triggers, and nested `rules[]`.

This supports hazards that become worse over time, cause secondary events, transform the environment, summon creatures, or lead into another encounter.

## Reset behaviour

A reset map may describe whether a hazard is continuous, automatic, manually reset, one-shot, delayed, or any other open canonical type:

```php
'reset' => [
    'type' => 'continuous',
    'automatic' => true,
    'interval' => 'immediate',
]
```

Again, this is canonical behaviour rather than the current live state of one particular trap.

## Cross-content references

`references[]` can connect hazards to encounters, adventures, locations, monsters, items, or future sourcebook structures. The Encounter Ledger already supports canonical `environment.hazards[]` references, so Phase III.4 fills the socket prepared in Phase III.3.

## Architectural boundary

A canonical hazard does not store:

- whether this live copy has already triggered
- who detected it
- whether it is currently disabled
- who failed a saving throw
- current countdowns or round state
- the exact token coordinates of a trap on a Tabletop scene

Those remain consumer/live-state concerns.

GMREXP owns **what bites back**.

Tabletop owns **who just got bitten**.
