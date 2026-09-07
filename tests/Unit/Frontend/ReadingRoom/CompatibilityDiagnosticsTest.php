<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Frontend\ReadingRoom;

use GreatMarketrealmExpansions\Frontend\ReadingRoom\CompatibilityDiagnostics;
use GreatMarketrealmExpansions\Library\CompatibilityIssue;
use GreatMarketrealmExpansions\Library\CompatibilityReport;
use PHPUnit\Framework\TestCase;

final class CompatibilityDiagnosticsTest extends TestCase
{
    private function diagnostics(array $issues = []): CompatibilityDiagnostics
    {
        return new CompatibilityDiagnostics(
            new CompatibilityReport('fixture-book', true, true, $issues)
        );
    }

    public function test_ready_report_has_satisfied_title(): void
    {
        self::assertSame('The Librarian is satisfied.', $this->diagnostics()->title());
    }

    public function test_ready_report_has_zero_counts(): void
    {
        $diagnostics = $this->diagnostics();
        self::assertSame(0, $diagnostics->issueCount());
        self::assertSame(0, $diagnostics->warningCount());
        self::assertSame(0, $diagnostics->blockingCount());
    }

    public function test_ready_summary_reads_existing_report_state(): void
    {
        self::assertStringContainsString('No compatibility issues were reported', $this->diagnostics()->summary());
    }

    public function test_warning_report_is_degraded(): void
    {
        $diagnostics = $this->diagnostics([
            new CompatibilityIssue(CompatibilityIssue::WARNING, 'optional_dependency_missing', 'Optional dependency missing.', 'optional-book'),
        ]);
        self::assertSame(CompatibilityReport::DEGRADED, $diagnostics->status());
        self::assertSame('The Librarian has raised an eyebrow.', $diagnostics->title());
    }

    public function test_warning_count_is_derived_from_report_issues(): void
    {
        $diagnostics = $this->diagnostics([
            new CompatibilityIssue(CompatibilityIssue::WARNING, 'optional_dependency_missing', 'One.'),
            new CompatibilityIssue(CompatibilityIssue::WARNING, 'consumer_version_unknown', 'Two.'),
        ]);
        self::assertSame(2, $diagnostics->warningCount());
        self::assertSame(0, $diagnostics->blockingCount());
    }

    public function test_blocking_report_is_blocked(): void
    {
        $diagnostics = $this->diagnostics([
            new CompatibilityIssue(CompatibilityIssue::BLOCKING, 'required_dependency_missing', 'Required dependency missing.', 'required-book'),
        ]);
        self::assertSame(CompatibilityReport::BLOCKED, $diagnostics->status());
        self::assertSame('The Librarian has stopped this book at the desk.', $diagnostics->title());
    }

    public function test_mixed_issue_counts_preserve_severity(): void
    {
        $diagnostics = $this->diagnostics([
            new CompatibilityIssue(CompatibilityIssue::WARNING, 'optional_dependency_missing', 'Warning.'),
            new CompatibilityIssue(CompatibilityIssue::BLOCKING, 'required_dependency_missing', 'Blocking.'),
        ]);
        self::assertSame(2, $diagnostics->issueCount());
        self::assertSame(1, $diagnostics->warningCount());
        self::assertSame(1, $diagnostics->blockingCount());
    }

    public function test_issue_order_is_not_reinterpreted_by_frontend(): void
    {
        $first = new CompatibilityIssue(CompatibilityIssue::WARNING, 'z_first_from_engine', 'First.');
        $second = new CompatibilityIssue(CompatibilityIssue::BLOCKING, 'a_second_from_engine', 'Second.');
        self::assertSame([$first, $second], $this->diagnostics([$first, $second])->issues());
    }

    public function test_severity_labels_are_presentation_only(): void
    {
        $warning = new CompatibilityIssue(CompatibilityIssue::WARNING, 'warning_code', 'Warning.');
        $blocking = new CompatibilityIssue(CompatibilityIssue::BLOCKING, 'blocking_code', 'Blocking.');
        $diagnostics = $this->diagnostics([$warning, $blocking]);
        self::assertSame('Warning', $diagnostics->severityLabel($warning));
        self::assertSame('Blocking', $diagnostics->severityLabel($blocking));
    }

    public function test_issue_code_label_does_not_change_canonical_code(): void
    {
        $issue = new CompatibilityIssue(CompatibilityIssue::BLOCKING, 'required_dependency_version_incompatible', 'Version mismatch.');
        $diagnostics = $this->diagnostics([$issue]);
        self::assertSame('Required Dependency Version Incompatible', $diagnostics->codeLabel($issue));
        self::assertSame('required_dependency_version_incompatible', $issue->code());
    }
}
