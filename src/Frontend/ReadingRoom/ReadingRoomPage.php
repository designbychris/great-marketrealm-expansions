<?php
namespace GreatMarketrealmExpansions\Frontend\ReadingRoom;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Library\Library;
use GreatMarketrealmExpansions\Import\ImportIssue;
use GreatMarketrealmExpansions\Import\ImportResult;
use GreatMarketrealmExpansions\Import\ImportService;
use GreatMarketrealmExpansions\Import\StagedDefinition;

final class ReadingRoomPage
{
    public const SHORTCODE = 'great_marketrealm_expansions';
    public const QUERY_VAR = 'gmrexp_reading_room';
    public const SECTION_QUERY_ARG = 'gmrexp_section';
    public const EXPANSION_QUERY_ARG = 'gmrexp_expansion';
    public const CONTENT_TYPE_QUERY_ARG = 'gmrexp_type';
    public const HOST_PAGE_OPTION = 'gmrexp_reading_room_host_page_id';
    public const ACTIVATION_ACTION = 'gmrexp_reading_room_activation';
    public const ACTIVATION_NONCE_ACTION = 'gmrexp_reading_room_activation';
    public const IMPORT_SUBMIT_FIELD = 'gmrexp_import_submit';
    public const IMPORT_NONCE_ACTION = 'gmrexp_reading_room_import';
    public const IMPORT_NONCE_FIELD = '_gmrexp_import_nonce';
    public const IMPORT_JSON_FIELD = 'gmrexp_import_json';
    public const STYLE_HANDLE = 'gmrexp-reading-room';
    public const ROUTE_VERSION = '1.0.0';

    public function __construct(
        private Catalogue $catalogue,
        private Library $library,
        private ReadingRoomAccess $access,
        private ReadingRoomNavigation $navigation,
        private ?ImportService $importer = null
    ) {}

    public function register(): void
    {
        if (function_exists('add_shortcode')) {
            add_shortcode(self::SHORTCODE, [$this, 'shortcode']);
        }

        if (function_exists('add_filter')) {
            add_filter('query_vars', [$this, 'queryVars']);
        }

        if (function_exists('add_action')) {
            add_action('init', [$this, 'registerRewriteRules']);
            add_action('init', [$this, 'maybeFlushRewriteRules'], 99);
            add_action('template_redirect', [$this, 'markRememberedHostPageDynamic'], 1);
            add_action('template_redirect', [$this, 'maybeRenderRoute']);
            add_action('wp_enqueue_scripts', [$this, 'registerAssets']);
            add_action('admin_post_' . self::ACTIVATION_ACTION, [$this, 'handleActivation']);
        }
    }

    /** @param list<string> $vars @return list<string> */
    public function queryVars(array $vars): array
    {
        if (!in_array(self::QUERY_VAR, $vars, true)) {
            $vars[] = self::QUERY_VAR;
        }
        return $vars;
    }

    public function registerRewriteRules(): void
    {
        if (!function_exists('add_rewrite_rule')) {
            return;
        }

        add_rewrite_rule(
            '^' . ReadingRoomNavigation::ROOT . '/?$',
            'index.php?' . self::QUERY_VAR . '=library',
            'top'
        );

        add_rewrite_rule(
            '^' . ReadingRoomNavigation::ROOT . '/(browse|import|review)/?$',
            'index.php?' . self::QUERY_VAR . '=$matches[1]',
            'top'
        );
    }

    public function maybeFlushRewriteRules(): void
    {
        if (
            !function_exists('get_option')
            || !function_exists('update_option')
            || !function_exists('flush_rewrite_rules')
        ) {
            return;
        }

        $version = self::ROUTE_VERSION;
        $stored = (string) get_option('gmrexp_reading_room_rewrite_version', '');

        if ($stored === $version) {
            return;
        }

        flush_rewrite_rules(false);
        update_option('gmrexp_reading_room_rewrite_version', $version, false);
    }

    public function registerAssets(): void
    {
        if (!function_exists('wp_register_style')) {
            return;
        }

        $url = function_exists('plugins_url') && defined('GMREXP_FILE')
            ? plugins_url('assets/css/reading-room.css', GMREXP_FILE)
            : '';

        if ($url !== '') {
            wp_register_style(self::STYLE_HANDLE, $url, [], defined('GMREXP_VERSION') ? GMREXP_VERSION : null);
        }
    }

    public function maybeRenderRoute(): void
    {
        if (!function_exists('get_query_var')) {
            return;
        }

        $section = get_query_var(self::QUERY_VAR);
        if (!is_string($section) || $section === '') {
            return;
        }

        $section = $this->navigation->normalizeSection($section);
        $hostUrl = $this->rememberedHostUrl();

        if ($hostUrl !== null && function_exists('wp_safe_redirect')) {
            wp_safe_redirect($this->sectionUrl($hostUrl, $section), 302);
            exit;
        }

        // Backwards-compatible fallback for installations that have not yet
        // rendered the shortcode on a WordPress host page.
        $this->enqueueAssets();

        if (function_exists('status_header')) {
            status_header(200);
        }

        if (function_exists('nocache_headers')) {
            nocache_headers();
        }

        $title = 'Great MarketRealm Expansions — The Reading Room';

        if (function_exists('wp_head') && function_exists('wp_footer')) {
            echo '<!doctype html><html';
            if (function_exists('language_attributes')) {
                echo ' ';
                language_attributes();
            }
            echo '><head><meta charset="';
            echo function_exists('get_bloginfo') ? esc_attr((string) get_bloginfo('charset')) : 'UTF-8';
            echo '"><meta name="viewport" content="width=device-width, initial-scale=1">';
            echo '<title>' . esc_html($title) . '</title>';
            wp_head();
            echo '</head><body class="gmrexp-reading-room-route">';
            echo $this->render($section);
            wp_footer();
            echo '</body></html>';
            exit;
        }

        echo $this->render($section);
        exit;
    }

