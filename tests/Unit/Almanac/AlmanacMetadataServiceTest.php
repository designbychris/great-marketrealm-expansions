<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Almanac;

use GreatMarketrealmExpansions\Almanac\AlmanacMetadataService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AlmanacMetadataServiceTest extends TestCase
{
    private string $root;
    private AlmanacMetadataService $service;
    protected function setUp(): void { $this->root = sys_get_temp_dir() . '/gmrexp-metadata-' . bin2hex(random_bytes(5)); mkdir($this->root . '/midnight-menu/assets', 0775, true); $this->service = new AlmanacMetadataService(); $this->writeManifest(); }
    protected function tearDown(): void { $this->removeTree($this->root); }

    public function test_presentation_metadata_changes_without_touching_content(): void
    {
        mkdir($this->root . '/midnight-menu/content/feat', 0775, true); file_put_contents($this->root . '/midnight-menu/content/feat/keep.php', '<?php return [];');
        $this->service->update($this->root, 'midnight-menu', ['name'=>'Midnight Menu','version'=>'0.1.1','description'=>'A shorter shelf summary.','artwork'=>'']);
        $manifest = require $this->root . '/midnight-menu/manifest.php';
        self::assertSame('midnight-menu', $manifest['key']); self::assertSame('Midnight Menu', $manifest['name']); self::assertSame('0.1.1', $manifest['version']); self::assertSame('A shorter shelf summary.', $manifest['description']); self::assertFileExists($this->root . '/midnight-menu/content/feat/keep.php');
    }

    public function test_existing_artwork_can_move_to_a_new_safe_pack_path(): void
    {
        file_put_contents($this->root . '/midnight-menu/assets/old.png', 'image'); $this->writeManifest('assets/old.png');
        $this->service->update($this->root, 'midnight-menu', ['name'=>'The Midnight Menu','version'=>'0.1.0','description'=>'Short.','artwork'=>'assets/new.png']);
        self::assertFileExists($this->root . '/midnight-menu/assets/new.png'); self::assertFileDoesNotExist($this->root . '/midnight-menu/assets/old.png');
        self::assertSame('assets/new.png', (require $this->root . '/midnight-menu/manifest.php')['artwork']);
    }

    public function test_remote_artwork_path_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class); $this->expectExceptionMessage('safe relative path');
        $this->service->update($this->root, 'midnight-menu', ['name'=>'The Midnight Menu','version'=>'0.1.0','description'=>'Short.','artwork'=>'https://example.com/cover.png']);
    }

    public function test_summary_is_bounded(): void
    {
        $this->expectException(InvalidArgumentException::class); $this->expectExceptionMessage('2,000 characters');
        $this->service->update($this->root, 'midnight-menu', ['name'=>'The Midnight Menu','version'=>'0.1.0','description'=>str_repeat('x',2001),'artwork'=>'']);
    }

    private function writeManifest(string $artwork=''): void { $m=['key'=>'midnight-menu','name'=>'The Midnight Menu','version'=>'0.1.0','description'=>'Long description.','compatibility'=>['ruleset'=>'great-marketrealm']]; if($artwork!==''){$m['artwork']=$artwork;} file_put_contents($this->root.'/midnight-menu/manifest.php', "<?php\nreturn ".var_export($m,true).";\n"); }
    private function removeTree(string $d): void { if(!is_dir($d))return; $it=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($d,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::CHILD_FIRST); foreach($it as $i){$i->isDir()?rmdir($i->getPathname()):unlink($i->getPathname());} rmdir($d); }
}
