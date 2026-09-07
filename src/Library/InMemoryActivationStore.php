<?php
namespace GreatMarketrealmExpansions\Library;

defined('ABSPATH') || exit;

final class InMemoryActivationStore implements ActivationStore
{
    /** @var array<string,bool> */
    private array $states = [];

    /** @param array<string,bool> $states */
    public function __construct(array $states = [])
    {
        foreach ($states as $key => $active) {
            $this->states[$this->key($key)] = (bool) $active;
        }
    }

    public function state(string $expansionKey): ?bool
    {
        $key = $this->key($expansionKey);
        return array_key_exists($key, $this->states) ? $this->states[$key] : null;
    }

    public function set(string $expansionKey, bool $active): void
    {
        $this->states[$this->key($expansionKey)] = $active;
    }

    public function all(): array
    {
        ksort($this->states);
        return $this->states;
    }

    private function key(string $key): string
    {
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9_\-]+/', '-', $key) ?? '';
        return trim(preg_replace('/-+/', '-', $key) ?? '', '-');
    }
}
