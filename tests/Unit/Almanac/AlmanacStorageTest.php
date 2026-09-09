<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Almanac;

use GreatMarketrealmExpansions\Almanac\AlmanacStorage;
use PHPUnit\Framework\TestCase;

final class AlmanacStorageTest extends TestCase
{
    private string $uploads;

    protected function setUp(): void
    {
        $this->uploads = sys_get_temp_dir() . '/gmrexp-keeper-library-' . bin2hex(random_bytes(5));
        mkdir($this->uploads, 0775, true);
    }

    protected function tearDown(): void { $this->removeTree($this->uploads); }

    public function test_keeper_root_lives_beneath_persistent_uploads_not_plugin_tree(): void
    {
        $storage = new AlmanacStorage(['basedir' => $this->uploads, 'baseurl' => 'https://example.test/uploads']);
        self::assertSame($this->uploads . '/great-marketrealm-expansions/almanacs', str_replace('\\', '/', $storage->keeperRoot()));
        self::assertStringNotContainsString('/content/expansions', str_replace('\\', '/', $storage->keeperRoot()));
    }

    public function test_keeper_root_is_created_on_demand(): void
    {
        $storage = new AlmanacStorage(['basedir' => $this->uploads, 'baseurl' => 'https://example.test/uploads']);
        $root = $storage->ensureKeeperRoot();
        self::assertDirectoryExists($root);
        self::assertSame($root, $storage->keeperRoot());
    }

    public function test_keeper_artwork_uses_uploads_url(): void
    {
        $storage = new AlmanacStorage(['basedir' => $this->uploads, 'baseurl' => 'https://example.test/uploads']);
        mkdir($storage->ensureKeeperRoot() . '/midnight-menu/assets', 0775, true);
        self::assertSame(
            'https://example.test/uploads/great-marketrealm-expansions/almanacs/midnight-menu/assets/cover.png',
            $storage->artworkUrl('midnight-menu', 'assets/cover.png')
        );
    }

    public function test_keeper_pack_survives_removal_of_an_unrelated_plugin_tree(): void
    {
        $storage = new AlmanacStorage(['basedir' => $this->uploads, 'baseurl' => 'https://example.test/uploads']);
        $pack = $storage->ensureKeeperRoot() . '/midnight-menu';
        mkdir($pack, 0775, true);
        file_put_contents($pack . '/manifest.php', '<?php return [];');

        $fakePlugin = $this->uploads . '/replaceable-plugin-tree';
        mkdir($fakePlugin, 0775, true);
        file_put_contents($fakePlugin . '/deployment.txt', 'old');
        $this->removeTree($fakePlugin);
        mkdir($fakePlugin, 0775, true);
        file_put_contents($fakePlugin . '/deployment.txt', 'new');

        self::assertTrue($storage->keeperPackExists('midnight-menu'));
        self::assertFileExists($pack . '/manifest.php');
    }

    private function removeTree(string $directory): void
    {
        if (!is_dir($directory)) { return; }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) { $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname()); }
        rmdir($directory);
    }
}
