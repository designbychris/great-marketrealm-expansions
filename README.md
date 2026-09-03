# Great MarketRealm Expansions

**Great MarketRealm Expansions (GMREXP)** is the expansion-content and optional-rules layer for The Great MarketRealm ecosystem.

It is intended to own canonical expansion packs and structured content definitions — races, subclasses, backgrounds, feats, spells, equipment, monsters, optional rules, adventures and future source-book material — while allowing the **Great MarketRealm Companion** and **Great MarketRealm Tabletop** plugins to consume that content through stable registries and integration contracts.

## Current milestone

**Phase I.1 — The Shelves Are Built**

The initial foundation provides:

- a lightweight application kernel and service container;
- an `ExpansionRegistry` for source/expansion packs;
- a generic `ContentRegistry` for expansion-owned definitions;
- framework-neutral domain objects suitable for use by Companion/Tabletop integrations;
- a PHPUnit 10 test foundation;
- no database schema and no front-end/admin UI yet.

## Development

```bash
composer install
php vendor/bin/phpunit --display-warnings
```

Requires PHP 8.1 or newer.
