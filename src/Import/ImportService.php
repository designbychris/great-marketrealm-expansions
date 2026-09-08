<?php
namespace GreatMarketrealmExpansions\Import;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use Throwable;

final class ImportService
{
    public const API_VERSION = '1.0.0';

    /** @var list<string> */
    private const CAPABILITIES = [
        'import.stage',
        'import.validate',
        'import.provenance',
        'import.review-flags',
        'import.structured-document',
        'import.json',
    ];

    public function __construct(private ContentValidator $validator) {}

    public function apiVersion(): string { return self::API_VERSION; }

    /** @return list<string> */
    public function capabilities(): array { return self::CAPABILITIES; }

    public function supports(string $capability): bool
    {
        return in_array(strtolower(trim($capability)), self::CAPABILITIES, true);
    }


    public function stageJson(string $json): ImportResult
    {
        try {
            $document = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return new ImportResult(
                new SourceDocument('unknown-source', 'invalid-json', 'Invalid JSON import source'),
                [],
                [
                    new ImportIssue(
                        ImportIssue::ERROR,
                        'json_invalid',
                        'Import JSON could not be decoded: ' . $exception->getMessage()
                    ),
                ]
            );
        }

        if (!is_array($document) || array_is_list($document)) {
            return new ImportResult(
                new SourceDocument('unknown-source', 'invalid-json-document', 'Invalid JSON import document'),
                [],
                [
                    new ImportIssue(
                        ImportIssue::ERROR,
                        'document_invalid',
                        'Import JSON must decode to an object/map.'
                    ),
                ]
            );
        }

        return $this->stage($document);
    }

    /**
     * Stage a non-executable structured representation of an external source.
     *
     * @param array<string,mixed> $document
     */
    public function stage(array $document): ImportResult
    {
        [$source, $sourceIssues] = $this->sourceFromDocument($document);
        $records = $document['records'] ?? [];

        if (!is_array($records) || !array_is_list($records)) {
            $sourceIssues[] = new ImportIssue(
                ImportIssue::ERROR,
                'records_invalid',
                'Import document "records" must be a list.',
                null,
                'records'
            );
            $records = [];
        }

        $staged = [];
        $seen = [];
        foreach ($records as $index => $record) {
            $recordId = 'record-' . ($index + 1);
            $staged[] = $this->stageRecord($source, $recordId, $record, $seen);
        }

        return new ImportResult($source, $staged, $sourceIssues);
    }

