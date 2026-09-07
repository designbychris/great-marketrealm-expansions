<?php
namespace GreatMarketrealmExpansions\Content\Schema\Constraints;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ValidationError;

final class HazardStructureConstraint implements ContentConstraint
{
    /** @return list<ValidationError> */
    public function validate(ContentDefinition $definition): array
    {
        $data = $definition->data();
        $errors = [];

        if (isset($data['severity']) && is_array($data['severity']) && $data['severity'] !== []) {
            $errors = array_merge($errors, $this->validateSeverity($data['severity']));
        }

        if (isset($data['trigger']) && is_array($data['trigger']) && $data['trigger'] !== []) {
            $errors = array_merge($errors, $this->validateTrigger($data['trigger']));
        }

        if (isset($data['detection']) && is_array($data['detection']) && $data['detection'] !== []) {
            $errors = array_merge($errors, $this->validateCheckMap('detection', $data['detection']));
        }

        if (isset($data['avoidance']) && is_array($data['avoidance'])) {
            $errors = array_merge($errors, $this->validateMethods('avoidance', $data['avoidance']));
        }

        if (isset($data['disarm']) && is_array($data['disarm']) && $data['disarm'] !== []) {
            $errors = array_merge($errors, $this->validateCheckMap('disarm', $data['disarm']));
        }

        if (isset($data['area']) && is_array($data['area']) && $data['area'] !== []) {
            $errors = array_merge($errors, $this->validateArea($data['area']));
        }

        if (isset($data['duration']) && is_array($data['duration']) && $data['duration'] !== []) {
            $errors = array_merge($errors, $this->validateDuration($data['duration']));
        }

        if (isset($data['effects']) && is_array($data['effects'])) {
            $errors = array_merge($errors, $this->validateMapList('effects', $data['effects']));
        }

        if (isset($data['consequences']) && is_array($data['consequences'])) {
            $errors = array_merge($errors, $this->validateNamedEntries('consequences', $data['consequences']));
        }

        if (isset($data['reset']) && is_array($data['reset']) && $data['reset'] !== []) {
            $errors = array_merge($errors, $this->validateReset($data['reset']));
        }

        if (isset($data['escalation']) && is_array($data['escalation'])) {
            $errors = array_merge($errors, $this->validateNamedEntries('escalation', $data['escalation']));
        }

        foreach (['references', 'keeper_notes'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $errors = array_merge($errors, $this->validateStringList($field, $data[$field]));
            }
        }

        return $errors;
    }

    /** @param array<string,mixed> $severity @return list<ValidationError> */
    private function validateSeverity(array $severity): array
    {
        $errors = [];

        if (isset($severity['rating']) && !(
            (is_string($severity['rating']) && trim($severity['rating']) !== '')
            || is_int($severity['rating'])
            || is_float($severity['rating'])
        )) {
            $errors[] = new ValidationError('severity.rating', 'Hazard severity rating must be numeric or a non-empty canonical key.');
        }

        foreach (['minimum_level', 'maximum_level'] as $field) {
            if (isset($severity[$field]) && (!is_int($severity[$field]) || $severity[$field] < 1)) {
                $errors[] = new ValidationError('severity.' . $field, 'Hazard level guidance must be a positive integer.');
            }
        }

        if (
            isset($severity['minimum_level'], $severity['maximum_level'])
            && is_int($severity['minimum_level'])
            && is_int($severity['maximum_level'])
            && $severity['minimum_level'] > $severity['maximum_level']
        ) {
            $errors[] = new ValidationError('severity.maximum_level', 'Hazard maximum level cannot be below its minimum level.');
        }

        return $errors;
    }

    /** @param array<string,mixed> $trigger @return list<ValidationError> */
    private function validateTrigger(array $trigger): array
    {
        $errors = [];

        if (!isset($trigger['type']) || !is_string($trigger['type']) || trim($trigger['type']) === '') {
            $errors[] = new ValidationError('trigger.type', 'Hazard triggers require a non-empty canonical type.');
        }

        foreach (['description', 'condition'] as $field) {
            if (isset($trigger[$field]) && (!is_string($trigger[$field]) || trim($trigger[$field]) === '')) {
                $errors[] = new ValidationError('trigger.' . $field, sprintf('Hazard trigger "%s" must be non-empty when supplied.', $field));
            }
        }

        if (isset($trigger['rules']) && (!is_array($trigger['rules']) || !array_is_list($trigger['rules']))) {
            $errors[] = new ValidationError('trigger.rules', 'Hazard trigger rules must be a list.');
        }

        return $errors;
    }

