# The Keeper Turns the Key

Phase V.3 adds front-end Expansion activation controls to the Reading Room.

The control is deliberately a thin UI over the existing Living Library:

```text
Reading Room Browse card
        ↓
secure WordPress POST
        ↓
Library::setActive()
        ↓
ActivationStore
```

No second activation model is introduced.

## What deactivation means

Deactivating an Expansion does not uninstall it, remove Catalogue records, change canonical IDs, rewrite Almanac files, or alter source content.

It only changes the Living Library decision about whether consumers should currently treat that installed pack as active.

## Security

The Reading Room activation POST:

- requires the existing Keeper capability `manage_options`;
- uses a WordPress nonce;
- accepts only an installed canonical Expansion key;
- normalizes the submitted key before lookup;
- treats only literal `1` as activation;
- redirects back to the Browse desk after the change.

The action name is:

```text
gmrexp_reading_room_activation
```

## Front-end behavior

An active Almanac shows **Deactivate**.

An inactive Almanac shows **Activate** and explicitly explains that it remains installed and canonical.

The Browse route continues to show the current active/inactive label and compatibility state after the change.

## Boundaries

V.3 changes activation only.

It does not open Expansion detail pages, explain compatibility diagnostics in depth, stage imports, review content, migrate content, publish Almanacs, or alter canonical definitions.

Pippin has been informed that “Turn the Key” is not permission to copy the key.


## V.3A cache coherency

The Reading Room host page is dynamic because it displays activation state. V.3A prevents ordinary WordPress-aware page caching of that host and invalidates its post cache after successful activation changes. See `THE-SIGN-REFUSES-TO-CHANGE.md`.
