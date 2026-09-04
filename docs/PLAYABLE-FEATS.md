# Playable Feats

Phase II.4 — Gifts, Knacks & Questionable Talents defines the canonical structure used for expansion-provided feats.

## Core identity

A feat inherits the common content fields. `name` remains the only universally required field so narrative/simple feats and older proving fixtures remain valid. Mechanical structure is added explicitly where the feat needs it.

## Optional mechanical structure

A feat may define:

- `prerequisites` — a list of structured prerequisite maps.
- `repeatable` — whether the feat may be selected more than once.
- `max_selections` — a positive upper bound when an explicit limit is useful.
- `grants` — structured things awarded by the feat.
- `choices` — structured choices made when the feat is selected.
- `modifiers` — structured modifications to character values or behaviour.
- `ability_score_rules` — structured ability-score effects or choices.

Each list entry is required to be a non-empty map. Phase II.4 deliberately validates the shape without assigning final execution semantics to those maps; that belongs to Phase II.7 — The Rules Engine.

## Repeatability contract

`max_selections` must be positive. A feat explicitly marked `repeatable: false` cannot declare more than one allowed selection. A repeatable feat may omit a maximum if the source material does not define one.

## Why the payloads stay neutral

The goal is for Companion and Tabletop to consume expansion feats without feat-specific PHP. II.4 gives those mechanics stable containers while avoiding premature rules-engine vocabulary. Later, II.7 can define the canonical operations understood inside prerequisites, grants, choices and modifiers.

## Import direction

This shape is intentionally friendly to sourcebook and Google Docs import. A feat heading can map to identity and description; prerequisite text/tables can map to `prerequisites`; repeatability notes can map to `repeatable`/`max_selections`; and structured benefits can be reviewed into grants, choices and modifiers before an Almanac is committed.