    public function handleActivation(): void
    {
        if (function_exists('current_user_can') && !current_user_can('manage_options')) {
            if (function_exists('wp_die')) {
                wp_die('You do not have permission to manage Great MarketRealm expansion activation.');
            }
            return;
        }

        if (function_exists('check_admin_referer')) {
            check_admin_referer(self::ACTIVATION_NONCE_ACTION);
        }

        $expansion = isset($_POST['expansion']) && is_string($_POST['expansion'])
            ? $this->normalizeExpansionKey($this->unslash($_POST['expansion']))
            : '';

        $active = isset($_POST['active']) && (string) $_POST['active'] === '1';
        $updated = false;

        if ($expansion !== '' && $this->library->isInstalled($expansion)) {
            $this->library->setActive($expansion, $active);
            $updated = true;
            $this->invalidateRememberedHostPageCache($expansion, $active);
        }

        $hostUrl = $this->rememberedHostUrl() ?? $this->currentHostUrl() ?? $this->urlFor('browse');
        $target = $this->sectionUrl($hostUrl, 'browse');

        if (function_exists('add_query_arg')) {
            $target = (string) add_query_arg(
                'gmrexp_activation_updated',
                $updated ? $expansion : '0',
                $target
            );
        }

        if (function_exists('wp_safe_redirect')) {
            wp_safe_redirect($target, 303);
            exit;
        }
    }

    /** @param array<string,mixed>|string $attributes */
    public function shortcode(array|string $attributes = []): string
    {
        $section = null;

        if (is_array($attributes) && isset($attributes['section']) && is_string($attributes['section'])) {
            $section = trim($attributes['section']);
        }

        if (($section === null || $section === '') && isset($_GET[self::SECTION_QUERY_ARG]) && is_string($_GET[self::SECTION_QUERY_ARG])) {
            $section = $this->unslash($_GET[self::SECTION_QUERY_ARG]);
        }

        $section = $this->navigation->normalizeSection($section);
        $baseUrl = $this->currentHostUrl();

        $this->markResponseDynamic();
        $this->rememberHostPage();
        $this->enqueueAssets();

        return $this->render($section, $baseUrl);
    }

    public function render(string $section = 'library', ?string $baseUrl = null): string
    {
        $section = $this->navigation->normalizeSection($section);
        $state = $this->access->state(
            function_exists('is_user_logged_in') ? is_user_logged_in() : true,
            function_exists('current_user_can') ? current_user_can('manage_options') : true
        );

        if ($state === ReadingRoomAccess::LOGIN_REQUIRED) {
            return $this->renderLoginRequired();
        }

        if ($state === ReadingRoomAccess::FORBIDDEN) {
            return $this->renderForbidden();
        }

        $summary = (new ReadingRoomSummary($this->catalogue, $this->library))->toArray();

        ob_start();
        ?>
        <main class="gmrexp-reading-room" data-gmrexp-reading-room data-section="<?php echo $this->escAttr($section); ?>">
            <header class="gmrexp-reading-room__masthead">
                <p class="gmrexp-reading-room__eyebrow">Great MarketRealm Expansions</p>
                <h1>The Reading Room</h1>
                <p class="gmrexp-reading-room__lede">The Keeper's front desk for the Living Library. Installed Almanacs remain canonical; this room reads their state through the Catalogue and Library APIs.</p>
            </header>

            <?php echo $this->renderNavigation($section, $baseUrl); ?>

            <?php if ($section === 'browse'): ?>
                <?php echo $this->renderBrowse($baseUrl); ?>
            <?php elseif ($section === 'import'): ?>
                <?php echo $this->renderImportDesk($baseUrl); ?>
            <?php elseif ($section !== 'library'): ?>
                <?php echo $this->renderPlaceholder($section, $baseUrl); ?>
            <?php else: ?>
                <section class="gmrexp-reading-room__section" aria-labelledby="gmrexp-library-heading">
                    <div class="gmrexp-reading-room__section-heading">
                        <div>
                            <p class="gmrexp-reading-room__kicker">Current shelf</p>
                            <h2 id="gmrexp-library-heading">Your Library</h2>
                        </div>
                        <span class="gmrexp-reading-room__status">Library overview</span>
                    </div>

                    <div class="gmrexp-reading-room__summary-grid" aria-label="Living Library summary">
                        <?php echo $this->summaryCard('Installed Almanacs', $summary['installed']); ?>
                        <?php echo $this->summaryCard('Active Almanacs', $summary['active']); ?>
                        <?php echo $this->summaryCard('Catalogue Entries', $summary['content']); ?>
                        <?php echo $this->summaryCard('Ready', $summary['compatibility']['ready']); ?>
                    </div>

                    <?php if ($summary['installed'] === 0): ?>
                        <div class="gmrexp-reading-room__empty">
                            <h3>The shelves are waiting.</h3>
                            <p>No expansion packs are currently loaded into the canonical Catalogue. The Reading Room is ready when the first Almanac arrives.</p>
                        </div>
                    <?php else: ?>
                        <div class="gmrexp-reading-room__shelf">
                            <?php foreach ($this->library->expansions() as $expansion): ?>
                                <?php
                                $catalogueExpansion = $expansion->expansion();
                                $report = $this->library->compatibility($catalogueExpansion->key());
                                $entryCount = count($this->catalogue->contentByExpansion($catalogueExpansion->key()));
                                ?>
                                <article class="gmrexp-reading-room__book">
                                    <div class="gmrexp-reading-room__book-topline">
                                        <span class="gmrexp-reading-room__pill"><?php echo $this->escHtml($expansion->active() ? 'Active' : 'Inactive'); ?></span>
                                        <a class="gmrexp-reading-room__compatibility" data-status="<?php echo $this->escAttr($report->status()); ?>" href="<?php echo $this->escAttr($this->compatibilityUrl($baseUrl, $catalogueExpansion->key())); ?>" aria-label="<?php echo $this->escAttr('Explain ' . ucfirst($report->status()) . ' compatibility for ' . $catalogueExpansion->name()); ?>">
                                            <?php echo $this->escHtml(ucfirst($report->status())); ?>
                                        </a>
                                    </div>
                                    <h3><?php echo $this->escHtml($catalogueExpansion->name()); ?></h3>
                                    <p class="gmrexp-reading-room__version">Version <?php echo $this->escHtml($catalogueExpansion->version()); ?></p>
                                    <?php if ($catalogueExpansion->description() !== ''): ?>
                                        <p><?php echo $this->escHtml($catalogueExpansion->description()); ?></p>
                                    <?php endif; ?>
                                    <dl class="gmrexp-reading-room__book-facts">
                                        <div><dt>Canonical key</dt><dd><code><?php echo $this->escHtml($catalogueExpansion->key()); ?></code></dd></div>
                                        <div><dt>Entries</dt><dd><?php echo $this->escHtml((string) $entryCount); ?></dd></div>
                                    </dl>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="gmrexp-reading-room__section gmrexp-reading-room__section--quiet" aria-labelledby="gmrexp-next-desks-heading">
                    <p class="gmrexp-reading-room__kicker">Doors prepared for later phases</p>
                    <h2 id="gmrexp-next-desks-heading">The rest of the Reading Room</h2>
                    <p>Browse and the Import Desk are now open. The Review Desk retains its stable route and remains deliberately closed until V.8.</p>
                </section>
            <?php endif; ?>

            <footer class="gmrexp-reading-room__footer">
                <p><strong>Reading Room rule:</strong> the interface displays state; the underlying APIs continue to own its meaning.</p>
            </footer>
        </main>
        <?php
        return trim((string) ob_get_clean());
    }

