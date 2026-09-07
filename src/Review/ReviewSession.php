<?php
namespace GreatMarketrealmExpansions\Review;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Import\ImportResult;
use GreatMarketrealmExpansions\Import\SourceDocument;
use Throwable;

final class ReviewSession
{
    /** @var array<string,ReviewItem> */
    private array $items = [];

    public function __construct(
        private ImportResult $import,
        private ContentValidator $validator
    ) {
        foreach ($import->definitions() as $index => $staged) {
            $reviewId = 'review-' . ($index + 1);
            $this->items[$reviewId] = new ReviewItem($reviewId, $staged);
        }
    }

    public function source(): SourceDocument { return $this->import->source(); }
    public function import(): ImportResult { return $this->import; }

    /** @return array<string,ReviewItem> */
    public function items(): array { return $this->items; }

    public function item(string $reviewId): ReviewItem
    {
        if (!isset($this->items[$reviewId])) {
            throw new ReviewDecisionException(sprintf('Review item "%s" does not exist.', $reviewId));
        }
        return $this->items[$reviewId];
    }

    public function approve(string $reviewId, string $note = ''): ReviewItem
    {
        $item = $this->item($reviewId);
        $item->approve($note);
        return $item;
    }

    public function reject(string $reviewId, string $note = ''): ReviewItem
    {
        $item = $this->item($reviewId);
        $item->reject($note);
        return $item;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function amend(
        string $reviewId,
        string $type,
        string $key,
        array $data,
        string $note = ''
    ): ReviewItem {
        $item = $this->item($reviewId);

        try {
            $definition = new ContentDefinition(
                $type,
                $key,
                $this->withImportProvenance($data, $item)
            );
        } catch (Throwable $exception) {
            throw new ReviewDecisionException(
                sprintf('Review item "%s" amendment identity is invalid: %s', $reviewId, $exception->getMessage()),
                0,
                $exception
            );
        }

        $validation = $this->validator->validate($definition);
        if (!$validation->valid()) {
            $errors = array_map(
                static fn ($error): string => sprintf('%s: %s', $error->field(), $error->message()),
                $validation->errors()
            );
            throw new ReviewDecisionException(sprintf(
                'Review item "%s" amendment failed canonical validation: %s',
                $reviewId,
                implode(' ', $errors)
            ));
        }

        $item->amend($definition, [], $note);
        return $item;
    }

    public function reset(string $reviewId): ReviewItem
    {
        $item = $this->item($reviewId);
        $item->reset();
        return $item;
    }

    public function pendingCount(): int
    {
        return count(array_filter($this->items, static fn (ReviewItem $item): bool => $item->pending()));
    }

    public function approvedCount(): int
    {
        return count(array_filter($this->items, static fn (ReviewItem $item): bool => $item->status() === ReviewItem::APPROVED));
    }

    public function amendedCount(): int
    {
        return count(array_filter($this->items, static fn (ReviewItem $item): bool => $item->amended()));
    }

    public function rejectedCount(): int
    {
        return count(array_filter($this->items, static fn (ReviewItem $item): bool => $item->rejected()));
    }

    public function resolvedCount(): int
    {
        return count($this->items) - $this->pendingCount();
    }

    public function complete(): bool
    {
        return $this->pendingCount() === 0;
    }

    /** @return list<ContentDefinition> */
    public function approvedDefinitions(): array
    {
        $definitions = [];
        foreach ($this->items as $item) {
            if (!$item->approved()) {
                continue;
            }
            $definition = $item->definition();
            if ($definition !== null) {
                $definitions[] = $definition;
            }
        }
        return $definitions;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'source' => $this->source()->toArray(),
            'item_count' => count($this->items),
            'pending_count' => $this->pendingCount(),
            'approved_count' => $this->approvedCount(),
            'amended_count' => $this->amendedCount(),
            'rejected_count' => $this->rejectedCount(),
            'resolved_count' => $this->resolvedCount(),
            'complete' => $this->complete(),
            'approved_definition_count' => count($this->approvedDefinitions()),
            'items' => array_map(
                static fn (ReviewItem $item): array => $item->toArray(),
                array_values($this->items)
            ),
        ];
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function withImportProvenance(array $data, ReviewItem $item): array
    {
        $source = $this->source();
        $existing = isset($data['provenance']) && is_array($data['provenance'])
            ? $data['provenance']
            : [];

        $protected = [
            'import_source_type' => $source->type(),
            'import_source_id' => $source->id(),
            'import_source_title' => $source->title(),
            'import_record_id' => $item->recordId(),
            'review_id' => $item->reviewId(),
            'review_status' => ReviewItem::AMENDED,
        ];

        if ($source->version() !== null && $source->version() !== '') {
            $protected['import_source_version'] = $source->version();
        }

        if ($item->staged()->sourceContext() !== []) {
            $protected['import_context'] = $item->staged()->sourceContext();
        }

        $original = $item->staged()->definition()?->provenance() ?? [];
        $data['provenance'] = $protected + $original + $existing;

        return $data;
    }
}
