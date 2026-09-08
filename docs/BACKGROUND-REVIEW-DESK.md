# Background Review Desk refinement

Phase V.9 keeps the canonical Background schema authoritative while making common Keeper review work easier.

## Friendly controls

Required Background proficiencies use one group per line, for example:

```text
skills | Athletics, Survival
tools | Cook's Utensils
```

Required Background features use one feature per line:

```text
kitchen-reflexes | Kitchen Reflexes | Advantage against mundane kitchen hazards.
```

These controls serialize back into the existing canonical `proficiencies` map and `features` array. They do not create a second schema.

## Starting equipment can be explicitly empty

`starting_equipment` remains a required canonical field, but an expansion source is allowed to establish that a Background has no starting equipment. In that case the canonical value is:

```json
[]
```

The Review Desk accepts either `[]` or a blank Starting Equipment control and normalizes it to the empty canonical array. It does not invent equipment to satisfy validation. Non-empty equipment continues to use the existing canonical JSON-array representation until a future structural editor can be built without guessing an equipment shape.

The guiding rule remains: Pippin may preserve and assist; the Keeper decides canon.
