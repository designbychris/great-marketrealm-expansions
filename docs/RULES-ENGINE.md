# The Rules Engine

Phase II.7 defines the neutral mechanical vocabulary shared by expansion content and consumer plugins.

The Rules Engine deliberately does not execute character-sheet or VTT behaviour inside GMREXP. It validates and exposes canonical rule statements so Companion and Tabletop can interpret the same content without writing PHP for every race, class, feat, spell, or item.

## Rules API

Rules API version: `1.0.0`

Public helper:

```php
\GreatMarketrealmExpansions\rules()
```

The Rules Engine is also exposed to connected consumers through the Integration Bridge.

Core capabilities:

- `rules.validate`
- `rules.statement`
- `rules.grant`
- `rules.choice`
- `rules.modifier`
- `rules.effect`
- `rules.requirement`
- `rules.content-validation`

## Canonical rule kinds

### Grant

A grant gives the subject something. It requires a canonical `type`.

```php
[
    'kind' => 'grant',
    'type' => 'proficiency',
    'value' => 'survival',
]
```

### Choice

A choice describes a decision the consumer must collect. It requires a canonical `key`, may specify a positive `count`, and may expose options as canonical keys or parameter maps.

```php
[
    'kind' => 'choice',
    'key' => 'choose-skill',
    'count' => 1,
    'options' => ['nature', 'survival'],
]
```

### Modifier

A modifier changes a target and requires `target`, `operation`, and `value`.

```php
[
    'kind' => 'modifier',
    'target' => 'armour-class',
    'operation' => 'add',
    'value' => 1,
]
```

Operation keys are intentionally canonical strings rather than a prematurely closed enum. The Rules API can version stricter execution semantics later without making content-specific PHP necessary.

### Effect

An effect requires a canonical `type` and may carry effect-specific structured data.

```php
[
    'kind' => 'effect',
    'type' => 'difficult-terrain',
    'area' => ['shape' => 'sphere', 'radius' => 10],
]
```

### Requirement

A requirement describes a prerequisite or eligibility condition and requires a canonical `type`.

```php
[
    'kind' => 'requirement',
    'type' => 'level',
    'minimum' => 4,
]
```

## Existing content containers

The same Rules Engine now validates the domain-specific containers already introduced by earlier Phase II work:

- `grants[]`
- `choices[]`
- `modifiers[]`
- `effects[]`
- `prerequisites[]`

It also recursively validates explicit nested `rules[]` lists. Race traits, class features, background features, and future nested content can therefore use one generic statement format.

For example:

```php
'features' => [
    [
        'key' => 'sure-footed',
        'name' => 'Sure Footed',
        'rules' => [
            [
                'kind' => 'modifier',
                'target' => 'walking-speed',
                'operation' => 'add',
                'value' => 5,
            ],
        ],
    ],
],
```

## Execution boundary

GMREXP validates and communicates meaning; it does not directly mutate a character or encounter.

A Companion consumer may interpret `walking-speed + 5` while generating a character. Tabletop may interpret an effect when resolving a spell or item. Both receive the same canonical statement.

This preserves the project boundary:

- Expansions owns expansion content and rule meaning.
- Companion owns character and user workflows.
- Tabletop owns live-play and VTT state.

## Future vocabulary

Phase II.7 establishes the stable envelope, not every possible MarketRealm mechanic. Future rule types can be added behind Rules API capabilities/versioning while keeping existing content readable.

That is especially important for future expansion-defined schools, resources, conditions, summoned creatures, terrain effects, and other questionable culinary magic.
