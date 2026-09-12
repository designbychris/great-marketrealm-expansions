# Internationalisation

## Interface translation

The plugin uses WordPress gettext with the `great-marketrealm-expansions` text domain and `/languages` domain path. The architecture is locale-agnostic: no code should test specifically for `nl_NL`, `de_DE`, or any other locale.

## Canonical content translation

Almanac definitions are authored MarketRealm content. Their names, rules text and lore are not generic application chrome and should be translated through a curated content pipeline so mechanics, terminology and puns can be reviewed.

The Reading Room and wp-admin interface should progressively move static UI strings into gettext as those surfaces stabilise.
