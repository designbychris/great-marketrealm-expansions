# Phase V.9 Refinement — The Keeper Asks What the Form Requires

The Review Desk is now schema-aware.

When the Keeper chooses a canonical content type, the form reads the existing `SchemaRegistry` and exposes that type's **required canonical fields** before an amendment can be accepted.

Examples:

- `magic-item` → `category`, `rarity`
- `weapon` → `category`, `damage`
- `armour` → `category`, `armour_class`
- `subclass` → `parent_class`, `entry_level`, `features`, `progression`
- `subrace` → `parent_race`, `traits`
- `spell` → `level`, `school`, `casting_time`, `range`, `components`, `duration`

The form does not invent values. Pippin still does not choose rarity, parent class, armour class, damage, or any other canonical mechanic for the Keeper.

## Field controls

Simple required values use normal form controls:

- string → text input
- integer / number → numeric input
- boolean → Yes / No selector

Complex required values continue to use explicit JSON editors:

- map → JSON object
- array → JSON array

Optional schema data remains available in **Advanced content data**.

## Validation boundary

The Review Desk does not replace validation. Submitted friendly fields are typed and merged back into the content data map, then the existing `ReviewSession::amend()` path and canonical `ContentValidator` remain authoritative.

A malformed JSON map/array is refused before canonical review validation with a field-specific message.

## Failed amendments preserve the Keeper's work

If canonical validation refuses an amendment, the selected content type, key, name, description, note, and representable schema values remain on the form for correction instead of resetting the record to an apparently blank Pending state.

## Progressive enhancement

The server renders required fields for an already-selected type. A lightweight Reading Room script updates the required-field panel immediately when the Keeper changes the content-type selector.

Without JavaScript, the canonical validator still protects the boundary and a failed submission returns with the selected type and required fields visible.

**The schema decides what is required. The Keeper decides the values.**
