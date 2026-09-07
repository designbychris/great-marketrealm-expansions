<?php
namespace GreatMarketrealmExpansions\Content\Schema\Constraints;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ValidationError;

final class AdventureStructureConstraint implements ContentConstraint
{
    /** @return list<ValidationError> */
    public function validate(ContentDefinition $definition): array
    {
        $data = $definition->data();
        $errors = [];

        if (isset($data['level_range']) && is_array($data['level_range']) && $data['level_range'] !== []) {
            $errors = array_merge($errors, $this->validateLevelRange($data['level_range']));
        }

        if (isset($data['entry_points']) && is_array($data['entry_points'])) {
            $errors = array_merge($errors, $this->validateEntryPoints($data['entry_points']));
        }

        if (isset($data['chapters']) && is_array($data['chapters'])) {
            $errors = array_merge($errors, $this->validateChapters($data['chapters']));
        }

        if (isset($data['appendices']) && is_array($data['appendices'])) {
            $errors = array_merge($errors, $this->validateAppendices($data['appendices']));
        }

        if (isset($data['progression']) && is_array($data['progression']) && $data['progression'] !== []) {
            $errors = array_merge($errors, $this->validateProgression($data['progression']));
        }

        foreach (['references', 'keeper_notes'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $errors = array_merge($errors, $this->validateStringList($field, $data[$field]));
            }
        }

        return $errors;
    }

    /** @param array<string,mixed> $range @return list<ValidationError> */
    private function validateLevelRange(array $range): array
    {
        $errors = [];

        foreach (['minimum', 'maximum'] as $field) {
            if (isset($range[$field]) && (!is_int($range[$field]) || $range[$field] < 1)) {
                $errors[] = new ValidationError('level_range.' . $field, 'Adventure level guidance must be a positive integer.');
            }
        }

        if (
            isset($range['minimum'], $range['maximum'])
            && is_int($range['minimum'])
            && is_int($range['maximum'])
            && $range['minimum'] > $range['maximum']
        ) {
            $errors[] = new ValidationError('level_range.maximum', 'Adventure maximum level cannot be below its minimum level.');
        }

        if (isset($range['notes']) && (!is_string($range['notes']) || trim($range['notes']) === '')) {
            $errors[] = new ValidationError('level_range.notes', 'Adventure level-range notes must be non-empty when supplied.');
        }

        return $errors;
    }

    /** @param list<mixed> $entries @return list<ValidationError> */
    private function validateEntryPoints(array $entries): array
    {
        $errors = [];
        $seen = [];

        foreach ($entries as $index => $entry) {
            if (!is_array($entry) || array_is_list($entry) || $entry === []) {
                $errors[] = new ValidationError('entry_points.' . $index, 'Adventure entry points must be non-empty maps.');
                continue;
            }

            foreach (['key', 'name'] as $field) {
                if (!isset($entry[$field]) || !is_string($entry[$field]) || trim($entry[$field]) === '') {
                    $errors[] = new ValidationError(
                        'entry_points.' . $index . '.' . $field,
                        sprintf('Adventure entry points require a non-empty "%s".', $field)
                    );
                }
            }

            if (isset($entry['key']) && is_string($entry['key']) && trim($entry['key']) !== '') {
                $key = trim($entry['key']);
                if (isset($seen[$key])) {
                    $errors[] = new ValidationError(
                        'entry_points.' . $index . '.key',
                        sprintf('Adventure entry-point key "%s" is duplicated.', $key)
                    );
                }
                $seen[$key] = true;
            }

            foreach (['description', 'chapter', 'scene'] as $field) {
                if (isset($entry[$field]) && (!is_string($entry[$field]) || trim($entry[$field]) === '')) {
                    $errors[] = new ValidationError(
                        'entry_points.' . $index . '.' . $field,
                        sprintf('Adventure entry-point "%s" must be non-empty when supplied.', $field)
                    );
                }
            }

            if (isset($entry['references']) && is_array($entry['references'])) {
                $errors = array_merge(
                    $errors,
                    $this->validateStringList('entry_points.' . $index . '.references', $entry['references'])
                );
            }
        }

        return $errors;
    }

