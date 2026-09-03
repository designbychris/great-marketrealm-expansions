<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Content\Types;

use GreatMarketrealmExpansions\Content\Types\ContentType;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ContentTypeCatalogueTest extends TestCase
{
    public function test_content_type_normalizes_identity_and_exposes_metadata(): void
    {
        $type = new ContentType('Magic Items!', 'Magic Item', 'Enchanted things.');
        self::assertSame('magic-items', $type->key());
        self::assertSame('Magic Item', $type->label());
        self::assertSame('Enchanted things.', $type->description());
    }

    public function test_core_catalogue_contains_expected_canonical_types(): void
    {
        $catalogue = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $catalogue->add($type); }

        self::assertCount(20, $catalogue->all());
        foreach (['race', 'subrace', 'class', 'subclass', 'background', 'feat', 'spell', 'weapon', 'armour', 'equipment', 'magic-item', 'monster', 'npc', 'rule', 'condition', 'adventure', 'encounter', 'hazard', 'treasure', 'language'] as $key) {
            self::assertTrue($catalogue->has($key), $key);
        }
    }

    public function test_duplicate_type_keys_are_rejected(): void
    {
        $catalogue = new ContentTypeCatalogue();
        $catalogue->add(new ContentType('feat', 'Feat'));
        $this->expectException(InvalidArgumentException::class);
        $catalogue->add(new ContentType('feat', 'Another Feat'));
    }
}
