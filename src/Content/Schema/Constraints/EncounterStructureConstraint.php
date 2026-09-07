<?php
namespace GreatMarketrealmExpansions\Content\Schema\Constraints;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ValidationError;

final class EncounterStructureConstraint implements ContentConstraint
{
    /** @return list<ValidationError> */
    public function validate(ContentDefinition $definition): array
    {
        $data = $definition->data();
        $errors = [];

        if (isset($data['participants']) && is_array($data['participants'])) {
            $errors = array_merge($errors, $this->validateParticipants('participants', $data['participants']));
        }

        if (isset($data['waves']) && is_array($data['waves'])) {
            $errors = array_merge($errors, $this->validateWaves($data['waves']));
        }

        if (isset($data['environment']) && is_array($data['environment']) && $data['environment'] !== []) {
            $errors = array_merge($errors, $this->validateEnvironment($data['environment']));
        }

        if (isset($data['objectives']) && is_array($data['objectives'])) {
            $errors = array_merge($errors, $this->validateObjectives($data['objectives']));
        }

        if (isset($data['difficulty']) && is_array($data['difficulty']) && $data['difficulty'] !== []) {
            $errors = array_merge($errors, $this->validateDifficulty($data['difficulty']));
        }

        if (isset($data['rewards']) && is_array($data['rewards'])) {
            $errors = array_merge($errors, $this->validateRewards($data['rewards']));
        }

        if (isset($data['triggers']) && is_array($data['triggers'])) {
            $errors = array_merge($errors, $this->validateTriggers($data['triggers']));
        }

        foreach (['references', 'keeper_notes'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $errors = array_merge($errors, $this->validateStringList($field, $data[$field]));
            }
        }

        return $errors;
    }

    /** @param list<mixed> $participants @return list<ValidationError> */
    private function validateParticipants(string $path, array $participants): array
    {
        $errors = [];

        foreach ($participants as $index => $participant) {
            if (!is_array($participant) || array_is_list($participant) || $participant === []) {
                $errors[] = new ValidationError($path . '.' . $index, 'Encounter participants must be non-empty maps.');
                continue;
            }

            if (!isset($participant['ref']) || !is_string($participant['ref']) || trim($participant['ref']) === '') {
                $errors[] = new ValidationError($path . '.' . $index . '.ref', 'Encounter participants require a canonical content reference.');
            }

            if (isset($participant['quantity']) && (!is_int($participant['quantity']) || $participant['quantity'] < 1)) {
                $errors[] = new ValidationError($path . '.' . $index . '.quantity', 'Encounter participant quantity must be a positive integer.');
            }

            foreach (['role', 'disposition', 'variant', 'notes'] as $field) {
                if (isset($participant[$field]) && (!is_string($participant[$field]) || trim($participant[$field]) === '')) {
                    $errors[] = new ValidationError(
                        $path . '.' . $index . '.' . $field,
                        sprintf('Encounter participant "%s" must be non-empty when supplied.', $field)
                    );
                }
            }

            if (isset($participant['placement'])) {
                if (!is_array($participant['placement']) || array_is_list($participant['placement']) || $participant['placement'] === []) {
                    $errors[] = new ValidationError($path . '.' . $index . '.placement', 'Encounter participant placement must be a non-empty map.');
                }
            }

            if (isset($participant['rules']) && (!is_array($participant['rules']) || !array_is_list($participant['rules']))) {
                $errors[] = new ValidationError($path . '.' . $index . '.rules', 'Encounter participant rules must be a list.');
            }
        }

        return $errors;
    }

