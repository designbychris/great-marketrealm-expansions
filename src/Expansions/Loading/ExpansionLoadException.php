<?php
namespace GreatMarketrealmExpansions\Expansions\Loading;

defined('ABSPATH') || exit;

use RuntimeException;
use Throwable;

final class ExpansionLoadException extends RuntimeException
{
    public function __construct(string $message, private ?string $source = null, ?Throwable $previous = null)
    {
        parent::__construct($source !== null ? sprintf('%s [%s]', $message, $source) : $message, 0, $previous);
    }

    public function source(): ?string { return $this->source; }
}
