# Great MarketRealm Expansions Roadmap

### V.10A.1 — The Librarian Stops Shelving Books in the Delivery Van
Keeper-published Almanacs now live in persistent WordPress uploads storage, while bundled Almanacs remain plugin-owned. Catalogue startup loads both stores; publication, artwork URLs, and metadata corrections respect the persistent boundary.


## Phase I — Foundations ✅

- **I.1 — The Shelves Are Built**: plugin bootstrap, kernel/container, expansion registry, generic content registry, tests. ✅
- **I.2 — Labels on Every Jar**: canonical content-type catalogue, schemas/validation, provenance and compatibility metadata. ✅
- **I.3 — The First Almanac**: first bundled expansion pack, deterministic file discovery, provenance stamping, atomic validation/loading and load reports. ✅
- **I.4 — The Keeper Opens the Catalogue**: immutable catalogue views, fluent read queries, API/capability discovery and read-only REST surfaces for consumers. ✅
- **I.5 — Bridges Between Kingdoms**: versioned consumer identity, capability negotiation, graceful connection/refusal contracts and the stable sibling-plugin Bridge. ✅

## Phase II — The Adventurer's Annex

- **II.1 — Peoples Beyond the Pantry**: structured playable race/subrace schemas, nested race validation and read-only Keeper's Catalogue wp-admin visibility. ✅
- **II.2 — Callings from Distant Shelves**: class/subclass levels, granted features, choices, prerequisites and parent-class relationships. ✅
- **II.3 — Lives Before Adventure**: backgrounds, proficiencies, languages, equipment, features and generation choices. ✅
- **II.4 — Gifts, Knacks & Questionable Talents**: feats, prerequisites, repeatability, grants, choices and modifiers. ✅
- **II.5 — The Expanded Spellbook**: spell definitions, spell lists, levels, schools, components, ranges, durations and scaling. ✅
- **II.6 — The Adventurer's Cupboard**: weapons, armour, equipment and magic items. ✅
- **II.7 — The Rules Engine**: neutral structured grants, choices and modifiers that consumers can interpret without content-specific PHP. ✅

## Phase III — Keeper Content

- **III.1 — The Keeper Opens the Bestiary**: structured monster stat blocks, traits, actions, challenge metadata and Rules Engine integration. ✅
- **III.2 — Faces Behind the Counter**: NPC identities, roles, relationships, dialogue/lore hooks and optional combat references. ✅
- **III.3 — The Encounter Ledger**: encounter compositions, participants, environments, objectives and rewards. ✅
- **III.4 — Things That Bite Back**: hazards, traps, environmental dangers and neutral mechanical effects. ✅
- **III.5 — The Keeper's Strongbox**: structured treasure, parcels, tables and rewards. ✅
- **III.6 — Marginalia in the Keeper's Handbook**: optional DM rules, conditions and expansion-scoped rule modules. ✅
- **III.7 — The Adventure Shelf**: adventures/source-book structures, chapters, scenes and canonical content references. ✅

## Phase IV — The Living Library

Expansion catalogue management, entitlement/availability rules if ever required, pack activation, compatibility reporting, Google Docs/sourcebook-to-Almanac import/export, reviewed transformations and content migrations.

- **IV.1 — The Keeper Marks the Active Shelves**: site/library-level installed-vs-active pack state, Library API and Keeper activation controls. ✅
- **IV.2 — The Librarian Checks the Labels**: compatibility reporting and dependency diagnostics. ✅
- **IV.3 — The Books Arrive by Owl, Cart, or Google Doc**: sourcebook/import staging and provenance-preserving transformations. ✅
- **IV.4 — The Keeper Reads Before Shelving**: review/approval workflow for transformed Almanac content. ✅
- **IV.5 — Moving Shelves Without Losing the Books**: content/schema migrations and version-transition tooling. ✅

## Phase V.10A — The Keeper Corrects the Catalogue ✅

- [x] Administrator-only published Almanac presentation-metadata corrections.
- [x] Immutable canonical expansion key and untouched canonical definition files.
- [x] Safe short-summary, version, name and pack-relative artwork correction.
- [x] Artwork-first 16:9 Library and Browse cards with copy beneath the image.
- [x] Atomic manifest replacement and regression coverage.

### Architectural rule

GMREXP owns expansion content and its meaning. Companion owns character/user workflows. Tabletop owns live play/VTT state. Consumers should reference canonical expansion IDs rather than duplicate expansion mechanics.

## Phase II.3 — Lives Before Adventure