    /** @param list<mixed> $waves @return list<ValidationError> */
    private function validateWaves(array $waves): array
    {
        $errors = [];
        $seen = [];

        foreach ($waves as $index => $wave) {
            if (!is_array($wave) || array_is_list($wave) || $wave === []) {
                $errors[] = new ValidationError('waves.' . $index, 'Encounter waves must be non-empty maps.');
                continue;
            }

            foreach (['key', 'name'] as $field) {
                if (!isset($wave[$field]) || !is_string($wave[$field]) || trim($wave[$field]) === '') {
                    $errors[] = new ValidationError(
                        'waves.' . $index . '.' . $field,
                        sprintf('Encounter waves require a non-empty "%s".', $field)
                    );
                }
            }

            if (isset($wave['key']) && is_string($wave['key']) && trim($wave['key']) !== '') {
                $key = trim($wave['key']);
                if (isset($seen[$key])) {
                    $errors[] = new ValidationError('waves.' . $index . '.key', sprintf('Encounter wave key "%s" is duplicated.', $key));
                }
                $seen[$key] = true;
            }

            if (isset($wave['trigger']) && (!is_string($wave['trigger']) || trim($wave['trigger']) === '')) {
                $errors[] = new ValidationError('waves.' . $index . '.trigger', 'Encounter wave trigger must be non-empty when supplied.');
            }

            if (!isset($wave['participants']) || !is_array($wave['participants']) || !array_is_list($wave['participants']) || $wave['participants'] === []) {
                $errors[] = new ValidationError('waves.' . $index . '.participants', 'Encounter waves require a non-empty participant list.');
            } else {
                $errors = array_merge(
                    $errors,
                    $this->validateParticipants('waves.' . $index . '.participants', $wave['participants'])
                );
            }
        }

        return $errors;
    }

    /** @param array<string,mixed> $environment @return list<ValidationError> */
    private function validateEnvironment(array $environment): array
    {
        $errors = [];

        foreach (['lighting', 'weather', 'terrain_notes'] as $field) {
            if (isset($environment[$field]) && (!is_string($environment[$field]) || trim($environment[$field]) === '')) {
                $errors[] = new ValidationError('environment.' . $field, sprintf('Encounter environment "%s" must be non-empty when supplied.', $field));
            }
        }

        foreach (['locations', 'terrain', 'hazards'] as $field) {
            if (isset($environment[$field])) {
                if (!is_array($environment[$field]) || !array_is_list($environment[$field])) {
                    $errors[] = new ValidationError('environment.' . $field, sprintf('Encounter environment "%s" must be a list.', $field));
                    continue;
                }
                $errors = array_merge(
                    $errors,
                    $this->validateStringList('environment.' . $field, $environment[$field])
                );
            }
        }

        if (isset($environment['rules']) && (!is_array($environment['rules']) || !array_is_list($environment['rules']))) {
            $errors[] = new ValidationError('environment.rules', 'Encounter environment rules must be a list.');
        }

        return $errors;
    }

    /** @param list<mixed> $objectives @return list<ValidationError> */
    private function validateObjectives(array $objectives): array
    {
        $errors = [];
        $seen = [];

        foreach ($objectives as $index => $objective) {
            if (!is_array($objective) || array_is_list($objective) || $objective === []) {
                $errors[] = new ValidationError('objectives.' . $index, 'Encounter objectives must be non-empty maps.');
                continue;
            }

            foreach (['key', 'name'] as $field) {
                if (!isset($objective[$field]) || !is_string($objective[$field]) || trim($objective[$field]) === '') {
                    $errors[] = new ValidationError(
                        'objectives.' . $index . '.' . $field,
                        sprintf('Encounter objectives require a non-empty "%s".', $field)
                    );
                }
            }

            if (isset($objective['key']) && is_string($objective['key']) && trim($objective['key']) !== '') {
                $key = trim($objective['key']);
                if (isset($seen[$key])) {
                    $errors[] = new ValidationError('objectives.' . $index . '.key', sprintf('Encounter objective key "%s" is duplicated.', $key));
                }
                $seen[$key] = true;
            }

            foreach (['type', 'description', 'success', 'failure'] as $field) {
                if (isset($objective[$field]) && (!is_string($objective[$field]) || trim($objective[$field]) === '')) {
                    $errors[] = new ValidationError(
                        'objectives.' . $index . '.' . $field,
                        sprintf('Encounter objective "%s" must be non-empty when supplied.', $field)
                    );
                }
            }

            if (isset($objective['rules']) && (!is_array($objective['rules']) || !array_is_list($objective['rules']))) {
                $errors[] = new ValidationError('objectives.' . $index . '.rules', 'Encounter objective rules must be a list.');
            }
        }

        return $errors;
    }

