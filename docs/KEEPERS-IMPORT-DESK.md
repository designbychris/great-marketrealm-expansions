# Phase V.6 — The Keeper's Import Desk

Phase V.6 opens the Reading Room's Import Desk as a Keeper-facing presentation layer over the existing Import API `1.0.0`.

## Purpose

The desk accepts a **neutral structured JSON document** and passes it directly to `ImportService::stageJson()`. It then renders the source identity, staging counts, record identities, source context, validation warnings/errors, and Keeper-review flags.

The frontend does not reinterpret import semantics. The Import API remains authoritative.

## Boundary

**Imported ≠ Canonical.**

A V.6 submission does not:

- mutate the Catalogue;
- create or install an Expansion pack;
- activate or deactivate anything in the Living Library;
- write an Almanac file;
- publish reviewed content;
- persist a review queue;
- execute PHP or another executable payload;
- fetch a Google Doc or other remote source.

The staged result is request-local. V.7 now owns the Google Docs source adapter; V.8 owns Keeper review, and later phases own proposed-Almanac and publication workflows. See `PIPPIN-FINDS-THE-GOOGLE-DOCS.md`.

## Input contract

The desk deliberately consumes the same neutral document shape already supported by the Import API:

```json
{
  "source": {
    "type": "structured-source",
    "id": "synthetic-sourcebook",
    "title": "Synthetic Sourcebook",
    "version": "draft-1"
  },
  "records": [
    {
      "id": "record-1",
      "type": "feat",
      "key": "synthetic-feat",
      "source": {
        "section": "Example Section"
      },
      "data": {
        "name": "Synthetic Feat",
        "description": "Non-canonical example content."
      }
    }
  ]
}
```

Content type and canonical key are explicit. The frontend does not guess either value.

## Security

The Reading Room retains its existing Keeper access gate. V.6 submissions also include a dedicated WordPress nonce when WordPress nonce APIs are present. Submitted JSON is treated as data and escaped when redisplayed.

## Routing

The existing Import Desk route opens in place; no rewrite contract changes were required. On a shortcode host page it remains:

```text
/expansions/?gmrexp_section=import
```

Legacy `/marketrealm-expansions/import/` compatibility routing remains available.