    private function renderNavigation(string $current, ?string $baseUrl = null): string
    {
        ob_start();
        ?>
        <nav class="gmrexp-reading-room__nav" aria-label="Reading Room">
            <ul>
                <?php foreach ($this->navigation->items() as $section => $item): ?>
                    <li>
                        <a
                            href="<?php echo $this->escAttr($this->navigationUrl($section, $baseUrl)); ?>"
                            <?php echo $section === $current ? 'aria-current="page"' : ''; ?>
                            <?php echo !$item['available'] ? 'data-coming-soon="true"' : ''; ?>
                        >
                            <span><?php echo $this->escHtml($item['label']); ?></span>
                            <?php if (!$item['available']): ?><small>Coming later</small><?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php
        return trim((string) ob_get_clean());
    }

    private function renderBrowse(?string $baseUrl = null): string
    {
        $requestedExpansion = isset($_GET[self::EXPANSION_QUERY_ARG]) && is_string($_GET[self::EXPANSION_QUERY_ARG])
            ? $this->normalizeExpansionKey($this->unslash($_GET[self::EXPANSION_QUERY_ARG]))
            : '';

        if ($requestedExpansion !== '') {
            return $this->renderExpansionDetail($requestedExpansion, $baseUrl);
        }

        $shelf = new BrowseShelf($this->catalogue, $this->library);
        $entries = $shelf->entries();

        ob_start();
        ?>
        <section class="gmrexp-reading-room__section" aria-labelledby="gmrexp-browse-heading">
            <?php if (isset($_GET['gmrexp_activation_updated'])): ?>
                <div class="gmrexp-reading-room__notice" role="status">
                    The Living Library has been updated.
                </div>
            <?php endif; ?>
            <div class="gmrexp-reading-room__section-heading">
                <div>
                    <p class="gmrexp-reading-room__kicker">Books upon the shelves</p>
                    <h2 id="gmrexp-browse-heading">Browse Installed Expansions</h2>
                    <p class="gmrexp-reading-room__section-intro">A read-only view of every Almanac currently installed in the canonical Catalogue. Activation controls live here, and each compatibility badge now opens the Librarian's read-only explanation inside the Almanac.</p>
                </div>
                <span class="gmrexp-reading-room__status"><?php echo $this->escHtml((string) count($entries)); ?> installed</span>
            </div>

            <?php if ($entries === []): ?>
                <div class="gmrexp-reading-room__empty">
                    <h3>Not a book in sight.</h3>
                    <p>No expansion packs are currently installed, so the Browse shelf has nothing to display yet.</p>
                </div>
            <?php else: ?>
                <div class="gmrexp-reading-room__browse-summary" aria-label="Browse shelf summary">
                    <?php echo $this->summaryCard('Installed Almanacs', $shelf->count()); ?>
                    <?php echo $this->summaryCard('Active Almanacs', $shelf->activeCount()); ?>
                    <?php echo $this->summaryCard('Catalogue Entries', $shelf->contentCount()); ?>
                </div>

                <div class="gmrexp-reading-room__browse-shelf">
                    <?php foreach ($entries as $entry): ?>
                        <article class="gmrexp-reading-room__browse-book" data-expansion="<?php echo $this->escAttr($entry->key()); ?>">
                            <div class="gmrexp-reading-room__book-topline">
                                <span class="gmrexp-reading-room__pill"><?php echo $this->escHtml($entry->active() ? 'Active' : 'Inactive'); ?></span>
                                <a class="gmrexp-reading-room__compatibility" data-status="<?php echo $this->escAttr($entry->compatibilityStatus()); ?>" href="<?php echo $this->escAttr($this->compatibilityUrl($baseUrl, $entry->key())); ?>" aria-label="<?php echo $this->escAttr('Explain ' . ucfirst($entry->compatibilityStatus()) . ' compatibility for ' . $entry->name()); ?>">
                                    <?php echo $this->escHtml(ucfirst($entry->compatibilityStatus())); ?>
                                </a>
                            </div>

                            <div class="gmrexp-reading-room__browse-book-heading">
                                <div>
                                    <p class="gmrexp-reading-room__book-label">Installed Almanac</p>
                                    <h3><?php echo $this->escHtml($entry->name()); ?></h3>
                                    <p class="gmrexp-reading-room__version">Version <?php echo $this->escHtml($entry->version()); ?></p>
                                </div>
                                <strong class="gmrexp-reading-room__entry-total">
                                    <span><?php echo $this->escHtml((string) $entry->entryCount()); ?></span>
                                    <?php echo $this->escHtml($entry->entryCount() === 1 ? 'entry' : 'entries'); ?>
                                </strong>
                            </div>

                            <?php if ($entry->description() !== ''): ?>
                                <p class="gmrexp-reading-room__browse-description"><?php echo $this->escHtml($entry->description()); ?></p>
                            <?php endif; ?>

                            <dl class="gmrexp-reading-room__book-facts">
                                <div><dt>Canonical key</dt><dd><code><?php echo $this->escHtml($entry->key()); ?></code></dd></div>
                                <div><dt>Library state</dt><dd><?php echo $this->escHtml($entry->active() ? 'Active' : 'Inactive'); ?></dd></div>
                                <div><dt>Compatibility</dt><dd><?php echo $this->escHtml(ucfirst($entry->compatibilityStatus())); ?></dd></div>
                            </dl>

                            <div class="gmrexp-reading-room__activation">
                                <div>
                                    <p class="gmrexp-reading-room__book-label">Library activation</p>
                                    <p class="gmrexp-reading-room__activation-copy">
                                        <?php echo $this->escHtml($entry->active()
                                            ? 'This Almanac is currently active for consumers of the Living Library.'
                                            : 'This Almanac remains installed and canonical, but consumers should currently treat it as inactive.'); ?>
                                    </p>
                                </div>
                                <?php echo $this->renderActivationForm($entry->key(), $entry->active()); ?>
                            </div>

                            <div class="gmrexp-reading-room__contents" aria-label="<?php echo $this->escAttr($entry->name() . ' content types'); ?>">
                                <p class="gmrexp-reading-room__book-label">Contents</p>
                                <?php if ($entry->contentTypes() === []): ?>
                                    <p class="gmrexp-reading-room__contents-empty">No canonical content entries are currently loaded for this Almanac.</p>
                                <?php else: ?>
                                    <ul class="gmrexp-reading-room__type-list">
                                        <?php foreach ($entry->contentTypes() as $type => $count): ?>
                                            <li>
                                                <a href="<?php echo $this->escAttr($this->expansionUrl($baseUrl, $entry->key(), $type)); ?>">
                                                    <span><?php echo $this->escHtml($this->contentTypeLabel($type)); ?></span>
                                                    <strong><?php echo $this->escHtml((string) $count); ?></strong>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>

                            <p class="gmrexp-reading-room__future-note"><a class="gmrexp-reading-room__book-open" href="<?php echo $this->escAttr($this->expansionUrl($baseUrl, $entry->key())); ?>">Open Almanac <span aria-hidden="true">→</span></a></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php
        return trim((string) ob_get_clean());
    }

