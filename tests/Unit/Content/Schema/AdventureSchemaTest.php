<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Content\Schema;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use PHPUnit\Framework\TestCase;

final class AdventureSchemaTest extends TestCase
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
    private function adventureData(): array
    {
        return [
            'name' => 'Fixture Adventure Shelf',
            'kind' => 'adventure',
            'synopsis' => 'A synthetic adventure used only to prove structure.',
            'level_range' => [
                'minimum' => 1,
                'maximum' => 4,
                'notes' => 'Synthetic guidance only.',
            ],
            'entry_points' => [
                [
                    'key' => 'front-door',
                    'name' => 'Front Door',
                    'description' => 'Begin at the obvious entrance.',
                    'chapter' => 'chapter-one',
                    'scene' => 'first-room',
                    'references' => ['first-almanac:npc:fixture-guide'],
                ],
            ],
            'prerequisites' => [
                ['type' => 'campaign-ready'],
            ],
            'chapters' => [
                [
                    'key' => 'chapter-one',
                    'name' => 'Chapter One',
                    'order' => 1,
                    'summary' => 'The party discovers that shelving is not inherently trustworthy.',
                    'sections' => [
                        [
                            'key' => 'arrival',
                            'name' => 'Arrival',
                            'summary' => 'The adventure begins.',
                            'scenes' => [
                                [
                                    'key' => 'first-room',
                                    'name' => 'The First Room',
                                    'kind' => 'exploration',
                                    'description' => 'A room with several suspiciously ordinary objects.',
                                    'location' => 'fixture:location:first-room',
                                    'encounters' => ['first-almanac:encounter:fixture-ambush'],
                                    'npcs' => ['first-almanac:npc:fixture-guide'],
                                    'monsters' => ['first-almanac:monster:milk-carton-mimic'],
                                    'hazards' => ['first-almanac:hazard:fixture-floor'],
                                    'treasure' => ['first-almanac:treasure:fixture-strongbox'],
                                    'conditions' => ['first-almanac:condition:fixture-sticky'],
                                    'rule_refs' => ['first-almanac:rule:fixture-option'],
                                    'references' => ['first-almanac:equipment:fixture-map'],
                                    'rules' => [
                                        ['kind' => 'requirement', 'type' => 'entered-room'],
                                    ],
                                ],
                            ],
                            'references' => ['first-almanac:adventure:fixture-related'],
                            'rules' => [
                                ['kind' => 'effect', 'type' => 'fixture-section-effect'],
                            ],
                        ],
                    ],
                    'references' => ['first-almanac:npc:fixture-guide'],
                    'rules' => [
                        ['kind' => 'effect', 'type' => 'fixture-chapter-effect'],
                    ],
                ],
            ],
            'appendices' => [
                [
                    'key' => 'keeper-reference',
                    'name' => 'Keeper Reference',
                    'description' => 'A synthetic appendix.',
                    'references' => ['first-almanac:rule:fixture-option'],
                ],
            ],
            'progression' => [
                'mode' => 'branching',
                'start' => 'chapter-one:first-room',
                'end' => 'chapter-one:last-room',
                'branches' => [
                    [
                        'key' => 'continue',
                        'from' => 'chapter-one:first-room',
                        'to' => 'chapter-one:last-room',
                        'condition' => 'The party chooses to continue.',
                        'rules' => [
                            ['kind' => 'requirement', 'type' => 'party-continues'],
                        ],
                    ],
                ],
            ],
            'references' => ['first-almanac:rule:fixture-option'],
            'keeper_notes' => ['Pippin has requested that all bookshelves be inspected first.'],
        ];
    }

    public function test_minimal_named_adventure_remains_valid(): void
    {
        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('adventure', 'fixture-minimal', [
                'name' => 'Fixture Minimal Adventure',
            ]))->valid()
        );
    }

    public function test_complete_structured_adventure_is_valid(): void
    {
        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('adventure', 'fixture-adventure', $this->adventureData()))->valid()
        );
    }

    public function test_adventure_schema_exposes_expected_keeper_fields(): void
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        $schema = $schemas->get('adventure');

        self::assertNotNull($schema);
        foreach ([
            'kind', 'synopsis', 'level_range', 'entry_points', 'prerequisites',
            'chapters', 'appendices', 'progression', 'references', 'keeper_notes',
        ] as $field) {
            self::assertArrayHasKey($field, $schema->fields(), 'Expected adventure schema field: ' . $field);
        }
    }

    public function test_level_range_supports_optional_positive_bounds(): void
    {
        $data = $this->adventureData();
        $data['level_range'] = ['minimum' => 2, 'maximum' => 8];

        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('adventure', 'level-range', $data))->valid()
        );
    }

    public function test_level_range_rejects_invalid_bounds(): void
    {
        $data = $this->adventureData();
        $data['level_range'] = ['minimum' => 5, 'maximum' => 2];
        $result = $this->validator()->validate(new ContentDefinition('adventure', 'bad-level-range', $data));

        self::assertFalse($result->valid());
        self::assertSame('level_range.maximum', $result->errors()[0]->field());
    }

    public function test_entry_points_require_unique_keys_and_names(): void
    {
        $data = $this->adventureData();
        $data['entry_points'] = [
            ['key' => 'same', 'name' => 'First'],
            ['key' => 'same', 'name' => 'Second'],
            ['key' => 'missing-name'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('adventure', 'bad-entry-points', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['entry_points.1.key', 'entry_points.2.name'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_entry_point_references_are_validated(): void
    {
        $data = $this->adventureData();
        $data['entry_points'][0]['references'] = [''];
        $result = $this->validator()->validate(new ContentDefinition('adventure', 'bad-entry-ref', $data));

        self::assertFalse($result->valid());
        self::assertSame('entry_points.0.references.0', $result->errors()[0]->field());
    }

    public function test_adventure_prerequisites_are_validated_by_rules_engine(): void
    {
        $data = $this->adventureData();
        $data['prerequisites'] = [['notes' => 'Missing type']];
        $result = $this->validator()->validate(new ContentDefinition('adventure', 'bad-prerequisite', $data));

        self::assertFalse($result->valid());
        self::assertSame('prerequisites.0.type', $result->errors()[0]->field());
    }

    public function test_chapters_require_unique_keys_names_and_positive_unique_order(): void
    {
        $data = $this->adventureData();
        $data['chapters'] = [
            ['key' => 'same', 'name' => 'First', 'order' => 1],
            ['key' => 'same', 'name' => 'Second', 'order' => 1],
            ['key' => 'third', 'name' => 'Third', 'order' => 0],
        ];
        $result = $this->validator()->validate(new ContentDefinition('adventure', 'bad-chapters', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['chapters.1.key', 'chapters.1.order', 'chapters.2.order'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_chapter_sections_must_be_non_empty_list_when_supplied(): void
    {
        $data = $this->adventureData();
        $data['chapters'][0]['sections'] = [];
        $result = $this->validator()->validate(new ContentDefinition('adventure', 'bad-sections', $data));

        self::assertFalse($result->valid());
        self::assertSame('chapters.0.sections', $result->errors()[0]->field());
    }

    public function test_sections_require_unique_keys_and_names(): void
    {
        $data = $this->adventureData();
        $data['chapters'][0]['sections'] = [
            ['key' => 'same', 'name' => 'First'],
            ['key' => 'same', 'name' => 'Second'],
            ['key' => 'missing-name'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('adventure', 'bad-section-keys', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['chapters.0.sections.1.key', 'chapters.0.sections.2.name'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_section_scenes_must_be_non_empty_list_when_supplied(): void
    {
        $data = $this->adventureData();
        $data['chapters'][0]['sections'][0]['scenes'] = [];
        $result = $this->validator()->validate(new ContentDefinition('adventure', 'bad-scenes', $data));

        self::assertFalse($result->valid());
        self::assertSame('chapters.0.sections.0.scenes', $result->errors()[0]->field());
    }

    public function test_scenes_require_unique_keys_and_names(): void
    {
        $data = $this->adventureData();
        $data['chapters'][0]['sections'][0]['scenes'] = [
            ['key' => 'same', 'name' => 'First'],
            ['key' => 'same', 'name' => 'Second'],
            ['key' => 'missing-name'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('adventure', 'bad-scene-keys', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            [
                'chapters.0.sections.0.scenes.1.key',
                'chapters.0.sections.0.scenes.2.name',
            ],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_scene_reference_containers_require_non_empty_string_lists(): void
    {
        $data = $this->adventureData();
        $scene =& $data['chapters'][0]['sections'][0]['scenes'][0];
        $scene['encounters'] = [''];
        $scene['npcs'] = [];
        $scene['hazards'] = [123];
        $result = $this->validator()->validate(new ContentDefinition('adventure', 'bad-scene-refs', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            [
                'chapters.0.sections.0.scenes.0.encounters.0',
                'chapters.0.sections.0.scenes.0.npcs',
                'chapters.0.sections.0.scenes.0.hazards.0',
            ],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_scene_rules_are_validated_by_rules_engine(): void
    {
        $data = $this->adventureData();
        $data['chapters'][0]['sections'][0]['scenes'][0]['rules'] = [
            ['kind' => 'modifier', 'target' => 'speed'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('adventure', 'bad-scene-rules', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            [
                'chapters.0.sections.0.scenes.0.rules.0.operation',
                'chapters.0.sections.0.scenes.0.rules.0.value',
            ],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_section_and_chapter_rules_are_validated_by_rules_engine(): void
    {
        $data = $this->adventureData();
        $data['chapters'][0]['rules'] = [['kind' => 'effect']];
        $data['chapters'][0]['sections'][0]['rules'] = [['kind' => 'requirement']];
        $result = $this->validator()->validate(new ContentDefinition('adventure', 'bad-parent-rules', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            [
                'chapters.0.sections.0.rules.0.type',
                'chapters.0.rules.0.type',
            ],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_appendices_require_unique_keys_names_and_valid_references(): void
    {
        $data = $this->adventureData();
        $data['appendices'] = [
            ['key' => 'same', 'name' => 'First', 'references' => ['first-almanac:rule:fixture']],
            ['key' => 'same', 'name' => 'Second', 'references' => ['']],
        ];
        $result = $this->validator()->validate(new ContentDefinition('adventure', 'bad-appendices', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['appendices.1.key', 'appendices.1.references.0'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_progression_supports_open_mode_start_end_and_branches(): void
    {
        $data = $this->adventureData();
        $data['progression']['mode'] = 'pippin-draws-arrows-in-the-margin';

        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('adventure', 'open-progression', $data))->valid()
        );
    }

    public function test_progression_branches_require_key_from_to_and_condition(): void
    {
        $data = $this->adventureData();
        $data['progression']['branches'] = [
            ['key' => 'route', 'from' => 'a', 'to' => 'b'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('adventure', 'bad-branch', $data));

        self::assertFalse($result->valid());
        self::assertSame('progression.branches.0.condition', $result->errors()[0]->field());
    }

    public function test_progression_branch_keys_must_be_unique(): void
    {
        $data = $this->adventureData();
        $data['progression']['branches'] = [
            ['key' => 'same', 'from' => 'a', 'to' => 'b', 'condition' => 'First'],
            ['key' => 'same', 'from' => 'b', 'to' => 'c', 'condition' => 'Second'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('adventure', 'bad-branch-keys', $data));

        self::assertFalse($result->valid());
        self::assertSame('progression.branches.1.key', $result->errors()[0]->field());
    }

    public function test_progression_branch_rules_are_validated_by_rules_engine(): void
    {
        $data = $this->adventureData();
        $data['progression']['branches'][0]['rules'] = [
            ['kind' => 'effect'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('adventure', 'bad-branch-rules', $data));

        self::assertFalse($result->valid());
        self::assertSame('progression.branches.0.rules.0.type', $result->errors()[0]->field());
    }

    public function test_top_level_references_and_keeper_notes_are_validated(): void
    {
        $data = $this->adventureData();
        $data['references'] = [''];
        $data['keeper_notes'] = [123];
        $result = $this->validator()->validate(new ContentDefinition('adventure', 'bad-top-refs', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['references.0', 'keeper_notes.0'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_open_adventure_scene_and_progression_vocabularies_allow_marketrealm_oddities(): void
    {
        $data = $this->adventureData();
        $data['kind'] = 'sourcebook-with-too-many-bookmarks';
        $data['chapters'][0]['sections'][0]['scenes'][0]['kind'] = 'bookshelf-negotiation';
        $data['progression']['mode'] = 'follow-pippins-map-until-it-stops-making-sense';

        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('adventure', 'future-oddity', $data))->valid()
        );
    }
}
