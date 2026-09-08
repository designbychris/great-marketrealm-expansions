<?php
namespace GreatMarketrealmExpansions\Import;

defined('ABSPATH') || exit;

final class GoogleDocsAcquisition
{
    /** @param array<string,mixed>|null $document */
    public function __construct(
        private ?GoogleDocReference $reference,
        private ?array $document,
        private ?ImportIssue $issue = null
    ) {}

    /** @param array<string,mixed> $document */
    public static function success(GoogleDocReference $reference, array $document): self
    {
        return new self($reference, $document);
    }

    public static function failure(?GoogleDocReference $reference, ImportIssue $issue): self
    {
        return new self($reference, null, $issue);
    }

    public function successful(): bool { return $this->document !== null && $this->issue === null; }
    public function reference(): ?GoogleDocReference { return $this->reference; }
    /** @return array<string,mixed>|null */
    public function document(): ?array { return $this->document; }
    public function issue(): ?ImportIssue { return $this->issue; }
    public function json(): ?string
    {
        if ($this->document === null) { return null; }
        $json = json_encode($this->document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return is_string($json) ? $json : null;
    }
}
