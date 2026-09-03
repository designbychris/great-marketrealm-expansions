<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Content\Schema;

use GreatMarketrealmExpansions\Content\Schema\FieldDefinition;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FieldDefinitionTest extends TestCase
{
    public function test_supported_field_types_validate_values(): void
    {
        self::assertTrue((new FieldDefinition('name', FieldDefinition::STRING))->accepts('Auby'));
        self::assertTrue((new FieldDefinition('level', FieldDefinition::INTEGER))->accepts(3));
        self::assertTrue((new FieldDefinition('enabled', FieldDefinition::BOOLEAN))->accepts(false));
        self::assertTrue((new FieldDefinition('tags', FieldDefinition::ARRAY))->accepts(['cold', 'magic']));
        self::assertTrue((new FieldDefinition('meta', FieldDefinition::MAP))->accepts(['source' => 'book']));
        self::assertFalse((new FieldDefinition('name', FieldDefinition::STRING))->accepts(''));
    }

    public function test_invalid_field_type_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FieldDefinition('thing', 'mystery');
    }
}
