<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Import;

use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use GreatMarketrealmExpansions\Import\ImportService;
use PHPUnit\Framework\TestCase;

final class StagedDefinitionSourceDataTest extends TestCase
{
    private function importer(): ImportService
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        return new ImportService(new ContentValidator($schemas));
    }

    public function test_unresolved_record_preserves_source_data_for_keeper_review(): void
    {
        $result = $this->importer()->stage([
            'source' => ['type' => 'google-doc', 'id' => 'fixture', 'title' => 'Fixture'],
            'records' => [[
                'id' => 'google-heading-1',
                'source' => ['heading' => 'Pizza Mimic', 'heading_level' => 2],
                'data' => ['name' => 'Pizza Mimic', 'description' => 'The pizza is the mimic.'],
                'review_required' => true,
            ]],
        ]);

        $staged = $result->definitions()[0];
        self::assertFalse($staged->valid());
        self::assertSame('Pizza Mimic', $staged->sourceData()['name']);
        self::assertSame('The pizza is the mimic.', $staged->sourceData()['description']);
        self::assertSame($staged->sourceData(), $staged->toArray()['source_data']);
    }
}
