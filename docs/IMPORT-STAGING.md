# The Books Arrive by Owl, Cart, or Google Doc

Phase IV.3 introduces the **Import API** and a safe staging pipeline for external source material.

The key architectural rule is simple:

```text
External source
    ↓
structured, non-executable representation
    ↓
Import API staging
    ↓
schema validation + provenance + review flags
    ↓
reviewable ImportResult

NOT

External source
    ↓
direct Catalogue mutation
```

Phase IV.3 does **not** publish imported content. That belongs to the next review/approval phase.

## Import API

Import API `1.0.0` exposes:

```text
import.stage
import.validate
import.provenance
import.review-flags
import.structured-document
import.json
```

The public helper is:

```php
\GreatMarketrealmExpansions\importer()
```

## Why staging exists

Canonical Great MarketRealm content will eventually come from several source forms:

- reviewed sourcebook exports
- Google Docs transformations
- manually prepared structured files
- migration tools
- future trusted import adapters

Those sources should never write directly into the live Catalogue.

The importer creates a reviewable intermediate result first.

## Structured document format

The staging format is intentionally plain data and non-executable:

```php
[
    'source' => [
        'type' => 'google-doc',
        'id' => 'document-id',
        'title' => 'Sourcebook Title',
        'version' => 'draft-7',
        'metadata' => [],
    ],

    'records' => [
        [
            'id' => 'heading-12',
            'type' => 'feat',
            'key' => 'fixture-knack',

            'source' => [
                'heading' => 'Fixture Knack',
                'section' => 'Chapter Two',
            ],

            'data' => [
                'name' => 'Fixture Knack',
            ],
        ],
    ],
]
```

This is an **intermediate representation**. A Google Docs adapter can eventually transform document headings, tables and paragraphs into this shape without teaching the core Import API anything about Google Docs itself.

## JSON staging

The same representation can be supplied as JSON:

```php
$result = \GreatMarketrealmExpansions\importer()->stageJson($json);
```

JSON is parsed as data. It is never executed.

Invalid JSON produces a structured `json_invalid` issue.

A JSON root that is not an object/map produces `document_invalid`.

This is the beginning of the safe external-import boundary promised by the Almanac format documentation: external files are data, never arbitrary PHP.

## Source identity

Every staged import has a `SourceDocument` with:

```text
type
id
title
version
metadata
```

`type`, `id`, and `title` are required for a clean source.

Examples of future source types might include `google-doc`, `json-export`, `manual-review`, or another explicit adapter name. The Import API does not hard-code a vocabulary.

## Provenance

Every successfully constructed staged definition receives protected import provenance:

```text
import_source_type
import_source_id
import_source_title
import_source_version
import_record_id
import_context
```

Existing sourcebook provenance such as chapter/page metadata is preserved.

Protected importer provenance takes precedence over source-supplied values, preventing a transformed record from spoofing its actual import source.

## No guessing

The importer does not invent canonical identities.

If a staged record has no explicit content `type`:

```text
content_type_unresolved
```

If it has no explicit canonical `key`:

```text
content_key_unresolved
```

The importer will not generate a key from a title and will not pick a content type because a heading “looks like” a subclass.

That decision belongs to a transformation rule or the Keeper.

## Ambiguity

A transformation adapter may supply `candidate_types` when it cannot confidently classify something.

For example:

```php
[
    'id' => 'heading-44',
    'candidate_types' => ['class', 'subclass'],
    'key' => 'school-of-preserving',
    'data' => [
        'name' => 'School of Preserving',
    ],
]
```

With no explicit `type`, the staging result records:

```text
source_ambiguity
content_type_unresolved
```

Nothing is silently guessed.

## Explicit review flags

Transformers may mark a record:

```php
'review_required' => true
```

A structurally valid record remains valid but receives:

```text
keeper_review_requested
```

and `requiresReview()` returns true.

This lets an importer say:

> “I could map this, but a Keeper should look at it.”

without pretending uncertainty is certainty.

## Validation

Every staged `ContentDefinition` passes through the same canonical schema pipeline used by Almanac content.

Schema failures become structured import issues:

```text
schema_validation_failed
```

including the exact content field reported by the canonical validator.

The Import API therefore does not create a second, looser definition of valid content.

## Duplicate identities

Duplicate staged identities inside one import are detected before review:

```text
duplicate_staged_identity
```

The second duplicate is invalid and reviewable.

## Result model

`ImportResult` exposes:

```text
source()
definitions()
issues()
validCount()
reviewCount()
hasErrors()
toArray()
```

Each `StagedDefinition` exposes:

```text
recordId()
definition()
issues()
sourceContext()
valid()
requiresReview()
toArray()
```

Each `ImportIssue` carries:

```text
severity
code
message
record_id
field
```

Stable codes should be used by future UI rather than parsing prose messages.

## Phase boundary

Phase IV.3 stages and validates.

It deliberately does **not**:

- install an expansion
- add records to the Catalogue
- activate a pack
- overwrite an Almanac
- write PHP content files
- approve ambiguity
- infer missing canon
- fetch Google Docs at gameplay runtime

The eventual source flow remains:

```text
Google Docs / sourcebook
        ↓
source-specific adapter
        ↓
structured import document
        ↓
Import API
        ↓
staged + validated result
        ↓
Phase IV.4 Keeper review
        ↓
reviewed Almanac
        ↓
normal Catalogue loading
```

This keeps runtime play independent of Google Docs and ensures canonical content remains reviewable and version-controlled.

Pippin has requested that owl-delivered books be checked for both provenance and owl droppings. Only the former is currently in scope.


## Phase IV.4 review hand-off

A staged `ImportResult` can now be opened by Review API `1.0.0`.

Review remains granular: each staged record may be approved, rejected, amended, or left pending. Amendments pass through canonical schema validation and retain protected source provenance.

Review does not publish to the Catalogue. See `docs/KEEPER-REVIEW.md`.
