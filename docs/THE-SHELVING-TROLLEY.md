# Phase V.9 — The Shelving Trolley

V.9 assembles Keeper-approved Review Desk definitions into a persistent **proposed Almanac**.

```text
Import source
    ↓
Review Desk
    ↓
Keeper-approved definitions only
    ↓
The Shelving Trolley
    ↓
Proposed Almanac
    ↓
STOP
```

## Proposal API

`AlmanacProposalService::API_VERSION = 1.0.0`

A proposal contains:

- proposed manifest identity (`key`, `name`, `version`, `description`);
- optional pack metadata such as `artwork`;
- original source provenance;
- Keeper-approved definitions only;
- counts for pending and rejected review records;
- deterministic content-family counts.

Pending definitions are excluded. Rejected source records are excluded. A proposal requires at least one approved definition and refuses duplicate canonical identities.

A new Keeper decision invalidates the stored proposal so stale trolley contents cannot silently survive a changed review.

## Persistence

The proposal is stored alongside the Administrator-private Review Desk queue. This is assembly state, not canonical Catalogue state.

## Proposed is not published

**Proposed ≠ Published.**

V.9 does not:

- write an Almanac directory;
- write PHP content files;
- install an expansion;
- activate an expansion;
- mutate the Catalogue.

Publication remains a later explicit phase.

## Optional Library artwork

Expansion manifests may carry a safe relative artwork path:

```php
'artwork' => 'assets/library-cover.jpg',
```

The image belongs inside the expansion pack itself. Installed bundled packs can use that artwork as a decorative hero behind the expansion identity on both **Your Library** and **Browse**. The same pack artwork is reused consistently in both places, gently zooms on hover/focus, falls back to the classic parchment treatment when absent, and respects reduced-motion preferences.

The proposal form records the intended relative artwork path. V.9 deliberately does not upload or publish image bytes; the eventual pack-writing/publication workflow will place the file in the expansion pack.

Remote URLs, absolute paths, and traversal paths are rejected by the proposal service.

## Small V.8 polish

The Review Desk source summary now wraps long Google Doc identifiers instead of allowing them to escape their card.
