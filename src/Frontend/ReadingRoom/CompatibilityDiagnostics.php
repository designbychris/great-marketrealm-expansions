<?php
namespace GreatMarketrealmExpansions\Frontend\ReadingRoom;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Library\CompatibilityIssue;
use GreatMarketrealmExpansions\Library\CompatibilityReport;

final class CompatibilityDiagnostics
{
    public function __construct(private CompatibilityReport $report) {}

    public function status(): string
    {
        return $this->report->status();
    }

    public function title(): string
    {
        return match ($this->status()) {
            CompatibilityReport::BLOCKED => 'The Librarian has stopped this book at the desk.',
            CompatibilityReport::DEGRADED => 'The Librarian has raised an eyebrow.',
            default => 'The Librarian is satisfied.',
        };
    }

    public function summary(): string
    {
        return match ($this->status()) {
            CompatibilityReport::BLOCKED => 'One or more blocking compatibility issues were reported by the Living Library. Activation state has not been changed automatically.',
            CompatibilityReport::DEGRADED => 'The Living Library reported one or more warnings. The Almanac remains installed; review the notes below before relying on every optional integration.',
            default => 'No compatibility issues were reported by the Living Library for this Almanac.',
        };
    }

    public function issueCount(): int
    {
        return count($this->report->issues());
    }

    public function blockingCount(): int
    {
        return count(array_filter(
            $this->report->issues(),
            static fn (CompatibilityIssue $issue): bool => $issue->blocking()
        ));
    }

    public function warningCount(): int
    {
        return $this->issueCount() - $this->blockingCount();
    }

    /** @return list<CompatibilityIssue> */
    public function issues(): array
    {
        return $this->report->issues();
    }

    public function severityLabel(CompatibilityIssue $issue): string
    {
        return $issue->blocking() ? 'Blocking' : 'Warning';
    }

    public function codeLabel(CompatibilityIssue $issue): string
    {
        return ucwords(str_replace('_', ' ', $issue->code()));
    }
}
