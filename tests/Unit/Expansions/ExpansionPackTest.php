<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Expansions;

use GreatMarketrealmExpansions\Expansions\ExpansionPack;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ExpansionPackTest extends TestCase
{
    public function test_it_normalizes_keys_and_exposes_metadata(): void
    {
        $pack = new ExpansionPack('  Cold Storage! ', 'Cold Storage', '1.2.0', 'Frozen horrors.');
        self::assertSame('cold-storage', $pack->key()); self::assertSame('Cold Storage', $pack->name());
        self::assertSame('1.2.0', $pack->version()); self::assertSame('Frozen horrors.', $pack->description());
    }

    /** @dataProvider invalidPackProvider */
    public function test_invalid_required_values_are_rejected(string $key, string $name, string $version): void
    {
        $this->expectException(InvalidArgumentException::class); new ExpansionPack($key, $name, $version);
    }

    public static function invalidPackProvider(): array
    {
        return [['!!!', 'Name', '1.0'], ['key', '   ', '1.0'], ['key', 'Name', '   ']];
    }
}
