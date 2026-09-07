<?php
namespace GreatMarketrealmExpansions\Frontend\ReadingRoom;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Library\Library;

final class ReadingRoomSummary
{
    public function __construct(
        private Catalogue $catalogue,
        private Library $library
    ) {}

    /**
     * @return array{
     *   installed:int,
     *   active:int,
     *   inactive:int,
     *   content:int,
     *   compatibility:array{ready:int,degraded:int,blocked:int},
     *   content_types:array<string,int>
     * }
     */
    public function toArray(): array
    {
        $contentTypes = [];
        foreach ($this->catalogue->allContent() as $entry) {
            $contentTypes[$entry->type()] = ($contentTypes[$entry->type()] ?? 0) + 1;
        }
        ksort($contentTypes);

        $compatibility = ['ready' => 0, 'degraded' => 0, 'blocked' => 0];
        foreach ($this->library->compatibilityReports() as $report) {
            $status = $report->status();
            $compatibility[$status] = ($compatibility[$status] ?? 0) + 1;
        }

        $installed = count($this->catalogue->expansions());
        $active = count($this->library->activeExpansions());

        return [
            'installed' => $installed,
            'active' => $active,
            'inactive' => max(0, $installed - $active),
            'content' => count($this->catalogue->allContent()),
            'compatibility' => $compatibility,
            'content_types' => $contentTypes,
        ];
    }
}
