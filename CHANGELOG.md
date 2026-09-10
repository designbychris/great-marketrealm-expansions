## 0.5.0-alpha12 — Phase V.12 — The Reading Room Gets Its Grand Reopening

- Reworked `/expansions/` into a Great MarketRealm family surface using the Companion parchment, ink, guild-purple, brass/gold and leather visual vocabulary.
- Restyled the masthead, leather navigation rail, catalogue summaries, Almanac shelves, forms, notices and controls without changing their underlying APIs or workflows.
- Preserved the reusable 3:2 Almanac cover contract while giving cards a book/spine treatment.
- Replaced stale future-door copy with a clear explanation of Your Library, Browse and Keeper-only desks.
- Strengthened keyboard focus, reduced-motion, forced-colours and responsive presentation.
- Preserved the distinction between site/library activation and Companion Campaign sharing.

# Changelog

## 0.5.0-alpha10.2.1 — V.10B.1 The Cover Stops Pretending It Hasn't Changed

- Cache-busts Keeper-published Almanac artwork URLs with the actual artwork file modification time.
- Replacing a catalogue-card image at the same pack-relative path now produces a new browser URL immediately, so stale cached cover art cannot survive a successful replacement upload.
- Adds a regression test proving the artwork URL changes when the underlying Keeper artwork file is replaced.


## 0.5.0-alpha10.2 — Phase V.10B — The Keeper Discovers the Clipboard Has Checkboxes

- Your Library now shows active Almanacs only; Browse remains the complete shelf of available installed expansions.
- Added Review Desk checkboxes, selection count, Select all/none, Bulk Accept, and Bulk Reject.
- Bulk acceptance uses the existing validated staged definition and leaves unsafe records pending instead of bypassing validation.
- Bulk decisions invalidate stale Shelving Trolley proposals but do not publish or activate content.
- Standardised Almanac cover presentation to a 3:2 landscape frame and documented the reusable cover-art contract.

### V.10A.1 — The Librarian Stops Shelving Books in the Delivery Van
Keeper-published Almanacs now live in persistent WordPress uploads storage, while bundled Almanacs remain plugin-owned. Catalogue startup loads both stores; publication, artwork URLs, and metadata corrections respect the persistent boundary.


## 0.5.0-alpha10.1 — Phase V.10A: The Keeper Corrects the Catalogue

- Added Metadata Correction API `1.0.0` for published Almanac presentation metadata.
- Added Administrator-only catalogue-card correction controls for name, version, short Library summary and pack-relative artwork.
- Preserved canonical expansion keys and all published content definitions: Correction ≠ Content Revision.
- Added safe artwork replacement/move behaviour and atomic manifest replacement.
- Refined Your Library and Browse cards so artwork is a clean 16:9 hero with metadata and summary beneath it rather than overlaid.
- Added regression coverage for metadata correction boundaries and the revised card structure.
- Recorded certified V.10 baseline: 659 tests / 1622 assertions.

## 0.5.0-alpha10 — Phase V.10: The Keeper Rings the Bell

- Added Publication API `1.0.0` as the explicit Proposed → Published boundary.
- Added Administrator-only Reading Room publication controls for complete proposed Almanacs.
- Publication writes to a sibling staging directory and atomically renames the complete pack into `content/expansions/<canonical-key>/`.
- Reuses the canonical `ContentValidator` and existing `ExpansionFileLoader`; loader failure rolls filesystem publication back.
- Refuses incomplete review, empty proposals, malformed identities, and overwrite of an already-published canonical key.
- Added safe publication-time Library artwork attachment for proposal-relative JPG/PNG/WEBP/GIF paths, bounded to 10 MB.
- Newly published Almanacs are installed into the current Catalogue but explicitly left inactive: Published ≠ Active.
- Preserved the V.9 Review Desk and proposal as the source of publication input; publication does not clear Keeper review decisions.
- Added publication architecture and rollback documentation.
- Recorded certified V.9 baseline: 654 tests / 1600 assertions.

