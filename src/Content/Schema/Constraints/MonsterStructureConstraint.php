<?php
namespace GreatMarketrealmExpansions\Content\Schema\Constraints;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ValidationError;

final class MonsterStructureConstraint implements ContentConstraint
{
    /** @return list<ValidationError> */
    public function validate(ContentDefinition $definition): array
    {
        $data = $definition->data();
        $errors = [];

        foreach (['size', 'creature_type', 'alignment'] as $field) {
            if (isset($data[$field]) && is_string($data[$field]) && trim($data[$field]) === '') {
                $errors[] = new ValidationError($field, sprintf('"%s" must be a non-empty canonical key or description.', $field));
            }
        }

        if (isset($data['proficiency_bonus']) && is_int($data['proficiency_bonus']) && $data['proficiency_bonus'] < 0) {
            $errors[] = new ValidationError('proficiency_bonus', 'Monster proficiency bonus cannot be negative.');
        }

        foreach (['damage_vulnerabilities', 'damage_resistances', 'damage_immunities', 'condition_immunities', 'languages'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $errors = array_merge($errors, $this->validateStringList($field, $data[$field]));
            }
        }

        foreach (['traits', 'actions', 'bonus_actions', 'reactions', 'legendary_actions', 'lair_actions'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $errors = array_merge($errors, $this->validateNamedEntries($field, $data[$field]));
            }
        }

        if (isset($data['armour_class']) && is_array($data['armour_class']) && $data['armour_class'] !== []) {
            $errors = array_merge($errors, $this->validateArmourClass($data['armour_class']));
        }

        if (isset($data['hit_points']) && is_array($data['hit_points']) && $data['hit_points'] !== []) {
            $errors = array_merge($errors, $this->validateHitPoints($data['hit_points']));
        }

        if (isset($data['speed']) && is_array($data['speed']) && $data['speed'] !== []) {
            $errors = array_merge($errors, $this->validateSpeed($data['speed']));
        }

        if (isset($data['abilities']) && is_array($data['abilities']) && $data['abilities'] !== []) {
            $errors = array_merge($errors, $this->validateAbilities($data['abilities']));
        }

        foreach (['saving_throws', 'skills'] as $field) {
            if (isset($data[$field]) && is_array($data[$field]) && $data[$field] !== []) {
                $errors = array_merge($errors, $this->validateNumericMap($field, $data[$field]));
            }
        }

        if (isset($data['senses']) && is_array($data['senses']) && $data['senses'] !== []) {
            $errors = array_merge($errors, $this->validateSenses($data['senses']));
        }

        if (isset($data['challenge']) && is_array($data['challenge']) && $data['challenge'] !== []) {
            $errors = array_merge($errors, $this->validateChallenge($data['challenge']));
        }

        return $errors;
    }

    /** @param array<string,mixed> $ac @return list<ValidationError> */
    private function validateArmourClass(array $ac): array
    {
        $errors = [];
        if (!isset($ac['value']) || !is_int($ac['value']) || $ac['value'] < 0) {
            $errors[] = new ValidationError('armour_class.value', 'Monster armour class requires a non-negative integer value.');
        }
        if (isset($ac['type']) && (!is_string($ac['type']) || trim($ac['type']) === '')) {
            $errors[] = new ValidationError('armour_class.type', 'Armour class type must be a non-empty canonical key or description.');
        }
        return $errors;
    }

    /** @param array<string,mixed> $hp @return list<ValidationError> */
    private function validateHitPoints(array $hp): array
    {
        $errors = [];
        if (!isset($hp['average']) || !is_int($hp['average']) || $hp['average'] < 1) {
            $errors[] = new ValidationError('hit_points.average', 'Monster hit points require a positive integer average.');
        }
        if (isset($hp['formula']) && (!is_string($hp['formula']) || trim($hp['formula']) === '')) {
            $errors[] = new ValidationError('hit_points.formula', 'Hit-point formula must be a non-empty dice expression.');
        }
        return $errors;
    }

    /** @param array<string,mixed> $speed @return list<ValidationError> */
    private function validateSpeed(array $speed): array
    {
        $errors = [];
        foreach ($speed as $mode => $value) {
            if ($mode === 'hover') {
                if (!is_bool($value)) {
                    $errors[] = new ValidationError('speed.hover', 'Hover speed flag must be boolean.');
                }
                continue;
            }
            if (!is_int($value) || $value < 0) {
                $errors[] = new ValidationError('speed.' . $mode, 'Monster speed distances must be non-negative integers.');
            }
        }
        return $errors;
    }

