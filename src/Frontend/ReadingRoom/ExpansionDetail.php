<?php
namespace GreatMarketrealmExpansions\Frontend\ReadingRoom;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Catalogue\CatalogueEntry;
use GreatMarketrealmExpansions\Library\Library;

final class ExpansionDetail
{
    public function __construct(
        private Catalogue $catalogue,
        private Library $library,
        private string $expansionKey
    ) {}

    public function exists(): bool
    {
        return $this->catalogue->expansion($this->expansionKey) !== null;
    }

    /** @return array<string,mixed>|null */
    public function expansion(): ?array
    {
        $expansion = $this->catalogue->expansion($this->expansionKey);
        if ($expansion === null) {
            return null;
        }

        return [
            'key' => $expansion->key(),
            'name' => $expansion->name(),
            'version' => $expansion->version(),
            'description' => $expansion->description(),
            'active' => $this->library->isActive($expansion->key()),
            'compatibility_status' => $this->library->compatibility($expansion->key())->status(),
            'entry_count' => count($this->catalogue->contentByExpansion($expansion->key())),
        ];
    }

    /** @return array<string,list<CatalogueEntry>> */
    public function families(): array
    {
        $families = [];
        foreach ($this->catalogue->contentByExpansion($this->expansionKey) as $entry) {
            $families[$entry->type()][] = $entry;
        }

        ksort($families);
        foreach ($families as &$entries) {
            usort(
                $entries,
                static fn (CatalogueEntry $a, CatalogueEntry $b): int =>
                    [strtolower($a->name()), $a->key()] <=> [strtolower($b->name()), $b->key()]
            );
        }
        unset($entries);

        return $families;
    }

    /** @return list<CatalogueEntry> */
    public function family(string $type): array
    {
        return $this->families()[$type] ?? [];
    }

    /** @return array<string,int> */
    public function familyCounts(): array
    {
        $counts = [];
        foreach ($this->families() as $type => $entries) {
            $counts[$type] = count($entries);
        }
        return $counts;
    }
}
