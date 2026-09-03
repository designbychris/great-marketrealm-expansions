<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Expansions;

use GreatMarketrealmExpansions\Expansions\ExpansionPack;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ExpansionRegistryTest extends TestCase
{
    public function test_packs_can_be_registered_and_retrieved(): void
    {
        $r = new ExpansionRegistry(); $p = new ExpansionPack('pantry', 'Pantry'); $r->add($p);
        self::assertTrue($r->has('pantry')); self::assertSame($p, $r->get('pantry')); self::assertSame(['pantry' => $p], $r->all());
    }

    public function test_missing_pack_returns_null(): void { self::assertNull((new ExpansionRegistry())->get('missing')); }

    public function test_duplicate_pack_keys_are_rejected(): void
    {
        $r = new ExpansionRegistry(); $r->add(new ExpansionPack('pantry', 'One'));
        $this->expectException(InvalidArgumentException::class); $r->add(new ExpansionPack('pantry', 'Two'));
    }
}
