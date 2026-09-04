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
- **II.5 — The Expanded Spellbook**: spell definitions, spell lists, levels, schools, components, ranges, durations and scaling.
- **II.6 — The Adventurer's Cupboard**: weapons, armour, equipment and magic items.
- **II.7 — The Rules Engine**: neutral structured grants, choices and modifiers that consumers can interpret without content-specific PHP.

## Phase III — Keeper Content

Monsters, NPCs, encounters, hazards, treasure, optional DM rules and adventure/source-book content.

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
- [ ] Server-side PHPUnit certification.
