<?php
namespace GreatMarketrealmExpansions\Frontend\ReadingRoom;

defined('ABSPATH') || exit;

final class ReadingRoomNavigation
{
    public const ROOT = 'marketrealm-expansions';

    /**
     * @return array<string,array{label:string,path:string,available:bool}>
     */
    public function items(): array
    {
        return [
            'library' => [
                'label' => 'Your Library',
                'path' => self::ROOT,
                'available' => true,
            ],
            'browse' => [
                'label' => 'Browse',
                'path' => self::ROOT . '/browse',
                'available' => true,
            ],
            'import' => [
                'label' => 'Import Desk',
                'path' => self::ROOT . '/import',
                'available' => true,
            ],
            'review' => [
                'label' => 'Review Desk',
                'path' => self::ROOT . '/review',
                'available' => true,
            ],
        ];
    }

    public function normalizeSection(?string $section): string
    {
        $section = strtolower(trim((string) $section));
        return array_key_exists($section, $this->items()) ? $section : 'library';
    }

    public function path(string $section = 'library'): string
    {
        $section = $this->normalizeSection($section);
        return $this->items()[$section]['path'];
    }

    public function sectionForRequestPath(string $path): ?string
    {
        $path = trim(parse_url($path, PHP_URL_PATH) ?? '', '/');

        foreach ($this->items() as $section => $item) {
            if ($path === $item['path']) {
                return $section;
            }
        }

        return null;
    }
}