    private function renderExpansionDetail(string $expansionKey, ?string $baseUrl = null): string
    {
        $detail = new ExpansionDetail($this->catalogue, $this->library, $expansionKey);
        $expansion = $detail->expansion();
        $browseUrl = $this->navigationUrl('browse', $baseUrl);

        if ($expansion === null) {
            return sprintf(
                '<section class="gmrexp-reading-room__section"><p class="gmrexp-reading-room__kicker">Book not found</p><h2>That Almanac is not on the shelf.</h2><p>The requested Expansion is not installed in the canonical Catalogue.</p><a class="gmrexp-reading-room__back" href="%s">Return to Browse</a></section>',
                $this->escAttr($browseUrl)
            );
        }

        $requestedType = isset($_GET[self::CONTENT_TYPE_QUERY_ARG]) && is_string($_GET[self::CONTENT_TYPE_QUERY_ARG])
            ? $this->normalizeContentType($this->unslash($_GET[self::CONTENT_TYPE_QUERY_ARG]))
            : '';
        $families = $detail->families();
        if ($requestedType !== '' && !array_key_exists($requestedType, $families)) {
            $requestedType = '';
        }
        $visibleFamilies = $requestedType === '' ? $families : [$requestedType => $families[$requestedType]];

        ob_start();
        ?>
        <section class="gmrexp-reading-room__section gmrexp-reading-room__detail" aria-labelledby="gmrexp-detail-heading">
            <a class="gmrexp-reading-room__back" href="<?php echo $this->escAttr($browseUrl); ?>">← Back to Browse</a>

            <div class="gmrexp-reading-room__detail-masthead">
                <div>
                    <p class="gmrexp-reading-room__kicker">Open Almanac</p>
                    <h2 id="gmrexp-detail-heading"><?php echo $this->escHtml((string) $expansion['name']); ?></h2>
                    <p class="gmrexp-reading-room__version">Version <?php echo $this->escHtml((string) $expansion['version']); ?></p>
                </div>
                <div class="gmrexp-reading-room__detail-statuses">
                    <span class="gmrexp-reading-room__pill"><?php echo $this->escHtml($expansion['active'] ? 'Active' : 'Inactive'); ?></span>
                    <a class="gmrexp-reading-room__compatibility" data-status="<?php echo $this->escAttr((string) $expansion['compatibility_status']); ?>" href="#gmrexp-compatibility-heading" aria-label="<?php echo $this->escAttr('Explain ' . ucfirst((string) $expansion['compatibility_status']) . ' compatibility'); ?>"><?php echo $this->escHtml(ucfirst((string) $expansion['compatibility_status'])); ?></a>
                </div>
            </div>

            <?php if ($expansion['description'] !== ''): ?>
                <p class="gmrexp-reading-room__detail-description"><?php echo $this->escHtml((string) $expansion['description']); ?></p>
            <?php endif; ?>

            <dl class="gmrexp-reading-room__book-facts gmrexp-reading-room__detail-facts">
                <div><dt>Canonical key</dt><dd><code><?php echo $this->escHtml((string) $expansion['key']); ?></code></dd></div>
                <div><dt>Catalogue entries</dt><dd><?php echo $this->escHtml((string) $expansion['entry_count']); ?></dd></div>
                <div><dt>Library state</dt><dd><?php echo $this->escHtml($expansion['active'] ? 'Active' : 'Inactive'); ?></dd></div>
            </dl>

            <div class="gmrexp-reading-room__family-nav" aria-label="Almanac content families">
                <p class="gmrexp-reading-room__book-label">Contents</p>
                <?php if ($families === []): ?>
                    <p class="gmrexp-reading-room__contents-empty">No canonical content entries are currently loaded for this Almanac.</p>
                <?php else: ?>
                    <ul>
                        <li><a <?php echo $requestedType === '' ? 'aria-current="page"' : ''; ?> href="<?php echo $this->escAttr($this->expansionUrl($baseUrl, $expansionKey)); ?>">All <strong><?php echo $this->escHtml((string) $expansion['entry_count']); ?></strong></a></li>
                        <?php foreach ($detail->familyCounts() as $type => $count): ?>
                            <li><a <?php echo $requestedType === $type ? 'aria-current="page"' : ''; ?> href="<?php echo $this->escAttr($this->expansionUrl($baseUrl, $expansionKey, $type)); ?>"><span><?php echo $this->escHtml($this->contentTypeLabel($type)); ?></span><strong><?php echo $this->escHtml((string) $count); ?></strong></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <?php echo $this->renderCompatibilityDiagnostics($expansionKey); ?>

            <?php foreach ($visibleFamilies as $type => $entries): ?>
                <section class="gmrexp-reading-room__family" aria-labelledby="gmrexp-family-<?php echo $this->escAttr($type); ?>">
                    <div class="gmrexp-reading-room__family-heading">
                        <div>
                            <p class="gmrexp-reading-room__book-label">Content family</p>
                            <h3 id="gmrexp-family-<?php echo $this->escAttr($type); ?>"><?php echo $this->escHtml($this->contentTypeLabel($type)); ?></h3>
                        </div>
                        <span><?php echo $this->escHtml((string) count($entries)); ?> <?php echo count($entries) === 1 ? 'entry' : 'entries'; ?></span>
                    </div>
                    <div class="gmrexp-reading-room__entry-list">
                        <?php foreach ($entries as $content): ?>
                            <article class="gmrexp-reading-room__content-entry" data-content-id="<?php echo $this->escAttr($content->id()); ?>">
                                <div class="gmrexp-reading-room__content-entry-heading">
                                    <div>
                                        <h4><?php echo $this->escHtml($content->name() !== '' ? $content->name() : $content->key()); ?></h4>
                                        <code><?php echo $this->escHtml($content->id()); ?></code>
                                    </div>
                                    <span class="gmrexp-reading-room__content-type"><?php echo $this->escHtml($this->contentTypeLabel($content->type())); ?></span>
                                </div>
                                <?php if (is_string($content->value('description')) && $content->value('description') !== ''): ?>
                                    <p><?php echo $this->escHtml((string) $content->value('description')); ?></p>
                                <?php endif; ?>
                                <?php if ($content->tags() !== []): ?>
                                    <ul class="gmrexp-reading-room__tag-list" aria-label="Tags">
                                        <?php foreach ($content->tags() as $tag): ?><li><?php echo $this->escHtml($tag); ?></li><?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </section>
        <?php
        return trim((string) ob_get_clean());
    }

