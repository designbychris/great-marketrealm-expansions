<?php
namespace GreatMarketrealmExpansions\Integration;

defined('ABSPATH') || exit;

use InvalidArgumentException;

final class ConsumerRegistry
{
    /** @var array<string, Consumer> */
    private array $consumers = [];

    public function register(Consumer $consumer): void
    {
        $existing = $this->consumers[$consumer->key()] ?? null;
        if ($existing !== null) {
            if ($existing->version() === $consumer->version() && $existing->toArray() === $consumer->toArray()) { return; }
            throw new InvalidArgumentException(sprintf(
                'Consumer "%s" is already registered with version %s.',
                $consumer->key(),
                $existing->version()
            ));
        }
        $this->consumers[$consumer->key()] = $consumer;
    }

    public function has(string $key): bool { return isset($this->consumers[$key]); }
    public function get(string $key): ?Consumer { return $this->consumers[$key] ?? null; }

    /** @return array<string, Consumer> */
    public function all(): array
    {
        $consumers = $this->consumers;
        ksort($consumers);
        return $consumers;
    }
}
