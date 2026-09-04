<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Content\Schema;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use PHPUnit\Framework\TestCase;

final class PlayableRaceSchemaTest extends TestCase
{
    private function validator(): ContentValidator
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        return new ContentValidator($schemas);
    }

    /** @return array<string, mixed> */
    private function raceData(): array
    {
        return [
            'name' => 'Fixture Folk',
            'description' => 'Synthetic test people used only by PHPUnit.',
            'creature_type' => 'humanoid',
            'size' => ['value' => 'medium'],
            'speed' => ['walk' => 30],
            'languages' => ['common'],
            'traits' => [
                ['key' => 'fixture-resolve', 'name' => 'Fixture Resolve', 'description' => 'A test trait.'],
            ],
        ];
    }

    public function test_complete_playable_race_is_valid(): void
    {
        $data = $this->raceData();
        $data['ability_score_rules'] = [['mode' => 'fixed', 'ability' => 'constitution', 'amount' => 2]];
        $data['language_choices'] = [['count' => 1, 'from' => 'any']];
        $data['proficiencies'] = ['skills' => ['survival'], 'tools' => ['cook-utensils']];
        $data['resistances'] = ['cold'];
        $data['senses'] = ['darkvision' => 60];
        $data['choices'] = [['key' => 'fixture-choice', 'count' => 1]];

        self::assertTrue($this->validator()->validate(new ContentDefinition('race', 'fixture-folk', $data))->valid());
    }

    public function test_race_requires_core_playable_fields(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('race', 'incomplete', ['name' => 'Incomplete']));
        self::assertFalse($result->valid());
        self::assertSame(['creature_type', 'size', 'speed', 'languages', 'traits'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_size_can_be_a_choice_of_sizes(): void
    {
        $data = $this->raceData();
        $data['size'] = ['options' => ['small', 'medium']];
        self::assertTrue($this->validator()->validate(new ContentDefinition('race', 'choice-sized', $data))->valid());
    }

    public function test_size_requires_a_fixed_value_or_options(): void
    {
        $data = $this->raceData();
        $data['size'] = ['note' => 'mysterious'];
        $result = $this->validator()->validate(new ContentDefinition('race', 'sizeless', $data));
        self::assertFalse($result->valid());
        self::assertSame('size', $result->errors()[0]->field());
    }

    public function test_race_requires_positive_walking_speed(): void
    {
        $data = $this->raceData();
        $data['speed'] = ['walk' => 0];
        $result = $this->validator()->validate(new ContentDefinition('race', 'stationary', $data));
        self::assertFalse($result->valid());
        self::assertSame('speed.walk', $result->errors()[0]->field());
    }

    public function test_alternate_speed_and_hover_types_are_validated(): void
    {
        $data = $this->raceData();
        $data['speed'] = ['walk' => 30, 'fly' => -5, 'hover' => 'yes'];
        $result = $this->validator()->validate(new ContentDefinition('race', 'bad-flier', $data));
        self::assertFalse($result->valid());
        self::assertSame(['speed.fly', 'speed.hover'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_traits_have_canonical_key_and_name(): void
    {
        $data = $this->raceData();
        $data['traits'] = [['description' => 'Missing identity.']];
        $result = $this->validator()->validate(new ContentDefinition('race', 'traitless-identity', $data));
        self::assertFalse($result->valid());
        self::assertSame(['traits.0.key', 'traits.0.name'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_proficiencies_senses_and_choices_have_structured_shapes(): void
    {
        $data = $this->raceData();
        $data['proficiencies'] = ['skills' => 'survival'];
        $data['senses'] = ['darkvision' => 'sixty'];
        $data['choices'] = ['not-a-map'];
        $result = $this->validator()->validate(new ContentDefinition('race', 'bad-structure', $data));
        self::assertFalse($result->valid());
        self::assertSame(
            ['proficiencies.skills', 'senses.darkvision', 'choices.0'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_minimal_subrace_inherits_parent_and_adds_traits(): void
    {
        $definition = new ContentDefinition('subrace', 'fixture-branch', [
            'name' => 'Fixture Branch',
            'parent_race' => 'fixture-folk',
            'traits' => [['key' => 'branch-trait', 'name' => 'Branch Trait']],
        ]);
        self::assertTrue($this->validator()->validate($definition)->valid());
    }

    public function test_subrace_requires_parent_and_traits(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('subrace', 'orphan', ['name' => 'Orphan']));
        self::assertFalse($result->valid());
        self::assertSame(['parent_race', 'traits'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }
}
