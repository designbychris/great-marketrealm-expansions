# The Living Library

Phase IV turns GMREXP from a passive catalogue of loaded Almanacs into a library that can tell consumers which installed expansion packs are currently active.

Phase IV.1 — **The Keeper Marks the Active Shelves** — establishes the activation boundary without changing canonical content or introducing entitlement assumptions.

## Library API

The Living Library exposes Library API `1.0.0`.

Capabilities:

```text
library.expansions
library.activation.read
library.activation.write
library.content.active
```

The public helper is:

```php
\GreatMarketrealmExpansions\library()
```

and connected consumers may negotiate Library capabilities through the existing Integration Bridge.

## Installed is not the same as active

A pack is **installed** when GMREXP has loaded its trusted Almanac into the canonical Catalogue.

A pack is **active** when the Living Library says consumers should currently treat that installed pack as enabled at the site/library level.

The canonical Catalogue deliberately remains complete even when a pack is inactive. Deactivating a pack does not delete its records, rewrite canonical IDs, unload files, or mutate content.

```text
Catalogue
    = everything installed and known

Living Library
    = which installed shelves are currently active
```

## Backwards-compatible default

An installed expansion with no saved activation decision defaults to **active**.

This preserves all existing behaviour for sites upgrading from Phase III: previously loaded expansion content does not silently disappear from consumers merely because the activation system now exists.

A Keeper may then explicitly deactivate or reactivate a pack.

## Active content

`Library::activeContent()` returns Catalogue entries belonging only to currently active packs.

This gives Companion and Tabletop a clean consumer path:

```php
if (function_exists('\\GreatMarketrealmExpansions\\library')) {
    $entries = \GreatMarketrealmExpansions\library()->activeContent();
}
```

Consumers that require the complete installed catalogue can continue using `catalogue()` instead.

## WordPress persistence

Production activation decisions are stored through `WordPressOptionActivationStore`.

The storage implementation is behind the `ActivationStore` contract, so tests and future campaign/user-scoped activation systems do not need to depend directly on WordPress options.

Phase IV.1 stores **site/library-level activation only**.

It does not claim that a user owns a pack, that a campaign has selected it, or that an external marketplace has granted an entitlement.

## Keeper Catalogue

The existing MarketRealm Expansions wp-admin Catalogue now shows:

- Library API version
- installed pack count
- active pack count
- Active/Inactive status per pack
- secure Activate/Deactivate controls

Canonical Almanac content remains read-only.

The activation form uses the normal WordPress `manage_options` capability, nonce checking, and `admin-post.php` action route.

## Bridge

Library capabilities are additive to Bridge API `1.0.0`.

Existing code that constructs `Bridge` without a Library remains valid. Such a Bridge simply does not advertise Library capabilities.

Connected consumers that negotiate a Library capability receive the Library service through `BridgeConnection::library()`.

## What Phase IV.1 does not decide

No entitlement or commerce model is introduced here.

There is deliberately no assumption about:

- paid versus free expansions
- store ownership
- licences
- per-user entitlements
- campaign-specific activation
- Fellowship activation
- remote availability
- marketplace discovery

Those concerns can be added later only when the product actually needs them.

For now:

```text
Installed?
    Catalogue knows it.

Active?
    Living Library knows it.

Owned?
    Not invented yet.

Enabled for this campaign?
    Future campaign layer.
```

Pippin's contribution to library administration remains simple:

> “If the shelf growls, mark it inactive.”
