<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Frontend\ReadingRoom;

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
use PHPUnit\Framework\TestCase;

final class ReadingRoomImportDeskTest extends TestCase
{
    private Catalogue $catalogue;
    private ReadingRoomPage $page;

    protected function setUp(): void
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) {
            $types->add($type);
        }

        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        $validator = new ContentValidator($schemas);

        $expansions = new ExpansionRegistry();
        $content = new ContentRegistry($validator);
        $this->catalogue = new Catalogue($expansions, $content);
        $library = new Library($this->catalogue, new InMemoryActivationStore());

        $this->page = new ReadingRoomPage(
            $this->catalogue,
            $library,
            new ReadingRoomAccess(),
            new ReadingRoomNavigation(),
            new ImportService($validator)
        );

        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_POST = [];
    }

    private function json(array $recordOverrides = [], array $sourceOverrides = []): string
    {
        $record = array_replace_recursive([
            'id' => 'heading-12',
            'type' => 'feat',
            'key' => 'fixture-knack',
            'source' => [
                'heading' => 'Fixture Knack',
                'section' => 'Chapter Two',
            ],
            'data' => [
                'name' => 'Fixture Knack',
                'description' => 'Synthetic import proving content.',
            ],
        ], $recordOverrides);

        $source = array_replace([
            'type' => 'structured-source',
            'id' => 'fixture-sourcebook',
            'title' => 'Fixture Sourcebook',
            'version' => 'draft-7',
        ], $sourceOverrides);

        return (string) json_encode(['source' => $source, 'records' => [$record]], JSON_UNESCAPED_SLASHES);
    }

    private function submit(string $json): string
    {
        $_POST = [
            ReadingRoomPage::IMPORT_SUBMIT_FIELD => '1',
            ReadingRoomPage::IMPORT_JSON_FIELD => $json,
        ];

        return $this->page->render('import', 'https://example.test/expansions/');
    }

    public function test_import_desk_is_an_available_navigation_item(): void
    {
        self::assertTrue((new ReadingRoomNavigation())->items()['import']['available']);
    }

    public function test_import_desk_renders_open_workflow_instead_of_reserved_placeholder(): void
    {
        $html = $this->page->render('import');

        self::assertStringContainsString("The Keeper's Import Desk", $html);
        self::assertStringContainsString('Stage Structured Source Material', $html);
        self::assertStringNotContainsString('Reserved desk', $html);
    }

    public function test_import_desk_states_imported_is_not_canonical_boundary(): void
    {
        $html = $this->page->render('import');

        self::assertStringContainsString('Imported ≠ Canonical.', $html);
        self::assertStringContainsString('V.7 can acquire an accessible Google Doc HTML export', $html);
    }

    public function test_import_form_posts_back_to_shortcode_host_import_section(): void
    {
        $html = $this->page->render('import', 'https://example.test/expansions/');

        self::assertStringContainsString('action="https://example.test/expansions/?gmrexp_section=import"', $html);
    }

    public function test_import_form_uses_stable_field_contracts(): void
    {
        self::assertSame('gmrexp_import_submit', ReadingRoomPage::IMPORT_SUBMIT_FIELD);
        self::assertSame('gmrexp_reading_room_import', ReadingRoomPage::IMPORT_NONCE_ACTION);
        self::assertSame('_gmrexp_import_nonce', ReadingRoomPage::IMPORT_NONCE_FIELD);
        self::assertSame('gmrexp_import_json', ReadingRoomPage::IMPORT_JSON_FIELD);
    }

    public function test_import_desk_exposes_neutral_example_without_canonical_marketrealm_content(): void
    {
        $html = $this->page->render('import');

        self::assertStringContainsString('Synthetic Sourcebook', $html);
        self::assertStringContainsString('synthetic-feat', $html);
        self::assertStringContainsString('Non-canonical example content', $html);
    }

    public function test_valid_json_stages_source_and_record(): void
    {
        $html = $this->submit($this->json());

        self::assertStringContainsString('Staging result', $html);
        self::assertStringContainsString('Fixture Sourcebook', $html);
        self::assertStringContainsString('fixture-knack', $html);
        self::assertStringContainsString('Structurally valid', $html);
    }

    public function test_valid_stage_reports_record_and_valid_counts(): void
    {
        $html = $this->submit($this->json());

        self::assertMatchesRegularExpression('/<dt>Records<\/dt><dd>1<\/dd>/', $html);
        self::assertMatchesRegularExpression('/<dt>Valid<\/dt><dd>1<\/dd>/', $html);
        self::assertMatchesRegularExpression('/<dt>Errors<\/dt><dd>0<\/dd>/', $html);
    }

    public function test_explicit_review_flag_is_presented_as_review_requested(): void
    {
        $html = $this->submit($this->json(['review_required' => true]));

        self::assertStringContainsString('Review requested', $html);
        self::assertStringContainsString('keeper_review_requested', $html);
        self::assertStringContainsString('WARNING', $html);
    }

    public function test_invalid_json_surfaces_import_api_error_code(): void
    {
        $html = $this->submit('{definitely-not-json');

        self::assertStringContainsString('Needs attention', $html);
        self::assertStringContainsString('json_invalid', $html);
        self::assertStringContainsString('No records were staged.', $html);
    }

    public function test_missing_explicit_content_type_is_not_guessed_in_frontend(): void
    {
        $document = json_decode($this->json(), true);
        unset($document['records'][0]['type']);
        $html = $this->submit((string) json_encode($document));

        self::assertStringContainsString('content_type_unresolved', $html);
        self::assertStringContainsString('Unresolved staged record', $html);
    }

    public function test_missing_explicit_canonical_key_is_not_generated(): void
    {
        $document = json_decode($this->json(), true);
        unset($document['records'][0]['key']);
        $html = $this->submit((string) json_encode($document));

        self::assertStringContainsString('content_key_unresolved', $html);
    }

    public function test_source_context_is_visible_for_keeper_inspection(): void
    {
        $html = $this->submit($this->json());

        self::assertStringContainsString('Source context', $html);
        self::assertStringContainsString('Chapter Two', $html);
        self::assertStringContainsString('Fixture Knack', $html);
    }

    public function test_schema_validation_failure_is_human_visible(): void
    {
        $document = json_decode($this->json(), true);
        $document['records'][0]['data'] = [];
        $html = $this->submit((string) json_encode($document));

        self::assertStringContainsString('schema_validation_failed', $html);
        self::assertStringContainsString('Field:', $html);
        self::assertStringContainsString('name', $html);
    }

    public function test_submitted_json_is_preserved_for_correction_and_escaped(): void
    {
        $json = $this->json(['data' => ['name' => '<script>alert(1)</script>']]);
        $html = $this->submit($json);

        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function test_staging_does_not_mutate_canonical_catalogue(): void
    {
        self::assertCount(0, $this->catalogue->allContent());
        $this->submit($this->json());
        self::assertCount(0, $this->catalogue->allContent());
    }

    public function test_staging_boundary_says_result_is_request_local_and_review_is_future_phase(): void
    {
        $html = $this->submit($this->json());

        self::assertStringContainsString('this staging result remains request-local until the Keeper explicitly sends it to the Review Desk', $html);
        self::assertStringContainsString('request-local until the Keeper explicitly sends it to the Review Desk', $html);
    }

    public function test_v6_does_not_change_reading_room_route_contract(): void
    {
        self::assertSame('1.0.0', ReadingRoomPage::ROUTE_VERSION);
    }
}