    /** @param array<string,mixed> $check @return list<ValidationError> */
    private function validateCheckMap(string $path, array $check): array
    {
        $errors = [];

        foreach (['check', 'description', 'success', 'failure'] as $field) {
            if (isset($check[$field]) && (!is_string($check[$field]) || trim($check[$field]) === '')) {
                $errors[] = new ValidationError($path . '.' . $field, sprintf('Hazard "%s" must be non-empty when supplied.', $field));
            }
        }

        if (isset($check['difficulty']) && !(
            is_int($check['difficulty'])
            || is_float($check['difficulty'])
            || (is_string($check['difficulty']) && trim($check['difficulty']) !== '')
        )) {
            $errors[] = new ValidationError($path . '.difficulty', 'Hazard check difficulty must be numeric or a non-empty canonical key.');
        }

        if (isset($check['passive']) && (!is_int($check['passive']) || $check['passive'] < 0)) {
            $errors[] = new ValidationError($path . '.passive', 'Hazard passive threshold must be a non-negative integer.');
        }

        if (isset($check['rules']) && (!is_array($check['rules']) || !array_is_list($check['rules']))) {
            $errors[] = new ValidationError($path . '.rules', 'Hazard check rules must be a list.');
        }

        return $errors;
    }

    /** @param list<mixed> $methods @return list<ValidationError> */
    private function validateMethods(string $path, array $methods): array
    {
        $errors = [];
        $seen = [];

        foreach ($methods as $index => $method) {
            if (!is_array($method) || array_is_list($method) || $method === []) {
                $errors[] = new ValidationError($path . '.' . $index, 'Hazard avoidance methods must be non-empty maps.');
                continue;
            }

            foreach (['key', 'name'] as $field) {
                if (!isset($method[$field]) || !is_string($method[$field]) || trim($method[$field]) === '') {
                    $errors[] = new ValidationError(
                        $path . '.' . $index . '.' . $field,
                        sprintf('Hazard avoidance methods require a non-empty "%s".', $field)
                    );
                }
            }

            if (isset($method['key']) && is_string($method['key']) && trim($method['key']) !== '') {
                $key = trim($method['key']);
                if (isset($seen[$key])) {
                    $errors[] = new ValidationError($path . '.' . $index . '.key', sprintf('Hazard avoidance key "%s" is duplicated.', $key));
                }
                $seen[$key] = true;
            }

            foreach (['description', 'check'] as $field) {
                if (isset($method[$field]) && (!is_string($method[$field]) || trim($method[$field]) === '')) {
                    $errors[] = new ValidationError(
                        $path . '.' . $index . '.' . $field,
                        sprintf('Hazard avoidance "%s" must be non-empty when supplied.', $field)
                    );
                }
            }

            if (isset($method['difficulty']) && !(
                is_int($method['difficulty'])
                || is_float($method['difficulty'])
                || (is_string($method['difficulty']) && trim($method['difficulty']) !== '')
            )) {
                $errors[] = new ValidationError($path . '.' . $index . '.difficulty', 'Hazard avoidance difficulty must be numeric or a non-empty canonical key.');
            }

            if (isset($method['rules']) && (!is_array($method['rules']) || !array_is_list($method['rules']))) {
                $errors[] = new ValidationError($path . '.' . $index . '.rules', 'Hazard avoidance rules must be a list.');
            }
        }

        return $errors;
    }

    /** @param array<string,mixed> $area @return list<ValidationError> */
    private function validateArea(array $area): array
    {
        $errors = [];

        if (isset($area['shape']) && (!is_string($area['shape']) || trim($area['shape']) === '')) {
            $errors[] = new ValidationError('area.shape', 'Hazard area shape must be a non-empty canonical key.');
        }

        foreach (['radius', 'diameter', 'length', 'width', 'height', 'depth'] as $field) {
            if (isset($area[$field]) && (!is_int($area[$field]) || $area[$field] < 0)) {
                $errors[] = new ValidationError('area.' . $field, 'Hazard area distances must be non-negative integers.');
            }
        }

        if (isset($area['units']) && (!is_string($area['units']) || trim($area['units']) === '')) {
            $errors[] = new ValidationError('area.units', 'Hazard area units must be a non-empty canonical key.');
        }

        return $errors;
    }

