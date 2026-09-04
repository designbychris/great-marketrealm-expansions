<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Content\Schema;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use PHPUnit\Framework\TestCase;

final class FeatSchemaTest extends TestCase
{
    private function validator(): ContentValidator
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        return new ContentValidator($schemas);
    }

    public function test_minimal_named_feat_remains_valid(): void
    {
        self::assertTrue(
            $this->validator()->validate(
                new ContentDefinition('feat', 'fixture-knack', ['name' => 'Fixture Knack'])
            )->valid()
        );
    }

    public function test_complete_structured_feat_is_valid(): void
    {
        $definition = new ContentDefinition('feat', 'fixture-gift', [
            'name' => 'Fixture Gift',
            'description' => 'Synthetic feat used only by PHPUnit.',
            'prerequisites' => [['type' => 'level', 'minimum' => 4]],
            'repeatable' => true,
            'max_selections' => 3,
            'grants' => [['type' => 'proficiency', 'value' => 'fixture-tool']],
            'choices' => [['key' => 'fixture-choice', 'count' => 1]],
            'modifiers' => [['target' => 'fixture-value', 'operation' => 'add', 'value' => 1]],
            'ability_score_rules' => [['choose' => 1, 'increase' => 1]],
        ]);

        self::assertTrue($this->validator()->validate($definition)->valid());
    }

    public function test_feat_schema_exposes_expected_domain_fields(): void
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        $schema = $schemas->get('feat');

        self::assertNotNull($schema);
        foreach (['prerequisites', 'repeatable', 'max_selections', 'grants', 'choices', 'modifiers', 'ability_score_rules'] as $field) {
            self::assertArrayHasKey($field, $schema->fields(), 'Expected feat schema field: ' . $field);
        }
    }

    public function test_prerequisites_must_be_structured_maps(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('feat', 'bad-prerequisites', [
            'name' => 'Bad Prerequisites',
            'prerequisites' => ['level-4'],
        ]));

        self::assertFalse($result->valid());
        self::assertSame('prerequisites.0', $result->errors()[0]->field());
    }

    public function test_grants_must_be_structured_maps(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('feat', 'bad-grants', [
            'name' => 'Bad Grants',
            'grants' => [[]],
        ]));

        self::assertFalse($result->valid());
        self::assertSame('grants.0', $result->errors()[0]->field());
    }

    public function test_choices_must_be_structured_maps(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('feat', 'bad-choices', [
            'name' => 'Bad Choices',
            'choices' => ['choose-a-thing'],
        ]));

        self::assertFalse($result->valid());
        self::assertSame('choices.0', $result->errors()[0]->field());
    }

    public function test_modifiers_must_be_structured_maps(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('feat', 'bad-modifiers', [
            'name' => 'Bad Modifiers',
            'modifiers' => [1],
        ]));

        self::assertFalse($result->valid());
        self::assertSame('modifiers.0', $result->errors()[0]->field());
    }

    public function test_ability_score_rules_must_be_structured_maps(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('feat', 'bad-ability-rules', [
            'name' => 'Bad Ability Rules',
            'ability_score_rules' => ['increase-strength'],
        ]));

        self::assertFalse($result->valid());
        self::assertSame('ability_score_rules.0', $result->errors()[0]->field());
    }

    public function test_max_selections_must_be_positive(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('feat', 'bad-maximum', [
            'name' => 'Bad Maximum',
            'repeatable' => true,
            'max_selections' => 0,
        ]));

        self::assertFalse($result->valid());
        self::assertSame('max_selections', $result->errors()[0]->field());
    }

    public function test_non_repeatable_feat_cannot_allow_multiple_selections(): void
    {
        $result = $this->validator()->validate(new ContentDefinition('feat', 'contradictory-repeatability', [
            'name' => 'Contradictory Repeatability',
            'repeatable' => false,
            'max_selections' => 2,
        ]));

        self::assertFalse($result->valid());
        self::assertSame('max_selections', $result->errors()[0]->field());
        self::assertStringContainsString('non-repeatable', $result->errors()[0]->message());
    }

    public function test_non_repeatable_feat_may_explicitly_limit_to_one_selection(): void
    {
        $definition = new ContentDefinition('feat', 'single-selection', [
            'name' => 'Single Selection',
            'repeatable' => false,
            'max_selections' => 1,
        ]);

        self::assertTrue($this->validator()->validate($definition)->valid());
    }
}
