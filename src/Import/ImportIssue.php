<?php
namespace GreatMarketrealmExpansions\Import;

defined('ABSPATH') || exit;

final class ImportIssue
{
    public const WARNING = 'warning';
    public const ERROR = 'error';

    public function __construct(
        private string $severity,
        private string $code,
        private string $message,
        private ?string $recordId = null,
        private ?string $field = null
    ) {}

    public function severity(): string { return $this->severity; }
    public function code(): string { return $this->code; }
    public function message(): string { return $this->message; }
    public function recordId(): ?string { return $this->recordId; }
    public function field(): ?string { return $this->field; }
    public function error(): bool { return $this->severity === self::ERROR; }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'severity' => $this->severity,
            'code' => $this->code,
            'message' => $this->message,
            'record_id' => $this->recordId,
            'field' => $this->field,
        ];
    }
}
