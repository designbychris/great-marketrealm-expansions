<?php
namespace GreatMarketrealmExpansions\Admin;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Integration\Bridge;
use GreatMarketrealmExpansions\Rules\RuleEngine;
use GreatMarketrealmExpansions\Library\Library;
use GreatMarketrealmExpansions\Migration\MigrationService;
use GreatMarketrealmExpansions\Import\ImportService;
use GreatMarketrealmExpansions\Review\ReviewService;

final class CatalogueAdminPage
{
    public const MENU_SLUG = 'great-marketrealm-expansions';

    private RuleEngine $rules;
    private ?Library $library;
    private ?ImportService $importer;
    private ?ReviewService $reviewer;
    private ?MigrationService $migrations;

    public function __construct(
        private Catalogue $catalogue,
        private Bridge $bridge,
        ?RuleEngine $rules = null,
        ?Library $library = null,
        ?ImportService $importer = null,
        ?ReviewService $reviewer = null,
        ?MigrationService $migrations = null
    ) {
        $this->rules = $rules ?? new RuleEngine();
        $this->library = $library;
        $this->importer = $importer;
        $this->reviewer = $reviewer;
        $this->migrations = $migrations;
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

        $reports = $this->library?->compatibilityReports() ?? [];
        $compatibility = ['ready' => 0, 'degraded' => 0, 'blocked' => 0];
        foreach ($reports as $report) {
            $compatibility[$report->status()] = ($compatibility[$report->status()] ?? 0) + 1;
        }

        return [
            'plugin_version' => defined('GMREXP_VERSION') ? GMREXP_VERSION : 'unknown',
            'catalogue_api_version' => $this->catalogue->apiVersion(),
            'bridge_api_version' => $this->bridge->apiVersion(),
            'rules_api_version' => $this->rules->apiVersion(),
            'library_api_version' => $this->library?->apiVersion(),
            'import_api_version' => $this->importer?->apiVersion(),
            'review_api_version' => $this->reviewer?->apiVersion(),
            'migration_api_version' => $this->migrations?->apiVersion(),
            'migration_step_count' => $this->migrations === null ? 0 : count($this->migrations->registry()->all()),
            'expansion_count' => count($this->catalogue->expansions()),
            'active_expansion_count' => $this->library === null ? count($this->catalogue->expansions()) : count($this->library->activeExpansions()),
            'compatibility' => $compatibility,
            'content_count' => count($entries),
            'content_types' => $types,
        ];
    }


