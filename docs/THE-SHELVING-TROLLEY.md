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

## V.9 Review Desk refinement — Keeper-friendly race controls

The schema-aware Review Desk now presents the required playable-race structure without asking the Keeper to author routine JSON:

- creature type uses a normal text field with common creature-type suggestions, never an automatic choice;
- fixed size uses a normal field with standard size suggestions and is converted to the canonical `size.value` map;
- walking speed is entered in feet and converted to the canonical `speed.walk` map;
- languages are entered as a comma-separated list and converted to the canonical array;
- race/subrace traits use one Keeper-authored line per trait: `canonical-key | Trait Name | Description`.

Optional or unusual structures (multiple size options, additional movement modes, rule objects, choices, proficiencies, senses, resistances, and similar details) remain available through Advanced content data. The canonical validator remains the final authority.

This is deliberately a presentation adapter over the existing race schema. It does not change race mechanics, infer missing sourcebook decisions, or weaken validation.
