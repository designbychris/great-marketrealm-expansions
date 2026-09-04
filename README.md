# Great MarketRealm Expansions

**Great MarketRealm Expansions (GMREXP)** is the expansion-content and optional-rules layer for The Great MarketRealm ecosystem.

It owns canonical expansion packs and structured content definitions — races, subclasses, backgrounds, feats, spells, equipment, monsters, optional rules, adventures and future source-book material — while allowing the **Great MarketRealm Companion** and **Great MarketRealm Tabletop** plugins to consume that content through stable registries and integration contracts.

## Current milestone

**Phase I.5 — Bridges Between Kingdoms**

GMREXP now exposes a formal, versioned integration Bridge for sibling Great MarketRealm plugins. Consumers identify themselves, declare minimum Bridge/Catalogue API versions, distinguish required from optional capabilities, and receive a negotiated `BridgeConnection` rather than reaching into GMREXP internals.

A compatible connection exposes the read-only Catalogue. Missing optional capabilities degrade cleanly; missing required capabilities or incompatible API versions return structured refusal reasons and no Catalogue object. Consumer discovery remains optional by design, so Companion and Tabletop can continue to boot and operate when GMREXP is absent.

The bundled **First Almanac** still proves the complete file → validation → registry → catalogue pipeline, while the new Bridge completes the supported path from that catalogue into neighbouring plugins.

See `docs/INTEGRATION-BRIDGE.md` for the sibling-plugin contract, `docs/CATALOGUE-API.md` for catalogue reads, and `docs/ALMANAC-FORMAT.md` for source packs.

## Development

```bash
composer install
php vendor/bin/phpunit --display-warnings
```

Requires PHP 8.1 or newer.
