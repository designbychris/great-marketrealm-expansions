<?php
namespace GreatMarketrealmExpansions\Integration;

defined('ABSPATH') || exit;

use InvalidArgumentException;

final class Consumer
{
    /** @var list<string> */
    private array $requiredCapabilities;
    /** @var list<string> */
    private array $optionalCapabilities;

    /**
     * @param list<string> $requiredCapabilities
     * @param list<string> $optionalCapabilities
     */
    public function __construct(
        private string $key,
        private string $name,
        private string $version,
        private string $minimumBridgeApiVersion = '1.0.0',
        private string $minimumCatalogueApiVersion = '1.0.0',
        array $requiredCapabilities = [],
        array $optionalCapabilities = []
    ) {
        $this->key = self::normaliseKey($this->key);
        $this->name = trim($this->name);
        $this->version = trim($this->version);
        $this->minimumBridgeApiVersion = self::normaliseVersion($this->minimumBridgeApiVersion, 'minimum Bridge API version');
        $this->minimumCatalogueApiVersion = self::normaliseVersion($this->minimumCatalogueApiVersion, 'minimum Catalogue API version');

        if ($this->key === '') { throw new InvalidArgumentException('Consumer key must not be empty.'); }
        if ($this->name === '') { throw new InvalidArgumentException('Consumer name must not be empty.'); }
        if ($this->version === '') { throw new InvalidArgumentException('Consumer version must not be empty.'); }

        $this->requiredCapabilities = self::normaliseCapabilities($requiredCapabilities);
        $this->optionalCapabilities = array_values(array_diff(
            self::normaliseCapabilities($optionalCapabilities),
            $this->requiredCapabilities
        ));
    }

    public function key(): string { return $this->key; }
    public function name(): string { return $this->name; }
    public function version(): string { return $this->version; }
    public function minimumBridgeApiVersion(): string { return $this->minimumBridgeApiVersion; }
    public function minimumCatalogueApiVersion(): string { return $this->minimumCatalogueApiVersion; }
    /** @return list<string> */
    public function requiredCapabilities(): array { return $this->requiredCapabilities; }
    /** @return list<string> */
    public function optionalCapabilities(): array { return $this->optionalCapabilities; }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'version' => $this->version,
            'minimum_bridge_api_version' => $this->minimumBridgeApiVersion,
            'minimum_catalogue_api_version' => $this->minimumCatalogueApiVersion,
            'required_capabilities' => $this->requiredCapabilities,
            'optional_capabilities' => $this->optionalCapabilities,
        ];
    }

    private static function normaliseKey(string $key): string
    {
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9_-]+/', '-', $key) ?? '';
        return trim($key, '-_');
    }

    private static function normaliseVersion(string $version, string $label): string
    {
        $version = trim($version);
        if (!preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version)) {
            throw new InvalidArgumentException(sprintf('Consumer %s "%s" is not a valid semantic version.', $label, $version));
        }
        return $version;
    }

    /** @param list<string> $capabilities
     *  @return list<string>
     */
    private static function normaliseCapabilities(array $capabilities): array
    {
        $result = [];
        foreach ($capabilities as $capability) {
            if (!is_string($capability)) { throw new InvalidArgumentException('Consumer capabilities must be strings.'); }
            $capability = strtolower(trim($capability));
            if ($capability === '') { throw new InvalidArgumentException('Consumer capability must not be empty.'); }
            $result[$capability] = true;
        }
        $keys = array_keys($result);
        sort($keys);
        return array_values($keys);
    }
}
