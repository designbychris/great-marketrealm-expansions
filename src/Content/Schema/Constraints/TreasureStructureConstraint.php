<?php
namespace GreatMarketrealmExpansions\Content\Schema\Constraints;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ValidationError;

final class TreasureStructureConstraint implements ContentConstraint
{
    /** @return list<ValidationError> */
    public function validate(ContentDefinition $definition): array
    {
        $data = $definition->data();
        $errors = [];

        if (isset($data['currency']) && is_array($data['currency'])) {
            $errors = array_merge($errors, $this->validateCurrency('currency', $data['currency']));
        }

        if (isset($data['items']) && is_array($data['items'])) {
            $errors = array_merge($errors, $this->validateItems('items', $data['items']));
        }

        if (isset($data['nested_treasure']) && is_array($data['nested_treasure'])) {
            $errors = array_merge($errors, $this->validateStringList('nested_treasure', $data['nested_treasure']));
        }

        if (isset($data['tables']) && is_array($data['tables'])) {
            $errors = array_merge($errors, $this->validateTables($data['tables']));
        }

        if (isset($data['selections']) && is_array($data['selections'])) {
            $errors = array_merge($errors, $this->validateSelections($data['selections']));
        }

        if (isset($data['value']) && is_array($data['value']) && $data['value'] !== []) {
            $errors = array_merge($errors, $this->validateValue($data['value']));
        }

        foreach (['references', 'keeper_notes'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $errors = array_merge($errors, $this->validateStringList($field, $data[$field]));
            }
        }

        return $errors;
    }

    /** @param array<string,mixed> $currency @return list<ValidationError> */
    private function validateCurrency(string $path, array $currency): array
    {
        $errors = [];
        foreach ($currency as $key => $amount) {
            if (!is_string($key) || trim($key) === '') {
                $errors[] = new ValidationError($path, 'Treasure currency keys must be non-empty canonical keys.');
                continue;
            }
            if (!(is_int($amount) || is_float($amount)) || $amount < 0) {
                $errors[] = new ValidationError($path . '.' . $key, 'Treasure currency amounts must be non-negative numbers.');
            }
        }
        return $errors;
    }

    /** @param list<mixed> $items @return list<ValidationError> */
    private function validateItems(string $path, array $items): array
    {
        $errors = [];
        foreach ($items as $index => $item) {
            if (!is_array($item) || array_is_list($item) || $item === []) {
                $errors[] = new ValidationError($path . '.' . $index, 'Treasure item entries must be non-empty maps.');
                continue;
            }

            if (!isset($item['ref']) || !is_string($item['ref']) || trim($item['ref']) === '') {
                $errors[] = new ValidationError($path . '.' . $index . '.ref', 'Treasure items require a canonical content reference.');
            }

            if (isset($item['quantity']) && (!is_int($item['quantity']) || $item['quantity'] < 1)) {
                $errors[] = new ValidationError($path . '.' . $index . '.quantity', 'Treasure item quantity must be a positive integer.');
            }

            if (isset($item['weight']) && (!(is_int($item['weight']) || is_float($item['weight'])) || $item['weight'] <= 0)) {
                $errors[] = new ValidationError($path . '.' . $index . '.weight', 'Treasure item weight must be a positive number when supplied.');
            }

            if (isset($item['chance']) && (!(is_int($item['chance']) || is_float($item['chance'])) || $item['chance'] < 0 || $item['chance'] > 100)) {
                $errors[] = new ValidationError($path . '.' . $index . '.chance', 'Treasure item chance must be between 0 and 100.');
            }

            foreach (['variant', 'notes'] as $field) {
                if (isset($item[$field]) && (!is_string($item[$field]) || trim($item[$field]) === '')) {
                    $errors[] = new ValidationError(
                        $path . '.' . $index . '.' . $field,
                        sprintf('Treasure item "%s" must be non-empty when supplied.', $field)
                    );
                }
            }

            if (isset($item['rules']) && (!is_array($item['rules']) || !array_is_list($item['rules']))) {
                $errors[] = new ValidationError($path . '.' . $index . '.rules', 'Treasure item rules must be a list.');
            }
        }
        return $errors;
    }

