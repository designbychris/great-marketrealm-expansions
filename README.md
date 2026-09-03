# Great MarketRealm Expansions

**Great MarketRealm Expansions (GMREXP)** is the expansion-content and optional-rules layer for The Great MarketRealm ecosystem.

It owns canonical expansion packs and structured content definitions — races, subclasses, backgrounds, feats, spells, equipment, monsters, optional rules, adventures and future source-book material — while allowing the **Great MarketRealm Companion** and **Great MarketRealm Tabletop** plugins to consume that content through stable registries and integration contracts.

## Current milestone

**Phase I.4 — The Keeper Opens the Catalogue**

GMREXP now exposes a stable, read-only Catalogue boundary over the registries populated by the Almanac loader. Sibling plugins can use the PHP `catalogue()` helper, while browser/client consumers can use the read-only WordPress REST surface under `great-marketrealm-expansions/v1`.

Catalogue results are immutable view objects, content keeps its fully qualified `expansion:type:key` identity, unqualified ambiguous lookups are rejected, and fluent queries can filter by type, expansion, key and tags. API-version and capability discovery let Companion/Tabletop feature-detect integrations without coupling themselves to a GMREXP plugin release number.

The bundled **First Almanac** remains the proving pack, supplying Iron Stomach and Milk Carton Mimic through the full file → validation → registry → catalogue pipeline.

See `docs/CATALOGUE-API.md` for the consumer contract and `docs/ALMANAC-FORMAT.md` for the source-pack format.

## Development

```bash
composer install
php vendor/bin/phpunit --display-warnings
```

Requires PHP 8.1 or newer.
