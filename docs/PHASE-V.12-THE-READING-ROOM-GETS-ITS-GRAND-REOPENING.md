# Phase V.12 — The Reading Room Gets Its Grand Reopening

Phase V.12 gives `/expansions/` the visual language of the Great MarketRealm family without moving any ownership boundaries.

## Design language

The Reading Room now shares the Companion's parchment, ink, guild-purple, brass/gold and leather vocabulary. Cinzel-style display/heading stacks and Cormorant-style body stacks gracefully fall back to Georgia/system fonts when the site's chosen faces are unavailable. Surfaces deliberately favour ledger edges and book-like geometry over modern rounded cards.

The masthead is a Guild archive title page; navigation is a leather desk rail; summary tiles are catalogue slips; Almanac cards read as books with a leather spine and retain the reusable 3:2 cover standard.

## Information architecture

No workflow semantics changed. **Your Library** remains the active shelf. **Browse** remains every installed Almanac. **Import Desk** and **Review Desk** remain Keeper-only workflows. The Library note now explains those boundaries in user-facing language rather than describing them as future doors.

Campaign sharing remains a Companion concern. Site/library activation still means that an Almanac is available for consumers; it does not silently share that Almanac with every Campaign.

## Accessibility and resilience

- Existing semantic headings, navigation, forms and ARIA states are preserved.
- Keyboard focus treatment is strengthened across links, buttons and form controls.
- Reduced-motion removes decorative lift/cover zoom.
- Forced-colours receives a high-contrast structural fallback.
- Responsive layouts retain the existing two-column and single-column breakpoints.
- The 3:2 artwork contract and graceful artwork fallback remain unchanged.

## Architectural rule

This is a presentation phase. Catalogue, Library, activation, import, review, proposal, publication, storage and consumer APIs continue to own meaning and state.
