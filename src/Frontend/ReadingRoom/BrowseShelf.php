<?php
namespace GreatMarketrealmExpansions\Frontend\ReadingRoom;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Library\Library;

final class BrowseShelf
{
    public function __construct(
        private Catalogue $catalogue,
        private Library $library
    ) {}

    /** @return list<BrowseShelfEntry> */
    public function entries(): array
    {
        $entries = [];

        foreach ($this->catalogue->expansions() as $expansion) {
            $contentTypes = [];

            foreach ($this->catalogue->contentByExpansion($expansion->key()) as $content) {
                $contentTypes[$content->type()] = ($contentTypes[$content->type()] ?? 0) + 1;
            }

            ksort($contentTypes);

            $entries[] = new BrowseShelfEntry(
                $expansion->key(),
                $expansion->name(),
                $expansion->version(),
                $expansion->description(),
                $this->library->isActive($expansion->key()),
                $this->library->compatibility($expansion->key())->status(),
                array_sum($contentTypes),
                $contentTypes,
                $expansion->metadata()
            );
        }

        usort(
            $entries,
            static fn (BrowseShelfEntry $a, BrowseShelfEntry $b): int =>
                [$a->name(), $a->key()] <=> [$b->name(), $b->key()]
        );

        return $entries;
    }

    public function count(): int
    {
        return count($this->entries());
    }

    public function activeCount(): int
    {
        return count(array_filter(
            $this->entries(),
            static fn (BrowseShelfEntry $entry): bool => $entry->active()
        ));
    }

    public function contentCount(): int
    {
        return array_sum(array_map(
            static fn (BrowseShelfEntry $entry): int => $entry->entryCount(),
            $this->entries()
        ));
    }
}
