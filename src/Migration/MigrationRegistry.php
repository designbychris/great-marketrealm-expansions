<?php
namespace GreatMarketrealmExpansions\Migration;

defined('ABSPATH') || exit;

use InvalidArgumentException;

final class MigrationRegistry
{
    /** @var array<string,MigrationStep> */
    private array $steps = [];

    public function add(MigrationStep $step): void
    {
        if (isset($this->steps[$step->id()])) {
            throw new InvalidArgumentException(sprintf('Migration step "%s" is already registered.', $step->id()));
        }

        foreach ($this->steps as $existing) {
            if (
                $existing->contentType() === $step->contentType()
                && version_compare($existing->fromVersion(), $step->fromVersion(), '==')
                && version_compare($existing->toVersion(), $step->toVersion(), '==')
            ) {
                throw new InvalidArgumentException(sprintf(
                    'Migration route "%s" %s -> %s is already registered.',
                    $step->contentType(),
                    $step->fromVersion(),
                    $step->toVersion()
                ));
            }
        }

        $this->steps[$step->id()] = $step;
    }

    public function has(string $id): bool
    {
        return isset($this->steps[$this->canonical($id)]);
    }

    public function get(string $id): ?MigrationStep
    {
        return $this->steps[$this->canonical($id)] ?? null;
    }

    /** @return list<MigrationStep> */
    public function all(): array
    {
        $steps = array_values($this->steps);
        usort($steps, static fn (MigrationStep $a, MigrationStep $b): int => $a->id() <=> $b->id());
        return $steps;
    }

    /** @return list<MigrationStep> */
    public function forType(string $contentType): array
    {
        $type = $this->canonical($contentType);
        $steps = array_values(array_filter(
            $this->steps,
            static fn (MigrationStep $step): bool => $step->contentType() === $type
        ));

        usort($steps, static function (MigrationStep $a, MigrationStep $b): int {
            $from = version_compare($a->fromVersion(), $b->fromVersion());
            if ($from !== 0) {
                return $from;
            }
            $to = version_compare($a->toVersion(), $b->toVersion());
            return $to !== 0 ? $to : ($a->id() <=> $b->id());
        });

        return $steps;
    }

    private function canonical(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_\-]+/', '-', $value) ?? '';
        return trim(preg_replace('/-+/', '-', $value) ?? '', '-');
    }
}
