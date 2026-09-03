<?php
namespace GreatMarketrealmExpansions\Content\Types;

defined('ABSPATH') || exit;

use InvalidArgumentException;

final class ContentTypeCatalogue
{
    /** @var array<string, ContentType> */
    private array $types = [];

    public function add(ContentType $type): void
    {
        if ($this->has($type->key())) {
            throw new InvalidArgumentException(sprintf('Content type "%s" is already registered.', $type->key()));
        }
        $this->types[$type->key()] = $type;
    }

    public function has(string $key): bool { return isset($this->types[$key]); }
    public function get(string $key): ?ContentType { return $this->types[$key] ?? null; }

    /** @return array<string, ContentType> */
    public function all(): array { return $this->types; }
}
