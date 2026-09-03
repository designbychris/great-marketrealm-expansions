# Almanac Pack Format

Phase I.3 introduces the first file-backed expansion-pack format.

## Directory structure

A bundled expansion lives beneath `content/expansions/<pack-key>/`:

```text
content/expansions/first-almanac/
├── manifest.php
└── content/
    ├── feats/
    │   └── iron-stomach.php
    └── monsters/
        └── milk-carton-mimic.php
```

The folders below `content/` are organisational only. The canonical content type is declared inside each definition, so files may later be grouped in whatever structure best suits a source book.

## Manifest

`manifest.php` returns an associative array. `key`, `name`, and `version` are required. `description` is optional. Any additional keys are preserved as expansion metadata.

```php
<?php
return [
    'key' => 'first-almanac',
    'name' => 'The First Almanac',
    'version' => '0.1.0',
    'description' => 'A Great MarketRealm expansion pack.',
    'compatibility' => ['ruleset' => 'great-marketrealm'],
];
```

## Content definitions

Each PHP content file returns one definition with a canonical `type`, `key`, and `data` map:

```php
<?php
return [
    'type' => 'feat',
    'key' => 'iron-stomach',
    'data' => [
        'name' => 'Iron Stomach',
        'description' => '...',
        'tags' => ['example'],
    ],
];
```

Definitions are normalized and validated through the Phase I.2 schema system before registration.

## Provenance

The loader automatically adds two protected provenance values:

- `expansion`: the canonical expansion-pack key.
- `file`: the path of the definition relative to the pack root.

Additional provenance such as source title, chapter, or page can be supplied by the definition and is retained.

Absolute server paths are not stored in content definitions.

## Atomic loading

A pack is preflighted completely before it is committed. The loader checks:

- manifest validity;
- content-file shape;
- canonical content-schema validity;
- duplicate identities inside the pack;
- collisions with existing content for the same expansion key.

If preflight fails, nothing from that pack is registered. If an unexpected error occurs during commit, the loader rolls back both the expansion and all of its content.

## Discovery order

Content files and expansion directories are sorted before loading. This makes discovery deterministic across filesystems and hosting environments.

## Trust boundary

The Phase I.3 format uses PHP arrays because bundled expansion packs are version-controlled plugin code. PHP pack files are therefore **trusted code**, not an upload/import format.

A future external import system must use a non-executable data format and must never `require` arbitrary user-supplied PHP files.
