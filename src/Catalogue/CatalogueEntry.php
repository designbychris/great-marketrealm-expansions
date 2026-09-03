<?php
namespace GreatMarketrealmExpansions\Catalogue;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;

final class CatalogueEntry
{
    /** @param array<string, mixed> $data */
    private function __construct(
        private string $expansionKey,
        private string $type,
        private string $key,
        private array $data
    ) {}

    public static function fromDefinition(string $expansionKey, ContentDefinition $definition): self
    {
        return new self($expansionKey, $definition->type(), $definition->key(), $definition->data());
    }

    public function id(): string { return $this->expansionKey . ':' . $this->type . ':' . $this->key; }
    public function expansionKey(): string { return $this->expansionKey; }
    public function type(): string { return $this->type; }
    public function key(): string { return $this->key; }
    /** @return array<string, mixed> */
    public function data(): array { return $this->data; }
    public function value(string $field, mixed $default = null): mixed { return $this->data[$field] ?? $default; }
    public function name(): string { return is_string($this->value('name')) ? $this->value('name') : ''; }
    /** @return list<string> */
    public function tags(): array
    {
        $tags = $this->value('tags', []);
        return is_array($tags) ? array_values(array_filter($tags, 'is_string')) : [];
    }
    /** @return array<string, mixed> */
    public function provenance(): array
    {
        $value = $this->value('provenance', []);
        return is_array($value) ? $value : [];
    }
    /** @return array<string, mixed> */
    public function compatibility(): array
    {
        $value = $this->value('compatibility', []);
        return is_array($value) ? $value : [];
    }
    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id(),
            'expansion' => $this->expansionKey,
            'type' => $this->type,
            'key' => $this->key,
            'data' => $this->data,
        ];
    }
}