- [x] Dedicated background schema.
- [x] Grouped skill/tool/weapon-style proficiency representation.
- [x] Structured starting equipment and equipment choices.
- [x] Keyed background features with future rules payloads.
- [x] Languages and language choices.
- [x] Optional feats and ability-score rules.
- [x] Personality traits, ideals, bonds, and flaws.
- [x] Generic character-generation choices.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 120 tests / 290 assertions.


## Phase II.4 — Gifts, Knacks & Questionable Talents

- [x] Dedicated feat schema.
- [x] Structured prerequisites.
- [x] Explicit repeatability and optional maximum selections.
- [x] Structured grants.
- [x] Structured character-generation choices.
- [x] Structured modifiers.
- [x] Optional ability-score rules.
- [x] Compatibility with simple named feats and the First Almanac proving fixture.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 131 tests / 316 assertions.


## Phase II.5 — The Expanded Spellbook

- [x] Dedicated spell schema.
- [x] Levels 0–9 and open canonical school keys.
- [x] Structured casting time, range, components and duration.
- [x] Ritual and concentration flags.
- [x] Canonical spell-list membership.
- [x] Structured targeting, spell attacks and saving throws.
- [x] Structured effects and scaling containers for the future Rules Engine.
- [x] Extensible custom schools without schema changes.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 145 tests / 357 assertions.


## Phase II.6 — The Adventurer's Cupboard

- [x] Dedicated schemas for weapons, armour, equipment and magic items.
- [x] Structured weapon damage, properties and range.
- [x] Structured armour class, requirements and stealth behaviour.
- [x] General equipment quantities, consumables, charges, cost and weight.
- [x] Magic-item rarity, attunement, charges, effects, modifiers and choices.
- [x] Open canonical category/rarity/property vocabularies for future MarketRealm oddities.
- [x] Rules Engine boundary retained for executable effects.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 161 tests / 385 assertions.


## Phase II.7 — The Rules Engine

- [x] Rules API `1.0.0`.
- [x] Neutral `grant`, `choice`, `modifier`, `effect`, and `requirement` statements.
- [x] Canonical `RuleStatement` representation.
- [x] Dedicated validation results, errors, and validation exception.
- [x] Shared rule validation across grants, choices, modifiers, effects, and prerequisites.
- [x] Recursive validation of nested `rules[]` in traits/features and future structures.
- [x] Public `rules()` helper and Kernel service.
- [x] Bridge capability negotiation and connected Rules Engine access.
- [x] Rules API visibility in the Keeper's Catalogue.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 183 tests / 432 assertions.


## Phase III.1 — The Keeper Opens the Bestiary

- [x] Dedicated monster schema.
- [x] Backwards compatibility with lightweight First Almanac monster fixtures.
- [x] Structured armour class, hit points, movement and abilities.
- [x] Saving throws, skills, vulnerabilities, resistances, immunities, senses and languages.
- [x] Challenge rating, XP and proficiency metadata.
- [x] Traits, actions, bonus actions, reactions, legendary actions and lair actions.
- [x] Optional spellcasting container.
- [x] Nested Rules Engine validation for trait/action mechanics.
- [x] Open canonical vocabularies for MarketRealm creature oddities.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 198 tests / 481 assertions.


## Phase III.2 — Faces Behind the Counter

- [x] Dedicated NPC schema.
- [x] Lightweight named NPC compatibility.
- [x] Structured identity metadata: aliases, titles, pronouns, species and age.
- [x] Open canonical roles and location references.
- [x] Structured affiliations and directional relationships.
- [x] Stable keyed dialogue entries with optional context.
- [x] Stable keyed lore hooks with triggers and canonical references.
- [x] Narrative personality, goals, secrets and mannerisms.
- [x] Optional canonical monster reference for combat identity.
- [x] Nested Rules Engine validation for NPC-specific combat rules.
- [x] Open vocabularies for future MarketRealm social oddities.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 213 tests / 520 assertions.


## Phase III.3 — The Encounter Ledger

- [x] Dedicated encounter schema.
- [x] Lightweight named encounter compatibility.
- [x] Canonical participant references with quantity, role, disposition, placement and variant metadata.
- [x] Keyed reinforcement/wave groups with triggers and participant composition.
- [x] Structured environment metadata: locations, terrain, hazards, lighting, weather and Keeper notes.
- [x] Keyed objectives with success/failure descriptions.
- [x] Open difficulty ratings, XP budgets and optional party guidance.
- [x] Rewards as canonical references or lightweight structured grants.
- [x] Stable encounter triggers and cross-content references.
- [x] Nested Rules Engine validation throughout encounter structures.
- [x] Clear separation between canonical encounter content and live Tabletop state.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 230 tests / 559 assertions.


