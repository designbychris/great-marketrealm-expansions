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
    public function test_first_almanac_has_retired_even_if_a_stale_folder_survives_deployment(): void
    {
        $root = sys_get_temp_dir() . '/gmrexp-retired-first-almanac-' . uniqid('', true);
        $firstAlmanac = $root . '/first-almanac';
        mkdir($firstAlmanac, 0777, true);
        file_put_contents($firstAlmanac . '/manifest.php', "<?php return ['key' => 'first-almanac', 'name' => 'The First Almanac', 'version' => '0.1.0'];");

        self::assertDirectoryExists($firstAlmanac);

        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        $validator = new ContentValidator($schemas);
        $expansions = new ExpansionRegistry();
        $content = new ContentRegistry($validator);
        $loader = new ExpansionFileLoader($expansions, $content, $validator);

        try {
            self::assertSame([], $loader->loadAll($root));
            self::assertFalse($expansions->has('first-almanac'));
            self::assertSame([], $content->forExpansion('first-almanac'));
        } finally {
            @unlink($firstAlmanac . '/manifest.php');
            @rmdir($firstAlmanac);
            @rmdir($root);
        }
    }
}
