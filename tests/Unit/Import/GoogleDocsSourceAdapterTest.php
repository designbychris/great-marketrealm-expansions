<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Import;

use GreatMarketrealmExpansions\Import\GoogleDocReference;
use GreatMarketrealmExpansions\Import\GoogleDocsSourceAdapter;
use PHPUnit\Framework\TestCase;

final class GoogleDocsSourceAdapterTest extends TestCase
{
    private function html(): string
    {
        return '<html><head><title>Synthetic Sourcebook</title></head><body>'
            . '<h1>Fixture Heading</h1><p>Fixture prose.</p>'
            . '<h2>Second Heading</h2><p>More source prose.</p></body></html>';
    }

    public function test_adapter_fetches_only_canonical_google_export_url(): void
    {
        $seen = '';
        $adapter = new GoogleDocsSourceAdapter(function (string $url) use (&$seen): array {
            $seen = $url;
            return ['ok' => true, 'status' => 200, 'body' => $this->html(), 'message' => ''];
        });
        $result = $adapter->acquire('https://docs.google.com/document/d/fixture-doc/edit');

        self::assertTrue($result->successful());
        self::assertSame('https://docs.google.com/document/d/fixture-doc/export?format=html', $seen);
    }

    public function test_transformation_preserves_google_source_identity_and_title(): void
    {
        $adapter = new GoogleDocsSourceAdapter();
        $doc = $adapter->transformHtml(GoogleDocReference::fromUrl('https://docs.google.com/document/d/fixture-doc/edit'), $this->html());

        self::assertSame('google-doc', $doc['source']['type']);
        self::assertSame('fixture-doc', $doc['source']['id']);
        self::assertSame('Synthetic Sourcebook', $doc['source']['title']);
        self::assertSame(GoogleDocsSourceAdapter::ADAPTER_VERSION, $doc['source']['metadata']['adapter_version']);
    }

    public function test_headings_become_neutral_review_records_without_invented_identity(): void
    {
        $adapter = new GoogleDocsSourceAdapter();
        $doc = $adapter->transformHtml(GoogleDocReference::fromUrl('https://docs.google.com/document/d/fixture-doc/edit'), $this->html());

        self::assertCount(2, $doc['records']);
        self::assertSame('Fixture Heading', $doc['records'][0]['data']['name']);
        self::assertSame('Fixture prose.', $doc['records'][0]['data']['description']);
        self::assertTrue($doc['records'][0]['review_required']);
        self::assertArrayNotHasKey('type', $doc['records'][0]);
        self::assertArrayNotHasKey('key', $doc['records'][0]);
    }

    public function test_invalid_google_doc_url_returns_stable_issue(): void
    {
        $result = (new GoogleDocsSourceAdapter())->acquire('https://example.test/not-google');
        self::assertFalse($result->successful());
        self::assertSame('google_doc_url_invalid', $result->issue()?->code());
    }

    public function test_failed_fetch_returns_stable_issue_without_document(): void
    {
        $adapter = new GoogleDocsSourceAdapter(static fn (string $url): array => [
            'ok' => false, 'status' => 403, 'body' => '', 'message' => 'Not accessible.',
        ]);
        $result = $adapter->acquire('https://docs.google.com/document/d/private-doc/edit');
        self::assertFalse($result->successful());
        self::assertSame('google_doc_fetch_failed', $result->issue()?->code());
        self::assertNull($result->document());
    }

    public function test_empty_export_is_refused(): void
    {
        $adapter = new GoogleDocsSourceAdapter(static fn (string $url): array => [
            'ok' => true, 'status' => 200, 'body' => '', 'message' => '',
        ]);
        $result = $adapter->acquire('https://docs.google.com/document/d/empty-doc/edit');
        self::assertSame('google_doc_empty', $result->issue()?->code());
    }

    public function test_large_export_is_refused_before_transformation(): void
    {
        $adapter = new GoogleDocsSourceAdapter(static fn (string $url): array => [
            'ok' => true, 'status' => 200, 'body' => str_repeat('x', GoogleDocsSourceAdapter::MAX_BYTES + 1), 'message' => '',
        ]);
        $result = $adapter->acquire('https://docs.google.com/document/d/large-doc/edit');
        self::assertSame('google_doc_too_large', $result->issue()?->code());
    }

    public function test_acquisition_json_is_non_executable_structured_document(): void
    {
        $adapter = new GoogleDocsSourceAdapter(fn (string $url): array => [
            'ok' => true, 'status' => 200, 'body' => $this->html(), 'message' => '',
        ]);
        $result = $adapter->acquire('https://docs.google.com/document/d/fixture-doc/edit');
        $decoded = json_decode((string) $result->json(), true);
        self::assertSame('google-doc', $decoded['source']['type']);
        self::assertSame('google-heading-1', $decoded['records'][0]['id']);
    }
}
