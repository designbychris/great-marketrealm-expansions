<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Content;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ContentDefinitionTest extends TestCase
{
    public function test_it_normalizes_identity_and_retains_data(): void
    {
        $d = new ContentDefinition('Sub Classes', 'Circle: Freezer', ['name' => 'Circle of the Freezer']);
        self::assertSame('sub-classes', $d->type()); self::assertSame('circle-freezer', $d->key());
        self::assertSame('Circle of the Freezer', $d->value('name')); self::assertSame('fallback', $d->value('missing', 'fallback'));
    }

    /** @dataProvider invalidDefinitionProvider */
    public function test_invalid_identity_is_rejected(string $type, string $key): void
    {
        $this->expectException(InvalidArgumentException::class); new ContentDefinition($type, $key);
    }

    public static function invalidDefinitionProvider(): array { return [['!!!', 'key'], ['type', '!!!']]; }
}
