<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Catalogue;

use GreatMarketrealmExpansions\Catalogue\AmbiguousCatalogueEntryException;
use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Catalogue\CatalogueEntry;
use GreatMarketrealmExpansions\Catalogue\CatalogueExpansion;
use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Expansions\ExpansionPack;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use PHPUnit\Framework\TestCase;

final class CatalogueTest extends TestCase
{
    private function catalogue(): Catalogue
    {
        $expansions = new ExpansionRegistry();
        $content = new ContentRegistry();

        $expansions->add(new ExpansionPack('alpha-pack', 'Alpha Pack', '1.2.0'));
        $expansions->add(new ExpansionPack('beta-pack', 'Beta Pack', '2.0.0'));

        $content->add('alpha-pack', new ContentDefinition('monster', 'milk-mimic', [
            'name' => 'Milk Mimic',
            'tags' => ['mimic', 'dairy'],
            'provenance' => ['expansion' => 'alpha-pack'],
        ]));
        $content->add('alpha-pack', new ContentDefinition('feat', 'iron-stomach', [
            'name' => 'Iron Stomach',
            'tags' => ['survival'],
        ]));
        $content->add('beta-pack', new ContentDefinition('monster', 'milk-mimic', [
            'name' => 'Greater Milk Mimic',
            'tags' => ['mimic'],
        ]));

        return new Catalogue($expansions, $content);
    }

    public function test_exposes_api_version_and_capabilities(): void
    {
        $catalogue = $this->catalogue();
        self::assertSame('1.0.0', $catalogue->apiVersion());
        self::assertTrue($catalogue->supports('catalogue.query.tag'));
        self::assertFalse($catalogue->supports('catalogue.write'));
        self::assertContains('catalogue.provenance', $catalogue->capabilities());
    }

    public function test_expansions_are_returned_as_read_only_views(): void
    {
        $expansions = $this->catalogue()->expansions();
        self::assertSame(['alpha-pack', 'beta-pack'], array_keys($expansions));
        self::assertInstanceOf(CatalogueExpansion::class, $expansions['alpha-pack']);
        self::assertSame('Alpha Pack', $expansions['alpha-pack']->name());
        self::assertFalse(method_exists($expansions['alpha-pack'], 'remove'));
    }

    public function test_can_fetch_one_expansion(): void
    {
        self::assertSame('2.0.0', $this->catalogue()->expansion('beta-pack')?->version());
        self::assertNull($this->catalogue()->expansion('missing-pack'));
    }

    public function test_can_fetch_fully_qualified_content(): void
    {
        $entry = $this->catalogue()->content('monster', 'milk-mimic', 'beta-pack');
        self::assertInstanceOf(CatalogueEntry::class, $entry);
        self::assertSame('beta-pack:monster:milk-mimic', $entry->id());
        self::assertSame('Greater Milk Mimic', $entry->name());
    }

    public function test_unqualified_content_lookup_succeeds_when_unique(): void
    {
        $entry = $this->catalogue()->content('feat', 'iron-stomach');
        self::assertSame('alpha-pack', $entry?->expansionKey());
    }

    public function test_unqualified_content_lookup_rejects_ambiguity(): void
    {
        $this->expectException(AmbiguousCatalogueEntryException::class);
        $this->expectExceptionMessage('alpha-pack, beta-pack');
        $this->catalogue()->content('monster', 'milk-mimic');
    }

    public function test_has_supports_qualified_lookup(): void
    {
        $catalogue = $this->catalogue();
        self::assertTrue($catalogue->has('monster', 'milk-mimic', 'alpha-pack'));
        self::assertFalse($catalogue->has('monster', 'missing', 'alpha-pack'));
    }

    public function test_content_views_are_sorted_by_canonical_id(): void
    {
        $ids = array_map(static fn (CatalogueEntry $entry): string => $entry->id(), $this->catalogue()->allContent());
        self::assertSame([
            'alpha-pack:feat:iron-stomach',
            'alpha-pack:monster:milk-mimic',
            'beta-pack:monster:milk-mimic',
        ], $ids);
    }

    public function test_type_and_expansion_convenience_queries(): void
    {
        $catalogue = $this->catalogue();
        self::assertCount(2, $catalogue->contentByType('monster'));
        self::assertCount(2, $catalogue->contentByExpansion('alpha-pack'));
        self::assertCount(1, $catalogue->contentByExpansionAndType('beta-pack', 'monster'));
    }
}
