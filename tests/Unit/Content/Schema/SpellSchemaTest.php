<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Content\Schema;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use PHPUnit\Framework\TestCase;

final class SpellSchemaTest extends TestCase
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
    private function spellData(): array
    {
        return [
            'name' => 'Fixture Spell',
            'description' => 'Synthetic spell used only by PHPUnit.',
            'level' => 2,
            'school' => 'fixture-mancy',
            'casting_time' => ['unit' => 'action', 'value' => 1],
            'range' => ['unit' => 'feet', 'value' => 60],
            'components' => ['verbal' => true, 'somatic' => true, 'material' => 'A tiny ceramic fixture.'],
            'duration' => ['type' => 'instantaneous'],
        ];
    }

    public function test_complete_spell_definition_is_valid(): void
    {
        $data = $this->spellData();
        $data['ritual'] = false;
        $data['concentration'] = true;
        $data['spell_lists'] = ['fixture-calling'];
        $data['targeting'] = ['shape' => 'sphere', 'radius' => 10];
        $data['saving_throw'] = ['ability' => 'dexterity', 'on_success' => 'half'];
        $data['effects'] = [['type' => 'damage', 'dice' => '2d6', 'damage_type' => 'fixture']];
        $data['scaling'] = [['type' => 'slot-level', 'per_level' => ['dice' => '1d6']]];
        self::assertTrue($this->validator()->validate(new ContentDefinition('spell', 'fixture-spell', $data))->valid());
    }

    public function test_spell_requires_core_spellbook_fields(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('spell', 'incomplete', ['name' => 'Incomplete']));
        self::assertFalse($result->valid());
        self::assertSame(['level', 'school', 'casting_time', 'range', 'components', 'duration'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_spell_level_accepts_cantrips_through_ninth_level(): void
    {
        foreach ([0, 9] as $level) {
            $data = $this->spellData(); $data['level'] = $level;
            self::assertTrue($this->validator()->validate(new ContentDefinition('spell', 'level-' . $level, $data))->valid());
        }
    }

    public function test_spell_level_rejects_values_outside_supported_range(): void
    {
        foreach ([-1, 10] as $level) {
            $data = $this->spellData(); $data['level'] = $level;
            $result = $this->validator()->validate(new ContentDefinition('spell', 'bad-level-' . $level, $data));
            self::assertFalse($result->valid());
            self::assertSame('level', $result->errors()[0]->field());
        }
    }

    public function test_school_is_open_to_custom_canonical_keys(): void
    {
        $data = $this->spellData();
        $data['school'] = 'saucemancy';
        self::assertTrue($this->validator()->validate(new ContentDefinition('spell', 'future-sauce-fixture', $data))->valid());
    }

    public function test_core_spell_maps_cannot_be_empty(): void
    {
        $data = $this->spellData();
        $data['casting_time'] = [];
        $data['range'] = [];
        $data['duration'] = [];
        $result = $this->validator()->validate(new ContentDefinition('spell', 'empty-maps', $data));
        self::assertFalse($result->valid());
        self::assertSame(['casting_time', 'range', 'duration'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_component_flags_are_boolean(): void
    {
        $data = $this->spellData();
        $data['components'] = ['verbal' => 'yes', 'somatic' => true];
        $result = $this->validator()->validate(new ContentDefinition('spell', 'bad-components', $data));
        self::assertFalse($result->valid());
        self::assertSame('components.verbal', $result->errors()[0]->field());
    }

    public function test_material_component_is_a_non_empty_description(): void
    {
        $data = $this->spellData();
        $data['components'] = ['material' => ''];
        $result = $this->validator()->validate(new ContentDefinition('spell', 'bad-material', $data));
        self::assertFalse($result->valid());
        self::assertSame('components.material', $result->errors()[0]->field());
    }

    public function test_components_require_at_least_one_active_component(): void
    {
        $data = $this->spellData();
        $data['components'] = ['verbal' => false, 'somatic' => false];
        $result = $this->validator()->validate(new ContentDefinition('spell', 'componentless', $data));
        self::assertFalse($result->valid());
        self::assertSame('components', $result->errors()[0]->field());
    }

    public function test_unknown_component_keys_are_rejected(): void
    {
        $data = $this->spellData();
        $data['components']['gravy'] = true;
        $result = $this->validator()->validate(new ContentDefinition('spell', 'mystery-component', $data));
        self::assertFalse($result->valid());
        self::assertSame('components.gravy', $result->errors()[0]->field());
    }

    public function test_spell_lists_are_canonical_string_keys(): void
    {
        $data = $this->spellData();
        $data['spell_lists'] = ['fixture-calling', ''];
        $result = $this->validator()->validate(new ContentDefinition('spell', 'bad-list', $data));
        self::assertFalse($result->valid());
        self::assertSame('spell_lists.1', $result->errors()[0]->field());
    }

    public function test_optional_target_attack_and_save_metadata_must_be_non_empty_maps(): void
    {
        $data = $this->spellData();
        $data['targeting'] = [];
        $data['attack'] = [];
        $data['saving_throw'] = [];
        $result = $this->validator()->validate(new ContentDefinition('spell', 'empty-mechanics', $data));
        self::assertFalse($result->valid());
        self::assertSame(['targeting', 'attack', 'saving_throw'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_effects_and_scaling_are_structured_map_lists(): void
    {
        $data = $this->spellData();
        $data['effects'] = ['damage'];
        $data['scaling'] = [[]];
        $result = $this->validator()->validate(new ContentDefinition('spell', 'bad-effects', $data));
        self::assertFalse($result->valid());
        self::assertSame(['effects.0', 'scaling.0'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_spell_schema_exposes_expected_domain_fields(): void
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry(); CoreSchemas::register($schemas, $types);
        $schema = $schemas->get('spell');
        self::assertNotNull($schema);
        foreach (['level','school','casting_time','range','components','duration','ritual','concentration','spell_lists','targeting','attack','saving_throw','effects','scaling'] as $field) {
            self::assertArrayHasKey($field, $schema->fields(), 'Expected spell schema field: ' . $field);
        }
    }
}
