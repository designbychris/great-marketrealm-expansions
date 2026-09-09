<?php
namespace GreatMarketrealmExpansions\Almanac;

defined('ABSPATH') || exit;

use InvalidArgumentException;
use RuntimeException;

/**
 * Safely correct presentation metadata for an already-published Almanac.
 * Canonical content definitions are never rewritten by this service.
 */
final class AlmanacMetadataService
{
    public const API_VERSION = '1.0.0';
    private const MAX_ARTWORK_BYTES = 10_485_760;

    /**
     * @param array{name:string,version:string,description:string,artwork:string} $changes
     * @param array<string,mixed>|null $artworkUpload
     */
    public function update(string $root, string $key, array $changes, ?array $artworkUpload = null): void
    {
        $root = rtrim($root, '/\\');
        $key = $this->safeSegment($key);
        if ($root === '' || $key === '') { throw new InvalidArgumentException('A published Almanac key is required.'); }
        $directory = $root . DIRECTORY_SEPARATOR . $key;
        $manifestPath = $directory . DIRECTORY_SEPARATOR . 'manifest.php';
        if (!is_dir($directory) || !is_readable($manifestPath)) { throw new RuntimeException('The published Almanac could not be found.'); }

        $manifest = require $manifestPath;
        if (!is_array($manifest) || ($manifest['key'] ?? null) !== $key) { throw new RuntimeException('The published Almanac manifest is invalid.'); }

        $name = trim($changes['name'] ?? '');
        $version = trim($changes['version'] ?? '');
        $description = trim($changes['description'] ?? '');
        $artwork = trim(str_replace('\\', '/', $changes['artwork'] ?? ''));
        if ($name === '' || $version === '') { throw new InvalidArgumentException('Almanac name and version are required.'); }
        if (strlen($description) > 2000) { throw new InvalidArgumentException('Library summary must be 2,000 characters or fewer.'); }
        if ($artwork !== '') { $this->assertArtworkPath($artwork); }

        $oldArtwork = isset($manifest['artwork']) && is_string($manifest['artwork']) ? trim(str_replace('\\', '/', $manifest['artwork'])) : '';
        $newArtworkPath = $artwork === '' ? null : $directory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $artwork);
        $createdArtwork = false;

        if ($this->hasUpload($artworkUpload)) {
            if ($newArtworkPath === null) { throw new InvalidArgumentException('Choose a Library artwork path before attaching replacement artwork.'); }
            $this->writeArtwork($newArtworkPath, $artworkUpload);
            $createdArtwork = true;
        } elseif ($artwork !== '' && $artwork !== $oldArtwork) {
            $oldPath = $oldArtwork === '' ? '' : $directory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $oldArtwork);
            if ($oldPath === '' || !is_file($oldPath) || !is_readable($oldPath)) {
                throw new InvalidArgumentException('Attach artwork when changing to a path that does not already exist in the Almanac pack.');
            }
            $this->ensureDirectory(dirname((string) $newArtworkPath));
            if (!@copy($oldPath, (string) $newArtworkPath)) { throw new RuntimeException('The existing Library artwork could not be moved to its new pack path.'); }
            $createdArtwork = true;
        } elseif ($artwork !== '' && ($newArtworkPath === null || !is_file($newArtworkPath))) {
            throw new InvalidArgumentException('The Library artwork path does not point to an existing image in this Almanac. Attach a replacement image.');
        }

        $manifest['name'] = $name;
        $manifest['version'] = $version;
        $manifest['description'] = $description;
        if ($artwork === '') { unset($manifest['artwork']); } else { $manifest['artwork'] = $artwork; }

        $temporary = $manifestPath . '.correcting-' . bin2hex(random_bytes(5));
        $php = "<?php\nreturn " . var_export($manifest, true) . ";\n";
        if (@file_put_contents($temporary, $php, LOCK_EX) === false || !@rename($temporary, $manifestPath)) {
            @unlink($temporary);
            if ($createdArtwork && $newArtworkPath !== null && $newArtworkPath !== ($oldArtwork === '' ? null : $directory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $oldArtwork))) { @unlink($newArtworkPath); }
            throw new RuntimeException('The corrected Almanac manifest could not be committed atomically.');
        }

        if ($oldArtwork !== '' && $oldArtwork !== $artwork) {
            $oldPath = $directory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $oldArtwork);
            if (is_file($oldPath)) { @unlink($oldPath); }
        }
    }

    private function safeSegment(string $value): string { $value = strtolower(trim($value)); return preg_match('/^[a-z0-9][a-z0-9_-]*$/', $value) ? $value : ''; }
    private function assertArtworkPath(string $path): void
    {
        if (str_starts_with($path, '/') || str_contains($path, '..') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $path)) { throw new InvalidArgumentException('Library artwork must be a safe relative path inside the Almanac pack.'); }
        if (!in_array(strtolower((string) pathinfo($path, PATHINFO_EXTENSION)), ['jpg','jpeg','png','webp','gif'], true)) { throw new InvalidArgumentException('Library artwork must be a JPG, PNG, WEBP, or GIF image.'); }
    }
    /** @param array<string,mixed>|null $upload */
    private function hasUpload(?array $upload): bool { return is_array($upload) && isset($upload['tmp_name']) && is_string($upload['tmp_name']) && $upload['tmp_name'] !== ''; }
    /** @param array<string,mixed>|null $upload */
    private function writeArtwork(string $target, ?array $upload): void
    {
        if (!$this->hasUpload($upload)) { throw new InvalidArgumentException('Attach the replacement Library artwork file.'); }
        $error = (int) ($upload['error'] ?? UPLOAD_ERR_OK); $size = (int) ($upload['size'] ?? 0); $tmp = (string) $upload['tmp_name'];
        if ($error !== UPLOAD_ERR_OK || $size < 1 || $size > self::MAX_ARTWORK_BYTES || !is_readable($tmp)) { throw new InvalidArgumentException('The replacement Library artwork upload is invalid or larger than 10 MB.'); }
        $info = @getimagesize($tmp); if ($info === false || !in_array((int) ($info[2] ?? 0), [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) { throw new InvalidArgumentException('The replacement Library artwork is not a recognised safe image.'); }
        $this->ensureDirectory(dirname($target));
        if (!@copy($tmp, $target)) { throw new RuntimeException('The replacement Library artwork could not be copied into the Almanac pack.'); }
    }
    private function ensureDirectory(string $directory): void { if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) { throw new RuntimeException('The Library artwork directory could not be created.'); } }
}
