<?php
namespace GreatMarketrealmExpansions\Library;

defined('ABSPATH') || exit;

final class CompatibilityIssue
{
    public const WARNING = 'warning';
    public const BLOCKING = 'blocking';

    public function __construct(
        private string $severity,
        private string $code,
        private string $message,
        private ?string $subject = null
    ) {}

    public function severity(): string { return $this->severity; }
    public function code(): string { return $this->code; }
    public function message(): string { return $this->message; }
    public function subject(): ?string { return $this->subject; }
    public function blocking(): bool { return $this->severity === self::BLOCKING; }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'severity' => $this->severity,
            'code' => $this->code,
            'message' => $this->message,
            'subject' => $this->subject,
        ];
    }
}
