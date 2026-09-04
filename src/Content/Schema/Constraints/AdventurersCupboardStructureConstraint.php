<?php
namespace GreatMarketrealmExpansions\Content\Schema\Constraints;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ValidationError;

final class AdventurersCupboardStructureConstraint implements ContentConstraint
{
    /** @return list<ValidationError> */
    public function validate(ContentDefinition $definition): array
    {
        $data = $definition->data();
        $errors = [];

        foreach (['category', 'rarity', 'proficiency'] as $field) {
            if (isset($data[$field]) && is_string($data[$field]) && trim($data[$field]) === '') {
                $errors[] = new ValidationError($field, sprintf('"%s" must be a non-empty canonical key.', $field));
            }
        }

        foreach (['weight'] as $field) {
            if (isset($data[$field]) && (is_int($data[$field]) || is_float($data[$field])) && $data[$field] < 0) {
                $errors[] = new ValidationError($field, ucfirst($field) . ' cannot be negative.');
            }
        }

        if (isset($data['quantity']) && is_int($data['quantity']) && $data['quantity'] < 1) {
            $errors[] = new ValidationError('quantity', 'Equipment quantity must be a positive integer.');
        }

        if (isset($data['strength_requirement']) && is_int($data['strength_requirement']) && $data['strength_requirement'] < 0) {
            $errors[] = new ValidationError('strength_requirement', 'Armour strength requirement cannot be negative.');
        }

        if (isset($data['properties']) && is_array($data['properties'])) {
            $errors = array_merge($errors, $this->validateProperties($data['properties']));
        }

        foreach (['effects', 'modifiers', 'choices'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $errors = array_merge($errors, $this->validateMapList($field, $data[$field]));
            }
        }

        if ($definition->type() === 'weapon' && isset($data['damage']) && is_array($data['damage']) && $data['damage'] !== []) {
            $errors = array_merge($errors, $this->validateDamage($data['damage']));
        }

        if ($definition->type() === 'armour' && isset($data['armour_class']) && is_array($data['armour_class']) && $data['armour_class'] !== []) {
            $errors = array_merge($errors, $this->validateArmourClass($data['armour_class']));
        }

        if (isset($data['range']) && is_array($data['range']) && $data['range'] !== []) {
            $errors = array_merge($errors, $this->validateRange($data['range']));
        }

        if (isset($data['cost']) && is_array($data['cost']) && $data['cost'] !== []) {
            $errors = array_merge($errors, $this->validateCost($data['cost']));
        }

        if (isset($data['charges']) && is_array($data['charges']) && $data['charges'] !== []) {
            $errors = array_merge($errors, $this->validateCharges($data['charges']));
        }

        if (isset($data['attunement']) && is_array($data['attunement']) && $data['attunement'] !== []) {
            $errors = array_merge($errors, $this->validateAttunement($data['attunement']));
        }

        return $errors;
    }

    /** @param array<string,mixed> $damage @return list<ValidationError> */
    private function validateDamage(array $damage): array
    {
        $errors = [];
        if (!isset($damage['dice']) || !is_string($damage['dice']) || trim($damage['dice']) === '') {
            $errors[] = new ValidationError('damage.dice', 'Weapon damage requires a non-empty dice expression.');
        }
        if (!isset($damage['type']) || !is_string($damage['type']) || trim($damage['type']) === '') {
            $errors[] = new ValidationError('damage.type', 'Weapon damage requires a non-empty canonical damage type.');
        }
        return $errors;
    }

