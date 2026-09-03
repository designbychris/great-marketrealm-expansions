<?php
namespace GreatMarketrealmExpansions\Catalogue;

defined('ABSPATH') || exit;

use RuntimeException;

final class AmbiguousCatalogueEntryException extends RuntimeException
{
    /** @param list<string> $expansionKeys */
    public static function forLookup(string $type, string $key, array $expansionKeys): self
    {
        return new self(sprintf(
            'Catalogue lookup "%s/%s" is ambiguous across expansions: %s.',
            $type,
            $key,
            implode(', ', $expansionKeys)
        ));
    }
}
