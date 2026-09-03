# Catalogue API

Phase I.4 adds the stable, read-only consumer boundary for Great MarketRealm expansion content. Consumers should prefer this API over reading `ContentRegistry`, `ExpansionRegistry`, Almanac files, or loader internals directly.

## PHP API

Use `GreatMarketrealmExpansions\catalogue()` or `Kernel::instance()->catalogue()`. The Catalogue returns read-only `CatalogueEntry` and `CatalogueExpansion` views rather than mutable registry objects.

Canonical content identity is `expansion:type:key`. A lookup may omit the expansion only when that type/key pair is unique across installed packs; ambiguous unqualified lookups raise `AmbiguousCatalogueEntryException` instead of choosing a pack silently.

```php
$catalogue = GreatMarketrealmExpansions\catalogue();

$catalogue->apiVersion();
$catalogue->capabilities();
$catalogue->supports('catalogue.query.tag');

$catalogue->expansions();
$catalogue->expansion('first-almanac');

$catalogue->content('monster', 'milk-carton-mimic', 'first-almanac');
$catalogue->contentByType('monster');
$catalogue->contentByExpansion('first-almanac');

$catalogue->query()
    ->type('monster')
    ->from('first-almanac')
    ->tag('mimic')
    ->get();
```

Query builders are immutable: each filter returns a cloned query. Multiple tags use AND semantics. Results are deterministically sorted by canonical ID.

## API discovery

The initial Catalogue API version is `1.0.0`. Consumers should feature-detect with `supports()` rather than infer behaviour from the plugin version. Initial capabilities cover expansion/content reads, fluent queries, provenance, compatibility, and REST access.

## WordPress REST API

The REST namespace is `great-marketrealm-expansions/v1`. It is intentionally read-only and serializes Catalogue views; it never exposes mutable registries.

- `GET /catalogue` — API version, capabilities and counts.
- `GET /expansions` — installed expansion views.
- `GET /expansions/{expansion}` — one expansion.
- `GET /content` — content search; optional `type`, `expansion`, `tag`, and `key` filters.
- `GET /content/{expansion}/{type}/{key}` — one fully qualified entry.

The Phase I.4 routes are public reads. Future entitlement/private-pack rules, if needed, belong to the Living Library rather than being guessed into this foundation.
