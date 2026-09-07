<?php
namespace GreatMarketrealmExpansions\Content\Schema\Constraints;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ValidationError;

final class NpcStructureConstraint implements ContentConstraint
{
    /** @return list<ValidationError> */
    public function validate(ContentDefinition $definition): array
    {
        $data = $definition->data();
        $errors = [];

        foreach (['roles', 'locations', 'personality', 'goals', 'secrets', 'mannerisms'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $errors = array_merge($errors, $this->validateStringList($field, $data[$field]));
            }
        }

        if (isset($data['identity']) && is_array($data['identity']) && $data['identity'] !== []) {
            $errors = array_merge($errors, $this->validateIdentity($data['identity']));
        }

        if (isset($data['affiliations']) && is_array($data['affiliations'])) {
            $errors = array_merge($errors, $this->validateAffiliations($data['affiliations']));
        }

        if (isset($data['relationships']) && is_array($data['relationships'])) {
            $errors = array_merge($errors, $this->validateRelationships($data['relationships']));
        }

        if (isset($data['dialogue']) && is_array($data['dialogue'])) {
            $errors = array_merge($errors, $this->validateDialogue($data['dialogue']));
        }

        if (isset($data['lore_hooks']) && is_array($data['lore_hooks'])) {
            $errors = array_merge($errors, $this->validateLoreHooks($data['lore_hooks']));
        }

        if (isset($data['combat']) && is_array($data['combat']) && $data['combat'] !== []) {
            $errors = array_merge($errors, $this->validateCombat($data['combat']));
        }

        return $errors;
    }

    /** @param array<string,mixed> $identity @return list<ValidationError> */
    private function validateIdentity(array $identity): array
    {
        $errors = [];
        $allowed = ['aliases', 'titles', 'pronouns', 'species', 'age'];

        foreach ($identity as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                $errors[] = new ValidationError('identity.' . $key, sprintf('Unknown NPC identity field "%s".', $key));
                continue;
            }

            if (in_array($key, ['aliases', 'titles'], true)) {
                if (!is_array($value) || !array_is_list($value)) {
                    $errors[] = new ValidationError('identity.' . $key, 'NPC identity aliases and titles must be lists.');
                    continue;
                }
                $errors = array_merge($errors, $this->validateStringList('identity.' . $key, $value));
                continue;
            }

            if (!is_string($value) || trim($value) === '') {
                $errors[] = new ValidationError('identity.' . $key, sprintf('NPC identity "%s" must be a non-empty string.', $key));
            }
        }

