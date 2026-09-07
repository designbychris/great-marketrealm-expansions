<?php
namespace GreatMarketrealmExpansions\Library;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Catalogue\CatalogueExpansion;

final class CompatibilityInspector
{
    public function __construct(private Catalogue $catalogue, private Library $library) {}

    /**
     * @param array<string,string> $environmentVersions
     */
    public function inspect(string $expansionKey, array $environmentVersions = []): CompatibilityReport
    {
        $expansion = $this->catalogue->expansion($expansionKey);
        if ($expansion === null) {
            return new CompatibilityReport($expansionKey, false, false, [
                new CompatibilityIssue(
                    CompatibilityIssue::BLOCKING,
                    'expansion_not_installed',
                    sprintf('Expansion pack "%s" is not installed.', $expansionKey),
                    $expansionKey
                ),
            ]);
        }

        $issues = [];
        foreach ($this->dependencies($expansion) as $dependency) {
            $issues = array_merge($issues, $this->inspectDependency($dependency));
        }

        foreach ($this->conflicts($expansion) as $conflict) {
            $issues = array_merge($issues, $this->inspectConflict($conflict));
        }

        foreach ($this->consumerRequirements($expansion) as $consumer => $constraint) {
            if (!array_key_exists($consumer, $environmentVersions)) {
                $issues[] = new CompatibilityIssue(
                    CompatibilityIssue::WARNING,
                    'consumer_version_unknown',
                    sprintf('Compatibility with "%s" cannot be verified because its version is unknown.', $consumer),
                    $consumer
                );
                continue;
            }

            if (!$this->matchesConstraint($environmentVersions[$consumer], $constraint)) {
                $issues[] = new CompatibilityIssue(
                    CompatibilityIssue::BLOCKING,
                    'consumer_version_incompatible',
                    sprintf(
                        'Consumer "%s" version %s does not satisfy required version %s.',
                        $consumer,
                        $environmentVersions[$consumer],
                        $constraint
                    ),
                    $consumer
                );
            }
        }

        return new CompatibilityReport(
            $expansion->key(),
            true,
            $this->library->isActive($expansion->key()),
            $issues
        );
    }

    /** @param array<string,string> $environmentVersions @return array<string,CompatibilityReport> */
    public function inspectAll(array $environmentVersions = []): array
    {
        $reports = [];
        foreach ($this->catalogue->expansions() as $key => $expansion) {
            $reports[$key] = $this->inspect($key, $environmentVersions);
        }
        ksort($reports);
        return $reports;
    }

    /** @return list<array{key:string,version:?string,required:bool,active:bool}> */
    private function dependencies(CatalogueExpansion $expansion): array
    {
        $raw = $expansion->meta('dependencies', []);
        if (!is_array($raw)) {
            return [];
        }

        $dependencies = [];
        foreach ($raw as $index => $entry) {
            if (is_string($index) && is_string($entry)) {
                $dependencies[] = [
                    'key' => $this->canonicalKey($index),
                    'version' => trim($entry) !== '' ? trim($entry) : null,
                    'required' => true,
                    'active' => true,
                ];
                continue;
            }

            if (!is_array($entry) || array_is_list($entry)) {
                continue;
            }

            $key = isset($entry['key']) && is_string($entry['key']) ? $this->canonicalKey($entry['key']) : '';
            if ($key === '') {
                continue;
            }

            $version = isset($entry['version']) && is_string($entry['version']) && trim($entry['version']) !== ''
                ? trim($entry['version'])
                : null;

            $dependencies[] = [
                'key' => $key,
                'version' => $version,
                'required' => !array_key_exists('required', $entry) || $entry['required'] !== false,
                'active' => !array_key_exists('active', $entry) || $entry['active'] !== false,
            ];
        }

        return $dependencies;
    }

    /** @return list<array{key:string,version:?string,active_only:bool}> */
    private function conflicts(CatalogueExpansion $expansion): array
    {
        $raw = $expansion->meta('conflicts', []);
        if (!is_array($raw)) {
            return [];
        }

        $conflicts = [];
        foreach ($raw as $index => $entry) {
            if (is_string($index) && is_string($entry)) {
                $conflicts[] = [
                    'key' => $this->canonicalKey($index),
                    'version' => trim($entry) !== '' ? trim($entry) : null,
                    'active_only' => true,
                ];
                continue;
            }

            if (is_string($entry)) {
                $conflicts[] = ['key' => $this->canonicalKey($entry), 'version' => null, 'active_only' => true];
                continue;
            }

            if (!is_array($entry) || array_is_list($entry)) {
                continue;
            }

            $key = isset($entry['key']) && is_string($entry['key']) ? $this->canonicalKey($entry['key']) : '';
            if ($key === '') {
                continue;
            }

            $conflicts[] = [
                'key' => $key,
                'version' => isset($entry['version']) && is_string($entry['version']) && trim($entry['version']) !== ''
                    ? trim($entry['version'])
                    : null,
                'active_only' => !array_key_exists('active_only', $entry) || $entry['active_only'] !== false,
            ];
        }

        return $conflicts;
    }

