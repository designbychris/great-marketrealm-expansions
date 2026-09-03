<?php
namespace GreatMarketrealmExpansions\Catalogue\Rest;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Catalogue\AmbiguousCatalogueEntryException;
use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Catalogue\CatalogueEntry;
use GreatMarketrealmExpansions\Catalogue\CatalogueExpansion;

final class CatalogueRestApi
{
    public const NAMESPACE = 'great-marketrealm-expansions/v1';

    public function __construct(private Catalogue $catalogue) {}

    public function registerRoutes(): void
    {
        if (!function_exists('register_rest_route')) { return; }

        register_rest_route(self::NAMESPACE, '/catalogue', [
            'methods' => 'GET',
            'callback' => fn (): array => $this->index(),
            'permission_callback' => '__return_true',
        ]);
        register_rest_route(self::NAMESPACE, '/expansions', [
            'methods' => 'GET',
            'callback' => fn (): array => $this->expansions(),
            'permission_callback' => '__return_true',
        ]);
        register_rest_route(self::NAMESPACE, '/expansions/(?P<expansion>[a-z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => fn (mixed $request): mixed => $this->expansion($this->param($request, 'expansion')),
            'permission_callback' => '__return_true',
        ]);
        register_rest_route(self::NAMESPACE, '/content', [
            'methods' => 'GET',
            'callback' => fn (mixed $request): array => $this->content($this->params($request)),
            'permission_callback' => '__return_true',
        ]);
        register_rest_route(self::NAMESPACE, '/content/(?P<expansion>[a-z0-9_-]+)/(?P<type>[a-z0-9_-]+)/(?P<key>[a-z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => fn (mixed $request): mixed => $this->entry(
                $this->param($request, 'expansion'),
                $this->param($request, 'type'),
                $this->param($request, 'key')
            ),
            'permission_callback' => '__return_true',
        ]);
    }

    /** @return array<string, mixed> */
    public function index(): array
    {
        return [
            'api_version' => $this->catalogue->apiVersion(),
            'capabilities' => $this->catalogue->capabilities(),
            'expansion_count' => count($this->catalogue->expansions()),
            'content_count' => count($this->catalogue->allContent()),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function expansions(): array
    {
        return array_values(array_map(
            static fn (CatalogueExpansion $expansion): array => $expansion->toArray(),
            $this->catalogue->expansions()
        ));
    }

    public function expansion(string $key): mixed
    {
        $expansion = $this->catalogue->expansion($key);
        return $expansion === null ? $this->notFound('Expansion not found.') : $expansion->toArray();
    }

    /** @param array<string, string> $filters
     *  @return list<array<string, mixed>>
     */
    public function content(array $filters = []): array
    {
        $query = $this->catalogue->query();
        if (($filters['type'] ?? '') !== '') { $query = $query->type($filters['type']); }
        if (($filters['expansion'] ?? '') !== '') { $query = $query->from($filters['expansion']); }
        if (($filters['tag'] ?? '') !== '') { $query = $query->tag($filters['tag']); }
        if (($filters['key'] ?? '') !== '') { $query = $query->key($filters['key']); }

        return array_map(static fn (CatalogueEntry $entry): array => $entry->toArray(), $query->get());
    }

    public function entry(string $expansion, string $type, string $key): mixed
    {
        try {
            $entry = $this->catalogue->content($type, $key, $expansion);
        } catch (AmbiguousCatalogueEntryException $exception) {
            return $this->error('gmrexp_ambiguous_content', $exception->getMessage(), 409);
        }
        return $entry === null ? $this->notFound('Content entry not found.') : $entry->toArray();
    }

    private function param(mixed $request, string $key): string
    {
        if (is_object($request) && method_exists($request, 'get_param')) {
            $value = $request->get_param($key);
            return is_scalar($value) ? trim((string) $value) : '';
        }
        if (is_array($request)) {
            $value = $request[$key] ?? '';
            return is_scalar($value) ? trim((string) $value) : '';
        }
        return '';
    }

    /** @return array<string, string> */
    private function params(mixed $request): array
    {
        $result = [];
        foreach (['type', 'expansion', 'tag', 'key'] as $key) {
            $value = $this->param($request, $key);
            if ($value !== '') { $result[$key] = $value; }
        }
        return $result;
    }

    private function notFound(string $message): mixed
    {
        return $this->error('gmrexp_not_found', $message, 404);
    }

    private function error(string $code, string $message, int $status): mixed
    {
        if (class_exists('WP_Error')) { return new \WP_Error($code, $message, ['status' => $status]); }
        return ['error' => $code, 'message' => $message, 'status' => $status];
    }
}
