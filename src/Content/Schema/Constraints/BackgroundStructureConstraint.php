<?php
namespace GreatMarketrealmExpansions\Content\Schema\Constraints;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ValidationError;

final class BackgroundStructureConstraint implements ContentConstraint
{
    /** @return list<ValidationError> */
    public function validate(ContentDefinition $definition): array
    {
        $data = $definition->data();
        $errors = [];

        if (isset($data['proficiencies']) && is_array($data['proficiencies'])) {
            foreach ($data['proficiencies'] as $kind => $values) {
                if (!is_array($values) || !array_is_list($values)) {
                    $errors[] = new ValidationError('proficiencies.' . $kind, 'Background proficiency groups must be lists of canonical keys.');
                    continue;
                }
                $errors = array_merge($errors, $this->validateStringList('proficiencies.' . $kind, $values));
            }
        }

        foreach (['languages', 'feats'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $errors = array_merge($errors, $this->validateStringList($field, $data[$field]));
            }
        }

        if (isset($data['starting_equipment']) && is_array($data['starting_equipment'])) {
            $errors = array_merge($errors, $this->validateMapList('starting_equipment', $data['starting_equipment']));
        }

        if (isset($data['features']) && is_array($data['features'])) {
            $errors = array_merge($errors, $this->validateFeatures($data['features']));
        }

        foreach (['language_choices', 'equipment_choices', 'ability_score_rules', 'choices'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $errors = array_merge($errors, $this->validateMapList($field, $data[$field]));
            }
        }

        if (isset($data['characteristics']) && is_array($data['characteristics'])) {
            $errors = array_merge($errors, $this->validateCharacteristics($data['characteristics']));
        }

        return $errors;
    }

    /** @param list<mixed> $features @return list<ValidationError> */
    private function validateFeatures(array $features): array
    {
        $errors = [];
        $seen = [];

        foreach ($features as $index => $feature) {
            if (!is_array($feature) || array_is_list($feature)) {
                $errors[] = new ValidationError('features.' . $index, 'Background features must be maps.');
                continue;
            }

            foreach (['key', 'name'] as $field) {
                if (!isset($feature[$field]) || !is_string($feature[$field]) || trim($feature[$field]) === '') {
                    $errors[] = new ValidationError('features.' . $index . '.' . $field, sprintf('Background features require a non-empty "%s".', $field));
                }
            }

            if (isset($feature['key']) && is_string($feature['key']) && trim($feature['key']) !== '') {
                $key = trim($feature['key']);
                if (isset($seen[$key])) {
                    $errors[] = new ValidationError('features.' . $index . '.key', sprintf('Background feature key "%s" is duplicated.', $key));
                }
                $seen[$key] = true;
            }

            if (isset($feature['description']) && (!is_string($feature['description']) || trim($feature['description']) === '')) {
                $errors[] = new ValidationError('features.' . $index . '.description', 'Background feature descriptions, when supplied, must be non-empty strings.');
            }

            if (isset($feature['rules']) && (!is_array($feature['rules']) || !array_is_list($feature['rules']))) {
                $errors[] = new ValidationError('features.' . $index . '.rules', 'Background feature rules must be a list.');
            }
        }

        return $errors;
    }

    /** @param array<string,mixed> $characteristics @return list<ValidationError> */
    private function validateCharacteristics(array $characteristics): array
    {
        $errors = [];
        $allowed = ['personality_traits', 'ideals', 'bonds', 'flaws'];

        foreach ($characteristics as $kind => $values) {
            if (!in_array($kind, $allowed, true)) {
                $errors[] = new ValidationError('characteristics.' . $kind, sprintf('Unknown background characteristic group "%s".', $kind));
                continue;
            }
            if (!is_array($values) || !array_is_list($values)) {
                $errors[] = new ValidationError('characteristics.' . $kind, 'Background characteristic groups must be lists.');
                continue;
            }
            $errors = array_merge($errors, $this->validateStringList('characteristics.' . $kind, $values));
        }

        return $errors;
    }

    /** @param list<mixed> $values @return list<ValidationError> */
    private function validateStringList(string $field, array $values): array
    {
        $errors = [];
        foreach ($values as $index => $value) {
            if (!is_string($value) || trim($value) === '') {
                $errors[] = new ValidationError($field . '.' . $index, sprintf('Entries in "%s" must be non-empty strings.', $field));
            }
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
}
