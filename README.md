# Great MarketRealm Expansions

**Great MarketRealm Expansions (GMREXP)** is the expansion-content and optional-rules layer for The Great MarketRealm ecosystem.

It is intended to own canonical expansion packs and structured content definitions — races, subclasses, backgrounds, feats, spells, equipment, monsters, optional rules, adventures and future source-book material — while allowing the **Great MarketRealm Companion** and **Great MarketRealm Tabletop** plugins to consume that content through stable registries and integration contracts.

## Current milestone

**Phase I.2 — Labels on Every Jar**

The foundation now provides a canonical 20-type content catalogue, reusable schemas, typed fields, structured validation errors, provenance/compatibility metadata, and registration-time validation. Subraces and subclasses also carry canonical parent references without prematurely hard-coding every game mechanic.

There is still no database schema or front-end/admin UI: expansion content remains a framework-light domain layer ready for the first real file-backed expansion pack in Phase I.3.

## Development

```bash
composer install
php vendor/bin/phpunit --display-warnings
```

Requires PHP 8.1 or newer.
