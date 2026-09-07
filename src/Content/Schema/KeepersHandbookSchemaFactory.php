<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\Schema\Constraints\KeeperRuleStructureConstraint;
use GreatMarketrealmExpansions\Content\Schema\Constraints\ConditionStructureConstraint;

final class KeepersHandbookSchemaFactory
{
    public static function ruleDefinition(): ContentSchema
    {
        return CoreSchemaFactory::make('rule', [
            new FieldDefinition('kind', FieldDefinition::STRING, false),
            new FieldDefinition('scope', FieldDefinition::MAP, false),
            new FieldDefinition('activation', FieldDefinition::MAP, false),
            new FieldDefinition('priority', FieldDefinition::INTEGER, false),
            new FieldDefinition('prerequisites', FieldDefinition::ARRAY, false),
            new FieldDefinition('rules', FieldDefinition::ARRAY, false),
            new FieldDefinition('conflicts', FieldDefinition::ARRAY, false),
            new FieldDefinition('supersedes', FieldDefinition::ARRAY, false),
            new FieldDefinition('references', FieldDefinition::ARRAY, false),
            new FieldDefinition('keeper_notes', FieldDefinition::ARRAY, false),
        ], [new KeeperRuleStructureConstraint()]);
    }

    public static function condition(): ContentSchema
    {
        return CoreSchemaFactory::make('condition', [
            new FieldDefinition('kind', FieldDefinition::STRING, false),
            new FieldDefinition('application', FieldDefinition::MAP, false),
            new FieldDefinition('duration', FieldDefinition::MAP, false),
            new FieldDefinition('stacking', FieldDefinition::MAP, false),
            new FieldDefinition('effects', FieldDefinition::ARRAY, false),
            new FieldDefinition('rules', FieldDefinition::ARRAY, false),
            new FieldDefinition('removal', FieldDefinition::ARRAY, false),
            new FieldDefinition('stages', FieldDefinition::ARRAY, false),
            new FieldDefinition('references', FieldDefinition::ARRAY, false),
            new FieldDefinition('keeper_notes', FieldDefinition::ARRAY, false),
        ], [new ConditionStructureConstraint()]);
    }
}
