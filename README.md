# Great MarketRealm Expansions

**Great MarketRealm Expansions (GMREXP)** is the expansion-content and optional-rules layer for The Great MarketRealm ecosystem.

It owns canonical expansion packs and structured content definitions — races, subclasses, backgrounds, feats, spells, equipment, monsters, optional rules, adventures and future source-book material — while allowing the **Great MarketRealm Companion** and **Great MarketRealm Tabletop** plugins to consume that content through stable catalogues and integration contracts.

## Current milestone

**Phase IV.1 — The Keeper Marks the Active Shelves**

Phase III Keeper Content is complete and certified at **313 tests / 755 assertions**.

Phase IV begins the **Living Library**: GMREXP can now distinguish between expansion packs that are installed in the canonical Catalogue and packs that are currently active for consumers. Installed packs default to active for backwards compatibility, activation is persisted behind a pluggable store, and the Keeper Catalogue provides secure Activate/Deactivate controls.

The canonical Catalogue remains complete and read-only regardless of activation state. Ownership, entitlement, marketplace availability, and campaign-specific activation are deliberately not invented in this phase.

See `docs/LIVING-LIBRARY.md`.


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


## Expanded Spellbook

Phase II.5 adds canonical expansion spell definitions with structured casting metadata, components, targeting, effects and scaling while keeping custom magic schools open-ended. See `docs/EXPANDED-SPELLBOOK.md`.


## The Adventurer's Cupboard

Phase II.6 adds canonical expansion definitions for weapons, armour, equipment, and magic items, including structured item statistics and future Rules Engine payloads. See `docs/ADVENTURERS-CUPBOARD.md`.


## The Rules Engine

Phase II.7 adds Rules API `1.0.0`: a neutral mechanical language for grants, choices, modifiers, effects, and requirements. Consumers can access it through `rules()` or the Integration Bridge. See `docs/RULES-ENGINE.md`.


## Keeper Content

Phase III begins with **The Keeper Opens the Bestiary**, adding canonical monster stat-block structures with nested Rules Engine mechanics. See `docs/KEEPERS-BESTIARY.md`.


### Faces Behind the Counter

Phase III.2 adds canonical NPC dossiers for identity, roles, relationships, dialogue, lore hooks, and optional monster-backed combat identity. See `docs/FACES-BEHIND-THE-COUNTER.md`.


### The Encounter Ledger

Phase III.3 adds canonical encounter compositions with participants, waves, environments, objectives, difficulty guidance, rewards, triggers, and Rules Engine mechanics. See `docs/ENCOUNTER-LEDGER.md`.


### Things That Bite Back

Phase III.4 adds canonical hazards and traps with detection, triggers, avoidance, disarming, effects, escalation, reset behaviour, and Rules Engine mechanics. See `docs/THINGS-THAT-BITE-BACK.md`.


### The Keeper's Strongbox

Phase III.5 adds canonical treasure parcels with currency, item references, nested treasure, random/weighted tables, selections, nominal value metadata, and Rules Engine grants. See `docs/KEEPERS-STRONGBOX.md`.


### Marginalia in the Keeper's Handbook

Phase III.6 adds canonical optional Keeper rules and conditions, including scopes, activation guidance, Rules Engine mechanics, condition application/duration/stacking, removal and escalation. See `docs/KEEPERS-HANDBOOK-MARGINALIA.md`.


### The Adventure Shelf

Phase III.7 adds whole-adventure/sourcebook structure with entry points, chapters, sections, scenes, appendices, progression branches, canonical content references, and Rules Engine integration. See `docs/ADVENTURE-SHELF.md`.
