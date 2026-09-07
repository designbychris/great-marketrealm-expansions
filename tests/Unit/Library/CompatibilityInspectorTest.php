<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Library;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Expansions\ExpansionPack;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use GreatMarketrealmExpansions\Library\CompatibilityIssue;
use GreatMarketrealmExpansions\Library\CompatibilityReport;
use GreatMarketrealmExpansions\Library\InMemoryActivationStore;
use GreatMarketrealmExpansions\Library\Library;
use PHPUnit\Framework\TestCase;

final class CompatibilityInspectorTest extends TestCase
{
    /**
     * @param array<string,array<string,mixed>> $metadata
     * @param array<string,bool> $states
     */
    private function library(array $metadata = [], array $states = []): Library
    {
        $expansions = new ExpansionRegistry();
        $content = new ContentRegistry();

        foreach ([
            'base-pack' => ['Base Pack', '1.5.0'],
            'addon-pack' => ['Addon Pack', '2.0.0'],
            'conflict-pack' => ['Conflict Pack', '3.0.0'],
        ] as $key => [$name, $version]) {
            $expansions->add(new ExpansionPack(
                $key,
                $name,
                $version,
                '',
                $metadata[$key] ?? []
            ));
        }

        return new Library(new Catalogue($expansions, $content), new InMemoryActivationStore($states));
    }

    public function test_pack_without_requirements_is_ready(): void
    {
        $report = $this->library()->compatibility('base-pack');

        self::assertSame(CompatibilityReport::READY, $report->status());
        self::assertTrue($report->ready());
        self::assertSame([], $report->issues());
    }

    public function test_unknown_pack_is_blocked(): void
    {
        $report = $this->library()->compatibility('missing-pack');

        self::assertTrue($report->blocked());
        self::assertFalse($report->installed());
        self::assertSame('expansion_not_installed', $report->issues()[0]->code());
    }

    public function test_required_missing_dependency_blocks_pack(): void
    {
        $library = $this->library([
            'addon-pack' => [
                'dependencies' => [
                    ['key' => 'missing-pack', 'version' => '>=1.0.0'],
                ],
            ],
        ]);

        $report = $library->compatibility('addon-pack');

        self::assertTrue($report->blocked());
        self::assertSame('required_dependency_missing', $report->issues()[0]->code());
        self::assertTrue($report->issues()[0]->blocking());
    }

    public function test_optional_missing_dependency_degrades_pack(): void
    {
        $library = $this->library([
            'addon-pack' => [
                'dependencies' => [
                    ['key' => 'missing-pack', 'required' => false],
                ],
            ],
        ]);

        $report = $library->compatibility('addon-pack');

        self::assertTrue($report->degraded());
        self::assertSame('optional_dependency_missing', $report->issues()[0]->code());
        self::assertFalse($report->issues()[0]->blocking());
    }

    public function test_required_dependency_version_constraint_is_checked(): void
    {
        $library = $this->library([
            'addon-pack' => [
                'dependencies' => [
                    ['key' => 'base-pack', 'version' => '>=2.0.0'],
                ],
            ],
        ]);

        $report = $library->compatibility('addon-pack');

        self::assertTrue($report->blocked());
        self::assertSame('required_dependency_version_incompatible', $report->issues()[0]->code());
    }

    public function test_comma_separated_version_constraints_are_supported(): void
    {
        $library = $this->library([
            'addon-pack' => [
                'dependencies' => [
                    ['key' => 'base-pack', 'version' => '>=1.0.0,<2.0.0'],
                ],
            ],
        ]);

        self::assertTrue($library->compatibility('addon-pack')->ready());
    }

    public function test_required_dependency_must_be_active_by_default(): void
    {
        $library = $this->library([
            'addon-pack' => [
                'dependencies' => [
                    ['key' => 'base-pack'],
                ],
            ],
        ], ['base-pack' => false]);

        $report = $library->compatibility('addon-pack');

        self::assertTrue($report->blocked());
        self::assertSame('required_dependency_inactive', $report->issues()[0]->code());
    }

    public function test_dependency_can_explicitly_not_require_active_state(): void
    {
        $library = $this->library([
            'addon-pack' => [
                'dependencies' => [
                    ['key' => 'base-pack', 'active' => false],
                ],
            ],
        ], ['base-pack' => false]);

        self::assertTrue($library->compatibility('addon-pack')->ready());
    }

    public function test_optional_inactive_dependency_degrades_pack(): void
    {
        $library = $this->library([
            'addon-pack' => [
                'dependencies' => [
                    ['key' => 'base-pack', 'required' => false],
                ],
            ],
        ], ['base-pack' => false]);

        $report = $library->compatibility('addon-pack');

        self::assertTrue($report->degraded());
        self::assertSame('optional_dependency_inactive', $report->issues()[0]->code());
    }