## 0.5.0-alpha9 — Phase V.9: The Shelving Trolley

- Refined Class and Subclass review with friendly saving-throw lists, proficiency groups, feature lines, and level/progression lines while preserving the existing canonical schemas.
- Class progression lines can represent feature-free levels without inventing mechanics; the canonical class validator still requires every level through `max_level`.
- Subclass progression may remain sparse and records only the source-established feature-grant levels.
- Parent class, entry level, hit die, and maximum level remain typed ordinary fields; Advanced content data remains available for optional resources, spellcasting, choices, and other richer structures.
- Refined Background review with friendly proficiency-group and feature-line editors while keeping canonical schema validation authoritative.
- Corrected Background `starting_equipment` semantics: the field remains required in canonical data but may explicitly be an empty array when the source defines no starting equipment.
- Review Desk now accepts `[]` or a blank Starting Equipment control for that intentional empty state instead of forcing invented equipment.
- Refined the schema-aware Review Desk with Keeper-friendly playable-race controls for creature type, fixed size, walking speed, languages, and traits while preserving canonical schema validation.
- Refined the Review Desk to read required fields directly from the canonical `SchemaRegistry`.
- Added dynamic required-field controls for all core content types.
- Added friendly typed controls for required string/integer/number/boolean values and JSON editors for required map/array values.
- Fixed Magic Item review so `category` and `rarity` can be supplied before canonical acceptance.
- Preserved failed amendment drafts so Keeper corrections do not lose the selected type and form values.
- Kept `ReviewSession::amend()` and the canonical validator authoritative; the frontend does not invent content values.
- Added Proposal API `1.0.0` and proposed-Almanac assembly.
- Added Review Desk Shelving Trolley UI.
- Proposed Almanacs contain only Keeper-approved definitions.
- Pending and rejected source records remain excluded.
- Empty proposals and duplicate canonical identities are refused.
- Proposal state persists privately with the Administrator review queue and is invalidated after later review decisions.
- Added proposed manifest key/name/version/description and optional safe relative `artwork` metadata.
- Added optional expansion-pack artwork treatment to both Your Library and Browse cards with consistent imagery, gentle hover/focus zoom, graceful fallback, and reduced-motion support.
- Tidied long Google Doc source identifiers in the Review Desk.
- Preserved `Proposed ≠ Published`: no Almanac files, Catalogue mutation, installation or activation.
- Recorded certified V.8 baseline: 609 tests / 1463 assertions.

## 0.5.0-alpha8 — Phase V.8: Red Ink and Questionable Margins

- Opened the Administrator-only Review Desk.
- Added explicit staged-source handoff from Import Desk to Review Desk.
- Added per-administrator WordPress user-meta review queue persistence.
- Preserved unresolved source data so Google heading names/prose remain available during classification.
- Added approve, classify/amend, ignore/reject, reconsider and clear-desk workflows.
- Routed classification/amendment through the existing Review API and canonical schema validation.
- Kept Review API and Reading Room route contracts at `1.0.0`.
- Preserved `Reviewed ≠ Published`: no Catalogue mutation, Almanac writing, installation, activation or publication.
- Recorded certified V.7 baseline: 590 tests / 1419 assertions.

## 0.5.0-alpha7 — Phase V.7: Pippin Finds the Google Docs

- Added strict Google Docs URL/document identity parsing and a bounded HTML-export source adapter.
- Added Google Docs acquisition to the administrator-only Import Desk.
- Transformed document headings/source prose into neutral review records and reused Import API `1.0.0` for staging.
- Deliberately left canonical content type/key unresolved rather than guessing from headings.
- Preserved Google document/source/heading provenance and marked extracted records for Keeper review.
- Added stable acquisition errors for invalid URLs, inaccessible/empty/oversized documents and unavailable adapter state.
- Restricted remote acquisition to derived HTTPS `docs.google.com` document export URLs; no generic URL fetcher.
- Kept private Google Docs outside the unauthenticated built-in fetcher and documented the future authenticated-connector seam.
- Added no Catalogue, activation, review-queue, filesystem, publication or runtime Google Docs dependency.
- Kept Reading Room route contract `1.0.0` and Import API `1.0.0` unchanged.
- Recorded the certified Phase V.6A baseline: 572 tests / 1380 assertions.

