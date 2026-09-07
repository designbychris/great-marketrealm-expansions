# The Reading Room

Phase V.1 opens the first Keeper-facing front-end surface for Great MarketRealm Expansions.

The Reading Room is deliberately an **adapter over existing GMREXP services**. It does not become a new source of expansion truth.

```text
Reading Room
    │
    ├── Catalogue → what is installed and canonical?
    ├── Library   → what is active and compatible?
    ├── Access    → may this visitor enter?
    └── Navigation
           ├── Your Library
           ├── Browse
           ├── Import Desk
           └── Review Desk
```

## Stable front-end routes

Phase V.1 reserves these routes:

```text
/marketrealm-expansions/
/marketrealm-expansions/browse/
/marketrealm-expansions/import/
/marketrealm-expansions/review/
```

`Your Library` opened in V.1. **Browse** opens in V.2.

Import Desk and Review Desk deliberately remain non-mutating placeholders until their respective phases. Reserving the routes now means later phases can replace those placeholders without breaking links or navigation contracts.

The rewrite rules are registered through WordPress and flushed once when the dedicated Reading Room route version changes. That route version is independent from the plugin release version, so ordinary releases do not cause unnecessary permalink flushes.

## Shortcode

A WordPress page may also embed the shell using:

```text
[great_marketrealm_expansions]
```

An optional reserved section can be selected:

```text
[great_marketrealm_expansions section="review"]
```

The shortcode and the virtual routes use the same renderer and services.

## Keeper access boundary

V.1 is a Keeper/editorial surface.

In WordPress:

```text
not signed in
    → sign-in gate

signed in without manage_options
    → Keeper access required

signed in with manage_options
    → Reading Room
```

This intentionally reuses the same capability currently protecting the Keeper's wp-admin Catalogue. Phase V.1 does not invent ownership, entitlement, account-level activation or campaign-specific permissions.

Those are separate future product decisions.

## Your Library

The V.1 Library view reads:

- installed expansion count from Catalogue;
- active expansion count from Living Library;
- canonical content count from Catalogue;
- compatibility state from Living Library;
- each installed pack's canonical key, name, version, description and content-entry count.

No Reading Room render mutates activation state.

The visual cards are therefore a presentation of established state, not a second persistence model.

## Graceful empty state

If no Almanacs are loaded, the route remains valid and presents an empty shelf rather than failing.

This is important for new installations, development environments and future remote/import workflows.

## Accessibility

The V.1 shell includes:

- semantic `main`, `nav`, `section`, `article`, `dl` structures;
- `aria-current` for active navigation;
- labelled navigation and summary regions;
- keyboard-visible focus states;
- responsive layout;
- reduced-motion treatment.

## Styling boundary

Phase V.1 introduces:

```text
assets/css/reading-room.css
```

This establishes the Reading Room's parchment/library visual shell without coupling visual presentation to Catalogue or Library logic.

Later Phase V work can extend the same class namespace rather than restyling the architecture from scratch.

## No workflow mutation yet

V.1 deliberately does **not**:

- activate or deactivate expansions from the front end;
- open individual catalogue definition/detail pages;
- import files or Google Docs;
- create review sessions;
- amend or approve staged content;
- publish Almanacs;
- run migrations;
- write to the Catalogue.

Those belong to later Phase V desks.

The Reading Room rule is:

> The interface displays state; the underlying APIs continue to own its meaning.

Pippin has been informed that “front-end shell” is not permission to remove the front wall.


## Phase V.2

The Browse desk now lists installed Almanacs, their Library/compatibility labels, and canonical content-family counts through `BrowseShelf`. See `BOOKS-UPON-THE-SHELVES.md`.
