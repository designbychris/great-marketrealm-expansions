# Phase V.5 — The Librarian Raises an Eyebrow

Phase V.5 turns the Reading Room compatibility badge into a doorway to the existing Living Library compatibility report.

## One compatibility authority

The Reading Room does not calculate dependency, conflict, or consumer-version semantics itself.

```text
Almanac
   ↓
Library::compatibility()
   ↓
CompatibilityReport
   ↓
CompatibilityDiagnostics presentation
```

`CompatibilityInspector` remains the authority for ready, degraded, and blocked results. V.5 only translates that report into Keeper-facing presentation.

## Badge behaviour

The Ready / Degraded / Blocked badge on Your Library, Browse, and the open Almanac links to the compatibility explanation for that Almanac.

The open Almanac keeps the explanation in the same Reading Room page beneath the contents index. No new rewrite route is introduced.

## Ready

A Ready report explicitly says that the Living Library returned no compatibility issues. It shows zero issue, warning, and blocking counts.

## Degraded

A Degraded report exposes the warning records already returned by the compatibility engine. Optional missing/inactive/version-mismatched dependencies and unknown consumer versions can therefore be understood without inventing frontend rules.

## Blocked

A Blocked report exposes blocking records such as missing/inactive/incompatible required dependencies, conflicts, or incompatible known consumer versions.

The report is diagnostic only. A blocked Almanac is not automatically deactivated.

## Issue fidelity

For every issue the Reading Room exposes:

- severity;
- stable issue code;
- existing compatibility-engine message;
- subject when one was supplied.

Human-readable code labels are presentation only; canonical issue codes are not rewritten.

## Mutation boundary

Viewing diagnostics never activates, deactivates, installs, removes, migrates, publishes, or rewrites content.

The established rule remains:

**Compatible ≠ Active.**

The badge explains why the Librarian is smiling, raising an eyebrow, or refusing to stamp the book. It does not move the book by itself.
