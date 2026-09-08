<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Import;

use GreatMarketrealmExpansions\Import\GoogleDocReference;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class GoogleDocReferenceTest extends TestCase
{
    public function test_edit_url_resolves_document_identity(): void
    {
        $ref = GoogleDocReference::fromUrl('https://docs.google.com/document/d/fixture_DOC-123/edit?usp=sharing');
        self::assertSame('fixture_DOC-123', $ref->documentId());
        self::assertSame('https://docs.google.com/document/d/fixture_DOC-123/export?format=html', $ref->exportUrl());
    }

    public function test_non_google_host_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        GoogleDocReference::fromUrl('https://example.test/document/d/fixture/edit');
    }

    public function test_non_https_google_url_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        GoogleDocReference::fromUrl('http://docs.google.com/document/d/fixture/edit');
    }

    public function test_non_document_google_url_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        GoogleDocReference::fromUrl('https://docs.google.com/spreadsheets/d/fixture/edit');
    }
}
