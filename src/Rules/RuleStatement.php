<?php
namespace GreatMarketrealmExpansions\Rules;

defined('ABSPATH') || exit;

final class RuleStatement
{
    /** @param array<string,mixed> $payload */
    public function __construct(private string $kind, private array $payload)
    {
    }

    public function kind(): string { return $this->kind; }

    /** @return array<string,mixed> */
    public function payload(): array { return $this->payload; }

    public function type(): ?string
    {
        $value = $this->payload['type'] ?? null;
        return is_string($value) ? $value : null;
    }

    public function target(): ?string
    {
        $value = $this->payload['target'] ?? null;
        return is_string($value) ? $value : null;
    }

    public function operation(): ?string
    {
        $value = $this->payload['operation'] ?? null;
        return is_string($value) ? $value : null;
    }

    public function value(mixed $default = null): mixed
    {
        return array_key_exists('value', $this->payload) ? $this->payload['value'] : $default;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return ['kind' => $this->kind] + $this->payload;
    }
}
