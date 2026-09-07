<?php
namespace GreatMarketrealmExpansions\Migration;

defined('ABSPATH') || exit;

final class MigrationIssue
{
    public function __construct(
        private string $code,
        private string $message,
        private ?string $stepId = null,
        private ?string $field = null
    ) {}

    public function code(): string { return $this->code; }
    public function message(): string { return $this->message; }
    public function stepId(): ?string { return $this->stepId; }
    public function field(): ?string { return $this->field; }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'step_id' => $this->stepId,
            'field' => $this->field,
        ];
    }
}
