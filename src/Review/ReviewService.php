<?php
namespace GreatMarketrealmExpansions\Review;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Content\Schema\ContentValidator;
use GreatMarketrealmExpansions\Import\ImportResult;

final class ReviewService
{
    public const API_VERSION = '1.0.0';

    /** @var list<string> */
    private const CAPABILITIES = [
        'review.open',
        'review.approve',
        'review.reject',
        'review.amend',
        'review.pending',
        'review.approved-definitions',
        'review.no-publication',
    ];

    public function __construct(private ContentValidator $validator) {}

    public function apiVersion(): string { return self::API_VERSION; }

    /** @return list<string> */
    public function capabilities(): array { return self::CAPABILITIES; }

    public function supports(string $capability): bool
    {
        return in_array(strtolower(trim($capability)), self::CAPABILITIES, true);
    }

    public function open(ImportResult $result): ReviewSession
    {
        return new ReviewSession($result, $this->validator);
    }
}
