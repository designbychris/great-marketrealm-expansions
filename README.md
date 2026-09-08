# Great MarketRealm Expansions

**Great MarketRealm Expansions (GMREXP)** is the expansion-content and optional-rules layer for The Great MarketRealm ecosystem.

It owns canonical expansion packs and structured content definitions — races, subclasses, backgrounds, feats, spells, equipment, monsters, optional rules, adventures and future source-book material — while allowing the **Great MarketRealm Companion** and **Great MarketRealm Tabletop** plugins to consume that content through stable catalogues and integration contracts.

## Current milestone

**Phase V.8 — Red Ink and Questionable Margins**

The Administrator-only Review Desk is open. Staged source can be handed from Import to a private Keeper queue, then approved, explicitly classified/amended through the existing Review API, ignored/rejected, or reconsidered. **Reviewed ≠ Published.**

Certified starting baseline: **590 tests / 1,419 assertions** (Phase V.7).

See `docs/RED-INK-AND-QUESTIONABLE-MARGINS.md`.


**Phase V.7 — Pippin Finds the Google Docs**

The administrator-only Import Desk can now acquire an accessible Google Docs HTML export, preserve its source identity/headings, transform it into the neutral Import API document shape, and stage it through the existing V.6 pipeline without guessing canonical type/key identity. The certified V.6A baseline is **572 tests / 1380 assertions**.

Private Docs remain safely unsupported by the built-in unauthenticated fetcher; a future authenticated connector can sit behind the same source-adapter boundary.

See `docs/PIPPIN-FINDS-THE-GOOGLE-DOCS.md`.

## Previous milestone

**Phase V.6 — The Keeper's Import Desk**

Phase V.5 is certified at **549 tests / 1,322 assertions**.

The Reading Room Import Desk now stages neutral structured JSON through the existing Import API and explains source identity, record validity, review flags, and validation issues without mutating the canonical Catalogue. Imported material remains non-canonical and request-local; Google Docs acquisition, Keeper review, proposed-Almanac assembly and publication remain separate later phases.

See `docs/KEEPERS-IMPORT-DESK.md`.


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


### Library Compatibility

Phase IV.2 adds dependency, conflict, and consumer-version diagnostics to the Living Library. See `docs/LIBRARY-COMPATIBILITY.md`.


### Import Staging

Phase IV.3 adds the neutral sourcebook/Google-Docs staging boundary and Import API `1.0.0`. See `docs/IMPORT-STAGING.md`.


### Keeper Review

Phase IV.4 adds granular review and amendment of staged sourcebook imports without publishing them. See `docs/KEEPER-REVIEW.md`.


### Content Migrations

Phase IV.5 adds explicit forward-only content/schema migration planning and atomic output boundaries. See `docs/CONTENT-MIGRATIONS.md`.


### The Reading Room

Phase V.1 opens the Keeper-facing front-end shell and reserves stable routes for the later Browse, Import, and Review desks. See `docs/READING-ROOM.md`.


### Books Upon the Shelves

Phase V.2 opens the read-only Browse desk for installed Expansion packs and their canonical content-family counts. See `docs/BOOKS-UPON-THE-SHELVES.md`.


### Two Front Doors

Phase V.2A keeps Reading Room navigation inside the WordPress page hosting the shortcode and redirects legacy virtual routes once that host is known. See `docs/TWO-FRONT-DOORS.md`.