## 0.5.0-alpha6.1 — Phase V.6A: The Keeper Locks the Staff Door

- Restricted the Import Desk and Review Desk to the WordPress `manage_options` capability.
- Kept Library and Browse available as read-only desks for signed-in users.
- Hid Import/Review navigation entries from non-administrator accounts.
- Added section-level authorization so manually entered Import/Review URLs cannot bypass the UI boundary.
- Prevented non-administrator Import POST staging by gating the section before its workflow executes.
- Hid Living Library Activate/Deactivate controls from non-administrator users while preserving the existing server-side capability check.
- Kept Catalogue, Import API, Review API and route contracts unchanged.
- Recorded the certified V.6 regression-hotfix baseline: 567 tests / 1366 assertions.

## 0.5.0-alpha6 — Phase V.6: The Keeper's Import Desk

- Opened the Reading Room Import Desk.
- Added Keeper-facing neutral structured JSON staging through Import API `1.0.0`.
- Added dedicated nonce/input contracts for frontend staging submissions.
- Added source identity and staged-record diagnostics with validity/review/error/warning counts.
- Exposed existing Import API issue severity, code, message, record and field values.
- Preserved explicit content type/key requirements without frontend guessing.
- Added escaped source-context display and correction-friendly submitted JSON retention.
- Shipped only a synthetic neutral example document; no canonical mechanics invented.
- Kept staging request-local with no Catalogue, activation, filesystem, review-queue or publication mutation.
- Kept remote Google Docs acquisition reserved for V.7.
- Kept Reading Room route contract at `1.0.0`.
- Recorded the certified Phase V.5 baseline: 549 tests / 1322 assertions.

## 0.5.0-alpha5 — Phase V.5: The Librarian Raises an Eyebrow

- Turned Ready / Degraded / Blocked Reading Room badges into links to human-readable compatibility diagnostics.
- Added `CompatibilityDiagnostics` as a presentation adapter over the existing `CompatibilityReport`.
- Added Ready explanations with explicit zero-issue state.
- Added Degraded warning presentation using existing compatibility-engine issue codes/messages/subjects.
- Added Blocked issue presentation without automatically changing activation state.
- Added issue, warning, and blocking counts.
- Preserved compatibility issue ordering and canonical issue codes from the Living Library.
- Added compatibility diagnostic links from Your Library, Browse, and open Almanac views.
- Kept diagnostics inside the existing open-Almanac view with no new rewrite route.
- Kept Reading Room route contract `1.0.0` and Library API `1.0.0` unchanged.
- Added no new compatibility semantics and no content/activation/import/review/migration/publication mutation.
- Recorded the certified Phase V.4 baseline: 529 tests / 1284 assertions.

## 0.5.0-alpha4 — Phase V.4: What Exactly Is in This Book?

- Added read-only installed-Almanac detail views to the Reading Room.
- Added `ExpansionDetail` as a presentation read model over Catalogue and Living Library.
- Added deterministic canonical content-family grouping and family filtering.
- Added canonical entry summaries with IDs, names, types, descriptions and tags when present.
- Added Open Almanac links and direct family links from Browse cards.
- Kept all detail navigation inside the existing shortcode host page.
- Added safe unknown-Expansion and unknown-family behavior.
- Added no new rewrite route and kept Reading Room route contract `1.0.0`.
- Added no frontend content persistence or mechanics interpretation.
- Kept activation mutation, expanded compatibility diagnostics, import, review, migration and publication out of the detail view.
- Recorded the certified Phase V.3A baseline: 509 tests / 1253 assertions.

