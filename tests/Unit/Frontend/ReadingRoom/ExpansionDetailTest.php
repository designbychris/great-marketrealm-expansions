<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Frontend\ReadingRoom;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Expansions\ExpansionPack;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use GreatMarketrealmExpansions\Frontend\ReadingRoom\ExpansionDetail;
use GreatMarketrealmExpansions\Library\InMemoryActivationStore;
use GreatMarketrealmExpansions\Library\Library;
use PHPUnit\Framework\TestCase;

final class ExpansionDetailTest extends TestCase
{
    /** @return array{ExpansionDetail,Library} */
    private function fixture(): array
    {
        $packs = new ExpansionRegistry();
        $content = new ContentRegistry();
        $packs->add(new ExpansionPack('fixture-book', 'Fixture Book', '2.1.0', 'A synthetic book.'));
        $content->add('fixture-book', new ContentDefinition('monster', 'zebra-beast', ['name' => 'Zebra Beast']));
        $content->add('fixture-book', new ContentDefinition('feat', 'second-feat', ['name' => 'Second Feat']));
        $content->add('fixture-book', new ContentDefinition('feat', 'alpha-feat', ['name' => 'Alpha Feat']));
        $catalogue = new Catalogue($packs, $content);
        $library = new Library($catalogue, new InMemoryActivationStore());
        return [new ExpansionDetail($catalogue, $library, 'fixture-book'), $library];
    }

    public function test_known_expansion_exists(): void { [$detail] = $this->fixture(); self::assertTrue($detail->exists()); }
    public function test_missing_expansion_does_not_exist(): void
    {
        [$detail] = $this->fixture();
        $reflection = new \ReflectionClass($detail);
        $catalogue = $reflection->getProperty('catalogue')->getValue($detail);
        $library = $reflection->getProperty('library')->getValue($detail);
        self::assertFalse((new ExpansionDetail($catalogue, $library, 'missing'))->exists());
    }
    public function test_expansion_summary_reads_catalogue_and_library(): void
    {
        [$detail] = $this->fixture(); $value = $detail->expansion();
        self::assertSame('Fixture Book', $value['name']); self::assertSame('2.1.0', $value['version']); self::assertTrue($value['active']); self::assertSame(3, $value['entry_count']);
    }
    public function test_inactive_state_is_read_from_living_library(): void
    {
        [$detail, $library] = $this->fixture(); $library->setActive('fixture-book', false); self::assertFalse($detail->expansion()['active']);
    }
    public function test_families_are_sorted_by_canonical_type(): void
    {
        [$detail] = $this->fixture(); self::assertSame(['feat', 'monster'], array_keys($detail->families()));
    }
    public function test_entries_inside_family_are_sorted_by_name_then_key(): void
    {
        [$detail] = $this->fixture(); self::assertSame(['alpha-feat', 'second-feat'], array_map(fn($e) => $e->key(), $detail->family('feat')));
    }
    public function test_family_counts_are_deterministic(): void
    {
        [$detail] = $this->fixture(); self::assertSame(['feat' => 2, 'monster' => 1], $detail->familyCounts());
    }
    public function test_unknown_family_returns_empty_list(): void { [$detail] = $this->fixture(); self::assertSame([], $detail->family('spell')); }
}