    /** @param list<mixed> $chapters @return list<ValidationError> */
    private function validateChapters(array $chapters): array
    {
        $errors = [];
        $seen = [];
        $orders = [];

        foreach ($chapters as $index => $chapter) {
            if (!is_array($chapter) || array_is_list($chapter) || $chapter === []) {
                $errors[] = new ValidationError('chapters.' . $index, 'Adventure chapters must be non-empty maps.');
                continue;
            }

            $errors = array_merge($errors, $this->validateKeyAndName('chapters.' . $index, $chapter, $seen, 'chapter'));

            if (isset($chapter['order'])) {
                if (!is_int($chapter['order']) || $chapter['order'] < 1) {
                    $errors[] = new ValidationError('chapters.' . $index . '.order', 'Adventure chapter order must be a positive integer.');
                } elseif (isset($orders[$chapter['order']])) {
                    $errors[] = new ValidationError(
                        'chapters.' . $index . '.order',
                        sprintf('Adventure chapter order "%d" is duplicated.', $chapter['order'])
                    );
                } else {
                    $orders[$chapter['order']] = true;
                }
            }

            foreach (['summary', 'description'] as $field) {
                if (isset($chapter[$field]) && (!is_string($chapter[$field]) || trim($chapter[$field]) === '')) {
                    $errors[] = new ValidationError(
                        'chapters.' . $index . '.' . $field,
                        sprintf('Adventure chapter "%s" must be non-empty when supplied.', $field)
                    );
                }
            }

            if (isset($chapter['sections'])) {
                if (!is_array($chapter['sections']) || !array_is_list($chapter['sections']) || $chapter['sections'] === []) {
                    $errors[] = new ValidationError('chapters.' . $index . '.sections', 'Adventure chapter sections must be a non-empty list when supplied.');
                } else {
                    $errors = array_merge(
                        $errors,
                        $this->validateSections('chapters.' . $index . '.sections', $chapter['sections'])
                    );
                }
            }

            $errors = array_merge($errors, $this->validateReferenceContainers('chapters.' . $index, $chapter));
        }

        return $errors;
    }

    /** @param list<mixed> $sections @return list<ValidationError> */
    private function validateSections(string $path, array $sections): array
    {
        $errors = [];
        $seen = [];

        foreach ($sections as $index => $section) {
            if (!is_array($section) || array_is_list($section) || $section === []) {
                $errors[] = new ValidationError($path . '.' . $index, 'Adventure sections must be non-empty maps.');
                continue;
            }

            $errors = array_merge(
                $errors,
                $this->validateKeyAndName($path . '.' . $index, $section, $seen, 'section')
            );

            foreach (['summary', 'description'] as $field) {
                if (isset($section[$field]) && (!is_string($section[$field]) || trim($section[$field]) === '')) {
                    $errors[] = new ValidationError(
                        $path . '.' . $index . '.' . $field,
                        sprintf('Adventure section "%s" must be non-empty when supplied.', $field)
                    );
                }
            }

            if (isset($section['scenes'])) {
                if (!is_array($section['scenes']) || !array_is_list($section['scenes']) || $section['scenes'] === []) {
                    $errors[] = new ValidationError($path . '.' . $index . '.scenes', 'Adventure section scenes must be a non-empty list when supplied.');
                } else {
                    $errors = array_merge(
                        $errors,
                        $this->validateScenes($path . '.' . $index . '.scenes', $section['scenes'])
                    );
                }
            }

            $errors = array_merge($errors, $this->validateReferenceContainers($path . '.' . $index, $section));
        }

        return $errors;
    }

    /** @param list<mixed> $scenes @return list<ValidationError> */
    private function validateScenes(string $path, array $scenes): array
    {
        $errors = [];
        $seen = [];

        foreach ($scenes as $index => $scene) {
            if (!is_array($scene) || array_is_list($scene) || $scene === []) {
                $errors[] = new ValidationError($path . '.' . $index, 'Adventure scenes must be non-empty maps.');
                continue;
            }

            $errors = array_merge(
                $errors,
                $this->validateKeyAndName($path . '.' . $index, $scene, $seen, 'scene')
            );

            foreach (['kind', 'description', 'location'] as $field) {
                if (isset($scene[$field]) && (!is_string($scene[$field]) || trim($scene[$field]) === '')) {
                    $errors[] = new ValidationError(
                        $path . '.' . $index . '.' . $field,
                        sprintf('Adventure scene "%s" must be non-empty when supplied.', $field)
                    );
                }
            }

            $errors = array_merge($errors, $this->validateReferenceContainers($path . '.' . $index, $scene));
        }

        return $errors;
    }

    /**
     * @param array<string,mixed> $entry
     * @param array<string,bool> $seen
     * @return list<ValidationError>
     */
    private function validateKeyAndName(string $path, array $entry, array &$seen, string $label): array
    {
        $errors = [];

        foreach (['key', 'name'] as $field) {
            if (!isset($entry[$field]) || !is_string($entry[$field]) || trim($entry[$field]) === '') {
                $errors[] = new ValidationError(
                    $path . '.' . $field,
                    sprintf('Adventure %s entries require a non-empty "%s".', $label, $field)
                );
            }
        }

        if (isset($entry['key']) && is_string($entry['key']) && trim($entry['key']) !== '') {
            $key = trim($entry['key']);
            if (isset($seen[$key])) {
                $errors[] = new ValidationError(
                    $path . '.key',
                    sprintf('Adventure %s key "%s" is duplicated.', $label, $key)
                );
            }
            $seen[$key] = true;
        }

        return $errors;
    }

