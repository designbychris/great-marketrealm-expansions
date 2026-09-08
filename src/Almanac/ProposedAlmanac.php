<?php
namespace GreatMarketrealmExpansions\Almanac;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Import\SourceDocument;
use InvalidArgumentException;

final class ProposedAlmanac
{
    /** @param list<ContentDefinition> $definitions @param array<string,mixed> $metadata */
    public function __construct(
        private string $key,
        private string $name,
        private string $version,
        private string $description,
        private SourceDocument $source,
        private array $definitions,
        private int $pendingCount,
        private int $rejectedCount,
        private array $metadata = []
    ) {
        $this->key = self::normalizeKey($this->key);
        $this->name = trim($this->name);
        $this->version = trim($this->version);
        $this->description = trim($this->description);
        if ($this->key === '' || $this->name === '' || $this->version === '') {
            throw new InvalidArgumentException('A proposed Almanac requires a key, name, and version.');
        }
    }

    public function key(): string { return $this->key; }
    public function name(): string { return $this->name; }
    public function version(): string { return $this->version; }
    public function description(): string { return $this->description; }
    public function source(): SourceDocument { return $this->source; }
    /** @return list<ContentDefinition> */
    public function definitions(): array { return $this->definitions; }
    public function pendingCount(): int { return $this->pendingCount; }
    public function rejectedCount(): int { return $this->rejectedCount; }
    public function definitionCount(): int { return count($this->definitions); }
    public function completeReview(): bool { return $this->pendingCount === 0; }
    /** @return array<string,mixed> */
    public function metadata(): array { return $this->metadata; }

    /** @return array<string,int> */
    public function contentTypes(): array
    {
        $counts = [];
        foreach ($this->definitions as $definition) {
            $counts[$definition->type()] = ($counts[$definition->type()] ?? 0) + 1;
        }
        ksort($counts);
        return $counts;
    }

    /** @return array<string,mixed> */
    public function manifest(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'version' => $this->version,
            'description' => $this->description,
        ] + $this->metadata;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'proposal_version' => '1.0.0',
            'manifest' => $this->manifest(),
            'source' => $this->source->toArray(),
            'definition_count' => $this->definitionCount(),
            'pending_count' => $this->pendingCount,
            'rejected_count' => $this->rejectedCount,
            'complete_review' => $this->completeReview(),
            'content_types' => $this->contentTypes(),
            'definitions' => array_map(
                static fn (ContentDefinition $definition): array => [
                    'type' => $definition->type(),
                    'key' => $definition->key(),
                    'data' => $definition->data(),
                ],
                $this->definitions
            ),
        ];
    }

    private static function normalizeKey(string $key): string
    {
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9_\-]+/', '-', $key) ?? '';
        return trim(preg_replace('/-+/', '-', $key) ?? '', '-');
    }
}
