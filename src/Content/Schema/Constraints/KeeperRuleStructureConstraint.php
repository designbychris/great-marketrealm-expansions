<?php
namespace GreatMarketrealmExpansions\Content\Schema\Constraints;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ValidationError;

final class KeeperRuleStructureConstraint implements ContentConstraint
{
    /** @return list<ValidationError> */
    public function validate(ContentDefinition $definition): array
    {
        $data = $definition->data();
        $errors = [];

        if (isset($data['scope']) && is_array($data['scope']) && $data['scope'] !== []) {
            foreach (['targets', 'contexts', 'content_types', 'references'] as $field) {
                if (!isset($data['scope'][$field])) {
                    continue;
                }

                if (!is_array($data['scope'][$field]) || !array_is_list($data['scope'][$field]) || $data['scope'][$field] === []) {
                    $errors[] = new ValidationError('scope.' . $field, sprintf('Rule scope "%s" must be a non-empty list.', $field));
                    continue;
                }

                $errors = array_merge($errors, $this->validateStringList('scope.' . $field, $data['scope'][$field]));
            }
        }

        if (isset($data['activation']) && is_array($data['activation']) && $data['activation'] !== []) {
            if (isset($data['activation']['mode']) && (!is_string($data['activation']['mode']) || trim($data['activation']['mode']) === '')) {
                $errors[] = new ValidationError('activation.mode', 'Rule activation mode must be a non-empty canonical key.');
            }

            if (isset($data['activation']['enabled_by_default']) && !is_bool($data['activation']['enabled_by_default'])) {
                $errors[] = new ValidationError('activation.enabled_by_default', 'Rule activation enabled-by-default flag must be boolean.');
            }

            if (isset($data['activation']['exclusive_group']) && (!is_string($data['activation']['exclusive_group']) || trim($data['activation']['exclusive_group']) === '')) {
                $errors[] = new ValidationError('activation.exclusive_group', 'Rule activation exclusive group must be non-empty when supplied.');
            }
        }

        foreach (['conflicts', 'supersedes', 'references', 'keeper_notes'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $errors = array_merge($errors, $this->validateStringList($field, $data[$field]));
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