## 0.5.0-alpha3.1 — Phase V.3A: The Keeper Turns the Key, the Sign Refuses to Change

- Fixed stale activation labels on the plain Reading Room shortcode host page.
- Marked the remembered Reading Room host dynamic during early `template_redirect`.
- Marked shortcode responses with the standard `DONOTCACHEPAGE` boundary.
- Added no-cache headers when response headers remain available.
- Invalidated the remembered WordPress host page after successful activation mutations.
- Used `clean_post_cache()` with a WordPress object-cache fallback.
- Added vendor-neutral Reading Room dynamic/activation-changed hooks for additional cache integrations.
- Kept Living Library/ActivationStore as the single activation-state authority.
- Changed no route, Catalogue, compatibility, import, review, migration or publication semantics.
- Recorded the certified Phase V.3 baseline: 503 tests / 1245 assertions.

## 0.5.0-alpha3 — Phase V.3: The Keeper Turns the Key

- Added front-end Activate/Deactivate controls to Reading Room Browse cards.
- Reused `Library::setActive()` and the existing ActivationStore rather than introducing a second activation model.
- Added secure WordPress admin-post handling with Keeper capability and nonce protection.
- Normalized submitted Expansion keys and ignored unknown packs.
- Added redirect-back-to-Browse behavior after activation changes.
- Clarified that inactive Almanacs remain installed and canonical.
- Added responsive activation-control styling and update feedback.
- Deliberately left V.4 detail pages and V.5 expanded compatibility diagnostics out of scope.
- Changed no Catalogue content, import, review, migration or publication semantics.
- Recorded the certified Phase V.2A baseline: 491 tests / 1226 assertions.

## 0.5.0-alpha2.1 — Phase V.2A: The Librarian Realises There Are Two Front Doors

- Fixed Reading Room navigation leaving the WordPress page that hosts `[great_marketrealm_expansions]`.
- Made the shortcode host page the preferred frontend Reading Room shell.
- Added stable `gmrexp_section` desk selection within the host-page permalink.
- Preserved explicit shortcode `section="..."` behavior with precedence over request selection.
- Added safe unknown-section fallback to Your Library.
- Added dynamic host-permalink discovery with no hard-coded `/expansions/` slug.
- Remembered the shortcode host by WordPress page ID so later slug/permalink changes remain resolvable.
- Redirected legacy `/marketrealm-expansions/...` routes to the remembered host page when available.
- Retained the legacy standalone renderer as a backwards-compatible fallback before a host is known.
- Preserved unrelated host-page query arguments while changing Reading Room desks.
- Kept Reading Room route contract `1.0.0`; rewrite patterns are unchanged.
- Changed no Catalogue, Library, compatibility, import, review, migration or publication semantics.
- Recorded the certified Phase V.2 baseline: 479 tests / 1209 assertions.

## 0.5.0-alpha2 — Phase V.2: Books Upon the Shelves

- Opened the Reading Room Browse desk at the stable route reserved in V.1.
- Added `BrowseShelf` as a deterministic read model over Catalogue and Living Library.
- Added immutable `BrowseShelfEntry` presentation values.
- Added canonical Expansion key, name, version and description browsing.
- Added read-only active/inactive and ready/degraded/blocked labels.
- Added per-Expansion canonical entry totals and deterministic content-family counts.
- Added human-readable display labels without changing canonical content type keys.
- Added graceful empty-Browse rendering.
- Added responsive Browse shelf/card styling.
- Kept the Reading Room route contract at `1.0.0`; no rewrite contract changed.
- Deliberately omitted V.3 activation controls, V.4 detail pages and V.5 expanded compatibility diagnostics.
- Added no import, review, migration, publication or Catalogue mutation capability.
- Shipped no invented canonical mechanics; PHPUnit uses synthetic proving Almanacs.
- Recorded the certified Phase V.1 baseline: 464 tests / 1166 assertions.
- Kept Catalogue API, Bridge API, Rules API, Library API, Import API, Review API and Migration API at `1.0.0`.