        return $errors;
    }

    /** @param list<mixed> $affiliations @return list<ValidationError> */
    private function validateAffiliations(array $affiliations): array
    {
        $errors = [];
        foreach ($affiliations as $index => $entry) {
            if (!is_array($entry) || array_is_list($entry) || $entry === []) {
                $errors[] = new ValidationError('affiliations.' . $index, 'NPC affiliations must be non-empty maps.');
                continue;
            }

            if (!isset($entry['target']) || !is_string($entry['target']) || trim($entry['target']) === '') {
                $errors[] = new ValidationError('affiliations.' . $index . '.target', 'NPC affiliations require a canonical target reference.');
            }

            if (isset($entry['role']) && (!is_string($entry['role']) || trim($entry['role']) === '')) {
                $errors[] = new ValidationError('affiliations.' . $index . '.role', 'NPC affiliation role must be a non-empty canonical key.');
            }

            if (isset($entry['notes']) && (!is_string($entry['notes']) || trim($entry['notes']) === '')) {
                $errors[] = new ValidationError('affiliations.' . $index . '.notes', 'NPC affiliation notes must be non-empty when supplied.');
            }
        }
        return $errors;
    }

    /** @param list<mixed> $relationships @return list<ValidationError> */
    private function validateRelationships(array $relationships): array
    {
        $errors = [];
        foreach ($relationships as $index => $entry) {
            if (!is_array($entry) || array_is_list($entry) || $entry === []) {
                $errors[] = new ValidationError('relationships.' . $index, 'NPC relationships must be non-empty maps.');
                continue;
            }

            foreach (['target', 'type'] as $field) {
                if (!isset($entry[$field]) || !is_string($entry[$field]) || trim($entry[$field]) === '') {
                    $errors[] = new ValidationError(
                        'relationships.' . $index . '.' . $field,
                        sprintf('NPC relationships require a non-empty "%s".', $field)
                    );
                }
            }

            foreach (['label', 'notes'] as $field) {
                if (isset($entry[$field]) && (!is_string($entry[$field]) || trim($entry[$field]) === '')) {
                    $errors[] = new ValidationError(
                        'relationships.' . $index . '.' . $field,
                        sprintf('NPC relationship "%s" must be non-empty when supplied.', $field)
                    );
                }
            }
        }
        return $errors;
    }

    /** @param list<mixed> $dialogue @return list<ValidationError> */
    private function validateDialogue(array $dialogue): array
    {
        $errors = [];
        $seen = [];

        foreach ($dialogue as $index => $entry) {
            if (!is_array($entry) || array_is_list($entry) || $entry === []) {
                $errors[] = new ValidationError('dialogue.' . $index, 'NPC dialogue entries must be non-empty maps.');
                continue;
            }

            foreach (['key', 'text'] as $field) {
                if (!isset($entry[$field]) || !is_string($entry[$field]) || trim($entry[$field]) === '') {
                    $errors[] = new ValidationError(
                        'dialogue.' . $index . '.' . $field,
                        sprintf('NPC dialogue entries require a non-empty "%s".', $field)
                    );
                }
            }

            if (isset($entry['key']) && is_string($entry['key']) && trim($entry['key']) !== '') {
                $key = trim($entry['key']);
                if (isset($seen[$key])) {
                    $errors[] = new ValidationError('dialogue.' . $index . '.key', sprintf('NPC dialogue key "%s" is duplicated.', $key));
                }
                $seen[$key] = true;
            }

            if (isset($entry['context']) && (!is_string($entry['context']) || trim($entry['context']) === '')) {
                $errors[] = new ValidationError('dialogue.' . $index . '.context', 'NPC dialogue context must be non-empty when supplied.');
            }
        }

        return $errors;
    }

    /** @param list<mixed> $hooks @return list<ValidationError> */
    private function validateLoreHooks(array $hooks): array
    {
        $errors = [];
        $seen = [];

        foreach ($hooks as $index => $entry) {
            if (!is_array($entry) || array_is_list($entry) || $entry === []) {
                $errors[] = new ValidationError('lore_hooks.' . $index, 'NPC lore hooks must be non-empty maps.');
                continue;
            }

            foreach (['key', 'name'] as $field) {
                if (!isset($entry[$field]) || !is_string($entry[$field]) || trim($entry[$field]) === '') {
                    $errors[] = new ValidationError(
                        'lore_hooks.' . $index . '.' . $field,
                        sprintf('NPC lore hooks require a non-empty "%s".', $field)
                    );
                }
            }

            if (isset($entry['key']) && is_string($entry['key']) && trim($entry['key']) !== '') {
                $key = trim($entry['key']);
                if (isset($seen[$key])) {
                    $errors[] = new ValidationError('lore_hooks.' . $index . '.key', sprintf('NPC lore hook key "%s" is duplicated.', $key));
                }
                $seen[$key] = true;
            }

            foreach (['description', 'trigger'] as $field) {
                if (isset($entry[$field]) && (!is_string($entry[$field]) || trim($entry[$field]) === '')) {
                    $errors[] = new ValidationError(
                        'lore_hooks.' . $index . '.' . $field,
                        sprintf('NPC lore hook "%s" must be non-empty when supplied.', $field)
                    );
                }
            }

            if (isset($entry['references'])) {
                if (!is_array($entry['references']) || !array_is_list($entry['references'])) {
                    $errors[] = new ValidationError('lore_hooks.' . $index . '.references', 'NPC lore-hook references must be a list.');
                } else {
                    $errors = array_merge(
                        $errors,
                        $this->validateStringList('lore_hooks.' . $index . '.references', $entry['references'])
                    );
                }
            }
        }

        return $errors;
    }

    /** @param array<string,mixed> $combat @return list<ValidationError> */
    private function validateCombat(array $combat): array
    {
        $errors = [];

        if (!isset($combat['monster']) || !is_string($combat['monster']) || trim($combat['monster']) === '') {
            $errors[] = new ValidationError('combat.monster', 'NPC combat data requires a canonical monster reference.');
        }

        foreach (['variant', 'notes'] as $field) {
            if (isset($combat[$field]) && (!is_string($combat[$field]) || trim($combat[$field]) === '')) {
                $errors[] = new ValidationError(
                    'combat.' . $field,
                    sprintf('NPC combat "%s" must be non-empty when supplied.', $field)
                );
            }
        }

        if (isset($combat['rules']) && (!is_array($combat['rules']) || !array_is_list($combat['rules']))) {
            $errors[] = new ValidationError('combat.rules', 'NPC combat rules must be a list.');
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
