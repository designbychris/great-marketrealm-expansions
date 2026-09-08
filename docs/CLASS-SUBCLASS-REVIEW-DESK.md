# Phase V.9 — Class & Subclass Review Desk Refinement

The Review Desk presents friendly controls for the required canonical Class and Subclass structures without creating a second schema or inventing mechanics.

## Class

Required Class fields remain `name`, `hit_die`, `max_level`, `saving_throw_proficiencies`, `proficiencies`, `features`, and `progression`. The Review Desk renders saving throws as a comma-separated list, proficiencies as `group | value, value`, features as `canonical-key | Name | Description`, and progression as `level | feature-key, feature-key`.

A Class may use a progression row with no feature keys, for example `2 |`. This becomes `{"level": 2}`. The existing canonical validator remains authoritative and still requires every level from 1 through `max_level`.

## Subclass

Required Subclass fields remain `name`, `parent_class`, `entry_level`, `features`, and `progression`. Parent class and entry level use ordinary typed fields. Features and progression use the same friendly editors as Class. Subclass progression may be sparse because the canonical validator only prevents grants before `entry_level`; it does not invent levels between feature grants.

## Boundary

The Keeper supplies canonical keys and values. The Review Desk only converts friendly input into the already-defined canonical data shapes before calling the existing Review API and `ContentValidator`. Optional resources, spellcasting, choices, prerequisites, and other complex structures remain available in Advanced content data.
