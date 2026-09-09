# Phase V.10A — The Keeper Corrects the Catalogue

V.10A adds a deliberately narrow correction workflow for **published Almanac presentation metadata**. It does not reopen, amend, or republish canonical content definitions.

The Administrator can correct an installed Almanac's display name, version, short Library summary, and safe pack-relative Library artwork path from **Your Library**. Artwork may be replaced with a bounded JPG/PNG/WEBP/GIF upload. If only the safe relative path changes, the existing pack artwork is moved when possible.

The canonical expansion key is immutable. Content files under `content/<type>/` are untouched. The manifest is committed through a temporary sibling file and atomic rename, preserving unrelated manifest metadata such as compatibility declarations.

Library and Browse cards now treat artwork as a clean 16:9 hero. Name, version, entry count and short summary render beneath the image rather than over it, so artwork remains readable and longer prose no longer obscures the cover.

**Correction ≠ Content Revision.** Mechanical changes still require a future controlled revision/update lifecycle.
