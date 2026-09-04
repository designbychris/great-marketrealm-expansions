<?php
namespace GreatMarketrealmExpansions\Integration;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Rules\RuleEngine;
use InvalidArgumentException;

final class Bridge
{
    public const API_VERSION = '1.0.0';

    /** @var list<string> */
    private const CAPABILITIES = [
        'bridge.connect',
        'bridge.consumer.registration',
        'bridge.capability-negotiation',
        'bridge.graceful-degradation',
        'bridge.catalogue.read',
    ];

    private RuleEngine $rules;

    public function __construct(private Catalogue $catalogue, private ConsumerRegistry $consumers, ?RuleEngine $rules = null)
    {
        $this->rules = $rules ?? new RuleEngine();
    }

    public function apiVersion(): string { return self::API_VERSION; }

    /** @return list<string> */
    public function capabilities(): array
    {
        $capabilities = array_values(array_unique(array_merge(
            self::CAPABILITIES,
            $this->catalogue->capabilities(),
            $this->rules->capabilities()
        )));
        sort($capabilities);
        return $capabilities;
    }

    public function supports(string $capability): bool
    {
        return in_array(strtolower(trim($capability)), $this->capabilities(), true);
    }

    public function connect(Consumer $consumer): BridgeConnection
    {
        $issues = [];
        try {
            $this->consumers->register($consumer);
        } catch (InvalidArgumentException $exception) {
            $issues[] = new BridgeIssue('consumer_conflict', $exception->getMessage());
        }

        if (version_compare(self::API_VERSION, $consumer->minimumBridgeApiVersion(), '<')) {
            $issues[] = new BridgeIssue(
                'bridge_api_incompatible',
                sprintf('Bridge API %s does not satisfy consumer minimum %s.', self::API_VERSION, $consumer->minimumBridgeApiVersion())
            );
        }

        $catalogueVersion = $this->catalogue->apiVersion();
        if (version_compare($catalogueVersion, $consumer->minimumCatalogueApiVersion(), '<')) {
            $issues[] = new BridgeIssue(
                'catalogue_api_incompatible',
                sprintf('Catalogue API %s does not satisfy consumer minimum %s.', $catalogueVersion, $consumer->minimumCatalogueApiVersion())
            );
        }

        $available = $this->capabilities();
        $missingRequired = array_values(array_diff($consumer->requiredCapabilities(), $available));
        $missingOptional = array_values(array_diff($consumer->optionalCapabilities(), $available));
        foreach ($missingRequired as $capability) {
            $issues[] = new BridgeIssue('required_capability_missing', sprintf('Required capability "%s" is unavailable.', $capability));
        }

        $requested = array_values(array_unique(array_merge(
            $consumer->requiredCapabilities(),
            $consumer->optionalCapabilities()
        )));
        sort($requested);
        $negotiated = array_values(array_intersect($requested, $available));

        return new BridgeConnection(
            $consumer,
            self::API_VERSION,
            $catalogueVersion,
            $available,
            $negotiated,
            $missingRequired,
            $missingOptional,
            $issues,
            $issues === [] ? $this->catalogue : null,
            $issues === [] ? $this->rules : null
        );
    }

    /** @return array<string, Consumer> */
    public function consumers(): array { return $this->consumers->all(); }
}
