<?php
namespace GreatMarketrealmExpansions\Content;

defined('ABSPATH') || exit;

use InvalidArgumentException;

final class ContentDefinition
{
    private string $type;
    private string $key;

    /** @param array<string, mixed> $data */
    public function __construct(string $type, string $key, private array $data = [])
    {
        $this->type = self::normalize($type);
        $this->key = self::normalize($key);

        if ($this->type === '' || $this->key === '') {
            throw new InvalidArgumentException('Content definitions require a valid type and key.');
        }
    }

    public function type(): string { return $this->type; }
    public function key(): string { return $this->key; }
    /** @return array<string, mixed> */
    public function data(): array { return $this->data; }
    public function value(string $field, mixed $default = null): mixed { return $this->data[$field] ?? $default; }

    private static function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_\-]+/', '-', $value) ?? '';
        return trim(preg_replace('/-+/', '-', $value) ?? '', '-');
    }
}
