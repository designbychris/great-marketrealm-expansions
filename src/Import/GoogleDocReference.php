<?php
namespace GreatMarketrealmExpansions\Import;

defined('ABSPATH') || exit;

use InvalidArgumentException;

final class GoogleDocReference
{
    public function __construct(private string $documentId, private string $sourceUrl)
    {
        $this->documentId = trim($this->documentId);
        $this->sourceUrl = trim($this->sourceUrl);
        if ($this->documentId === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $this->documentId)) {
            throw new InvalidArgumentException('A Google Doc reference requires a valid document ID.');
        }
    }

    public static function fromUrl(string $url): self
    {
        $url = trim($url);
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if (($parts['scheme'] ?? '') !== 'https' || $host !== 'docs.google.com') {
            throw new InvalidArgumentException('Google Doc URLs must use https://docs.google.com.');
        }

        if (!preg_match('#^/document/d/([A-Za-z0-9_-]+)(?:/|$)#', $path, $matches)) {
            throw new InvalidArgumentException('The URL does not contain a Google Docs document ID.');
        }

        return new self($matches[1], $url);
    }

    public function documentId(): string { return $this->documentId; }
    public function sourceUrl(): string { return $this->sourceUrl; }
    public function exportUrl(): string
    {
        return 'https://docs.google.com/document/d/' . rawurlencode($this->documentId) . '/export?format=html';
    }
}
