# Moving Shelves Without Losing the Books

Phase IV.5 introduces the **Migration API** for explicit, auditable, forward-only content/schema transitions.

The architectural rule is:

```text
existing canonical definition
        ↓
explicit migration plan
        ↓
trusted registered migration steps
        ↓
final canonical schema validation
        ↓
migrated definition

NOT

old content
        ↓
silent mutation in the live Catalogue
```

Migration API `1.0.0` is transformation tooling. It does not rewrite loaded Catalogue entries, modify activation state, publish Almanac files, or invent missing migration routes.

## Migration API

The public helper is:

```php
\GreatMarketrealmExpansions\migrations()
```

Capabilities:

```text
migration.register
migration.plan
migration.apply
migration.batch
migration.validate
migration.identity-preservation
migration.provenance
migration.atomic-output
migration.no-catalogue-mutation
```

There is deliberately no `migration.publish` capability.

## Trusted migration steps

A migration step is registered by trusted plugin/application code:

```php
$migrations->register(
    new MigrationStep(
        'feat-1-to-2',
        'feat',
        '1.0.0',
        '2.0.0',
        static function (array $data): array {
            $data['description'] = $data['legacy_note'] ?? '';
            unset($data['legacy_note']);

            return $data;
        }
    )
);
```

The callback receives only the content data map. It cannot change the canonical content type or key because identity is held outside the callback by the Migration API.

Migration callbacks are trusted code shipped with the plugin or another trusted extension. They are **not** an executable import format and must never be loaded from arbitrary user uploads.

## Forward-only transitions

Every registered step must move strictly forward:

```text
1.0.0 → 2.0.0   allowed
2.0.0 → 2.0.0   refused
2.0.0 → 1.0.0   refused
```

Downgrade planning is deliberately unsupported in Phase IV.5.

Rollback belongs to storage/publication infrastructure that can restore the previous versioned artefact, not to a migration callback attempting to reverse unknown transformations.

## Planning

The Migration API resolves an explicit chain for one content type:

```text
feat 1.0.0
    ↓ feat-1-to-2
feat 2.0.0
    ↓ feat-2-to-3
feat 3.0.0
```

```php
$plan = $migrations->plan('feat', '1.0.0', '3.0.0');
```

The plan records the ordered step IDs.

If no complete route exists, planning fails.

If more than one complete route exists, planning also fails rather than guessing which route the Keeper intended.

```text
migration path missing     → refuse
migration path ambiguous   → refuse
```

This follows the same rule established by import staging: uncertainty is surfaced, not silently resolved.

## Semantic version comparison

Version transitions use PHP semantic-style `version_compare()` comparisons.

Equivalent forms such as:

```text
1.0
1.0.0
```

are treated as the same version for planning purposes.

The Migration API deliberately does not introduce Composer-style constraint syntax here. A migration step names one explicit source version and one explicit target version.

## Applying migrations

```php
$result = $migrations->migrate(
    $definition,
    '1.0.0',
    '3.0.0'
);
```

A successful result contains:

```text
source definition
from version
target version
migrated definition
ordered applied step IDs
```

A failed result contains structured issues and no migrated definition.

Stable Phase IV.5 issue codes include:

```text
migration_direction_invalid
migration_path_missing
migration_path_ambiguous
migration_step_failed
migration_step_invalid_output
migration_validation_failed
```

## Canonical identity cannot drift

The migration callback transforms only `ContentDefinition::data()`.

The Migration API reconstructs the result using the original:

```text
type
key
```

so migration cannot accidentally turn:

```text
feat:fixture-feat
```

into:

```text
spell:some-new-key
```

Changing canonical identity is a different operation and should be handled explicitly by future content-mapping/publication tooling rather than smuggled into a schema migration.

## Source definitions are immutable

Migration works on a copied data array and produces a new `ContentDefinition`.

The original definition remains unchanged even if:

- several steps succeed,
- a later step fails,
- final validation fails,
- batch migration fails.

This makes dry-run and diagnostic use safe.

## Final canonical validation

After all migration steps run, the resulting definition passes through the existing `ContentValidator`.

A transformation that produces structurally invalid current content is refused:

```text
migration_validation_failed
```

The Migration API therefore does not create a second definition of “valid content.”

## Migration provenance

Successful migrations preserve existing provenance and append protected migration history:

```php
'provenance' => [
    // existing provenance remains here...

    'migration_history' => [
        [
            'step_id' => 'feat-1-to-2',
            'from_version' => '1.0.0',
            'to_version' => '2.0.0',
        ],
        [
            'step_id' => 'feat-2-to-3',
            'from_version' => '2.0.0',
            'to_version' => '3.0.0',
        ],
    ],
],
```

Existing migration history is retained and new entries are appended in application order.

Migration callbacks cannot spoof the protected history or overwrite protected pre-existing provenance such as import source identity.

## Same-version checks

Migrating from a version to its semantic equivalent is a no-op:

```text
1.0 → 1.0.0
```

No migration history entry is added.

The existing definition is still canonical-schema validated, so a no-op request cannot be used to bless invalid content.

## Batch migrations and atomic output

A list of definitions can be migrated together:

```php
$batch = $migrations->migrateBatch(
    $definitions,
    '1.0.0',
    '2.0.0'
);
```

Every item receives an individual diagnostic result.

However:

```php
$batch->definitions();
```

returns migrated definitions **only if every item succeeded**.

If one item fails:

```text
definition A  success
definition B  failure
definition C  success

BatchMigrationResult::definitions()
        ↓
[]
```

This is an atomic **output boundary**, not a database/filesystem transaction. Phase IV.5 still performs no persistence. The future publication layer can use this boundary to avoid writing a half-migrated pack.

## No default canonical migrations

Phase IV.5 ships the migration architecture, not invented Great MarketRealm migration rules.

No production migration step is registered merely to demonstrate the API. PHPUnit uses deliberately synthetic proving transformations.

Real migration steps should be introduced only when a real Almanac/schema version transition requires them.

## Relationship to import and review

The Living Library pipeline now has distinct responsibilities:

```text
External source
    ↓
Import API
    ↓
Review API
    ↓
Reviewed definitions
    ↓
future versioned Almanac
    ↓
Migration API when schema/content versions evolve
    ↓
validated migrated definitions
    ↓
future atomic publication/storage layer
```

Import decides how external source material maps into GMREXP structures.

Review records the Keeper's interpretation.

Migration moves known GMREXP structures between explicit versions.

None of those operations means “publish this into the live Catalogue.”

Pippin's migration procedure is reportedly: measure shelf, move shelf, measure shelf again, then complain that the room has changed shape. The first three steps are supported.
