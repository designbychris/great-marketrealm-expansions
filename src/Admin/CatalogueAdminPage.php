<?php
namespace GreatMarketrealmExpansions\Admin;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Integration\Bridge;
use GreatMarketrealmExpansions\Rules\RuleEngine;

final class CatalogueAdminPage
{
    public const MENU_SLUG = 'great-marketrealm-expansions';

    private RuleEngine $rules;

    public function __construct(private Catalogue $catalogue, private Bridge $bridge, ?RuleEngine $rules = null)
    {
        $this->rules = $rules ?? new RuleEngine();
    }

    public function registerMenu(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'Great MarketRealm Expansions',
            'MarketRealm Expansions',
            'manage_options',
            self::MENU_SLUG,
            [$this, 'render'],
            'dashicons-book-alt',
            58
        );
    }

    /** @return array<string, mixed> */
    public function summary(): array
    {
        $entries = $this->catalogue->allContent();
        $types = [];
        foreach ($entries as $entry) {
            $types[$entry->type()] = ($types[$entry->type()] ?? 0) + 1;
        }
        ksort($types);

        return [
            'plugin_version' => defined('GMREXP_VERSION') ? GMREXP_VERSION : 'unknown',
            'catalogue_api_version' => $this->catalogue->apiVersion(),
            'bridge_api_version' => $this->bridge->apiVersion(),
            'rules_api_version' => $this->rules->apiVersion(),
            'expansion_count' => count($this->catalogue->expansions()),
            'content_count' => count($entries),
            'content_types' => $types,
        ];
    }

    public function render(): void
    {
        if (function_exists('current_user_can') && !current_user_can('manage_options')) {
            if (function_exists('wp_die')) {
                wp_die('You do not have permission to view the Great MarketRealm Expansions catalogue.');
            }
            return;
        }

        $summary = $this->summary();
        $expansions = $this->catalogue->expansions();
        $entries = $this->catalogue->allContent();
        ?>
        <div class="wrap gmrexp-admin">
            <h1>Great MarketRealm Expansions</h1>
            <p class="description">The Keeper's read-only view of installed expansion packs and their canonical catalogue content.</p>

            <div class="notice notice-info inline"><p><strong>Read-only catalogue:</strong> expansion content is loaded from trusted Almanac files. There are no editable settings on this screen.</p></div>

            <h2>Library Status</h2>
            <table class="widefat striped" style="max-width:900px">
                <tbody>
                    <tr><th scope="row">Plugin version</th><td><?php echo esc_html((string) $summary['plugin_version']); ?></td></tr>
                    <tr><th scope="row">Catalogue API</th><td><?php echo esc_html((string) $summary['catalogue_api_version']); ?></td></tr>
                    <tr><th scope="row">Bridge API</th><td><?php echo esc_html((string) $summary['bridge_api_version']); ?></td></tr>
                    <tr><th scope="row">Rules API</th><td><?php echo esc_html((string) $summary['rules_api_version']); ?></td></tr>
                    <tr><th scope="row">Installed expansion packs</th><td><?php echo esc_html((string) $summary['expansion_count']); ?></td></tr>
                    <tr><th scope="row">Catalogue entries</th><td><?php echo esc_html((string) $summary['content_count']); ?></td></tr>
                </tbody>
            </table>

            <h2>Installed Almanacs</h2>
            <table class="widefat striped" style="max-width:1100px">
                <thead><tr><th>Name</th><th>Key</th><th>Version</th><th>Description</th></tr></thead>
                <tbody>
                <?php if ($expansions === []): ?>
                    <tr><td colspan="4">No expansion packs are currently loaded.</td></tr>
                <?php else: ?>
                    <?php foreach ($expansions as $expansion): ?>
                        <tr>
                            <td><strong><?php echo esc_html($expansion->name()); ?></strong></td>
                            <td><code><?php echo esc_html($expansion->key()); ?></code></td>
                            <td><?php echo esc_html($expansion->version()); ?></td>
                            <td><?php echo esc_html($expansion->description()); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

            <h2>Content by Type</h2>
            <?php if ($summary['content_types'] === []): ?>
                <p>No catalogue content is currently loaded.</p>
            <?php else: ?>
                <p>
                    <?php foreach ($summary['content_types'] as $type => $count): ?>
                        <span style="display:inline-block;margin:0 8px 8px 0;padding:5px 9px;background:#fff;border:1px solid #c3c4c7;border-radius:3px"><code><?php echo esc_html((string) $type); ?></code> &times; <?php echo esc_html((string) $count); ?></span>
                    <?php endforeach; ?>
                </p>
            <?php endif; ?>

            <h2>Catalogue</h2>
            <table class="widefat striped" style="max-width:1100px">
                <thead><tr><th>Name</th><th>Type</th><th>Expansion</th><th>Canonical ID</th></tr></thead>
                <tbody>
                <?php if ($entries === []): ?>
                    <tr><td colspan="4">The catalogue is empty.</td></tr>
                <?php else: ?>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td><strong><?php echo esc_html($entry->name()); ?></strong></td>
                            <td><code><?php echo esc_html($entry->type()); ?></code></td>
                            <td><code><?php echo esc_html($entry->expansionKey()); ?></code></td>
                            <td><code><?php echo esc_html($entry->id()); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
