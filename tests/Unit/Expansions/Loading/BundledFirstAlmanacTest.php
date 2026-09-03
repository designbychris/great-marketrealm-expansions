<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Expansions\Loading;

use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use GreatMarketrealmExpansions\Expansions\Loading\ExpansionFileLoader;
use PHPUnit\Framework\TestCase;

final class BundledFirstAlmanacTest extends TestCase
{
    public function test_bundled_first_almanac_loads_end_to_end(): void
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        $validator = new ContentValidator($schemas);
        $expansions = new ExpansionRegistry();
        $content = new ContentRegistry($validator);
        $loader = new ExpansionFileLoader($expansions, $content, $validator);

        $result = $loader->load(dirname(__DIR__, 4) . '/content/expansions/first-almanac');

        self::assertSame('first-almanac', $result->pack()->key());
        self::assertSame(2, $result->total());
        self::assertSame('Iron Stomach', $content->get('first-almanac', 'feat', 'iron-stomach')?->value('name'));
        self::assertSame('Milk Carton Mimic', $content->get('first-almanac', 'monster', 'milk-carton-mimic')?->value('name'));
    }
}
