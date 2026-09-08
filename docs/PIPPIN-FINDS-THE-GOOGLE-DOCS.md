# Phase V.7 — Pippin Finds the Google Docs

Phase V.7 adds the first Google Docs source adapter to the Keeper's Import Desk.

The architectural rule remains:

```text
Google Doc
    ↓
GoogleDocsSourceAdapter
    ↓
neutral structured document
    ↓
ImportService::stage()
    ↓
validation + provenance + review diagnostics

NOT

Google Doc → Catalogue
```

## Keeper workflow

The administrator-only Import Desk now accepts a Google Docs document URL. The adapter validates that the URL is an HTTPS `docs.google.com/document/d/...` document and derives Google's HTML export endpoint from the document ID.

When the export is accessible, headings and the text beneath them are transformed into neutral records. The generated JSON is copied into the existing structured staging ledger and immediately passed through Import API `1.0.0`.

The Keeper can therefore inspect and correct the neutral representation using the same V.6 workflow rather than learning a second importer.

## No guessing

A Google Docs heading is **not** automatically a race, class, spell, monster, feat, item, or any other canonical content type.

The adapter deliberately does not invent:

- `type`
- `key`
- canonical mechanics

Each extracted heading is marked `review_required`, and the existing Import API reports unresolved type/key diagnostics. Later transformation/review work may resolve those identities explicitly from approved source conventions.

## Provenance

The source envelope records:

- source type `google-doc`
- Google document ID
- source URL
- adapter name
- adapter version
- document title when available

Each heading record carries heading text, heading level, and ordinal source context. Import API provenance then protects that source identity during staging.

## Security boundary

The V.6A Administrator boundary remains authoritative. Non-administrators cannot open or POST to the Import Desk.

The adapter only accepts HTTPS URLs on the exact `docs.google.com` host with a Google Docs document path. It derives the export URL itself, so the Keeper cannot use the form as a generic server-side URL fetcher.

Exports are limited to 5 MB and use bounded WordPress HTTP timeouts/redirects.

## Private documents

The built-in WordPress fetcher does not contain Google credentials and does not attempt to borrow a browser or ChatGPT login session. It can therefore acquire only a document whose HTML export is accessible to the WordPress site.

A private Google Doc will fail safely with `google_doc_fetch_failed`. A future authenticated source connector can be placed behind the same adapter boundary without changing Import API or canonical runtime content.

## Runtime independence

Google Docs remains an authoring/source surface. Companion, Tabletop, and gameplay runtime never fetch Google Docs. Reviewed Almanac files remain the deterministic canonical artefacts consumed at runtime.

## Stable issue codes

The acquisition layer introduces:

```text
google_doc_url_invalid
google_doc_fetch_failed
google_doc_empty
google_doc_too_large
google_docs_adapter_unavailable
```

After successful acquisition, all staging diagnostics continue to come from the existing Import API.

## Still not publication

Phase V.7 does not persist a review queue, approve content, write Almanac files, install expansions, activate expansions, or mutate the Catalogue.

**Imported ≠ Canonical.**
