<?php
namespace GreatMarketrealmExpansions\Library;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Catalogue\CatalogueEntry;
use InvalidArgumentException;

final class Library
{
    public const API_VERSION = '1.0.0';

    /** @var list<string> */
    private const CAPABILITIES = [
        'library.expansions',
        'library.activation.read',
        'library.activation.write',
        'library.content.active',
        'library.compatibility.report',
        'library.dependencies',
        'library.conflicts',
    ];

    public function __construct(private Catalogue $catalogue, private ActivationStore $activation) {}

    public function apiVersion(): string { return self::API_VERSION; }
    /** @return list<string> */
    public function capabilities(): array { return self::CAPABILITIES; }

    public function supports(string $capability): bool
    {
        return in_array(strtolower(trim($capability)), self::CAPABILITIES, true);
    }

    /** @return array<string,LibraryExpansion> */
    public function expansions(): array
    {
        $result = [];
        foreach ($this->catalogue->expansions() as $key => $expansion) {
            $result[$key] = new LibraryExpansion($expansion, $this->isActive($key));
        }
        ksort($result);
        return $result;
    }

    /** @return array<string,LibraryExpansion> */
    public function activeExpansions(): array
    {
        return array_filter($this->expansions(), static fn (LibraryExpansion $e): bool => $e->active());
    }

    /** @return array<string,LibraryExpansion> */
    public function inactiveExpansions(): array
    {
        return array_filter($this->expansions(), static fn (LibraryExpansion $e): bool => !$e->active());
    }

    public function isInstalled(string $expansionKey): bool
    {
        return $this->catalogue->expansion($expansionKey) !== null;
    }

    public function isActive(string $expansionKey): bool
    {
        if (!$this->isInstalled($expansionKey)) {
            return false;
        }

        $stored = $this->activation->state($expansionKey);
        return $stored ?? true;
    }

    public function setActive(string $expansionKey, bool $active): void
    {
        if (!$this->isInstalled($expansionKey)) {
            throw new InvalidArgumentException(sprintf('Expansion pack "%s" is not installed.', $expansionKey));
        }

        $this->activation->set($expansionKey, $active);
    }


    /** @param array<string,string> $environmentVersions */
    public function compatibility(string $expansionKey, array $environmentVersions = []): CompatibilityReport
    {
        return (new CompatibilityInspector($this->catalogue, $this))->inspect($expansionKey, $environmentVersions);
    }

    /** @param array<string,string> $environmentVersions @return array<string,CompatibilityReport> */
    public function compatibilityReports(array $environmentVersions = []): array
    {
        return (new CompatibilityInspector($this->catalogue, $this))->inspectAll($environmentVersions);
    }

    /** @return list<CatalogueEntry> */
    public function activeContent(): array
    {
        $entries = [];
        foreach (array_keys($this->activeExpansions()) as $key) {
            foreach ($this->catalogue->contentByExpansion($key) as $entry) {
                $entries[] = $entry;
            }
        }
        usort($entries, static fn (CatalogueEntry $a, CatalogueEntry $b): int => $a->id() <=> $b->id());
        return $entries;
    }
}
