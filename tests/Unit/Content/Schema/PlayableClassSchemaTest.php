<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Content\Schema;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use PHPUnit\Framework\TestCase;

final class PlayableClassSchemaTest extends TestCase
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
    private function classData(int $maxLevel = 3): array
    {
        $progression = [];
        for ($level = 1; $level <= $maxLevel; $level++) {
            $progression[] = [
                'level' => $level,
                'features' => $level === 1 ? ['fixture-feature'] : [],
            ];
        }

        return [
            'name' => 'Fixture Calling',
            'description' => 'Synthetic class used only by PHPUnit.',
            'hit_die' => 8,
            'max_level' => $maxLevel,
            'saving_throw_proficiencies' => ['wisdom', 'charisma'],
            'proficiencies' => ['armour' => ['light'], 'weapons' => ['simple']],
            'features' => [
                ['key' => 'fixture-feature', 'name' => 'Fixture Feature', 'description' => 'A test feature.'],
            ],
            'progression' => $progression,
        ];
    }

    public function test_complete_class_definition_is_valid(): void
    {
        $data = $this->classData();
        $data['primary_abilities'] = ['wisdom'];
        $data['starting_equipment'] = [['choose' => 1, 'from' => ['fixture-pack']]];
        $data['resources'] = [['key' => 'fixture-points', 'name' => 'Fixture Points', 'recharge' => 'long-rest']];
        $data['spellcasting'] = ['ability' => 'wisdom', 'progression' => 'full', 'prepares_spells' => true, 'spell_lists' => ['fixture-calling']];
        $data['subclass_selection'] = ['level' => 3, 'label' => 'Fixture Tradition'];
        $data['choices'] = [['key' => 'fixture-choice', 'count' => 1]];
        self::assertTrue($this->validator()->validate(new ContentDefinition('class', 'fixture-calling', $data))->valid());
    }

    public function test_class_requires_core_calling_fields(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('class', 'incomplete', ['name' => 'Incomplete']));
        self::assertFalse($result->valid());
        self::assertSame(
            ['hit_die', 'max_level', 'saving_throw_proficiencies', 'proficiencies', 'features', 'progression'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_hit_die_and_max_level_are_bounded(): void
    {
        $data = $this->classData();
        $data['hit_die'] = 7;
        $data['max_level'] = 31;
        $result = $this->validator()->validate(new ContentDefinition('class', 'bad-bounds', $data));
        self::assertFalse($result->valid());
        self::assertSame(['hit_die', 'max_level'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_features_require_unique_keys_and_names(): void
    {
        $data = $this->classData();
        $data['features'] = [
            ['key' => 'same', 'name' => 'First'],
            ['key' => 'same', 'name' => 'Second'],
            ['description' => 'Missing identity'],
        ];
        $data['progression'][0]['features'] = ['same'];
        $result = $this->validator()->validate(new ContentDefinition('class', 'duplicate-feature', $data));
        self::assertFalse($result->valid());
        self::assertSame(
            ['features.1.key', 'features.2.key', 'features.2.name'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_progression_references_declared_features(): void
    {
        $data = $this->classData();
        $data['progression'][1]['features'] = ['unknown-feature'];
        $result = $this->validator()->validate(new ContentDefinition('class', 'unknown-feature', $data));
        self::assertFalse($result->valid());
        self::assertSame('progression.1.features.0', $result->errors()[0]->field());
    }

    public function test_class_progression_must_cover_every_level_to_max_level(): void
    {
        $data = $this->classData(4);
        array_splice($data['progression'], 2, 1);
        $result = $this->validator()->validate(new ContentDefinition('class', 'missing-level', $data));
        self::assertFalse($result->valid());
        self::assertSame('progression', $result->errors()[0]->field());
        self::assertStringContainsString('level 3', $result->errors()[0]->message());
    }

    public function test_progression_levels_must_be_unique(): void
    {
        $data = $this->classData();
        $data['progression'][2]['level'] = 2;
        $result = $this->validator()->validate(new ContentDefinition('class', 'duplicate-level', $data));
        self::assertFalse($result->valid());
        self::assertSame('progression.2.level', $result->errors()[0]->field());
    }

    public function test_proficiency_and_starting_equipment_shapes_are_validated(): void
    {
        $data = $this->classData();
        $data['proficiencies'] = ['armour' => 'light'];
        $data['starting_equipment'] = ['not-a-map'];
        $result = $this->validator()->validate(new ContentDefinition('class', 'bad-structures', $data));
        self::assertFalse($result->valid());
        self::assertSame(['proficiencies.armour', 'starting_equipment.0'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_resources_and_spellcasting_have_structured_shapes(): void
    {
        $data = $this->classData();
        $data['resources'] = [['name' => 'Nameless Key'], ['key' => 'points', 'name' => 'Points', 'recharge' => '']];
        $data['spellcasting'] = ['ability' => '', 'prepares_spells' => 'yes', 'spell_lists' => 'fixture'];
        $result = $this->validator()->validate(new ContentDefinition('class', 'bad-mechanics', $data));
        self::assertFalse($result->valid());
        self::assertSame(
            ['resources.0.key', 'resources.1.recharge', 'spellcasting.ability', 'spellcasting.prepares_spells', 'spellcasting.spell_lists'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_subclass_requires_parent_entry_level_features_and_progression(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('subclass', 'incomplete-path', ['name' => 'Incomplete Path']));
        self::assertFalse($result->valid());
        self::assertSame(
            ['parent_class', 'entry_level', 'features', 'progression'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_valid_subclass_progression_can_start_at_entry_level(): void
    {
        $definition = new ContentDefinition('subclass', 'fixture-path', [
            'name' => 'Fixture Path',
            'parent_class' => 'fixture-calling',
            'entry_level' => 3,
            'features' => [['key' => 'path-feature', 'name' => 'Path Feature']],
            'progression' => [
                ['level' => 3, 'features' => ['path-feature']],
                ['level' => 6, 'features' => []],
            ],
        ]);
        self::assertTrue($this->validator()->validate($definition)->valid());
    }

    public function test_subclass_cannot_grant_features_before_entry_level(): void
    {
        $definition = new ContentDefinition('subclass', 'early-path', [
            'name' => 'Early Path',
            'parent_class' => 'fixture-calling',
            'entry_level' => 3,
            'features' => [['key' => 'early-feature', 'name' => 'Early Feature']],
            'progression' => [['level' => 2, 'features' => ['early-feature']]],
        ]);
        $result = $this->validator()->validate($definition);
        self::assertFalse($result->valid());
        self::assertSame('progression', $result->errors()[0]->field());
    }

    public function test_subclass_prerequisites_and_choices_are_maps(): void
    {
        $definition = new ContentDefinition('subclass', 'bad-path', [
            'name' => 'Bad Path',
            'parent_class' => 'fixture-calling',
            'entry_level' => 3,
            'features' => [['key' => 'path-feature', 'name' => 'Path Feature']],
            'progression' => [['level' => 3, 'features' => ['path-feature']]],
            'prerequisites' => ['bad'],
            'choices' => ['bad'],
        ]);
        $result = $this->validator()->validate($definition);
        self::assertFalse($result->valid());
        self::assertSame(['choices.0', 'prerequisites.0'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }
}
