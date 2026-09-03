<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Catalogue;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Catalogue\Rest\CatalogueRestApi;
use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Expansions\ExpansionPack;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use PHPUnit\Framework\TestCase;

final class CatalogueRestApiTest extends TestCase
{
    private function api(): CatalogueRestApi
    {
        $expansions = new ExpansionRegistry();
        $content = new ContentRegistry();
        $expansions->add(new ExpansionPack('first-almanac', 'The First Almanac', '0.1.0'));
        $content->add('first-almanac', new ContentDefinition('monster', 'milk-carton-mimic', [
            'name' => 'Milk Carton Mimic',
            'tags' => ['mimic', 'foundation-sample'],
        ]));
        $content->add('first-almanac', new ContentDefinition('feat', 'iron-stomach', [
            'name' => 'Iron Stomach',
            'tags' => ['foundation-sample'],
        ]));
        return new CatalogueRestApi(new Catalogue($expansions, $content));
    }

    public function test_index_exposes_version_capabilities_and_counts(): void
    {
        $index = $this->api()->index();
        self::assertSame('1.0.0', $index['api_version']);
        self::assertSame(1, $index['expansion_count']);
        self::assertSame(2, $index['content_count']);
        self::assertContains('catalogue.rest', $index['capabilities']);
    }

    public function test_expansions_are_serialized_for_rest_consumers(): void
    {
        $rows = $this->api()->expansions();
        self::assertCount(1, $rows);
        self::assertSame('The First Almanac', $rows[0]['name']);
    }

    public function test_content_endpoint_filters_without_exposing_registries(): void
    {
        $rows = $this->api()->content(['type' => 'monster', 'tag' => 'mimic']);
        self::assertCount(1, $rows);
        self::assertSame('first-almanac:monster:milk-carton-mimic', $rows[0]['id']);
    }

    public function test_entry_endpoint_returns_not_found_shape_outside_wordpress(): void
    {
        $result = $this->api()->entry('first-almanac', 'monster', 'missing');
        self::assertSame('gmrexp_not_found', $result['error']);
        self::assertSame(404, $result['status']);
    }
}
