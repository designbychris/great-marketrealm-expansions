<?php
namespace GreatMarketrealmExpansions\Import;

defined('ABSPATH') || exit;

use InvalidArgumentException;

final class SourceDocument
{
    /** @param array<string,mixed> $metadata */
    public function __construct(
        private string $type,
        private string $id,
        private string $title,
        private ?string $version = null,
        private array $metadata = []
    ) {
        $this->type = $this->canonical($this->type);
        $this->id = trim($this->id);
        $this->title = trim($this->title);
        $this->version = $this->version === null ? null : trim($this->version);

        if ($this->type === '' || $this->id === '' || $this->title === '') {
            throw new InvalidArgumentException('Import sources require non-empty type, id, and title.');
        }
    }

    public function type(): string { return $this->type; }
    public function id(): string { return $this->id; }
    public function title(): string { return $this->title; }
    public function version(): ?string { return $this->version; }
    /** @return array<string,mixed> */
    public function metadata(): array { return $this->metadata; }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
            'title' => $this->title,
            'version' => $this->version,
            'metadata' => $this->metadata,
        ];
    }

    private function canonical(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_\-]+/', '-', $value) ?? '';
        return trim(preg_replace('/-+/', '-', $value) ?? '', '-');
    }
}
