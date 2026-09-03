<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

use InvalidArgumentException;

final class FieldDefinition
{
    public const STRING = 'string';
    public const INTEGER = 'integer';
    public const NUMBER = 'number';
    public const BOOLEAN = 'boolean';
    public const ARRAY = 'array';
    public const MAP = 'map';

    /** @var list<string> */
    private const SUPPORTED_TYPES = [self::STRING, self::INTEGER, self::NUMBER, self::BOOLEAN, self::ARRAY, self::MAP];

    public function __construct(
        private string $name,
        private string $type = self::STRING,
        private bool $required = false,
        private bool $allowEmpty = false
    ) {
        $this->name = trim($this->name);
        if ($this->name === '') {
            throw new InvalidArgumentException('Schema fields require a name.');
        }
        if (!in_array($this->type, self::SUPPORTED_TYPES, true)) {
            throw new InvalidArgumentException(sprintf('Unsupported schema field type "%s".', $this->type));
        }
    }

    public function name(): string { return $this->name; }
    public function type(): string { return $this->type; }
    public function required(): bool { return $this->required; }
    public function allowEmpty(): bool { return $this->allowEmpty; }

    public function accepts(mixed $value): bool
    {
        if (!$this->allowEmpty && $this->isEmpty($value)) {
            return false;
        }

        return match ($this->type) {
            self::STRING => is_string($value),
            self::INTEGER => is_int($value),
            self::NUMBER => is_int($value) || is_float($value),
            self::BOOLEAN => is_bool($value),
            self::ARRAY => is_array($value) && array_is_list($value),
            self::MAP => is_array($value) && !array_is_list($value),
            default => false,
        };
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}
