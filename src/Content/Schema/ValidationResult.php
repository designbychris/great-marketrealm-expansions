<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

final class ValidationResult
{
    /** @param list<ValidationError> $errors */
    public function __construct(private array $errors = []) {}
    public function valid(): bool { return $this->errors === []; }
    /** @return list<ValidationError> */
    public function errors(): array { return $this->errors; }
}
