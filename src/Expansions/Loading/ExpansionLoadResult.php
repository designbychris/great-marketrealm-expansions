<?php
namespace GreatMarketrealmExpansions\Expansions\Loading;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Expansions\ExpansionPack;

final class ExpansionLoadResult
{
    /** @param array<string, int> $contentCounts */
    public function __construct(private ExpansionPack $pack, private array $contentCounts, private array $files) {}

    public function pack(): ExpansionPack { return $this->pack; }
    public function total(): int { return array_sum($this->contentCounts); }
    /** @return array<string, int> */
    public function contentCounts(): array { return $this->contentCounts; }
    /** @return list<string> */
    public function files(): array { return $this->files; }
}
