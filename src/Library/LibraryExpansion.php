<?php
namespace GreatMarketrealmExpansions\Library;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Catalogue\CatalogueExpansion;

final class LibraryExpansion
{
    public function __construct(private CatalogueExpansion $expansion, private bool $active) {}

    public function key(): string { return $this->expansion->key(); }
    public function name(): string { return $this->expansion->name(); }
    public function version(): string { return $this->expansion->version(); }
    public function description(): string { return $this->expansion->description(); }
    public function active(): bool { return $this->active; }
    public function expansion(): CatalogueExpansion { return $this->expansion; }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return array_merge($this->expansion->toArray(), ['active' => $this->active]);
    }
}
