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
use GreatMarketrealmExpansions\Import\GoogleDocsSourceAdapter;
use GreatMarketrealmExpansions\Import\ImportService;
use GreatMarketrealmExpansions\Library\InMemoryActivationStore;
use GreatMarketrealmExpansions\Library\Library;
use PHPUnit\Framework\TestCase;

final class ReadingRoomGoogleDocsTest extends TestCase
{
    private Catalogue $catalogue;
    private ReadingRoomPage $page;

    protected function setUp(): void
    {
        $types = new ContentTypeCatalogue();
        foreach (CoreContentTypes::all() as $type) { $types->add($type); }
        $schemas = new SchemaRegistry();
        CoreSchemas::register($schemas, $types);
        $validator = new ContentValidator($schemas);
        $this->catalogue = new Catalogue(new ExpansionRegistry(), new ContentRegistry($validator));
        $library = new Library($this->catalogue, new InMemoryActivationStore());
        $adapter = new GoogleDocsSourceAdapter(static fn (string $url): array => [
            'ok' => true,
            'status' => 200,
            'body' => '<html><head><title>Synthetic Google Source</title></head><body><h1>Fixture Heading</h1><p>Fixture prose.</p></body></html>',
            'message' => '',
        ]);
        $this->page = new ReadingRoomPage(
            $this->catalogue, $library, new ReadingRoomAccess(), new ReadingRoomNavigation(),
            new ImportService($validator), $adapter
        );
        $_POST = [];
    }

    protected function tearDown(): void { $_POST = []; }

    public function test_import_desk_exposes_google_docs_acquisition_form(): void
    {
        $html = $this->page->render('import');
        self::assertStringContainsString('Pippin finds the Google Docs', $html);
        self::assertStringContainsString('Find Google Doc', $html);
        self::assertStringContainsString(ReadingRoomPage::GOOGLE_DOC_URL_FIELD, $html);
    }

    public function test_google_doc_submission_flows_into_existing_import_staging_pipeline(): void
    {
        $_POST = [
            ReadingRoomPage::GOOGLE_DOC_SUBMIT_FIELD => '1',
            ReadingRoomPage::GOOGLE_DOC_URL_FIELD => 'https://docs.google.com/document/d/fixture-doc/edit',
        ];
        $html = $this->page->render('import', 'https://example.test/expansions/');
        self::assertStringContainsString('Google Doc acquired.', $html);
        self::assertStringContainsString('Synthetic Google Source', $html);
        self::assertStringContainsString('content_type_unresolved', $html);
        self::assertStringContainsString('content_key_unresolved', $html);
    }

    public function test_google_doc_transformation_is_visible_for_keeper_correction(): void
    {
        $_POST = [
            ReadingRoomPage::GOOGLE_DOC_SUBMIT_FIELD => '1',
            ReadingRoomPage::GOOGLE_DOC_URL_FIELD => 'https://docs.google.com/document/d/fixture-doc/edit',
        ];
        $html = $this->page->render('import');
        self::assertStringContainsString('&quot;type&quot;: &quot;google-doc&quot;', $html);
        self::assertStringContainsString('Fixture Heading', $html);
    }

    public function test_google_docs_intake_does_not_mutate_catalogue(): void
    {
        $_POST = [
            ReadingRoomPage::GOOGLE_DOC_SUBMIT_FIELD => '1',
            ReadingRoomPage::GOOGLE_DOC_URL_FIELD => 'https://docs.google.com/document/d/fixture-doc/edit',
        ];
        $this->page->render('import');
        self::assertCount(0, $this->catalogue->allContent());
    }

    public function test_invalid_google_doc_url_is_explained_without_fetching_arbitrary_host(): void
    {
        $_POST = [
            ReadingRoomPage::GOOGLE_DOC_SUBMIT_FIELD => '1',
            ReadingRoomPage::GOOGLE_DOC_URL_FIELD => 'https://example.test/not-google',
        ];
        $html = $this->page->render('import');
        self::assertStringContainsString('Pippin could not retrieve that document.', $html);
        self::assertStringContainsString('google_doc_url_invalid', $html);
    }

    public function test_v7_keeps_reading_room_route_contract_stable(): void
    {
        self::assertSame('1.0.0', ReadingRoomPage::ROUTE_VERSION);
    }
}
