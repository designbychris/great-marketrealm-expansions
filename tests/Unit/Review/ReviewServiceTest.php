<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Review;

use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use GreatMarketrealmExpansions\Import\ImportService;
use GreatMarketrealmExpansions\Review\ReviewDecisionException;
use GreatMarketrealmExpansions\Review\ReviewItem;
use GreatMarketrealmExpansions\Review\ReviewService;
use PHPUnit\Framework\TestCase;

final class ReviewServiceTest extends TestCase
{
    /** @return array{ImportService,ReviewService} */
    private function services(): array
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) {
            $types->add($type);
        }

        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        $validator = new ContentValidator($schemas);

        return [new ImportService($validator), new ReviewService($validator)];
    }

    /** @return array<string,mixed> */
    private function document(): array
    {
        return [
            'source' => [
                'type' => 'google-doc',
                'id' => 'review-fixture-doc',
                'title' => 'Review Fixture Sourcebook',
                'version' => 'draft-4',
            ],
            'records' => [
                [
                    'id' => 'feat-heading',
                    'type' => 'feat',
                    'key' => 'fixture-feat',
                    'source' => [
                        'heading' => 'Fixture Feat',
                        'section' => 'Chapter One',
                    ],
                    'data' => [
                        'name' => 'Fixture Feat',
                        'description' => 'Synthetic review proving content.',
                    ],
                ],
                [
                    'id' => 'ambiguous-heading',
                    'candidate_types' => ['class', 'subclass'],
                    'key' => 'ambiguous-thing',
                    'source' => [
                        'heading' => 'Ambiguous Thing',
                    ],
                    'data' => [
                        'name' => 'Ambiguous Thing',
                    ],
                ],
                [
                    'id' => 'review-flag-heading',
                    'type' => 'feat',
                    'key' => 'reviewed-feat',
                    'review_required' => true,
                    'data' => [
                        'name' => 'Reviewed Feat',
                    ],
                ],
            ],
        ];
    }

    private function session()
    {
        [$importer, $reviewer] = $this->services();
        return $reviewer->open($importer->stage($this->document()));
    }

    public function test_review_api_version_and_capabilities_are_stable(): void
    {
        [, $reviewer] = $this->services();

        self::assertSame('1.0.0', $reviewer->apiVersion());
        self::assertTrue($reviewer->supports('review.open'));
        self::assertTrue($reviewer->supports('review.approve'));
        self::assertTrue($reviewer->supports('review.reject'));
        self::assertTrue($reviewer->supports('review.amend'));
        self::assertTrue($reviewer->supports('review.pending'));
        self::assertTrue($reviewer->supports('review.approved-definitions'));
        self::assertTrue($reviewer->supports('review.no-publication'));
        self::assertFalse($reviewer->supports('review.publish'));
    }

    public function test_open_creates_one_pending_review_item_per_staged_record(): void
    {
        $session = $this->session();

        self::assertSame(['review-1', 'review-2', 'review-3'], array_keys($session->items()));
        self::assertSame(3, $session->pendingCount());
        self::assertSame(0, $session->resolvedCount());
        self::assertFalse($session->complete());
    }

    public function test_review_session_preserves_source_identity(): void
    {
        $source = $this->session()->source();

        self::assertSame('google-doc', $source->type());
        self::assertSame('review-fixture-doc', $source->id());
        self::assertSame('Review Fixture Sourcebook', $source->title());
        self::assertSame('draft-4', $source->version());
    }

    public function test_valid_staged_definition_can_be_approved(): void
    {
        $item = $this->session()->approve('review-1', 'Checked against the source.');

        self::assertSame(ReviewItem::APPROVED, $item->status());
        self::assertTrue($item->approved());
        self::assertTrue($item->resolved());
        self::assertSame('Checked against the source.', $item->note());
        self::assertSame('fixture-feat', $item->definition()?->key());
    }

    public function test_review_flagged_but_structurally_valid_definition_can_be_explicitly_approved(): void
    {
        $session = $this->session();
        self::assertTrue($session->item('review-3')->staged()->requiresReview());

        $item = $session->approve('review-3');

        self::assertTrue($item->approved());
        self::assertSame(ReviewItem::APPROVED, $item->status());
    }

    public function test_invalid_staged_definition_cannot_be_approved_without_amendment(): void
    {
        $this->expectException(ReviewDecisionException::class);
        $this->expectExceptionMessage('cannot approve its original staged definition');

        $this->session()->approve('review-2');
    }

    public function test_any_staged_definition_can_be_rejected(): void
    {
        $item = $this->session()->reject('review-2', 'Heading is flavour text, not mechanics.');

        self::assertTrue($item->rejected());
        self::assertTrue($item->resolved());
        self::assertSame('Heading is flavour text, not mechanics.', $item->note());
        self::assertFalse($item->approved());
    }

    public function test_invalid_unresolved_record_can_be_fixed_by_amendment(): void
    {
        $item = $this->session()->amend(
            'review-2',
            'feat',
            'ambiguous-thing',
            ['name' => 'Ambiguous Thing'],
            'Keeper resolved this as a feat.'
        );

        self::assertTrue($item->amended());
        self::assertTrue($item->approved());
        self::assertSame('feat', $item->definition()?->type());
        self::assertSame('ambiguous-thing', $item->definition()?->key());
    }

    public function test_amendment_must_pass_canonical_schema_validation(): void
    {
        $this->expectException(ReviewDecisionException::class);
        $this->expectExceptionMessage('failed canonical validation');

        $this->session()->amend(
            'review-2',
            'feat',
            'ambiguous-thing',
            []
        );
    }

    public function test_amendment_identity_must_be_valid(): void
    {
        $this->expectException(ReviewDecisionException::class);
        $this->expectExceptionMessage('amendment identity is invalid');

        $this->session()->amend(
            'review-2',
            '',
            '',
            ['name' => 'Still Ambiguous']
        );
    }

    public function test_amendment_preserves_protected_import_provenance(): void
    {
        $item = $this->session()->amend(
            'review-2',
            'feat',
            'ambiguous-thing',
            ['name' => 'Ambiguous Thing']
        );

        $provenance = $item->definition()?->provenance() ?? [];

        self::assertSame('google-doc', $provenance['import_source_type'] ?? null);
        self::assertSame('review-fixture-doc', $provenance['import_source_id'] ?? null);
        self::assertSame('Review Fixture Sourcebook', $provenance['import_source_title'] ?? null);
        self::assertSame('draft-4', $provenance['import_source_version'] ?? null);
        self::assertSame('ambiguous-heading', $provenance['import_record_id'] ?? null);
    }

    public function test_amendment_adds_review_audit_provenance(): void
    {
        $item = $this->session()->amend(
            'review-2',
            'feat',
            'ambiguous-thing',
            ['name' => 'Ambiguous Thing']
        );

        $provenance = $item->definition()?->provenance() ?? [];

        self::assertSame('review-2', $provenance['review_id'] ?? null);
        self::assertSame('amended', $provenance['review_status'] ?? null);
    }

    public function test_amendment_preserves_source_context(): void
    {
        $item = $this->session()->amend(
            'review-2',
            'feat',
            'ambiguous-thing',
            ['name' => 'Ambiguous Thing']
        );

        self::assertSame(
            'Ambiguous Thing',
            $item->definition()?->provenance()['import_context']['heading'] ?? null
        );
    }

    public function test_amendment_cannot_spoof_protected_source_or_review_provenance(): void
    {
        $item = $this->session()->amend(
            'review-2',
            'feat',
            'ambiguous-thing',
            [
                'name' => 'Ambiguous Thing',
                'provenance' => [
                    'import_source_id' => 'spoofed',
                    'review_id' => 'spoofed-review',
                ],
            ]
        );

        $provenance = $item->definition()?->provenance() ?? [];

        self::assertSame('review-fixture-doc', $provenance['import_source_id'] ?? null);
        self::assertSame('review-2', $provenance['review_id'] ?? null);
    }

    public function test_rejected_item_can_be_reset_to_pending(): void
    {
        $session = $this->session();
        $session->reject('review-1', 'Temporary decision.');

        $item = $session->reset('review-1');

        self::assertTrue($item->pending());
        self::assertSame('', $item->note());
        self::assertFalse($item->resolved());
    }

    public function test_amended_item_can_be_reset_to_original_pending_definition(): void
    {
        $session = $this->session();
        $session->amend(
            'review-1',
            'feat',
            'amended-fixture-feat',
            ['name' => 'Amended Fixture Feat']
        );

        $item = $session->reset('review-1');

        self::assertTrue($item->pending());
        self::assertSame('fixture-feat', $item->definition()?->key());
        self::assertFalse($item->amended());
    }

    public function test_decisions_are_granular_per_item(): void
    {
        $session = $this->session();
        $session->approve('review-1');
        $session->reject('review-2');

        self::assertSame(1, $session->approvedCount());
        self::assertSame(1, $session->rejectedCount());
        self::assertSame(1, $session->pendingCount());
        self::assertSame(2, $session->resolvedCount());
        self::assertFalse($session->complete());
    }

    public function test_session_is_complete_only_when_every_item_has_a_decision(): void
    {
        $session = $this->session();
        $session->approve('review-1');
        $session->reject('review-2');
        $session->approve('review-3');

        self::assertTrue($session->complete());
        self::assertSame(0, $session->pendingCount());
        self::assertSame(3, $session->resolvedCount());
    }

    public function test_approved_definitions_include_approved_and_amended_but_not_rejected_or_pending(): void
    {
        $session = $this->session();
        $session->approve('review-1');
        $session->amend(
            'review-2',
            'feat',
            'resolved-feature',
            ['name' => 'Resolved Feature']
        );
        $session->reject('review-3');

        $keys = array_map(static fn ($definition): string => $definition->key(), $session->approvedDefinitions());

        self::assertSame(['fixture-feat', 'resolved-feature'], $keys);
    }

    public function test_counts_distinguish_approved_amended_rejected_and_pending(): void
    {
        $session = $this->session();
        $session->approve('review-1');
        $session->amend(
            'review-2',
            'feat',
            'resolved-feature',
            ['name' => 'Resolved Feature']
        );

        self::assertSame(1, $session->approvedCount());
        self::assertSame(1, $session->amendedCount());
        self::assertSame(0, $session->rejectedCount());
        self::assertSame(1, $session->pendingCount());
    }

    public function test_unknown_review_item_is_rejected_safely(): void
    {
        $this->expectException(ReviewDecisionException::class);
        $this->expectExceptionMessage('does not exist');

        $this->session()->approve('review-999');
    }

    public function test_review_notes_are_trimmed(): void
    {
        $item = $this->session()->approve('review-1', '  Verified carefully.  ');

        self::assertSame('Verified carefully.', $item->note());
    }

    public function test_session_serialises_review_progress(): void
    {
        $session = $this->session();
        $session->approve('review-1');
        $session->reject('review-2', 'Not canonical content.');

        $data = $session->toArray();

        self::assertSame('review-fixture-doc', $data['source']['id']);
        self::assertSame(3, $data['item_count']);
        self::assertSame(1, $data['pending_count']);
        self::assertSame(1, $data['approved_count']);
        self::assertSame(0, $data['amended_count']);
        self::assertSame(1, $data['rejected_count']);
        self::assertSame(2, $data['resolved_count']);
        self::assertFalse($data['complete']);
        self::assertSame(1, $data['approved_definition_count']);
    }

    public function test_review_item_serialises_original_review_context_and_decision(): void
    {
        $item = $this->session()->approve('review-3', 'Keeper checked it.');

        $data = $item->toArray();

        self::assertSame('review-3', $data['review_id']);
        self::assertSame('review-flag-heading', $data['record_id']);
        self::assertSame('approved', $data['status']);
        self::assertTrue($data['resolved']);
        self::assertTrue($data['approved']);
        self::assertTrue($data['original_requires_review']);
        self::assertSame('Keeper checked it.', $data['note']);
    }

    public function test_opening_same_import_produces_deterministic_review_ids_and_initial_state(): void
    {
        [$importer, $reviewer] = $this->services();
        $result = $importer->stage($this->document());

        self::assertSame(
            $reviewer->open($result)->toArray(),
            $reviewer->open($result)->toArray()
        );
    }
}
