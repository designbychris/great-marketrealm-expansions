# Phase V.10 — The Keeper Rings the Bell

Phase V.10 is the explicit canonical publication boundary for a complete V.9 proposed Almanac.

## Boundary

`Reviewed → Proposed → Published → Active` are distinct states.

The publication action is Administrator-only because it lives on the existing protected Review Desk. It requires a persisted proposal with no pending review records and at least one approved definition.

Publication writes into `content/expansions/<canonical-key>/` through a sibling staging directory. The service writes the manifest and one PHP array file per approved canonical definition, validates every definition through the existing `ContentValidator`, then atomically renames the complete staging directory into place. The existing `ExpansionFileLoader` is the final authority: if loading fails, the published directory is removed and the loader's own registry rollback keeps the Catalogue unchanged.

Existing canonical keys are never overwritten by this phase. Updating an already-published Almanac is a separate future lifecycle concern.

## Artwork

If the V.9 proposal records a pack-relative `artwork` path, the Keeper must attach the corresponding image while ringing the bell. Publication accepts JPG/JPEG, PNG, WEBP, or GIF images up to 10 MB, verifies that the uploaded bytes are a recognised image, and copies the file to the exact safe relative path inside the staged pack. A proposal without artwork does not require an upload.

## Activation

Published does not mean active. After the existing Almanac Loader installs the pack into the current Catalogue, the Reading Room records the newly published pack as inactive. The Keeper can then use the existing Library activation control deliberately.

## Failure rule

If any validation, write, artwork, atomic rename, or canonical-loader step fails, the publication does not leave a half-published Almanac behind.
