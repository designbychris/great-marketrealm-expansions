<?php
namespace GreatMarketrealmExpansions\Content\Schema\Constraints;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ValidationError;

final class ConditionStructureConstraint implements ContentConstraint
{
    /** @return list<ValidationError> */
    public function validate(ContentDefinition $definition): array
    {
        $data = $definition->data();
        $errors = [];

        if (isset($data['application']) && is_array($data['application']) && $data['application'] !== []) {
            $errors = array_merge($errors, $this->validateApplication($data['application']));
        }

        if (isset($data['duration']) && is_array($data['duration']) && $data['duration'] !== []) {
            $errors = array_merge($errors, $this->validateDuration($data['duration']));
        }

        if (isset($data['stacking']) && is_array($data['stacking']) && $data['stacking'] !== []) {
            $errors = array_merge($errors, $this->validateStacking($data['stacking']));
        }

        if (isset($data['removal']) && is_array($data['removal'])) {
            $errors = array_merge($errors, $this->validateKeyedMethods('removal', $data['removal']));
        }

        if (isset($data['stages']) && is_array($data['stages'])) {
            $errors = array_merge($errors, $this->validateKeyedMethods('stages', $data['stages']));
        }

        foreach (['references', 'keeper_notes'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $errors = array_merge($errors, $this->validateStringList($field, $data[$field]));
            }
        }

        return $errors;
    }

    /** @param array<string,mixed> $application @return list<ValidationError> */
    private function validateApplication(array $application): array
    {
        $errors = [];

        foreach (['type', 'source', 'save', 'check'] as $field) {
            if (isset($application[$field]) && (!is_string($application[$field]) || trim($application[$field]) === '')) {
                $errors[] = new ValidationError(
                    'application.' . $field,
                    sprintf('Condition application "%s" must be non-empty when supplied.', $field)
                );
            }
        }

        if (isset($application['difficulty']) && !(
            is_int($application['difficulty'])
            || is_float($application['difficulty'])
            || (is_string($application['difficulty']) && trim($application['difficulty']) !== '')
        )) {
            $errors[] = new ValidationError('application.difficulty', 'Condition application difficulty must be numeric or a non-empty canonical key.');
        }

        if (isset($application['rules']) && (!is_array($application['rules']) || !array_is_list($application['rules']))) {
            $errors[] = new ValidationError('application.rules', 'Condition application rules must be a list.');
        }

        return $errors;
    }

    /** @param array<string,mixed> $duration @return list<ValidationError> */
    private function validateDuration(array $duration): array
    {
        $errors = [];

        if (!isset($duration['type']) || !is_string($duration['type']) || trim($duration['type']) === '') {
            $errors[] = new ValidationError('duration.type', 'Condition duration requires a non-empty canonical type.');
        }

        if (isset($duration['rounds']) && (!is_int($duration['rounds']) || $duration['rounds'] < 1)) {
            $errors[] = new ValidationError('duration.rounds', 'Condition duration rounds must be a positive integer.');
        }

        foreach (['until', 'ends_on'] as $field) {
            if (isset($duration[$field]) && (!is_string($duration[$field]) || trim($duration[$field]) === '')) {
                $errors[] = new ValidationError('duration.' . $field, sprintf('Condition duration "%s" must be non-empty when supplied.', $field));
            }
        }

        return $errors;
    }

    /** @param array<string,mixed> $stacking @return list<ValidationError> */
    private function validateStacking(array $stacking): array
    {
        $errors = [];

        if (isset($stacking['mode']) && (!is_string($stacking['mode']) || trim($stacking['mode']) === '')) {
            $errors[] = new ValidationError('stacking.mode', 'Condition stacking mode must be a non-empty canonical key.');
        }

        if (isset($stacking['maximum']) && (!is_int($stacking['maximum']) || $stacking['maximum'] < 1)) {
            $errors[] = new ValidationError('stacking.maximum', 'Condition stacking maximum must be a positive integer.');
        }

        if (isset($stacking['refreshes_duration']) && !is_bool($stacking['refreshes_duration'])) {
            $errors[] = new ValidationError('stacking.refreshes_duration', 'Condition stacking refreshes-duration flag must be boolean.');
        }

        return $errors;
    }

    /** @param list<mixed> $entries @return list<ValidationError> */
    private function validateKeyedMethods(string $path, array $entries): array
    {
        $errors = [];
        $seen = [];

        foreach ($entries as $index => $entry) {
            if (!is_array($entry) || array_is_list($entry) || $entry === []) {
                $errors[] = new ValidationError($path . '.' . $index, sprintf('Condition "%s" entries must be non-empty maps.', $path));
                continue;
            }

            foreach (['key', 'name'] as $field) {
                if (!isset($entry[$field]) || !is_string($entry[$field]) || trim($entry[$field]) === '') {
                    $errors[] = new ValidationError(
                        $path . '.' . $index . '.' . $field,
                        sprintf('Condition "%s" entries require a non-empty "%s".', $path, $field)
                    );
                }
            }

            if (isset($entry['key']) && is_string($entry['key']) && trim($entry['key']) !== '') {
                $key = trim($entry['key']);
                if (isset($seen[$key])) {
                    $errors[] = new ValidationError(
                        $path . '.' . $index . '.key',
                        sprintf('Condition %s key "%s" is duplicated.', $path, $key)
                    );
                }
                $seen[$key] = true;
            }

            foreach (['description', 'trigger', 'check', 'save'] as $field) {
                if (isset($entry[$field]) && (!is_string($entry[$field]) || trim($entry[$field]) === '')) {
                    $errors[] = new ValidationError(
                        $path . '.' . $index . '.' . $field,
                        sprintf('Condition "%s" "%s" must be non-empty when supplied.', $path, $field)
                    );
                }
            }

            if (isset($entry['difficulty']) && !(
                is_int($entry['difficulty'])
                || is_float($entry['difficulty'])
                || (is_string($entry['difficulty']) && trim($entry['difficulty']) !== '')
            )) {
                $errors[] = new ValidationError(
                    $path . '.' . $index . '.difficulty',
                    sprintf('Condition "%s" difficulty must be numeric or a non-empty canonical key.', $path)
                );
            }

            if (isset($entry['rules']) && (!is_array($entry['rules']) || !array_is_list($entry['rules']))) {
                $errors[] = new ValidationError(
                    $path . '.' . $index . '.rules',
                    sprintf('Condition "%s" rules must be a list.', $path)
                );
            }
        }

        return $errors;
    }

    /** @param list<mixed> $values @return list<ValidationError> */
    private function validateStringList(string $field, array $values): array
    {
        $errors = [];
        foreach ($values as $index => $value) {
            if (!is_string($value) || trim($value) === '') {
                $errors[] = new ValidationError(
                    $field . '.' . $index,
                    sprintf('Entries in "%s" must be non-empty canonical keys, references, or descriptions.', $field)
                );
            }
        }
        return $errors;
    }
}
