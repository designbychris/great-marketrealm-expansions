<?php
namespace GreatMarketrealmExpansions\Rules;

defined('ABSPATH') || exit;

final class RuleValidationError
{
    public function __construct(private string $field, private string $message) {}

    public function field(): string { return $this->field; }
    public function message(): string { return $this->message; }

    /** @return array{field:string,message:string} */
    public function toArray(): array
    {
        return ['field' => $this->field, 'message' => $this->message];
    }
}
