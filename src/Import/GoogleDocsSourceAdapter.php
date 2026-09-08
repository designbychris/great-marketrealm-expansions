<?php
namespace GreatMarketrealmExpansions\Import;

defined('ABSPATH') || exit;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;

final class GoogleDocsSourceAdapter
{
    public const ADAPTER_VERSION = '1.0.0';
    public const MAX_BYTES = 5242880;

    /** @var null|\Closure(string):array{ok:bool,status:int,body:string,message:string} */
    private ?\Closure $fetcher;

    /** @param null|callable(string):array{ok:bool,status:int,body:string,message:string} $fetcher */
    public function __construct(?callable $fetcher = null)
    {
        $this->fetcher = $fetcher === null ? null : \Closure::fromCallable($fetcher);
    }

    public function acquire(string $url): GoogleDocsAcquisition
    {
        try {
            $reference = GoogleDocReference::fromUrl($url);
        } catch (InvalidArgumentException $exception) {
            return GoogleDocsAcquisition::failure(null, new ImportIssue(
                ImportIssue::ERROR,
                'google_doc_url_invalid',
                $exception->getMessage(),
                null,
                'google_doc_url'
            ));
        }

        $response = $this->fetch($reference->exportUrl());
        if (!$response['ok']) {
            return GoogleDocsAcquisition::failure($reference, new ImportIssue(
                ImportIssue::ERROR,
                'google_doc_fetch_failed',
                $response['message'] !== '' ? $response['message'] : 'The Google Doc could not be fetched.',
                null,
                'google_doc_url'
            ));
        }

        if ($response['body'] === '') {
            return GoogleDocsAcquisition::failure($reference, new ImportIssue(
                ImportIssue::ERROR,
                'google_doc_empty',
                'The Google Doc export was empty.',
                null,
                'google_doc_url'
            ));
        }

        if (strlen($response['body']) > self::MAX_BYTES) {
            return GoogleDocsAcquisition::failure($reference, new ImportIssue(
                ImportIssue::ERROR,
                'google_doc_too_large',
                'The Google Doc export exceeds the 5 MB intake limit.',
                null,
                'google_doc_url'
            ));
        }

        return GoogleDocsAcquisition::success($reference, $this->transformHtml($reference, $response['body']));
    }

    /** @return array<string,mixed> */
    public function transformHtml(GoogleDocReference $reference, string $html): array
    {
        $title = 'Google Doc ' . $reference->documentId();
        $records = [];

        if (class_exists(DOMDocument::class)) {
            $dom = new DOMDocument();
            $previous = libxml_use_internal_errors(true);
            $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            if ($loaded) {
                $titleNodes = $dom->getElementsByTagName('title');
                if ($titleNodes->length > 0 && trim((string) $titleNodes->item(0)?->textContent) !== '') {
                    $title = trim((string) $titleNodes->item(0)?->textContent);
                }

                $ordinal = 0;
                foreach ($dom->getElementsByTagName('*') as $node) {
                    if (!$node instanceof DOMElement || !preg_match('/^h([1-6])$/i', $node->tagName, $match)) { continue; }
                    $heading = trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? '');
                    if ($heading === '') { continue; }
                    $ordinal++;
                    $description = $this->followingText($node);
                    $records[] = $this->sectionRecord($ordinal, (int) $match[1], $heading, $description);
                }

                if ($records === []) {
                    $body = $dom->getElementsByTagName('body')->item(0);
                    $text = $body ? trim(preg_replace('/\s+/u', ' ', $body->textContent) ?? '') : '';
                    if ($text !== '') {
                        $records[] = $this->sectionRecord(1, 0, $title, $text);
                    }
                }
            }
        }