## Phase III.4 — Things That Bite Back

- [x] Dedicated hazard schema.
- [x] Lightweight named hazard compatibility.
- [x] Open hazard kind and severity vocabulary with optional level guidance.
- [x] Structured trigger definitions with nested Rules Engine support.
- [x] Detection and disarming checks with numeric or open difficulty vocabulary.
- [x] Stable keyed avoidance methods.
- [x] Structured area and duration metadata.
- [x] Top-level effects validated by Rules API `1.0.0`.
- [x] Stable keyed consequences and escalation stages.
- [x] Reset/repeat behaviour without leaking live-state concerns into canonical content.
- [x] Canonical cross-content references and Keeper notes.
- [x] Clear boundary between reusable hazard definitions and live Tabletop hazard state.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 248 tests / 603 assertions.


## Phase III.5 — The Keeper's Strongbox

- [x] Dedicated treasure schema.
- [x] Lightweight named treasure compatibility.
- [x] Open canonical currency map with non-negative amounts.
- [x] Canonical item references with quantity, chance, weighting and optional variant metadata.
- [x] Nested treasure references for reusable parcel composition.
- [x] Keyed random/weighted treasure tables with roll ranges or relative weights.
- [x] Keyed treasure selections with canonical references or structured reward options.
- [x] Top-level Rules Engine grants and nested rule validation.
- [x] Optional nominal value metadata and open distribution vocabulary.
- [x] Canonical cross-content references and Keeper notes.
- [x] Clear boundary between reusable treasure definitions and Companion/Tabletop award state.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 266 tests / 646 assertions.


## Phase III.6 — Marginalia in the Keeper's Handbook

- [x] Dedicated schemas for canonical `rule` and `condition` content.
- [x] Lightweight named rule/condition compatibility.
- [x] Open rule kinds, applicability scopes and activation metadata.
- [x] Optional rule priorities, prerequisites, conflicts and supersession references.
- [x] Generic optional-rule mechanics validated by Rules API `1.0.0`.
- [x] Structured condition application metadata with open saves/checks/difficulties.
- [x] Structured condition duration and stacking semantics.
- [x] Top-level condition effects and generic Rules Engine statements.
- [x] Stable keyed condition removal methods and escalation/stages.
- [x] Canonical cross-content references and Keeper notes.
- [x] Clear separation between canonical definitions, campaign activation state and live condition state.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 290 tests / 705 assertions.


## Phase III.7 — The Adventure Shelf

- [x] Dedicated adventure/sourcebook schema.
- [x] Lightweight named adventure compatibility.
- [x] Open adventure kind and optional level guidance.
- [x] Stable keyed adventure entry points.
- [x] Rules Engine-backed adventure prerequisites.
- [x] Stable keyed chapters with optional explicit ordering.
- [x] Nested keyed sections and scenes.
- [x] Scene-level canonical references to encounters, NPCs, monsters, hazards, treasure, conditions and rule definitions.
- [x] Nested Rules Engine validation throughout chapter/section/scene structures.
- [x] Stable keyed appendices and canonical reference containers.
- [x] Open progression modes with keyed branching paths and nested rules.
- [x] Clear separation between canonical adventure structure and live campaign/session progress.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 313 tests / 755 assertions.


## Phase IV.1 — The Keeper Marks the Active Shelves

- [x] Library API `1.0.0`.
- [x] Stable installed-versus-active expansion distinction.
- [x] Backwards-compatible default: newly observed installed packs are active until explicitly disabled.
- [x] Pluggable `ActivationStore` boundary.
- [x] WordPress-option production activation persistence.
- [x] In-memory activation store for isolated tests and future alternate scopes.
- [x] Active and inactive expansion views.
- [x] Active-content view without mutating the canonical Catalogue.
- [x] Public `library()` helper and Kernel service.
- [x] Additive Library capability negotiation through Bridge API `1.0.0`.
- [x] Backwards-compatible Bridge construction when no Library is supplied.
- [x] Keeper Catalogue activation status, counts and secure Activate/Deactivate controls.
- [x] No invented ownership, entitlement, marketplace or campaign-scoping model.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 328 tests / 795 assertions.


## Phase IV.2 — The Librarian Checks the Labels

