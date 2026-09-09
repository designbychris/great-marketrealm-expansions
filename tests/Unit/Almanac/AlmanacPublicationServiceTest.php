<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Almanac;

use GreatMarketrealmExpansions\Almanac\AlmanacPublicationException;
use GreatMarketrealmExpansions\Almanac\AlmanacPublicationService;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use GreatMarketrealmExpansions\Expansions\Loading\ExpansionFileLoader;
use PHPUnit\Framework\TestCase;

final class AlmanacPublicationServiceTest extends TestCase
{
    private string $root;
    private ExpansionRegistry $expansions;
    private ContentRegistry $content;
    private AlmanacPublicationService $service;

    protected function setUp(): void
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        $validator = new ContentValidator($schemas);
        $this->expansions = new ExpansionRegistry();
        $this->content = new ContentRegistry($validator);
        $loader = new ExpansionFileLoader($this->expansions, $this->content, $validator);
        $this->service = new AlmanacPublicationService($validator, $loader);
        $this->root = sys_get_temp_dir() . '/gmrexp-publish-' . bin2hex(random_bytes(5));
        mkdir($this->root, 0775, true);
    }

    protected function tearDown(): void { $this->removeTree($this->root); }

    public function test_complete_proposal_is_written_and_loaded_atomically(): void
    {
        $result = $this->service->publish($this->proposal(), $this->root);
        self::assertSame('midnight-menu', $result->key());
        self::assertSame(1, $result->definitionCount());
        self::assertFileExists($this->root . '/midnight-menu/manifest.php');
        self::assertFileExists($this->root . '/midnight-menu/content/feat/night-snack.php');
        self::assertTrue($this->expansions->has('midnight-menu'));
        self::assertNotNull($this->content->get('midnight-menu', 'feat', 'night-snack'));
        self::assertSame([], glob($this->root . '/.midnight-menu.publishing-*') ?: []);
    }

    public function test_incomplete_review_cannot_cross_publication_boundary(): void
    {
        $proposal = $this->proposal();
        $proposal['complete_review'] = false;
        $proposal['pending_count'] = 1;
        $this->expectException(AlmanacPublicationException::class);
        $this->expectExceptionMessage('Review Desk is complete');
        try { $this->service->publish($proposal, $this->root); }
        finally { self::assertDirectoryDoesNotExist($this->root . '/midnight-menu'); }
    }

    public function test_existing_canonical_key_is_never_overwritten(): void
    {
        mkdir($this->root . '/midnight-menu');
        file_put_contents($this->root . '/midnight-menu/keep.txt', 'keeper');
        $this->expectException(AlmanacPublicationException::class);
        $this->expectExceptionMessage('already published');
        try { $this->service->publish($this->proposal(), $this->root); }
        finally { self::assertSame('keeper', file_get_contents($this->root . '/midnight-menu/keep.txt')); }
    }

    public function test_manifest_artwork_requires_attached_image_before_publication(): void
    {
        $proposal = $this->proposal();
        $proposal['manifest']['artwork'] = 'assets/library-cover.png';
        $this->expectException(AlmanacPublicationException::class);
        $this->expectExceptionMessage('Attach the Library artwork file');
        try { $this->service->publish($proposal, $this->root); }
        finally { self::assertDirectoryDoesNotExist($this->root . '/midnight-menu'); }
    }

    public function test_attached_artwork_is_copied_to_manifest_relative_path(): void
    {
        $proposal = $this->proposal();
        $proposal['manifest']['artwork'] = 'assets/library-cover.png';
        $image = $this->root . '/upload.png';
        file_put_contents($image, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $upload = ['error' => UPLOAD_ERR_OK, 'size' => filesize($image), 'tmp_name' => $image, 'name' => 'cover.png', 'type' => 'image/png'];
        $this->service->publish($proposal, $this->root, $upload);
        self::assertFileExists($this->root . '/midnight-menu/assets/library-cover.png');
        $manifest = require $this->root . '/midnight-menu/manifest.php';
        self::assertSame('assets/library-cover.png', $manifest['artwork']);
    }

    /** @return array<string,mixed> */
    private function proposal(): array
    {
        return [
            'proposal_version' => '1.0.0',
            'manifest' => ['key' => 'midnight-menu', 'name' => 'The Midnight Menu', 'version' => '0.1.0', 'description' => 'Synthetic publication fixture.'],
            'definition_count' => 1,
            'pending_count' => 0,
            'rejected_count' => 0,
            'complete_review' => true,
            'definitions' => [[
                'type' => 'feat',
                'key' => 'night-snack',
                'data' => ['name' => 'Night Snack', 'description' => 'Synthetic fixture only.'],
            ]],
        ];
    }

    private function removeTree(string $directory): void
    {
        if (!is_dir($directory)) { return; }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) { $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname()); }
        rmdir($directory);
    }
}
