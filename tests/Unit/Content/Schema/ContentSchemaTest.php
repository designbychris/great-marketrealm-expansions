<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Content\Schema;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ContentSchema;
use GreatMarketrealmExpansions\Content\Schema\FieldDefinition;
use PHPUnit\Framework\TestCase;

final class ContentSchemaTest extends TestCase
{
    public function test_required_fields_are_enforced(): void
    {
        $schema = new ContentSchema('feat', [new FieldDefinition('name', FieldDefinition::STRING, true)]);
        $result = $schema->validate(new ContentDefinition('feat', 'iron-stomach'));
        self::assertFalse($result->valid());
        self::assertSame('name', $result->errors()[0]->field());
    }

    public function test_valid_definition_passes_schema(): void
    {
        $schema = new ContentSchema('feat', [new FieldDefinition('name', FieldDefinition::STRING, true)]);
        self::assertTrue($schema->validate(new ContentDefinition('feat', 'iron-stomach', ['name' => 'Iron Stomach']))->valid());
    }

    public function test_wrong_content_type_fails_schema(): void
    {
        $schema = new ContentSchema('feat');
        $result = $schema->validate(new ContentDefinition('spell', 'soup-bolt', ['name' => 'Soup Bolt']));
        self::assertFalse($result->valid());
        self::assertSame('type', $result->errors()[0]->field());
    }
}