    private function renderCompatibilityDiagnostics(string $expansionKey): string
    {
        $diagnostics = new CompatibilityDiagnostics($this->library->compatibility($expansionKey));

        ob_start();
        ?>
        <section class="gmrexp-reading-room__compatibility-panel" data-status="<?php echo $this->escAttr($diagnostics->status()); ?>" aria-labelledby="gmrexp-compatibility-heading">
            <div class="gmrexp-reading-room__compatibility-heading">
                <div>
                    <p class="gmrexp-reading-room__book-label">Compatibility</p>
                    <h3 id="gmrexp-compatibility-heading"><?php echo $this->escHtml($diagnostics->title()); ?></h3>
                </div>
                <span class="gmrexp-reading-room__compatibility" data-status="<?php echo $this->escAttr($diagnostics->status()); ?>"><?php echo $this->escHtml(ucfirst($diagnostics->status())); ?></span>
            </div>

            <p class="gmrexp-reading-room__compatibility-summary"><?php echo $this->escHtml($diagnostics->summary()); ?></p>

            <dl class="gmrexp-reading-room__compatibility-counts">
                <div><dt>Issues</dt><dd><?php echo $this->escHtml((string) $diagnostics->issueCount()); ?></dd></div>
                <div><dt>Warnings</dt><dd><?php echo $this->escHtml((string) $diagnostics->warningCount()); ?></dd></div>
                <div><dt>Blocking</dt><dd><?php echo $this->escHtml((string) $diagnostics->blockingCount()); ?></dd></div>
            </dl>

            <?php if ($diagnostics->issues() === []): ?>
                <div class="gmrexp-reading-room__compatibility-clear">
                    <strong>Nothing further to report.</strong>
                    <p>The compatibility engine returned no warnings or blocking issues for this installed Almanac.</p>
                </div>
            <?php else: ?>
                <ol class="gmrexp-reading-room__compatibility-issues">
                    <?php foreach ($diagnostics->issues() as $issue): ?>
                        <li data-severity="<?php echo $this->escAttr($issue->severity()); ?>">
                            <div class="gmrexp-reading-room__compatibility-issue-topline">
                                <strong><?php echo $this->escHtml($diagnostics->severityLabel($issue)); ?></strong>
                                <code><?php echo $this->escHtml($issue->code()); ?></code>
                            </div>
                            <h4><?php echo $this->escHtml($diagnostics->codeLabel($issue)); ?></h4>
                            <p><?php echo $this->escHtml($issue->message()); ?></p>
                            <?php if ($issue->subject() !== null && $issue->subject() !== ''): ?>
                                <p class="gmrexp-reading-room__compatibility-subject"><span>Subject</span> <code><?php echo $this->escHtml($issue->subject()); ?></code></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>

            <p class="gmrexp-reading-room__compatibility-boundary"><strong>Reading Room rule:</strong> diagnostics explain compatibility; they do not automatically activate, deactivate, install, remove, or rewrite an Almanac.</p>
        </section>
        <?php
        return trim((string) ob_get_clean());
    }

    public function compatibilityUrl(?string $baseUrl, string $expansionKey): string
    {
        return $this->expansionUrl($baseUrl, $expansionKey) . '#gmrexp-compatibility-heading';
    }

    public function expansionUrl(?string $baseUrl, string $expansionKey, ?string $type = null): string
    {
        $url = $this->navigationUrl('browse', $baseUrl);
        $args = [self::EXPANSION_QUERY_ARG => $this->normalizeExpansionKey($expansionKey)];
        if ($type !== null && $type !== '') {
            $args[self::CONTENT_TYPE_QUERY_ARG] = $this->normalizeContentType($type);
        }

        if (function_exists('add_query_arg')) {
            return (string) add_query_arg($args, $url);
        }

        $separator = str_contains($url, '?') ? '&' : '?';
        return $url . $separator . http_build_query($args);
    }

    private function normalizeContentType(string $type): string
    {
        $type = strtolower(trim($type));
        $type = preg_replace('/[^a-z0-9_\-]+/', '-', $type) ?? '';
        return trim(preg_replace('/-+/', '-', $type) ?? '', '-');
    }

    private function renderActivationForm(string $expansionKey, bool $active): string
    {
        $actionUrl = function_exists('admin_url') ? admin_url('admin-post.php') : '#';
        $nextState = $active ? '0' : '1';
        $buttonLabel = $active ? 'Deactivate' : 'Activate';
        $nonce = '';

        if (function_exists('wp_nonce_field')) {
            ob_start();
            wp_nonce_field(self::ACTIVATION_NONCE_ACTION);
            $nonce = (string) ob_get_clean();
        }

        return sprintf(
            '<form class="gmrexp-reading-room__activation-form" method="post" action="%s">' .
            '<input type="hidden" name="action" value="%s">' .
            '<input type="hidden" name="expansion" value="%s">' .
            '<input type="hidden" name="active" value="%s">' .
            '%s' .
            '<button class="gmrexp-reading-room__button gmrexp-reading-room__button--compact" type="submit">%s</button>' .
            '</form>',
            $this->escAttr($actionUrl),
            $this->escAttr(self::ACTIVATION_ACTION),
            $this->escAttr($expansionKey),
            $this->escAttr($nextState),
            $nonce,
            $this->escHtml($buttonLabel)
        );
    }

