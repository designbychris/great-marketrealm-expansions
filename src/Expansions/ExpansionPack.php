<?php
namespace GreatMarketrealmExpansions\Expansions;

defined('ABSPATH') || exit;

use InvalidArgumentException;

final class ExpansionPack
{
    private string $key;

    public function __construct(
        string $key,
        private string $name,
        private string $version = '1.0.0',
        private string $description = ''
    ) {
        $this->key = self::normalizeKey($key);
        $this->name = trim($name);
        $this->version = trim($version);
        $this->description = trim($description);

        if ($this->key === '') {
            throw new InvalidArgumentException('An expansion pack must have a valid key.');
        }
        if ($this->name === '') {
            throw new InvalidArgumentException('An expansion pack must have a name.');
        }
        if ($this->version === '') {
            throw new InvalidArgumentException('An expansion pack must have a version.');
        }
    }

    public function key(): string { return $this->key; }
    public function name(): string { return $this->name; }
    public function version(): string { return $this->version; }
    public function description(): string { return $this->description; }

    private static function normalizeKey(string $key): string
    {
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9_\-]+/', '-', $key) ?? '';
        return trim(preg_replace('/-+/', '-', $key) ?? '', '-');
    }
}
