# The Adventure Shelf

Phase III.7 gives expansion packs a canonical structure for whole adventures and sourcebook-style adventure content.

The Adventure Shelf does not duplicate the Bestiary, NPC records, hazards, treasure, rules, or conditions. Instead, it provides the **book spine** that points to those canonical records and arranges them into chapters, sections, scenes, appendices, entry points, and progression paths.

`name` remains the only universally required field. That keeps lightweight adventure records, sourcebook stubs, and partially reviewed imports valid.

## Adventure structure

An adventure may describe:

- an open `kind`
- a synopsis
- optional level guidance
- one or more entry points
- Rules Engine prerequisites
- chapters, sections, and scenes
- appendices
- progression/branching guidance
- cross-content references
- Keeper notes

## Chapters, sections, and scenes

Chapters are stable keyed records. They may carry an explicit positive `order`, summary/description text, canonical references, nested rules, and sections.

Sections are likewise keyed records and can contain scenes.

Scenes are the smallest structural unit in this phase. A scene may carry an open scene kind, description, location reference, canonical content references, and optional neutral `rules[]`.

A scene can point to:

```text
encounters
npcs
monsters
hazards
treasure
conditions
rule_refs
references
```

Those are references only. The underlying mechanics remain owned by the relevant canonical content type.

## Example

```php
[
    'name' => 'Fixture Adventure Shelf',
    'kind' => 'adventure',

    'chapters' => [
        [
            'key' => 'chapter-one',
            'name' => 'Chapter One',
            'order' => 1,

            'sections' => [
                [
                    'key' => 'arrival',
                    'name' => 'Arrival',

                    'scenes' => [
                        [
                            'key' => 'first-room',
                            'name' => 'The First Room',
                            'kind' => 'exploration',

                            'encounters' => [
                                'expansion:encounter:fixture-ambush',
                            ],

                            'hazards' => [
                                'expansion:hazard:fixture-floor',
                            ],

                            'treasure' => [
                                'expansion:treasure:fixture-strongbox',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
]
```

## Entry points

`entry_points[]` provide stable named ways into the adventure. They can point to a chapter or scene and carry supporting references.

This supports adventures with more than one starting hook without forcing every sourcebook into a single linear opening.

## Progression and branches

`progression` may define an open mode plus optional start/end identifiers and keyed branches.

A branch records:

- `key`
- `from`
- `to`
- `condition`
- optional nested `rules[]`

This communicates canonical adventure flow without storing a party's live progress.

## Appendices

`appendices[]` are stable keyed reference containers for Keeper material that belongs to the adventure but does not fit directly into chapter/scene flow.

## Rules Engine integration

Adventure-level `prerequisites[]` use Rules API requirement statements.

Nested `rules[]` in chapters, sections, scenes, and progression branches are validated recursively by the shared Rules Engine.

GMREXP therefore understands the neutral mechanics attached to an adventure without introducing adventure-specific execution code.

## Architectural boundary

GMREXP does not store:

- which chapter the live party is currently in
- which scene has been completed
- which branch the party chose
- current encounter state
- current NPC disposition
- which hazard has fired
- which treasure parcel has been claimed
- which optional campaign rule is currently enabled
- live token, initiative, fog, HP, or session state

Those remain Companion/Tabletop/campaign concerns.

```text
Adventure
  ├─ Chapters
  │   └─ Sections
  │       └─ Scenes
  │           ├─ Encounters
  │           ├─ NPCs
  │           ├─ Monsters
  │           ├─ Hazards
  │           ├─ Treasure
  │           ├─ Conditions
  │           └─ Rule references
  ├─ Appendices
  ├─ Entry points
  └─ Progression

GMREXP
    = what the book says

Consumer state
    = where the party currently is
```

This completes Phase III's Keeper Content layer: the individual canonical records now have a structure capable of tying them together into an entire adventure/sourcebook.
