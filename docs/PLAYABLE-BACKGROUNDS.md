# Playable Backgrounds

Phase II.3 — Lives Before Adventure defines the canonical structure used for expansion-provided playable backgrounds.

## Required fields

A background inherits the common content fields and requires:

- `name`
- `proficiencies` — grouped lists of canonical proficiency keys.
- `starting_equipment` — a list of structured equipment grants.
- `features` — keyed reusable background features.

## Optional fields

Backgrounds may also define:

- `description`
- `languages`
- `language_choices`
- `equipment_choices`
- `ability_score_rules`
- `feats`
- `characteristics`
- `choices`
- common provenance, compatibility, and tags.

`characteristics` supports the familiar authoring groups `personality_traits`, `ideals`, `bonds`, and `flaws`. These are descriptive prompts, not character state.

Feature entries use stable keys and may later carry neutral `rules` lists for the Rules Engine. Phase II.3 validates the container without inventing rule semantics early.

## Import direction

This shape is intentionally friendly to sourcebook import. A Google Docs background heading can map to one definition; proficiency/language/equipment tables can map to structured fields; feature prose can map to keyed feature entries; and characteristic tables can map to their named groups. Imported definitions should still be validated and reviewed before they become canonical Almanac files.
