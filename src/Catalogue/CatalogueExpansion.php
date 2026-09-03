<?php
namespace GreatMarketrealmExpansions\Catalogue;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Expansions\ExpansionPack;

final class CatalogueExpansion
{
    /** @param array<string, mixed> $metadata */
    private function __construct(
        private string $key,
        private string $name,
        private string $version,
        private string $description,
        private array $metadata
    ) {}

    public static function fromPack(ExpansionPack $pack): self
    {
        return new self($pack->key(), $pack->name(), $pack->version(), $pack->description(), $pack->metadata());
    }

    public function key(): string { return $this->key; }
    public function name(): string { return $this->name; }
    public function version(): string { return $this->version; }
    public function description(): string { return $this->description; }
    /** @return array<string, mixed> */
    public function metadata(): array { return $this->metadata; }
    public function meta(string $key, mixed $default = null): mixed { return $this->metadata[$key] ?? $default; }
    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'version' => $this->version,
            'description' => $this->description,
            'metadata' => $this->metadata,
        ];
    }
}