- [x] Ready / degraded / blocked compatibility reports.
- [x] Stable compatibility issue codes and severities.
- [x] Required expansion dependencies.
- [x] Optional expansion dependencies with graceful degradation.
- [x] Installed-versus-active dependency checks.
- [x] Version-constrained dependency checks.
- [x] Expansion conflict declarations.
- [x] Active-only and install-level conflict modes.
- [x] Version-scoped conflicts.
- [x] Optional Companion/Tabletop/consumer version declarations.
- [x] Consumer environment-version verification without guessing installed versions.
- [x] Backwards-compatible Library API capability additions.
- [x] Compatibility reporting through the existing bridged Library service.
- [x] Keeper Catalogue ready/degraded/blocked counts and per-pack diagnostics.
- [x] Diagnostic-only behaviour: no automatic activation mutation.
- [x] Almanac manifest documentation and regression coverage.
- [x] Server-side PHPUnit certification — 351 tests / 843 assertions.


## Phase IV.3 — The Books Arrive by Owl, Cart, or Google Doc

- [x] Import API `1.0.0`.
- [x] Non-executable structured source-document staging.
- [x] Non-executable JSON staging entry point.
- [x] Stable source identity: type, ID, title, version and metadata.
- [x] Protected import provenance stamping.
- [x] Preservation of source-provided provenance and review context.
- [x] Canonical schema validation for staged definitions.
- [x] Stable import warning/error codes.
- [x] Explicit Keeper-review flags.
- [x] Ambiguous candidate-type reporting without guessing.
- [x] Missing content types remain unresolved instead of inferred.
- [x] Missing canonical keys remain unresolved instead of generated from titles.
- [x] Duplicate staged-identity detection.
- [x] Deterministic staging for identical source documents.
- [x] Public `importer()` helper and Kernel service.
- [x] Import API visibility in Keeper diagnostics.
- [x] No Catalogue mutation, activation mutation or runtime Google Docs dependency.
- [x] Google Docs remains a future source adapter into the same neutral staging format.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 375 tests / 921 assertions.


## Phase IV.4 — The Keeper Reads Before Shelving

- [x] Review API `1.0.0`.
- [x] Deterministic review sessions opened from staged `ImportResult` objects.
- [x] Stable per-session review IDs with preserved source record IDs.
- [x] Granular pending / approved / rejected / amended states.
- [x] Explicit approval of valid staged definitions.
- [x] Invalid staged definitions cannot be approved unchanged.
- [x] Rejection of valid, invalid or ambiguous staged records.
- [x] Keeper amendments for unresolved or incorrect staged records.
- [x] Canonical schema validation for every amendment.
- [x] Protected import provenance retained through amendment.
- [x] Review provenance added to amended definitions.
- [x] Source/review provenance spoofing prevented.
- [x] Reversible decisions through reset-to-pending.
- [x] Optional trimmed review notes.
- [x] Review progress and completion counts.
- [x] Approved-definition extraction excludes rejected and pending records.
- [x] Public `reviewer()` helper and Kernel service.
- [x] Review API visibility in Keeper diagnostics.
- [x] No Catalogue mutation, activation, Almanac writing or publication capability.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 401 tests / 1011 assertions.


## Phase IV.5 — Moving Shelves Without Losing the Books

- [x] Migration API `1.0.0`.
- [x] Trusted registered migration-step abstraction.
- [x] Stable content-type / from-version / to-version routes.
- [x] Strictly forward-only migration steps.
- [x] Semantic version comparison using explicit version endpoints.
- [x] Deterministic migration registry ordering.
- [x] Explicit migration planning across chained steps.
- [x] Missing migration paths refused.
- [x] Ambiguous migration paths refused rather than guessed.
- [x] Canonical type/key identity preserved outside migration callbacks.
- [x] Source `ContentDefinition` objects remain unmodified.
- [x] Structured migration failures for route, callback, output, and validation errors.
- [x] Final migrated definitions pass through canonical schema validation.
- [x] Existing provenance preserved.
- [x] Protected ordered migration history appended on success.
- [x] Migration callbacks cannot spoof protected prior provenance/history.
- [x] Same-version migrations are validated no-ops without synthetic history.
- [x] Batch migration diagnostics.
- [x] Atomic batch output: no migrated definition set exposed unless every item succeeds.
- [x] Public `migrations()` helper and Kernel service.
- [x] Migration API and registered-step count visible in Keeper diagnostics.
- [x] No production/canonical migration rules invented.
- [x] No Catalogue, Library activation, filesystem, or publication mutation.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 434 tests / 1099 assertions.


# Phase V — The Keeper Opens the Reading Room

