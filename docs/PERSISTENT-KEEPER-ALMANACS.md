# Phase V.10A.1 — The Librarian Stops Shelving Books in the Delivery Van

Keeper-published Almanacs are runtime data, not plugin code.

## Storage boundary

- Bundled/developer Almanacs remain in `content/expansions/` inside the plugin and travel with deployments.
- Keeper-published Almanacs live in the WordPress uploads tree at `great-marketrealm-expansions/almanacs/`.
- Startup loads bundled Almanacs first, then the persistent Keeper library, into the same canonical Catalogue.
- New publication, artwork, and catalogue-card corrections target the persistent Keeper library only.
- Bundled Almanacs are not editable from the Reading Room correction desk.

This prevents plugin replacement or upgrade from deleting books published by the Keeper.

**Rule:** Code can be redeployed. Published books must survive the delivery van leaving.
