<?php
namespace GreatMarketrealmExpansions\Review;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\ContentDefinition;
use GreatMarketrealmExpansions\Import\StagedDefinition;

final class ReviewItem
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
    public const AMENDED = 'amended';

    /**
     * @param list<string> $amendmentErrors
     */
    public function __construct(
        private string $reviewId,
        private StagedDefinition $staged,
        private string $status = self::PENDING,
        private ?ContentDefinition $amendedDefinition = null,
        private string $note = '',
        private array $amendmentErrors = []
    ) {}

    public function reviewId(): string { return $this->reviewId; }
    public function recordId(): string { return $this->staged->recordId(); }
    public function staged(): StagedDefinition { return $this->staged; }
    public function status(): string { return $this->status; }
    public function note(): string { return $this->note; }
    /** @return list<string> */
    public function amendmentErrors(): array { return $this->amendmentErrors; }

    public function definition(): ?ContentDefinition
    {
        return $this->status === self::AMENDED
            ? $this->amendedDefinition
            : $this->staged->definition();
    }

    public function pending(): bool { return $this->status === self::PENDING; }
    public function approved(): bool { return in_array($this->status, [self::APPROVED, self::AMENDED], true); }
    public function rejected(): bool { return $this->status === self::REJECTED; }
    public function amended(): bool { return $this->status === self::AMENDED; }
    public function resolved(): bool { return !$this->pending(); }

    public function canApproveOriginal(): bool
    {
        return $this->staged->valid();
    }

    public function approve(string $note = ''): void
    {
        if (!$this->canApproveOriginal()) {
            throw new ReviewDecisionException(sprintf(
                'Review item "%s" cannot approve its original staged definition because it is invalid.',
                $this->reviewId
            ));
        }

        $this->status = self::APPROVED;
        $this->amendedDefinition = null;
        $this->amendmentErrors = [];
        $this->note = trim($note);
    }

    public function reject(string $note = ''): void
    {
        $this->status = self::REJECTED;
        $this->amendedDefinition = null;
        $this->amendmentErrors = [];
        $this->note = trim($note);
    }

    /** @param list<string> $errors */
    public function amend(ContentDefinition $definition, array $errors = [], string $note = ''): void
    {
        if ($errors !== []) {
            throw new ReviewDecisionException(sprintf(
                'Review item "%s" amendment is not valid: %s',
                $this->reviewId,
                implode(' ', $errors)
            ));
        }

        $this->status = self::AMENDED;
        $this->amendedDefinition = $definition;
        $this->amendmentErrors = [];
        $this->note = trim($note);
    }

    public function reset(): void
    {
        $this->status = self::PENDING;
        $this->amendedDefinition = null;
        $this->amendmentErrors = [];
        $this->note = '';
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'review_id' => $this->reviewId,
            'record_id' => $this->recordId(),
            'status' => $this->status,
            'resolved' => $this->resolved(),
            'approved' => $this->approved(),
            'amended' => $this->amended(),
            'note' => $this->note,
            'type' => $this->definition()?->type(),
            'key' => $this->definition()?->key(),
            'original_valid' => $this->staged->valid(),
            'original_requires_review' => $this->staged->requiresReview(),
            'amendment_errors' => $this->amendmentErrors,
            'staged' => $this->staged->toArray(),
        ];
    }
}
