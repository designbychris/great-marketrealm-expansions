<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Content\Schema;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ContentValidationException;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use PHPUnit\Framework\TestCase;

final class CoreSchemasTest extends TestCase
{
    private function validator(): ContentValidator
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        return new ContentValidator($schemas);
    }

    public function test_every_core_type_receives_a_schema(): void
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        self::assertCount(count($types->all()), $schemas->all());
    }

    public function test_every_core_definition_requires_a_name(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('monster', 'milk-mimic'));
        self::assertFalse($result->valid());
        self::assertSame('name', $result->errors()[0]->field());
    }

    public function test_subclass_requires_parent_class(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('subclass', 'circle-freezer', ['name' => 'Circle of the Freezer']));
        self::assertFalse($result->valid());
        self::assertSame('parent_class', $result->errors()[0]->field());
    }

    public function test_subrace_requires_parent_race(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('subrace', 'red-apple', ['name' => 'Red Apple']));
        self::assertFalse($result->valid());
        self::assertSame('parent_race', $result->errors()[0]->field());
    }

    public function test_provenance_and_compatibility_are_validated_as_maps(): void
    {
        $valid = new ContentDefinition('feat', 'iron-stomach', [
            'name' => 'Iron Stomach',
            'provenance' => ['source' => 'Frozen Aisles', 'page' => 12],
            'compatibility' => ['ruleset' => 'great-marketrealm'],
        ]);
        self::assertTrue($this->validator()->validate($valid)->valid());

        $invalid = new ContentDefinition('feat', 'paper-stomach', ['name' => 'Paper Stomach', 'provenance' => ['not-a-map']]);
        self::assertFalse($this->validator()->validate($invalid)->valid());
    }

    public function test_unknown_content_type_is_rejected(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('mystery-jar', 'x', ['name' => 'X']));
        self::assertFalse($result->valid());
        self::assertSame('type', $result->errors()[0]->field());
    }

    public function test_assert_valid_throws_rich_validation_exception(): void
    {
        $this->expectException(ContentValidationException::class);
        $this->validator()->assertValid(new ContentDefinition('feat', 'nameless'));
    }
}