    /**
     * @param mixed $record
     * @param array<string,string> $seen
     */
    private function stageRecord(SourceDocument $source, string $fallbackId, mixed $record, array &$seen): StagedDefinition
    {
        if (!is_array($record) || (array_is_list($record) && $record !== [])) {
            return new StagedDefinition($fallbackId, null, [
                new ImportIssue(
                    ImportIssue::ERROR,
                    'record_invalid',
                    'Import records must be maps.',
                    $fallbackId
                ),
            ]);
        }

        $recordId = isset($record['id']) && is_string($record['id']) && trim($record['id']) !== ''
            ? trim($record['id'])
            : $fallbackId;

        $context = isset($record['source']) && is_array($record['source']) && (!array_is_list($record['source']) || $record['source'] === [])
            ? $record['source']
            : [];

        $issues = [];
        $type = isset($record['type']) && is_string($record['type']) ? trim($record['type']) : '';
        $key = isset($record['key']) && is_string($record['key']) ? trim($record['key']) : '';

        $candidateTypes = $record['candidate_types'] ?? [];
        if ($type === '' && is_array($candidateTypes) && count($candidateTypes) > 1) {
            $issues[] = new ImportIssue(
                ImportIssue::WARNING,
                'source_ambiguity',
                'The source record has more than one candidate content type and requires Keeper review.',
                $recordId,
                'type'
            );
        }

        if ($type === '') {
            $issues[] = new ImportIssue(
                ImportIssue::ERROR,
                'content_type_unresolved',
                'A staged record requires an explicit content type.',
                $recordId,
                'type'
            );
        }

        if ($key === '') {
            $issues[] = new ImportIssue(
                ImportIssue::ERROR,
                'content_key_unresolved',
                'A staged record requires an explicit canonical key.',
                $recordId,
                'key'
            );
        }

        $data = $record['data'] ?? null;
        if (!is_array($data) || (array_is_list($data) && $data !== [])) {
            $issues[] = new ImportIssue(
                ImportIssue::ERROR,
                'content_data_invalid',
                'A staged record requires a content data map.',
                $recordId,
                'data'
            );
        }

        if ($type === '' || $key === '' || !is_array($data) || (array_is_list($data) && $data !== [])) {
            return new StagedDefinition(
                $recordId,
                null,
                $issues,
                $context,
                is_array($data) && (!array_is_list($data) || $data === []) ? $data : []
            );
        }

        try {
            $definition = new ContentDefinition($type, $key, $this->withProvenance($data, $source, $recordId, $context));
        } catch (Throwable $exception) {
            $issues[] = new ImportIssue(
                ImportIssue::ERROR,
                'content_identity_invalid',
                $exception->getMessage(),
                $recordId
            );
            return new StagedDefinition($recordId, null, $issues, $context, $data);
        }

        $identity = $definition->type() . ':' . $definition->key();
        if (isset($seen[$identity])) {
            $issues[] = new ImportIssue(
                ImportIssue::ERROR,
                'duplicate_staged_identity',
                sprintf('Staged content identity "%s" also appears in record "%s".', $identity, $seen[$identity]),
                $recordId
            );
        } else {
            $seen[$identity] = $recordId;
        }

        $validation = $this->validator->validate($definition);
        foreach ($validation->errors() as $error) {
            $issues[] = new ImportIssue(
                ImportIssue::ERROR,
                'schema_validation_failed',
                $error->message(),
                $recordId,
                $error->field()
            );
        }

        if (!empty($record['review_required'])) {
            $issues[] = new ImportIssue(
                ImportIssue::WARNING,
                'keeper_review_requested',
                'The source transformation explicitly marked this record for Keeper review.',
                $recordId
            );
        }

        return new StagedDefinition($recordId, $definition, $issues, $context, $data);
    }

    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    private function withProvenance(array $data, SourceDocument $source, string $recordId, array $context): array
    {
        $existing = isset($data['provenance']) && is_array($data['provenance']) ? $data['provenance'] : [];

        $protected = [
            'import_source_type' => $source->type(),
            'import_source_id' => $source->id(),
            'import_source_title' => $source->title(),
            'import_record_id' => $recordId,
        ];

        if ($source->version() !== null && $source->version() !== '') {
            $protected['import_source_version'] = $source->version();
        }

        if ($context !== []) {
            $protected['import_context'] = $context;
        }

        $data['provenance'] = $protected + $existing;
        return $data;
    }

    /**
     * @param array<string,mixed> $document
     * @return array{SourceDocument,list<ImportIssue>}
     */
    private function sourceFromDocument(array $document): array
    {
        $raw = $document['source'] ?? [];
        $issues = [];

        if (!is_array($raw) || (array_is_list($raw) && $raw !== [])) {
            $raw = [];
            $issues[] = new ImportIssue(
                ImportIssue::ERROR,
                'source_invalid',
                'Import document "source" must be a map.',
                null,
                'source'
            );
        }

        $type = isset($raw['type']) && is_string($raw['type']) && trim($raw['type']) !== ''
            ? $raw['type']
            : 'unknown-source';
        $id = isset($raw['id']) && is_string($raw['id']) && trim($raw['id']) !== ''
            ? $raw['id']
            : 'unidentified-source';
        $title = isset($raw['title']) && is_string($raw['title']) && trim($raw['title']) !== ''
            ? $raw['title']
            : 'Untitled import source';

        foreach (['type', 'id', 'title'] as $field) {
            if (!isset($raw[$field]) || !is_string($raw[$field]) || trim($raw[$field]) === '') {
                $issues[] = new ImportIssue(
                    ImportIssue::ERROR,
                    'source_field_missing',
                    sprintf('Import source field "%s" is required.', $field),
                    null,
                    'source.' . $field
                );
            }
        }

        $version = isset($raw['version']) && is_string($raw['version']) ? $raw['version'] : null;
        $metadata = isset($raw['metadata']) && is_array($raw['metadata']) && (!array_is_list($raw['metadata']) || $raw['metadata'] === [])
            ? $raw['metadata']
            : [];

        return [new SourceDocument($type, $id, $title, $version, $metadata), $issues];
    }
}
