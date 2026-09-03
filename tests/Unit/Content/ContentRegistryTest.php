<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Content;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ContentRegistryTest extends TestCase
{
    public function test_content_is_scoped_by_expansion_type_and_key(): void
    {
        $r = new ContentRegistry(); $d = new ContentDefinition('feat', 'iron-stomach'); $r->add('core-plus', $d);
        self::assertSame($d, $r->get('core-plus', 'feat', 'iron-stomach')); self::assertNull($r->get('other', 'feat', 'iron-stomach'));
    }

    public function test_content_can_be_collected_by_type_across_expansions(): void
    {
        $r = new ContentRegistry(); $a = new ContentDefinition('feat', 'a'); $b = new ContentDefinition('feat', 'b');
        $r->add('one', $a); $r->add('two', $b);
        self::assertSame(['one:a' => $a, 'two:b' => $b], $r->ofType('feat'));
    }

    public function test_content_can_be_collected_for_one_expansion(): void
    {
        $r = new ContentRegistry(); $d = new ContentDefinition('spell', 'soup-bolt'); $r->add('cookbook', $d);
        self::assertSame(['spell' => ['soup-bolt' => $d]], $r->forExpansion('cookbook'));
    }

    public function test_duplicate_content_is_rejected(): void
    {
        $r = new ContentRegistry(); $r->add('pack', new ContentDefinition('feat', 'same'));
        $this->expectException(InvalidArgumentException::class); $r->add('pack', new ContentDefinition('feat', 'same'));
    }

    public function test_blank_expansion_key_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class); (new ContentRegistry())->add(' ', new ContentDefinition('feat', 'x'));
    }

    public function test_expansion_content_can_be_removed_for_atomic_loader_rollback(): void
    {
        $registry = new ContentRegistry();
        $registry->add('pantry', new ContentDefinition('feat', 'test', ['name' => 'Test']));
        $registry->removeExpansion('pantry');
        self::assertSame([], $registry->forExpansion('pantry'));
    }
}
