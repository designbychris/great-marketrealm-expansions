<?php
namespace GreatMarketrealmExpansions\Application;

defined('ABSPATH') || exit;

use InvalidArgumentException;

final class Container
{
    /** @var array<string, callable(self): mixed> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    public function singleton(string $id, callable $factory): void
    {
        $this->bindings[$id] = $factory;
        unset($this->instances[$id]);
    }

    public function instance(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->instances) || array_key_exists($id, $this->bindings);
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (!array_key_exists($id, $this->bindings)) {
            throw new InvalidArgumentException(sprintf('No service is registered for "%s".', $id));
        }

        return $this->instances[$id] = ($this->bindings[$id])($this);
    }
}
