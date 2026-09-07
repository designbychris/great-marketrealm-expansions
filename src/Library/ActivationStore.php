<?php
namespace GreatMarketrealmExpansions\Library;

defined('ABSPATH') || exit;

interface ActivationStore
{
    public function state(string $expansionKey): ?bool;
    public function set(string $expansionKey, bool $active): void;
    /** @return array<string,bool> */
    public function all(): array;
}
