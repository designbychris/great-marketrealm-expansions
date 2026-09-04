<?php
namespace GreatMarketrealmExpansions\Content\Schema\Constraints;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ValidationError;
use GreatMarketrealmExpansions\Rules\RuleEngine;
use GreatMarketrealmExpansions\Rules\RuleValidationException;

final class RulesContentConstraint implements ContentConstraint
{
    public function __construct(private ?RuleEngine $rules = null)
    {
        $this->rules ??= new RuleEngine();
    }

    /** @return list<ValidationError> */
    public function validate(ContentDefinition $definition): array
    {
        $data = $definition->data();
        $errors = [];

        $containers = [
            'grants' => RuleEngine::GRANT,
            'choices' => RuleEngine::CHOICE,
            'modifiers' => RuleEngine::MODIFIER,
            'effects' => RuleEngine::EFFECT,
            'prerequisites' => RuleEngine::REQUIREMENT,
        ];

        foreach ($containers as $field => $kind) {
            if (!isset($data[$field]) || !is_array($data[$field]) || !array_is_list($data[$field])) {
                continue;
            }

            foreach ($data[$field] as $index => $payload) {
                // Existing domain constraints own list/map shape validation.
                if (!is_array($payload) || array_is_list($payload) || $payload === []) {
                    continue;
                }

                $result = $this->rules->validate($kind, $payload);
                foreach ($result->errors() as $error) {
                    $errors[] = new ValidationError(
                        $field . '.' . $index . '.' . $error->field(),
                        $error->message()
                    );
                }
            }
        }

        $errors = array_merge($errors, $this->validateGenericRules($data));

        return $errors;
    }

    /**
     * Recursively validates any explicit `rules` list. This is how race traits,
     * class features, background features and future nested structures can all
     * speak the same neutral mechanical language.
     *
     * @param array<mixed> $node
     * @return list<ValidationError>
     */
    private function validateGenericRules(array $node, string $path = ''): array
    {
        $errors = [];

        foreach ($node as $key => $value) {
            $fieldPath = $path === '' ? (string) $key : $path . '.' . $key;

            if ($key === 'rules' && is_array($value) && array_is_list($value)) {
                foreach ($value as $index => $rule) {
                    if (!is_array($rule) || array_is_list($rule) || $rule === []) {
                        $errors[] = new ValidationError(
                            $fieldPath . '.' . $index,
                            'Rule entries must be non-empty maps.'
                        );
                        continue;
                    }

                    try {
                        $this->rules->statementFromArray($rule);
                    } catch (RuleValidationException $exception) {
                        foreach ($exception->result()->errors() as $error) {
                            $errors[] = new ValidationError(
                                $fieldPath . '.' . $index . '.' . $error->field(),
                                $error->message()
                            );
                        }
                    }
                }
                continue;
            }

            if (is_array($value)) {
                $errors = array_merge($errors, $this->validateGenericRules($value, $fieldPath));
            }
        }

        return $errors;
    }
}
