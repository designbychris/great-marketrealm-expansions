<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Content\Schema;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use PHPUnit\Framework\TestCase;

final class RulesContentConstraintTest extends TestCase
{
    private function validator(): ContentValidator
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        return new ContentValidator($schemas);
    }

    public function test_feat_rule_containers_share_the_rules_engine_vocabulary(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('feat', 'bad-rules', [
            'name' => 'Bad Rules',
            'grants' => [['value' => 'survival']],
            'choices' => [['count' => 1]],
            'modifiers' => [['target' => 'armour-class', 'operation' => 'add']],
            'prerequisites' => [['minimum' => 4]],
        ]));

        self::assertFalse($result->valid());
        self::assertSame([
            'grants.0.type',
            'choices.0.key',
            'modifiers.0.value',
            'prerequisites.0.type',
        ], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_spell_effects_use_the_same_effect_vocabulary(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('spell', 'bad-effect', [
            'name' => 'Bad Effect',
            'level' => 1,
            'school' => 'fixture',
            'casting_time' => ['unit' => 'action'],
            'range' => ['unit' => 'self'],
            'components' => ['verbal' => true],
            'duration' => ['type' => 'instantaneous'],
            'effects' => [['dice' => '1d6']],
        ]));

        self::assertFalse($result->valid());
        self::assertSame('effects.0.type', $result->errors()[0]->field());
    }

    public function test_nested_feature_rules_accept_generic_statements(): void
    {
        $definition = new ContentDefinition('background', 'rule-background', [
            'name' => 'Rule Background',
            'proficiencies' => ['skills' => ['survival']],
            'starting_equipment' => [['item' => 'fixture-kit']],
            'features' => [[
                'key' => 'fixture-feature',
                'name' => 'Fixture Feature',
                'rules' => [
                    ['kind' => 'grant', 'type' => 'proficiency', 'value' => 'nature'],
                    ['kind' => 'modifier', 'target' => 'walking-speed', 'operation' => 'add', 'value' => 5],
                ],
            ]],
        ]);

        self::assertTrue($this->validator()->validate($definition)->valid());
    }

    public function test_nested_feature_rules_report_precise_paths(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('background', 'bad-rule-background', [
            'name' => 'Bad Rule Background',
            'proficiencies' => ['skills' => ['survival']],
            'starting_equipment' => [['item' => 'fixture-kit']],
            'features' => [[
                'key' => 'fixture-feature',
                'name' => 'Fixture Feature',
                'rules' => [
                    ['kind' => 'modifier', 'target' => 'armour-class'],
                ],
            ]],
        ]));

        self::assertFalse($result->valid());
        self::assertSame([
            'features.0.rules.0.operation',
            'features.0.rules.0.value',
        ], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_nested_rules_reject_unknown_kinds(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('background', 'mystery-rule-background', [
            'name' => 'Mystery Rule Background',
            'proficiencies' => ['skills' => ['survival']],
            'starting_equipment' => [['item' => 'fixture-kit']],
            'features' => [[
                'key' => 'fixture-feature',
                'name' => 'Fixture Feature',
                'rules' => [['kind' => 'telepathy-with-cutlery', 'type' => 'spoon']],
            ]],
        ]));

        self::assertFalse($result->valid());
        self::assertSame('features.0.rules.0.kind', $result->errors()[0]->field());
    }
}