        if ($records === []) {
            if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $titleMatch)) {
                $candidateTitle = $this->plainText($titleMatch[1]);
                if ($candidateTitle !== '') { $title = $candidateTitle; }
            }

            if (preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/is', $html, $headingMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                foreach ($headingMatches as $index => $match) {
                    $heading = $this->plainText($match[2][0]);
                    if ($heading === '') { continue; }
                    $start = $match[0][1] + strlen($match[0][0]);
                    $end = isset($headingMatches[$index + 1]) ? $headingMatches[$index + 1][0][1] : strlen($html);
                    $description = $this->plainText(substr($html, $start, max(0, $end - $start)));
                    $records[] = $this->sectionRecord(count($records) + 1, (int) $match[1][0], $heading, $description);
                }
            }

            if ($records === []) {
                $text = $this->plainText($html);
                if ($text !== '') { $records[] = $this->sectionRecord(1, 0, $title, $text); }
            }
        }

        return [
            'source' => [
                'type' => 'google-doc',
                'id' => $reference->documentId(),
                'title' => $title,
                'version' => null,
                'metadata' => [
                    'source_url' => $reference->sourceUrl(),
                    'adapter' => 'google-docs-html-export',
                    'adapter_version' => self::ADAPTER_VERSION,
                ],
            ],
            'records' => $records,
        ];
    }

    /** @return array<string,mixed> */
    private function sectionRecord(int $ordinal, int $level, string $heading, string $description): array
    {
        return [
            'id' => 'google-heading-' . $ordinal,
            'source' => [
                'heading' => $heading,
                'heading_level' => $level,
                'ordinal' => $ordinal,
            ],
            'data' => array_filter([
                'name' => $heading,
                'description' => $description,
            ], static fn (mixed $value): bool => $value !== ''),
            'review_required' => true,
        ];
    }

    private function followingText(DOMElement $heading): string
    {
        $parts = [];
        $node = $heading->nextSibling;
        while ($node !== null) {
            if ($node instanceof DOMElement && preg_match('/^h[1-6]$/i', $node->tagName)) { break; }
            $text = trim(preg_replace('/\s+/u', ' ', (string) $node->textContent) ?? '');
            if ($text !== '') { $parts[] = $text; }
            $node = $node->nextSibling;
        }
        return trim(implode("\n\n", $parts));
    }

    private function plainText(string $html): string
    {
        $html = preg_replace('/<(br|\/p|\/div|\/li)>/i', "\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s*\n\s*/u', "\n", $text) ?? $text;
        return trim($text);
    }

    /** @return array{ok:bool,status:int,body:string,message:string} */
    private function fetch(string $url): array
    {
        if ($this->fetcher !== null) {
            return ($this->fetcher)($url);
        }

        if (!function_exists('wp_remote_get')) {
            return ['ok' => false, 'status' => 0, 'body' => '', 'message' => 'WordPress HTTP transport is unavailable.'];
        }

        $response = wp_remote_get($url, [
            'timeout' => 15,
            'redirection' => 3,
            'limit_response_size' => self::MAX_BYTES + 1,
            'user-agent' => 'GreatMarketRealmExpansions/' . (defined('GMREXP_VERSION') ? GMREXP_VERSION : self::ADAPTER_VERSION),
        ]);

        if (function_exists('is_wp_error') && is_wp_error($response)) {
            return ['ok' => false, 'status' => 0, 'body' => '', 'message' => $response->get_error_message()];
        }
        if (!is_array($response)) {
            return ['ok' => false, 'status' => 0, 'body' => '', 'message' => 'Google Docs returned an invalid HTTP response.'];
        }

        $status = function_exists('wp_remote_retrieve_response_code') ? (int) wp_remote_retrieve_response_code($response) : 0;
        $body = function_exists('wp_remote_retrieve_body') ? (string) wp_remote_retrieve_body($response) : '';
        if ($status < 200 || $status >= 300) {
            return ['ok' => false, 'status' => $status, 'body' => '', 'message' => sprintf('Google Docs returned HTTP %d. The document may not be available to this site.', $status)];
        }

        return ['ok' => true, 'status' => $status, 'body' => $body, 'message' => ''];
    }
}
