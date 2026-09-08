# Administrator-only Reading Room desks

Phase V.6A tightens the Reading Room access boundary around source intake and review.

## Policy

The Reading Room keeps its read-only Library and Browse desks available to signed-in users.

The following desks require the WordPress `manage_options` capability:

- Import Desk
- Review Desk

In a standard WordPress installation, `manage_options` is an Administrator-level capability. Capability checks are preferred to hard-coded role-name checks so the security boundary follows WordPress permission semantics and remains compatible with deliberately delegated administrator-equivalent accounts.

## Import security

The Import Desk is rejected before its staging renderer or POST workflow is reached when the current account lacks `manage_options`.

That means a non-administrator cannot bypass the hidden navigation item by manually entering:

```text
?gmrexp_section=import
```

or by POSTing the Import Desk fields directly to that page.

## Review security

The same section-level gate already protects the reserved Review Desk and will continue to protect its future workflow when that desk opens.

## Navigation

Import Desk and Review Desk are omitted from Reading Room navigation for non-administrator accounts.

This is a usability measure only. The section-level authorization check remains the actual security boundary.

## Activation controls

Browse remains readable to signed-in users, but Activate/Deactivate controls are hidden unless the current account has `manage_options`.

The existing activation POST handler already enforces that capability server-side.

## Boundary

This phase does not change Catalogue visibility, canonical content, activation semantics, Import API semantics, Review API semantics, or route identity.
