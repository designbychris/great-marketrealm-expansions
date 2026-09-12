# Great MarketRealm Expansions language packs

English is the canonical source language for the Expansions interface.

The WordPress text domain is `great-marketrealm-expansions`. Locale packs follow standard WordPress names, for example `great-marketrealm-expansions-nl_NL.po` / `.mo`.

## Interface vs Almanac content

Buttons, labels, navigation, status/error messages and accessibility copy belong to interface gettext catalogues. Expansion names, rules, races, classes, spells, Bestiary entries, adventures and other Almanac material are canonical authored content and will use the curated MarketRealm content-translation pipeline.

Because Expansions is still under active construction, establishing this foundation now prevents new UI from accumulating localisation debt. A full interface-string audit should be repeated once the Reading Room and admin surfaces settle.

## POT catalogue

```bash
wp i18n make-pot . languages/great-marketrealm-expansions.pot --domain=great-marketrealm-expansions --exclude=vendor,node_modules
```
