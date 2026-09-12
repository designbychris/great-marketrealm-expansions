# Phase V.12B.4 — The Front Door Remembers Who You Are

V.12B.4 closes the remaining Reading Room front-door and visual-delivery gap without changing Catalogue, Library, publication, activation, Companion-consumer, or Tabletop-consumer semantics.

## Companion login handoff

Logged-out Reading Room visitors now carry a concrete Reading Room return URL into the sign-in handoff. Expansions first accepts a sibling integration through the `gmrexp/companion_login_url` filter, then recognises Companion login helpers when available, then resolves the local Companion/Guild Gate page. If Companion genuinely cannot be resolved, the existing WordPress login URL remains the safe fallback.

The return URL preserves the current Reading Room section and the supported deeper Browse selectors (`gmrexp_expansion` and `gmrexp_type`). That means a visitor can enter from `/expansions/`, Discover, or an Almanac/content view, authenticate through Companion, and return to the requested Reading Room location.

## Deterministic asset versions

`reading-room.css` and `reading-room-review.js` are registered with versions based on their real filesystem modification times. The plugin release version remains the fallback if an asset path is unavailable. A changed asset therefore receives a changed URL automatically rather than depending on browser, host, or optimisation caches noticing a static plugin version.

## Why V.12B.3 did not appear

The intended V.12B.3 stylesheet rules were present in the repository, but the entire phase block had been appended using literal escaped `\\n` sequences. The browser therefore did not receive those declarations as normal CSS rules. V.12B.4 replaces that malformed text with real CSS while retaining the approved V.12B.3 design: four equal desktop destinations, a gentle Keeper tint, featured-art focal adjustment, category medallions/count layout, pluralised labels supplied by the existing renderer, and feature metadata on its own line.

The fix is deliberately scoped to the plugin-owned rules and avoids a blanket increase in CSS specificity, so site/Customizer cleanup remains free to make small presentation adjustments.
