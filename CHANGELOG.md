# Changelog












## 0.3.0-alpha7 — Phase III.7: The Adventure Shelf

- Added a dedicated adventure/sourcebook schema.
- Added stable keyed entry points, chapters, nested sections, scenes, and appendices.
- Added optional level guidance and open adventure/scene/progression vocabularies.
- Added scene-level canonical references to encounters, NPCs, monsters, hazards, treasure, conditions, rules, and general content.
- Added open progression modes with keyed branches and Rules Engine-backed branch conditions/mechanics.
- Connected adventure prerequisites and nested chapter/section/scene mechanics to Rules API `1.0.0`.
- Preserved the boundary between canonical book/adventure structure and live campaign/session progress.
- Recorded the certified Phase III.6 baseline: 290 tests / 705 assertions.
- Kept Catalogue API, Bridge API, and Rules API at `1.0.0`.

## 0.3.0-alpha6 — Phase III.6: Marginalia in the Keeper's Handbook

- Added dedicated canonical schemas for `rule` and `condition` content.
- Added optional Keeper-rule scopes, activation guidance, priorities, prerequisites, conflicts, supersession, references, and notes.
- Connected optional-rule prerequisites and generic mechanics to Rules API `1.0.0`.
- Added condition application, duration, stacking, effects, removal methods, escalation/stages, references, and notes.
- Connected condition effects and nested mechanics to Rules API `1.0.0`.
- Kept rule kinds, scope values, activation modes, condition kinds, application types, timing vocabulary, stacking modes, saves, checks, and difficulties extensible.
- Preserved the boundary between canonical definitions, campaign rule activation, and live condition state.
- Recorded the certified Phase III.5 baseline: 266 tests / 646 assertions.
- Kept Catalogue API, Bridge API, and Rules API at `1.0.0`.

## 0.3.0-alpha5 — Phase III.5: The Keeper's Strongbox

- Added a dedicated treasure schema for reusable parcels, rewards, caches, hoards, and sourcebook treasure.
- Added open canonical currency maps and optional nominal value metadata.
- Added canonical item references with quantity, chance, weighting, variants, notes, and nested rules.
- Added nested treasure composition.
- Added keyed random/weighted treasure tables with range-based or relative-weight entries.
- Added keyed treasure selections containing canonical references or structured reward maps.
- Connected top-level treasure grants and nested mechanics to Rules API `1.0.0`.
- Preserved the boundary between canonical reward definitions and Companion/Tabletop ownership, inventory, currency, and claimed-state workflows.
- Recorded the certified Phase III.4 baseline: 248 tests / 603 assertions.
- Kept Catalogue API, Bridge API, and Rules API at `1.0.0`.

## 0.3.0-alpha4 — Phase III.4: Things That Bite Back

- Added a dedicated hazard schema for traps, environmental dangers, and other Keeper-facing hazards.
- Added open severity metadata with optional level guidance.
- Added structured trigger, detection, avoidance, disarming, area, duration, reset, consequence, and escalation data.
- Connected top-level hazard effects and nested hazard mechanics to Rules API `1.0.0`.
- Added canonical cross-content references for encounter/adventure/location integration.
- Preserved a clean boundary between reusable hazard definitions and live Tabletop trigger/disabled/reset state.
- Kept hazard kinds, trigger types, severity ratings, area shapes, checks, and reset types extensible.
- Recorded the certified Phase III.3 baseline: 230 tests / 559 assertions.
- Kept Catalogue API, Bridge API, and Rules API at `1.0.0`.

## 0.3.0-alpha3 — Phase III.3: The Encounter Ledger

- Added a dedicated encounter schema for Keeper-facing expansion content.
- Added canonical participant references with quantities, roles, dispositions, placement metadata, variants, and encounter-specific rules.
- Added keyed encounter waves/reinforcements.
- Added structured environment metadata for locations, terrain, hazards, lighting, weather, and neutral mechanics.
- Added keyed objectives, encounter triggers, difficulty metadata, rewards, references, and Keeper notes.
- Added optional XP budgets and suggested party size/level without hard-coding a difficulty vocabulary.
- Connected nested encounter mechanics to Rules API `1.0.0`.
- Preserved the boundary between canonical encounter content and live Tabletop state.
- Recorded the certified Phase III.2 baseline: 213 tests / 520 assertions.
- Kept Catalogue API, Bridge API, and Rules API at `1.0.0`.

## 0.3.0-alpha2 — Phase III.2: Faces Behind the Counter

- Added a dedicated NPC schema for Keeper-facing expansion content.
- Added structured identity metadata, roles, affiliations, locations, relationships, personality notes, goals, secrets, and mannerisms.
- Added stable keyed dialogue entries and lore hooks.
- Added canonical cross-content references for affiliations, relationships, locations, lore hooks, and combat identities.
- Added optional monster-backed combat identity without duplicating stat blocks.
- Connected NPC-specific `combat.rules[]` to Rules API `1.0.0`.
- Kept social roles, species, relationship types, affiliation roles, and references extensible.
- Recorded the certified Phase III.1 baseline: 198 tests / 481 assertions.
- Kept Catalogue API, Bridge API, and Rules API at `1.0.0`.

