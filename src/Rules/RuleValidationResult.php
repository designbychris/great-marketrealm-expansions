<?php
namespace GreatMarketrealmExpansions\Rules;

defined('ABSPATH') || exit;

final class RuleValidationResult
{
    /** @param list<RuleValidationError> $errors */
    public function __construct(private array $errors = []) {}

    public function valid(): bool { return $this->errors === []; }

    /** @return list<RuleValidationError> */
    public function errors(): array { return $this->errors; }
}
