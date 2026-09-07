# Faces Behind the Counter

Phase III.2 gives expansion-provided NPCs a dedicated Keeper-facing structure for identity, social role, relationships, dialogue, lore, and optional combat references.

The schema is intentionally suitable for both lightweight story NPCs and richer campaign characters. `name` remains the only universally required field, so a simple named NPC can exist without needing a complete dossier.

## Identity

NPC identity metadata can describe aliases, titles, pronouns, species, and age. Aliases and titles are lists; the remaining values are descriptive strings.

This metadata is narrative rather than character-sheet state. Companion continues to own player-character identity and workflow.

## Roles, affiliations, and locations

NPCs may declare open canonical role keys, affiliation maps, and canonical location references.

Affiliations point at a target reference and may include a role and notes. The target may eventually identify a faction, guild, household, settlement, organisation, or another expansion-defined concept.

## Relationships

Relationships are directional declarations containing:

- a canonical `target`
- an open `type`
- optional label
- optional Keeper notes

Relationship types remain open vocabulary so sourcebooks can describe anything from ally and rival to considerably stranger MarketRealm social arrangements without requiring PHP changes.

## Dialogue

Dialogue entries have a stable key and text, with optional context:

```php
[
    'key' => 'greeting',
    'text' => 'Welcome to the counter.',
    'context' => 'first meeting',
]
```

Keys are unique within the NPC so future UI can reference individual lines or dialogue beats deterministically.

## Lore hooks

Lore hooks are keyed named entries that may include description, trigger text, and canonical references to other content.

This is deliberately broader than a quest system. A lore hook might point towards an adventure, location, NPC, encounter, item, rumour, or future sourcebook structure.

## Optional combat identity

An NPC that can enter combat may reference a canonical monster definition:

```php
'combat' => [
    'monster' => 'expansion:monster:keeper-guard',
    'variant' => 'shopkeeper',
]
```

This avoids duplicating a stat block inside the NPC record. GMREXP owns both canonical records; Tabletop may resolve the reference when the NPC becomes a live encounter participant.

Optional `combat.rules[]` may carry additional Rules Engine statements for the NPC-specific variant.

## Open vocabularies

Roles, species, relationship types, affiliation roles, and referenced content remain open canonical vocabularies. Phase III.2 defines structure without pretending the Keeper has already encountered every possible person who might stand behind a counter.