    /** @param array<string,mixed> $difficulty @return list<ValidationError> */
    private function validateDifficulty(array $difficulty): array
    {
        $errors = [];

        if (isset($difficulty['rating']) && !(
            (is_string($difficulty['rating']) && trim($difficulty['rating']) !== '')
            || is_int($difficulty['rating'])
            || is_float($difficulty['rating'])
        )) {
            $errors[] = new ValidationError('difficulty.rating', 'Encounter difficulty rating must be numeric or a non-empty canonical key.');
        }

        if (isset($difficulty['xp_budget']) && (!is_int($difficulty['xp_budget']) || $difficulty['xp_budget'] < 0)) {
            $errors[] = new ValidationError('difficulty.xp_budget', 'Encounter XP budget must be a non-negative integer.');
        }

        if (isset($difficulty['party'])) {
            if (!is_array($difficulty['party']) || array_is_list($difficulty['party']) || $difficulty['party'] === []) {
                $errors[] = new ValidationError('difficulty.party', 'Encounter party guidance must be a non-empty map.');
            } else {
                foreach (['size', 'level'] as $field) {
                    if (isset($difficulty['party'][$field]) && (!is_int($difficulty['party'][$field]) || $difficulty['party'][$field] < 1)) {
                        $errors[] = new ValidationError(
                            'difficulty.party.' . $field,
                            sprintf('Encounter party "%s" must be a positive integer.', $field)
                        );
                    }
                }
            }
        }

        return $errors;
    }

    /** @param list<mixed> $rewards @return list<ValidationError> */
    private function validateRewards(array $rewards): array
    {
        $errors = [];
        foreach ($rewards as $index => $reward) {
            if (is_string($reward)) {
                if (trim($reward) === '') {
                    $errors[] = new ValidationError('rewards.' . $index, 'Encounter reward references cannot be empty.');
                }
                continue;
            }

            if (!is_array($reward) || array_is_list($reward) || $reward === []) {
                $errors[] = new ValidationError('rewards.' . $index, 'Encounter rewards must be canonical references or non-empty maps.');
                continue;
            }

            if (!isset($reward['type']) || !is_string($reward['type']) || trim($reward['type']) === '') {
                $errors[] = new ValidationError('rewards.' . $index . '.type', 'Structured encounter rewards require a non-empty type.');
            }

            if (isset($reward['quantity']) && (!is_int($reward['quantity']) || $reward['quantity'] < 1)) {
                $errors[] = new ValidationError('rewards.' . $index . '.quantity', 'Encounter reward quantity must be a positive integer.');
            }
        }
        return $errors;
    }

    /** @param list<mixed> $triggers @return list<ValidationError> */
    private function validateTriggers(array $triggers): array
    {
        $errors = [];
        $seen = [];

        foreach ($triggers as $index => $trigger) {
            if (!is_array($trigger) || array_is_list($trigger) || $trigger === []) {
                $errors[] = new ValidationError('triggers.' . $index, 'Encounter triggers must be non-empty maps.');
                continue;
            }

            foreach (['key', 'when'] as $field) {
                if (!isset($trigger[$field]) || !is_string($trigger[$field]) || trim($trigger[$field]) === '') {
                    $errors[] = new ValidationError(
                        'triggers.' . $index . '.' . $field,
                        sprintf('Encounter triggers require a non-empty "%s".', $field)
                    );
                }
            }

            if (isset($trigger['key']) && is_string($trigger['key']) && trim($trigger['key']) !== '') {
                $key = trim($trigger['key']);
                if (isset($seen[$key])) {
                    $errors[] = new ValidationError('triggers.' . $index . '.key', sprintf('Encounter trigger key "%s" is duplicated.', $key));
                }
                $seen[$key] = true;
            }

            if (isset($trigger['rules']) && (!is_array($trigger['rules']) || !array_is_list($trigger['rules']))) {
                $errors[] = new ValidationError('triggers.' . $index . '.rules', 'Encounter trigger rules must be a list.');
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
