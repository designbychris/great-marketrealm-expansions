# Keeper's Catalogue in wp-admin

Phase II.1 adds the first visible WordPress admin surface for GMREXP.

After activating the plugin, administrators should see a top-level **MarketRealm Expansions** menu in wp-admin. The screen is intentionally read-only and shows:

- GMREXP plugin version;
- Catalogue API version;
- Bridge API version;
- number of loaded expansion packs and catalogue entries;
- installed Almanac names/keys/versions;
- counts by content type;
- each loaded entry's name, type, expansion and fully qualified canonical ID.

This screen is a diagnostic window onto the existing Catalogue, not a content editor. Almanac files remain the canonical source of truth. Pack activation, imports, compatibility management and richer Library UI belong to later Living Library work.
