# Phase V.8 — Red Ink and Questionable Margins

V.8 opens the Administrator-only Review Desk and connects the existing Import API to the existing Review API.

## Workflow

```text
Google Doc / structured source
        ↓
Import Desk
        ↓
ImportService staging
        ↓
Send to Review Desk
        ↓
Administrator-private review queue
        ↓
ReviewService / ReviewSession
        ↓
Approve original / classify+amend / reject
        ↓
reviewed definitions only
```

## Keeper decisions

A staged record may be:

- approved unchanged when the staged definition is already valid;
- classified/amended by supplying an explicit canonical content type, canonical key, and content data;
- ignored/rejected when a source heading is structural, commentary, or otherwise not canonical content;
- reconsidered later.

Unresolved Google headings retain their neutral `data` map so the Review Desk can prefill the source name and prose without inventing type or key.

## Persistence

V.8 introduces an Administrator-private Review Desk queue backed by WordPress user meta. The queue stores the neutral staged JSON and explicit Keeper decisions. It does not store canonical Catalogue entries.

The queue belongs to the signed-in administrator rather than being global site state.

Because WordPress unslashes metadata values before storage, the queue is pre-slashed on write so source JSON and Keeper-authored data survive the user-meta round trip unchanged.

## Security

The V.6A `manage_options` boundary remains authoritative. Import and Review are hidden from non-administrators and direct requests are rejected before either workflow runs.

All Review Desk POST actions use a dedicated nonce in WordPress.

## Existing APIs remain authoritative

V.8 does not introduce a second review engine. Classification/amendment is validated by `ReviewSession::amend()`. Approval and rejection use the existing Review API.

Review API remains `1.0.0`.

## Publication boundary

**Reviewed ≠ Published.**

The Review Desk does not:

- write Almanac files;
- install an expansion;
- activate an expansion;
- mutate the Catalogue;
- publish approved definitions.

V.9 — The Shelving Trolley will turn reviewed definitions into a proposed Almanac.