Phase V turns the Living Library infrastructure into a Keeper-facing front-end workflow while preserving the ownership boundaries established in Phases I–IV.

Planned sequence:

- **V.1 — The Reading Room Opens**: front-end shell, Keeper access, stable navigation/routes, read-only Library summary. ✅
- **V.2 — Books Upon the Shelves**: browse installed Expansion packs. ✅
- **V.3 — The Keeper Turns the Key**: front-end Expansion activation controls. ✅
- **V.4 — What Exactly Is in This Book?**: Expansion detail and content-family views. ✅
- **V.5 — The Librarian Raises an Eyebrow**: human-readable compatibility/dependency diagnostics. ✅
- **V.6 — The Keeper's Import Desk**: structured source-material staging UI. ✅
- **V.7 — Pippin Finds the Google Docs**: Google Docs source adapter into neutral Import API documents.
- **V.8 — Red Ink and Questionable Margins**: Keeper review UI.
- **V.9 — The Shelving Trolley**: reviewed-definition to proposed-Almanac workflow.
- **V.10 — The Keeper Rings the Bell**: atomic publication/install workflow.


## Phase V.1 — The Reading Room Opens

- [x] Plugin version `0.5.0-alpha1`.
- [x] Keeper-facing front-end Reading Room shell.
- [x] Stable `/marketrealm-expansions/` Library route.
- [x] Stable reserved Browse, Import Desk and Review Desk routes.
- [x] Dedicated Reading Room route version with one-time rewrite refresh only when route contracts change.
- [x] `[great_marketrealm_expansions]` shortcode using the same renderer.
- [x] Explicit guest / signed-in non-Keeper / Keeper access states.
- [x] Existing `manage_options` Keeper capability reused; no new entitlement model invented.
- [x] Read-only summary sourced from Catalogue and Living Library APIs.
- [x] Installed / active / catalogue-entry / compatibility counts.
- [x] Installed expansion cards sourced from canonical Catalogue/Library views.
- [x] Graceful empty-Library state.
- [x] Non-mutating placeholders for future Phase V desks.
- [x] Responsive Reading Room stylesheet.
- [x] Semantic landmarks, active-navigation state, focus visibility and reduced-motion support.
- [x] Public `reading_room()` helper and Kernel service.
- [x] No front-end activation, import, review, publication or migration mutation.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 464 tests / 1166 assertions.


## Phase V.2 — Books Upon the Shelves

- [x] Plugin version `0.5.0-alpha2`.
- [x] Open the stable `/marketrealm-expansions/browse/` route reserved in V.1.
- [x] Browse navigation marked available without changing route contracts.
- [x] Dedicated `BrowseShelf` read model over Catalogue and Living Library.
- [x] Immutable `BrowseShelfEntry` presentation values.
- [x] Deterministic Expansion ordering by name/key.
- [x] Canonical key, name, version and description displayed.
- [x] Active/inactive Library state displayed read-only.
- [x] Ready/degraded/blocked compatibility state displayed read-only.
- [x] Total canonical entry counts per Expansion.
- [x] Deterministic canonical content-family counts per Expansion.
- [x] Human-readable content-type labels without changing canonical type keys.
- [x] Graceful empty Browse shelf.
- [x] Explicit V.4 boundary for individual Expansion/content details.
- [x] No activation controls before V.3.
- [x] No expanded compatibility diagnostics before V.5.
- [x] No import, review, migration, publication or Catalogue mutation.
- [x] Responsive Browse shelf styling.
- [x] Synthetic-only regression fixtures; no invented canonical mechanics.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 479 tests / 1209 assertions.


## Phase V.2A — The Librarian Realises There Are Two Front Doors

- [x] Plugin version `0.5.0-alpha2.1`.
- [x] Keep the WordPress shortcode page as the primary Reading Room host.
- [x] Add stable section request key `gmrexp_section`.
- [x] Library desk resolves to the clean host-page permalink.
- [x] Browse / Import / Review resolve inside the same host page via section query.
- [x] Existing explicit shortcode `section="..."` remains supported.
- [x] Explicit shortcode section takes precedence over request section.
- [x] Unknown request sections normalize safely to Library.
- [x] Host-page permalink discovered dynamically; no `/expansions/` slug hard-coded.
- [x] Host WordPress page remembered by page ID rather than raw permalink.
- [x] Legacy `/marketrealm-expansions/...` routes redirect to the remembered shortcode host when available.
- [x] Legacy standalone renderer retained as fallback before a host page has been observed.
- [x] Unrelated query arguments preserved when switching Reading Room desks.
- [x] Reading Room route version remains `1.0.0`; rewrite patterns did not change.
- [x] No Catalogue, Library, compatibility, import, review, migration or publication mutation.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 491 tests / 1226 assertions.


