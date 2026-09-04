<?php
namespace GreatMarketrealmExpansions\Rules;

defined('ABSPATH') || exit;

final class RuleEngine
{
    public const API_VERSION = '1.0.0';

    public const GRANT = 'grant';
    public const CHOICE = 'choice';
    public const MODIFIER = 'modifier';
    public const EFFECT = 'effect';
    public const REQUIREMENT = 'requirement';

    /** @var list<string> */
    private const KINDS = [
        self::GRANT,
        self::CHOICE,
        self::MODIFIER,
        self::EFFECT,
        self::REQUIREMENT,
    ];

    /** @var list<string> */
    private const CAPABILITIES = [
        'rules.validate',
        'rules.statement',
        'rules.grant',
        'rules.choice',
        'rules.modifier',
        'rules.effect',
        'rules.requirement',
        'rules.content-validation',
    ];

    public function apiVersion(): string { return self::API_VERSION; }

    /** @return list<string> */
    public function capabilities(): array { return self::CAPABILITIES; }

    public function supports(string $capability): bool
    {
        return in_array(strtolower(trim($capability)), self::CAPABILITIES, true);
    }

    /** @return list<string> */
    public function kinds(): array { return self::KINDS; }

    /** @param array<string,mixed> $payload */
    public function validate(string $kind, array $payload): RuleValidationResult
    {
        $kind = self::canonicalKey($kind);
        if (!in_array($kind, self::KINDS, true)) {
            return new RuleValidationResult([
                new RuleValidationError('kind', sprintf('Unknown rule kind "%s".', $kind)),
            ]);
        }

        return new RuleValidationResult(match ($kind) {
            self::GRANT => $this->validateGrant($payload),
            self::CHOICE => $this->validateChoice($payload),
            self::MODIFIER => $this->validateModifier($payload),
            self::EFFECT => $this->validateEffect($payload),
            self::REQUIREMENT => $this->validateRequirement($payload),
        });
    }

    /** @param array<string,mixed> $payload */
    public function statement(string $kind, array $payload): RuleStatement
    {
        $result = $this->validate($kind, $payload);
        if (!$result->valid()) {
            throw new RuleValidationException($result);
        }

        return new RuleStatement(self::canonicalKey($kind), $this->canonicalisePayload($payload));
    }

    /**
     * Accepts the generic in-content shape:
     * ['kind' => 'modifier', 'target' => 'armour-class', ...]
     *
     * @param array<string,mixed> $rule
     */
    public function statementFromArray(array $rule): RuleStatement
    {
        $kind = $rule['kind'] ?? null;
        if (!is_string($kind) || trim($kind) === '') {
            throw new RuleValidationException(new RuleValidationResult([
                new RuleValidationError('kind', 'A generic rule statement requires a non-empty kind.'),
            ]));
        }

        unset($rule['kind']);
        return $this->statement($kind, $rule);
    }

    /** @param array<string,mixed> $payload @return list<RuleValidationError> */
    private function validateGrant(array $payload): array
    {
        return $this->requireCanonicalString($payload, 'type', 'A grant requires a non-empty canonical type.');
    }

    /** @param array<string,mixed> $payload @return list<RuleValidationError> */
    private function validateChoice(array $payload): array
    {
        $errors = $this->requireCanonicalString($payload, 'key', 'A choice requires a non-empty canonical key.');

        if (array_key_exists('count', $payload) && (!is_int($payload['count']) || $payload['count'] < 1)) {
            $errors[] = new RuleValidationError('count', 'Choice count must be a positive integer.');
        }

        if (array_key_exists('options', $payload)) {
            if (!is_array($payload['options']) || !array_is_list($payload['options']) || $payload['options'] === []) {
                $errors[] = new RuleValidationError('options', 'Choice options must be a non-empty list.');
            } else {
                foreach ($payload['options'] as $index => $option) {
                    if (
                        !(is_string($option) && trim($option) !== '')
                        && !(is_array($option) && !array_is_list($option) && $option !== [])
                    ) {
                        $errors[] = new RuleValidationError('options.' . $index, 'Choice options must be non-empty canonical keys or maps.');
                    }
                }
            }
        }

        return $errors;
    }

    /** @param array<string,mixed> $payload @return list<RuleValidationError> */
    private function validateModifier(array $payload): array
    {
        $errors = [];
        $errors = array_merge($errors, $this->requireCanonicalString($payload, 'target', 'A modifier requires a non-empty canonical target.'));
        $errors = array_merge($errors, $this->requireCanonicalString($payload, 'operation', 'A modifier requires a non-empty canonical operation.'));

        if (!array_key_exists('value', $payload)) {
            $errors[] = new RuleValidationError('value', 'A modifier requires a value.');
        }

        return $errors;
    }

    /** @param array<string,mixed> $payload @return list<RuleValidationError> */
    private function validateEffect(array $payload): array
    {
        return $this->requireCanonicalString($payload, 'type', 'An effect requires a non-empty canonical type.');
    }

    /** @param array<string,mixed> $payload @return list<RuleValidationError> */
    private function validateRequirement(array $payload): array
    {
        return $this->requireCanonicalString($payload, 'type', 'A requirement requires a non-empty canonical type.');
    }

    /**
     * @param array<string,mixed> $payload
     * @return list<RuleValidationError>
     */
    private function requireCanonicalString(array $payload, string $field, string $message): array
    {
        if (!array_key_exists($field, $payload) || !is_string($payload[$field]) || trim($payload[$field]) === '') {
            return [new RuleValidationError($field, $message)];
        }

        return [];
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function canonicalisePayload(array $payload): array
    {
        foreach (['type', 'key', 'target', 'operation'] as $field) {
            if (isset($payload[$field]) && is_string($payload[$field])) {
                $payload[$field] = self::canonicalKey($payload[$field]);
            }
        }
        return $payload;
    }

    private static function canonicalKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_\-]+/', '-', $value) ?? '';
        return trim(preg_replace('/-+/', '-', $value) ?? '', '-');
    }
}
