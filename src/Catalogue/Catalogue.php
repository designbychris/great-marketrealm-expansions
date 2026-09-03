<?php
namespace GreatMarketrealmExpansions\Catalogue;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;

final class Catalogue
{
    public const API_VERSION = '1.0.0';

    /** @var list<string> */
    private const CAPABILITIES = [
        'catalogue.expansions',
        'catalogue.content',
        'catalogue.query',
        'catalogue.query.type',
        'catalogue.query.expansion',
        'catalogue.query.tag',
        'catalogue.provenance',
        'catalogue.compatibility',
        'catalogue.rest',
    ];

    public function __construct(
        private ExpansionRegistry $expansions,
        private ContentRegistry $content
    ) {}

    public function apiVersion(): string { return self::API_VERSION; }

    /** @return list<string> */
    public function capabilities(): array { return self::CAPABILITIES; }

    public function supports(string $capability): bool
    {
        return in_array($capability, self::CAPABILITIES, true);
    }

    /** @return array<string, CatalogueExpansion> */
    public function expansions(): array
    {
        $results = [];
        foreach ($this->expansions->all() as $key => $pack) {
            $results[$key] = CatalogueExpansion::fromPack($pack);
        }
        ksort($results);
        return $results;
    }

    public function expansion(string $key): ?CatalogueExpansion
    {
        $pack = $this->expansions->get($key);
        return $pack === null ? null : CatalogueExpansion::fromPack($pack);
    }

    public function has(string $type, string $key, ?string $expansionKey = null): bool
    {
        if ($expansionKey !== null) {
            return $this->content->get($expansionKey, $type, $key) !== null;
        }
        return $this->query()->type($type)->key($key)->count() > 0;
    }

    public function content(string $type, string $key, ?string $expansionKey = null): ?CatalogueEntry
    {
        if ($expansionKey !== null) {
            $definition = $this->content->get($expansionKey, $type, $key);
            return $definition === null ? null : CatalogueEntry::fromDefinition($expansionKey, $definition);
        }

        $matches = $this->query()->type($type)->key($key)->get();
        if (count($matches) > 1) {
            throw AmbiguousCatalogueEntryException::forLookup(
                $type,
                $key,
                array_values(array_map(static fn (CatalogueEntry $entry): string => $entry->expansionKey(), $matches))
            );
        }
        return $matches[0] ?? null;
    }

    /** @return list<CatalogueEntry> */
    public function contentByType(string $type): array
    {
        return $this->query()->type($type)->get();
    }

    /** @return list<CatalogueEntry> */
    public function contentByExpansion(string $expansionKey): array
    {
        return $this->query()->from($expansionKey)->get();
    }

    /** @return list<CatalogueEntry> */
    public function contentByExpansionAndType(string $expansionKey, string $type): array
    {
        return $this->query()->from($expansionKey)->type($type)->get();
    }

    /** @return list<CatalogueEntry> */
    public function allContent(): array
    {
        $results = [];
        foreach ($this->expansions->all() as $expansionKey => $pack) {
            foreach ($this->content->forExpansion($expansionKey) as $definitions) {
                foreach ($definitions as $definition) {
                    $results[] = CatalogueEntry::fromDefinition($expansionKey, $definition);
                }
            }
        }
        usort($results, static fn (CatalogueEntry $a, CatalogueEntry $b): int => $a->id() <=> $b->id());
        return $results;
    }

    public function query(): CatalogueQuery { return new CatalogueQuery($this); }
}
