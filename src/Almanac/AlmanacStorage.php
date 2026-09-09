<?php
namespace GreatMarketrealmExpansions\Almanac;

defined('ABSPATH') || exit;

use RuntimeException;

/**
 * Separates immutable plugin-bundled Almanacs from Keeper-published runtime data.
 *
 * Bundled Almanacs travel with plugin deployments. Keeper Almanacs live beneath
 * WordPress uploads so replacing the plugin cannot remove published books.
 */
final class AlmanacStorage
{
    /** @param array{basedir:string,baseurl:string}|null $uploadsOverride */
    public function __construct(private ?array $uploadsOverride = null) {}

    public const API_VERSION = '1.0.0';
    private const DIRECTORY = 'great-marketrealm-expansions/almanacs';

    public function bundledRoot(): string
    {
        if (!defined('GMREXP_PATH')) {
            throw new RuntimeException('The bundled Almanac root is unavailable.');
        }
        return rtrim(GMREXP_PATH, '/\\') . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . 'expansions';
    }

    public function keeperRoot(): string
    {
        $uploads = $this->uploads();
        return rtrim($uploads['basedir'], '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::DIRECTORY);
    }

    public function ensureKeeperRoot(): string
    {
        $root = $this->keeperRoot();
        if (!is_dir($root) && !@mkdir($root, 0775, true) && !is_dir($root)) {
            throw new RuntimeException('The persistent Keeper Almanac library could not be created.');
        }
        return $root;
    }

    public function keeperPackExists(string $key): bool
    {
        return is_dir($this->keeperRoot() . DIRECTORY_SEPARATOR . $this->safeKey($key));
    }

    public function bundledPackExists(string $key): bool
    {
        return is_dir($this->bundledRoot() . DIRECTORY_SEPARATOR . $this->safeKey($key));
    }

    public function packRoot(string $key): ?string
    {
        $key = $this->safeKey($key);
        $keeper = $this->keeperRoot() . DIRECTORY_SEPARATOR . $key;
        if (is_dir($keeper)) { return $keeper; }
        $bundled = $this->bundledRoot() . DIRECTORY_SEPARATOR . $key;
        return is_dir($bundled) ? $bundled : null;
    }

    public function isKeeperPublished(string $key): bool
    {
        return $this->keeperPackExists($key);
    }

    public function artworkUrl(string $key, string $relative): ?string
    {
        $key = $this->safeKey($key);
        $relative = trim(str_replace('\\', '/', $relative));
        if ($relative === '' || str_starts_with($relative, '/') || str_contains($relative, '..') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $relative)) { return null; }
        $segments = array_map('rawurlencode', array_values(array_filter(explode('/', $relative), static fn (string $part): bool => $part !== '')));
        if ($this->keeperPackExists($key)) {
            $uploads = $this->uploads();
            return rtrim($uploads['baseurl'], '/') . '/' . self::DIRECTORY . '/' . rawurlencode($key) . '/' . implode('/', $segments);
        }
        if ($this->bundledPackExists($key) && function_exists('plugins_url') && defined('GMREXP_FILE')) {
            return plugins_url('content/expansions/' . rawurlencode($key) . '/' . implode('/', $segments), GMREXP_FILE);
        }
        return null;
    }

    /** @return array{basedir:string,baseurl:string} */
    private function uploads(): array
    {
        if ($this->uploadsOverride !== null) { return $this->uploadsOverride; }
        if (!function_exists('wp_upload_dir')) {
            throw new RuntimeException('WordPress uploads are unavailable for Keeper-published Almanacs.');
        }
        $uploads = wp_upload_dir(null, false);
        if (!is_array($uploads) || !empty($uploads['error']) || empty($uploads['basedir']) || empty($uploads['baseurl'])) {
            throw new RuntimeException('WordPress could not provide a persistent uploads directory for Keeper-published Almanacs.');
        }
        return ['basedir' => (string) $uploads['basedir'], 'baseurl' => (string) $uploads['baseurl']];
    }

    private function safeKey(string $key): string
    {
        $key = strtolower(trim($key));
        if ($key === '' || !preg_match('/^[a-z0-9][a-z0-9_-]*$/', $key)) {
            throw new RuntimeException('The Almanac key is not safe for storage.');
        }
        return $key;
    }
}
