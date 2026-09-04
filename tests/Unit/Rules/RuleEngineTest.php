<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Rules;

use GreatMarketrealmExpansions\Rules\RuleEngine;
use GreatMarketrealmExpansions\Rules\RuleValidationException;
use PHPUnit\Framework\TestCase;

final class RuleEngineTest extends TestCase
{
    public function test_rules_api_version_and_capabilities_are_stable(): void
    {
        $rules = new RuleEngine();
        self::assertSame('1.0.0', $rules->apiVersion());
        self::assertTrue($rules->supports('rules.validate'));
        self::assertTrue($rules->supports('rules.modifier'));
        self::assertFalse($rules->supports('rules.telepathy'));
    }

    public function test_supported_rule_kinds_are_explicit(): void
    {
        self::assertSame(
            ['grant', 'choice', 'modifier', 'effect', 'requirement'],
            (new RuleEngine())->kinds()
        );
    }

    public function test_grant_requires_a_type(): void
    {
        $result = (new RuleEngine())->validate('grant', []);
        self::assertFalse($result->valid());
        self::assertSame('type', $result->errors()[0]->field());
    }

    public function test_valid_grant_becomes_rule_statement(): void
    {
        $statement = (new RuleEngine())->statement('grant', [
            'type' => 'Skill Proficiency',
            'value' => 'survival',
        ]);
        self::assertSame('grant', $statement->kind());
        self::assertSame('skill-proficiency', $statement->type());
        self::assertSame('survival', $statement->value());
    }

    public function test_choice_requires_key_and_positive_count_when_present(): void
    {
        $result = (new RuleEngine())->validate('choice', ['count' => 0]);
        self::assertFalse($result->valid());
        self::assertSame(['key', 'count'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_choice_options_accept_keys_and_parameter_maps(): void
    {
        $result = (new RuleEngine())->validate('choice', [
            'key' => 'choose-proficiency',
            'count' => 1,
            'options' => ['survival', ['key' => 'nature', 'minimum_level' => 2]],
        ]);
        self::assertTrue($result->valid());
    }

    public function test_choice_options_reject_empty_entries(): void
    {
        $result = (new RuleEngine())->validate('choice', [
            'key' => 'choose-proficiency',
            'options' => ['', []],
        ]);
        self::assertFalse($result->valid());
        self::assertSame(['options.0', 'options.1'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_modifier_requires_target_operation_and_value(): void
    {
        $result = (new RuleEngine())->validate('modifier', []);
        self::assertFalse($result->valid());
        self::assertSame(['target', 'operation', 'value'], array_map(static fn ($e) => $e->field(), $result->errors()));
    }

    public function test_modifier_preserves_zero_as_a_real_value(): void
    {
        $statement = (new RuleEngine())->statement('modifier', [
            'target' => 'Walking Speed',
            'operation' => 'Set',
            'value' => 0,
        ]);
        self::assertSame('walking-speed', $statement->target());
        self::assertSame('set', $statement->operation());
        self::assertSame(0, $statement->value());
    }

    public function test_effect_requires_type(): void
    {
        $result = (new RuleEngine())->validate('effect', []);
        self::assertFalse($result->valid());
        self::assertSame('type', $result->errors()[0]->field());
    }

    public function test_requirement_requires_type(): void
    {
        $result = (new RuleEngine())->validate('requirement', []);
        self::assertFalse($result->valid());
        self::assertSame('type', $result->errors()[0]->field());
    }

    public function test_unknown_rule_kind_is_rejected(): void
    {
        $result = (new RuleEngine())->validate('mystery', ['type' => 'thing']);
        self::assertFalse($result->valid());
        self::assertSame('kind', $result->errors()[0]->field());
    }

    public function test_generic_rule_arrays_use_the_kind_field(): void
    {
        $statement = (new RuleEngine())->statementFromArray([
            'kind' => 'modifier',
            'target' => 'Armour Class',
            'operation' => 'Add',
            'value' => 1,
        ]);
        self::assertSame([
            'kind' => 'modifier',
            'target' => 'armour-class',
            'operation' => 'add',
            'value' => 1,
        ], $statement->toArray());
    }

    public function test_generic_rule_arrays_require_kind(): void
    {
        $this->expectException(RuleValidationException::class);
        (new RuleEngine())->statementFromArray(['type' => 'proficiency']);
    }

    public function test_invalid_statement_throws_validation_exception(): void
    {
        try {
            (new RuleEngine())->statement('modifier', ['target' => 'ac']);
            self::fail('Expected invalid modifier to throw.');
        } catch (RuleValidationException $exception) {
            self::assertFalse($exception->result()->valid());
            self::assertSame(['operation', 'value'], array_map(static fn ($e) => $e->field(), $exception->result()->errors()));
        }
    }
}