    /** @param array<string,mixed> $duration @return list<ValidationError> */
    private function validateDuration(array $duration): array
    {
        $errors = [];

        if (!isset($duration['type']) || !is_string($duration['type']) || trim($duration['type']) === '') {
            $errors[] = new ValidationError('duration.type', 'Hazard duration requires a non-empty canonical type.');
        }

        if (isset($duration['rounds']) && (!is_int($duration['rounds']) || $duration['rounds'] < 1)) {
            $errors[] = new ValidationError('duration.rounds', 'Hazard duration rounds must be a positive integer.');
        }

        if (isset($duration['until']) && (!is_string($duration['until']) || trim($duration['until']) === '')) {
            $errors[] = new ValidationError('duration.until', 'Hazard duration "until" text must be non-empty when supplied.');
        }

        return $errors;
    }

    /** @param list<mixed> $entries @return list<ValidationError> */
    private function validateNamedEntries(string $path, array $entries): array
    {
        $errors = [];
        $seen = [];

        foreach ($entries as $index => $entry) {
            if (!is_array($entry) || array_is_list($entry) || $entry === []) {
                $errors[] = new ValidationError($path . '.' . $index, sprintf('Hazard "%s" entries must be non-empty maps.', $path));
                continue;
            }

            foreach (['key', 'name'] as $field) {
                if (!isset($entry[$field]) || !is_string($entry[$field]) || trim($entry[$field]) === '') {
                    $errors[] = new ValidationError(
                        $path . '.' . $index . '.' . $field,
                        sprintf('Hazard "%s" entries require a non-empty "%s".', $path, $field)
                    );
                }
            }

            if (isset($entry['key']) && is_string($entry['key']) && trim($entry['key']) !== '') {
                $key = trim($entry['key']);
                if (isset($seen[$key])) {
                    $errors[] = new ValidationError($path . '.' . $index . '.key', sprintf('Hazard %s key "%s" is duplicated.', $path, $key));
                }
                $seen[$key] = true;
            }

            foreach (['description', 'trigger'] as $field) {
                if (isset($entry[$field]) && (!is_string($entry[$field]) || trim($entry[$field]) === '')) {
                    $errors[] = new ValidationError(
                        $path . '.' . $index . '.' . $field,
                        sprintf('Hazard "%s" "%s" must be non-empty when supplied.', $path, $field)
                    );
                }
            }

            if (isset($entry['rules']) && (!is_array($entry['rules']) || !array_is_list($entry['rules']))) {
                $errors[] = new ValidationError($path . '.' . $index . '.rules', sprintf('Hazard "%s" rules must be a list.', $path));
            }
        }

        return $errors;
    }

    /** @param array<string,mixed> $reset @return list<ValidationError> */
    private function validateReset(array $reset): array
    {
        $errors = [];

        if (!isset($reset['type']) || !is_string($reset['type']) || trim($reset['type']) === '') {
            $errors[] = new ValidationError('reset.type', 'Hazard reset requires a non-empty canonical type.');
        }

        if (isset($reset['automatic']) && !is_bool($reset['automatic'])) {
            $errors[] = new ValidationError('reset.automatic', 'Hazard reset automatic flag must be boolean.');
        }

        foreach (['interval', 'description'] as $field) {
            if (isset($reset[$field]) && (!is_string($reset[$field]) || trim($reset[$field]) === '')) {
                $errors[] = new ValidationError('reset.' . $field, sprintf('Hazard reset "%s" must be non-empty when supplied.', $field));
            }
        }

        if (isset($reset['rules']) && (!is_array($reset['rules']) || !array_is_list($reset['rules']))) {
            $errors[] = new ValidationError('reset.rules', 'Hazard reset rules must be a list.');
        }

        return $errors;
    }

    /** @param list<mixed> $values @return list<ValidationError> */
    private function validateMapList(string $field, array $values): array
    {
        $errors = [];
        foreach ($values as $index => $value) {
            if (!is_array($value) || array_is_list($value) || $value === []) {
                $errors[] = new ValidationError($field . '.' . $index, sprintf('Entries in "%s" must be non-empty maps.', $field));
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
