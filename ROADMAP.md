# Great MarketRealm Expansions Roadmap

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
- **III.5 — The Keeper's Strongbox**: structured treasure, parcels, tables and rewards.
- **III.6 — Marginalia in the Keeper's Handbook**: optional DM rules, conditions and expansion-scoped rule modules.
- **III.7 — The Adventure Shelf**: adventures/source-book structures, chapters, scenes and canonical content references.

## Phase IV — The Living Library

Expansion catalogue management, entitlement/availability rules if ever required, pack activation, compatibility reporting, Google Docs/sourcebook-to-Almanac import/export, reviewed transformations and content migrations.

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
- [ ] Server-side PHPUnit certification.