    private function normalizeExpansionKey(string $key): string
    {
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9_\-]+/', '-', $key) ?? '';
        return trim(preg_replace('/-+/', '-', $key) ?? '', '-');
    }

    private function contentTypeLabel(string $type): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $type));
    }

    private function renderImportDesk(?string $baseUrl = null): string
    {
        $submittedJson = $this->submittedImportJson();
        $submission = $this->importSubmission($submittedJson);
        $actionUrl = $this->navigationUrl('import', $baseUrl);

        ob_start();
        ?>
        <section class="gmrexp-reading-room__section gmrexp-reading-room__import" aria-labelledby="gmrexp-import-heading">
            <div class="gmrexp-reading-room__section-heading">
                <div>
                    <p class="gmrexp-reading-room__kicker">The Keeper's Import Desk</p>
                    <h2 id="gmrexp-import-heading">Stage Structured Source Material</h2>
                    <p class="gmrexp-reading-room__section-intro">Paste a neutral Import API document here to validate and stage source material before Keeper review. Nothing staged at this desk is published, installed, activated, or written into the canonical Catalogue.</p>
                </div>
                <span class="gmrexp-reading-room__status">Import API <?php echo $this->escHtml($this->importer?->apiVersion() ?? 'unavailable'); ?></span>
            </div>

            <div class="gmrexp-reading-room__import-boundary">
                <strong>Imported ≠ Canonical.</strong>
                <span>This desk stages non-executable structured data for inspection only. Google Docs acquisition belongs to V.7; this phase does not fetch remote documents or accept executable PHP.</span>
            </div>

            <?php if ($this->importer === null): ?>
                <div class="gmrexp-reading-room__empty">
                    <h3>The intake ledger is unavailable.</h3>
                    <p>The Import API has not been supplied to this Reading Room instance.</p>
                </div>
            <?php else: ?>
                <form class="gmrexp-reading-room__import-form" method="post" action="<?php echo $this->escAttr($actionUrl); ?>">
                    <?php echo $this->renderImportNonce(); ?>
                    <input type="hidden" name="<?php echo $this->escAttr(self::IMPORT_SUBMIT_FIELD); ?>" value="1">
                    <label for="gmrexp-import-json"><strong>Structured Import JSON</strong></label>
                    <p class="gmrexp-reading-room__field-help">The document must contain a source map and a records list. Content type and canonical key are never guessed.</p>
                    <textarea id="gmrexp-import-json" name="<?php echo $this->escAttr(self::IMPORT_JSON_FIELD); ?>" rows="16" spellcheck="false" placeholder="Paste a structured Import API JSON document…"><?php echo $this->escHtml($submittedJson); ?></textarea>
                    <div class="gmrexp-reading-room__import-actions">
                        <button class="gmrexp-reading-room__button" type="submit">Stage Source Material</button>
                        <span>No files are written by this action.</span>
                    </div>
                </form>

                <details class="gmrexp-reading-room__import-example">
                    <summary>Show the neutral document shape</summary>
                    <pre><code><?php echo $this->escHtml($this->importExampleJson()); ?></code></pre>
                </details>

                <?php if ($submission['attempted']): ?>
                    <?php if (!$submission['nonce_valid']): ?>
                        <div class="gmrexp-reading-room__import-security" role="alert">
                            <h3>The intake stamp could not be verified.</h3>
                            <p>Please reload the Import Desk and submit the source material again. Nothing was staged.</p>
                        </div>
                    <?php elseif ($submission['result'] instanceof ImportResult): ?>
                        <?php echo $this->renderImportResult($submission['result']); ?>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </section>
        <?php
        return trim((string) ob_get_clean());
    }

    /** @return array{attempted:bool,nonce_valid:bool,result:?ImportResult} */
    private function importSubmission(string $json): array
    {
        $attempted = isset($_POST[self::IMPORT_SUBMIT_FIELD]) && (string) $_POST[self::IMPORT_SUBMIT_FIELD] === '1';
        if (!$attempted || $this->importer === null) {
            return ['attempted' => $attempted, 'nonce_valid' => true, 'result' => null];
        }

        $nonceValid = $this->verifyImportNonce();
        if (!$nonceValid) {
            return ['attempted' => true, 'nonce_valid' => false, 'result' => null];
        }

        return ['attempted' => true, 'nonce_valid' => true, 'result' => $this->importer->stageJson($json)];
    }

    private function submittedImportJson(): string
    {
        if (!isset($_POST[self::IMPORT_JSON_FIELD]) || !is_string($_POST[self::IMPORT_JSON_FIELD])) {
            return '';
        }

        return $this->unslash($_POST[self::IMPORT_JSON_FIELD]);
    }

    private function verifyImportNonce(): bool
    {
        if (!function_exists('wp_verify_nonce')) {
            return true;
        }

        $nonce = isset($_POST[self::IMPORT_NONCE_FIELD]) && is_string($_POST[self::IMPORT_NONCE_FIELD])
            ? $this->unslash($_POST[self::IMPORT_NONCE_FIELD])
            : '';

        return $nonce !== '' && (bool) wp_verify_nonce($nonce, self::IMPORT_NONCE_ACTION);
    }

    private function renderImportNonce(): string
    {
        if (!function_exists('wp_nonce_field')) {
            return '';
        }

        ob_start();
        wp_nonce_field(self::IMPORT_NONCE_ACTION, self::IMPORT_NONCE_FIELD, false);
        return (string) ob_get_clean();
    }

    private function renderImportResult(ImportResult $result): string
    {
        $definitions = $result->definitions();
        $sourceIssues = $result->issues();
        $recordIssues = 0;
        $errorCount = 0;
        $warningCount = 0;

        foreach ($sourceIssues as $issue) {
            $issue->error() ? $errorCount++ : $warningCount++;
        }
        foreach ($definitions as $definition) {
            foreach ($definition->issues() as $issue) {
                $recordIssues++;
                $issue->error() ? $errorCount++ : $warningCount++;
            }
        }

        $source = $result->source();
        ob_start();
        ?>
        <section class="gmrexp-reading-room__staging" aria-labelledby="gmrexp-staging-heading">
            <div class="gmrexp-reading-room__compatibility-heading">
                <div>
                    <p class="gmrexp-reading-room__kicker">Staging result</p>
                    <h3 id="gmrexp-staging-heading"><?php echo $this->escHtml($source->title()); ?></h3>
                    <p class="gmrexp-reading-room__version"><?php echo $this->escHtml($source->type()); ?> · <?php echo $this->escHtml($source->id()); ?><?php echo $source->version() !== null && $source->version() !== '' ? ' · ' . $this->escHtml($source->version()) : ''; ?></p>
                </div>
                <span class="gmrexp-reading-room__pill"><?php echo $this->escHtml($result->hasErrors() ? 'Needs attention' : ($result->reviewCount() > 0 ? 'Review requested' : 'Structurally valid')); ?></span>
            </div>

            <dl class="gmrexp-reading-room__import-counts">
                <div><dt>Records</dt><dd><?php echo $this->escHtml((string) count($definitions)); ?></dd></div>
                <div><dt>Valid</dt><dd><?php echo $this->escHtml((string) $result->validCount()); ?></dd></div>
                <div><dt>Review</dt><dd><?php echo $this->escHtml((string) $result->reviewCount()); ?></dd></div>
                <div><dt>Errors</dt><dd><?php echo $this->escHtml((string) $errorCount); ?></dd></div>
                <div><dt>Warnings</dt><dd><?php echo $this->escHtml((string) $warningCount); ?></dd></div>
            </dl>

            <?php if ($sourceIssues !== []): ?>
                <div class="gmrexp-reading-room__import-source-issues">
                    <h4>Source document issues</h4>
                    <?php echo $this->renderImportIssues($sourceIssues); ?>
                </div>
            <?php endif; ?>

            <?php if ($definitions === []): ?>
                <div class="gmrexp-reading-room__empty gmrexp-reading-room__empty--compact">
                    <h4>No records were staged.</h4>
                    <p>Review the source-document issues above and try again.</p>
                </div>
            <?php else: ?>
                <div class="gmrexp-reading-room__staged-records">
                    <?php foreach ($definitions as $definition): ?>
                        <?php echo $this->renderStagedDefinition($definition); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <p class="gmrexp-reading-room__compatibility-boundary"><strong>Staging boundary:</strong> these results exist for this request only. V.6 does not persist a review queue, mutate the Catalogue, install an Almanac, or activate content. The Review Desk opens in V.8.</p>
        </section>
        <?php
        return trim((string) ob_get_clean());
    }

    private function renderStagedDefinition(StagedDefinition $staged): string
    {
        $definition = $staged->definition();
        $status = !$staged->valid() ? 'Invalid' : ($staged->requiresReview() ? 'Review' : 'Valid');

        ob_start();
        ?>
        <article class="gmrexp-reading-room__staged-record" data-valid="<?php echo $staged->valid() ? 'true' : 'false'; ?>">
            <div class="gmrexp-reading-room__content-entry-heading">
                <div>
                    <p class="gmrexp-reading-room__book-label">Record <?php echo $this->escHtml($staged->recordId()); ?></p>
                    <h4><?php echo $this->escHtml($definition?->data()['name'] ?? 'Unresolved staged record'); ?></h4>
                    <?php if ($definition !== null): ?>
                        <code><?php echo $this->escHtml($definition->type() . ':' . $definition->key()); ?></code>
                    <?php endif; ?>
                </div>
                <span class="gmrexp-reading-room__content-type"><?php echo $this->escHtml($status); ?></span>
            </div>

            <?php if ($staged->sourceContext() !== []): ?>
                <details class="gmrexp-reading-room__record-context">
                    <summary>Source context</summary>
                    <pre><code><?php echo $this->escHtml((string) json_encode($staged->sourceContext(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></code></pre>
                </details>
            <?php endif; ?>

            <?php if ($staged->issues() !== []): ?>
                <?php echo $this->renderImportIssues($staged->issues()); ?>
            <?php elseif ($staged->valid()): ?>
                <p class="gmrexp-reading-room__record-clear">No validation issues were reported for this staged record.</p>
            <?php endif; ?>
        </article>
        <?php
        return trim((string) ob_get_clean());
    }

    /** @param list<ImportIssue> $issues */
    private function renderImportIssues(array $issues): string
    {
        ob_start();
        ?>
        <ul class="gmrexp-reading-room__import-issues">
            <?php foreach ($issues as $issue): ?>
                <li data-severity="<?php echo $this->escAttr($issue->severity()); ?>">
                    <div class="gmrexp-reading-room__compatibility-issue-topline">
                        <strong><?php echo $this->escHtml(strtoupper($issue->severity())); ?></strong>
                        <code><?php echo $this->escHtml($issue->code()); ?></code>
                    </div>
                    <p><?php echo $this->escHtml($issue->message()); ?></p>
                    <?php if ($issue->field() !== null || $issue->recordId() !== null): ?>
                        <p class="gmrexp-reading-room__compatibility-subject">
                            <?php if ($issue->recordId() !== null): ?><span>Record:</span> <?php echo $this->escHtml($issue->recordId()); ?><?php endif; ?>
                            <?php if ($issue->field() !== null): ?><?php echo $issue->recordId() !== null ? ' · ' : ''; ?><span>Field:</span> <?php echo $this->escHtml($issue->field()); ?><?php endif; ?>
                        </p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
        return trim((string) ob_get_clean());
    }

    private function importExampleJson(): string
    {
        $example = [
            'source' => [
                'type' => 'structured-source',
                'id' => 'synthetic-sourcebook',
                'title' => 'Synthetic Sourcebook',
                'version' => 'draft-1',
            ],
            'records' => [
                [
                    'id' => 'record-1',
                    'type' => 'feat',
                    'key' => 'synthetic-feat',
                    'source' => ['section' => 'Example Section'],
                    'data' => [
                        'name' => 'Synthetic Feat',
                        'description' => 'Non-canonical example content used to demonstrate the Import API shape.',
                    ],
                ],
            ],
        ];

        return (string) json_encode($example, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function renderPlaceholder(string $section, ?string $baseUrl = null): string
    {
        $item = $this->navigation->items()[$section];
        ob_start();
        ?>
        <section class="gmrexp-reading-room__section gmrexp-reading-room__placeholder" aria-labelledby="gmrexp-placeholder-heading">
            <p class="gmrexp-reading-room__kicker">Reserved desk</p>
            <h2 id="gmrexp-placeholder-heading"><?php echo $this->escHtml($item['label']); ?></h2>
            <p>This route is intentionally stable from V.1 onward, but its workflow has not opened yet. No placeholder action mutates Catalogue, Library, Import, Review, or Migration state.</p>
            <a class="gmrexp-reading-room__back" href="<?php echo $this->escAttr($this->navigationUrl('library', $baseUrl)); ?>">Return to Your Library</a>
        </section>
        <?php
        return trim((string) ob_get_clean());
    }

    private function renderLoginRequired(): string
    {
        $loginUrl = '#';
        if (function_exists('wp_login_url')) {
            $loginUrl = wp_login_url($this->currentHostUrl() ?? $this->urlFor('library'));
        }

        return sprintf(
            '<main class="gmrexp-reading-room gmrexp-reading-room--gate"><section class="gmrexp-reading-room__gate"><p class="gmrexp-reading-room__eyebrow">Great MarketRealm Expansions</p><h1>The Reading Room is closed.</h1><p>Please sign in before entering the Keeper&apos;s Reading Room.</p><a class="gmrexp-reading-room__button" href="%s">Sign in</a></section></main>',
            $this->escAttr($loginUrl)
        );
    }

    private function renderForbidden(): string
    {
        return '<main class="gmrexp-reading-room gmrexp-reading-room--gate"><section class="gmrexp-reading-room__gate"><p class="gmrexp-reading-room__eyebrow">Great MarketRealm Expansions</p><h1>Keeper access required.</h1><p>Your account is signed in, but it does not currently have permission to manage the Expansion Library.</p></section></main>';
    }

    private function summaryCard(string $label, int $value): string
    {
        return sprintf(
            '<div class="gmrexp-reading-room__summary-card"><strong>%s</strong><span>%s</span></div>',
            $this->escHtml((string) $value),
            $this->escHtml($label)
        );
    }

    public function sectionUrl(string $baseUrl, string $section): string
    {
        $section = $this->navigation->normalizeSection($section);
        $baseUrl = $this->stripSectionQueryArg(trim($baseUrl));

        if ($section === 'library') {
            return $baseUrl;
        }

        if (function_exists('add_query_arg')) {
            return (string) add_query_arg(self::SECTION_QUERY_ARG, $section, $baseUrl);
        }

        $separator = str_contains($baseUrl, '?') ? '&' : '?';
        return $baseUrl . $separator . rawurlencode(self::SECTION_QUERY_ARG) . '=' . rawurlencode($section);
    }

    private function navigationUrl(string $section, ?string $baseUrl = null): string
    {
        if ($baseUrl !== null && trim($baseUrl) !== '') {
            return $this->sectionUrl($baseUrl, $section);
        }

        return $this->urlFor($section);
    }

    public function markRememberedHostPageDynamic(): void
    {
        if (!function_exists('get_queried_object_id')) {
            return;
        }

        $currentPageId = (int) get_queried_object_id();
        $rememberedPageId = $this->rememberedHostPageId();

        if ($currentPageId > 0 && $rememberedPageId > 0 && $currentPageId === $rememberedPageId) {
            $this->markResponseDynamic();
        }
    }

    public function markResponseDynamic(): void
    {
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }

        if (function_exists('nocache_headers') && !headers_sent()) {
            nocache_headers();
        }

        if (function_exists('do_action')) {
            do_action('gmrexp/reading_room_dynamic');
        }
    }

    private function invalidateRememberedHostPageCache(string $expansionKey, bool $active): void
    {
        $pageId = $this->rememberedHostPageId();
        if ($pageId <= 0) {
            return;
        }

        if (function_exists('clean_post_cache')) {
            clean_post_cache($pageId);
        } elseif (function_exists('wp_cache_delete')) {
            wp_cache_delete($pageId, 'posts');
        }

        if (function_exists('do_action')) {
            do_action(
                'gmrexp/reading_room_activation_changed',
                $expansionKey,
                $active,
                $pageId
            );
        }
    }

    private function rememberedHostPageId(): int
    {
        if (!function_exists('get_option')) {
            return 0;
        }

        return max(0, (int) get_option(self::HOST_PAGE_OPTION, 0));
    }

    private function currentHostUrl(): ?string
    {
        if (function_exists('get_queried_object_id') && function_exists('get_permalink')) {
            $pageId = (int) get_queried_object_id();
            if ($pageId > 0) {
                $url = get_permalink($pageId);
                if (is_string($url) && $url !== '') {
                    return $this->stripSectionQueryArg($url);
                }
            }
        }

        return null;
    }

    private function rememberHostPage(): void
    {
        if (!function_exists('get_queried_object_id') || !function_exists('update_option')) {
            return;
        }

        $pageId = (int) get_queried_object_id();
        if ($pageId <= 0) {
            return;
        }

        update_option(self::HOST_PAGE_OPTION, $pageId, false);
    }

    private function rememberedHostUrl(): ?string
    {
        if (!function_exists('get_option') || !function_exists('get_permalink')) {
            return null;
        }

        $pageId = $this->rememberedHostPageId();
        if ($pageId <= 0) {
            return null;
        }

        $url = get_permalink($pageId);
        return is_string($url) && $url !== '' ? $this->stripSectionQueryArg($url) : null;
    }

    private function stripSectionQueryArg(string $url): string
    {
        if (function_exists('remove_query_arg')) {
            return (string) remove_query_arg(self::SECTION_QUERY_ARG, $url);
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        $query = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            unset($query[self::SECTION_QUERY_ARG]);
        }

        $rebuilt = '';
        if (isset($parts['scheme'])) {
            $rebuilt .= $parts['scheme'] . '://';
        }
        if (isset($parts['user'])) {
            $rebuilt .= $parts['user'];
            if (isset($parts['pass'])) {
                $rebuilt .= ':' . $parts['pass'];
            }
            $rebuilt .= '@';
        }
        if (isset($parts['host'])) {
            $rebuilt .= $parts['host'];
        }
        if (isset($parts['port'])) {
            $rebuilt .= ':' . $parts['port'];
        }
        $rebuilt .= $parts['path'] ?? '';
        if ($query !== []) {
            $rebuilt .= '?' . http_build_query($query);
        }
        if (isset($parts['fragment'])) {
            $rebuilt .= '#' . $parts['fragment'];
        }

        return $rebuilt;
    }

    private function unslash(string $value): string
    {
        return function_exists('wp_unslash') ? (string) wp_unslash($value) : stripslashes($value);
    }

    private function urlFor(string $section): string
    {
        $path = $this->navigation->path($section);

        if (function_exists('home_url')) {
            return (string) home_url('/' . trim($path, '/') . '/');
        }

        return '/' . trim($path, '/') . '/';
    }

    private function enqueueAssets(): void
    {
        $this->registerAssets();

        if (function_exists('wp_enqueue_style')) {
            wp_enqueue_style(self::STYLE_HANDLE);
        }
    }

    private function escHtml(string $value): string
    {
        return function_exists('esc_html') ? esc_html($value) : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function escAttr(string $value): string
    {
        return function_exists('esc_attr') ? esc_attr($value) : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