    /** @param array<string,mixed> $armourClass @return list<ValidationError> */
    private function validateArmourClass(array $armourClass): array
    {
        $errors = [];
        if (!isset($armourClass['base']) || !is_int($armourClass['base']) || $armourClass['base'] < 0) {
            $errors[] = new ValidationError('armour_class.base', 'Armour class requires a non-negative integer base value.');
        }
        if (isset($armourClass['ability_modifier']) && (!is_string($armourClass['ability_modifier']) || trim($armourClass['ability_modifier']) === '')) {
            $errors[] = new ValidationError('armour_class.ability_modifier', 'Armour ability modifier must be a non-empty canonical ability key.');
        }
        if (isset($armourClass['modifier_cap']) && (!is_int($armourClass['modifier_cap']) || $armourClass['modifier_cap'] < 0)) {
            $errors[] = new ValidationError('armour_class.modifier_cap', 'Armour modifier cap must be a non-negative integer.');
        }
        return $errors;
    }

    /** @param array<string,mixed> $range @return list<ValidationError> */
    private function validateRange(array $range): array
    {
        $errors = [];
        foreach (['normal', 'long'] as $field) {
            if (isset($range[$field]) && (!is_int($range[$field]) || $range[$field] < 0)) {
                $errors[] = new ValidationError('range.' . $field, 'Weapon range distances must be non-negative integers.');
            }
        }
        if (!isset($range['normal'])) {
            $errors[] = new ValidationError('range.normal', 'Weapon range requires a normal distance.');
        }
        if (isset($range['long'], $range['normal']) && is_int($range['long']) && is_int($range['normal']) && $range['long'] < $range['normal']) {
            $errors[] = new ValidationError('range.long', 'Long range cannot be shorter than normal range.');
        }
        return $errors;
    }

    /** @param array<string,mixed> $cost @return list<ValidationError> */
    private function validateCost(array $cost): array
    {
        $errors = [];
        if (!isset($cost['amount']) || !(is_int($cost['amount']) || is_float($cost['amount'])) || $cost['amount'] < 0) {
            $errors[] = new ValidationError('cost.amount', 'Cost requires a non-negative numeric amount.');
        }
        if (!isset($cost['currency']) || !is_string($cost['currency']) || trim($cost['currency']) === '') {
            $errors[] = new ValidationError('cost.currency', 'Cost requires a non-empty canonical currency key.');
        }
        return $errors;
    }

    /** @param array<string,mixed> $charges @return list<ValidationError> */
    private function validateCharges(array $charges): array
    {
        $errors = [];
        if (!isset($charges['maximum']) || !is_int($charges['maximum']) || $charges['maximum'] < 1) {
            $errors[] = new ValidationError('charges.maximum', 'Charges require a positive integer maximum.');
        }
        if (isset($charges['recharge']) && (!is_string($charges['recharge']) || trim($charges['recharge']) === '')) {
            $errors[] = new ValidationError('charges.recharge', 'Charge recharge must be a non-empty canonical key or description.');
        }
        return $errors;
    }

    /** @param array<string,mixed> $attunement @return list<ValidationError> */
    private function validateAttunement(array $attunement): array
    {
        $errors = [];
        if (!isset($attunement['required']) || !is_bool($attunement['required'])) {
            $errors[] = new ValidationError('attunement.required', 'Attunement must declare a boolean "required" flag.');
        }
        if (isset($attunement['requirements']) && (!is_array($attunement['requirements']) || !array_is_list($attunement['requirements']))) {
            $errors[] = new ValidationError('attunement.requirements', 'Attunement requirements must be a list.');
        } elseif (isset($attunement['requirements'])) {
            $errors = array_merge($errors, $this->validateMapList('attunement.requirements', $attunement['requirements']));
        }
        return $errors;
    }

    /** @param list<mixed> $properties @return list<ValidationError> */
    private function validateProperties(array $properties): array
    {
        $errors = [];
        foreach ($properties as $index => $property) {
            if (is_string($property)) {
                if (trim($property) === '') {
                    $errors[] = new ValidationError('properties.' . $index, 'Property keys must be non-empty strings.');
                }
                continue;
            }
            if (!is_array($property) || array_is_list($property) || $property === []) {
                $errors[] = new ValidationError('properties.' . $index, 'Properties must be canonical string keys or non-empty parameter maps.');
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
