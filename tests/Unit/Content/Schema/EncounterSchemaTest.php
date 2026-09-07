<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Content\Schema;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use PHPUnit\Framework\TestCase;

final class EncounterSchemaTest extends TestCase
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
    private function encounterData(): array
    {
        return [
            'name' => 'Fixture Ambush',
            'kind' => 'combat',
            'setup' => 'Synthetic encounter used only by PHPUnit.',
            'participants' => [
                [
                    'ref' => 'first-almanac:monster:milk-carton-mimic',
                    'quantity' => 3,
                    'role' => 'ambusher',
                    'disposition' => 'hostile',
                    'placement' => ['zone' => 'cold-shelf'],
                    'rules' => [
                        ['kind' => 'modifier', 'target' => 'initiative', 'operation' => 'add', 'value' => 1],
                    ],
                ],
                [
                    'ref' => 'first-almanac:npc:fixture-shopkeeper',
                    'quantity' => 1,
                    'role' => 'bystander',
                    'disposition' => 'frightened',
                ],
            ],
            'waves' => [
                [
                    'key' => 'reinforcements',
                    'name' => 'Reinforcements',
                    'trigger' => 'At the end of round two.',
                    'participants' => [
                        ['ref' => 'first-almanac:monster:fixture-reinforcement', 'quantity' => 2],
                    ],
                ],
            ],
            'environment' => [
                'locations' => ['first-almanac:location:fixture-cold-aisle'],
                'terrain' => ['slippery-floor'],
                'hazards' => ['first-almanac:hazard:fixture-drip'],
                'lighting' => 'dim',
                'weather' => 'indoors',
                'terrain_notes' => 'A suspiciously reinforced butter display provides cover.',
                'rules' => [
                    ['kind' => 'effect', 'type' => 'difficult-terrain'],
                ],
            ],
            'objectives' => [
                [
                    'key' => 'protect-shopkeeper',
                    'name' => 'Protect the Shopkeeper',
                    'type' => 'defend',
                    'description' => 'Keep the shopkeeper safe.',
                    'success' => 'The shopkeeper survives.',
                    'failure' => 'The shopkeeper is incapacitated.',
                    'rules' => [
                        ['kind' => 'requirement', 'type' => 'survival'],
                    ],
                ],
            ],
            'difficulty' => [
                'rating' => 'moderate',
                'xp_budget' => 600,
                'party' => ['size' => 4, 'level' => 2],
            ],
            'rewards' => [
                'first-almanac:treasure:fixture-crate',
                ['type' => 'xp', 'quantity' => 100],
            ],
            'triggers' => [
                [
                    'key' => 'alarm',
                    'when' => 'The first mimic is revealed.',
                    'rules' => [
                        ['kind' => 'effect', 'type' => 'raise-alarm'],
                    ],
                ],
            ],
            'references' => ['first-almanac:adventure:fixture-delivery'],
            'keeper_notes' => ['The butter display is definitely normal. Probably.'],
        ];
    }

    public function test_minimal_named_encounter_remains_valid(): void
    {
        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('encounter', 'fixture-minimal', [
                'name' => 'Fixture Minimal Encounter',
            ]))->valid()
        );
    }

    public function test_complete_structured_encounter_is_valid(): void
    {
        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('encounter', 'fixture-ambush', $this->encounterData()))->valid()
        );
    }

    public function test_encounter_schema_exposes_expected_keeper_fields(): void
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        $schema = $schemas->get('encounter');

        self::assertNotNull($schema);
        foreach ([
            'kind', 'setup', 'participants', 'waves', 'environment', 'objectives',
            'difficulty', 'rewards', 'triggers', 'references', 'keeper_notes',
        ] as $field) {
            self::assertArrayHasKey($field, $schema->fields(), 'Expected encounter schema field: ' . $field);
        }
    }

    public function test_participants_require_canonical_reference(): void
    {
        $data = $this->encounterData();
        $data['participants'] = [['quantity' => 2]];
        $result = $this->validator()->validate(new ContentDefinition('encounter', 'bad-participant', $data));

        self::assertFalse($result->valid());
        self::assertSame('participants.0.ref', $result->errors()[0]->field());
    }

    public function test_participant_quantity_must_be_positive_integer(): void
    {
        $data = $this->encounterData();
        $data['participants'][0]['quantity'] = 0;
        $result = $this->validator()->validate(new ContentDefinition('encounter', 'bad-quantity', $data));

        self::assertFalse($result->valid());
        self::assertSame('participants.0.quantity', $result->errors()[0]->field());
    }

    public function test_participant_rules_are_validated_by_rules_engine(): void
    {
        $data = $this->encounterData();
        $data['participants'][0]['rules'] = [
            ['kind' => 'modifier', 'target' => 'initiative'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('encounter', 'bad-participant-rules', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['participants.0.rules.0.operation', 'participants.0.rules.0.value'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_waves_require_unique_keys_names_and_participants(): void
    {
        $data = $this->encounterData();
        $data['waves'] = [
            ['key' => 'wave', 'name' => 'First', 'participants' => [['ref' => 'first-almanac:monster:one']]],
            ['key' => 'wave', 'name' => 'Second', 'participants' => [['ref' => 'first-almanac:monster:two']]],
            ['key' => 'empty', 'name' => 'Empty', 'participants' => []],
        ];
        $result = $this->validator()->validate(new ContentDefinition('encounter', 'bad-waves', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['waves.1.key', 'waves.2.participants'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_environment_lists_require_non_empty_strings(): void
    {
        $data = $this->encounterData();
        $data['environment']['locations'] = ['first-almanac:location:fixture', ''];
        $data['environment']['terrain'] = [123];
        $result = $this->validator()->validate(new ContentDefinition('encounter', 'bad-environment-lists', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['environment.locations.1', 'environment.terrain.0'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_environment_rules_are_validated_by_rules_engine(): void
    {
        $data = $this->encounterData();
        $data['environment']['rules'] = [
            ['kind' => 'effect'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('encounter', 'bad-environment-rules', $data));

        self::assertFalse($result->valid());
        self::assertSame('environment.rules.0.type', $result->errors()[0]->field());
    }

    public function test_objectives_require_unique_keys_and_names(): void
    {
        $data = $this->encounterData();
        $data['objectives'] = [
            ['key' => 'survive', 'name' => 'Survive'],
            ['key' => 'survive', 'name' => 'Survive Again'],
            ['key' => 'missing-name'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('encounter', 'bad-objectives', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['objectives.1.key', 'objectives.2.name'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_difficulty_supports_open_rating_and_party_guidance(): void
    {
        $data = $this->encounterData();
        $data['difficulty'] = [
            'rating' => 'unexpectedly-saucy',
            'xp_budget' => 0,
            'party' => ['size' => 6, 'level' => 3],
        ];

        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('encounter', 'saucy-difficulty', $data))->valid()
        );
    }

    public function test_difficulty_rejects_negative_budget_and_invalid_party_guidance(): void
    {
        $data = $this->encounterData();
        $data['difficulty'] = [
            'xp_budget' => -1,
            'party' => ['size' => 0, 'level' => 'two'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('encounter', 'bad-difficulty', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['difficulty.xp_budget', 'difficulty.party.size', 'difficulty.party.level'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_rewards_accept_references_or_structured_reward_maps(): void
    {
        $data = $this->encounterData();
        $data['rewards'] = [
            'first-almanac:treasure:fixture-cache',
            ['type' => 'currency', 'quantity' => 3, 'currency' => 'fixture-coin'],
        ];

        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('encounter', 'reward-shapes', $data))->valid()
        );
    }

    public function test_rewards_reject_empty_references_and_invalid_structured_rewards(): void
    {
        $data = $this->encounterData();
        $data['rewards'] = ['', ['quantity' => 0]];
        $result = $this->validator()->validate(new ContentDefinition('encounter', 'bad-rewards', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['rewards.0', 'rewards.1.type', 'rewards.1.quantity'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_triggers_require_unique_key_and_when_text(): void
    {
        $data = $this->encounterData();
        $data['triggers'] = [
            ['key' => 'alarm', 'when' => 'Something happens.'],
            ['key' => 'alarm', 'when' => 'Something else happens.'],
            ['key' => 'missing-when'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('encounter', 'bad-triggers', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['triggers.1.key', 'triggers.2.when'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_references_and_keeper_notes_are_non_empty_string_lists(): void
    {
        $data = $this->encounterData();
        $data['references'] = [''];
        $data['keeper_notes'] = [123];
        $result = $this->validator()->validate(new ContentDefinition('encounter', 'bad-notes', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['references.0', 'keeper_notes.0'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_open_encounter_kinds_roles_and_dispositions_allow_marketrealm_oddities(): void
    {
        $data = $this->encounterData();
        $data['kind'] = 'negotiation-with-an-angry-pudding';
        $data['participants'][0]['role'] = 'custard-enforcer';
        $data['participants'][0]['disposition'] = 'professionally-offended';

        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('encounter', 'future-oddity', $data))->valid()
        );
    }
}