    public function handleActivation(): void
    {
        if ($this->library === null) {
            return;
        }

        if (function_exists('current_user_can') && !current_user_can('manage_options')) {
            if (function_exists('wp_die')) {
                wp_die('You do not have permission to manage Great MarketRealm expansion activation.');
            }
            return;
        }

        if (function_exists('check_admin_referer')) {
            check_admin_referer('gmrexp_set_expansion_activation');
        }

        $key = isset($_POST['expansion']) && is_string($_POST['expansion'])
            ? sanitize_key(wp_unslash($_POST['expansion']))
            : '';
        $active = isset($_POST['active']) && (string) $_POST['active'] === '1';

        if ($key !== '' && $this->library->isInstalled($key)) {
            $this->library->setActive($key, $active);
        }

        if (function_exists('wp_safe_redirect') && function_exists('admin_url')) {
            wp_safe_redirect(admin_url('admin.php?page=' . self::MENU_SLUG . '&gmrexp_activation_updated=1'));
            exit;
        }
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
            <p class="description">The Keeper's Living Library of installed expansion packs, activation state, and canonical catalogue content.</p>

            <div class="notice notice-info inline"><p><strong>Canonical catalogue:</strong> expansion content remains read-only and is loaded from trusted Almanac files. Library activation only controls whether consumers should treat a loaded pack as active.</p></div>

            <h2>Library Status</h2>
            <table class="widefat striped" style="max-width:900px">
                <tbody>
                    <tr><th scope="row">Plugin version</th><td><?php echo esc_html((string) $summary['plugin_version']); ?></td></tr>
                    <tr><th scope="row">Catalogue API</th><td><?php echo esc_html((string) $summary['catalogue_api_version']); ?></td></tr>
                    <tr><th scope="row">Bridge API</th><td><?php echo esc_html((string) $summary['bridge_api_version']); ?></td></tr>
                    <tr><th scope="row">Rules API</th><td><?php echo esc_html((string) $summary['rules_api_version']); ?></td></tr>
                    <tr><th scope="row">Library API</th><td><?php echo esc_html((string) ($summary['library_api_version'] ?? 'unavailable')); ?></td></tr>
                    <tr><th scope="row">Import API</th><td><?php echo esc_html((string) ($summary['import_api_version'] ?? 'unavailable')); ?></td></tr>
                    <tr><th scope="row">Review API</th><td><?php echo esc_html((string) ($summary['review_api_version'] ?? 'unavailable')); ?></td></tr>
                    <tr><th scope="row">Migration API</th><td><?php echo esc_html((string) ($summary['migration_api_version'] ?? 'unavailable')); ?></td></tr>
                    <tr><th scope="row">Registered migration steps</th><td><?php echo esc_html((string) ($summary['migration_step_count'] ?? 0)); ?></td></tr>
                    <tr><th scope="row">Installed expansion packs</th><td><?php echo esc_html((string) $summary['expansion_count']); ?></td></tr>
                    <tr><th scope="row">Active expansion packs</th><td><?php echo esc_html((string) $summary['active_expansion_count']); ?></td></tr>
                    <tr><th scope="row">Compatibility: ready</th><td><?php echo esc_html((string) ($summary['compatibility']['ready'] ?? 0)); ?></td></tr>
                    <tr><th scope="row">Compatibility: degraded</th><td><?php echo esc_html((string) ($summary['compatibility']['degraded'] ?? 0)); ?></td></tr>
                    <tr><th scope="row">Compatibility: blocked</th><td><?php echo esc_html((string) ($summary['compatibility']['blocked'] ?? 0)); ?></td></tr>
                    <tr><th scope="row">Catalogue entries</th><td><?php echo esc_html((string) $summary['content_count']); ?></td></tr>
                </tbody>
            </table>

            <h2>Installed Almanacs</h2>
            <table class="widefat striped" style="max-width:1100px">
                <thead><tr><th>Name</th><th>Key</th><th>Version</th><th>Library</th><th>Compatibility</th><th>Description</th><th>Action</th></tr></thead>
                <tbody>
                <?php if ($expansions === []): ?>
                    <tr><td colspan="7">No expansion packs are currently loaded.</td></tr>
                <?php else: ?>
                    <?php foreach ($expansions as $expansion): ?>
                        <?php
                        $is_active = $this->library === null ? true : $this->library->isActive($expansion->key());
                        $compatibility_report = $this->library?->compatibility($expansion->key());
                        $compatibility_status = $compatibility_report?->status() ?? 'unavailable';
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($expansion->name()); ?></strong></td>
                            <td><code><?php echo esc_html($expansion->key()); ?></code></td>
                            <td><?php echo esc_html($expansion->version()); ?></td>
                            <td><strong><?php echo esc_html($is_active ? 'Active' : 'Inactive'); ?></strong></td>
                            <td>
                                <strong><?php echo esc_html(ucfirst($compatibility_status)); ?></strong>
                                <?php if ($compatibility_report !== null && $compatibility_report->issues() !== []): ?>
                                    <ul style="margin:6px 0 0 18px">
                                        <?php foreach ($compatibility_report->issues() as $issue): ?>
                                            <li><code><?php echo esc_html($issue->code()); ?></code>: <?php echo esc_html($issue->message()); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($expansion->description()); ?></td>
                            <td>
                                <?php if ($this->library !== null): ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <input type="hidden" name="action" value="gmrexp_set_expansion_activation">
                                        <input type="hidden" name="expansion" value="<?php echo esc_attr($expansion->key()); ?>">
                                        <input type="hidden" name="active" value="<?php echo $is_active ? '0' : '1'; ?>">
                                        <?php if (function_exists('wp_nonce_field')) { wp_nonce_field('gmrexp_set_expansion_activation'); } ?>
                                        <button type="submit" class="button"><?php echo esc_html($is_active ? 'Deactivate' : 'Activate'); ?></button>
                                    </form>
                                <?php else: ?>
                                    <span aria-label="Activation unavailable">&mdash;</span>
                                <?php endif; ?>
                            </td>
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