    /** @param list<mixed> $tables @return list<ValidationError> */
    private function validateTables(array $tables): array
    {
        $errors = [];
        $seen = [];

        foreach ($tables as $index => $table) {
            if (!is_array($table) || array_is_list($table) || $table === []) {
                $errors[] = new ValidationError('tables.' . $index, 'Treasure tables must be non-empty maps.');
                continue;
            }

            foreach (['key', 'name'] as $field) {
                if (!isset($table[$field]) || !is_string($table[$field]) || trim($table[$field]) === '') {
                    $errors[] = new ValidationError(
                        'tables.' . $index . '.' . $field,
                        sprintf('Treasure tables require a non-empty "%s".', $field)
                    );
                }
            }

            if (isset($table['key']) && is_string($table['key']) && trim($table['key']) !== '') {
                $key = trim($table['key']);
                if (isset($seen[$key])) {
                    $errors[] = new ValidationError('tables.' . $index . '.key', sprintf('Treasure table key "%s" is duplicated.', $key));
                }
                $seen[$key] = true;
            }

            if (isset($table['die']) && (!is_string($table['die']) || trim($table['die']) === '')) {
                $errors[] = new ValidationError('tables.' . $index . '.die', 'Treasure table die expression must be non-empty when supplied.');
            }

            if (isset($table['rolls']) && (!is_int($table['rolls']) || $table['rolls'] < 1)) {
                $errors[] = new ValidationError('tables.' . $index . '.rolls', 'Treasure table rolls must be a positive integer.');
            }

            if (!isset($table['entries']) || !is_array($table['entries']) || !array_is_list($table['entries']) || $table['entries'] === []) {
                $errors[] = new ValidationError('tables.' . $index . '.entries', 'Treasure tables require a non-empty entry list.');
                continue;
            }

            $errors = array_merge($errors, $this->validateTableEntries('tables.' . $index . '.entries', $table['entries']));
        }

        return $errors;
    }

    /** @param list<mixed> $entries @return list<ValidationError> */
    private function validateTableEntries(string $path, array $entries): array
    {
        $errors = [];

        foreach ($entries as $index => $entry) {
            if (!is_array($entry) || array_is_list($entry) || $entry === []) {
                $errors[] = new ValidationError($path . '.' . $index, 'Treasure table entries must be non-empty maps.');
                continue;
            }

            $hasRange = isset($entry['min']) || isset($entry['max']);
            $hasWeight = isset($entry['weight']);

            if (!$hasRange && !$hasWeight) {
                $errors[] = new ValidationError($path . '.' . $index, 'Treasure table entries require either a roll range or a positive weight.');
            }

            if ($hasRange) {
                foreach (['min', 'max'] as $field) {
                    if (!isset($entry[$field]) || !is_int($entry[$field])) {
                        $errors[] = new ValidationError($path . '.' . $index . '.' . $field, 'Treasure table roll ranges require integer minimum and maximum values.');
                    }
                }
                if (
                    isset($entry['min'], $entry['max'])
                    && is_int($entry['min'])
                    && is_int($entry['max'])
                    && $entry['min'] > $entry['max']
                ) {
                    $errors[] = new ValidationError($path . '.' . $index . '.max', 'Treasure table maximum cannot be below its minimum.');
                }
            }

            if ($hasWeight && (!(is_int($entry['weight']) || is_float($entry['weight'])) || $entry['weight'] <= 0)) {
                $errors[] = new ValidationError($path . '.' . $index . '.weight', 'Treasure table weight must be a positive number.');
            }

            if (!isset($entry['reward']) || !is_array($entry['reward']) || array_is_list($entry['reward']) || $entry['reward'] === []) {
                $errors[] = new ValidationError($path . '.' . $index . '.reward', 'Treasure table entries require a non-empty reward map.');
            } else {
                $errors = array_merge($errors, $this->validateRewardMap($path . '.' . $index . '.reward', $entry['reward']));
            }
        }

        return $errors;
    }

