# What Exactly Is in This Book?

Phase V.4 opens installed Almanacs from the Reading Room without creating a second content store.

```text
BrowseShelf
    ↓ Open Almanac
ExpansionDetail
    ↓
Catalogue::contentByExpansion()
    ↓
canonical content families and entries
```

## Expansion detail

The detail view reads canonical Expansion identity, version and description from Catalogue, current activation and compatibility labels from Living Library, and entry content directly from Catalogue.

No detail data is copied into WordPress posts or frontend-specific persistence.

## Content families

Entries are grouped by their canonical content type. Family keys remain unchanged internally; labels such as `magic-item` → `Magic Item` are presentation only.

Families are deterministically ordered by canonical type. Entries within each family are ordered by display name and canonical key.

The Keeper can view all families or select one family using the `gmrexp_type` read-only filter.

## Entry summaries

V.4 displays each entry's canonical ID, name, type, description when present, and tags when present. These values are read from `CatalogueEntry`; the Reading Room does not reinterpret mechanics.

Full schema-aware field rendering/editing is deliberately not invented here. V.4 answers which canonical records are in an Almanac and provides a useful inspection surface without becoming a content editor.

## Host-page routing

The existing shortcode host remains the only front door:

```text
/expansions/?gmrexp_section=browse&gmrexp_expansion=first-almanac
/expansions/?gmrexp_section=browse&gmrexp_expansion=first-almanac&gmrexp_type=monster
```

The actual host permalink is discovered dynamically; `/expansions/` is only an example.

## Boundaries

V.4 does not mutate activation, compatibility, Catalogue content, Import, Review, Migration or publication state. The detail view contains no activation form. V.5 remains responsible for expanded human-readable compatibility diagnostics.

Pippin has now opened the book. The spine remains structurally load-bearing only in his notes.
