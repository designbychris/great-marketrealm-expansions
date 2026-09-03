<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

use InvalidArgumentException;

final class SchemaRegistry
{
    /** @var array<string, ContentSchema> */
    private array $schemas = [];

    public function add(ContentSchema $schema): void
    {
        if ($this->has($schema->type())) {
            throw new InvalidArgumentException(sprintf('Schema for content type "%s" is already registered.', $schema->type()));
        }
        $this->schemas[$schema->type()] = $schema;
    }

    public function has(string $type): bool { return isset($this->schemas[$type]); }
    public function get(string $type): ?ContentSchema { return $this->schemas[$type] ?? null; }
    /** @return array<string, ContentSchema> */
    public function all(): array { return $this->schemas; }
}