    public function test_associative_dependency_version_syntax_is_supported(): void
    {
        $library = $this->library([
            'addon-pack' => [
                'dependencies' => [
                    'base-pack' => '>=1.0.0,<2.0.0',
                ],
            ],
        ]);

        self::assertTrue($library->compatibility('addon-pack')->ready());
    }

    public function test_active_conflicting_pack_blocks_compatibility(): void
    {
        $library = $this->library([
            'addon-pack' => [
                'conflicts' => ['conflict-pack'],
            ],
        ]);

        $report = $library->compatibility('addon-pack');

        self::assertTrue($report->blocked());
        self::assertSame('conflicting_expansion_present', $report->issues()[0]->code());
        self::assertSame('conflict-pack', $report->issues()[0]->subject());
    }

    public function test_inactive_conflicting_pack_does_not_block_by_default(): void
    {
        $library = $this->library([
            'addon-pack' => [
                'conflicts' => ['conflict-pack'],
            ],
        ], ['conflict-pack' => false]);

        self::assertTrue($library->compatibility('addon-pack')->ready());
    }

    public function test_conflict_can_apply_even_when_other_pack_is_inactive(): void
    {
        $library = $this->library([
            'addon-pack' => [
                'conflicts' => [
                    ['key' => 'conflict-pack', 'active_only' => false],
                ],
            ],
        ], ['conflict-pack' => false]);

        self::assertTrue($library->compatibility('addon-pack')->blocked());
    }

    public function test_version_scoped_conflict_only_applies_to_matching_version(): void
    {
        $library = $this->library([
            'addon-pack' => [
                'conflicts' => [
                    ['key' => 'conflict-pack', 'version' => '<3.0.0'],
                ],
            ],
        ]);

        self::assertTrue($library->compatibility('addon-pack')->ready());
    }

    public function test_unknown_consumer_version_degrades_report(): void
    {
        $library = $this->library([
            'addon-pack' => [
                'compatibility' => [
                    'consumers' => [
                        'great-marketrealm-companion' => '>=1.0.0',
                    ],
                ],
            ],
        ]);

        $report = $library->compatibility('addon-pack');

        self::assertTrue($report->degraded());
        self::assertSame('consumer_version_unknown', $report->issues()[0]->code());
    }

    public function test_known_compatible_consumer_version_is_ready(): void
    {
        $library = $this->library([
            'addon-pack' => [
                'compatibility' => [
                    'consumers' => [
                        'great-marketrealm-companion' => '>=1.0.0,<2.0.0',
                    ],
                ],
            ],
        ]);

        self::assertTrue(
            $library->compatibility('addon-pack', [
                'great-marketrealm-companion' => '1.4.0',
            ])->ready()
        );
    }

    public function test_known_incompatible_consumer_version_blocks_report(): void
    {
        $library = $this->library([
            'addon-pack' => [
                'compatibility' => [
                    'consumers' => [
                        'great-marketrealm-tabletop' => '>=2.0.0',
                    ],
                ],
            ],
        ]);

        $report = $library->compatibility('addon-pack', [
            'great-marketrealm-tabletop' => '1.8.0',
        ]);

        self::assertTrue($report->blocked());
        self::assertSame('consumer_version_incompatible', $report->issues()[0]->code());
    }

    public function test_all_reports_are_sorted_by_expansion_key(): void
    {
        $reports = $this->library()->compatibilityReports();

        self::assertSame(
            ['addon-pack', 'base-pack', 'conflict-pack'],
            array_keys($reports)
        );
    }

    public function test_report_serialises_status_and_issues(): void
    {
        $library = $this->library([
            'addon-pack' => [
                'dependencies' => [
                    ['key' => 'missing-pack', 'required' => false],
                ],
            ],
        ]);

        $data = $library->compatibility('addon-pack')->toArray();

        self::assertSame('addon-pack', $data['expansion']);
        self::assertTrue($data['installed']);
        self::assertTrue($data['active']);
        self::assertSame('degraded', $data['status']);
        self::assertSame('warning', $data['issues'][0]['severity']);
        self::assertSame('optional_dependency_missing', $data['issues'][0]['code']);
    }

    public function test_invalid_version_constraint_is_safely_reported_as_incompatible(): void
    {
        $library = $this->library([
            'addon-pack' => [
                'dependencies' => [
                    ['key' => 'base-pack', 'version' => '^1.0'],
                ],
            ],
        ]);

        self::assertSame(
            'required_dependency_version_incompatible',
            $library->compatibility('addon-pack')->issues()[0]->code()
        );
    }

    public function test_library_advertises_compatibility_capabilities(): void
    {
        $library = $this->library();

        self::assertTrue($library->supports('library.compatibility.report'));
        self::assertTrue($library->supports('library.dependencies'));
        self::assertTrue($library->supports('library.conflicts'));
    }
}
