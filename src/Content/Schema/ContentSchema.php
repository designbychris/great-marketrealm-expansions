<?php
namespace GreatMarketrealmExpansions\Content\Schema;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use InvalidArgumentException;

final class ContentSchema
{
    /** @var array<string, FieldDefinition> */
    private array $fields = [];

    /** @param list<FieldDefinition> $fields */
    public function __construct(private string $type, array $fields = [])
    {
        $this->type = trim($this->type);
        if ($this->type === '') {
            throw new InvalidArgumentException('Content schemas require a content type.');
        }
        foreach ($fields as $field) { $this->addField($field); }
    }

    public function type(): string { return $this->type; }

    public function addField(FieldDefinition $field): void
    {
        $this->fields[$field->name()] = $field;
    }

    /** @return array<string, FieldDefinition> */
    public function fields(): array { return $this->fields; }

    public function validate(ContentDefinition $definition): ValidationResult
    {
        $errors = [];
        if ($definition->type() !== $this->type) {
            $errors[] = new ValidationError('type', sprintf('Expected content type "%s"; got "%s".', $this->type, $definition->type()));
            return new ValidationResult($errors);
        }

        $data = $definition->data();
        foreach ($this->fields as $field) {
            $exists = array_key_exists($field->name(), $data);
            if (!$exists) {
                if ($field->required()) {
                    $errors[] = new ValidationError($field->name(), sprintf('Field "%s" is required.', $field->name()));
                }
                continue;
            }
            if (!$field->accepts($data[$field->name()])) {
                $errors[] = new ValidationError($field->name(), sprintf('Field "%s" must be a non-empty %s.', $field->name(), $field->type()));
            }
        }
        return new ValidationResult($errors);
    }
}