    /** @return array<string,string> */
    private function consumerRequirements(CatalogueExpansion $expansion): array
    {
        $compatibility = $expansion->meta('compatibility', []);
        if (!is_array($compatibility)) {
            return [];
        }

        $raw = $compatibility['consumers'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $requirements = [];
        foreach ($raw as $consumer => $constraint) {
            if (
                is_string($consumer)
                && trim($consumer) !== ''
                && is_string($constraint)
                && trim($constraint) !== ''
            ) {
                $requirements[$this->canonicalKey($consumer)] = trim($constraint);
            }
        }
        ksort($requirements);
        return $requirements;
    }

    /** @param array{key:string,version:?string,required:bool,active:bool} $dependency @return list<CompatibilityIssue> */
    private function inspectDependency(array $dependency): array
    {
        $pack = $this->catalogue->expansion($dependency['key']);
        $severity = $dependency['required'] ? CompatibilityIssue::BLOCKING : CompatibilityIssue::WARNING;

        if ($pack === null) {
            return [new CompatibilityIssue(
                $severity,
                $dependency['required'] ? 'required_dependency_missing' : 'optional_dependency_missing',
                sprintf(
                    '%s dependency "%s" is not installed.',
                    $dependency['required'] ? 'Required' : 'Optional',
                    $dependency['key']
                ),
                $dependency['key']
            )];
        }

        if ($dependency['version'] !== null && !$this->matchesConstraint($pack->version(), $dependency['version'])) {
            return [new CompatibilityIssue(
                $severity,
                $dependency['required'] ? 'required_dependency_version_incompatible' : 'optional_dependency_version_incompatible',
                sprintf(
                    '%s dependency "%s" version %s does not satisfy %s.',
                    $dependency['required'] ? 'Required' : 'Optional',
                    $dependency['key'],
                    $pack->version(),
                    $dependency['version']
                ),
                $dependency['key']
            )];
        }

        if ($dependency['active'] && !$this->library->isActive($dependency['key'])) {
            return [new CompatibilityIssue(
                $severity,
                $dependency['required'] ? 'required_dependency_inactive' : 'optional_dependency_inactive',
                sprintf(
                    '%s dependency "%s" is installed but inactive.',
                    $dependency['required'] ? 'Required' : 'Optional',
                    $dependency['key']
                ),
                $dependency['key']
            )];
        }

        return [];
    }

    /** @param array{key:string,version:?string,active_only:bool} $conflict @return list<CompatibilityIssue> */
    private function inspectConflict(array $conflict): array
    {
        $pack = $this->catalogue->expansion($conflict['key']);
        if ($pack === null) {
            return [];
        }

        if ($conflict['version'] !== null && !$this->matchesConstraint($pack->version(), $conflict['version'])) {
            return [];
        }

        if ($conflict['active_only'] && !$this->library->isActive($conflict['key'])) {
            return [];
        }

        return [new CompatibilityIssue(
            CompatibilityIssue::BLOCKING,
            'conflicting_expansion_present',
            sprintf('Conflicting expansion "%s" is installed%s.', $conflict['key'], $conflict['active_only'] ? ' and active' : ''),
            $conflict['key']
        )];
    }

    private function matchesConstraint(string $version, string $constraint): bool
    {
        $constraint = trim($constraint);
        if ($constraint === '' || $constraint === '*') {
            return true;
        }

        foreach (preg_split('/\s*,\s*/', $constraint) ?: [] as $part) {
            if ($part === '') {
                continue;
            }

            if (preg_match('/^(>=|<=|>|<|=|==|!=)?\s*([0-9]+(?:\.[0-9]+){0,3}(?:[-+][0-9A-Za-z.-]+)?)$/', $part, $matches) !== 1) {
                return false;
            }

            $operator = $matches[1] !== '' ? $matches[1] : '=';
            if (!version_compare($version, $matches[2], $operator)) {
                return false;
            }
        }

        return true;
    }

    private function canonicalKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_\-]+/', '-', $value) ?? '';
        return trim(preg_replace('/-+/', '-', $value) ?? '', '-');
    }
}
