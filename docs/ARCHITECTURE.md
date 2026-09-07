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


### Phase II.6 item boundary

Weapons, armour, equipment, and magic items now use dedicated schemas. Item definitions own descriptive/statistical structure; executable effects, modifiers, choices, and other rule statements remain neutral payloads until the Phase II.7 Rules Engine defines their semantics.


### Phase II.7 rules boundary

The Rules Engine is a read/validate/interpret contract, not a state mutation service. GMREXP owns canonical mechanical statements and validation. Consumer plugins decide how those statements affect their own state. Generic nested `rules[]` statements and established domain containers (`grants`, `choices`, `modifiers`, `effects`, `prerequisites`) converge on the same Rules API.


### Phase III.1 Keeper content boundary

Monster definitions now have a dedicated schema but remain content, not live encounter state. GMREXP owns the canonical monster record and mechanical rule statements. Tabletop may instantiate those records into encounter tokens/actors without duplicating the source mechanics.


### Phase III.2 NPC boundary

NPC definitions are canonical narrative/content records, not WordPress users, Companion characters, or live Tabletop actors. An NPC can reference a canonical monster for combat without duplicating that stat block. Relationships and lore hooks similarly reference content by stable identifiers so later campaign/adventure systems can connect records without embedding copies.


### Phase III.3 encounter boundary

Encounter definitions are reusable canonical recipes. They reference monsters, NPCs, hazards, treasure, locations, and future content rather than duplicating them. Live initiative order, token state, HP, rounds, placement coordinates, fog, and encounter progress remain Tabletop state. Encounter-specific rule statements may describe canonical variants without mutating either the source records or live state.


### Phase III.4 hazard boundary

Hazard definitions are reusable canonical content. They may describe trigger conditions, detection, avoidance, disarming, area, duration, neutral effects, escalation, reset behaviour, and references to encounters or adventures. Whether a particular hazard instance has triggered, been discovered, been disabled, reset, or affected a live actor remains Tabletop/session state.


### Phase III.5 treasure boundary

Treasure definitions are reusable canonical reward content. They may reference existing Catalogue items, compose nested treasure parcels, define currency bundles, weighted or range-based tables, selections, and neutral grants. Award ownership, current character currency, inventory mutation, Fellowship Treasury balances, claimed state, and session-specific random results remain Companion/Tabletop workflow or live state.


### Phase III.6 rule and condition boundary

Optional `rule` definitions are canonical content. They may describe scope, activation guidance, priority, prerequisites, conflicts, supersession, and neutral Rules Engine statements. Which rules are actually enabled for a campaign is consumer/campaign state.

`condition` definitions describe canonical application, duration, stacking, effects, removal methods, and stages. Which live actor has a condition, current stack counts, remaining duration, and current removal state remain Companion/Tabletop concerns.


### Phase III.7 adventure boundary

Adventure records are canonical book/adventure structure. They arrange chapters, sections, scenes, appendices, entry points, progression branches, and references to existing canonical content. They do not duplicate monster, NPC, hazard, treasure, rule, condition, or encounter mechanics.

The party's current chapter/scene, chosen branch, completed objectives, claimed rewards, and all live session state remain consumer concerns.


### Phase IV.1 Living Library boundary

The Catalogue answers **what is installed and canonical**. The Living Library answers **which installed packs are currently active at the site/library level**.

Activation never removes or rewrites Catalogue content. Packs with no explicit activation decision default to active, preserving Phase III behaviour. Production activation persistence sits behind `ActivationStore`, currently implemented with a WordPress option.

Ownership, entitlement, remote marketplace availability and campaign-specific activation are intentionally outside Phase IV.1.


### Phase IV.2 compatibility boundary

Expansion dependency, conflict, and consumer-version declarations are pack metadata interpreted by the Living Library. Compatibility evaluation produces deterministic `ready`, `degraded`, or `blocked` reports with stable diagnostic codes.

Compatibility is deliberately diagnostic. The inspector never mutates activation state, unloads packs, or rewrites canonical content. Consumer versions are evaluated only when explicitly supplied; the Library does not guess sibling-plugin installations or versions.


### Phase IV.3 import staging boundary

External documents do not become Catalogue content directly. Source-specific adapters produce a non-executable structured import document; Import API `1.0.0` stamps protected provenance, validates each staged definition through the canonical schema pipeline, surfaces ambiguity and returns a reviewable result.

