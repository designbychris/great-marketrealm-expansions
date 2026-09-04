<?php
namespace GreatMarketrealmExpansions\Content\Schema\Constraints;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ValidationError;

final class PlayableClassStructureConstraint implements ContentConstraint
{
    /** @return list<ValidationError> */
    public function validate(ContentDefinition $definition): array
    {
        $data = $definition->data();
        $errors = [];

        if ($definition->type() === 'class') {
            if (isset($data['hit_die']) && is_int($data['hit_die']) && ($data['hit_die'] < 4 || $data['hit_die'] > 20 || $data['hit_die'] % 2 !== 0)) {
                $errors[] = new ValidationError('hit_die', 'Class hit dice must be an even integer from 4 to 20.');
            }
            if (isset($data['max_level']) && is_int($data['max_level']) && ($data['max_level'] < 1 || $data['max_level'] > 30)) {
                $errors[] = new ValidationError('max_level', 'Class maximum level must be between 1 and 30.');
            }
            if (isset($data['saving_throw_proficiencies']) && is_array($data['saving_throw_proficiencies'])) {
                $errors = array_merge($errors, $this->validateStringList('saving_throw_proficiencies', $data['saving_throw_proficiencies']));
            }
            if (isset($data['primary_abilities']) && is_array($data['primary_abilities'])) {
                $errors = array_merge($errors, $this->validateStringList('primary_abilities', $data['primary_abilities']));
            }
            if (isset($data['proficiencies']) && is_array($data['proficiencies'])) {
                $errors = array_merge($errors, $this->validateGroupedLists('proficiencies', $data['proficiencies']));
            }
            if (isset($data['starting_equipment']) && is_array($data['starting_equipment'])) {
                $errors = array_merge($errors, $this->validateMapList('starting_equipment', $data['starting_equipment']));
            }
            if (isset($data['subclass_selection']) && is_array($data['subclass_selection'])) {
                $errors = array_merge($errors, $this->validateSubclassSelection($data['subclass_selection']));
            }
        }

        if ($definition->type() === 'subclass' && isset($data['entry_level']) && is_int($data['entry_level']) && $data['entry_level'] < 1) {
            $errors[] = new ValidationError('entry_level', 'Subclass entry level must be a positive integer.');
        }

        if (isset($data['features']) && is_array($data['features'])) {
            $errors = array_merge($errors, $this->validateFeatures($data['features']));
        }
        if (isset($data['progression']) && is_array($data['progression'])) {
            $errors = array_merge($errors, $this->validateProgression($definition, $data['progression'], $data));
        }
        if (isset($data['resources']) && is_array($data['resources'])) {
            $errors = array_merge($errors, $this->validateResources($data['resources']));
        }
        if (isset($data['spellcasting']) && is_array($data['spellcasting'])) {
            $errors = array_merge($errors, $this->validateSpellcasting($data['spellcasting']));
        }
        foreach (['choices', 'prerequisites'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $errors = array_merge($errors, $this->validateMapList($field, $data[$field]));
            }
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
                $errors[] = new ValidationError('features.' . $index, 'Class features must be maps.');
                continue;
            }
            foreach (['key', 'name'] as $field) {
                if (!isset($feature[$field]) || !is_string($feature[$field]) || trim($feature[$field]) === '') {
                    $errors[] = new ValidationError('features.' . $index . '.' . $field, sprintf('Class features require a non-empty "%s".', $field));
                }
            }
            if (isset($feature['key']) && is_string($feature['key']) && trim($feature['key']) !== '') {
                $key = trim($feature['key']);
                if (isset($seen[$key])) {
                    $errors[] = new ValidationError('features.' . $index . '.key', sprintf('Feature key "%s" is duplicated.', $key));
                }
                $seen[$key] = true;
            }
            if (isset($feature['description']) && (!is_string($feature['description']) || trim($feature['description']) === '')) {
                $errors[] = new ValidationError('features.' . $index . '.description', 'Feature descriptions, when supplied, must be non-empty strings.');
            }
            if (isset($feature['rules']) && (!is_array($feature['rules']) || !array_is_list($feature['rules']))) {
                $errors[] = new ValidationError('features.' . $index . '.rules', 'Feature rules must be a list.');
            }
        }
        return $errors;
    }

    /** @param list<mixed> $progression @param array<string,mixed> $data @return list<ValidationError> */
    private function validateProgression(ContentDefinition $definition, array $progression, array $data): array
    {
        $errors = [];
        $levels = [];
        $featureKeys = [];
        if (isset($data['features']) && is_array($data['features'])) {
            foreach ($data['features'] as $feature) {
                if (is_array($feature) && isset($feature['key']) && is_string($feature['key'])) {
                    $featureKeys[trim($feature['key'])] = true;
                }
            }
        }

        foreach ($progression as $index => $row) {
            if (!is_array($row) || array_is_list($row)) {
                $errors[] = new ValidationError('progression.' . $index, 'Progression entries must be maps.');
                continue;
            }
            if (!isset($row['level']) || !is_int($row['level']) || $row['level'] < 1) {
                $errors[] = new ValidationError('progression.' . $index . '.level', 'Progression levels must be positive integers.');
                continue;
            }
            $level = $row['level'];
            if (isset($levels[$level])) {
                $errors[] = new ValidationError('progression.' . $index . '.level', sprintf('Progression level %d is duplicated.', $level));
            }
            $levels[$level] = true;

            if (isset($row['features'])) {
                if (!is_array($row['features']) || !array_is_list($row['features'])) {
                    $errors[] = new ValidationError('progression.' . $index . '.features', 'Progression feature grants must be a list of feature keys.');
                } else {
                    foreach ($row['features'] as $featureIndex => $featureKey) {
                        if (!is_string($featureKey) || trim($featureKey) === '') {
                            $errors[] = new ValidationError('progression.' . $index . '.features.' . $featureIndex, 'Progression feature keys must be non-empty strings.');
                        } elseif (!isset($featureKeys[trim($featureKey)])) {
                            $errors[] = new ValidationError('progression.' . $index . '.features.' . $featureIndex, sprintf('Progression references unknown feature "%s".', trim($featureKey)));
                        }
                    }
                }
            }

            if (isset($row['resources']) && (!is_array($row['resources']) || array_is_list($row['resources']))) {
                $errors[] = new ValidationError('progression.' . $index . '.resources', 'Progression resource values must be a map.');
            }
            if (isset($row['spellcasting']) && (!is_array($row['spellcasting']) || array_is_list($row['spellcasting']))) {
                $errors[] = new ValidationError('progression.' . $index . '.spellcasting', 'Progression spellcasting values must be a map.');
            }
        }

        if ($definition->type() === 'class' && isset($data['max_level']) && is_int($data['max_level']) && $data['max_level'] > 0 && $data['max_level'] <= 30) {
            for ($level = 1; $level <= $data['max_level']; $level++) {
                if (!isset($levels[$level])) {
                    $errors[] = new ValidationError('progression', sprintf('Class progression must define level %d.', $level));
                    break;
                }
            }
            foreach (array_keys($levels) as $level) {
                if ($level > $data['max_level']) {
                    $errors[] = new ValidationError('progression', sprintf('Class progression level %d exceeds max_level %d.', $level, $data['max_level']));
                    break;
                }
            }
        }

        if ($definition->type() === 'subclass' && isset($data['entry_level']) && is_int($data['entry_level']) && $data['entry_level'] > 0 && $levels !== []) {
            $firstLevel = min(array_keys($levels));
            if ($firstLevel < $data['entry_level']) {
                $errors[] = new ValidationError('progression', 'Subclass progression cannot grant features before its entry level.');
            }
        }

        return $errors;
    }

    /** @param list<mixed> $resources @return list<ValidationError> */
    private function validateResources(array $resources): array
    {
        $errors = [];
        $seen = [];
        foreach ($resources as $index => $resource) {
            if (!is_array($resource) || array_is_list($resource)) {
                $errors[] = new ValidationError('resources.' . $index, 'Class resources must be maps.');
                continue;
            }
            foreach (['key', 'name'] as $field) {
                if (!isset($resource[$field]) || !is_string($resource[$field]) || trim($resource[$field]) === '') {
                    $errors[] = new ValidationError('resources.' . $index . '.' . $field, sprintf('Class resources require a non-empty "%s".', $field));
                }
            }
            if (isset($resource['key']) && is_string($resource['key']) && trim($resource['key']) !== '') {
                $key = trim($resource['key']);
                if (isset($seen[$key])) {
                    $errors[] = new ValidationError('resources.' . $index . '.key', sprintf('Resource key "%s" is duplicated.', $key));
                }
                $seen[$key] = true;
            }
            if (isset($resource['recharge']) && (!is_string($resource['recharge']) || trim($resource['recharge']) === '')) {
                $errors[] = new ValidationError('resources.' . $index . '.recharge', 'Resource recharge values, when supplied, must be non-empty strings.');
            }
        }
        return $errors;
    }

    /** @param array<string,mixed> $spellcasting @return list<ValidationError> */
    private function validateSpellcasting(array $spellcasting): array
    {
        $errors = [];
        if (isset($spellcasting['ability']) && (!is_string($spellcasting['ability']) || trim($spellcasting['ability']) === '')) {
            $errors[] = new ValidationError('spellcasting.ability', 'Spellcasting ability must be a non-empty canonical ability key.');
        }
        if (isset($spellcasting['progression']) && (!is_string($spellcasting['progression']) || trim($spellcasting['progression']) === '')) {
            $errors[] = new ValidationError('spellcasting.progression', 'Spellcasting progression must be a non-empty string.');
        }
        if (isset($spellcasting['prepares_spells']) && !is_bool($spellcasting['prepares_spells'])) {
            $errors[] = new ValidationError('spellcasting.prepares_spells', 'Spell preparation flag must be boolean.');
        }
        if (isset($spellcasting['spell_lists']) && is_array($spellcasting['spell_lists'])) {
            $errors = array_merge($errors, $this->validateStringList('spellcasting.spell_lists', $spellcasting['spell_lists']));
        } elseif (isset($spellcasting['spell_lists'])) {
            $errors[] = new ValidationError('spellcasting.spell_lists', 'Spell lists must be a list of canonical keys.');
        }
        return $errors;
    }

    /** @param array<string,mixed> $selection @return list<ValidationError> */
    private function validateSubclassSelection(array $selection): array
    {
        $errors = [];
        if (!isset($selection['level']) || !is_int($selection['level']) || $selection['level'] < 1) {
            $errors[] = new ValidationError('subclass_selection.level', 'Subclass selection requires a positive level.');
        }
        if (isset($selection['label']) && (!is_string($selection['label']) || trim($selection['label']) === '')) {
            $errors[] = new ValidationError('subclass_selection.label', 'Subclass selection label must be a non-empty string.');
        }
        return $errors;
    }

    /** @param array<string,mixed> $groups @return list<ValidationError> */
    private function validateGroupedLists(string $field, array $groups): array
    {
        $errors = [];
        foreach ($groups as $kind => $values) {
            if (!is_array($values) || !array_is_list($values)) {
                $errors[] = new ValidationError($field . '.' . $kind, 'Grouped values must be lists of canonical keys.');
                continue;
            }
            $errors = array_merge($errors, $this->validateStringList($field . '.' . $kind, $values));
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
                $errors[] = new ValidationError($field . '.' . $index, sprintf('Entries in "%s" must be non-empty strings.', $field));
            }
        }
        return $errors;
    }
}
