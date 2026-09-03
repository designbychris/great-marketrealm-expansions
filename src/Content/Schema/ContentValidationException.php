<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

use InvalidArgumentException;

final class ContentValidationException extends InvalidArgumentException
{
    public function __construct(private ValidationResult $result)
    {
        $messages = array_map(static fn (ValidationError $error): string => $error->message(), $result->errors());
        parent::__construct('Content definition failed validation: ' . implode(' ', $messages));
    }

    public function result(): ValidationResult { return $this->result; }
}
