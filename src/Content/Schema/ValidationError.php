<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

final class ValidationError
{
    public function __construct(private string $field, private string $message) {}
    public function field(): string { return $this->field; }
    public function message(): string { return $this->message; }
}
