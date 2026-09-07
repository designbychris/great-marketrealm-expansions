# The Librarian Checks the Labels

Phase IV.2 adds dependency and compatibility diagnostics to the Living Library.

The purpose is diagnostic rather than destructive. GMREXP can now tell the Keeper whether an installed expansion is ready, degraded, or blocked without silently changing activation state.

## Compatibility states

```text
ready
    No known compatibility problems.

degraded
    The pack can still be used, but an optional dependency is unavailable
    or a declared consumer version cannot currently be verified.

blocked
    A required dependency is missing/inactive/incompatible, a declared
    consumer version is incompatible, or a conflicting pack is present.
```

These states describe the pack's current compatibility context. They do not mutate the Catalogue and they do not automatically activate or deactivate packs.

## Manifest dependency labels

Expansion manifests may declare `dependencies`.

Structured form:

```php
'dependencies' => [
    [
        'key' => 'base-pack',
        'version' => '>=1.0.0,<2.0.0',
        'required' => true,
        'active' => true,
    ],
    [
        'key' => 'optional-library',
        'required' => false,
    ],
],
```

Compact required-dependency form:

```php
'dependencies' => [
    'base-pack' => '>=1.0.0,<2.0.0',
],
```

`required` defaults to `true`.

`active` defaults to `true`, meaning a dependency must normally be both installed and active. A definition may set `active => false` when installation alone is sufficient.

## Version constraints

Phase IV.2 deliberately uses a small, predictable constraint language instead of introducing a package-manager dependency.

Supported forms include:

```text
*
1.2.0
=1.2.0
!=1.2.0
>1.2.0
>=1.2.0
<2.0.0
<=2.0.0
>=1.0.0,<2.0.0
```

Every comma-separated clause must match.

Unsupported constraint syntax is treated conservatively as incompatible rather than guessed.

## Optional dependencies

Missing, inactive, or version-incompatible optional dependencies produce a warning and a `degraded` report rather than blocking the pack.

This makes it possible for future expansions to advertise optional integrations without turning those integrations into hard requirements.

## Conflicts

Expansion manifests may declare `conflicts`.

Simple form:

```php
'conflicts' => [
    'old-pack',
],
```

Structured form:

```php
'conflicts' => [
    [
        'key' => 'old-pack',
        'version' => '<2.0.0',
        'active_only' => true,
    ],
],
```

By default, a conflict blocks only while the conflicting pack is active.

Set `active_only => false` only when merely having the conflicting pack installed is itself a problem.

Version-scoped conflicts apply only when the installed conflicting pack matches the declared constraint.

## Consumer compatibility

Existing manifest `compatibility` metadata can now optionally describe consumer-version requirements:

```php
'compatibility' => [
    'ruleset' => 'great-marketrealm',

    'consumers' => [
        'great-marketrealm-companion' => '>=1.0.0',
        'great-marketrealm-tabletop' => '>=1.0.0,<2.0.0',
    ],
],
```

The Living Library accepts an environment-version map when evaluating compatibility:

```php
$report = \GreatMarketrealmExpansions\library()->compatibility(
    'fixture-pack',
    [
        'great-marketrealm-companion' => '1.4.0',
        'great-marketrealm-tabletop' => '1.8.0',
    ]
);
```

If a declared consumer version is not supplied, the report is `degraded` with `consumer_version_unknown`.

If the supplied version fails the declared constraint, the report is `blocked`.

The Library does not guess sibling-plugin versions.

## API

Library API remains `1.0.0`; Phase IV.2 adds backward-compatible capabilities:

```text
library.compatibility.report
library.dependencies
library.conflicts
```

Public methods:

```php
$report = \GreatMarketrealmExpansions\library()->compatibility('pack-key');
$reports = \GreatMarketrealmExpansions\library()->compatibilityReports();
```

A report exposes:

```text
expansionKey()
installed()
active()
status()
issues()
ready()
degraded()
blocked()
toArray()
```

Issues expose severity, stable diagnostic code, message, and optional subject expansion/consumer key.

## Diagnostic codes

Current codes include:

```text
expansion_not_installed

required_dependency_missing
required_dependency_inactive
required_dependency_version_incompatible

optional_dependency_missing
optional_dependency_inactive
optional_dependency_version_incompatible

conflicting_expansion_present

consumer_version_unknown
consumer_version_incompatible
```

Callers should prefer the stable code over parsing human-readable messages.

## Keeper Catalogue

The wp-admin Living Library now shows compatibility alongside activation state.

Each installed pack displays:

```text
Ready
Degraded
Blocked
```

and any diagnostic issues are listed beneath the status.

The Library Status table also reports counts for ready, degraded, and blocked packs.

Activation remains a separate control. Phase IV.2 deliberately does not auto-disable a blocked pack, because diagnostic policy and Keeper intent are separate concerns.

## Architectural boundary

```text
Manifest
    ↓
Dependency / conflict / compatibility labels
    ↓
Compatibility Inspector
    ↓
Ready / Degraded / Blocked
    ↓
Keeper or consumer decides what to do

NOT:

Compatibility Inspector
    ↓
silently rewrites activation
```

This keeps compatibility evaluation deterministic, visible, and reversible.

Pippin's label-reading protocol remains under review after he filed:

> “Warning: this label appears to be compatible with the shelf it is attached to.”
