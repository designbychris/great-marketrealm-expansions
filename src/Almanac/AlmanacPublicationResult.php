<?php
namespace GreatMarketrealmExpansions\Almanac;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Expansions\Loading\ExpansionLoadResult;

final class AlmanacPublicationResult
{
    public function __construct(
        private string $directory,
        private ExpansionLoadResult $loadResult
    ) {}

    public function directory(): string { return $this->directory; }
    public function loadResult(): ExpansionLoadResult { return $this->loadResult; }
    public function key(): string { return $this->loadResult->pack()->key(); }
    public function definitionCount(): int { return count($this->loadResult->files()); }
}
