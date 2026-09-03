<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Expansions\Loading;

use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use GreatMarketrealmExpansions\Expansions\Loading\ExpansionFileLoader;
use GreatMarketrealmExpansions\Expansions\Loading\ExpansionLoadException;
use PHPUnit\Framework\TestCase;

final class ExpansionFileLoaderTest extends TestCase
{
    /** @var list<string> */
    private array $temporaryDirectories = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryDirectories as $directory) {
            $this->deleteDirectory($directory);
        }
    }

    public function test_valid_pack_is_loaded_into_both_registries(): void
    {
        [$loader, $expansions, $content] = $this->loader();
        $directory = $this->pack('test-pack', [
            'content/feats/iron-stomach.php' => $this->definition('feat', 'iron-stomach', ['name' => 'Iron Stomach']),
            'content/monsters/milk-mimic.php' => $this->definition('monster', 'milk-mimic', ['name' => 'Milk Mimic']),
        ]);

        $result = $loader->load($directory);

        self::assertSame('test-pack', $result->pack()->key());
        self::assertSame(2, $result->total());
        self::assertSame(['feat' => 1, 'monster' => 1], $result->contentCounts());
        self::assertTrue($expansions->has('test-pack'));
        self::assertSame('Iron Stomach', $content->get('test-pack', 'feat', 'iron-stomach')?->value('name'));
    }

    public function test_loader_stamps_canonical_provenance_without_exposing_absolute_path(): void
    {
        [$loader, , $content] = $this->loader();
        $directory = $this->pack('source-pack', [
            'content/feats/test.php' => $this->definition('feat', 'test', [
                'name' => 'Test',
                'provenance' => ['page' => 42],
            ]),
        ]);

        $loader->load($directory);
        $provenance = $content->get('source-pack', 'feat', 'test')?->provenance();

        self::assertSame('source-pack', $provenance['expansion'] ?? null);
        self::assertSame('content/feats/test.php', $provenance['file'] ?? null);
        self::assertSame(42, $provenance['page'] ?? null);
        self::assertStringNotContainsString($directory, (string) ($provenance['file'] ?? ''));
    }

    public function test_manifest_metadata_is_preserved_on_pack(): void
    {
        [$loader] = $this->loader();
        $directory = $this->pack('metadata-pack', [], [
            'status' => 'preview',
            'compatibility' => ['ruleset' => 'great-marketrealm'],
        ]);

        $pack = $loader->load($directory)->pack();
        self::assertSame('preview', $pack->meta('status'));
        self::assertSame(['ruleset' => 'great-marketrealm'], $pack->meta('compatibility'));
    }

    public function test_invalid_content_rejects_entire_pack_atomically(): void
    {
        [$loader, $expansions, $content] = $this->loader();
        $directory = $this->pack('broken-pack', [
            'content/feats/good.php' => $this->definition('feat', 'good', ['name' => 'Good']),
            'content/subclasses/bad.php' => $this->definition('subclass', 'bad', ['name' => 'Missing Parent']),
        ]);

        try {
            $loader->load($directory);
            self::fail('Expected invalid content to fail loading.');
        } catch (ExpansionLoadException $exception) {
            self::assertStringContainsString('parent_class', $exception->getMessage());
        }

        self::assertFalse($expansions->has('broken-pack'));
        self::assertSame([], $content->forExpansion('broken-pack'));
    }

    public function test_duplicate_identity_inside_pack_is_rejected_atomically(): void
    {
        [$loader, $expansions] = $this->loader();
        $directory = $this->pack('duplicate-pack', [
            'content/a.php' => $this->definition('feat', 'same', ['name' => 'One']),
            'content/b.php' => $this->definition('feat', 'same', ['name' => 'Two']),
        ]);

        $this->expectException(ExpansionLoadException::class);
        $this->expectExceptionMessage('Duplicate content identity');
        try {
            $loader->load($directory);
        } finally {
            self::assertFalse($expansions->has('duplicate-pack'));
        }
    }

    public function test_missing_manifest_is_reported_with_source_context(): void
    {
        [$loader] = $this->loader();
        $directory = $this->temporaryDirectory();

        $this->expectException(ExpansionLoadException::class);
        $this->expectExceptionMessage('manifest');
        $loader->load($directory);
    }

    public function test_content_file_must_return_expected_shape(): void
    {
        [$loader] = $this->loader();
        $directory = $this->pack('shape-pack', [
            'content/oops.php' => "<?php\nreturn ['surprise' => true];\n",
        ]);

        $this->expectException(ExpansionLoadException::class);
        $this->expectExceptionMessage('string "type"');
        $loader->load($directory);
    }

    public function test_load_all_is_deterministic_and_ignores_non_pack_directories(): void
    {
        [$loader] = $this->loader();
        $root = $this->temporaryDirectory();
        $this->writePackAt($root . '/z-pack', 'z-pack');
        $this->writePackAt($root . '/a-pack', 'a-pack');
        mkdir($root . '/notes', 0777, true);

        $results = $loader->loadAll($root);
        self::assertSame(['a-pack', 'z-pack'], array_map(static fn ($result): string => $result->pack()->key(), $results));
    }

    public function test_loading_same_pack_twice_is_rejected(): void
    {
        [$loader] = $this->loader();
        $directory = $this->pack('once-pack');
        $loader->load($directory);

        $this->expectException(ExpansionLoadException::class);
        $this->expectExceptionMessage('already registered');
        $loader->load($directory);
    }

    /** @return array{0:ExpansionFileLoader,1:ExpansionRegistry,2:ContentRegistry} */
    private function loader(): array
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        $validator = new ContentValidator($schemas);
        $expansions = new ExpansionRegistry();
        $content = new ContentRegistry($validator);
        return [new ExpansionFileLoader($expansions, $content, $validator), $expansions, $content];
    }

    /** @param array<string, string> $files @param array<string, mixed> $extraManifest */
    private function pack(string $key, array $files = [], array $extraManifest = []): string
    {
        $directory = $this->temporaryDirectory();
        $manifest = array_merge([
            'key' => $key,
            'name' => ucwords(str_replace('-', ' ', $key)),
            'version' => '1.0.0',
            'description' => 'Test pack.',
        ], $extraManifest);
        file_put_contents($directory . '/manifest.php', "<?php\nreturn " . var_export($manifest, true) . ";\n");
        foreach ($files as $relative => $contents) {
            $path = $directory . '/' . $relative;
            if (!is_dir(dirname($path))) { mkdir(dirname($path), 0777, true); }
            file_put_contents($path, $contents);
        }
        return $directory;
    }

    private function writePackAt(string $directory, string $key): void
    {
        mkdir($directory, 0777, true);
        $manifest = ['key' => $key, 'name' => $key, 'version' => '1.0.0'];
        file_put_contents($directory . '/manifest.php', "<?php\nreturn " . var_export($manifest, true) . ";\n");
    }

    /** @param array<string, mixed> $data */
    private function definition(string $type, string $key, array $data): string
    {
        return "<?php\nreturn " . var_export(['type' => $type, 'key' => $key, 'data' => $data], true) . ";\n";
    }

    private function temporaryDirectory(): string
    {
        $directory = sys_get_temp_dir() . '/gmrexp-' . bin2hex(random_bytes(8));
        mkdir($directory, 0777, true);
        $this->temporaryDirectories[] = $directory;
        return $directory;
    }

    private function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) { return; }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($directory);
    }
}