## Phase V.3 — The Keeper Turns the Key

- [x] Plugin version `0.5.0-alpha3`.
- [x] Front-end Activate/Deactivate controls on Browse cards.
- [x] Reuse `Library::setActive()` as the single activation authority.
- [x] Existing `ActivationStore` remains the persistence boundary.
- [x] Secure WordPress `admin-post.php` action.
- [x] Existing Keeper `manage_options` capability required.
- [x] WordPress nonce protection.
- [x] Submitted Expansion key normalized and checked as installed.
- [x] Only literal `1` requests activation.
- [x] Redirect back to the Browse desk after mutation.
- [x] Inactive copy explicitly states the Almanac remains installed/canonical.
- [x] Deactivation does not remove Catalogue records or rewrite canonical content.
- [x] Responsive activation-control styling.
- [x] No V.4 detail-page behavior.
- [x] No V.5 expanded compatibility diagnostics.
- [x] No import, review, migration or publication mutation.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 503 tests / 1245 assertions.


## Phase V.3A — The Keeper Turns the Key, the Sign Refuses to Change

- [x] Plugin version `0.5.0-alpha3.1`.
- [x] Mark remembered Reading Room host page dynamic during early `template_redirect`.
- [x] Mark shortcode response dynamic on first host-page render.
- [x] Define standard `DONOTCACHEPAGE` cache-prevention boundary.
- [x] Emit `nocache_headers()` while headers remain available.
- [x] Invalidate remembered host page after successful activation changes.
- [x] Use `clean_post_cache()` as the primary WordPress cache invalidation boundary.
- [x] Fall back to `wp_cache_delete(..., 'posts')` when required.
- [x] Emit `gmrexp/reading_room_dynamic` extension hook.
- [x] Emit `gmrexp/reading_room_activation_changed` extension hook with Expansion/state/page ID.
- [x] Do not invalidate host page for ignored/unknown Expansion submissions.
- [x] Preserve Living Library as the only activation state authority.
- [x] Preserve Reading Room route contract `1.0.0`.
- [x] Document external reverse-proxy/CDN limitation without adding vendor-specific coupling.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 509 tests / 1253 assertions.


## Phase V.4 — What Exactly Is in This Book?

- [x] Plugin version `0.5.0-alpha4`.
- [x] Open installed Almanacs from Browse.
- [x] Dedicated `ExpansionDetail` read model over Catalogue and Living Library.
- [x] Canonical Expansion identity/version/description displayed.
- [x] Current active/inactive and compatibility labels displayed read-only.
- [x] Canonical entry total displayed.
- [x] Entries grouped deterministically by canonical content type.
- [x] Entries ordered deterministically by name/key within each family.
- [x] Human-readable family labels remain presentation-only.
- [x] All-content and single-family read-only views.
- [x] Canonical entry ID, name, type, description and tags displayed when present.
- [x] Safe not-found view for unknown Expansion keys.
- [x] Unknown family filters safely fall back to all content.
- [x] Detail navigation remains inside the shortcode host page.
- [x] No new rewrite route; Reading Room route contract remains `1.0.0`.
- [x] No duplicate frontend content persistence.
- [x] No activation mutation from detail view.
- [x] No V.5 expanded compatibility diagnostics.
- [x] No import, review, migration or publication mutation.
- [x] Synthetic-only regression fixtures; no invented canonical mechanics.
- [x] Responsive detail/family/entry styling.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 529 tests / 1284 assertions.


## Phase V.5 — The Librarian Raises an Eyebrow

- [x] Plugin version `0.5.0-alpha5`.
- [x] Compatibility badge opens the Almanac compatibility explanation.
- [x] Badge links available from Your Library, Browse, and open Almanac views.
- [x] `CompatibilityDiagnostics` presentation adapter over existing `CompatibilityReport`.
- [x] Ready explanation for reports with no compatibility issues.
- [x] Degraded explanation for compatibility warnings.
- [x] Blocked explanation for compatibility blocking issues.
- [x] Issue, warning, and blocking counts.
- [x] Existing issue severity, code, message, and subject exposed read-only.
- [x] Canonical issue codes preserved exactly.
- [x] Compatibility-engine issue ordering preserved.
- [x] No automatic activation/deactivation from compatibility status.
- [x] No installation, removal, migration, publication, or content mutation.
- [x] No new rewrite route; Reading Room route contract remains `1.0.0`.
- [x] Existing Library compatibility semantics remain authoritative.
- [x] Responsive and accessible diagnostic presentation.
- [x] Regression coverage and documentation.
- [x] Server-side PHPUnit certification — 549 tests / 1322 assertions.


