<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\Schema\Constraints\HazardStructureConstraint;

final class HazardSchemaFactory
{
    public static function hazard(): ContentSchema
    {
        return CoreSchemaFactory::make('hazard', [
            new FieldDefinition('kind', FieldDefinition::STRING, false),
            new FieldDefinition('severity', FieldDefinition::MAP, false),
            new FieldDefinition('trigger', FieldDefinition::MAP, false),
            new FieldDefinition('detection', FieldDefinition::MAP, false),
            new FieldDefinition('avoidance', FieldDefinition::ARRAY, false),
            new FieldDefinition('disarm', FieldDefinition::MAP, false),
            new FieldDefinition('area', FieldDefinition::MAP, false),
            new FieldDefinition('duration', FieldDefinition::MAP, false),
            new FieldDefinition('effects', FieldDefinition::ARRAY, false),
            new FieldDefinition('consequences', FieldDefinition::ARRAY, false),
            new FieldDefinition('reset', FieldDefinition::MAP, false),
            new FieldDefinition('escalation', FieldDefinition::ARRAY, false),
            new FieldDefinition('references', FieldDefinition::ARRAY, false),
            new FieldDefinition('keeper_notes', FieldDefinition::ARRAY, false),
        ], [new HazardStructureConstraint()]);
    }
}
