# Phase V.10B — The Keeper Discovers the Clipboard Has Checkboxes

Phase V.10B turns the Reading Room into a clearer two-shelf experience and removes repetitive Review Desk approval work without weakening canonical review boundaries.

## Your Library and Browse

**Your Library is the active shelf.** Only Almanacs currently active for Living Library consumers are displayed there.

**Browse is the available shelf.** Every installed canonical Almanac remains visible there whether active or inactive. Activation controls remain in Browse, so an Almanac can enter or leave Your Library without changing its installed or canonical state.

This preserves the existing state boundaries:

- Installed ≠ Active.
- Inactive ≠ Unavailable.
- Your Library ≠ the complete Catalogue.

## Bulk Review

Pending Review Desk cards expose checkboxes associated with one bulk-review form. The Keeper can select all pending records, clear the selection, accept selected records, or reject selected records.

Bulk acceptance is deliberately conservative. It only accepts a selected staged definition when the existing Review API says the original staged definition is valid. A selected record that cannot be accepted safely remains pending and is reported as requiring attention. One problematic record therefore does not prevent other safe selections from being accepted.

Bulk rejection records an ordinary Review Desk rejection for each selected pending record. It does not delete source material or canonical content.

Every successful bulk decision invalidates a stale Shelving Trolley proposal exactly as an individual review decision does.

> **Bulk Review ≠ Publication ≠ Activation.**

## Almanac cover standard

Library and Browse cards now share a **3:2 landscape** artwork frame. Legacy images continue to render through `object-fit: cover`, while future covers should preferably be authored at 1800 × 1200 pixels or another 3:2 equivalent.

One pack-relative artwork path remains the canonical image source so future Companion and Tabletop consumers can reuse the same cover rather than inventing consumer-specific artwork.
