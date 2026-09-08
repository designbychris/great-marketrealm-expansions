<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Almanac;

use GreatMarketrealmExpansions\Almanac\AlmanacProposalService;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use GreatMarketrealmExpansions\Import\ImportService;
use GreatMarketrealmExpansions\Review\ReviewService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AlmanacProposalServiceTest extends TestCase
{
    private function review(): array
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        $validator = new ContentValidator($schemas);
        $importer = new ImportService($validator);
        $result = $importer->stage([
            'source' => ['type' => 'google-doc', 'id' => 'fixture', 'title' => 'Synthetic Expansion'],
            'records' => [
                ['id' => 'one', 'type' => 'monster', 'key' => 'toast-beast', 'data' => ['name' => 'Toast Beast']],
                ['id' => 'two', 'source' => ['heading' => 'Structural'], 'data' => ['name' => 'Structural'], 'review_required' => true],
            ],
        ]);
        return [(new ReviewService($validator))->open($result), $validator];
    }

    public function test_proposal_api_version_is_stable(): void
    {
        self::assertSame('1.0.0', AlmanacProposalService::API_VERSION);
    }

    public function test_proposal_collects_only_keeper_approved_definitions(): void
    {
        [$review] = $this->review();
        $review->approve('review-1');
        $proposal = (new AlmanacProposalService())->propose($review, 'synthetic-expansion', 'Synthetic Expansion', '0.1.0');

        self::assertSame(1, $proposal->definitionCount());
        self::assertSame('toast-beast', $proposal->definitions()[0]->key());
        self::assertSame(1, $proposal->pendingCount());
        self::assertFalse($proposal->completeReview());
    }

    public function test_rejected_records_are_counted_but_not_shelved(): void
    {
        [$review] = $this->review();
        $review->approve('review-1');
        $review->reject('review-2', 'Structural heading.');
        $proposal = (new AlmanacProposalService())->propose($review, 'synthetic-expansion', 'Synthetic Expansion', '0.1.0');

        self::assertSame(1, $proposal->definitionCount());
        self::assertSame(1, $proposal->rejectedCount());
        self::assertTrue($proposal->completeReview());
    }

    public function test_manifest_carries_optional_pack_artwork_path(): void
    {
        [$review] = $this->review();
        $review->approve('review-1');
        $proposal = (new AlmanacProposalService())->propose(
            $review, 'synthetic-expansion', 'Synthetic Expansion', '0.1.0', 'A test.',
            ['artwork' => 'assets/library-cover.jpg']
        );

        self::assertSame('assets/library-cover.jpg', $proposal->manifest()['artwork']);
    }

    /**
     * @dataProvider unsafeArtworkProvider
     */
    public function test_artwork_must_be_safe_relative_pack_path(string $artwork): void
    {
        [$review] = $this->review();
        $review->approve('review-1');
        $this->expectException(InvalidArgumentException::class);
        (new AlmanacProposalService())->propose(
            $review, 'synthetic-expansion', 'Synthetic Expansion', '0.1.0', '', ['artwork' => $artwork]
        );
    }

    public static function unsafeArtworkProvider(): array
    {
        return [
            'absolute' => ['/tmp/cover.jpg'],
            'traversal' => ['../cover.jpg'],
            'remote' => ['https://example.test/cover.jpg'],
        ];
    }

    public function test_proposal_requires_at_least_one_approved_definition(): void
    {
        [$review] = $this->review();
        $this->expectException(InvalidArgumentException::class);
        (new AlmanacProposalService())->propose($review, 'synthetic-expansion', 'Synthetic Expansion', '0.1.0');
    }

    public function test_serialized_proposal_is_a_non_publishing_assembly_projection(): void
    {
        [$review] = $this->review();
        $review->approve('review-1');
        $array = (new AlmanacProposalService())->propose($review, 'synthetic-expansion', 'Synthetic Expansion', '0.1.0')->toArray();

        self::assertSame('1.0.0', $array['proposal_version']);
        self::assertSame('synthetic-expansion', $array['manifest']['key']);
        self::assertSame(1, $array['definition_count']);
        self::assertArrayHasKey('definitions', $array);
    }
}
