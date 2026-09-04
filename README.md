# Great MarketRealm Expansions

**Great MarketRealm Expansions (GMREXP)** is the expansion-content and optional-rules layer for The Great MarketRealm ecosystem.

It owns canonical expansion packs and structured content definitions — races, subclasses, backgrounds, feats, spells, equipment, monsters, optional rules, adventures and future source-book material — while allowing the **Great MarketRealm Companion** and **Great MarketRealm Tabletop** plugins to consume that content through stable catalogues and integration contracts.

## Current milestone

**Phase II.1 — Peoples Beyond the Pantry**

Phase I is complete. Phase II begins by teaching GMREXP how to describe a complete playable race or subrace as structured expansion data without race-specific PHP. The race schema now understands creature type, fixed-or-choice size, movement speeds, languages, traits, ability-score rules, language choices, proficiencies, resistances, senses and character-generation choices. Subraces identify a canonical parent race and supply their own traits plus optional overrides.

The first Phase II push remains canon-neutral: production code defines the format, while PHPUnit fixtures prove it. Real Great MarketRealm race mechanics should be entered only from an approved canonical source.

A new read-only **MarketRealm Expansions** wp-admin page also provides a visible Keeper's Catalogue showing loaded Almanacs, API versions, content counts and canonical IDs. It is diagnostic/catalogue UI, not an editor.

See `docs/PLAYABLE-RACES.md`, `docs/ADMIN-CATALOGUE.md`, `docs/INTEGRATION-BRIDGE.md`, `docs/CATALOGUE-API.md` and `docs/ALMANAC-FORMAT.md`.

## Development

```bash
composer install
php vendor/bin/phpunit --display-warnings
```

Requires PHP 8.1 or newer.


## Phase II player-option schemas

- Playable races/subraces: see `docs/PLAYABLE-RACES.md`.
- Playable classes/subclasses: see `docs/PLAYABLE-CLASSES.md`.

Canonical mechanics should come from approved source material. Future Google Docs import should transform source documents into reviewed Almanac definitions rather than making runtime gameplay depend on live Docs.


## Playable backgrounds

Phase II.3 adds canonical expansion background definitions with structured proficiencies, equipment, features, languages, generation choices, and characteristic prompts. See `docs/PLAYABLE-BACKGROUNDS.md`.


## Playable feats

Phase II.4 adds canonical feat definitions with prerequisites, repeatability, grants, choices, modifiers and ability-score rules while reserving execution semantics for the later Rules Engine. See `docs/PLAYABLE-FEATS.md`.