    /**
     * Validates canonical references that can appear at chapter, section, or scene level.
     *
     * @param array<string,mixed> $entry
     * @return list<ValidationError>
     */
    private function validateReferenceContainers(string $path, array $entry): array
    {
        $errors = [];

        foreach ([
            'encounters',
            'npcs',
            'monsters',
            'hazards',
            'treasure',
            'conditions',
            'rule_refs',
            'references',
        ] as $field) {
            if (!isset($entry[$field])) {
                continue;
            }

            if (!is_array($entry[$field]) || !array_is_list($entry[$field]) || $entry[$field] === []) {
                $errors[] = new ValidationError(
                    $path . '.' . $field,
                    sprintf('Adventure "%s" references must be a non-empty list when supplied.', $field)
                );
                continue;
            }

            $errors = array_merge(
                $errors,
                $this->validateStringList($path . '.' . $field, $entry[$field])
            );
        }

        return $errors;
    }

    /** @param list<mixed> $appendices @return list<ValidationError> */
    private function validateAppendices(array $appendices): array
    {
        $errors = [];
        $seen = [];

        foreach ($appendices as $index => $appendix) {
            if (!is_array($appendix) || array_is_list($appendix) || $appendix === []) {
                $errors[] = new ValidationError('appendices.' . $index, 'Adventure appendices must be non-empty maps.');
                continue;
            }

            $errors = array_merge(
                $errors,
                $this->validateKeyAndName('appendices.' . $index, $appendix, $seen, 'appendix')
            );

            if (isset($appendix['description']) && (!is_string($appendix['description']) || trim($appendix['description']) === '')) {
                $errors[] = new ValidationError('appendices.' . $index . '.description', 'Adventure appendix descriptions must be non-empty when supplied.');
            }

            $errors = array_merge($errors, $this->validateReferenceContainers('appendices.' . $index, $appendix));
        }

        return $errors;
    }

    /** @param array<string,mixed> $progression @return list<ValidationError> */
    private function validateProgression(array $progression): array
    {
        $errors = [];

        if (isset($progression['mode']) && (!is_string($progression['mode']) || trim($progression['mode']) === '')) {
            $errors[] = new ValidationError('progression.mode', 'Adventure progression mode must be a non-empty canonical key.');
        }

        foreach (['start', 'end'] as $field) {
            if (isset($progression[$field]) && (!is_string($progression[$field]) || trim($progression[$field]) === '')) {
                $errors[] = new ValidationError('progression.' . $field, sprintf('Adventure progression "%s" must be non-empty when supplied.', $field));
            }
        }

        if (isset($progression['branches'])) {
            if (!is_array($progression['branches']) || !array_is_list($progression['branches']) || $progression['branches'] === []) {
                $errors[] = new ValidationError('progression.branches', 'Adventure progression branches must be a non-empty list when supplied.');
            } else {
                $errors = array_merge($errors, $this->validateBranches($progression['branches']));
            }
        }

        return $errors;
    }

    /** @param list<mixed> $branches @return list<ValidationError> */
    private function validateBranches(array $branches): array
    {
        $errors = [];
        $seen = [];

        foreach ($branches as $index => $branch) {
            if (!is_array($branch) || array_is_list($branch) || $branch === []) {
                $errors[] = new ValidationError('progression.branches.' . $index, 'Adventure progression branches must be non-empty maps.');
                continue;
            }

            if (!isset($branch['key']) || !is_string($branch['key']) || trim($branch['key']) === '') {
                $errors[] = new ValidationError('progression.branches.' . $index . '.key', 'Adventure progression branches require a non-empty key.');
            } else {
                $key = trim($branch['key']);
                if (isset($seen[$key])) {
                    $errors[] = new ValidationError(
                        'progression.branches.' . $index . '.key',
                        sprintf('Adventure progression branch key "%s" is duplicated.', $key)
                    );
                }
                $seen[$key] = true;
            }

            foreach (['from', 'to', 'condition'] as $field) {
                if (!isset($branch[$field]) || !is_string($branch[$field]) || trim($branch[$field]) === '') {
                    $errors[] = new ValidationError(
                        'progression.branches.' . $index . '.' . $field,
                        sprintf('Adventure progression branches require a non-empty "%s".', $field)
                    );
                }
            }

            if (isset($branch['rules']) && (!is_array($branch['rules']) || !array_is_list($branch['rules']))) {
                $errors[] = new ValidationError('progression.branches.' . $index . '.rules', 'Adventure progression branch rules must be a list.');
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