## 0.5.0-alpha1 — Phase V.1: The Reading Room Opens

- Began Phase V — The Keeper Opens the Reading Room.
- Added the Keeper-facing front-end Reading Room shell.
- Added stable `/marketrealm-expansions/`, Browse, Import Desk and Review Desk routes.
- Added a dedicated Reading Room route version and one-time WordPress rewrite refresh only when route contracts change.
- Added `[great_marketrealm_expansions]` using the same renderer as the virtual routes.
- Reused the established `manage_options` Keeper capability with explicit guest and forbidden gates.
- Added read-only installed, active, catalogue-entry and compatibility summaries backed by Catalogue and Library APIs.
- Added installed Expansion cards without introducing a second content or activation store.
- Added graceful empty-Library rendering.
- Added deliberate non-mutating placeholders for future Browse, Import and Review workflows.
- Added responsive/accessibility-aware Reading Room styling.
- Added public `reading_room()` access through the Kernel/service container.
- Deliberately omitted front-end activation, import, review, migration and publication mutation.
- Recorded the certified Phase IV.5 baseline: 434 tests / 1099 assertions.
- Kept Catalogue API, Bridge API, Rules API, Library API, Import API, Review API and Migration API at `1.0.0`.

## 0.4.0-alpha5 — Phase IV.5: Moving Shelves Without Losing the Books

- Added Migration API `1.0.0`.
- Added trusted registered migration steps with explicit content type, source version and target version.
- Enforced strictly forward-only migration transitions.
- Added deterministic migration planning across chained explicit steps.
- Refused missing and ambiguous migration routes instead of guessing.
- Preserved canonical content type/key identity outside migration callbacks.
- Kept source ContentDefinition objects immutable during migration.
- Added structured route, step-output, callback and canonical-validation failures.
- Validated final migrated definitions through the existing canonical schema pipeline.
- Preserved source/import provenance and appended protected ordered migration history.
- Prevented migration callbacks from spoofing protected provenance/history.
- Added semantically equivalent same-version validation no-ops.
- Added batch migration diagnostics with all-or-nothing migrated-definition output.
- Added public `migrations()` access through the Kernel/service container.
- Added Migration API and registered-step counts to Keeper diagnostics.
- Shipped no invented production migration rules; tests use synthetic proving transforms.
- Deliberately omitted Catalogue mutation, activation, filesystem writes and publication.
- Recorded the certified Phase IV.4 baseline: 401 tests / 1011 assertions.
- Kept Catalogue API, Bridge API, Rules API, Library API, Import API and Review API at `1.0.0`.

## 0.4.0-alpha4 — Phase IV.4: The Keeper Reads Before Shelving

- Added Review API `1.0.0`.
- Added deterministic review sessions over staged Import API results.
- Added granular pending, approved, rejected and amended review states.
- Allowed structurally valid staged definitions to be explicitly approved.
- Prevented invalid/unresolved staged definitions from being approved unchanged.
- Added explicit rejection for any staged record.
- Added Keeper amendments validated through the canonical schema pipeline.
- Preserved protected import provenance and source context through amendments.
- Added protected review provenance for amended definitions.
- Added reversible reset-to-pending decisions and optional review notes.
- Added review progress/completion counts and approved-definition extraction.
- Added public `reviewer()` access through the Kernel/service container.
- Added Review API visibility to Keeper diagnostics.
- Deliberately omitted publication, Catalogue mutation, activation and Almanac-writing capabilities.
- Recorded the certified Phase IV.3 baseline: 375 tests / 921 assertions.
- Kept Catalogue API, Bridge API, Rules API, Library API and Import API at `1.0.0`.

## 0.4.0-alpha3 — Phase IV.3: The Books Arrive by Owl, Cart, or Google Doc

