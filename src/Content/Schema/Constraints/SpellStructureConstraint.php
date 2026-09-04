<?php
namespace GreatMarketrealmExpansions\Content\Schema\Constraints;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ValidationError;

final class SpellStructureConstraint implements ContentConstraint
{
    /** @return list<ValidationError> */
    public function validate(ContentDefinition $definition): array
    {
        $data = $definition->data();
        $errors = [];

        if (isset($data['level']) && is_int($data['level']) && ($data['level'] < 0 || $data['level'] > 9)) {
            $errors[] = new ValidationError('level', 'Spell level must be an integer from 0 (cantrip) to 9.');
        }

        if (isset($data['school']) && is_string($data['school']) && trim($data['school']) === '') {
            $errors[] = new ValidationError('school', 'Spell school must be a non-empty canonical key.');
        }

        // FieldDefinition owns top-level type/emptiness validation. This
        // constraint only adds spell-specific semantics so each bad field is
        // reported once.
        if (isset($data['components']) && is_array($data['components']) && $data['components'] !== []) {
            $errors = array_merge($errors, $this->validateComponents($data['components']));
        }

        if (isset($data['spell_lists']) && is_array($data['spell_lists'])) {
            $errors = array_merge($errors, $this->validateStringList('spell_lists', $data['spell_lists']));
        }

        foreach (['effects', 'scaling'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $errors = array_merge($errors, $this->validateMapList($field, $data[$field]));
            }
        }

        return $errors;
    }

    /** @param array<string,mixed> $components @return list<ValidationError> */
    private function validateComponents(array $components): array
    {
        $errors = [];
        $known = ['verbal', 'somatic', 'material'];
        $hasComponent = false;

        foreach (['verbal', 'somatic'] as $component) {
            if (array_key_exists($component, $components)) {
                if (!is_bool($components[$component])) {
                    $errors[] = new ValidationError('components.' . $component, ucfirst($component) . ' component flags must be boolean.');
                } elseif ($components[$component]) {
                    $hasComponent = true;
                }
            }
        }

        if (array_key_exists('material', $components)) {
            if (!is_string($components['material']) || trim($components['material']) === '') {
                $errors[] = new ValidationError('components.material', 'Material components must be a non-empty description when supplied.');
            } else {
                $hasComponent = true;
            }
        }

        foreach ($components as $key => $_value) {
            if (!in_array($key, $known, true)) {
                $errors[] = new ValidationError('components.' . $key, sprintf('Unknown spell component key "%s".', $key));
            }
        }

        if ($errors === [] && !$hasComponent) {
            $errors[] = new ValidationError('components', 'A spell must declare at least one verbal, somatic, or material component.');
        }

        return $errors;
    }

    /** @param list<mixed> $values @return list<ValidationError> */
    private function validateStringList(string $field, array $values): array
    {
        $errors = [];
        foreach ($values as $index => $value) {
            if (!is_string($value) || trim($value) === '') {
                $errors[] = new ValidationError($field . '.' . $index, sprintf('Entries in "%s" must be non-empty canonical keys.', $field));
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
