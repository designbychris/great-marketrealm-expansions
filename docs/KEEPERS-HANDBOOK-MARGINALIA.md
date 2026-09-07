# Marginalia in the Keeper's Handbook

Phase III.6 gives expansion packs canonical structures for optional Keeper rules and conditions.

The purpose is deliberately narrow: GMREXP describes **what a rule or condition means**. Companion and Tabletop decide whether an optional rule is enabled for a particular campaign and whether a live creature is currently affected by a condition.

`name` remains the only universally required field for both `rule` and `condition`, preserving lightweight sourcebook notes and partial import-review records.

## Optional Keeper rules

A rule definition may describe an open `kind`, scope, activation guidance, ordering priority, prerequisites, neutral Rules Engine statements, conflicts, superseded rules, references, and Keeper notes.

```php
[
    'name' => 'Fixture Optional Rule',
    'kind' => 'campaign-option',

    'scope' => [
        'targets' => ['character'],
        'contexts' => ['exploration'],
        'content_types' => ['race', 'class'],
    ],

    'activation' => [
        'mode' => 'optional',
        'enabled_by_default' => false,
    ],

    'rules' => [
        [
            'kind' => 'modifier',
            'target' => 'fixture-value',
            'operation' => 'add',
            'value' => 1,
        ],
    ],
]
```

### Scope

Scope uses open lists for:

- `targets`
- `contexts`
- `content_types`
- `references`

This communicates applicability without hard-coding edition- or expansion-specific taxonomies.

### Activation metadata

`activation` can carry an open canonical `mode`, an `enabled_by_default` recommendation, and an optional `exclusive_group`.

These are **canonical rule-definition properties**, not campaign state. A consumer may use them when presenting campaign setup, but GMREXP does not record which option the Keeper actually selected.

### Conflicts and supersession

`conflicts[]` and `supersedes[]` are canonical rule references. They let future catalogue/activation tooling explain rule relationships without copying mechanics or forcing activation decisions into the content layer.

### Rules Engine integration

`prerequisites[]` use Rules API requirement statements directly.

`rules[]` use generic Rules Engine statements with explicit `kind`, allowing grants, choices, modifiers, effects, and requirements to coexist in a single optional-rule module.

## Conditions

A condition may describe how it is applied, how long it normally lasts, stacking behaviour, effects, generic rules, removal methods, escalation/stages, references, and Keeper notes.

```php
[
    'name' => 'Fixture Sticky',
    'kind' => 'status',

    'application' => [
        'type' => 'contact',
        'save' => 'dexterity',
        'difficulty' => 12,
    ],

    'duration' => [
        'type' => 'until-removed',
        'until' => 'The creature is thoroughly cleaned.',
    ],

    'effects' => [
        ['type' => 'sticky-movement'],
    ],
]
```

### Application

Application metadata may include an open type/source, save or check identifiers, numeric or open difficulty, and nested `rules[]`.

This describes the canonical route by which a condition can be imposed; it does not identify a live affected actor.

### Duration

Duration has an open canonical type and can optionally describe a positive round count, `until` prose, or an `ends_on` timing key.

Again, the definition says how the condition normally behaves. A live countdown belongs to the consumer.

### Stacking

Conditions may describe an open stacking mode, a positive maximum, and whether reapplication normally refreshes duration.

This supports simple non-stacking conditions as well as future expansion-specific staged or accumulating states without requiring new PHP.

### Effects and rules

Top-level `effects[]` use Rules API effect statements.

Generic `rules[]` use explicit Rules Engine statements for mechanics that do not fit a single effect declaration.

### Removal and stages

`removal[]` and `stages[]` are stable keyed entries with names plus optional descriptions, triggers, checks/saves, difficulty, and nested `rules[]`.

This supports conditions that can be removed in several ways or that intensify through named stages.

## Architectural boundary

GMREXP does not store:

- which optional rule a Keeper has enabled for a campaign
- whether a rule is currently suppressed for one session
- which actor currently has a condition
- how many live stacks an actor has
- remaining condition rounds
- which removal attempt has already been made
- live concentration, turn, token, or encounter state

Those belong to Companion/Tabletop and future campaign-activation workflows.

```text
GMREXP Rule
    = what the optional rule means

Campaign / Consumer
    = whether that rule is enabled

GMREXP Condition
    = what the condition means

Live Consumer State
    = who currently has it
```

The margin may say, “Optional rule: for added realism...”

Pippin remains free to close the handbook.