## Phase V.6 — The Keeper's Import Desk

- [x] Plugin version `0.5.0-alpha6`.
- [x] Open the reserved Import Desk navigation item.
- [x] Keeper-facing structured JSON staging form.
- [x] Reuse Import API `1.0.0`; no second importer or frontend parsing semantics.
- [x] Dedicated WordPress nonce contract for Import Desk submissions.
- [x] Preserve submitted JSON for correction while escaping output.
- [x] Display source type, ID, title and source version.
- [x] Display staged-record, valid, review, error and warning counts.
- [x] Display staged type/key identities and source context.
- [x] Display Import API warning/error severity, stable code, message, record and field.
- [x] Surface explicit Keeper-review flags without auto-approving them.
- [x] Preserve explicit type/key requirement; frontend never guesses either.
- [x] Ship a synthetic neutral document-shape example only; no invented canon.
- [x] Staging result remains request-local; no review queue persistence in V.6.
- [x] No Catalogue, Library activation, filesystem, review, migration or publication mutation.
- [x] No remote Google Docs fetch; source adapter remains V.7.
- [x] Existing Reading Room route contract remains `1.0.0`.
- [x] Responsive/accessibility styling and regression coverage.
- [x] Server-side PHPUnit certification — 567 tests / 1366 assertions.


## Phase V.6A — The Keeper Locks the Staff Door

- [x] Plugin version `0.5.0-alpha6.1`.
- [x] Record certified V.6 regression-hotfix baseline: 567 tests / 1366 assertions.
- [x] Keep Library and Browse readable for signed-in users.
- [x] Restrict Import Desk to WordPress Administrator-level `manage_options`.
- [x] Restrict Review Desk to the same administrator capability.
- [x] Hide Import/Review navigation entries from non-administrator users.
- [x] Reject direct Import/Review section requests before desk workflows render.
- [x] Prevent direct Import POST staging by non-administrator users.
- [x] Hide Living Library Activate/Deactivate controls from non-administrators.
- [x] Preserve server-side activation capability enforcement.
- [x] Preserve Catalogue, Import API, Review API and route contracts.
- [x] Add access-boundary regression coverage and documentation.
- [x] Server-side PHPUnit certification — 572 tests / 1380 assertions.


## Phase V.7 — Pippin Finds the Google Docs

- [x] Plugin version `0.5.0-alpha7`.
- [x] Record certified V.6A baseline: 572 tests / 1380 assertions.
- [x] Add strict Google Docs document URL/reference parsing.
- [x] Add bounded Google Docs HTML-export acquisition through WordPress HTTP.
- [x] Restrict acquisition to derived `https://docs.google.com/document/d/.../export?format=html` URLs.
- [x] Transform headings and source prose into the neutral Import API document shape.
- [x] Preserve document and heading provenance.
- [x] Leave canonical type/key unresolved rather than guessing.
- [x] Mark extracted records for Keeper review.
- [x] Feed successful acquisition into existing Import API staging.
- [x] Preserve generated JSON in the V.6 correction ledger.
- [x] Keep Import Desk Administrator-only under V.6A.
- [x] Fail safely for inaccessible/private documents.
- [x] No Catalogue, activation, review-queue, filesystem or publication mutation.
- [x] No Google Docs dependency at gameplay/runtime.
- [x] Reading Room route and Import API contracts remain `1.0.0`.
- [x] Regression coverage and documentation.
- [ ] Server-side PHPUnit certification.


## Phase V.8 — Red Ink and Questionable Margins

- [x] Plugin version `0.5.0-alpha8`.
- [x] Record certified V.7 baseline: 590 tests / 1419 assertions.
- [x] Open Review Desk navigation while retaining V.6A Administrator-only access.
- [x] Add explicit Import Desk → Review Desk handoff.
- [x] Add Administrator-private persistent Review queue.
- [x] Preserve unresolved source data for Keeper correction.
- [x] Present source context and Import API issues beside each review record.
- [x] Allow valid staged definitions to be approved unchanged.
- [x] Allow unresolved records to be explicitly classified and amended through Review API validation.
- [x] Allow structural/non-canonical source headings to be ignored/rejected.
- [x] Allow decisions to be reconsidered.
- [x] Keep Review API and Reading Room route contracts at 1.0.0.
- [x] Preserve Reviewed ≠ Published boundary.
- [x] Keep Catalogue, Almanac filesystem, installation and activation untouched.
- [x] Add regression coverage and frontend styling.
- [ ] Server-side PHPUnit certification.


