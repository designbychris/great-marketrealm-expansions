# Books Upon the Shelves

Phase V.2 opens the **Browse** desk in the Keeper's Reading Room.

The purpose of this phase is intentionally narrow:

> Let the Keeper browse the Expansion packs that are already installed without creating another content store, activation workflow, or detail-page system.

The Browse shelf is therefore a read-only projection over the existing Catalogue and Living Library APIs.

```text
Catalogue
    ├── canonical Expansion identity
    ├── version / description
    └── content entries
             │
             v
       BrowseShelf
             ^
             │
Living Library
    ├── active / inactive
    └── compatibility status
```

## Browse route

The route reserved in V.1 is now open:

```text
/marketrealm-expansions/browse/
```

The Reading Room route contract itself has not changed, so `ReadingRoomPage::ROUTE_VERSION` remains `1.0.0`. No permalink flush is required simply because Browse changed from reserved to available.

## BrowseShelf

`BrowseShelf` is a small read model for the front-end. It receives the existing `Catalogue` and `Library` services and produces deterministic `BrowseShelfEntry` values.

Each entry exposes:

- canonical expansion key;
- canonical name;
- version;
- description;
- active/inactive Library state;
- compatibility status;
- total canonical entry count;
- deterministic content-type counts.

It does not retain or mutate the underlying `ExpansionPack`, `ContentDefinition`, activation store, or compatibility reports.

## Deterministic ordering

Installed Expansion packs are presented alphabetically by name with canonical key as the stable tie-breaker.

Content-family counts are sorted by canonical type key before rendering.

That keeps Browse output predictable regardless of file discovery or registry insertion order.

## Contents at a glance

V.2 deliberately shows **content-family counts**, not full definition details.

For example, an Almanac might display:

```text
Contents

Feat       1
Monster    1
```

Those labels are presentation-only transformations of the canonical content type keys. The type key itself remains unchanged in Catalogue.

Full Expansion/content detail belongs to **Phase V.4 — What Exactly Is in This Book?**

## Read-only state labels

Browse displays whether an Expansion is:

```text
Active / Inactive
Ready / Degraded / Blocked
```

but it does not provide controls for either concept.

- Activation controls belong to **V.3 — The Keeper Turns the Key**.
- Human-readable dependency and compatibility diagnostics belong to **V.5 — The Librarian Raises an Eyebrow**.

This keeps V.2 from quietly absorbing later workflow responsibilities.

## Empty shelves

An installation with no loaded Almanacs renders a deliberate empty Browse state rather than failing:

```text
Not a book in sight.
```

The route remains stable and usable while content is absent.

## No canonical content invented

V.2 adds no Great MarketRealm expansion mechanics or production Almanacs. PHPUnit coverage uses synthetic books and content definitions only.

## V.2 boundary

Browse can answer:

```text
What Expansion packs are installed?
What version is each one?
How many canonical definitions are in each?
What content families occur in each?
Is the pack active?
What is its compatibility status?
```

Browse cannot answer or perform:

```text
Change activation
Explain compatibility issues in full
Open individual canonical records
Stage imports
Review imports
Migrate content
Publish Almanacs
```

Those remain later Reading Room phases.

Pippin has discovered the shelf catalogue.

This is preferable to Pippin discovering the shelf brackets.


## V.2A routing note

The Browse UI itself is unchanged by V.2A. When embedded on a WordPress page, Browse now opens inside that same shortcode host using `?gmrexp_section=browse` rather than navigating to the standalone legacy route. See `TWO-FRONT-DOORS.md`.


## V.4 detail opening

The V.2 future boundary is now fulfilled: Browse cards and content-family chips open the installed Almanac detail view inside the same shortcode host.
