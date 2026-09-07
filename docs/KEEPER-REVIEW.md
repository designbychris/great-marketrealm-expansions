# The Keeper Reads Before Shelving

Phase IV.4 adds an explicit, granular review layer between staged imports and future Almanac publication.

The core rule is:

```text
ImportResult
    ↓
Review Session
    ├─ Approve
    ├─ Reject
    ├─ Amend
    └─ Leave Pending
    ↓
Reviewed Definitions

NOT

ImportResult
    ↓
automatic publication
```

Phase IV.4 still does **not** publish to the Catalogue. It records the Keeper's interpretation and produces approved definitions for the future shelving/publication phase.

## Review API

Review API `1.0.0` exposes:

```text
review.open
review.approve
review.reject
review.amend
review.pending
review.approved-definitions
review.no-publication
```

The public helper is:

```php
\GreatMarketrealmExpansions\reviewer()
```

A review session begins from an existing staged `ImportResult`:

```php
$import = \GreatMarketrealmExpansions\importer()->stage($document);
$review = \GreatMarketrealmExpansions\reviewer()->open($import);
```

## Deterministic review items

Each staged definition becomes one review item in source order.

Review IDs are deterministic within the session:

```text
review-1
review-2
review-3
...
```

The original import `record_id` remains preserved separately, so a review decision can always be traced back to the source record.

## Review states

Every review item has exactly one current state:

```text
pending
approved
rejected
amended
```

`pending` is the default.

An `approved` item retains the original staged definition.

A `rejected` item contributes no approved definition.

An `amended` item contains a Keeper-supplied replacement definition that has passed canonical schema validation.

Pending items remain unresolved.

## Granular decisions

Decisions are made per review item, not per whole import.

A single import can therefore contain:

```text
review-1  approved
review-2  amended
review-3  rejected
review-4  pending
```

This is deliberate. Sourcebook imports often contain a mixture of obvious records, ambiguous headings, flavour-only material, and records that need a small correction.

The Keeper should not be forced into an all-or-nothing decision.

## Approving

Only a structurally valid staged definition can be approved unchanged.

```php
$review->approve('review-1', 'Checked against the source.');
```

A staged record that failed canonical validation or has an unresolved type/key cannot be approved merely by clicking through it.

It must either be rejected or amended.

A staged definition that is structurally valid but explicitly flagged for Keeper review may be approved after that review. The review action is the explicit acknowledgement that the Keeper has checked it.

## Rejecting

Any item can be rejected:

```php
$review->reject(
    'review-2',
    'This heading is flavour text rather than mechanical content.'
);
```

Rejection does not delete the staged import record. The source context and decision remain visible in the review session.

## Amending

An invalid or ambiguous staged record may be resolved with an explicit amendment:

```php
$review->amend(
    'review-2',
    'feat',
    'school-of-preserving',
    [
        'name' => 'School of Preserving',
    ],
    'Keeper resolved this heading as a feat.'
);
```

The amendment is not trusted merely because a Keeper supplied it.

It is run through the same canonical `ContentValidator` used by normal Almanac and import staging content.

If validation fails, the amendment is refused and the item remains unresolved.

This preserves the single canonical definition of valid GMREXP content.

## Amendment provenance

Amended definitions retain protected import provenance:

```text
import_source_type
import_source_id
import_source_title
import_source_version
import_record_id
import_context
```

and add review provenance:

```text
review_id
review_status = amended
```

Source or amendment data cannot spoof these protected values.

This allows a future published Almanac record to retain a chain such as:

```text
Google Doc
    ↓
source record
    ↓
import transformation
    ↓
Keeper amendment
    ↓
reviewed definition
```

## Resetting decisions

A decision may be reset to pending:

```php
$review->reset('review-1');
```

Resetting an amended item restores the original staged definition as the pending review target.

This makes the review layer reversible before publication.

## Review notes

Approve, reject and amend actions may carry a short review note.

Notes are review metadata only. They do not become canonical mechanics.

## Session progress

A `ReviewSession` exposes counts for:

```text
pending
approved
amended
rejected
resolved
```

and:

```php
$review->complete();
```

returns true only when no item remains pending.

The session can also return:

```php
$review->approvedDefinitions();
```

which includes definitions in the `approved` and `amended` states only.

Rejected and pending definitions are excluded.

## No publication yet

Phase IV.4 deliberately provides no:

```text
review.publish
review.install
review.write-almanac
review.activate
```

capability.

A completed review session is still not an installed expansion.

That separation matters:

```text
Keeper reviewed this interpretation
```

is not the same statement as:

```text
This content has been published into a versioned Almanac.
```

The publication/migration layer can therefore enforce its own atomicity, pack identity, collision, versioning and filesystem rules later.

## Architecture

The source pipeline is now:

```text
Google Docs / sourcebook / JSON
        ↓
source-specific adapter
        ↓
Import API
        ↓
Staged ImportResult
        ↓
Review API
        ├─ approve
        ├─ reject
        ├─ amend
        └─ pending
        ↓
Reviewed Definitions
        ↓
future Almanac publication
        ↓
normal Catalogue loader
```

Pippin has apparently interpreted “read before shelving” as permission to read the first three pages of every book and then judge it by the map quality. The Keeper may wish to retain final editorial authority.
