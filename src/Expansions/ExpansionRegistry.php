<?php
namespace GreatMarketrealmExpansions\Expansions;

defined('ABSPATH') || exit;

use InvalidArgumentException;

final class ExpansionRegistry
{
    /** @var array<string, ExpansionPack> */
    private array $packs = [];

    public function add(ExpansionPack $pack): void
    {
        if ($this->has($pack->key())) {
            throw new InvalidArgumentException(sprintf('Expansion pack "%s" is already registered.', $pack->key()));
        }
        $this->packs[$pack->key()] = $pack;
    }

    public function has(string $key): bool
    {
        return isset($this->packs[$key]);
    }

    public function get(string $key): ?ExpansionPack
    {
        return $this->packs[$key] ?? null;
    }

    /** @return array<string, ExpansionPack> */
    public function all(): array
    {
        return $this->packs;
    }
}
