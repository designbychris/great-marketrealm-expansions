<?php
namespace GreatMarketrealmExpansions\Content\Types;

defined('ABSPATH') || exit;

use InvalidArgumentException;

final class ContentType
{
    private string $key;
    private string $label;

    public function __construct(string $key, string $label, private string $description = '')
    {
        $this->key = self::normalize($key);
        $this->label = trim($label);
        $this->description = trim($description);

        if ($this->key === '' || $this->label === '') {
            throw new InvalidArgumentException('Content types require a valid key and label.');
        }
    }

    public function key(): string { return $this->key; }
    public function label(): string { return $this->label; }
    public function description(): string { return $this->description; }

    private static function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_\-]+/', '-', $value) ?? '';
        return trim(preg_replace('/-+/', '-', $value) ?? '', '-');
    }
}
