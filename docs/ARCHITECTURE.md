# Architecture

GMREXP begins as a registry-driven content provider rather than a second character manager or VTT.

## Boundaries

**Expansions owns:** expansion/source-pack identity, structured rules/content definitions, validation/provenance, compatibility metadata, and stable read APIs.

**Companion owns:** authentication, users, character creation/editing, character persistence, Fellowships and player-facing workflows.

**Tabletop owns:** campaigns/scenes, tokens, encounters, fog/walls, live state and Keeper/player VTT workflows.

## Core registries

`ExpansionRegistry` identifies installed/available packs. `ContentRegistry` stores definitions beneath an expansion key, content type and content key. These are deliberately PHP-domain objects without WordPress dependencies.

## Content types and schemas

`ContentTypeCatalogue` is the canonical list of content categories understood by GMREXP. Phase I.2 begins with 20 types spanning player options, rules, equipment, Keeper content and adventures. Third-party or later first-party code may extend the catalogue through the same domain objects rather than changing registry internals.

Every canonical type has a `ContentSchema`. All definitions require a non-empty `name`; common optional interoperable fields include `description`, `provenance`, `compatibility` and `tags`. Relationship-bearing types can add requirements without imposing mechanics prematurely: `subrace` requires `parent_race`, while `subclass` requires `parent_class`.

The kernel-provided `ContentRegistry` receives a `ContentValidator`, so definitions entering the official registry must have a known type and satisfy its schema. Validation returns structured `ValidationResult` / `ValidationError` objects, and registration failures raise `ContentValidationException`.

`provenance` is reserved for source-book/source-document metadata such as source title, page/reference and authorship notes. `compatibility` is reserved for consumer/ruleset/version constraints. Their internal keys remain intentionally extensible until real expansion packs exercise them.

## Catalogue boundary

`Catalogue` is the supported consumer boundary. It reads from the registries but returns immutable view objects rather than the mutable domain objects themselves. Content IDs remain fully qualified as `expansion:type:key`; unqualified lookups are allowed only when unique.

`CatalogueQuery` supplies immutable fluent filtering and deterministic results. `apiVersion()`, `capabilities()` and `supports()` provide integration feature discovery independently of the WordPress plugin version.

`CatalogueRestApi` adapts the same Catalogue to public read-only WordPress REST routes. It contains no separate content store or rules logic. Companion and Tabletop should consume the Catalogue contract rather than Almanac files or registries directly.


## Integration Bridge

`Bridge` is the supported in-process doorway for sibling Great MarketRealm plugins. A consumer supplies an immutable `Consumer` declaration containing its identity/version, minimum Bridge and Catalogue API versions, and required/optional capabilities.

The Bridge negotiates that declaration against its own integration capabilities plus the capabilities advertised by the Catalogue. A compatible request receives a `BridgeConnection` with read-only Catalogue access. Missing optional capabilities are recorded but tolerated; incompatible API versions, missing required capabilities and conflicting consumer identities produce structured `BridgeIssue` values and no Catalogue object.

`ConsumerRegistry` is request-lifetime integration state only. It exists to detect conflicting declarations inside one runtime and is not persistent content or user data.

Consumers must feature-detect the public `bridge()` function so GMREXP remains an optional enhancement rather than a boot dependency. Plugin release version, Bridge API version and Catalogue API version are intentionally independent contracts.


## Phase II playable character-option schemas

Phase II introduces domain constraints layered on top of typed fields. `ContentSchema` may now receive `ContentConstraint` objects that validate nested structures after the ordinary top-level field checks. This keeps `ContentRegistry` generic while allowing content families to become semantically richer.

`PlayableRaceSchemaFactory` defines the canonical `race` and `subrace` contracts. A complete race describes creature type, size, movement, languages and structured traits, with optional ability-score rules, language choices, proficiencies, resistances, senses and other character-generation choices. A subrace identifies `parent_race`, requires its own traits, and may override the same vocabulary without copying the parent definition.

The format intentionally stops short of interpreting those rules. GMREXP describes canonical expansion mechanics; the later Rules Engine and consumer adapters will decide how structured grants/choices affect a character.

## Keeper's Catalogue wp-admin view

`CatalogueAdminPage` is a read-only diagnostic surface registered as **MarketRealm Expansions** in wp-admin. It reports plugin/Catalogue/Bridge versions, installed Almanacs, total catalogue content, counts by type and canonical content IDs. It adapts the existing Catalogue and introduces no editable store or second source of truth.


## Playable class schemas

Phase II.2 specializes `class` and `subclass` validation through `PlayableClassSchemaFactory` and `PlayableClassStructureConstraint`. Feature definitions are canonical reusable records; level progression references feature keys and remains data rather than class-specific PHP. See `PLAYABLE-CLASSES.md`.
