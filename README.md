# Great MarketRealm Expansions

**Great MarketRealm Expansions (GMREXP)** is the expansion-content and optional-rules layer for The Great MarketRealm ecosystem.

It owns canonical expansion packs and structured content definitions — races, subclasses, backgrounds, feats, spells, equipment, monsters, optional rules, adventures and future source-book material — while allowing the **Great MarketRealm Companion** and **Great MarketRealm Tabletop** plugins to consume that content through stable registries and integration contracts.

## Current milestone

**Phase I.3 — The First Almanac**

The plugin now has a deterministic, file-backed Almanac loader. Expansion packs provide a manifest plus individual content-definition files. A complete pack is discovered, provenance-stamped, schema-validated, collision-checked and only then committed atomically to the expansion/content registries.

The first bundled proving pack, **The First Almanac**, loads two lightweight foundation entries: **Iron Stomach** and **Milk Carton Mimic**. They exist to certify the ingestion pipeline rather than to define their full game mechanics yet.

WordPress automatically loads bundled packs from `content/expansions/` during `plugins_loaded`. Consumers can also obtain the loader from the Kernel or the `loader()` helper.

See `docs/ALMANAC-FORMAT.md` for the pack format and trust boundary.

## Development

```bash
composer install
php vendor/bin/phpunit --display-warnings
```

Requires PHP 8.1 or newer.
