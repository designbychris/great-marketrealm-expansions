# Changelog

## 0.2.0-alpha1 — Phase II.1 — Peoples Beyond the Pantry

- Added full playable `race` and inheriting `subrace` schema vocabulary.
- Added schema-level structural constraints for fixed-or-choice size, movement speeds, language lists and structured traits.
- Added validation for proficiencies, resistances, senses, ability-score rule maps, language choices and character-generation choice maps.
- Extended `ContentSchema` with reusable domain constraints so future content families can enforce nested rules without hard-coding them into the registry.
- Kept Phase II.1 canon-neutral: production ships the model while tests use synthetic race fixtures.
- Added the read-only **MarketRealm Expansions** wp-admin Keeper's Catalogue with Almanac/API/content diagnostics.
- Added playable-race and admin-catalogue documentation plus regression coverage.

## 0.1.0-alpha5 — Phase I.5 — Bridges Between Kingdoms

- Added the stable `Bridge` service and public `bridge()` helper for sibling-plugin integration.
- Added immutable consumer declarations with semantic Bridge/Catalogue API requirements.
- Added required-versus-optional capability negotiation and graceful degradation.
- Added structured `BridgeConnection` and `BridgeIssue` results instead of routine compatibility exceptions crossing plugin boundaries.
- Refused incompatible connections now deliberately expose no Catalogue object.
- Added request-lifetime consumer registration with idempotent identical registration and conflict detection.
- Kept GMREXP optional for Companion/Tabletop through documented feature-detection and no hard plugin dependency.
- Added the Integration Bridge contract documentation and regression coverage.

## 0.1.0-alpha4 — Phase I.4 — The Keeper Opens the Catalogue

- Added the stable read-only `Catalogue` consumer service and public `catalogue()` helper.
- Added immutable `CatalogueEntry` and `CatalogueExpansion` view objects.
- Added fully qualified `expansion:type:key` content identity and ambiguity-safe unqualified lookup.
- Added immutable fluent catalogue queries for type, expansion, key and tag filters.
- Added deterministic catalogue ordering plus convenience reads by type/expansion.
- Added Catalogue API version `1.0.0`, capability discovery and feature detection.
- Added read-only WordPress REST endpoints under `great-marketrealm-expansions/v1`.
- Added consumer/API documentation and Catalogue regression coverage.

## 0.1.0-alpha3 — Phase I.3 — The First Almanac

- Added deterministic file-backed expansion discovery and loading.
- Added manifest parsing with preserved expansion metadata.
- Added per-definition source provenance stamping without absolute server paths.
- Added whole-pack schema preflight and duplicate/collision detection.
- Added atomic registry commit with rollback support.
- Added structured `ExpansionLoadResult` reporting.
- Added Kernel and helper access to the expansion loader.
- Added automatic loading of bundled packs from `content/expansions/`.
- Added the bundled `first-almanac` proving pack with Iron Stomach and Milk Carton Mimic sample entries.
- Added Almanac format/trust-boundary documentation and loader regression coverage.

## Phase I.2 Hotfix — CLI bootstrap

- Allow Composer to load `src/functions.php` before WordPress defines `ABSPATH`.
- Fix silent early exit when running PHPUnit or `phpunit --version` from CLI.

## 0.1.0-alpha2 — Phase I.2 — Labels on Every Jar

- Added the canonical 20-type content catalogue.
- Added reusable content schemas and typed field definitions.
- Added structured validation results/errors and registration-time validation.
- Added common provenance, compatibility, description and tag metadata fields.
- Added parent relationship requirements for subraces and subclasses.
- Exposed content-type and schema registries through the kernel and helper API.
- Added schema architecture documentation and regression coverage.

## 0.1.0-alpha1 — Phase I.1

- Created initial WordPress plugin bootstrap.
- Added application kernel and lightweight service container.
- Added expansion-pack model and registry.
- Added generic content definition and registry.
- Added public registry helper functions.
- Added PHPUnit 10 test foundation.
- Added initial architecture and roadmap documentation.
