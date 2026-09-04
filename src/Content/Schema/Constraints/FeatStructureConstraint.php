<?php
namespace GreatMarketrealmExpansions\Content\Schema\Constraints;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ValidationError;

final class FeatStructureConstraint implements ContentConstraint
{
    /** @return list<ValidationError> */
    public function validate(ContentDefinition $definition): array
    {
        $data = $definition->data();
        $errors = [];

        foreach (['prerequisites', 'grants', 'choices', 'modifiers', 'ability_score_rules'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $errors = array_merge($errors, $this->validateMapList($field, $data[$field]));
            }
        }

        if (isset($data['max_selections']) && is_int($data['max_selections']) && $data['max_selections'] < 1) {
            $errors[] = new ValidationError('max_selections', 'Feat maximum selections must be a positive integer.');
        }

        if (
            isset($data['repeatable'], $data['max_selections'])
            && is_bool($data['repeatable'])
            && is_int($data['max_selections'])
            && $data['repeatable'] === false
            && $data['max_selections'] > 1
        ) {
            $errors[] = new ValidationError('max_selections', 'A non-repeatable feat cannot allow more than one selection.');
        }

        return $errors;
    }

    /** @param list<mixed> $values @return list<ValidationError> */
    private function validateMapList(string $field, array $values): array
    {
        $errors = [];

        foreach ($values as $index => $value) {
            if (!is_array($value) || array_is_list($value) || $value === []) {
                $errors[] = new ValidationError(
                    $field . '.' . $index,
                    sprintf('Entries in "%s" must be non-empty maps.', $field)
                );
            }
        }

        return $errors;
    }
}