    /** @param list<mixed> $selections @return list<ValidationError> */
    private function validateSelections(array $selections): array
    {
        $errors = [];
        $seen = [];

        foreach ($selections as $index => $selection) {
            if (!is_array($selection) || array_is_list($selection) || $selection === []) {
                $errors[] = new ValidationError('selections.' . $index, 'Treasure selections must be non-empty maps.');
                continue;
            }

            foreach (['key', 'name'] as $field) {
                if (!isset($selection[$field]) || !is_string($selection[$field]) || trim($selection[$field]) === '') {
                    $errors[] = new ValidationError(
                        'selections.' . $index . '.' . $field,
                        sprintf('Treasure selections require a non-empty "%s".', $field)
                    );
                }
            }

            if (isset($selection['key']) && is_string($selection['key']) && trim($selection['key']) !== '') {
                $key = trim($selection['key']);
                if (isset($seen[$key])) {
                    $errors[] = new ValidationError('selections.' . $index . '.key', sprintf('Treasure selection key "%s" is duplicated.', $key));
                }
                $seen[$key] = true;
            }

            if (isset($selection['count']) && (!is_int($selection['count']) || $selection['count'] < 1)) {
                $errors[] = new ValidationError('selections.' . $index . '.count', 'Treasure selection count must be a positive integer.');
            }

            if (!isset($selection['options']) || !is_array($selection['options']) || !array_is_list($selection['options']) || $selection['options'] === []) {
                $errors[] = new ValidationError('selections.' . $index . '.options', 'Treasure selections require a non-empty option list.');
                continue;
            }

            foreach ($selection['options'] as $optionIndex => $option) {
                if (is_string($option)) {
                    if (trim($option) === '') {
                        $errors[] = new ValidationError('selections.' . $index . '.options.' . $optionIndex, 'Treasure selection references cannot be empty.');
                    }
                    continue;
                }

                if (!is_array($option) || array_is_list($option) || $option === []) {
                    $errors[] = new ValidationError('selections.' . $index . '.options.' . $optionIndex, 'Treasure selection options must be canonical references or non-empty reward maps.');
                    continue;
                }

                $errors = array_merge(
                    $errors,
                    $this->validateRewardMap('selections.' . $index . '.options.' . $optionIndex, $option)
                );
            }
        }

        return $errors;
    }

    /** @param array<string,mixed> $reward @return list<ValidationError> */
    private function validateRewardMap(string $path, array $reward): array
    {
        $errors = [];

        if (!isset($reward['type']) || !is_string($reward['type']) || trim($reward['type']) === '') {
            $errors[] = new ValidationError($path . '.type', 'Structured treasure rewards require a non-empty type.');
        }

        if (isset($reward['quantity']) && (!is_int($reward['quantity']) || $reward['quantity'] < 1)) {
            $errors[] = new ValidationError($path . '.quantity', 'Structured treasure reward quantity must be a positive integer.');
        }

        if (isset($reward['ref']) && (!is_string($reward['ref']) || trim($reward['ref']) === '')) {
            $errors[] = new ValidationError($path . '.ref', 'Structured treasure reward references must be non-empty when supplied.');
        }

        if (isset($reward['currency']) && (!is_string($reward['currency']) || trim($reward['currency']) === '')) {
            $errors[] = new ValidationError($path . '.currency', 'Structured treasure reward currency must be non-empty when supplied.');
        }

        if (isset($reward['rules']) && (!is_array($reward['rules']) || !array_is_list($reward['rules']))) {
            $errors[] = new ValidationError($path . '.rules', 'Structured treasure reward rules must be a list.');
        }

        return $errors;
    }

    /** @param array<string,mixed> $value @return list<ValidationError> */
    private function validateValue(array $value): array
    {
        $errors = [];

        if (!isset($value['amount']) || !(is_int($value['amount']) || is_float($value['amount'])) || $value['amount'] < 0) {
            $errors[] = new ValidationError('value.amount', 'Treasure value requires a non-negative numeric amount.');
        }

        if (!isset($value['currency']) || !is_string($value['currency']) || trim($value['currency']) === '') {
            $errors[] = new ValidationError('value.currency', 'Treasure value requires a non-empty canonical currency key.');
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
