<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Content;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Content\Schema\ContentValidationException;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use PHPUnit\Framework\TestCase;

final class ValidatedContentRegistryTest extends TestCase
{
    private function registry(): ContentRegistry
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        return new ContentRegistry(new ContentValidator($schemas));
    }

    public function test_valid_content_is_accepted(): void
    {
        $definition = new ContentDefinition('subclass', 'circle-freezer', [
            'name' => 'Circle of the Freezer',
            'parent_class' => 'druid',
            'entry_level' => 2,
            'features' => [
                [
                    'key' => 'frozen-shape',
                    'name' => 'Frozen Shape',
                ],
            ],
            'progression' => [
                [
                    'level' => 2,
                    'features' => ['frozen-shape'],
                ],
            ],
        ]);

        $registry = $this->registry();
        $registry->add('frozen-aisles', $definition);

        self::assertSame(
            $definition,
            $registry->get('frozen-aisles', 'subclass', 'circle-freezer')
        );
    }

    public function test_invalid_content_is_rejected_before_registration(): void
    {
        $this->expectException(ContentValidationException::class);
        $this->registry()->add(
            'frozen-aisles',
            new ContentDefinition('subclass', 'circle-freezer', [
                'name' => 'Circle of the Freezer',
            ])
        );
    }
}
