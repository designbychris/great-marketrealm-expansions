<?php
namespace GreatMarketrealmExpansions\Content;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use InvalidArgumentException;

final class ContentRegistry
{
    /** @var array<string, array<string, array<string, ContentDefinition>>> */
    private array $content = [];

    public function __construct(private ?ContentValidator $validator = null) {}

    public function add(string $expansionKey, ContentDefinition $definition): void
    {
        $expansionKey = trim($expansionKey);
        if ($expansionKey === '') {
            throw new InvalidArgumentException('Content must belong to an expansion pack.');
        }

        if ($this->validator !== null) {
            $this->validator->assertValid($definition);
        }

        $type = $definition->type();
        $key = $definition->key();
        if (isset($this->content[$expansionKey][$type][$key])) {
            throw new InvalidArgumentException(sprintf('Content "%s/%s/%s" is already registered.', $expansionKey, $type, $key));
        }
        $this->content[$expansionKey][$type][$key] = $definition;
    }

    public function get(string $expansionKey, string $type, string $key): ?ContentDefinition
    {
        return $this->content[$expansionKey][$type][$key] ?? null;
    }

    /** @return array<string, ContentDefinition> */
    public function ofType(string $type): array
    {
        $results = [];
        foreach ($this->content as $expansionKey => $types) {
            foreach ($types[$type] ?? [] as $key => $definition) {
                $results[$expansionKey . ':' . $key] = $definition;
            }
        }
        return $results;
    }

    /** @return array<string, array<string, ContentDefinition>> */
    public function forExpansion(string $expansionKey): array
    {
        return $this->content[$expansionKey] ?? [];
    }
}
