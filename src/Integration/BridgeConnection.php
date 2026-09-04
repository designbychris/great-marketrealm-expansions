<?php
namespace GreatMarketrealmExpansions\Integration;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Rules\RuleEngine;

final class BridgeConnection
{
    /** @param list<string> $availableCapabilities
     *  @param list<string> $negotiatedCapabilities
     *  @param list<string> $missingRequiredCapabilities
     *  @param list<string> $missingOptionalCapabilities
     *  @param list<BridgeIssue> $issues
     */
    public function __construct(
        private Consumer $consumer,
        private string $bridgeApiVersion,
        private string $catalogueApiVersion,
        private array $availableCapabilities,
        private array $negotiatedCapabilities,
        private array $missingRequiredCapabilities,
        private array $missingOptionalCapabilities,
        private array $issues,
        private ?Catalogue $catalogue,
        private ?RuleEngine $rules = null
    ) {}

    public function connected(): bool { return $this->catalogue !== null && $this->issues === []; }
    public function consumer(): Consumer { return $this->consumer; }
    public function bridgeApiVersion(): string { return $this->bridgeApiVersion; }
    public function catalogueApiVersion(): string { return $this->catalogueApiVersion; }
    /** @return list<string> */
    public function availableCapabilities(): array { return $this->availableCapabilities; }
    /** @return list<string> */
    public function negotiatedCapabilities(): array { return $this->negotiatedCapabilities; }
    /** @return list<string> */
    public function missingRequiredCapabilities(): array { return $this->missingRequiredCapabilities; }
    /** @return list<string> */
    public function missingOptionalCapabilities(): array { return $this->missingOptionalCapabilities; }
    /** @return list<BridgeIssue> */
    public function issues(): array { return $this->issues; }
    public function catalogue(): ?Catalogue { return $this->catalogue; }
    public function rules(): ?RuleEngine { return $this->rules; }

    public function supports(string $capability): bool
    {
        return $this->connected() && in_array(strtolower(trim($capability)), $this->availableCapabilities, true);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'connected' => $this->connected(),
            'consumer' => $this->consumer->toArray(),
            'bridge_api_version' => $this->bridgeApiVersion,
            'catalogue_api_version' => $this->catalogueApiVersion,
            'rules_api_version' => $this->rules?->apiVersion(),
            'available_capabilities' => $this->availableCapabilities,
            'negotiated_capabilities' => $this->negotiatedCapabilities,
            'missing_required_capabilities' => $this->missingRequiredCapabilities,
            'missing_optional_capabilities' => $this->missingOptionalCapabilities,
            'issues' => array_map(static fn (BridgeIssue $issue): array => $issue->toArray(), $this->issues),
        ];
    }
}