    /** @param array<string,mixed> $abilities @return list<ValidationError> */
    private function validateAbilities(array $abilities): array
    {
        $errors = [];
        foreach (['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'] as $ability) {
            if (!array_key_exists($ability, $abilities)) {
                $errors[] = new ValidationError('abilities.' . $ability, sprintf('Monster abilities require "%s".', $ability));
                continue;
            }
            if (!is_int($abilities[$ability]) || $abilities[$ability] < 0) {
                $errors[] = new ValidationError('abilities.' . $ability, 'Monster ability scores must be non-negative integers.');
            }
        }
        return $errors;
    }

    /** @param array<string,mixed> $values @return list<ValidationError> */
    private function validateNumericMap(string $field, array $values): array
    {
        $errors = [];
        foreach ($values as $key => $value) {
            if (!(is_int($value) || is_float($value))) {
                $errors[] = new ValidationError($field . '.' . $key, sprintf('Values in "%s" must be numeric bonuses.', $field));
            }
        }
        return $errors;
    }

    /** @param array<string,mixed> $senses @return list<ValidationError> */
    private function validateSenses(array $senses): array
    {
        $errors = [];
        foreach ($senses as $sense => $distance) {
            if ($sense === 'passive_perception') {
                if (!is_int($distance) || $distance < 0) {
                    $errors[] = new ValidationError('senses.passive_perception', 'Passive perception must be a non-negative integer.');
                }
                continue;
            }
            if (!is_int($distance) || $distance < 0) {
                $errors[] = new ValidationError('senses.' . $sense, 'Sense distances must be non-negative integers.');
            }
        }
        return $errors;
    }

    /** @param array<string,mixed> $challenge @return list<ValidationError> */
    private function validateChallenge(array $challenge): array
    {
        $errors = [];
        if (!array_key_exists('rating', $challenge) || !(is_int($challenge['rating']) || is_float($challenge['rating']) || is_string($challenge['rating']))) {
            $errors[] = new ValidationError('challenge.rating', 'Challenge requires a numeric or canonical string rating.');
        } elseif (is_string($challenge['rating']) && trim($challenge['rating']) === '') {
            $errors[] = new ValidationError('challenge.rating', 'Challenge rating cannot be empty.');
        }

        if (isset($challenge['xp']) && (!is_int($challenge['xp']) || $challenge['xp'] < 0)) {
            $errors[] = new ValidationError('challenge.xp', 'Challenge XP must be a non-negative integer.');
        }
        return $errors;
    }

    /** @param list<mixed> $values @return list<ValidationError> */
    private function validateStringList(string $field, array $values): array
    {
        $errors = [];
        foreach ($values as $index => $value) {
            if (!is_string($value) || trim($value) === '') {
                $errors[] = new ValidationError($field . '.' . $index, sprintf('Entries in "%s" must be non-empty canonical keys or descriptions.', $field));
            }
        }
        return $errors;
    }

    /** @param list<mixed> $entries @return list<ValidationError> */
    private function validateNamedEntries(string $field, array $entries): array
    {
        $errors = [];
        $keys = [];

        foreach ($entries as $index => $entry) {
            if (!is_array($entry) || array_is_list($entry) || $entry === []) {
                $errors[] = new ValidationError($field . '.' . $index, sprintf('Entries in "%s" must be non-empty maps.', $field));
                continue;
            }

            if (!isset($entry['key']) || !is_string($entry['key']) || trim($entry['key']) === '') {
                $errors[] = new ValidationError($field . '.' . $index . '.key', 'Monster trait/action entries require a non-empty key.');
            } elseif (in_array($entry['key'], $keys, true)) {
                $errors[] = new ValidationError($field . '.' . $index . '.key', sprintf('Duplicate monster entry key "%s".', $entry['key']));
            } else {
                $keys[] = $entry['key'];
            }

            if (!isset($entry['name']) || !is_string($entry['name']) || trim($entry['name']) === '') {
                $errors[] = new ValidationError($field . '.' . $index . '.name', 'Monster trait/action entries require a non-empty name.');
            }

            if (isset($entry['description']) && (!is_string($entry['description']) || trim($entry['description']) === '')) {
                $errors[] = new ValidationError($field . '.' . $index . '.description', 'Monster trait/action descriptions must be non-empty when supplied.');
            }

            if (isset($entry['rules']) && (!is_array($entry['rules']) || !array_is_list($entry['rules']))) {
                $errors[] = new ValidationError($field . '.' . $index . '.rules', 'Monster trait/action rules must be a list.');
            }
        }

        return $errors;
    }
}