## Phase V.9 — The Shelving Trolley

- [x] Plugin version `0.5.0-alpha9`.
- [x] Record certified V.8 baseline: 609 tests / 1463 assertions.
- [x] Add Proposal API `1.0.0`.
- [x] Assemble Keeper-approved definitions only.
- [x] Exclude pending and rejected records from the proposed Almanac.
- [x] Refuse empty proposals and duplicate canonical identities.
- [x] Persist proposal state beside the Administrator-private Review queue.
- [x] Invalidate stale proposals whenever a Keeper review decision changes.
- [x] Add proposed manifest identity fields.
- [x] Add optional safe relative Library artwork metadata.
- [x] Add matching artwork hero treatment to Your Library and Browse with hover/focus zoom, graceful fallback, and reduced-motion support.
- [x] Tidy long Review Desk source identifiers.
- [x] Preserve `Proposed ≠ Published`.
- [x] Keep Catalogue, installation and activation untouched.
- [x] Refine playable-race Review Desk requirements into Keeper-friendly controls instead of routine JSON, without guessing missing canon.
- [x] Make Review Desk requirements schema-aware for every core content type.
- [x] Expose required fields dynamically when the Keeper chooses a content type.
- [x] Preserve failed amendment drafts for correction.
- [x] Keep canonical validation authoritative; do not invent requirement values.
- [x] Refine Background requirements into friendly proficiency/feature controls.
- [x] Permit required-but-empty Background starting equipment when the source defines none.
- [ ] Server-side PHPUnit certification after schema-aware refinement.

### Phase V.9 — The Keeper Measures the Monsters — complete
- [x] Align expansion monster vocabulary with the Companion shared Bestiary / Steward's Workshop.
- [x] Add friendly Review Desk monster stat controls.
- [x] Preserve unknown source fields as absent rather than guessed.
- [x] Preserve GMREXP ownership of expansion definitions; Companion/Tabletop remain consumers.
- [ ] Wire published expansion monsters into Companion and Tabletop consumer bridges in a later phase.

## Phase V.10 — The Keeper Rings the Bell

- [x] Explicit Proposed → Published Keeper action.
- [x] Complete-review and non-empty-proposal publication gates.
- [x] Atomic sibling staging and canonical pack installation.
- [x] Existing canonical key overwrite protection.
- [x] Canonical schema validation before filesystem commit.
- [x] Existing Almanac Loader as final publication authority.
- [x] Rollback on write, artwork, rename, or loader failure.
- [x] Safe pack-local artwork attachment at publication time.
- [x] Published ≠ Active; newly published pack is left inactive for explicit Keeper activation.
- [x] Reading Room publication state and regression coverage.
- [ ] Server-side PHPUnit certification.


### Phase V.10B — The Keeper Discovers the Clipboard Has Checkboxes ✅

- Your Library is the active shelf; Browse is the full available shelf.
- Bulk Review Desk selection with Select all/none, Accept selected, and Reject selected.
- Invalid/unsafe selections remain pending for individual attention.
- Almanac cover artwork standard: 3:2 landscape, reusable across consumers.
- Boundary preserved: Bulk Review ≠ Publication ≠ Activation.

## Phase V.11 — The Bridges Open to Adventurers

- [x] Build a consumer-safe active-content catalogue over the Living Library.
- [x] Expose only definitions belonging to active Almanacs.
- [x] Preserve fully-qualified `expansion:type:key` canonical identity and provenance.
- [x] Support active reads by type, expansion, expansion+type, and exact identity.
- [x] Make activation/deactivation visible immediately without copying definitions.
- [x] Negotiate `consumer-content.*` capabilities through the existing Integration Bridge.
- [x] Expose the active-content service through Bridge connections, Kernel, and public helper.
- [x] Keep Companion character workflow and Tabletop live-play state outside GMREXP.
- [x] Preserve optional-plugin and legacy Bridge constructor compatibility.
- [x] Add regression coverage and consumer documentation.
- [ ] Wire Companion UI to active expansion races/backgrounds/classes/subclasses/monsters in its own phase.
- [ ] Wire Tabletop Bestiary/encounter tooling to active expansion monsters in its own phase.
- [ ] Server-side PHPUnit certification.
