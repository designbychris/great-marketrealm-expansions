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
    public function test_first_almanac_has_retired_from_the_bundled_shelf(): void
    {
        $bundledRoot = dirname(__DIR__, 4) . '/content/expansions';
        $firstAlmanac = $bundledRoot . '/first-almanac';

        self::assertDirectoryExists($bundledRoot);
        self::assertDirectoryDoesNotExist($firstAlmanac);

        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        $validator = new ContentValidator($schemas);
        $expansions = new ExpansionRegistry();
        $content = new ContentRegistry($validator);
        $loader = new ExpansionFileLoader($expansions, $content, $validator);

        self::assertSame([], $loader->loadAll($bundledRoot));
        self::assertFalse($expansions->has('first-almanac'));
    }
}
