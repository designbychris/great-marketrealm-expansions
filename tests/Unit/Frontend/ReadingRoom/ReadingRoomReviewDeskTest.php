<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Frontend\ReadingRoom;

use GreatMarketrealmExpansions\Almanac\AlmanacProposalService;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Content\Schema\CoreSchemas;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;
use GreatMarketrealmExpansions\Content\Types\ContentTypeCatalogue;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use GreatMarketrealmExpansions\Frontend\ReadingRoom\ReadingRoomAccess;
use GreatMarketrealmExpansions\Frontend\ReadingRoom\ReadingRoomNavigation;
use GreatMarketrealmExpansions\Frontend\ReadingRoom\ReadingRoomPage;
use GreatMarketrealmExpansions\Import\ImportService;
use GreatMarketrealmExpansions\Library\InMemoryActivationStore;
use GreatMarketrealmExpansions\Library\Library;
use GreatMarketrealmExpansions\Review\InMemoryReviewQueueStore;
use GreatMarketrealmExpansions\Review\ReviewService;
use PHPUnit\Framework\TestCase;

final class ReadingRoomReviewDeskTest extends TestCase
{
    private ReadingRoomPage $page;
    private InMemoryReviewQueueStore $queue;
    private Catalogue $catalogue;

    protected function setUp(): void
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        $validator = new ContentValidator($schemas);
        $this->catalogue = new Catalogue(new ExpansionRegistry(), new ContentRegistry($validator));
        $library = new Library($this->catalogue, new InMemoryActivationStore());
        $this->queue = new InMemoryReviewQueueStore();
        $this->page = new ReadingRoomPage(
            $this->catalogue,
            $library,
            new ReadingRoomAccess(),
            new ReadingRoomNavigation(),
            new ImportService($validator),
            null,
            new ReviewService($validator),
            $this->queue,
            new AlmanacProposalService(),
            $schemas
        );
        $_POST = [];
    }

    protected function tearDown(): void { $_POST = []; }

    private function unresolvedJson(): string
    {
        return (string) json_encode([
            'source' => ['type' => 'google-doc', 'id' => 'midnight-menu', 'title' => 'Synthetic Midnight Menu'],
            'records' => [[
                'id' => 'google-heading-1',
                'source' => ['heading' => 'Pizza Mimic', 'heading_level' => 2, 'ordinal' => 1],
                'data' => ['name' => 'Pizza Mimic', 'description' => 'The pizza is the mimic.'],
                'review_required' => true,
            ]],
        ], JSON_UNESCAPED_SLASHES);
    }

    private function queueSource(): string
    {
        $_POST = [
            ReadingRoomPage::REVIEW_QUEUE_SUBMIT_FIELD => '1',
            ReadingRoomPage::REVIEW_JSON_FIELD => $this->unresolvedJson(),
        ];
        return $this->page->render('review', 'https://example.test/expansions/');
    }

    public function test_review_navigation_is_open(): void
    {
        self::assertTrue((new ReadingRoomNavigation())->items()['review']['available']);
    }

    public function test_empty_review_desk_explains_handoff_from_import(): void
    {
        $html = $this->page->render('review');
        self::assertStringContainsString("The Keeper's Review Desk", $html);
        self::assertStringContainsString('No papers on the desk.', $html);
        self::assertStringContainsString('Send to Review Desk', $html);
    }

    public function test_review_desk_states_reviewed_is_not_published_boundary(): void
    {
        self::assertStringContainsString('Reviewed ≠ Published.', $this->page->render('review'));
    }

    public function test_staged_source_can_be_sent_to_private_review_queue(): void
    {
        $html = $this->queueSource();
        self::assertStringContainsString('The staged source is now on the Review Desk.', $html);
        self::assertStringContainsString('Synthetic Midnight Menu', $html);
        self::assertNotNull($this->queue->load());
    }

    public function test_unresolved_google_record_prefills_source_name_and_prose(): void
    {
        $html = $this->queueSource();
        self::assertStringContainsString('value="Pizza Mimic"', $html);
        self::assertStringContainsString('The pizza is the mimic.', $html);
        self::assertStringContainsString('content_type_unresolved', $html);
    }

    public function test_review_form_offers_core_content_types_without_guessing_one(): void
    {
        $html = $this->queueSource();
        self::assertStringContainsString('<option value="">Choose what this record is…</option>', $html);
        self::assertStringContainsString('<option value="monster"', $html);
        self::assertStringNotContainsString('<option value="monster" selected', $html);
    }

    public function test_keeper_can_classify_and_amend_unresolved_record_through_review_api(): void
    {
        $this->queueSource();
        $_POST = [
            ReadingRoomPage::REVIEW_DECISION_SUBMIT_FIELD => '1',
            ReadingRoomPage::REVIEW_RECORD_FIELD => 'google-heading-1',
            ReadingRoomPage::REVIEW_ACTION_FIELD => 'amend',
            'gmrexp_review_type' => 'monster',
            'gmrexp_review_key' => 'pizza-mimic',
            'gmrexp_review_name' => 'Pizza Mimic',
            'gmrexp_review_description' => 'The pizza is the mimic.',
            'gmrexp_review_data' => '{"name":"Pizza Mimic","description":"The pizza is the mimic."}',
            'gmrexp_review_note' => 'Classified by the Keeper.',
        ];
        $html = $this->page->render('review');
        self::assertStringContainsString('monster:pizza-mimic', $html);
        self::assertStringContainsString('Accepted for the proposed Almanac', $html);
        self::assertSame('amend', $this->queue->load()['decisions']['google-heading-1']['action']);
    }

    public function test_keeper_can_ignore_structural_heading(): void
    {
        $this->queueSource();
        $_POST = [
            ReadingRoomPage::REVIEW_DECISION_SUBMIT_FIELD => '1',
            ReadingRoomPage::REVIEW_RECORD_FIELD => 'google-heading-1',
            ReadingRoomPage::REVIEW_ACTION_FIELD => 'reject',
            'gmrexp_review_note' => 'Structural heading only.',
        ];
        $html = $this->page->render('review');
        self::assertStringContainsString('Ignored / rejected as canonical content', $html);
        self::assertStringContainsString('Structural heading only.', $html);
    }

    public function test_keeper_can_reconsider_persisted_decision(): void
    {
        $this->queueSource();
        $state = $this->queue->load();
        $state['decisions']['google-heading-1'] = ['action' => 'reject', 'note' => 'Temporary'];
        $this->queue->save($state);
        $_POST = [
            ReadingRoomPage::REVIEW_DECISION_SUBMIT_FIELD => '1',
            ReadingRoomPage::REVIEW_RECORD_FIELD => 'google-heading-1',
            ReadingRoomPage::REVIEW_ACTION_FIELD => 'reset',
        ];
        $html = $this->page->render('review');
        self::assertArrayNotHasKey('google-heading-1', $this->queue->load()['decisions']);
        self::assertStringContainsString('Choose what this record is…', $html);
    }

    public function test_invalid_amendment_is_refused_and_not_persisted(): void
    {
        $this->queueSource();
        $_POST = [
            ReadingRoomPage::REVIEW_DECISION_SUBMIT_FIELD => '1',
            ReadingRoomPage::REVIEW_RECORD_FIELD => 'google-heading-1',
            ReadingRoomPage::REVIEW_ACTION_FIELD => 'amend',
            'gmrexp_review_type' => 'monster',
            'gmrexp_review_key' => '',
            'gmrexp_review_name' => 'Pizza Mimic',
            'gmrexp_review_description' => '',
            'gmrexp_review_data' => '{"name":"Pizza Mimic"}',
        ];
        $html = $this->page->render('review');
        self::assertStringContainsString('Review decision not recorded.', $html);
        self::assertSame([], $this->queue->load()['decisions']);
    }

    public function test_clear_review_desk_does_not_mutate_catalogue(): void
    {
        $this->queueSource();
        $_POST = [ReadingRoomPage::REVIEW_CLEAR_SUBMIT_FIELD => '1'];
        $html = $this->page->render('review');
        self::assertNull($this->queue->load());
        self::assertCount(0, $this->catalogue->allContent());
        self::assertStringContainsString('No canonical content was changed.', $html);
    }

    public function test_v8_keeps_route_and_review_api_contracts_stable(): void
    {
        self::assertSame('1.0.0', ReadingRoomPage::ROUTE_VERSION);
        self::assertSame('1.0.0', ReviewService::API_VERSION);
    }

    public function test_import_staging_renders_send_to_review_desk_handoff(): void
    {
        $_POST = [
            ReadingRoomPage::IMPORT_SUBMIT_FIELD => '1',
            ReadingRoomPage::IMPORT_JSON_FIELD => $this->unresolvedJson(),
        ];

        $html = $this->page->render('import', 'https://example.test/expansions/');

        self::assertStringContainsString('Send to Review Desk', $html);
        self::assertStringContainsString('gmrexp_review_queue_submit', $html);
        self::assertStringContainsString('gmrexp_section=review', $html);
    }


    public function test_shelving_trolley_waits_for_an_approved_definition(): void
    {
        $html = $this->queueSource();
        self::assertStringContainsString('Prepare a proposed Almanac', $html);
        self::assertStringContainsString('Accept at least one canonical definition', $html);
        self::assertStringContainsString('disabled', $html);
    }

    public function test_shelving_trolley_builds_persistent_proposal_from_accepted_definition(): void
    {
        $this->queueSource();
        $state = $this->queue->load();
        $state['decisions']['google-heading-1'] = [
            'action' => 'amend',
            'type' => 'monster',
            'key' => 'pizza-mimic',
            'data' => ['name' => 'Pizza Mimic'],
            'note' => '',
        ];
        $this->queue->save($state);

        $_POST = [
            ReadingRoomPage::ALMANAC_PROPOSAL_SUBMIT_FIELD => '1',
            'gmrexp_almanac_key' => 'midnight-menu',
            'gmrexp_almanac_name' => 'The Midnight Menu',
            'gmrexp_almanac_version' => '0.1.0',
            'gmrexp_almanac_description' => 'A proposed expansion.',
            'gmrexp_almanac_artwork' => 'assets/library-cover.jpg',
        ];
        $html = $this->page->render('review');

        self::assertStringContainsString('The Shelving Trolley has assembled a proposed Almanac.', $html);
        self::assertStringContainsString('midnight-menu', $html);
        self::assertStringContainsString('assets/library-cover.jpg', $html);
        self::assertSame(1, $this->queue->load()['proposal']['definition_count']);
    }

    public function test_proposal_explicitly_states_proposed_is_not_published(): void
    {
        $html = $this->queueSource();
        self::assertStringContainsString('Proposed ≠ Published', $html);
    }

    public function test_new_review_decision_invalidates_stale_proposal(): void
    {
        $this->queueSource();
        $state = $this->queue->load();
        $state['proposal'] = ['manifest' => ['key' => 'stale']];
        $this->queue->save($state);

        $_POST = [
            ReadingRoomPage::REVIEW_DECISION_SUBMIT_FIELD => '1',
            ReadingRoomPage::REVIEW_RECORD_FIELD => 'google-heading-1',
            ReadingRoomPage::REVIEW_ACTION_FIELD => 'reject',
        ];
        $this->page->render('review');

        self::assertArrayNotHasKey('proposal', $this->queue->load());
    }


    public function test_magic_item_selection_exposes_schema_required_fields_after_failed_amendment(): void
    {
        $this->queueSource();
        $_POST = [
            ReadingRoomPage::REVIEW_DECISION_SUBMIT_FIELD => '1',
            ReadingRoomPage::REVIEW_RECORD_FIELD => 'google-heading-1',
            ReadingRoomPage::REVIEW_ACTION_FIELD => 'amend',
            'gmrexp_review_type' => 'magic-item',
            'gmrexp_review_key' => 'midnight-trinket',
            'gmrexp_review_name' => 'Midnight Trinket',
            'gmrexp_review_description' => 'Synthetic magic item.',
            'gmrexp_review_data' => '{"name":"Midnight Trinket"}',
        ];

        $html = $this->page->render('review');

        self::assertStringContainsString('Review decision not recorded.', $html);
        self::assertStringContainsString('name="gmrexp_review_schema[category]"', $html);
        self::assertStringContainsString('name="gmrexp_review_schema[rarity]"', $html);
        self::assertStringContainsString('value="magic-item" selected', $html);
    }

    public function test_keeper_can_accept_magic_item_after_completing_schema_requirements(): void
    {
        $this->queueSource();
        $_POST = [
            ReadingRoomPage::REVIEW_DECISION_SUBMIT_FIELD => '1',
            ReadingRoomPage::REVIEW_RECORD_FIELD => 'google-heading-1',
            ReadingRoomPage::REVIEW_ACTION_FIELD => 'amend',
            'gmrexp_review_type' => 'magic-item',
            'gmrexp_review_key' => 'midnight-trinket',
            'gmrexp_review_name' => 'Midnight Trinket',
            'gmrexp_review_description' => 'Synthetic magic item.',
            'gmrexp_review_data' => '{"name":"Midnight Trinket"}',
            'gmrexp_review_schema' => [
                'category' => 'wondrous-item',
                'rarity' => 'rare',
            ],
        ];

        $html = $this->page->render('review');
        $decision = $this->queue->load()['decisions']['google-heading-1'];

        self::assertStringContainsString('magic-item:midnight-trinket', $html);
        self::assertStringContainsString('Accepted for the proposed Almanac', $html);
        self::assertSame('wondrous-item', $decision['data']['category']);
        self::assertSame('rare', $decision['data']['rarity']);
    }

    public function test_weapon_required_map_field_is_schema_aware_json_editor(): void
    {
        $this->queueSource();
        $_POST = [
            ReadingRoomPage::REVIEW_DECISION_SUBMIT_FIELD => '1',
            ReadingRoomPage::REVIEW_RECORD_FIELD => 'google-heading-1',
            ReadingRoomPage::REVIEW_ACTION_FIELD => 'amend',
            'gmrexp_review_type' => 'weapon',
            'gmrexp_review_key' => 'fixture-blade',
            'gmrexp_review_name' => 'Fixture Blade',
            'gmrexp_review_description' => '',
            'gmrexp_review_data' => '{"name":"Fixture Blade"}',
        ];

        $html = $this->page->render('review');

        self::assertStringContainsString('name="gmrexp_review_schema[category]"', $html);
        self::assertStringContainsString('name="gmrexp_review_schema[damage]"', $html);
        self::assertStringContainsString('Enter a JSON object', $html);
    }

    public function test_schema_required_json_map_is_typed_before_review_validation(): void
    {
        $this->queueSource();
        $_POST = [
            ReadingRoomPage::REVIEW_DECISION_SUBMIT_FIELD => '1',
            ReadingRoomPage::REVIEW_RECORD_FIELD => 'google-heading-1',
            ReadingRoomPage::REVIEW_ACTION_FIELD => 'amend',
            'gmrexp_review_type' => 'weapon',
            'gmrexp_review_key' => 'fixture-blade',
            'gmrexp_review_name' => 'Fixture Blade',
            'gmrexp_review_description' => '',
            'gmrexp_review_data' => '{"name":"Fixture Blade"}',
            'gmrexp_review_schema' => [
                'category' => 'simple-melee',
                'damage' => '{"dice":"1d6","type":"slashing"}',
            ],
        ];

        $html = $this->page->render('review');
        $decision = $this->queue->load()['decisions']['google-heading-1'];

        self::assertStringContainsString('weapon:fixture-blade', $html);
        self::assertSame(['dice' => '1d6', 'type' => 'slashing'], $decision['data']['damage']);
    }

    public function test_invalid_required_json_is_refused_with_friendly_field_error(): void
    {
        $this->queueSource();
        $_POST = [
            ReadingRoomPage::REVIEW_DECISION_SUBMIT_FIELD => '1',
            ReadingRoomPage::REVIEW_RECORD_FIELD => 'google-heading-1',
            ReadingRoomPage::REVIEW_ACTION_FIELD => 'amend',
            'gmrexp_review_type' => 'weapon',
            'gmrexp_review_key' => 'fixture-blade',
            'gmrexp_review_name' => 'Fixture Blade',
            'gmrexp_review_description' => '',
            'gmrexp_review_data' => '{"name":"Fixture Blade"}',
            'gmrexp_review_schema' => [
                'category' => 'simple-melee',
                'damage' => '{broken json}',
            ],
        ];

        $html = $this->page->render('review');

        self::assertStringContainsString('Review decision not recorded.', $html);
        self::assertStringContainsString('Damage must contain valid JSON.', $html);
        self::assertSame([], $this->queue->load()['decisions']);
    }

    public function test_schema_projection_includes_requirements_for_all_core_types(): void
    {
        $html = $this->queueSource();

        self::assertStringContainsString('&quot;magic-item&quot;', $html);
        self::assertStringContainsString('&quot;parent_class&quot;', $html);
        self::assertStringContainsString('&quot;parent_race&quot;', $html);
        self::assertStringContainsString('&quot;casting_time&quot;', $html);
        self::assertStringContainsString('&quot;armour_class&quot;', $html);
    }

    public function test_race_requirements_render_keeper_friendly_controls_instead_of_raw_json(): void
    {
        $this->queueSource();
        $_POST = [
            ReadingRoomPage::REVIEW_DECISION_SUBMIT_FIELD => '1',
            ReadingRoomPage::REVIEW_RECORD_FIELD => 'google-heading-1',
            ReadingRoomPage::REVIEW_ACTION_FIELD => 'amend',
            'gmrexp_review_type' => 'race',
            'gmrexp_review_key' => 'pizzakin',
            'gmrexp_review_name' => 'Pizzakin',
            'gmrexp_review_description' => 'Synthetic race.',
            'gmrexp_review_data' => '{"name":"Pizzakin"}',
        ];

        $html = $this->page->render('review');

        self::assertStringContainsString('list="gmrexp-creature-types"', $html);
        self::assertStringContainsString('list="gmrexp-creature-sizes"', $html);
        self::assertStringContainsString('Walking speed in feet', $html);
        self::assertStringContainsString('Separate languages with commas', $html);
        self::assertStringContainsString('canonical-key | Trait Name | Description', $html);
    }

    public function test_keeper_can_accept_race_using_friendly_structured_requirements(): void
    {
        $this->queueSource();
        $_POST = [
            ReadingRoomPage::REVIEW_DECISION_SUBMIT_FIELD => '1',
            ReadingRoomPage::REVIEW_RECORD_FIELD => 'google-heading-1',
            ReadingRoomPage::REVIEW_ACTION_FIELD => 'amend',
            'gmrexp_review_type' => 'race',
            'gmrexp_review_key' => 'pizzakin',
            'gmrexp_review_name' => 'Pizzakin',
            'gmrexp_review_description' => 'Synthetic race.',
            'gmrexp_review_data' => '{"name":"Pizzakin"}',
            'gmrexp_review_schema' => [
                'creature_type' => 'Humanoid',
                'size' => 'Medium',
                'speed' => '30',
                'languages' => 'Common, Market Tongue',
                'traits' => "hot-from-oven | Hot From the Oven | Fire resistance.\ncheese-pull | Cheese Pull | Stretchy cheese.",
            ],
        ];

        $html = $this->page->render('review');
        $decision = $this->queue->load()['decisions']['google-heading-1'];

        self::assertStringContainsString('race:pizzakin', $html);
        self::assertSame('Humanoid', $decision['data']['creature_type']);
        self::assertSame(['value' => 'Medium'], $decision['data']['size']);
        self::assertSame(['walk' => 30], $decision['data']['speed']);
        self::assertSame(['Common', 'Market Tongue'], $decision['data']['languages']);
        self::assertSame('hot-from-oven', $decision['data']['traits'][0]['key']);
        self::assertSame('Hot From the Oven', $decision['data']['traits'][0]['name']);
    }

    public function test_race_trait_lines_require_keeper_supplied_key_and_name(): void
    {
        $this->queueSource();
        $_POST = [
            ReadingRoomPage::REVIEW_DECISION_SUBMIT_FIELD => '1',
            ReadingRoomPage::REVIEW_RECORD_FIELD => 'google-heading-1',
            ReadingRoomPage::REVIEW_ACTION_FIELD => 'amend',
            'gmrexp_review_type' => 'race',
            'gmrexp_review_key' => 'pizzakin',
            'gmrexp_review_name' => 'Pizzakin',
            'gmrexp_review_data' => '{"name":"Pizzakin"}',
            'gmrexp_review_schema' => [
                'creature_type' => 'Humanoid', 'size' => 'Medium', 'speed' => '30',
                'languages' => 'Common', 'traits' => 'Hot From the Oven',
            ],
        ];

        $html = $this->page->render('review');

        self::assertStringContainsString('Review decision not recorded.', $html);
        self::assertStringContainsString('canonical-key | Trait Name | Description', $html);
        self::assertSame([], $this->queue->load()['decisions']);
    }

    public function test_race_friendly_controls_still_leave_optional_complex_data_to_advanced_editor(): void
    {
        $this->queueSource();
        $_POST = [
            ReadingRoomPage::REVIEW_DECISION_SUBMIT_FIELD => '1',
            ReadingRoomPage::REVIEW_RECORD_FIELD => 'google-heading-1',
            ReadingRoomPage::REVIEW_ACTION_FIELD => 'amend',
            'gmrexp_review_type' => 'race',
            'gmrexp_review_key' => 'pizzakin',
            'gmrexp_review_name' => 'Pizzakin',
            'gmrexp_review_data' => '{"name":"Pizzakin","resistances":["fire"]}',
        ];

        $html = $this->page->render('review');
        self::assertStringContainsString('Optional schema fields can still be added here.', $html);
        self::assertStringContainsString('&quot;resistances&quot;', $html);
    }

}
