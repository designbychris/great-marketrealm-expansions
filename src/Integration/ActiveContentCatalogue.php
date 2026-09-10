<?php
namespace GreatMarketrealmExpansions\Integration;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Catalogue\CatalogueEntry;
use GreatMarketrealmExpansions\Library\Library;

/**
 * Consumer-safe view of canonical content from active Almanacs only.
 *
 * Companion and Tabletop can consume the same fully-qualified catalogue
 * identities without learning how activation is stored or duplicating
 * expansion definitions into their own persistence layers.
 */
final class ActiveContentCatalogue
{
    public const API_VERSION = '1.0.0';

    /** @var list<string> */
    private const CAPABILITIES = [
        'consumer-content.active',
        'consumer-content.expansion',
        'consumer-content.lookup',
        'consumer-content.provenance',
        'consumer-content.type',
    ];

    public function __construct(private Library $library) {}

    public function apiVersion(): string { return self::API_VERSION; }

    /** @return list<string> */
    public function capabilities(): array { return self::CAPABILITIES; }

    public function supports(string $capability): bool
    {
        return in_array(strtolower(trim($capability)), self::CAPABILITIES, true);
    }

    /** @return list<CatalogueEntry> */
    public function all(): array
    {
        return $this->library->activeContent();
    }

    /** @return list<CatalogueEntry> */
    public function ofType(string $type): array
    {
        $type = strtolower(trim($type));
        return array_values(array_filter(
            $this->all(),
            static fn (CatalogueEntry $entry): bool => $entry->type() === $type
        ));
    }

    /** @return list<CatalogueEntry> */
    public function fromExpansion(string $expansionKey): array
    {
        $expansionKey = strtolower(trim($expansionKey));
        if (!$this->library->isActive($expansionKey)) {
            return [];
        }

        return array_values(array_filter(
            $this->all(),
            static fn (CatalogueEntry $entry): bool => $entry->expansionKey() === $expansionKey
        ));
    }

    /** @return list<CatalogueEntry> */
    public function fromExpansionAndType(string $expansionKey, string $type): array
    {
        $type = strtolower(trim($type));
        return array_values(array_filter(
            $this->fromExpansion($expansionKey),
            static fn (CatalogueEntry $entry): bool => $entry->type() === $type
        ));
    }

    public function find(string $expansionKey, string $type, string $key): ?CatalogueEntry
    {
        foreach ($this->fromExpansionAndType($expansionKey, $type) as $entry) {
            if ($entry->key() === strtolower(trim($key))) {
                return $entry;
            }
        }
        return null;
    }
}
