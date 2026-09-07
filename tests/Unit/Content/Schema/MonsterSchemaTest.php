<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Content\Schema;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use PHPUnit\Framework\TestCase;

final class MonsterSchemaTest extends TestCase
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
    private function monsterData(): array
    {
        return [
            'name' => 'Fixture Beast',
            'size' => 'medium',
            'creature_type' => 'fixture-creature',
            'alignment' => 'fixture-neutral',
            'armour_class' => ['value' => 14, 'type' => 'natural-armour'],
            'hit_points' => ['average' => 27, 'formula' => '5d8+5'],
            'speed' => ['walk' => 30, 'climb' => 20],
            'abilities' => [
                'strength' => 14,
                'dexterity' => 12,
                'constitution' => 13,
                'intelligence' => 6,
                'wisdom' => 10,
                'charisma' => 8,
            ],
            'saving_throws' => ['strength' => 4],
            'skills' => ['perception' => 2],
            'damage_resistances' => ['fixture-damage'],
            'condition_immunities' => ['fixture-condition'],
            'senses' => ['darkvision' => 60, 'passive_perception' => 12],
            'languages' => ['common'],
            'challenge' => ['rating' => 2, 'xp' => 450],
            'proficiency_bonus' => 2,
            'traits' => [[
                'key' => 'fixture-trait',
                'name' => 'Fixture Trait',
                'description' => 'Synthetic trait.',
                'rules' => [
                    ['kind' => 'grant', 'type' => 'resistance', 'value' => 'fixture-damage'],
                ],
            ]],
            'actions' => [[
                'key' => 'fixture-swipe',
                'name' => 'Fixture Swipe',
                'rules' => [
                    ['kind' => 'effect', 'type' => 'damage', 'dice' => '1d6'],
                ],
            ]],
        ];
    }

    public function test_foundation_sample_style_monster_remains_valid(): void
    {
        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('monster', 'milk-carton-mimic', [
                'name' => 'Milk Carton Mimic',
                'description' => 'Foundation sample.',
                'tags' => ['foundation-sample', 'mimic'],
            ]))->valid()
        );
    }

    public function test_complete_structured_monster_is_valid(): void
    {
        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('monster', 'fixture-beast', $this->monsterData()))->valid()
        );
    }

    public function test_monster_schema_exposes_expected_keeper_fields(): void
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        $schema = $schemas->get('monster');

        self::assertNotNull($schema);
        foreach ([
            'size', 'creature_type', 'alignment', 'armour_class', 'hit_points', 'speed',
            'abilities', 'saving_throws', 'skills', 'damage_vulnerabilities',
            'damage_resistances', 'damage_immunities', 'condition_immunities',
            'senses', 'languages', 'challenge', 'proficiency_bonus', 'traits',
            'actions', 'bonus_actions', 'reactions', 'legendary_actions',
            'lair_actions', 'spellcasting',
        ] as $field) {
            self::assertArrayHasKey($field, $schema->fields(), 'Expected monster schema field: ' . $field);
        }
    }

    public function test_armour_class_requires_non_negative_value(): void
    {
        $data = $this->monsterData();
        $data['armour_class'] = ['value' => -1];
        $result = $this->validator()->validate(new ContentDefinition('monster', 'bad-ac', $data));

        self::assertFalse($result->valid());
        self::assertSame('armour_class.value', $result->errors()[0]->field());
    }

    public function test_hit_points_require_positive_average(): void
    {
        $data = $this->monsterData();
        $data['hit_points'] = ['average' => 0];
        $result = $this->validator()->validate(new ContentDefinition('monster', 'bad-hp', $data));

        self::assertFalse($result->valid());
        self::assertSame('hit_points.average', $result->errors()[0]->field());
    }

    public function test_speed_distances_cannot_be_negative(): void
    {
        $data = $this->monsterData();
        $data['speed'] = ['walk' => -5];
        $result = $this->validator()->validate(new ContentDefinition('monster', 'bad-speed', $data));

        self::assertFalse($result->valid());
        self::assertSame('speed.walk', $result->errors()[0]->field());
    }

    public function test_hover_flag_must_be_boolean(): void
    {
        $data = $this->monsterData();
        $data['speed'] = ['fly' => 40, 'hover' => 'yes'];
        $result = $this->validator()->validate(new ContentDefinition('monster', 'bad-hover', $data));

        self::assertFalse($result->valid());
        self::assertSame('speed.hover', $result->errors()[0]->field());
    }

    public function test_abilities_require_all_six_scores_when_supplied(): void
    {
        $data = $this->monsterData();
        unset($data['abilities']['charisma']);
        $result = $this->validator()->validate(new ContentDefinition('monster', 'missing-charisma', $data));

        self::assertFalse($result->valid());
        self::assertSame('abilities.charisma', $result->errors()[0]->field());
    }

    public function test_saving_throws_and_skills_are_numeric_maps(): void
    {
        $data = $this->monsterData();
        $data['saving_throws'] = ['wisdom' => 'good'];
        $data['skills'] = ['stealth' => 'sneaky'];
        $result = $this->validator()->validate(new ContentDefinition('monster', 'bad-bonuses', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['saving_throws.wisdom', 'skills.stealth'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_damage_condition_and_language_lists_require_non_empty_strings(): void
    {
        $data = $this->monsterData();
        $data['damage_immunities'] = [''];
        $data['condition_immunities'] = [123];
        $data['languages'] = ['common', ''];
        $result = $this->validator()->validate(new ContentDefinition('monster', 'bad-lists', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['damage_immunities.0', 'condition_immunities.0', 'languages.1'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_challenge_supports_fractional_string_ratings_and_xp(): void
    {
        $data = $this->monsterData();
        $data['challenge'] = ['rating' => '1/2', 'xp' => 100];

        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('monster', 'fractional-challenge', $data))->valid()
        );
    }

    public function test_challenge_rejects_empty_rating_and_negative_xp(): void
    {
        $data = $this->monsterData();
        $data['challenge'] = ['rating' => '', 'xp' => -1];
        $result = $this->validator()->validate(new ContentDefinition('monster', 'bad-challenge', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['challenge.rating', 'challenge.xp'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_traits_and_actions_require_unique_keys_and_names(): void
    {
        $data = $this->monsterData();
        $data['traits'] = [
            ['key' => 'same', 'name' => 'First'],
            ['key' => 'same', 'name' => 'Second'],
            ['key' => 'missing-name'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('monster', 'bad-traits', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['traits.1.key', 'traits.2.name'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_monster_action_rules_are_validated_by_rules_engine(): void
    {
        $data = $this->monsterData();
        $data['actions'] = [[
            'key' => 'broken-action',
            'name' => 'Broken Action',
            'rules' => [
                ['kind' => 'modifier', 'target' => 'armour-class'],
            ],
        ]];
        $result = $this->validator()->validate(new ContentDefinition('monster', 'bad-action-rules', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['actions.0.rules.0.operation', 'actions.0.rules.0.value'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_custom_marketrealm_creature_types_are_open_vocabulary(): void
    {
        $data = $this->monsterData();
        $data['creature_type'] = 'sentient-condiment-aberration';
        $data['size'] = 'picnic-sized';
        $data['alignment'] = 'aggressively-peckish';

        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('monster', 'future-oddity', $data))->valid()
        );
    }
}
