<?php
namespace GreatMarketrealmExpansions\Catalogue;

defined('ABSPATH') || exit;

final class CatalogueQuery
{
    /** @var list<string> */
    private array $types = [];
    /** @var list<string> */
    private array $expansions = [];
    /** @var list<string> */
    private array $tags = [];
    private ?string $key = null;

    public function __construct(private Catalogue $catalogue) {}

    public function type(string $type): self
    {
        $clone = clone $this;
        $clone->types = [$type];
        return $clone;
    }

    /** @param list<string> $types */
    public function types(array $types): self
    {
        $clone = clone $this;
        $clone->types = array_values($types);
        return $clone;
    }

    public function from(string $expansionKey): self
    {
        $clone = clone $this;
        $clone->expansions = [$expansionKey];
        return $clone;
    }

    /** @param list<string> $expansionKeys */
    public function fromAny(array $expansionKeys): self
    {
        $clone = clone $this;
        $clone->expansions = array_values($expansionKeys);
        return $clone;
    }

    public function tag(string $tag): self
    {
        $clone = clone $this;
        $clone->tags[] = $tag;
        return $clone;
    }

    public function key(string $key): self
    {
        $clone = clone $this;
        $clone->key = $key;
        return $clone;
    }

    /** @return list<CatalogueEntry> */
    public function get(): array
    {
        return array_values(array_filter($this->catalogue->allContent(), function (CatalogueEntry $entry): bool {
            if ($this->types !== [] && !in_array($entry->type(), $this->types, true)) { return false; }
            if ($this->expansions !== [] && !in_array($entry->expansionKey(), $this->expansions, true)) { return false; }
            if ($this->key !== null && $entry->key() !== $this->key) { return false; }
            foreach ($this->tags as $tag) {
                if (!in_array($tag, $entry->tags(), true)) { return false; }
            }
            return true;
        }));
    }

    public function first(): ?CatalogueEntry
    {
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function count(): int { return count($this->get()); }
}
