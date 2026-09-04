# The Expanded Spellbook

Phase II.5 gives expansion spells a canonical, importer-friendly structure without prematurely defining the Phase II.7 Rules Engine.

## Required spell fields

Alongside the common `name` field, spells require:

- `level` — 0 for cantrips through 9.
- `school` — an open canonical key. Core or future MarketRealm schools such as `saucemancy` can be represented without changing the schema.
- `casting_time` — structured map.
- `range` — structured map.
- `components` — verbal/somatic flags and/or a material description.
- `duration` — structured map.

## Optional spell fields

- `ritual`
- `concentration`
- `spell_lists`
- `targeting`
- `attack`
- `saving_throw`
- `effects`
- `scaling`

`effects` and `scaling` are lists of structured maps. Their final executable vocabulary belongs to Phase II.7. This lets an Almanac describe damage, healing, conditions, movement, upcasting, cantrip scaling, and stranger MarketRealm effects now without embedding content-specific PHP.

## Schools stay extensible

The schema deliberately does not hard-code a traditional list of magic schools. A school is a canonical key, so later expansions can introduce additional disciplines without a spell-schema migration.

## Sourcebook import

A future Google Docs/sourcebook importer can map spell headings and stat blocks into these fields, validate the transformed definition, surface ambiguities for review, and only then write canonical Almanac files. Runtime play should consume those reviewed files rather than parsing Google Docs live.
