<?php
namespace GreatMarketrealmExpansions\Migration;

defined('ABSPATH') || exit;

use Closure;
use InvalidArgumentException;

final class MigrationStep
{
    private Closure $transformer;

    /**
     * @param callable(array<string,mixed>):array<string,mixed> $transformer
     */
    public function __construct(
        private string $id,
        private string $contentType,
        private string $fromVersion,
        private string $toVersion,
        callable $transformer
    ) {
        $this->id = $this->canonical($this->id);
        $this->contentType = $this->canonical($this->contentType);
        $this->fromVersion = trim($this->fromVersion);
        $this->toVersion = trim($this->toVersion);

        if ($this->id === '' || $this->contentType === '' || $this->fromVersion === '' || $this->toVersion === '') {
            throw new InvalidArgumentException('Migration steps require id, content type, from version, and to version.');
        }

        if (version_compare($this->toVersion, $this->fromVersion, '<=')) {
            throw new InvalidArgumentException('Migration steps must move strictly forward in version order.');
        }

        $this->transformer = Closure::fromCallable($transformer);
    }

    public function id(): string { return $this->id; }
    public function contentType(): string { return $this->contentType; }
    public function fromVersion(): string { return $this->fromVersion; }
    public function toVersion(): string { return $this->toVersion; }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    public function transform(array $data): array
    {
        return ($this->transformer)($data);
    }

    private function canonical(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_\-]+/', '-', $value) ?? '';
        return trim(preg_replace('/-+/', '-', $value) ?? '', '-');
    }
}
