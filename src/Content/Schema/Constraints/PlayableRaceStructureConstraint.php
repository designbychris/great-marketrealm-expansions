<?php
namespace GreatMarketrealmExpansions\Content\Schema\Constraints;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ValidationError;

final class PlayableRaceStructureConstraint implements ContentConstraint
{

    /** @return list<ValidationError> */
    public function validate(ContentDefinition $definition): array
    {
        $data = $definition->data();
        $errors = [];

        if (isset($data['size']) && is_array($data['size'])) {
            $errors = array_merge($errors, $this->validateSize($data['size']));
        }

        if (isset($data['speed']) && is_array($data['speed'])) {
            $errors = array_merge($errors, $this->validateSpeed($data['speed']));
        }

        if (isset($data['languages']) && is_array($data['languages'])) {
            $errors = array_merge($errors, $this->validateStringList('languages', $data['languages']));
        }

        if (isset($data['traits']) && is_array($data['traits'])) {
            $errors = array_merge($errors, $this->validateTraits($data['traits']));
        }

        if (isset($data['resistances']) && is_array($data['resistances'])) {
            $errors = array_merge($errors, $this->validateStringList('resistances', $data['resistances']));
        }

        if (isset($data['proficiencies']) && is_array($data['proficiencies'])) {
            foreach ($data['proficiencies'] as $kind => $values) {
                if (!is_array($values) || !array_is_list($values)) {
                    $errors[] = new ValidationError('proficiencies.' . $kind, 'Proficiency groups must be lists of canonical keys.');
                    continue;
                }
                $errors = array_merge($errors, $this->validateStringList('proficiencies.' . $kind, $values));
            }
        }

        if (isset($data['senses']) && is_array($data['senses'])) {
            foreach ($data['senses'] as $sense => $distance) {
                if (!is_int($distance) || $distance < 0) {
                    $errors[] = new ValidationError('senses.' . $sense, 'Sense distances must be non-negative integers measured in feet.');
                }
            }
        }

        foreach (['ability_score_rules', 'language_choices', 'choices'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                foreach ($data[$field] as $index => $choice) {
                    if (!is_array($choice) || array_is_list($choice) || $choice === []) {
                        $errors[] = new ValidationError($field . '.' . $index, sprintf('Entries in "%s" must be non-empty maps.', $field));
                    }
                }
            }
        }

        return $errors;
    }

    /** @param array<string, mixed> $size @return list<ValidationError> */
    private function validateSize(array $size): array
    {
        $hasValue = isset($size['value']) && is_string($size['value']) && trim($size['value']) !== '';
        $hasOptions = isset($size['options']) && is_array($size['options']) && array_is_list($size['options']) && $size['options'] !== [];

        if (!$hasValue && !$hasOptions) {
            return [new ValidationError('size', 'Size must provide a fixed "value" or a non-empty "options" list.')];
        }

        if ($hasOptions) {
            return $this->validateStringList('size.options', $size['options']);
        }

        return [];
    }

    /** @param array<string, mixed> $speed @return list<ValidationError> */
    private function validateSpeed(array $speed): array
    {
        $errors = [];
        if (!isset($speed['walk']) || !is_int($speed['walk']) || $speed['walk'] <= 0) {
            $errors[] = new ValidationError('speed.walk', 'Playable races must define a positive walking speed in feet.');
        }

        foreach (['swim', 'climb', 'fly', 'burrow'] as $mode) {
            if (array_key_exists($mode, $speed) && (!is_int($speed[$mode]) || $speed[$mode] < 0)) {
                $errors[] = new ValidationError('speed.' . $mode, sprintf('The %s speed must be a non-negative integer measured in feet.', $mode));
            }
        }

        if (array_key_exists('hover', $speed) && !is_bool($speed['hover'])) {
            $errors[] = new ValidationError('speed.hover', 'Hover must be a boolean.');
        }

        return $errors;
    }

    /** @param list<mixed> $traits @return list<ValidationError> */
    private function validateTraits(array $traits): array
    {
        $errors = [];
        foreach ($traits as $index => $trait) {
            if (!is_array($trait) || array_is_list($trait)) {
                $errors[] = new ValidationError('traits.' . $index, 'Race traits must be maps.');
                continue;
            }

            foreach (['key', 'name'] as $field) {
                if (!isset($trait[$field]) || !is_string($trait[$field]) || trim($trait[$field]) === '') {
                    $errors[] = new ValidationError('traits.' . $index . '.' . $field, sprintf('Race traits require a non-empty "%s".', $field));
                }
            }

            if (isset($trait['description']) && (!is_string($trait['description']) || trim($trait['description']) === '')) {
                $errors[] = new ValidationError('traits.' . $index . '.description', 'Trait descriptions, when supplied, must be non-empty strings.');
            }

            if (isset($trait['rules']) && (!is_array($trait['rules']) || !array_is_list($trait['rules']))) {
                $errors[] = new ValidationError('traits.' . $index . '.rules', 'Trait rules must be a list.');
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
                $errors[] = new ValidationError($field . '.' . $index, sprintf('Entries in "%s" must be non-empty strings.', $field));
            }
        }
        return $errors;
    }
}
