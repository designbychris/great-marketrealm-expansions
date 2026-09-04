# The Adventurer's Cupboard

Phase II.6 gives expansion-provided weapons, armour, equipment, and magic items dedicated canonical structures.

The goal is to make item content predictable for Companion, Tabletop, importers, and the future Rules Engine without hard-coding individual MarketRealm items into PHP.

## Weapons

Weapons require:

- `name`
- `category`
- `damage` with a dice expression and canonical damage-type key

They may also define properties, range, proficiency, weight, cost, and structured effects.

Properties can be simple canonical keys or parameter maps, allowing both uncomplicated properties and things such as a versatile alternate damage die.

## Armour

Armour requires:

- `name`
- `category`
- `armour_class`

`armour_class` carries a non-negative base value and may describe an ability modifier and modifier cap. Armour may additionally define a strength requirement, stealth disadvantage, properties, weight, cost, and effects.

## Equipment

General equipment requires `name` and `category`. It may define quantity, consumable state, charges, properties, weight, cost, and effects.

## Magic items

Magic items require:

- `name`
- `category`
- `rarity`

They may additionally define attunement, consumable state, charges, properties, weight, effects, modifiers, and choices.

Attunement is structured as a map with a boolean `required` flag and optional structured requirements.

## Open vocabularies

Categories, rarities, properties, currencies, damage types, and similar identifiers are canonical keys rather than closed enums. Expansion content can therefore introduce legitimate MarketRealm oddities without requiring a schema release.

## Rules Engine boundary

`effects`, `modifiers`, `choices`, parameterised properties, and attunement requirements are validated as structured containers in Phase II.6. Their executable vocabulary belongs to Phase II.7 — The Rules Engine.

## Import direction

Sourcebook item tables can map naturally into these definitions. Names, categories, costs, weights, damage, armour values, rarity, attunement, charges, and prose effects can be transformed into reviewed Almanac files and then pass through the same validation pipeline as hand-authored content.
