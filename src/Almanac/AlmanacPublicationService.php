<?php
namespace GreatMarketrealmExpansions\Almanac;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Expansions\Loading\ExpansionFileLoader;
use Throwable;

final class AlmanacPublicationService
{
    public const API_VERSION = '1.0.0';
    private const MAX_ARTWORK_BYTES = 10_485_760;

    public function __construct(
        private ContentValidator $validator,
        private ExpansionFileLoader $loader
    ) {}

    /**
     * Publish a persisted V.9 proposal into the canonical Almanac root.
     *
     * @param array<string,mixed> $proposal
     * @param array<string,mixed>|null $artworkUpload A single PHP-style uploaded file.
     */
    public function publish(array $proposal, string $root, ?array $artworkUpload = null): AlmanacPublicationResult
    {
        $manifest = isset($proposal['manifest']) && is_array($proposal['manifest']) ? $proposal['manifest'] : [];
        $definitions = isset($proposal['definitions']) && is_array($proposal['definitions']) ? $proposal['definitions'] : [];

        $key = $this->manifestString($manifest, 'key');
        $this->manifestString($manifest, 'name');
        $this->manifestString($manifest, 'version');
        if (empty($proposal['complete_review']) || (int) ($proposal['pending_count'] ?? 0) !== 0) {
            throw new AlmanacPublicationException('The Keeper can publish only after the Review Desk is complete.');
        }
        if ($definitions === []) {
            throw new AlmanacPublicationException('A published Almanac requires at least one approved definition.');
        }

        $root = rtrim($root, '/\\');
        if ($root === '') {
            throw new AlmanacPublicationException('The Almanac publication root is unavailable.');
        }
        if (!is_dir($root) && !@mkdir($root, 0775, true) && !is_dir($root)) {
            throw new AlmanacPublicationException('The Almanac publication root could not be created.');
        }
        if (!is_writable($root)) {
            throw new AlmanacPublicationException('The Almanac publication root is not writable.');
        }

        $final = $root . DIRECTORY_SEPARATOR . $key;
        if (file_exists($final)) {
            throw new AlmanacPublicationException(sprintf('An Almanac with canonical key "%s" is already published.', $key));
        }

        $stage = $root . DIRECTORY_SEPARATOR . '.' . $key . '.publishing-' . bin2hex(random_bytes(6));
        if (!@mkdir($stage, 0775, true)) {
            throw new AlmanacPublicationException('The atomic publication staging directory could not be created.');
        }

        try {
            $this->writePhpArray($stage . DIRECTORY_SEPARATOR . 'manifest.php', $manifest);
            $seen = [];
            foreach ($definitions as $index => $record) {
                if (!is_array($record)) {
                    throw new AlmanacPublicationException(sprintf('Proposed definition %d is malformed.', $index + 1));
                }
                $type = isset($record['type']) && is_string($record['type']) ? $this->safeSegment($record['type']) : '';
                $contentKey = isset($record['key']) && is_string($record['key']) ? $this->safeSegment($record['key']) : '';
                $data = isset($record['data']) && is_array($record['data']) ? $record['data'] : null;
                if ($type === '' || $contentKey === '' || $data === null) {
                    throw new AlmanacPublicationException(sprintf('Proposed definition %d must contain canonical type, key, and data.', $index + 1));
                }
                $identity = $type . ':' . $contentKey;
                if (isset($seen[$identity])) {
                    throw new AlmanacPublicationException(sprintf('The proposal contains duplicate canonical identity "%s".', $identity));
                }
                $seen[$identity] = true;

                $definition = new ContentDefinition($type, $contentKey, $data);
                $validation = $this->validator->validate($definition);
                if (!$validation->valid()) {
                    $messages = array_map(static fn ($error): string => $error->message(), $validation->errors());
                    throw new AlmanacPublicationException(sprintf('Canonical definition "%s" failed publication validation: %s', $identity, implode(' ', $messages)));
                }

                $directory = $stage . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . $type;
                $this->ensureDirectory($directory);
                $this->writePhpArray($directory . DIRECTORY_SEPARATOR . $contentKey . '.php', [
                    'type' => $type,
                    'key' => $contentKey,
                    'data' => $data,
                ]);
            }

            $artwork = isset($manifest['artwork']) && is_string($manifest['artwork']) ? trim(str_replace('\\', '/', $manifest['artwork'])) : '';
            if ($artwork !== '') {
                $this->publishArtwork($stage, $artwork, $artworkUpload);
            } elseif ($this->hasUploadedArtwork($artworkUpload)) {
                throw new AlmanacPublicationException('Choose a Library artwork path in the proposed Almanac before attaching artwork for publication.');
            }

            if (!@rename($stage, $final)) {
                throw new AlmanacPublicationException('The Almanac could not cross the atomic publication boundary. No canonical files were installed.');
            }

            try {
                $loadResult = $this->loader->load($final);
            } catch (Throwable $exception) {
                $this->removeTree($final);
                throw new AlmanacPublicationException('The published files did not pass the canonical Almanac loader and were rolled back: ' . $exception->getMessage(), 0, $exception);
            }

            return new AlmanacPublicationResult($final, $loadResult);
        } catch (Throwable $exception) {
            if (is_dir($stage)) {
                $this->removeTree($stage);
            }
            if ($exception instanceof AlmanacPublicationException) {
                throw $exception;
            }
            throw new AlmanacPublicationException('The Almanac could not be published atomically: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /** @param array<string,mixed> $manifest */
    private function manifestString(array $manifest, string $field): string
    {
        $value = isset($manifest[$field]) && is_string($manifest[$field]) ? trim($manifest[$field]) : '';
        if ($value === '') {
            throw new AlmanacPublicationException(sprintf('Proposed Almanac manifest field "%s" is required.', $field));
        }
        if ($field === 'key' && $this->safeSegment($value) !== $value) {
            throw new AlmanacPublicationException('The proposed Almanac canonical key is not safe for publication.');
        }
        return $value;
    }

    private function safeSegment(string $value): string
    {
        $value = strtolower(trim($value));
        if ($value === '' || !preg_match('/^[a-z0-9][a-z0-9_-]*$/', $value)) {
            return '';
        }
        return $value;
    }

    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new AlmanacPublicationException('A canonical Almanac content directory could not be created.');
        }
    }

    /** @param array<string,mixed> $value */
    private function writePhpArray(string $path, array $value): void
    {
        $payload = "<?php\nreturn " . var_export($value, true) . ";\n";
        if (@file_put_contents($path, $payload, LOCK_EX) === false) {
            throw new AlmanacPublicationException(sprintf('Could not write Almanac file "%s".', basename($path)));
        }
    }

    /** @param array<string,mixed>|null $upload */
    private function publishArtwork(string $stage, string $relative, ?array $upload): void
    {
        if ($relative === '' || str_starts_with($relative, '/') || str_contains($relative, '..') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $relative)) {
            throw new AlmanacPublicationException('Library artwork must use the safe relative path recorded by the proposed Almanac.');
        }
        $extension = strtolower((string) pathinfo($relative, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            throw new AlmanacPublicationException('Library artwork must be a JPG, PNG, WEBP, or GIF image.');
        }
        if (!$this->hasUploadedArtwork($upload)) {
            throw new AlmanacPublicationException(sprintf('Attach the Library artwork file for "%s" before ringing the bell.', $relative));
        }
        $error = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new AlmanacPublicationException('The Library artwork upload did not complete successfully.');
        }
        $size = (int) ($upload['size'] ?? 0);
        if ($size < 1 || $size > self::MAX_ARTWORK_BYTES) {
            throw new AlmanacPublicationException('Library artwork must be between 1 byte and 10 MB.');
        }
        $tmp = isset($upload['tmp_name']) && is_string($upload['tmp_name']) ? $upload['tmp_name'] : '';
        if ($tmp === '' || !is_file($tmp) || !is_readable($tmp)) {
            throw new AlmanacPublicationException('The uploaded Library artwork could not be read.');
        }
        $imageInfo = @getimagesize($tmp);
        $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!is_array($imageInfo) || !isset($imageInfo['mime']) || !in_array($imageInfo['mime'], $allowedMime, true)) {
            throw new AlmanacPublicationException('The attached Library artwork is not a recognised safe image.');
        }
        $destination = $stage . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $this->ensureDirectory(dirname($destination));
        $moved = function_exists('is_uploaded_file') && is_uploaded_file($tmp)
            ? @move_uploaded_file($tmp, $destination)
            : @copy($tmp, $destination);
        if (!$moved) {
            throw new AlmanacPublicationException('The Library artwork could not be copied into the Almanac pack.');
        }
    }

    /** @param array<string,mixed>|null $upload */
    private function hasUploadedArtwork(?array $upload): bool
    {
        return is_array($upload) && (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    private function removeTree(string $directory): void
    {
        if (!is_dir($directory)) { return; }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) { @rmdir($item->getPathname()); }
            else { @unlink($item->getPathname()); }
        }
        @rmdir($directory);
    }
}
