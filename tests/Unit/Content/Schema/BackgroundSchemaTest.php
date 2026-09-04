<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Content\Schema;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use PHPUnit\Framework\TestCase;

final class BackgroundSchemaTest extends TestCase
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
    private function backgroundData(): array
    {
        return [
            'name' => 'Fixture Life',
            'description' => 'Synthetic background used only by PHPUnit.',
            'proficiencies' => [
                'skills' => ['insight', 'survival'],
                'tools' => ['fixture-tool'],
            ],
            'starting_equipment' => [
                ['item' => 'fixture-pack', 'quantity' => 1],
            ],
            'features' => [
                ['key' => 'fixture-feature', 'name' => 'Fixture Feature', 'description' => 'A synthetic feature.'],
            ],
        ];
    }

    public function test_complete_background_definition_is_valid(): void
    {
        $data = $this->backgroundData();
        $data['languages'] = ['common'];
        $data['language_choices'] = [['count' => 1, 'from' => ['any']]];
        $data['equipment_choices'] = [['choose' => 1, 'from' => ['fixture-a', 'fixture-b']]];
        $data['ability_score_rules'] = [['choose' => 2, 'increase' => 1]];
        $data['feats'] = ['fixture-feat'];
        $data['characteristics'] = [
            'personality_traits' => ['I alphabetise my imaginary pantry.'],
            'ideals' => ['Order.'],
            'bonds' => ['My fixture belongs in the test suite.'],
            'flaws' => ['I distrust unsorted arrays.'],
        ];
        $data['choices'] = [['key' => 'fixture-choice', 'count' => 1]];

        self::assertTrue($this->validator()->validate(new ContentDefinition('background', 'fixture-life', $data))->valid());
    }

    public function test_background_requires_core_life_fields(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('background', 'incomplete', ['name' => 'Incomplete']));
        self::assertFalse($result->valid());
        self::assertSame(
            ['proficiencies', 'starting_equipment', 'features'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_proficiency_groups_must_be_lists_of_canonical_keys(): void
    {
        $data = $this->backgroundData();
        $data['proficiencies'] = ['skills' => 'survival', 'tools' => ['']];
        $result = $this->validator()->validate(new ContentDefinition('background', 'bad-proficiencies', $data));
        self::assertFalse($result->valid());
        self::assertSame(['proficiencies.skills', 'proficiencies.tools.0'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_starting_equipment_entries_must_be_maps(): void
    {
        $data = $this->backgroundData();
        $data['starting_equipment'] = ['fixture-pack'];
        $result = $this->validator()->validate(new ContentDefinition('background', 'bad-equipment', $data));
        self::assertFalse($result->valid());
        self::assertSame('starting_equipment.0', $result->errors()[0]->field());
    }

    public function test_background_features_require_unique_keys_and_names(): void
    {
        $data = $this->backgroundData();
        $data['features'] = [
            ['key' => 'same', 'name' => 'First'],
            ['key' => 'same', 'name' => 'Second'],
            ['description' => 'Missing identity'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('background', 'bad-features', $data));
        self::assertFalse($result->valid());
        self::assertSame(
            ['features.1.key', 'features.2.key', 'features.2.name'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_feature_rules_must_be_lists(): void
    {
        $data = $this->backgroundData();
        $data['features'][0]['rules'] = ['kind' => 'grant'];
        $result = $this->validator()->validate(new ContentDefinition('background', 'bad-rules', $data));
        self::assertFalse($result->valid());
        self::assertSame('features.0.rules', $result->errors()[0]->field());
    }

    public function test_languages_and_feats_are_canonical_string_lists(): void
    {
        $data = $this->backgroundData();
        $data['languages'] = ['common', ''];
        $data['feats'] = [123];
        $result = $this->validator()->validate(new ContentDefinition('background', 'bad-lists', $data));
        self::assertFalse($result->valid());
        self::assertSame(['languages.1', 'feats.0'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_generation_choice_collections_require_non_empty_maps(): void
    {
        $data = $this->backgroundData();
        $data['language_choices'] = ['bad'];
        $data['equipment_choices'] = [[]];
        $data['ability_score_rules'] = ['bad'];
        $data['choices'] = ['bad'];
        $result = $this->validator()->validate(new ContentDefinition('background', 'bad-choices', $data));
        self::assertFalse($result->valid());
        self::assertSame(
            ['language_choices.0', 'equipment_choices.0', 'ability_score_rules.0', 'choices.0'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_characteristics_support_the_four_background_groups(): void
    {
        $data = $this->backgroundData();
        $data['characteristics'] = [
            'personality_traits' => ['Methodical.'],
            'ideals' => ['Curiosity.'],
            'bonds' => ['The test suite.'],
            'flaws' => ['Overly synthetic.'],
        ];
        self::assertTrue($this->validator()->validate(new ContentDefinition('background', 'characterful', $data))->valid());
    }

    public function test_characteristics_reject_unknown_groups_and_non_string_entries(): void
    {
        $data = $this->backgroundData();
        $data['characteristics'] = [
            'personality_traits' => [42],
            'catchphrases' => ['Not a canonical group.'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('background', 'bad-characteristics', $data));
        self::assertFalse($result->valid());
        self::assertSame(
            ['characteristics.personality_traits.0', 'characteristics.catchphrases'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_background_schema_exposes_expected_domain_fields(): void
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        $schema = $schemas->get('background');

        self::assertNotNull($schema);
        foreach (['proficiencies', 'starting_equipment', 'features', 'languages', 'language_choices', 'equipment_choices', 'ability_score_rules', 'feats', 'characteristics', 'choices'] as $field) {
            self::assertArrayHasKey($field, $schema->fields(), 'Expected background schema field: ' . $field);
        }
    }
}
