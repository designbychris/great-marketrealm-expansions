<?php
namespace GreatMarketrealmExpansions\Rules;

defined('ABSPATH') || exit;

use InvalidArgumentException;

final class RuleValidationException extends InvalidArgumentException
{
    public function __construct(private RuleValidationResult $result)
    {
        parent::__construct('The rule statement is invalid.');
    }

    public function result(): RuleValidationResult { return $this->result; }
}
