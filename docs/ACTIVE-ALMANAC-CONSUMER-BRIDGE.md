# Phase V.11 — The Bridges Open to Adventurers

Phase V.11 turns activation into a stable consumer boundary.

Companion and Tabletop must not copy expansion definitions into their own canonical stores. They connect through the existing Integration Bridge and read `ActiveContentCatalogue`, which exposes only content belonging to currently active Almanacs.

```text
Published Almanac
      ↓
Living Library activation
      ↓
ActiveContentCatalogue
      ↓
 ┌───────────────┬───────────────┐
 ↓               ↓
Companion       Tabletop
```

## Rules

- One canonical definition, multiple consumers.
- Inactive Almanacs remain installed and browsable but are invisible through the active consumer view.
- Activating or deactivating an Almanac changes consumer visibility immediately; no content is copied or rewritten.
- Consumer lookups remain fully qualified by `expansion:type:key`, so identical local keys in different Almanacs do not collide.
- Provenance travels with the existing `CatalogueEntry`.
- Consumers negotiate `consumer-content.active` (and narrower capabilities) through the existing Bridge.
- GMREXP still owns expansion meaning; Companion owns character workflow; Tabletop owns live-play state.

## Public surface

`GreatMarketrealmExpansions\active_content()` returns the request-scoped active consumer catalogue.

A connected Bridge consumer can also use `BridgeConnection::activeContent()` after negotiating an appropriate `consumer-content.*` capability.

The service API is `1.0.0`. Bridge API is `1.1.0` for the new external connection surface; consumers requiring Bridge `1.0.0` remain compatible. Catalogue API remains `1.0.0`.
