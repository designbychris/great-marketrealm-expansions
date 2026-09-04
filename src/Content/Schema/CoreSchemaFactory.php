<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\Schema\Constraints\ContentConstraint;
use GreatMarketrealmExpansions\Content\Schema\Constraints\RulesContentConstraint;

final class CoreSchemaFactory
{
    /** @return list<FieldDefinition> */
    private static function commonFields(): array
    {
        return [
            new FieldDefinition('name', FieldDefinition::STRING, true),
            new FieldDefinition('description', FieldDefinition::STRING, false),
            new FieldDefinition('provenance', FieldDefinition::MAP, false),
            new FieldDefinition('compatibility', FieldDefinition::MAP, false),
            new FieldDefinition('tags', FieldDefinition::ARRAY, false),
        ];
    }

    /** @param list<FieldDefinition> $extra @param list<ContentConstraint> $constraints */
    public static function make(string $type, array $extra = [], array $constraints = []): ContentSchema
    {
        $constraints[] = new RulesContentConstraint();
        return new ContentSchema($type, array_merge(self::commonFields(), $extra), $constraints);
    }
}
