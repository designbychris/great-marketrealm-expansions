# Content Types & Schemas

Phase I.2 gives every expansion definition a canonical label and validation contract before real expansion books are loaded.

## Canonical types

Player-facing: `race`, `subrace`, `class`, `subclass`, `background`, `feat`, `spell`, `language`.

Items: `weapon`, `armour`, `equipment`, `magic-item`, `treasure`.

Keeper/rules: `monster`, `npc`, `rule`, `condition`, `encounter`, `hazard`.

Published content: `adventure`.

## Common fields

Every definition requires `name` as a non-empty string. Schemas also recognise optional `description` (string), `provenance` (map), `compatibility` (map), and `tags` (list).

`provenance` records where a definition came from. `compatibility` records the ruleset/plugin/consumer constraints under which it is intended to operate. We validate their shape now while leaving their nested keys extensible until real source packs establish the durable contract.

## Relationships

`subrace` additionally requires `parent_race`.

`subclass` additionally requires `parent_class`.

References are canonical keys rather than copied parent data. Cross-definition existence checks belong to the loader/linking phase once a complete expansion pack can be inspected as a unit.

## Validation philosophy

Schemas reject malformed identity and interoperability data early, but they do not yet pretend every monster, spell or feat can be expressed with a final mechanical schema. Those rules will be tightened incrementally from canonical MarketRealm source material, with backwards-compatible migrations where needed.
