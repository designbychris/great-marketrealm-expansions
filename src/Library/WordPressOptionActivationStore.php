<?php
namespace GreatMarketrealmExpansions\Library;

defined('ABSPATH') || exit;

final class WordPressOptionActivationStore implements ActivationStore
{
    public const OPTION = 'gmrexp_expansion_activation';

    /** @var array<string,bool> */
    private array $fallback = [];

    public function state(string $expansionKey): ?bool
    {
        $states = $this->read();
        $key = $this->key($expansionKey);
        return array_key_exists($key, $states) ? (bool) $states[$key] : null;
    }

    public function set(string $expansionKey, bool $active): void
    {
        $states = $this->read();
        $states[$this->key($expansionKey)] = $active;
        ksort($states);

        if (function_exists('update_option')) {
            update_option(self::OPTION, $states, false);
            return;
        }

        $this->fallback = $states;
    }

    public function all(): array
    {
        $states = $this->read();
        ksort($states);
        return $states;
    }

    /** @return array<string,bool> */
    private function read(): array
    {
        $value = function_exists('get_option') ? get_option(self::OPTION, []) : $this->fallback;
        if (!is_array($value)) {
            return [];
        }

        $states = [];
        foreach ($value as $key => $active) {
            if (!is_string($key)) {
                continue;
            }
            $states[$this->key($key)] = (bool) $active;
        }
        return $states;
    }

    private function key(string $key): string
    {
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9_\-]+/', '-', $key) ?? '';
        return trim(preg_replace('/-+/', '-', $key) ?? '', '-');
    }
}