The Import API does not publish, activate, install or overwrite expansion content. Google Docs is therefore an upstream source adapter concern rather than a gameplay/runtime dependency.


### Phase IV.4 review boundary

Review API `1.0.0` sits between import staging and future Almanac publication. Each staged record receives an independent pending/approved/rejected/amended decision. Invalid staged records cannot be approved unchanged; amendments are passed through the canonical schema validator and retain protected source provenance.

Review sessions are intentionally review-state objects rather than Catalogue writers. Completion means every staged record has a Keeper decision, not that content has been published or installed.


### Phase IV.5 migration boundary

Migration API `1.0.0` provides trusted, explicit, forward-only transformation chains for known GMREXP content definitions. Migration steps operate on definition data only, so canonical type/key identity remains outside transformation callbacks. Successful results preserve provenance and append protected migration history before final canonical schema validation.

Migration is non-persistent in this phase. Batch migration provides an atomic output view — migrated definitions are exposed as a group only when every item succeeds — but performs no filesystem, database, Catalogue, Library activation, or publication mutation.


### Phase V.1 Reading Room boundary

The Reading Room is a front-end adapter, not another domain store. `ReadingRoomSummary` reads installed/canonical data from `Catalogue` and active/compatibility state from `Library`. `ReadingRoomPage` presents that information behind the established Keeper capability boundary.

Stable Library/Browse/Import/Review routes are reserved in V.1. Only the Library route is functionally open; future routes are deliberately non-mutating placeholders. The same renderer is available through `[great_marketrealm_expansions]`.

No Phase V.1 component writes Catalogue content, changes Living Library activation, stages imports, records review decisions, runs migrations, or publishes Almanac files.


### Phase V.2 Browse shelf boundary

`BrowseShelf` is a deterministic read model over the existing `Catalogue` and `Library` services. It creates immutable `BrowseShelfEntry` presentation values containing canonical pack identity, version/description, content-family counts, activation state and compatibility status.

It is not a registry and does not persist anything. Rendering Browse cannot activate/deactivate packs, change compatibility state, mutate Catalogue definitions, or create detail records.

The V.1 `/marketrealm-expansions/browse/` route is reused unchanged, so the Reading Room route contract remains `1.0.0`.


### Phase V.2A shortcode-host boundary

The WordPress page containing `[great_marketrealm_expansions]` is the preferred owner of the outer frontend page. GMREXP owns only the Reading Room section rendered inside that shortcode.

Reading Room desk navigation therefore uses the host page permalink plus `gmrexp_section`, rather than navigating away to a second standalone page hierarchy. The host page is remembered by WordPress page ID solely so the earlier virtual routes can redirect into the canonical shortcode host once known.

Legacy virtual routes remain fallback-compatible; this hotfix changes no Catalogue, Library or content ownership boundary.


### Phase V.3 activation boundary

The Reading Room now exposes a secure WordPress POST adapter over `Library::setActive()`. The frontend does not own activation state; `ActivationStore` remains the persistence boundary and Catalogue remains complete regardless of active/inactive status.

Deactivating a pack changes consumer availability only. Canonical content identity and installed records are untouched.


### Phase V.3A cache-coherency boundary

The Reading Room host page contains dynamic Living Library state and must not be treated as immutable page HTML. Once the shortcode host is known, GMREXP marks that page `DONOTCACHEPAGE` during early `template_redirect`; shortcode rendering applies the same boundary for the first request.

Successful activation changes invalidate the remembered WordPress host page through `clean_post_cache()` (or the object-cache fallback) while leaving `ActivationStore` as the sole state authority. Optional hooks allow installation-specific cache layers to participate without introducing a core dependency on any cache vendor.


### Phase V.4 detail boundary

`ExpansionDetail` is a read model only. It groups `CatalogueEntry` objects returned by the canonical Catalogue and decorates Expansion identity with current Living Library labels. No content is copied into frontend persistence, and no mechanics are interpreted by the Reading Room.


### Phase V.5 compatibility diagnostics boundary

`CompatibilityInspector` and `CompatibilityReport` remain the sole compatibility semantics. `Frontend\ReadingRoom\CompatibilityDiagnostics` is a presentation adapter only: it derives labels/counts and exposes the existing issues without mutation. Ready, degraded, and blocked never implicitly change Living Library activation.
