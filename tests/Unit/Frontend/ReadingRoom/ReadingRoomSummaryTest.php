<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Frontend\ReadingRoom;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Expansions\ExpansionPack;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use GreatMarketrealmExpansions\Frontend\ReadingRoom\ReadingRoomSummary;
use GreatMarketrealmExpansions\Library\InMemoryActivationStore;
use GreatMarketrealmExpansions\Library\Library;
use PHPUnit\Framework\TestCase;

final class ReadingRoomSummaryTest extends TestCase
{
    private function makeLibrary(): array
    {
        $expansions = new ExpansionRegistry();
        $content = new ContentRegistry();

        $expansions->add(new ExpansionPack('alpha', 'Alpha Almanac'));
        $expansions->add(new ExpansionPack('beta', 'Beta Almanac'));
        $content->add('alpha', new ContentDefinition('feat', 'alpha-feat', ['name' => 'Alpha Feat']));
        $content->add('alpha', new ContentDefinition('monster', 'alpha-monster', ['name' => 'Alpha Monster']));
        $content->add('beta', new ContentDefinition('feat', 'beta-feat', ['name' => 'Beta Feat']));

        $catalogue = new Catalogue($expansions, $content);
        $library = new Library($catalogue, new InMemoryActivationStore(['beta' => false]));

        return [$catalogue, $library];
    }

    public function test_summary_reads_installed_and_active_counts_from_existing_apis(): void
    {
        [$catalogue, $library] = $this->makeLibrary();

        $summary = (new ReadingRoomSummary($catalogue, $library))->toArray();

        self::assertSame(2, $summary['installed']);
        self::assertSame(1, $summary['active']);
        self::assertSame(1, $summary['inactive']);
    }

    public function test_summary_counts_catalogue_entries_without_duplicating_content_state(): void
    {
        [$catalogue, $library] = $this->makeLibrary();

        self::assertSame(3, (new ReadingRoomSummary($catalogue, $library))->toArray()['content']);
    }

    public function test_summary_counts_content_types_deterministically(): void
    {
        [$catalogue, $library] = $this->makeLibrary();

        self::assertSame(
            ['feat' => 2, 'monster' => 1],
            (new ReadingRoomSummary($catalogue, $library))->toArray()['content_types']
        );
    }

    public function test_summary_reports_compatibility_without_mutating_activation(): void
    {
        [$catalogue, $library] = $this->makeLibrary();
        $before = $library->isActive('beta');

        $summary = (new ReadingRoomSummary($catalogue, $library))->toArray();

        self::assertSame(2, $summary['compatibility']['ready']);
        self::assertSame(0, $summary['compatibility']['degraded']);
        self::assertSame(0, $summary['compatibility']['blocked']);
        self::assertSame($before, $library->isActive('beta'));
    }

    public function test_empty_catalogue_has_zeroed_summary(): void
    {
        $catalogue = new Catalogue(new ExpansionRegistry(), new ContentRegistry());
        $library = new Library($catalogue, new InMemoryActivationStore());

        $summary = (new ReadingRoomSummary($catalogue, $library))->toArray();

        self::assertSame(0, $summary['installed']);
        self::assertSame(0, $summary['active']);
        self::assertSame(0, $summary['inactive']);
        self::assertSame(0, $summary['content']);
        self::assertSame([], $summary['content_types']);
    }
}
