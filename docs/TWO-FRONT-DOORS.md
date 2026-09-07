# The Librarian Realises There Are Two Front Doors

Phase V.2A fixes the Reading Room host-page routing mismatch discovered during the V.2 visual smoke test.

## The problem

V.1 introduced both:

```text
A normal WordPress page containing:
[great_marketrealm_expansions]
```

and direct virtual Reading Room routes:

```text
/marketrealm-expansions/
/marketrealm-expansions/browse/
```

The Reading Room navigation pointed at the virtual routes. Those routes rendered GMREXP directly, bypassing the WordPress Page containing the shortcode and therefore bypassing the surrounding page/theme flow.

The shortcode itself was working correctly; the navigation was simply leaving its host page.

## The V.2A rule

The WordPress page containing the shortcode is now the Reading Room's primary front door.

If the shortcode lives at:

```text
/expansions/
```

the desks resolve to:

```text
/expansions/
    → Your Library

/expansions/?gmrexp_section=browse
    → Browse

/expansions/?gmrexp_section=import
    → Import Desk

/expansions/?gmrexp_section=review
    → Review Desk
```

The page slug is not hard-coded. The shortcode discovers the current queried WordPress page and uses that permalink as its navigation base.

## Why query arguments first?

V.2A deliberately uses a section query argument instead of inventing child WordPress pages or taking ownership of `/expansions/*` rewrite rules.

That gives the theme and WordPress page system one clear owner of the outer page while GMREXP owns only the Reading Room section rendered inside the shortcode.

Pretty nested URLs can be introduced later if there is a real benefit, without changing the current content/state architecture.

## Shortcode section resolution

The existing explicit shortcode form remains valid:

```text
[great_marketrealm_expansions section="review"]
```

An explicit `section` attribute wins over the request query.

For the ordinary host page:

```text
[great_marketrealm_expansions]
```

the shortcode reads:

```text
gmrexp_section
```

and normalizes it through the existing Reading Room navigation contract. Unknown section values safely fall back to `library`.

## Remembering the host page

When the shortcode renders on a WordPress page, GMREXP remembers the page **ID**, not its current slug or raw URL.

This means a later WordPress permalink/slug change can still resolve through `get_permalink()`.

The option is:

```text
gmrexp_reading_room_host_page_id
```

It is routing configuration only; it is not Expansion, Catalogue, Library, entitlement or campaign state.

## Legacy virtual routes

The original `/marketrealm-expansions/...` routes remain registered for backwards compatibility.

Once a shortcode host page has been observed, a request to one of those legacy routes redirects to the corresponding desk on the remembered WordPress host page.

If no host page has ever been observed, the old standalone renderer remains as a safe fallback rather than producing a broken route.

## Route contract

The rewrite patterns themselves did not change, so:

```text
ReadingRoomPage::ROUTE_VERSION = 1.0.0
```

remains correct.

V.2A changes the preferred navigation destination, not the legacy rewrite shape.

## Boundary

V.2A changes only Reading Room routing/presentation.

It does not:

- change Catalogue content;
- change Library activation;
- change compatibility interpretation;
- stage imports;
- review content;
- migrate content;
- publish Almanacs;
- invent canonical mechanics.

There is now one front door.

Pippin has been asked to stop labelling the window “secondary entrance”.
