<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Content\Schema;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use PHPUnit\Framework\TestCase;

final class KeepersHandbookSchemaTest extends TestCase
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
    private function ruleData(): array
    {
        return [
            'name' => 'Fixture Optional Rule',
            'kind' => 'campaign-option',
            'scope' => [
                'targets' => ['character'],
                'contexts' => ['exploration'],
                'content_types' => ['race', 'class'],
                'references' => ['first-almanac:rule:fixture-companion'],
            ],
            'activation' => [
                'mode' => 'optional',
                'enabled_by_default' => false,
                'exclusive_group' => 'fixture-rule-family',
            ],
            'priority' => 10,
            'prerequisites' => [
                ['type' => 'campaign-option-enabled'],
            ],
            'rules' => [
                ['kind' => 'modifier', 'target' => 'fixture-value', 'operation' => 'add', 'value' => 1],
            ],
            'conflicts' => ['first-almanac:rule:fixture-conflict'],
            'supersedes' => ['first-almanac:rule:fixture-old-rule'],
            'references' => ['first-almanac:adventure:fixture-adventure'],
            'keeper_notes' => ['This rule exists solely to prove structure.'],
        ];
    }

    /** @return array<string,mixed> */
    private function conditionData(): array
    {
        return [
            'name' => 'Fixture Sticky',
            'kind' => 'status',
            'application' => [
                'type' => 'contact',
                'source' => 'fixture-syrup',
                'save' => 'dexterity',
                'difficulty' => 12,
                'rules' => [
                    ['kind' => 'requirement', 'type' => 'touched-syrup'],
                ],
            ],
            'duration' => [
                'type' => 'until-removed',
                'until' => 'The creature is thoroughly cleaned.',
            ],
            'stacking' => [
                'mode' => 'intensify',
                'maximum' => 3,
                'refreshes_duration' => true,
            ],
            'effects' => [
                ['type' => 'sticky-movement'],
            ],
            'rules' => [
                ['kind' => 'modifier', 'target' => 'speed', 'operation' => 'subtract', 'value' => 5],
            ],
            'removal' => [
                [
                    'key' => 'wash',
                    'name' => 'Wash It Off',
                    'check' => 'survival',
                    'difficulty' => 10,
                    'rules' => [
                        ['kind' => 'effect', 'type' => 'remove-condition'],
                    ],
                ],
            ],
            'stages' => [
                [
                    'key' => 'very-sticky',
                    'name' => 'Very Sticky',
                    'trigger' => 'At two stacks.',
                    'rules' => [
                        ['kind' => 'effect', 'type' => 'fixture-escalation'],
                    ],
                ],
            ],
            'references' => ['first-almanac:hazard:fixture-syrup-floor'],
            'keeper_notes' => ['The spoon is not helping.'],
        ];
    }

    public function test_minimal_named_rule_remains_valid(): void
    {
        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('rule', 'fixture-minimal-rule', [
                'name' => 'Fixture Minimal Rule',
            ]))->valid()
        );
    }

    public function test_complete_structured_rule_is_valid(): void
    {
        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('rule', 'fixture-rule', $this->ruleData()))->valid()
        );
    }

    public function test_rule_schema_exposes_expected_keeper_fields(): void
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        $schema = $schemas->get('rule');

        self::assertNotNull($schema);
        foreach ([
            'kind', 'scope', 'activation', 'priority', 'prerequisites', 'rules',
            'conflicts', 'supersedes', 'references', 'keeper_notes',
        ] as $field) {
            self::assertArrayHasKey($field, $schema->fields(), 'Expected rule schema field: ' . $field);
        }
    }

    public function test_rule_scope_lists_must_be_non_empty_lists(): void
    {
        $data = $this->ruleData();
        $data['scope']['targets'] = 'character';
        $data['scope']['contexts'] = [];
        $result = $this->validator()->validate(new ContentDefinition('rule', 'bad-scope', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['scope.targets', 'scope.contexts'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_rule_scope_entries_must_be_non_empty_strings(): void
    {
        $data = $this->ruleData();
        $data['scope']['targets'] = ['character', ''];
        $result = $this->validator()->validate(new ContentDefinition('rule', 'bad-scope-entry', $data));

        self::assertFalse($result->valid());
        self::assertSame('scope.targets.1', $result->errors()[0]->field());
    }

    public function test_rule_activation_boolean_and_strings_are_validated(): void
    {
        $data = $this->ruleData();
        $data['activation'] = [
            'mode' => '',
            'enabled_by_default' => 'sometimes',
            'exclusive_group' => '',
        ];
        $result = $this->validator()->validate(new ContentDefinition('rule', 'bad-activation', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['activation.mode', 'activation.enabled_by_default', 'activation.exclusive_group'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_rule_prerequisites_are_validated_by_rules_engine(): void
    {
        $data = $this->ruleData();
        $data['prerequisites'] = [['notes' => 'Missing type']];
        $result = $this->validator()->validate(new ContentDefinition('rule', 'bad-prerequisite', $data));

        self::assertFalse($result->valid());
        self::assertSame('prerequisites.0.type', $result->errors()[0]->field());
    }

    public function test_rule_generic_rules_are_validated_by_rules_engine(): void
    {
        $data = $this->ruleData();
        $data['rules'] = [
            ['kind' => 'modifier', 'target' => 'speed'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('rule', 'bad-rules', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['rules.0.operation', 'rules.0.value'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_rule_reference_lists_are_validated(): void
    {
        $data = $this->ruleData();
        $data['conflicts'] = [''];
        $data['supersedes'] = [123];
        $data['references'] = [''];
        $data['keeper_notes'] = [false];
        $result = $this->validator()->validate(new ContentDefinition('rule', 'bad-rule-refs', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['conflicts.0', 'supersedes.0', 'references.0', 'keeper_notes.0'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_open_rule_kind_scope_and_activation_vocabularies_allow_oddities(): void
    {
        $data = $this->ruleData();
        $data['kind'] = 'keeper-wrote-this-in-the-margin';
        $data['scope']['contexts'] = ['pippin-is-looking-at-the-bookshelf'];
        $data['activation']['mode'] = 'only-if-everyone-agrees-this-is-a-good-idea';

        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('rule', 'future-rule-oddity', $data))->valid()
        );
    }

    public function test_minimal_named_condition_remains_valid(): void
    {
        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('condition', 'fixture-minimal-condition', [
                'name' => 'Fixture Minimal Condition',
            ]))->valid()
        );
    }

    public function test_complete_structured_condition_is_valid(): void
    {
        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('condition', 'fixture-sticky', $this->conditionData()))->valid()
        );
    }

    public function test_condition_schema_exposes_expected_keeper_fields(): void
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        $schema = $schemas->get('condition');

        self::assertNotNull($schema);
        foreach ([
            'kind', 'application', 'duration', 'stacking', 'effects', 'rules',
            'removal', 'stages', 'references', 'keeper_notes',
        ] as $field) {
            self::assertArrayHasKey($field, $schema->fields(), 'Expected condition schema field: ' . $field);
        }
    }

    public function test_condition_application_supports_open_numeric_or_named_difficulty(): void
    {
        $data = $this->conditionData();
        $data['application']['difficulty'] = 'keeper-decides-later';

        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('condition', 'open-difficulty', $data))->valid()
        );
    }

    public function test_condition_application_rejects_empty_strings_and_bad_difficulty(): void
    {
        $data = $this->conditionData();
        $data['application'] = [
            'type' => '',
            'save' => '',
            'difficulty' => [],
        ];
        $result = $this->validator()->validate(new ContentDefinition('condition', 'bad-application', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['application.type', 'application.save', 'application.difficulty'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_condition_application_rules_are_validated_by_rules_engine(): void
    {
        $data = $this->conditionData();
        $data['application']['rules'] = [
            ['kind' => 'effect'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('condition', 'bad-application-rules', $data));

        self::assertFalse($result->valid());
        self::assertSame('application.rules.0.type', $result->errors()[0]->field());
    }

    public function test_condition_duration_requires_type_and_positive_rounds(): void
    {
        $data = $this->conditionData();
        $data['duration'] = ['rounds' => 0];
        $result = $this->validator()->validate(new ContentDefinition('condition', 'bad-duration', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['duration.type', 'duration.rounds'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_condition_stacking_metadata_is_validated(): void
    {
        $data = $this->conditionData();
        $data['stacking'] = [
            'mode' => '',
            'maximum' => 0,
            'refreshes_duration' => 'yes',
        ];
        $result = $this->validator()->validate(new ContentDefinition('condition', 'bad-stacking', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['stacking.mode', 'stacking.maximum', 'stacking.refreshes_duration'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_condition_top_level_effects_are_validated_by_rules_engine(): void
    {
        $data = $this->conditionData();
        $data['effects'] = [['notes' => 'Missing type']];
        $result = $this->validator()->validate(new ContentDefinition('condition', 'bad-effect', $data));

        self::assertFalse($result->valid());
        self::assertSame('effects.0.type', $result->errors()[0]->field());
    }

    public function test_condition_generic_rules_are_validated_by_rules_engine(): void
    {
        $data = $this->conditionData();
        $data['rules'] = [['kind' => 'unknown-kind']];
        $result = $this->validator()->validate(new ContentDefinition('condition', 'bad-generic-rule', $data));

        self::assertFalse($result->valid());
        self::assertSame('rules.0.kind', $result->errors()[0]->field());
    }

    public function test_condition_removal_methods_require_unique_keys_and_names(): void
    {
        $data = $this->conditionData();
        $data['removal'] = [
            ['key' => 'wash', 'name' => 'Wash'],
            ['key' => 'wash', 'name' => 'Wash Again'],
            ['key' => 'missing-name'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('condition', 'bad-removal', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['removal.1.key', 'removal.2.name'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_condition_stage_rules_are_validated_by_rules_engine(): void
    {
        $data = $this->conditionData();
        $data['stages'][0]['rules'] = [
            ['kind' => 'modifier', 'target' => 'speed'],
        ];
        $result = $this->validator()->validate(new ContentDefinition('condition', 'bad-stage-rule', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['stages.0.rules.0.operation', 'stages.0.rules.0.value'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_condition_reference_lists_are_validated(): void
    {
        $data = $this->conditionData();
        $data['references'] = [''];
        $data['keeper_notes'] = [123];
        $result = $this->validator()->validate(new ContentDefinition('condition', 'bad-condition-refs', $data));

        self::assertFalse($result->valid());
        self::assertSame(
            ['references.0', 'keeper_notes.0'],
            array_map(static fn ($e) => $e->field(), $result->errors())
        );
    }

    public function test_open_condition_vocabularies_allow_marketrealm_oddities(): void
    {
        $data = $this->conditionData();
        $data['kind'] = 'bureaucratically-inconvenienced';
        $data['application']['type'] = 'incorrect-form-submitted';
        $data['duration']['type'] = 'until-counter-three-reopens';
        $data['stacking']['mode'] = 'forms-accumulate';

        self::assertTrue(
            $this->validator()->validate(new ContentDefinition('condition', 'future-condition-oddity', $data))->valid()
        );
    }
}
