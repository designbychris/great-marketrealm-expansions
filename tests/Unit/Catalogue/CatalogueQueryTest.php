<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Catalogue;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Expansions\ExpansionPack;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use PHPUnit\Framework\TestCase;

final class CatalogueQueryTest extends TestCase
{
    private function catalogue(): Catalogue
    {
        $expansions = new ExpansionRegistry();
        $content = new ContentRegistry();
        foreach (['one', 'two'] as $key) { $expansions->add(new ExpansionPack($key, ucfirst($key))); }
        $content->add('one', new ContentDefinition('monster', 'a', ['name' => 'A', 'tags' => ['mimic', 'cold']]));
        $content->add('one', new ContentDefinition('feat', 'b', ['name' => 'B', 'tags' => ['cold']]));
        $content->add('two', new ContentDefinition('monster', 'c', ['name' => 'C', 'tags' => ['mimic']]));
        return new Catalogue($expansions, $content);
    }

    public function test_query_filters_by_type_expansion_and_tag(): void
    {
        $result = $this->catalogue()->query()->type('monster')->from('one')->tag('mimic')->get();
        self::assertCount(1, $result);
        self::assertSame('one:monster:a', $result[0]->id());
    }

    public function test_multiple_tags_use_and_semantics(): void
    {
        $result = $this->catalogue()->query()->tag('mimic')->tag('cold')->get();
        self::assertCount(1, $result);
        self::assertSame('a', $result[0]->key());
    }

    public function test_types_and_from_any_accept_multiple_values(): void
    {
        $query = $this->catalogue()->query()->types(['monster', 'feat'])->fromAny(['one', 'two']);
        self::assertSame(3, $query->count());
    }

    public function test_key_first_and_count_helpers(): void
    {
        $query = $this->catalogue()->query()->key('c');
        self::assertSame(1, $query->count());
        self::assertSame('C', $query->first()?->name());
    }

    public function test_query_builder_is_immutable(): void
    {
        $base = $this->catalogue()->query()->type('monster');
        $one = $base->from('one');
        self::assertCount(2, $base->get());
        self::assertCount(1, $one->get());
    }
}
