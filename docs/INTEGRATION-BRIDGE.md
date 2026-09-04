# Integration Bridge

Phase I.5 introduces the supported in-process integration boundary for sibling Great MarketRealm plugins.

## Goal

Companion and Tabletop must never need to inspect GMREXP registries, Almanac files, service-container bindings or plugin internals. They discover GMREXP, describe the integration contract they need, and receive a negotiated `BridgeConnection`.

GMREXP remains optional. Consumers should feature-detect the public helper before referring to GMREXP classes:

```php
if (!function_exists('\\GreatMarketrealmExpansions\\bridge')) {
    // GMREXP is absent: continue with the consumer's core behaviour.
    return;
}
```

Do not make Companion or Tabletop boot depend on GMREXP being installed.

## Consumer declaration

A consumer identifies itself with `Consumer`:

```php
use GreatMarketrealmExpansions\Integration\Consumer;

$consumer = new Consumer(
    'great-marketrealm-companion',
    'Great MarketRealm Companion',
    '1.0.0',
    '1.0.0', // minimum Bridge API
    '1.0.0', // minimum Catalogue API
    ['catalogue.content'],
    ['catalogue.query.tag']
);
```

Required capabilities are hard requirements. Optional capabilities are preferences that may be unavailable without preventing a connection.

## Connecting

```php
$connection = \GreatMarketrealmExpansions\bridge()->connect($consumer);

if (!$connection->connected()) {
    foreach ($connection->issues() as $issue) {
        // Log/report $issue->code() and $issue->message() if useful.
    }
    return;
}

$catalogue = $connection->catalogue();
```

A refused connection deliberately returns `null` from `catalogue()`. Consumers cannot bypass a failed compatibility negotiation accidentally.

## Capability negotiation

The Bridge publishes both its own integration capabilities and the Catalogue capabilities available through the connected GMREXP release.

```php
$bridge->apiVersion();
$bridge->capabilities();
$bridge->supports('catalogue.query.tag');

$connection->negotiatedCapabilities();
$connection->missingRequiredCapabilities();
$connection->missingOptionalCapabilities();
$connection->supports('catalogue.query.tag');
```

Current Bridge API version: `1.0.0`.

Bridge-level capabilities begin with:

- `bridge.connect`
- `bridge.consumer.registration`
- `bridge.capability-negotiation`
- `bridge.graceful-degradation`
- `bridge.catalogue.read`

The current Catalogue capabilities are merged into the Bridge's advertised capability set.

## Graceful refusal

Connection failures are represented by `BridgeIssue` values instead of routine integration mismatches throwing across plugin boundaries. Current issue codes include:

- `consumer_conflict`
- `bridge_api_incompatible`
- `catalogue_api_incompatible`
- `required_capability_missing`

Missing optional capabilities are recorded separately and do not make the connection fail.

## Consumer registration

Successful or attempted connections register the consumer identity for the duration of the request. Re-registering the identical consumer is idempotent. A conflicting declaration using the same key but a different contract is refused as `consumer_conflict` rather than silently replacing the first consumer.

The registry is runtime integration state, not user data and not persistent WordPress storage.

## Version contract

The WordPress plugin version, Bridge API version and Catalogue API version are separate contracts:

- plugin release: `0.1.0-alpha5`
- Bridge API: `1.0.0`
- Catalogue API: `1.0.0`

Consumers should negotiate against API versions/capabilities rather than comparing the GMREXP plugin release number.

## Phase I.5 boundary

This phase changes GMREXP only. Companion and Tabletop adapters will be added deliberately in their own repositories later. Until then, this document is the contract those adapters should implement.