- Added Import API `1.0.0`.
- Added non-executable structured-document and JSON staging.
- Added source identity, source context and protected provenance stamping.
- Added canonical schema validation for staged external definitions.
- Added stable import issue codes, warning/error severity, review flags and serialisation.
- Added explicit ambiguity reporting without guessing missing content types.
- Refused to generate missing canonical keys from source titles.
- Added duplicate staged-identity detection and deterministic staging.
- Added public `importer()` access through the Kernel/service container.
- Added Import API visibility to Keeper diagnostics.
- Kept staging separate from publication: no Catalogue, activation or Almanac mutation occurs.
- Preserved the future Google Docs integration as a source-specific adapter rather than a runtime dependency.
- Recorded the certified Phase IV.2 baseline: 351 tests / 843 assertions.
- Kept Catalogue API, Bridge API, Rules API and Library API at `1.0.0`.

## 0.4.0-alpha2 — Phase IV.2: The Librarian Checks the Labels

- Added ready/degraded/blocked Living Library compatibility reports.
- Added stable compatibility issue codes, severities, subjects, and serialisation.
- Added required and optional expansion dependencies with active-state and version checks.
- Added compact and structured dependency manifest forms.
- Added expansion conflicts with active-only/install-level and optional version scoping.
- Added optional consumer-version requirements beneath manifest compatibility metadata.
- Added conservative version-constraint evaluation with comma-separated comparisons.
- Added Library capabilities for compatibility reports, dependencies, and conflicts.
- Upgraded the Keeper Catalogue with compatibility counts and per-pack diagnostic messages.
- Kept compatibility evaluation diagnostic-only; no pack is automatically activated or deactivated.
- Recorded the certified Phase IV.1 baseline: 328 tests / 795 assertions.
- Kept Catalogue API, Bridge API, Rules API, and Library API at `1.0.0`.

## 0.4.0-alpha1 — Phase IV.1: The Keeper Marks the Active Shelves

- Began Phase IV — The Living Library.
- Added Library API `1.0.0` and public `library()` access.
- Added installed-versus-active expansion state without mutating the canonical Catalogue.
- Added backwards-compatible default activation for installed packs with no saved decision.
- Added pluggable activation storage with WordPress-option persistence and an in-memory test implementation.
- Added active/inactive expansion views and active-content filtering.
- Added Library capability negotiation and connected Library access through Bridge API `1.0.0`.
- Preserved backwards compatibility for Bridge consumers that do not use the Living Library.
- Upgraded the Keeper Catalogue with Library status, active-pack counts, and secure Activate/Deactivate controls.
- Deliberately deferred ownership, entitlement, marketplace and campaign-specific activation semantics.
- Recorded the certified Phase III.7 baseline: 313 tests / 755 assertions.
- Kept Catalogue API, Bridge API, and Rules API at `1.0.0`.

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

### Phase V.9 — The Keeper Measures the Monsters
- Added a Keeper-friendly Monster Bestiary contract to the Review Desk, aligned with the established Companion Steward's Workshop vocabulary.
- Added canonical monster projection fields for mythic actions, player-safe description, Field Guide visibility, and Keeper notes.
- Preserved blank/unknown monster fields instead of inventing source mechanics.
- Added regression coverage for Companion-ready monster review and sparse/reference monsters.

## 0.5.0-alpha11 — Phase V.11 — The Bridges Open to Adventurers

- Added `ActiveContentCatalogue` API 1.0.0 as the stable consumer view of active Almanac content.
- Added active reads by content type, expansion, expansion+type, and fully-qualified canonical identity.
- Kept inactive Almanacs installed and browsable while excluding them from Companion/Tabletop-facing content.
- Preserved canonical `CatalogueEntry` identity and provenance rather than copying expansion records into consumers.
- Added `consumer-content.*` Bridge capabilities and `BridgeConnection::activeContent()`; Bridge API advances additively to `1.1.0`.
- Added Kernel and `active_content()` helper access while preserving legacy Bridge construction.
- Documented the one-definition/multiple-consumers boundary and added regression coverage.
