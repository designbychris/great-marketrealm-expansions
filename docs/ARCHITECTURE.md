# Architecture

GMREXP begins as a registry-driven content provider rather than a second character manager or VTT.

## Boundaries

**Expansions owns:** expansion/source-pack identity, structured rules/content definitions, validation/provenance, compatibility metadata, and stable read APIs.

**Companion owns:** authentication, users, character creation/editing, character persistence, Fellowships and player-facing workflows.

**Tabletop owns:** campaigns/scenes, tokens, encounters, fog/walls, live state and Keeper/player VTT workflows.

## Core registries

`ExpansionRegistry` identifies installed/available packs. `ContentRegistry` stores definitions beneath an expansion key, content type and content key. These are deliberately PHP-domain objects without WordPress dependencies.

A future schema layer will validate content types before real expansion data is introduced. A future integration layer will expose read-only views to Companion and Tabletop without requiring either consumer to know how expansion files are stored.
