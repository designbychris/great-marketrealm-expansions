<?php
namespace GreatMarketrealmExpansions\Expansions\Loading;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Expansions\ExpansionPack;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use Throwable;

final class ExpansionFileLoader
{
    public function __construct(
        private ExpansionRegistry $expansions,
        private ContentRegistry $content,
        private ContentValidator $validator
    ) {}

    public function load(string $directory): ExpansionLoadResult
    {
        $directory = rtrim($directory, '/\\');
        $manifestPath = $directory . DIRECTORY_SEPARATOR . 'manifest.php';

        if (!is_dir($directory)) {
            throw new ExpansionLoadException('Expansion directory does not exist.', $directory);
        }
        if (!is_readable($manifestPath)) {
            throw new ExpansionLoadException('Expansion manifest is missing or unreadable.', $manifestPath);
        }

        $manifest = $this->readArrayFile($manifestPath, 'Expansion manifest must return an array.');
        $pack = $this->packFromManifest($manifest, $manifestPath);

        if ($this->expansions->has($pack->key())) {
            throw new ExpansionLoadException(sprintf('Expansion pack "%s" is already registered.', $pack->key()), $manifestPath);
        }

        [$definitions, $files] = $this->discoverDefinitions($directory, $pack);
        $this->preflight($pack, $definitions, $files);

        $counts = [];
        try {
            $this->expansions->add($pack);
            foreach ($definitions as $index => $definition) {
                $this->content->add($pack->key(), $definition);
                $counts[$definition->type()] = ($counts[$definition->type()] ?? 0) + 1;
            }
        } catch (Throwable $exception) {
            $this->content->removeExpansion($pack->key());
            $this->expansions->remove($pack->key());
            $source = isset($index) && isset($files[$index]) ? $files[$index] : 'manifest.php';
            throw new ExpansionLoadException('Expansion could not be committed atomically: ' . $exception->getMessage(), $source, $exception);
        }
        ksort($counts);

        return new ExpansionLoadResult($pack, $counts, $files);
    }

    /** @return list<ExpansionLoadResult> */
    public function loadAll(string $root): array
    {
        $root = rtrim($root, '/\\');
        if (!is_dir($root)) {
            return [];
        }

        $directories = glob($root . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];
        sort($directories, SORT_STRING);

        $results = [];
        foreach ($directories as $directory) {
            if (is_readable($directory . DIRECTORY_SEPARATOR . 'manifest.php')) {
                $results[] = $this->load($directory);
            }
        }
        return $results;
    }

    /** @param array<string, mixed> $manifest */
    private function packFromManifest(array $manifest, string $source): ExpansionPack
    {
        foreach (['key', 'name', 'version'] as $required) {
            if (!isset($manifest[$required]) || !is_string($manifest[$required]) || trim($manifest[$required]) === '') {
                throw new ExpansionLoadException(sprintf('Manifest field "%s" is required and must be a non-empty string.', $required), $source);
            }
        }

        $description = $manifest['description'] ?? '';
        if (!is_string($description)) {
            throw new ExpansionLoadException('Manifest field "description" must be a string.', $source);
        }

        $metadata = $manifest;
        unset($metadata['key'], $metadata['name'], $metadata['version'], $metadata['description']);

        try {
            return new ExpansionPack($manifest['key'], $manifest['name'], $manifest['version'], $description, $metadata);
        } catch (Throwable $exception) {
            throw new ExpansionLoadException('Expansion manifest is invalid: ' . $exception->getMessage(), $source, $exception);
        }
    }

    /** @return array{0:list<ContentDefinition>,1:list<string>} */
    private function discoverDefinitions(string $directory, ExpansionPack $pack): array
    {
        $contentDirectory = $directory . DIRECTORY_SEPARATOR . 'content';
        if (!is_dir($contentDirectory)) {
            return [[], []];
        }

        $paths = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($contentDirectory, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                $paths[] = $file->getPathname();
            }
        }
        sort($paths, SORT_STRING);

        $definitions = [];
        $files = [];
        foreach ($paths as $path) {
            $payload = $this->readArrayFile($path, 'Content file must return an array.');
            if (!isset($payload['type'], $payload['key'], $payload['data']) || !is_string($payload['type']) || !is_string($payload['key']) || !is_array($payload['data'])) {
                throw new ExpansionLoadException('Content file must provide string "type", string "key", and array "data" values.', $this->relativePath($directory, $path));
            }

            $relative = $this->relativePath($directory, $path);
            $data = $payload['data'];
            $provenance = isset($data['provenance']) && is_array($data['provenance']) ? $data['provenance'] : [];
            $data['provenance'] = [
                'expansion' => $pack->key(),
                'file' => $relative,
            ] + $provenance;

            try {
                $definitions[] = new ContentDefinition($payload['type'], $payload['key'], $data);
            } catch (Throwable $exception) {
                throw new ExpansionLoadException('Content identity is invalid: ' . $exception->getMessage(), $relative, $exception);
            }
            $files[] = $relative;
        }

        return [$definitions, $files];
    }

    /** @param list<ContentDefinition> $definitions @param list<string> $files */
    private function preflight(ExpansionPack $pack, array $definitions, array $files): void
    {
        $seen = [];
        foreach ($definitions as $index => $definition) {
            $identity = $definition->type() . '/' . $definition->key();
            if (isset($seen[$identity])) {
                throw new ExpansionLoadException(sprintf('Duplicate content identity "%s" also appears in "%s".', $identity, $seen[$identity]), $files[$index]);
            }
            $seen[$identity] = $files[$index];

            if ($this->content->get($pack->key(), $definition->type(), $definition->key()) !== null) {
                throw new ExpansionLoadException(sprintf('Content "%s/%s/%s" is already registered.', $pack->key(), $definition->type(), $definition->key()), $files[$index]);
            }

            $result = $this->validator->validate($definition);
            if (!$result->valid()) {
                $messages = array_map(static fn ($error): string => $error->message(), $result->errors());
                throw new ExpansionLoadException('Content validation failed: ' . implode(' ', $messages), $files[$index]);
            }
        }
    }

    /** @return array<string, mixed> */
    private function readArrayFile(string $path, string $error): array
    {
        try {
            $payload = (static function (string $__path): mixed { return require $__path; })($path);
        } catch (Throwable $exception) {
            throw new ExpansionLoadException('PHP content file could not be read: ' . $exception->getMessage(), $path, $exception);
        }
        if (!is_array($payload)) {
            throw new ExpansionLoadException($error, $path);
        }
        return $payload;
    }

    private function relativePath(string $directory, string $path): string
    {
        $directory = str_replace('\\', '/', rtrim($directory, '/\\'));
        $path = str_replace('\\', '/', $path);
        return ltrim(str_starts_with($path, $directory) ? substr($path, strlen($directory)) : $path, '/');
    }
}
