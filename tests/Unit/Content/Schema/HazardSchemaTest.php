<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Content\Schema;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use PHPUnit\Framework\TestCase;

final class HazardSchemaTest extends TestCase
{
    private function validator(): ContentValidator
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        return new ContentValidator($schemas);
    }

    /** @return array<string,mixed> */
    private function hazardData(): array
    {
        return [
            'name' => 'Fixture Freezer Leak',
            'kind' => 'environmental',
            'severity' => [
                'rating' => 'moderate',
                'minimum_level' => 1,
                'maximum_level' => 5,
            ],
            'trigger' => [
                'type' => 'enter-area',
                'description' => 'A creature steps onto the leaking floor.',
                'condition' => 'The floor has not been dried.',
                'rules' => [
                    ['kind' => 'requirement', 'type' => 'entered-area'],
                ],
            ],
            'detection' => [
                'check' => 'perception',
                'difficulty' => 12,
                'passive' => 12,
                'description' => 'Notice the suspicious sheen on the floor.',
                'success' => 'The hazard is noticed.',
                'failure' => 'The hazard remains unnoticed.',
            ],
            'avoidance' => [
                [
                    'key' => 'walk-around',
                    'name' => 'Walk Around It',
                    'description' => 'Use the dry edge of the aisle.',
                    'check' => 'acrobatics',
                    'difficulty' => 10,
                    'rules' => [
                        ['kind' => 'effect', 'type' => 'avoid-hazard'],
                    ],
                ],
            ],
            'disarm' => [
                'check' => 'survival',
                'difficulty' => 10,
                'description' => 'Dry the floor and stop the leak.',
            ],
            'area' => [
                'shape' => 'rectangle',
                'length' => 20,
                'width' => 10,
                'units' => 'feet',
            ],
            'duration' => [
                'type' => 'persistent',
                'until' => 'The leak is stopped.',
            ],
            'effects' => [
                [
                    'type' => 'fall-prone',
                    'save' => 'dexterity',
                    'difficulty' => 11,
                ],
            ],
            'consequences' => [
                [
                    'key' => 'crate-slide',
                    'name' => 'Crate Slide',
                    'description' => 'A nearby crate slides toward the creature.',
                    'rules' => [
                        ['kind' => 'effect', 'type' => 'forced-movement'],
                    ],
                ],
            ],
            'reset' => [
                'type' => 'continuous',
                'automatic' => true,
                'interval' => 'immediate',
                'description' => 'The leak keeps producing water.',
            ],
            'escalation' => [
                [
                    'key' => 'freezes-over',
                    'name' => 'Freezes Over',
                    'trigger' => 'After prolonged exposure to Frostreem air.',
                    'rules' => [
                        ['kind' => 'effect', 'type' => 'ice-terrain'],
                    ],
                ],
            ],
            'references' => [
                'first-almanac:encounter:fixture-ambush',
            ],
            'keeper_notes' => [
                'The wet-floor sign is technically present, but upside down.',
            ],
        ];
    }

    public function test_minimal_named_hazard_remains_valid(): void
    {
        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('hazard', 'fixture-minimal', [
                'name' => 'Fixture Minimal Hazard',
            ]))->valid()
        );
    }

    public function test_complete_structured_hazard_is_valid(): void
    {
        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('hazard', 'fixture-freezer-leak', $this->hazardData()))->valid()
        );
    }

    public function test_hazard_schema_exposes_expected_keeper_fields(): void
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        $schema = $schemas->get('hazard');

        self::assertNotNull($schema);
        foreach ([
            'kind', 'severity', 'trigger', 'detection', 'avoidance', 'disarm',
            'area', 'duration', 'effects', 'consequences', 'reset', 'escalation',
            'references', 'keeper_notes',
        ] as $field) {
            self::assertArrayHasKey($field, $schema->fields(), 'Expected hazard schema field: ' . $field);
        }
    }

    public function test_severity_supports_open_rating_and_level_range(): void
    {
        $data = $this->hazardData();
        $data['severity'] = [
            'rating' => 'mildly-concerning-but-sticky',
            'minimum_level' => 2,
            'maximum_level' => 7,
        ];

        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('hazard', 'open-severity', $data))->valid()
        );
    }

    public function test_severity_rejects_invalid_level_range(): void
    {
        $data = $this->hazardData();
        $data['severity'] = [
            'minimum_level' => 5,
            'maximum_level' => 2,
        ];
        $result = $this->validator()->validate(new ContentDefinition('hazard', 'bad-severity', $data));

        self::assertFalse($result->valid());
        self::assertSame('severity.maximum_level', $result->errors()[0]->field());
    }

    public function test_trigger_requires_type(): void
    {
        $data = $this->hazardData();
        $data['trigger'] = ['description' => 'Something unfortunate happens.'];
        $result = $this->validator()->validate(new ContentDefinition('hazard', 'bad-trigger', $data));

        self::assertFalse($result->valid());
        self::assertSame('trigger.type', $result->errors()[0]->field());
    }

    public function test_trigger_rules_are_validated_by_rules_engine(): void
    {
        $data = $this->hazardData();
        $data['trigger']['rules'] = [
            ['kind' => 'modifier', 'target' => 'speed'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('hazard', 'bad-trigger-rules', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['trigger.rules.0.operation', 'trigger.rules.0.value'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_detection_supports_numeric_or_open_difficulty(): void
    {
        $data = $this->hazardData();
        $data['detection']['difficulty'] = 'keeper-decides';

        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('hazard', 'open-detection', $data))->valid()
        );
    }

    public function test_detection_rejects_invalid_passive_threshold(): void
    {
        $data = $this->hazardData();
        $data['detection']['passive'] = -1;
        $result = $this->validator()->validate(new ContentDefinition('hazard', 'bad-passive', $data));

        self::assertFalse($result->valid());
        self::assertSame('detection.passive', $result->errors()[0]->field());
    }

    public function test_avoidance_methods_require_unique_keys_and_names(): void
    {
        $data = $this->hazardData();
        $data['avoidance'] = [
            ['key' => 'jump', 'name' => 'Jump'],
            ['key' => 'jump', 'name' => 'Jump Again'],
            ['key' => 'missing-name'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('hazard', 'bad-avoidance', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['avoidance.1.key', 'avoidance.2.name'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_disarm_check_rules_are_validated_by_rules_engine(): void
    {
        $data = $this->hazardData();
        $data['disarm']['rules'] = [
            ['kind' => 'effect'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('hazard', 'bad-disarm-rules', $data));

        self::assertFalse($result->valid());
        self::assertSame('disarm.rules.0.type', $result->errors()[0]->field());
    }

    public function test_area_distances_must_be_non_negative_integers(): void
    {
        $data = $this->hazardData();
        $data['area'] = [
            'shape' => 'cone',
            'length' => -5,
            'width' => 'wide',
        ];
        $result = $this->validator()->validate(new ContentDefinition('hazard', 'bad-area', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['area.length', 'area.width'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_duration_requires_type_and_positive_round_count(): void
    {
        $data = $this->hazardData();
        $data['duration'] = ['rounds' => 0];
        $result = $this->validator()->validate(new ContentDefinition('hazard', 'bad-duration', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['duration.type', 'duration.rounds'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_top_level_effects_are_validated_by_rules_engine(): void
    {
        $data = $this->hazardData();
        $data['effects'] = [
            ['damage' => '1d6'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('hazard', 'bad-effect', $data));

        self::assertFalse($result->valid());
        self::assertSame('effects.0.type', $result->errors()[0]->field());
    }

    public function test_consequences_and_escalation_require_unique_keys_and_names(): void
    {
        $data = $this->hazardData();
        $data['consequences'] = [
            ['key' => 'same', 'name' => 'First'],
            ['key' => 'same', 'name' => 'Second'],
        ];
        $data['escalation'] = [
            ['key' => 'missing-name'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('hazard', 'bad-stages', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['consequences.1.key', 'escalation.0.name'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_reset_requires_type_and_boolean_automatic_flag(): void
    {
        $data = $this->hazardData();
        $data['reset'] = [
            'automatic' => 'yes',
        ];
        $result = $this->validator()->validate(new ContentDefinition('hazard', 'bad-reset', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['reset.type', 'reset.automatic'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_references_and_keeper_notes_are_non_empty_string_lists(): void
    {
        $data = $this->hazardData();
        $data['references'] = [''];
        $data['keeper_notes'] = [123];
        $result = $this->validator()->validate(new ContentDefinition('hazard', 'bad-notes', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['references.0', 'keeper_notes.0'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_open_hazard_kinds_trigger_types_and_area_shapes_allow_marketrealm_oddities(): void
    {
        $data = $this->hazardData();
        $data['kind'] = 'architectural-betrayal';
        $data['trigger']['type'] = 'pippin-measures-the-wrong-wall';
        $data['area']['shape'] = 'suspiciously-bookshelf-shaped';

        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('hazard', 'future-oddity', $data))->valid()
        );
    }
}
