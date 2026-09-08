<?php
namespace GreatMarketrealmExpansions\Almanac;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Review\ReviewSession;
use InvalidArgumentException;

final class AlmanacProposalService
{
    public const API_VERSION = '1.0.0';

    /** @param array<string,mixed> $metadata */
    public function propose(
        ReviewSession $review,
        string $key,
        string $name,
        string $version,
        string $description = '',
        array $metadata = []
    ): ProposedAlmanac {
        $definitions = $review->approvedDefinitions();
        if ($definitions === []) {
            throw new InvalidArgumentException('A proposed Almanac requires at least one Keeper-approved definition.');
        }

        $seen = [];
        foreach ($definitions as $definition) {
            $identity = $definition->type() . ':' . $definition->key();
            if (isset($seen[$identity])) {
                throw new InvalidArgumentException(sprintf('A proposed Almanac cannot contain duplicate canonical identity "%s".', $identity));
            }
            $seen[$identity] = true;
        }

        $artwork = $metadata['artwork'] ?? null;
        if ($artwork !== null) {
            if (!is_string($artwork)) {
                throw new InvalidArgumentException('Proposed Almanac artwork must be a relative path string.');
            }
            $artwork = trim(str_replace('\\', '/', $artwork));
            if ($artwork === '' || str_starts_with($artwork, '/') || str_contains($artwork, '..') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $artwork)) {
                throw new InvalidArgumentException('Proposed Almanac artwork must be a safe relative pack path.');
            }
            $metadata['artwork'] = $artwork;
        }

        return new ProposedAlmanac(
            $key, $name, $version, $description, $review->source(),
            $definitions, $review->pendingCount(), $review->rejectedCount(), $metadata
        );
    }
}
