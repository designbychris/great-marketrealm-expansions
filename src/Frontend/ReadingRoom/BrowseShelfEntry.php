<?php
namespace GreatMarketrealmExpansions\Frontend\ReadingRoom;

defined('ABSPATH') || exit;

final class BrowseShelfEntry
{
    /**
     * @param array<string,int> $contentTypes
     */
    public function __construct(
        private string $key,
        private string $name,
        private string $version,
        private string $description,
        private bool $active,
        private string $compatibilityStatus,
        private int $entryCount,
        private array $contentTypes,
        private array $metadata = []
    ) {}

    public function key(): string { return $this->key; }
    public function name(): string { return $this->name; }
    public function version(): string { return $this->version; }
    public function description(): string { return $this->description; }
    public function active(): bool { return $this->active; }
    public function compatibilityStatus(): string { return $this->compatibilityStatus; }
    public function entryCount(): int { return $this->entryCount; }

    /** @return array<string,int> */
    public function contentTypes(): array { return $this->contentTypes; }
    /** @return array<string,mixed> */
    public function metadata(): array { return $this->metadata; }
    public function meta(string $key, mixed $default = null): mixed { return $this->metadata[$key] ?? $default; }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'version' => $this->version,
            'description' => $this->description,
            'active' => $this->active,
            'compatibility_status' => $this->compatibilityStatus,
            'entry_count' => $this->entryCount,
            'content_types' => $this->contentTypes,
            'metadata' => $this->metadata,
        ];
    }
}