## 0.3.0-alpha1 — Phase III.1: The Keeper Opens the Bestiary

- Added a dedicated monster schema for Keeper-facing expansion content.
- Added structured armour class, hit points, movement, abilities, saves, skills, senses, languages, challenge metadata, and defensive traits.
- Added keyed traits, actions, bonus actions, reactions, legendary actions, and lair actions.
- Connected monster trait/action `rules[]` to Rules API `1.0.0`.
- Preserved compatibility with the lightweight First Almanac Milk Carton Mimic proving fixture.
- Kept creature types, alignments, damage types, movement modes, and similar identifiers extensible.
- Recorded the certified Phase II.7 baseline: 183 tests / 432 assertions.
- Kept Catalogue API, Bridge API, and Rules API at `1.0.0`.

## 0.2.0-alpha7 — Phase II.7: The Rules Engine

- Added Rules API `1.0.0`.
- Added canonical grant, choice, modifier, effect, and requirement statements.
- Added RuleStatement plus dedicated rule validation result/error/exception types.
- Added shared Rules Engine validation to existing content rule containers.
- Added recursive validation for explicit nested `rules[]` structures.
- Added public `rules()` helper and Kernel/container service.
- Exposed Rules Engine capabilities and access through the Integration Bridge.
- Added Rules API visibility to the read-only Keeper's Catalogue.
- Recorded the certified Phase II.6 baseline: 161 tests / 385 assertions.
- Kept Catalogue API and Bridge API at `1.0.0`.

## 0.2.0-alpha6 — Phase II.6: The Adventurer's Cupboard

- Added dedicated schemas for weapons, armour, equipment, and magic items.
- Added structured weapon damage, range, properties, proficiency, cost, and weight.
- Added structured armour class, strength requirements, stealth behaviour, properties, cost, and weight.
- Added equipment quantities, consumable state, charges, properties, cost, weight, and effects.
- Added magic-item rarity, attunement, charges, effects, modifiers, and choices.
- Kept categories, rarities, properties, currencies, and similar identifiers extensible rather than hard-coded.
- Preserved the Phase II.7 boundary by validating mechanical payload containers without executing them.
- Recorded the certified Phase II.5 baseline: 145 tests / 357 assertions.
- Kept Catalogue API and Bridge API at 1.0.0.

## 0.2.0-alpha5 — Phase II.5: The Expanded Spellbook

- Added a dedicated spell schema and structural constraint.
- Added spell levels, open school keys, casting time, range, components and duration.
- Added ritual/concentration flags and canonical spell-list membership.
- Added structured targeting, attack, saving throw, effects and scaling containers.
- Kept custom schools extensible and execution semantics reserved for the Phase II.7 Rules Engine.
- Added Phase II.5 PHPUnit coverage and spellbook documentation.
- Certified Phase II.4 at 131 tests / 316 assertions.
- Kept Catalogue API and Bridge API at 1.0.0.

## 0.2.0-alpha4 — Phase II.4: Gifts, Knacks & Questionable Talents

- Added a dedicated playable-feat schema and structural constraint.
- Added structured prerequisites, grants, choices, modifiers and ability-score rules.
- Added explicit feat repeatability and positive maximum-selection validation.
- Preserved compatibility with simple named feats and the bundled First Almanac proving fixture.
- Added Phase II.4 PHPUnit coverage and playable-feat documentation.
- Certified Phase II.3 at 120 tests / 290 assertions.
- Kept Catalogue API and Bridge API at 1.0.0.

## 0.2.0-alpha3 — Phase II.3: Lives Before Adventure

- Added a dedicated playable-background schema and structural constraint.
- Added structured proficiencies, starting equipment, features, languages, generation choices, feats, ability-score rules, and characteristics.
- Added validation for keyed background features and canonical characteristic groups.
- Added Phase II.3 PHPUnit coverage and playable-background documentation.
- Kept Catalogue API and Bridge API at 1.0.0.

## 0.2.0-alpha2 — Phase II.2 — Callings from Distant Shelves

- Added canonical playable `class` and `subclass` schema vocabulary.
- Added class hit-die, maximum-level, saving-throw, proficiency, starting-equipment and primary-ability validation.
- Separated reusable feature definitions from level progression and validate progression feature references.
- Added complete class level coverage, duplicate-level detection and subclass entry-level boundaries.
- Added structured class resources, spellcasting metadata, subclass selection, prerequisites and generation choices.
- Kept Phase II.2 canon-neutral: production ships the model while PHPUnit uses synthetic class/subclass fixtures.
- Documented the class/subclass format and its future Google Docs-to-Almanac import boundary.
- Kept Catalogue API and Bridge API stable at `1.0.0`.

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
