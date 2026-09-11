<?php
namespace GreatMarketrealmExpansions\Frontend\ReadingRoom;

use GreatMarketrealmExpansions\Almanac\AlmanacProposalService;
use GreatMarketrealmExpansions\Almanac\AlmanacPublicationService;
use GreatMarketrealmExpansions\Almanac\AlmanacMetadataService;
use GreatMarketrealmExpansions\Almanac\AlmanacStorage;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Catalogue\CatalogueExpansion;
use GreatMarketrealmExpansions\Library\Library;
use GreatMarketrealmExpansions\Import\ImportIssue;
use GreatMarketrealmExpansions\Import\ImportResult;
use GreatMarketrealmExpansions\Import\ImportService;
use GreatMarketrealmExpansions\Import\GoogleDocsAcquisition;
use GreatMarketrealmExpansions\Import\GoogleDocsSourceAdapter;
use GreatMarketrealmExpansions\Import\StagedDefinition;
use GreatMarketrealmExpansions\Review\ReviewDecisionException;
use GreatMarketrealmExpansions\Review\ReviewItem;
use GreatMarketrealmExpansions\Review\ReviewQueueStore;
use GreatMarketrealmExpansions\Review\ReviewService;
use GreatMarketrealmExpansions\Review\ReviewSession;
use GreatMarketrealmExpansions\Content\Types\CoreContentTypes;
use GreatMarketrealmExpansions\Content\Schema\FieldDefinition;
use GreatMarketrealmExpansions\Content\Schema\SchemaRegistry;

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
    public const GOOGLE_DOC_SUBMIT_FIELD = 'gmrexp_google_doc_submit';
    public const GOOGLE_DOC_URL_FIELD = 'gmrexp_google_doc_url';
    public const GOOGLE_DOC_NONCE_ACTION = 'gmrexp_reading_room_google_doc';
    public const GOOGLE_DOC_NONCE_FIELD = '_gmrexp_google_doc_nonce';
    public const REVIEW_QUEUE_SUBMIT_FIELD = 'gmrexp_review_queue_submit';
    public const REVIEW_DECISION_SUBMIT_FIELD = 'gmrexp_review_decision_submit';
    public const REVIEW_CLEAR_SUBMIT_FIELD = 'gmrexp_review_clear_submit';
    public const REVIEW_NONCE_ACTION = 'gmrexp_reading_room_review';
    public const REVIEW_NONCE_FIELD = '_gmrexp_review_nonce';
    public const REVIEW_JSON_FIELD = 'gmrexp_review_json';
    public const REVIEW_RECORD_FIELD = 'gmrexp_review_record';
    public const REVIEW_ACTION_FIELD = 'gmrexp_review_action';
    public const REVIEW_BULK_SUBMIT_FIELD = 'gmrexp_review_bulk_submit';
    public const REVIEW_BULK_RECORDS_FIELD = 'gmrexp_review_bulk_records';
    public const REVIEW_BULK_ACTION_FIELD = 'gmrexp_review_bulk_action';
    public const ALMANAC_PROPOSAL_SUBMIT_FIELD = 'gmrexp_almanac_proposal_submit';
    public const ALMANAC_PUBLICATION_SUBMIT_FIELD = 'gmrexp_almanac_publication_submit';
    public const ALMANAC_ARTWORK_UPLOAD_FIELD = 'gmrexp_almanac_artwork_upload';
    public const METADATA_ACTION = 'gmrexp_correct_almanac_metadata';
    public const METADATA_NONCE_ACTION = 'gmrexp_correct_almanac_metadata';
    public const METADATA_ARTWORK_UPLOAD_FIELD = 'gmrexp_metadata_artwork_upload';
    public const STYLE_HANDLE = 'gmrexp-reading-room';
    public const SCRIPT_HANDLE = 'gmrexp-reading-room-review';
    public const ROUTE_VERSION = '1.0.0';

    public function __construct(
        private Catalogue $catalogue,
        private Library $library,
        private ReadingRoomAccess $access,
        private ReadingRoomNavigation $navigation,
        private ?ImportService $importer = null,
        private ?GoogleDocsSourceAdapter $googleDocs = null,
        private ?ReviewService $reviewer = null,
        private ?ReviewQueueStore $reviewQueue = null,
        private ?AlmanacProposalService $proposals = null,
        private ?SchemaRegistry $schemas = null,
        private ?AlmanacPublicationService $publisher = null,
        private ?AlmanacMetadataService $metadata = null,
        private ?AlmanacStorage $almanacStorage = null
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
            add_action('admin_post_' . self::METADATA_ACTION, [$this, 'handleMetadataCorrection']);
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
        if (!function_exists('plugins_url') || !defined('GMREXP_FILE')) {
            return;
        }

        $version = defined('GMREXP_VERSION') ? GMREXP_VERSION : null;

        if (function_exists('wp_register_style')) {
            wp_register_style(
                self::STYLE_HANDLE,
                plugins_url('assets/css/reading-room.css', GMREXP_FILE),
                [],
                $version
            );
        }

        if (function_exists('wp_register_script')) {
            wp_register_script(
                self::SCRIPT_HANDLE,
                plugins_url('assets/js/reading-room-review.js', GMREXP_FILE),
                [],
                $version,
                true
            );
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

    public function handleMetadataCorrection(): void
    {
        if (function_exists('current_user_can') && !current_user_can('manage_options')) { if (function_exists('wp_die')) { wp_die('You do not have permission to correct published Almanac metadata.'); } return; }
        if (function_exists('check_admin_referer')) { check_admin_referer(self::METADATA_NONCE_ACTION); }
        $key = isset($_POST['expansion']) && is_string($_POST['expansion']) ? $this->normalizeExpansionKey($this->unslash($_POST['expansion'])) : '';
        $error = '';
        try {
            if ($this->metadata === null || $this->almanacStorage === null) { throw new \RuntimeException('The Catalogue correction desk is unavailable.'); }
            if (!$this->almanacStorage->isKeeperPublished($key)) { throw new \RuntimeException('Bundled Almanacs travel with the plugin and cannot be corrected from the Reading Room.'); }
            $upload = isset($_FILES[self::METADATA_ARTWORK_UPLOAD_FIELD]) && is_array($_FILES[self::METADATA_ARTWORK_UPLOAD_FIELD]) ? $_FILES[self::METADATA_ARTWORK_UPLOAD_FIELD] : null;
            $this->metadata->update($this->almanacStorage->keeperRoot(), $key, [
                'name' => isset($_POST['name']) && is_string($_POST['name']) ? $this->unslash($_POST['name']) : '',
                'version' => isset($_POST['version']) && is_string($_POST['version']) ? $this->unslash($_POST['version']) : '',
                'description' => isset($_POST['description']) && is_string($_POST['description']) ? $this->unslash($_POST['description']) : '',
                'artwork' => isset($_POST['artwork']) && is_string($_POST['artwork']) ? $this->unslash($_POST['artwork']) : '',
            ], $upload);
            $this->invalidateRememberedHostPageCache($key, $this->library->isActive($key));
        } catch (\Throwable $exception) { $error = $exception->getMessage(); }
        $hostUrl = $this->rememberedHostUrl() ?? $this->currentHostUrl() ?? $this->urlFor('library');
        $target = $this->sectionUrl($hostUrl, 'library');
        if (function_exists('add_query_arg')) { $target = (string) add_query_arg($error === '' ? 'gmrexp_metadata_updated' : 'gmrexp_metadata_error', $error === '' ? $key : $error, $target); }
        if (function_exists('wp_safe_redirect')) { wp_safe_redirect($target, 303); exit; }
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
        $loggedIn = function_exists('is_user_logged_in') ? is_user_logged_in() : true;
        $canManageExpansions = function_exists('current_user_can') ? current_user_can('manage_options') : true;
        $state = $this->access->sectionState(
            $section,
            $loggedIn,
            $canManageExpansions
        );

        if ($state === ReadingRoomAccess::LOGIN_REQUIRED) {
            return $this->renderLoginRequired();
        }

        if ($state === ReadingRoomAccess::FORBIDDEN) {
            return $this->renderForbidden();
        }

        $summary = (new ReadingRoomSummary($this->catalogue, $this->library))->toArray();
        $activeLibraryEntries = array_values(array_filter(
            $this->library->expansions(),
            static fn ($entry): bool => $entry->active()
        ));
        $activeEntryCount = 0;
        $activeReadyCount = 0;
        foreach ($activeLibraryEntries as $activeEntry) {
            $key = $activeEntry->expansion()->key();
            $activeEntryCount += count($this->catalogue->contentByExpansion($key));
            if ($this->library->compatibility($key)->status() === 'ready') { $activeReadyCount++; }
        }

        ob_start();
        ?>
        <main class="gmrexp-reading-room" data-gmrexp-reading-room data-section="<?php echo $this->escAttr($section); ?>">
            <header class="gmrexp-reading-room__masthead">
                <p class="gmrexp-reading-room__eyebrow">Great MarketRealm Expansions</p>
                <h1>The Reading Room</h1>
                <p class="gmrexp-reading-room__lede">Welcome to the Guild's Living Library: discover Almanacs, keep your active shelf, and — when entrusted with the keys — prepare new books for the Great MarketRealm.</p>
            </header>

            <?php echo $this->renderNavigation($section, $baseUrl, $canManageExpansions); ?>

            <?php if ($section === 'browse'): ?>
                <?php echo $this->renderBrowse($baseUrl); ?>
            <?php elseif ($section === 'import'): ?>
                <?php echo $this->renderImportDesk($baseUrl); ?>
            <?php elseif ($section === 'review'): ?>
                <?php echo $this->renderReviewDesk($baseUrl); ?>
            <?php elseif ($section !== 'library'): ?>
                <?php echo $this->renderPlaceholder($section, $baseUrl); ?>
            <?php else: ?>
                <?php
                $browseShelf = new BrowseShelf($this->catalogue, $this->library);
                $browseEntries = $browseShelf->entries();
                $featuredEntry = $activeLibraryEntries[0] ?? ($this->library->expansions()[0] ?? null);
                ?>
                <section class="gmrexp-reading-room__experience" aria-labelledby="gmrexp-library-heading">
                    <?php if (isset($_GET['gmrexp_metadata_updated'])): ?><div class="gmrexp-reading-room__notice" role="status">The Keeper corrected the published Almanac catalogue card.</div><?php endif; ?>
                    <?php if (isset($_GET['gmrexp_metadata_error']) && is_string($_GET['gmrexp_metadata_error'])): ?><div class="gmrexp-reading-room__import-security" role="alert"><strong>Catalogue correction not saved.</strong><p><?php echo $this->escHtml($this->unslash($_GET['gmrexp_metadata_error'])); ?></p></div><?php endif; ?>

                    <?php if ($featuredEntry !== null): ?>
                        <?php
                        $featuredExpansion = $featuredEntry->expansion();
                        $featuredArtwork = $this->libraryArtworkUrl($featuredExpansion);
                        $featuredCounts = $this->contentTypeCountsForExpansion($featuredExpansion->key());
                        $featuredEntryCount = array_sum($featuredCounts);
                        ?>
                        <section class="gmrexp-reading-room__feature gmrexp-reading-room__feature--<?php echo $this->escAttr($featuredExpansion->key()); ?>" aria-labelledby="gmrexp-featured-heading">
                            <div class="gmrexp-reading-room__feature-art">
                                <?php if ($featuredArtwork !== null): ?>
                                    <img src="<?php echo $this->escAttr($featuredArtwork); ?>" alt="" loading="eager">
                                <?php else: ?>
                                    <div class="gmrexp-reading-room__feature-placeholder" aria-hidden="true"><span>Great MarketRealm</span><strong><?php echo $this->escHtml($featuredExpansion->name()); ?></strong><small>A Realm Expansion</small></div>
                                <?php endif; ?>
                            </div>
                            <div class="gmrexp-reading-room__feature-copy">
                                <p class="gmrexp-reading-room__kicker">Featured expansion</p>
                                <h2 id="gmrexp-featured-heading"><?php echo $this->escHtml($featuredExpansion->name()); ?></h2>
                                <p class="gmrexp-reading-room__feature-subtitle"><?php echo $featuredExpansion->key() === 'midnight-menu' ? 'A Takeawayton Expansion' : 'A Great MarketRealm Almanac'; ?></p>
                                <?php if ($featuredExpansion->description() !== ''): ?><p class="gmrexp-reading-room__feature-description"><?php echo $this->escHtml($featuredExpansion->description()); ?></p><?php endif; ?>
                                <?php echo $this->renderContentTypeChips($featuredCounts, 5); ?>
                                <div class="gmrexp-reading-room__feature-actions">
                                    <a class="gmrexp-reading-room__button gmrexp-reading-room__button--feature" href="<?php echo $this->escAttr($this->expansionUrl($baseUrl, $featuredExpansion->key())); ?>">Explore <?php echo $this->escHtml($featuredExpansion->name()); ?> <span aria-hidden="true">→</span></a>
                                    <span class="gmrexp-reading-room__feature-meta"><?php echo $this->escHtml((string) $featuredEntryCount); ?> additions · <?php echo $featuredEntry->active() ? 'Active' : 'Available'; ?></span>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>

                    <section class="gmrexp-reading-room__collection" aria-labelledby="gmrexp-library-heading">
                        <div class="gmrexp-reading-room__collection-heading">
                            <div><p class="gmrexp-reading-room__kicker">Your adventures beyond the MarketRealm</p><h2 id="gmrexp-library-heading">Your Library</h2><p>These Almanacs are active for your account and ready to share with your campaigns.</p></div>
                            <a class="gmrexp-reading-room__text-link" href="<?php echo $this->escAttr($this->navigationUrl('browse', $baseUrl)); ?>">View all expansions <span aria-hidden="true">→</span></a>
                        </div>
                        <div class="gmrexp-reading-room__catalogue-strip" aria-label="Living Library summary">
                            <span><strong><?php echo $this->escHtml((string) $summary['active']); ?></strong> active</span><span><strong><?php echo $this->escHtml((string) $summary['installed']); ?></strong> Available in Browse</span><span><strong><?php echo $this->escHtml((string) $activeEntryCount); ?></strong> Active Entries</span><span><strong><?php echo $this->escHtml((string) $activeReadyCount); ?></strong> ready</span>
                        </div>
                        <?php if ($activeLibraryEntries === []): ?>
                            <div class="gmrexp-reading-room__empty gmrexp-reading-room__empty--inviting"><h3>Your next adventure is waiting.</h3><p>You have no active Almanacs yet. Discover a realm, activate it, and share it with your campaigns.</p><a class="gmrexp-reading-room__button gmrexp-reading-room__button--primary" href="<?php echo $this->escAttr($this->navigationUrl('browse', $baseUrl)); ?>">Discover expansions →</a></div>
                        <?php else: ?>
                            <div class="gmrexp-reading-room__active-list">
                                <?php foreach ($activeLibraryEntries as $expansion): ?>
                                    <?php $catalogueExpansion = $expansion->expansion(); $artworkUrl = $this->libraryArtworkUrl($catalogueExpansion); $counts = $this->contentTypeCountsForExpansion($catalogueExpansion->key()); ?>
                                    <article class="gmrexp-reading-room__active-card gmrexp-reading-room__active-card--<?php echo $this->escAttr($catalogueExpansion->key()); ?>">
                                        <a class="gmrexp-reading-room__active-art" href="<?php echo $this->escAttr($this->expansionUrl($baseUrl, $catalogueExpansion->key())); ?>" aria-label="<?php echo $this->escAttr('Open Almanac: ' . $catalogueExpansion->name()); ?>">
                                            <?php if ($artworkUrl !== null): ?><img src="<?php echo $this->escAttr($artworkUrl); ?>" alt="" loading="lazy"><?php else: ?><span class="gmrexp-reading-room__mini-placeholder" aria-hidden="true"><?php echo $this->escHtml($catalogueExpansion->name()); ?></span><?php endif; ?><span class="gmrexp-reading-room__active-ribbon">Active</span>
                                        </a>
                                        <div class="gmrexp-reading-room__active-copy"><p class="gmrexp-reading-room__book-label">Active Almanac</p><h3><?php echo $this->escHtml($catalogueExpansion->name()); ?></h3><p class="gmrexp-reading-room__version">Version <?php echo $this->escHtml($catalogueExpansion->version()); ?></p><?php if ($catalogueExpansion->description() !== ''): ?><p><?php echo $this->escHtml($catalogueExpansion->description()); ?></p><?php endif; ?><?php echo $this->renderContentTypeChips($counts, 4); ?></div>
                                        <div class="gmrexp-reading-room__active-actions"><a class="gmrexp-reading-room__button gmrexp-reading-room__button--primary" href="<?php echo $this->escAttr($this->expansionUrl($baseUrl, $catalogueExpansion->key())); ?>">View details →</a><?php echo $this->renderActivationForm($catalogueExpansion->key(), true); ?><?php echo $this->renderCatalogueEditor($catalogueExpansion); ?><details class="gmrexp-reading-room__keeper-details"><summary>Keeper information</summary><dl><div><dt>Canonical key</dt><dd><code><?php echo $this->escHtml($catalogueExpansion->key()); ?></code></dd></div><div><dt>Entries</dt><dd><?php echo $this->escHtml((string) array_sum($counts)); ?></dd></div><div><dt>Compatibility</dt><dd><?php echo $this->escHtml(ucfirst($this->library->compatibility($catalogueExpansion->key())->status())); ?></dd></div></dl></details></div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="gmrexp-reading-room__discover-preview" aria-labelledby="gmrexp-discover-heading">
                        <div class="gmrexp-reading-room__collection-heading"><div><p class="gmrexp-reading-room__kicker">Discover new expansions</p><h2 id="gmrexp-discover-heading">More worlds are waiting</h2><p>Browse every Almanac installed in the Living Library and choose which adventures belong on your active shelf.</p></div><a class="gmrexp-reading-room__text-link" href="<?php echo $this->escAttr($this->navigationUrl('browse', $baseUrl)); ?>">Open Discover <span aria-hidden="true">→</span></a></div>
                        <div class="gmrexp-reading-room__discover-grid">
                            <?php foreach (array_slice($browseEntries, 0, 4) as $entry): ?>
                                <?php $artworkUrl = $this->browseArtworkUrl($entry); $counts = $entry->contentTypes(); ?>
                                <article class="gmrexp-reading-room__discover-card gmrexp-reading-room__discover-card--<?php echo $this->escAttr($entry->key()); ?>">
                                    <a class="gmrexp-reading-room__discover-art" href="<?php echo $this->escAttr($this->expansionUrl($baseUrl, $entry->key())); ?>" aria-label="<?php echo $this->escAttr('Open Almanac: ' . $entry->name()); ?>"><?php if ($artworkUrl !== null): ?><img src="<?php echo $this->escAttr($artworkUrl); ?>" alt="" loading="lazy"><?php else: ?><span class="gmrexp-reading-room__mini-placeholder" aria-hidden="true"><?php echo $this->escHtml($entry->name()); ?></span><?php endif; ?></a>
                                    <div class="gmrexp-reading-room__discover-copy"><h3><?php echo $this->escHtml($entry->name()); ?></h3><p class="gmrexp-reading-room__version"><?php echo $entry->active() ? 'Already in your library' : 'Available to activate'; ?></p><?php if ($entry->description() !== ''): ?><p><?php echo $this->escHtml($entry->description()); ?></p><?php endif; ?><?php echo $this->renderContentTypeChips($counts, 3); ?><a class="gmrexp-reading-room__text-link" href="<?php echo $this->escAttr($this->expansionUrl($baseUrl, $entry->key())); ?>">View details →</a></div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </section>
            <?php endif; ?>

            <footer class="gmrexp-reading-room__footer">

                <p><strong>Reading Room rule:</strong> the interface displays state; the underlying APIs continue to own its meaning.</p>
            </footer>
        </main>
        <?php
        return trim((string) ob_get_clean());
    }

    private function renderNavigation(string $current, ?string $baseUrl = null, bool $canManageExpansions = true): string
    {
        ob_start();
        ?>
        <nav class="gmrexp-reading-room__nav" aria-label="Reading Room">
            <div class="gmrexp-reading-room__nav-primary">
                <?php foreach (['library', 'browse'] as $section): ?>
                    <?php $item = $this->navigation->items()[$section]; ?>
                    <a
                        class="gmrexp-reading-room__nav-link gmrexp-reading-room__nav-link--<?php echo $this->escAttr($section); ?>"
                        href="<?php echo $this->escAttr($this->navigationUrl($section, $baseUrl)); ?>"
                        <?php echo $section === $current ? 'aria-current="page"' : ''; ?>
                    >
                        <span class="gmrexp-reading-room__nav-icon" aria-hidden="true"><?php echo $section === 'library' ? '▤' : '✦'; ?></span>
                        <span class="gmrexp-reading-room__nav-copy">
                            <strong><?php echo $this->escHtml($item['label']); ?></strong>
                            <small><?php echo $section === 'library' ? 'Your active adventures' : 'Explore every available expansion'; ?></small>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if ($canManageExpansions): ?>
                <div class="gmrexp-reading-room__nav-keeper">
                    <span class="gmrexp-reading-room__nav-keeper-label">Keeper tools</span>
                    <?php foreach (['import', 'review'] as $section): ?>
                        <?php $item = $this->navigation->items()[$section]; ?>
                        <a
                            class="gmrexp-reading-room__nav-link gmrexp-reading-room__nav-link--keeper gmrexp-reading-room__nav-link--<?php echo $this->escAttr($section); ?>"
                            href="<?php echo $this->escAttr($this->navigationUrl($section, $baseUrl)); ?>"
                            <?php echo $section === $current ? 'aria-current="page"' : ''; ?>
                        >
                            <span class="gmrexp-reading-room__nav-icon" aria-hidden="true"><?php echo $section === 'import' ? '⇧' : '▧'; ?></span>
                            <span class="gmrexp-reading-room__nav-copy">
                                <strong><?php echo $this->escHtml($item['label']); ?></strong>
                                <small><?php echo $section === 'import' ? 'Bring in new Almanacs' : 'Check and approve records'; ?></small>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </nav>
        <?php
        return trim((string) ob_get_clean());
    }

    /** @return array<string,int> */
    private function contentTypeCountsForExpansion(string $expansionKey): array
    {
        $counts = [];
        foreach ($this->catalogue->contentByExpansion($expansionKey) as $content) {
            $type = $content->type();
            $counts[$type] = ($counts[$type] ?? 0) + 1;
        }
        arsort($counts);
        return $counts;
    }

    /** @param array<string,int> $counts */
    private function renderContentTypeChips(array $counts, int $limit = 5): string
    {
        if ($counts === []) {
            return '';
        }

        $visible = array_slice($counts, 0, max(1, $limit), true);
        $remaining = max(0, count($counts) - count($visible));
        ob_start();
        ?>
        <ul class="gmrexp-reading-room__content-chips" aria-label="Expansion contents">
            <?php foreach ($visible as $type => $count): ?>
                <li data-content-type="<?php echo $this->escAttr($type); ?>"><span><?php echo $this->escHtml($this->contentTypeLabel($type)); ?></span><strong><?php echo $this->escHtml((string) $count); ?></strong></li>
            <?php endforeach; ?>
            <?php if ($remaining > 0): ?><li class="is-more"><span>More</span><strong>+<?php echo $this->escHtml((string) $remaining); ?></strong></li><?php endif; ?>
        </ul>
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
                    <h2 id="gmrexp-browse-heading">Browse Available Expansions</h2>
                    <p class="gmrexp-reading-room__section-intro">Every Almanac currently available to this installation appears here, whether active or inactive. Activate the expansions you want on Your Library shelf; compatibility badges open the Librarian's read-only explanation inside each Almanac.</p>
                </div>
                <span class="gmrexp-reading-room__status"><?php echo $this->escHtml((string) count($entries)); ?> available</span>
            </div>

            <?php if ($entries === []): ?>
                <div class="gmrexp-reading-room__empty">
                    <h3>Not a book in sight.</h3>
                    <p>No expansion packs are currently installed, so the Browse shelf has nothing to display yet.</p>
                </div>
            <?php else: ?>
                <div class="gmrexp-reading-room__browse-summary" aria-label="Browse shelf summary">
                    <?php echo $this->summaryCard('Available Almanacs', $shelf->count()); ?>
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

                            <?php $artworkUrl = $this->browseArtworkUrl($entry); ?>
                            <a class="gmrexp-reading-room__cover-link" href="<?php echo $this->escAttr($this->expansionUrl($baseUrl, $entry->key())); ?>" aria-label="<?php echo $this->escAttr('Open Almanac: ' . $entry->name()); ?>">
                                <div class="gmrexp-reading-room__browse-artwork-frame<?php echo $artworkUrl === null ? ' is-placeholder' : ''; ?>">
                                    <?php if ($artworkUrl !== null): ?>
                                        <img class="gmrexp-reading-room__browse-artwork" src="<?php echo $this->escAttr($artworkUrl); ?>" alt="" loading="lazy">
                                    <?php else: ?>
                                        <span class="gmrexp-reading-room__cover-placeholder" aria-hidden="true"><strong><?php echo $this->escHtml($entry->name()); ?></strong><small>Great MarketRealm Almanac</small></span>
                                    <?php endif; ?>
                                    <span class="gmrexp-reading-room__cover-cue" aria-hidden="true">Open Almanac →</span>
                                </div>
                            </a>
                            <div class="gmrexp-reading-room__browse-identity-copy gmrexp-reading-room__catalogue-plate">
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
                                </div>

                            <?php $browseCatalogueExpansion = $this->catalogue->expansion($entry->key()); ?>
                            <?php if ($browseCatalogueExpansion instanceof CatalogueExpansion): ?><?php echo $this->renderCatalogueEditor($browseCatalogueExpansion); ?><?php endif; ?>

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
        if (function_exists('current_user_can') && !current_user_can('manage_options')) {
            return '';
        }

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
        $googleSubmission = $this->googleDocSubmission();
        $submittedJson = $googleSubmission['json'] ?? $this->submittedImportJson();
        $submission = $this->importSubmission($submittedJson, $googleSubmission['successful']);
        $actionUrl = $this->navigationUrl('import', $baseUrl);

        ob_start();
        ?>
        <section class="gmrexp-reading-room__section gmrexp-reading-room__import" aria-labelledby="gmrexp-import-heading">
            <div class="gmrexp-reading-room__section-heading">
                <div>
                    <p class="gmrexp-reading-room__kicker">The Keeper's Import Desk</p>
                    <h2 id="gmrexp-import-heading">Stage Structured Source Material</h2>
                    <p class="gmrexp-reading-room__section-intro">Bring a Google Doc to Pippin or paste a neutral Import API document here to validate and stage source material before Keeper review. Nothing staged at this desk is published, installed, activated, or written into the canonical Catalogue.</p>
                </div>
                <span class="gmrexp-reading-room__status">Import API <?php echo $this->escHtml($this->importer?->apiVersion() ?? 'unavailable'); ?></span>
            </div>

            <div class="gmrexp-reading-room__import-boundary">
                <strong>Imported ≠ Canonical.</strong>
                <span>This desk stages non-executable structured data for inspection only. V.7 can acquire an accessible Google Doc HTML export, but it never treats headings as canonical types or keys and never accepts executable PHP.</span>
            </div>

            <?php if ($this->importer === null): ?>
                <div class="gmrexp-reading-room__empty">
                    <h3>The intake ledger is unavailable.</h3>
                    <p>The Import API has not been supplied to this Reading Room instance.</p>
                </div>
            <?php else: ?>
                <section class="gmrexp-reading-room__google-doc" aria-labelledby="gmrexp-google-doc-heading">
                    <div class="gmrexp-reading-room__section-heading">
                        <div>
                            <p class="gmrexp-reading-room__kicker">Pippin finds the Google Docs</p>
                            <h3 id="gmrexp-google-doc-heading">Acquire a Google Doc</h3>
                            <p class="gmrexp-reading-room__field-help">Paste a Google Docs document URL. The site requests Google's HTML export, preserves headings and source text, and converts them into neutral review records. Content type and canonical key are deliberately left unresolved.</p>
                        </div>
                        <span class="gmrexp-reading-room__status">Adapter <?php echo $this->escHtml(GoogleDocsSourceAdapter::ADAPTER_VERSION); ?></span>
                    </div>
                    <form class="gmrexp-reading-room__google-doc-form" method="post" action="<?php echo $this->escAttr($actionUrl); ?>">
                        <?php echo $this->renderGoogleDocNonce(); ?>
                        <input type="hidden" name="<?php echo $this->escAttr(self::GOOGLE_DOC_SUBMIT_FIELD); ?>" value="1">
                        <label for="gmrexp-google-doc-url"><strong>Google Doc URL</strong></label>
                        <input id="gmrexp-google-doc-url" type="url" name="<?php echo $this->escAttr(self::GOOGLE_DOC_URL_FIELD); ?>" value="<?php echo $this->escAttr($googleSubmission['url']); ?>" placeholder="https://docs.google.com/document/d/…/edit" required>
                        <div class="gmrexp-reading-room__import-actions">
                            <button class="gmrexp-reading-room__button" type="submit">Find Google Doc</button>
                            <span>Accessible documents only; private Docs need a future authenticated source connector.</span>
                        </div>
                    </form>
                    <?php if ($googleSubmission['attempted'] && !$googleSubmission['nonce_valid']): ?>
                        <div class="gmrexp-reading-room__import-security" role="alert"><h3>The Google Docs intake stamp could not be verified.</h3><p>Reload the Import Desk and try again.</p></div>
                    <?php elseif ($googleSubmission['acquisition'] instanceof GoogleDocsAcquisition && !$googleSubmission['acquisition']->successful()): ?>
                        <?php $googleIssue = $googleSubmission['acquisition']->issue(); ?>
                        <div class="gmrexp-reading-room__import-security" role="alert">
                            <h3>Pippin could not retrieve that document.</h3>
                            <?php if ($googleIssue !== null): ?><p><code><?php echo $this->escHtml($googleIssue->code()); ?></code> — <?php echo $this->escHtml($googleIssue->message()); ?></p><?php endif; ?>
                        </div>
                    <?php elseif ($googleSubmission['successful']): ?>
                        <div class="gmrexp-reading-room__notice" role="status"><strong>Google Doc acquired.</strong> Its neutral transformation has been copied into the structured staging ledger below. Unresolved identities are expected until Keeper review.</div>
                    <?php endif; ?>
                </section>

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
                        <?php echo $this->renderSendToReviewDesk($submittedJson, $baseUrl); ?>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </section>
        <?php
        return trim((string) ob_get_clean());
    }

    /** @return array{attempted:bool,nonce_valid:bool,result:?ImportResult} */
    private function importSubmission(string $json, bool $forcedAttempt = false): array
    {
        $attempted = $forcedAttempt || (isset($_POST[self::IMPORT_SUBMIT_FIELD]) && (string) $_POST[self::IMPORT_SUBMIT_FIELD] === '1');
        if (!$attempted || $this->importer === null) {
            return ['attempted' => $attempted, 'nonce_valid' => true, 'result' => null];
        }

        $nonceValid = $forcedAttempt ? true : $this->verifyImportNonce();
        if (!$nonceValid) {
            return ['attempted' => true, 'nonce_valid' => false, 'result' => null];
        }

        return ['attempted' => true, 'nonce_valid' => true, 'result' => $this->importer->stageJson($json)];
    }

    /** @return array{attempted:bool,nonce_valid:bool,successful:bool,url:string,json:?string,acquisition:?GoogleDocsAcquisition} */
    private function googleDocSubmission(): array
    {
        $attempted = isset($_POST[self::GOOGLE_DOC_SUBMIT_FIELD]) && (string) $_POST[self::GOOGLE_DOC_SUBMIT_FIELD] === '1';
        $url = isset($_POST[self::GOOGLE_DOC_URL_FIELD]) && is_string($_POST[self::GOOGLE_DOC_URL_FIELD])
            ? trim($this->unslash($_POST[self::GOOGLE_DOC_URL_FIELD]))
            : '';

        if (!$attempted) {
            return ['attempted' => false, 'nonce_valid' => true, 'successful' => false, 'url' => $url, 'json' => null, 'acquisition' => null];
        }
        if (!$this->verifyGoogleDocNonce()) {
            return ['attempted' => true, 'nonce_valid' => false, 'successful' => false, 'url' => $url, 'json' => null, 'acquisition' => null];
        }
        if ($this->googleDocs === null) {
            $acquisition = GoogleDocsAcquisition::failure(null, new ImportIssue(ImportIssue::ERROR, 'google_docs_adapter_unavailable', 'The Google Docs source adapter is unavailable.'));
            return ['attempted' => true, 'nonce_valid' => true, 'successful' => false, 'url' => $url, 'json' => null, 'acquisition' => $acquisition];
        }

        $acquisition = $this->googleDocs->acquire($url);
        $json = $acquisition->json();
        return ['attempted' => true, 'nonce_valid' => true, 'successful' => $acquisition->successful() && $json !== null, 'url' => $url, 'json' => $json, 'acquisition' => $acquisition];
    }

    private function verifyGoogleDocNonce(): bool
    {
        if (!function_exists('wp_verify_nonce')) { return true; }
        $nonce = isset($_POST[self::GOOGLE_DOC_NONCE_FIELD]) && is_string($_POST[self::GOOGLE_DOC_NONCE_FIELD])
            ? $this->unslash($_POST[self::GOOGLE_DOC_NONCE_FIELD]) : '';
        return $nonce !== '' && (bool) wp_verify_nonce($nonce, self::GOOGLE_DOC_NONCE_ACTION);
    }

    private function renderGoogleDocNonce(): string
    {
        if (!function_exists('wp_nonce_field')) { return ''; }
        ob_start();
        wp_nonce_field(self::GOOGLE_DOC_NONCE_ACTION, self::GOOGLE_DOC_NONCE_FIELD, false);
        return (string) ob_get_clean();
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

            <p class="gmrexp-reading-room__compatibility-boundary"><strong>Staging boundary:</strong> this staging result remains request-local until the Keeper explicitly sends it to the Review Desk. Review decisions persist privately for that Administrator, but neither desk mutates the Catalogue, installs an Almanac, or activates content.</p>
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


    private function renderSendToReviewDesk(string $json, ?string $baseUrl = null): string
    {
        if ($json === '' || $this->reviewer === null || $this->reviewQueue === null) { return ''; }
        ob_start(); ?>
        <form class="gmrexp-reading-room__review-handoff" method="post" action="<?php echo $this->escAttr($this->navigationUrl('review', $baseUrl)); ?>">
            <?php echo $this->renderReviewNonce(); ?>
            <input type="hidden" name="<?php echo $this->escAttr(self::REVIEW_QUEUE_SUBMIT_FIELD); ?>" value="1">
            <textarea name="<?php echo $this->escAttr(self::REVIEW_JSON_FIELD); ?>" hidden><?php echo $this->escHtml($json); ?></textarea>
            <div><p class="gmrexp-reading-room__book-label">Ready for the Keeper</p><p>Move this staged source into your private Review Desk queue. Nothing is published by this action.</p></div>
            <button class="gmrexp-reading-room__button" type="submit">Send to Review Desk →</button>
        </form>
        <?php return trim((string) ob_get_clean());
    }

    private function renderReviewDesk(?string $baseUrl = null): string
    {
        $notice = ''; $error = '';
        if ($this->reviewer === null || $this->reviewQueue === null || $this->importer === null) {
            return '<section class="gmrexp-reading-room__section"><p class="gmrexp-reading-room__kicker">Red ink and questionable margins</p><h2>The Review Desk</h2><div class="gmrexp-reading-room__empty"><h3>The red ledger is unavailable.</h3><p>The Review API, Import API, or Review queue has not been supplied.</p></div></section>';
        }

        if ($this->reviewPostAttempted() && !$this->verifyReviewNonce()) {
            $error = 'The Review Desk stamp could not be verified. Reload the desk and try again.';
        } elseif (isset($_POST[self::REVIEW_QUEUE_SUBMIT_FIELD])) {
            $json = isset($_POST[self::REVIEW_JSON_FIELD]) && is_string($_POST[self::REVIEW_JSON_FIELD]) ? $this->unslash($_POST[self::REVIEW_JSON_FIELD]) : '';
            $result = $this->importer->stageJson($json);
            if ($json === '' || $result->definitions() === []) {
                $error = 'The staged source could not be opened for review.';
            } else {
                $this->reviewQueue->save(['json' => $json, 'decisions' => []]);
                $notice = 'The staged source is now on the Review Desk.';
            }
        } elseif (isset($_POST[self::REVIEW_CLEAR_SUBMIT_FIELD])) {
            $this->reviewQueue->clear();
            $notice = 'The Review Desk has been cleared. No canonical content was changed.';
        } elseif (isset($_POST[self::ALMANAC_PUBLICATION_SUBMIT_FIELD])) {
            try {
                $publishedKey = $this->publishAlmanac();
                $notice = sprintf('The Keeper rang the bell. Almanac "%s" is published and installed in the Library.', $publishedKey);
            } catch (\Throwable $exception) {
                $error = $exception->getMessage();
            }
        } elseif (isset($_POST[self::ALMANAC_PROPOSAL_SUBMIT_FIELD])) {
            try {
                $this->buildAlmanacProposal();
                $notice = 'The Shelving Trolley has assembled a proposed Almanac.';
            } catch (\Throwable $exception) {
                $error = $exception->getMessage();
            }
        } elseif (isset($_POST[self::REVIEW_BULK_SUBMIT_FIELD])) {
            try {
                $summary = $this->applyBulkReviewDecision();
                $notice = sprintf(
                    'Bulk review complete: %d accepted · %d rejected · %d requires attention.',
                    $summary['accepted'],
                    $summary['rejected'],
                    $summary['attention']
                );
            } catch (\Throwable $exception) {
                $error = $exception->getMessage();
            }
        } elseif (isset($_POST[self::REVIEW_DECISION_SUBMIT_FIELD])) {
            try {
                $this->applyReviewDecision();
                $notice = 'The Keeper\'s decision has been recorded.';
            } catch (\Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        $session = $this->reviewSessionFromState($this->reviewQueue->load());
        ob_start(); ?>
        <section class="gmrexp-reading-room__section gmrexp-reading-room__review" aria-labelledby="gmrexp-review-heading">
            <div class="gmrexp-reading-room__section-heading">
                <div><p class="gmrexp-reading-room__kicker">Red ink and questionable margins</p><h2 id="gmrexp-review-heading">The Keeper's Review Desk</h2><p class="gmrexp-reading-room__section-intro">Classify, amend, approve, or reject staged source records. Pippin may preserve and suggest; the Keeper decides what a record means.</p></div>
                <span class="gmrexp-reading-room__status">Review API <?php echo $this->escHtml($this->reviewer->apiVersion()); ?></span>
            </div>
            <div class="gmrexp-reading-room__review-boundary"><strong>Reviewed ≠ Published.</strong><span>Decisions remain in the Administrator's private Review Desk queue. The Shelving Trolley can assemble approved definitions later; review itself does not publish, install, activate, or mutate the Catalogue.</span></div>
            <?php if ($notice !== ''): ?><div class="gmrexp-reading-room__notice" role="status"><?php echo $this->escHtml($notice); ?></div><?php endif; ?>
            <?php if ($error !== ''): ?><div class="gmrexp-reading-room__import-security" role="alert"><strong>Review decision not recorded.</strong><p><?php echo $this->escHtml($error); ?></p></div><?php endif; ?>

            <?php if (!$session instanceof ReviewSession): ?>
                <div class="gmrexp-reading-room__empty"><h3>No papers on the desk.</h3><p>Stage source material at the Import Desk, then choose <strong>Send to Review Desk</strong>.</p><a class="gmrexp-reading-room__back" href="<?php echo $this->escAttr($this->navigationUrl('import', $baseUrl)); ?>">Go to Import Desk →</a></div>
            <?php else: ?>
                <?php $source = $session->source(); ?>
                <div class="gmrexp-reading-room__review-summary">
                    <div><span>Source</span><strong><?php echo $this->escHtml($source->title()); ?></strong></div>
                    <div><span>Records</span><strong><?php echo count($session->items()); ?></strong></div>
                    <div><span>Pending</span><strong><?php echo $session->pendingCount(); ?></strong></div>
                    <div><span>Accepted</span><strong><?php echo $session->approvedCount() + $session->amendedCount(); ?></strong></div>
                    <div><span>Rejected</span><strong><?php echo $session->rejectedCount(); ?></strong></div>
                </div>
                <div class="gmrexp-reading-room__review-toolbar">
                    <p><strong><?php echo $session->resolvedCount(); ?></strong> of <strong><?php echo count($session->items()); ?></strong> records resolved.</p>
                    <form method="post" action="<?php echo $this->escAttr($this->navigationUrl('review', $baseUrl)); ?>"><?php echo $this->renderReviewNonce(); ?><input type="hidden" name="<?php echo $this->escAttr(self::REVIEW_CLEAR_SUBMIT_FIELD); ?>" value="1"><button class="gmrexp-reading-room__button gmrexp-reading-room__button--compact" type="submit">Clear Review Desk</button></form>
                </div>
                <?php if ($session->pendingCount() > 0): ?>
                    <form id="gmrexp-review-bulk-form" class="gmrexp-reading-room__bulk-review" data-gmrexp-bulk-review method="post" action="<?php echo $this->escAttr($this->navigationUrl('review', $baseUrl)); ?>">
                        <?php echo $this->renderReviewNonce(); ?>
                        <input type="hidden" name="<?php echo $this->escAttr(self::REVIEW_BULK_SUBMIT_FIELD); ?>" value="1">
                        <div>
                            <p class="gmrexp-reading-room__book-label">Bulk review</p>
                            <strong><span data-gmrexp-bulk-count>0</span> selected</strong>
                            <span class="gmrexp-reading-room__field-help">Only checked pending records are affected. Records that cannot be accepted safely remain pending for individual attention.</span>
                        </div>
                        <div class="gmrexp-reading-room__bulk-review-controls">
                            <button class="gmrexp-reading-room__back" type="button" data-gmrexp-select-all>Select all pending</button>
                            <button class="gmrexp-reading-room__back" type="button" data-gmrexp-select-none>Select none</button>
                            <button class="gmrexp-reading-room__button gmrexp-reading-room__button--compact" type="submit" name="<?php echo $this->escAttr(self::REVIEW_BULK_ACTION_FIELD); ?>" value="accept">Accept selected</button>
                            <button class="gmrexp-reading-room__back" type="submit" name="<?php echo $this->escAttr(self::REVIEW_BULK_ACTION_FIELD); ?>" value="reject" data-gmrexp-confirm="Reject the selected staged records? This records a Review Desk decision only; nothing is published.">Reject selected</button>
                        </div>
                    </form>
                <?php endif; ?>
                <?php echo $this->renderShelvingTrolley($session, $baseUrl); ?>

                <div class="gmrexp-reading-room__review-stack"><?php foreach ($session->items() as $item) { echo $this->renderReviewItem($item, $baseUrl); } ?></div>
            <?php endif; ?>
        </section>
        <?php return trim((string) ob_get_clean());
    }

    private function renderReviewItem(ReviewItem $item, ?string $baseUrl): string
    {
        $staged = $item->staged();
        $definition = $item->definition();
        $data = $definition?->data() ?? $staged->sourceData();
        $name = isset($data['name']) && is_string($data['name']) ? $data['name'] : '';
        $description = isset($data['description']) && is_string($data['description']) ? $data['description'] : '';
        $type = $definition?->type() ?? '';
        $key = $definition?->key() ?? '';
        $context = $staged->sourceContext();
        $heading = isset($context['heading']) && is_string($context['heading']) ? $context['heading'] : $name;
        $dataJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
        ob_start(); ?>
        <article class="gmrexp-reading-room__review-card" data-status="<?php echo $this->escAttr($item->status()); ?>" id="<?php echo $this->escAttr($item->reviewId()); ?>">
            <div class="gmrexp-reading-room__review-card-topline">
                <div class="gmrexp-reading-room__review-card-heading">
                    <?php if ($item->pending()): ?><label class="gmrexp-reading-room__review-select"><input type="checkbox" name="<?php echo $this->escAttr(self::REVIEW_BULK_RECORDS_FIELD); ?>[]" value="<?php echo $this->escAttr($item->recordId()); ?>" form="gmrexp-review-bulk-form" data-gmrexp-review-select aria-label="Select <?php echo $this->escAttr($heading !== '' ? $heading : $item->recordId()); ?> for bulk review"><span aria-hidden="true">Select</span></label><?php endif; ?>
                    <div><p class="gmrexp-reading-room__book-label"><?php echo $this->escHtml($item->recordId()); ?></p><h3><?php echo $this->escHtml($heading !== '' ? $heading : 'Unresolved staged record'); ?></h3></div>
                </div>
                <span class="gmrexp-reading-room__pill"><?php echo $this->escHtml(ucfirst($item->status())); ?></span>
            </div>
            <?php if ($context !== []): ?><details class="gmrexp-reading-room__review-source"><summary>Source context</summary><pre><code><?php echo $this->escHtml((string) json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?></code></pre></details><?php endif; ?>
            <?php if ($staged->issues() !== []): ?><div class="gmrexp-reading-room__review-questions"><?php foreach ($staged->issues() as $issue): ?><p><strong><?php echo $this->escHtml($issue->code()); ?></strong> — <?php echo $this->escHtml($issue->message()); ?></p><?php endforeach; ?></div><?php endif; ?>

            <?php if ($item->resolved()): ?>
                <div class="gmrexp-reading-room__review-resolution"><strong><?php echo $this->escHtml($item->rejected() ? 'Ignored / rejected as canonical content' : 'Accepted for the proposed Almanac'); ?></strong><?php if ($item->definition() !== null): ?> <code><?php echo $this->escHtml($item->definition()->type() . ':' . $item->definition()->key()); ?></code><?php endif; ?><?php if ($item->note() !== ''): ?><p><?php echo $this->escHtml($item->note()); ?></p><?php endif; ?>
                    <form method="post" action="<?php echo $this->escAttr($this->navigationUrl('review', $baseUrl) . '#' . $item->reviewId()); ?>"><?php echo $this->renderReviewNonce(); ?><input type="hidden" name="<?php echo $this->escAttr(self::REVIEW_DECISION_SUBMIT_FIELD); ?>" value="1"><input type="hidden" name="<?php echo $this->escAttr(self::REVIEW_RECORD_FIELD); ?>" value="<?php echo $this->escAttr($item->recordId()); ?>"><input type="hidden" name="<?php echo $this->escAttr(self::REVIEW_ACTION_FIELD); ?>" value="reset"><button class="gmrexp-reading-room__back" type="submit">Reconsider</button></form>
                </div>
            <?php else: ?>
                <?php $draft = $this->reviewDraft($item, $type, $key, $name, $description, $data); ?>
                <?php $type = $draft['type']; $key = $draft['key']; $name = $draft['name']; $description = $draft['description']; $data = $draft['data']; ?>
                <?php $dataJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}'; ?>
                <?php $schemaMap = $this->reviewRequiredSchemaMap(); ?>
                <form class="gmrexp-reading-room__review-form" data-gmrexp-review-form method="post" action="<?php echo $this->escAttr($this->navigationUrl('review', $baseUrl) . '#' . $item->reviewId()); ?>">
                    <?php echo $this->renderReviewNonce(); ?><input type="hidden" name="<?php echo $this->escAttr(self::REVIEW_DECISION_SUBMIT_FIELD); ?>" value="1"><input type="hidden" name="<?php echo $this->escAttr(self::REVIEW_RECORD_FIELD); ?>" value="<?php echo $this->escAttr($item->recordId()); ?>">
                    <div class="gmrexp-reading-room__review-grid">
                        <label><span>Content type</span><select name="gmrexp_review_type" data-gmrexp-review-type><option value="">Choose what this record is…</option><?php foreach (CoreContentTypes::all() as $contentType): ?><option value="<?php echo $this->escAttr($contentType->key()); ?>" <?php echo $contentType->key() === $type ? 'selected' : ''; ?>><?php echo $this->escHtml($contentType->label()); ?></option><?php endforeach; ?></select></label>
                        <label><span>Canonical key</span><input type="text" name="gmrexp_review_key" value="<?php echo $this->escAttr($key); ?>" placeholder="e.g. pizza-mimic"></label>
                    </div>
                    <label><span>Name <strong aria-hidden="true">*</strong></span><input type="text" name="gmrexp_review_name" value="<?php echo $this->escAttr($name); ?>" required></label>
                    <label><span>Description / source prose</span><textarea name="gmrexp_review_description" rows="5"><?php echo $this->escHtml($description); ?></textarea></label>

                    <section class="gmrexp-reading-room__schema-requirements" data-gmrexp-schema-requirements data-schema="<?php echo $this->escAttr((string) json_encode($schemaMap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?>" data-existing="<?php echo $this->escAttr((string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?>">
                        <div class="gmrexp-reading-room__schema-heading">
                            <div><p class="gmrexp-reading-room__book-label">Canonical requirements</p><h4>Required fields for this content type</h4></div>
                            <span class="gmrexp-reading-room__pill" data-gmrexp-schema-count><?php echo $this->escHtml((string) count($this->reviewRequiredFields($type))); ?> required</span>
                        </div>
                        <p class="gmrexp-reading-room__field-help" data-gmrexp-schema-help><?php echo $type === '' ? 'Choose a content type and the Review Desk will show its required canonical fields here.' : 'Complete the required fields below before accepting this classification. Complex map/array fields use JSON.'; ?></p>
                        <div class="gmrexp-reading-room__schema-fields" data-gmrexp-schema-fields>
                            <?php echo $this->renderReviewRequiredFields($type, $data); ?>
                        </div>
                    </section>
                    <?php echo $this->renderMonsterReviewContract($type, $data); ?>

                    <details class="gmrexp-reading-room__review-advanced"><summary>Advanced content data</summary><p class="gmrexp-reading-room__field-help">The existing Review API validates this complete data map. Name, description, and the schema-aware required fields above overwrite matching values when accepted. Optional schema fields can still be added here.</p><textarea name="gmrexp_review_data" rows="10" spellcheck="false"><?php echo $this->escHtml($dataJson); ?></textarea></details>
                    <label><span>Keeper note (optional)</span><input type="text" name="gmrexp_review_note" value="<?php echo $this->escAttr($draft['note']); ?>" placeholder="Why was this classified, amended, or ignored?"></label>
                    <div class="gmrexp-reading-room__review-actions"><?php if ($item->canApproveOriginal()): ?><button class="gmrexp-reading-room__button" type="submit" name="<?php echo $this->escAttr(self::REVIEW_ACTION_FIELD); ?>" value="approve">Approve Original</button><?php endif; ?><button class="gmrexp-reading-room__button" type="submit" name="<?php echo $this->escAttr(self::REVIEW_ACTION_FIELD); ?>" value="amend">Accept Classification / Amendment</button><button class="gmrexp-reading-room__back" type="submit" name="<?php echo $this->escAttr(self::REVIEW_ACTION_FIELD); ?>" value="reject">Ignore / Reject</button></div>
                </form>
            <?php endif; ?>
        </article>
        <?php return trim((string) ob_get_clean());
    }



    /**
     * Preserve a failed amendment on the same request so the Keeper does not
     * lose a chosen type or schema values after canonical validation refuses it.
     *
     * @param array<string,mixed> $data
     * @return array{type:string,key:string,name:string,description:string,data:array<string,mixed>,note:string}
     */
    private function reviewDraft(
        ReviewItem $item,
        string $type,
        string $key,
        string $name,
        string $description,
        array $data
    ): array {
        $recordId = isset($_POST[self::REVIEW_RECORD_FIELD]) && is_string($_POST[self::REVIEW_RECORD_FIELD])
            ? trim($this->unslash($_POST[self::REVIEW_RECORD_FIELD]))
            : '';
        $action = isset($_POST[self::REVIEW_ACTION_FIELD]) && is_string($_POST[self::REVIEW_ACTION_FIELD])
            ? strtolower(trim($this->unslash($_POST[self::REVIEW_ACTION_FIELD])))
            : '';

        if ($recordId !== $item->recordId() || $action !== 'amend') {
            return [
                'type' => $type,
                'key' => $key,
                'name' => $name,
                'description' => $description,
                'data' => $data,
                'note' => '',
            ];
        }

        $type = isset($_POST['gmrexp_review_type']) && is_string($_POST['gmrexp_review_type'])
            ? trim($this->unslash($_POST['gmrexp_review_type'])) : $type;
        $key = isset($_POST['gmrexp_review_key']) && is_string($_POST['gmrexp_review_key'])
            ? trim($this->unslash($_POST['gmrexp_review_key'])) : $key;
        $name = isset($_POST['gmrexp_review_name']) && is_string($_POST['gmrexp_review_name'])
            ? trim($this->unslash($_POST['gmrexp_review_name'])) : $name;
        $description = isset($_POST['gmrexp_review_description']) && is_string($_POST['gmrexp_review_description'])
            ? trim($this->unslash($_POST['gmrexp_review_description'])) : $description;
        $note = isset($_POST['gmrexp_review_note']) && is_string($_POST['gmrexp_review_note'])
            ? trim($this->unslash($_POST['gmrexp_review_note'])) : '';

        $rawData = isset($_POST['gmrexp_review_data']) && is_string($_POST['gmrexp_review_data'])
            ? $this->unslash($_POST['gmrexp_review_data']) : '';
        if ($rawData !== '') {
            $decoded = json_decode($rawData, true);
            if (is_array($decoded) && (!array_is_list($decoded) || $decoded === [])) {
                $data = $decoded;
            }
        }

        if ($name !== '') { $data['name'] = $name; }
        if ($description !== '') { $data['description'] = $description; }

        try {
            $data = $this->mergePostedReviewSchemaData($type, $data);
        } catch (ReviewDecisionException) {
            // Keep whatever can be represented safely; the original validation
            // message remains visible above the Review Desk.
        }

        return [
            'type' => $type,
            'key' => $key,
            'name' => $name,
            'description' => $description,
            'data' => $data,
            'note' => $note,
        ];
    }

    /** @return array<string,list<array{name:string,type:string,label:string,required:bool,allow_empty:bool,control:string}>> */
    private function reviewRequiredSchemaMap(): array
    {
        if ($this->schemas === null) { return []; }

        $map = [];
        foreach ($this->schemas->all() as $type => $schema) {
            $map[$type] = [];
            foreach ($schema->fields() as $field) {
                if (!$field->required() || $field->name() === 'name') { continue; }
                $map[$type][] = [
                    'name' => $field->name(),
                    'type' => $field->type(),
                    'label' => $this->schemaFieldLabel($field->name()),
                    'required' => true,
                    'allow_empty' => $field->allowEmpty(),
                    'control' => $this->schemaFieldControl($type, $field->name()),
                ];
            }
        }
        ksort($map);
        return $map;
    }

    /** @return list<FieldDefinition> */
    private function reviewRequiredFields(string $type): array
    {
        if ($type === '' || $this->schemas === null) { return []; }
        $schema = $this->schemas->get($type);
        if ($schema === null) { return []; }

        return array_values(array_filter(
            $schema->fields(),
            static fn (FieldDefinition $field): bool => $field->required() && $field->name() !== 'name'
        ));
    }

    /** @param array<string,mixed> $data */
    private function renderReviewRequiredFields(string $type, array $data): string
    {
        $fields = $this->reviewRequiredFields($type);
        if ($fields === []) { return ''; }

        ob_start();
        foreach ($fields as $field) {
            $name = $field->name();
            $value = $data[$name] ?? null;
            $inputName = 'gmrexp_review_schema[' . $name . ']';
            $control = $this->schemaFieldControl($type, $name);
            ?>
            <label class="gmrexp-reading-room__schema-field" data-schema-field="<?php echo $this->escAttr($name); ?>" data-schema-control="<?php echo $this->escAttr($control); ?>">
                <span><?php echo $this->escHtml($this->schemaFieldLabel($name)); ?> <strong aria-hidden="true">*</strong> <small><?php echo $this->escHtml($field->type()); ?><?php echo $field->allowEmpty() ? ' · may be empty' : ''; ?></small></span>
                <?php if ($control === 'creature-type'): ?>
                    <input type="text" name="<?php echo $this->escAttr($inputName); ?>" value="<?php echo $this->escAttr($this->schemaFriendlyValue($control, $value)); ?>" list="gmrexp-creature-types" placeholder="e.g. Humanoid" required>
                    <small class="gmrexp-reading-room__field-help">Choose the creature classification intended by the source. Common examples include Humanoid, Fey, Construct, Undead, and Monstrosity; the Review Desk does not choose one for you.</small>
                <?php elseif ($control === 'size'): ?>
                    <input type="text" name="<?php echo $this->escAttr($inputName); ?>" value="<?php echo $this->escAttr($this->schemaFriendlyValue($control, $value)); ?>" list="gmrexp-creature-sizes" placeholder="e.g. Medium" required>
                    <small class="gmrexp-reading-room__field-help">Enter the race's fixed size. More complex size choices can still be represented in Advanced content data.</small>
                <?php elseif ($control === 'walking-speed'): ?>
                    <input type="number" min="1" step="1" name="<?php echo $this->escAttr($inputName); ?>" value="<?php echo $this->escAttr($this->schemaFriendlyValue($control, $value)); ?>" placeholder="30" required>
                    <small class="gmrexp-reading-room__field-help">Walking speed in feet. Optional swim, climb, fly, burrow, or hover movement can be added in Advanced content data.</small>
                <?php elseif ($control === 'string-list'): ?>
                    <input type="text" name="<?php echo $this->escAttr($inputName); ?>" value="<?php echo $this->escAttr($this->schemaFriendlyValue($control, $value)); ?>" placeholder="e.g. Common, Market Tongue" required>
                    <small class="gmrexp-reading-room__field-help">Separate languages with commas. Only enter languages actually established for this race.</small>
                <?php elseif ($control === 'canonical-string-list'): ?>
                    <input type="text" name="<?php echo $this->escAttr($inputName); ?>" value="<?php echo $this->escAttr($this->schemaFriendlyValue($control, $value)); ?>" placeholder="e.g. Strength, Constitution" required>
                    <small class="gmrexp-reading-room__field-help">Separate canonical values with commas. The Review Desk preserves exactly what the Keeper enters; it does not infer missing proficiencies.</small>
                <?php elseif ($control === 'progression-lines'): ?>
                    <textarea name="<?php echo $this->escAttr($inputName); ?>" rows="8" spellcheck="false" required><?php echo $this->escHtml($this->schemaFriendlyValue($control, $value)); ?></textarea>
                    <small class="gmrexp-reading-room__field-help">One level per line: <code>3 | hangry-rage</code> or <code>6 | feed-me, second-feature</code>. A class must still define every level through Max Level; subclasses may use only the levels at which they grant features.</small>
                <?php elseif ($control === 'trait-lines'): ?>
                    <textarea name="<?php echo $this->escAttr($inputName); ?>" rows="7" spellcheck="false" required><?php echo $this->escHtml($this->schemaFriendlyValue($control, $value)); ?></textarea>
                    <small class="gmrexp-reading-room__field-help">One trait per line: <code>canonical-key | Trait Name | Description</code>. The Keeper supplies the key; Pippin does not invent it. Rules can be refined later in Advanced content data.</small>
                <?php elseif ($control === 'proficiency-groups'): ?>
                    <textarea name="<?php echo $this->escAttr($inputName); ?>" rows="5" spellcheck="false" required><?php echo $this->escHtml($this->schemaFriendlyValue($control, $value)); ?></textarea>
                    <small class="gmrexp-reading-room__field-help">One proficiency group per line: <code>skills | Athletics, Survival</code> or <code>tools | Cook's Utensils</code>. Use only groups and proficiencies established by the source.</small>
                <?php elseif ($control === 'feature-lines'): ?>
                    <textarea name="<?php echo $this->escAttr($inputName); ?>" rows="7" spellcheck="false" required><?php echo $this->escHtml($this->schemaFriendlyValue($control, $value)); ?></textarea>
                    <small class="gmrexp-reading-room__field-help">One feature per line: <code>canonical-key | Feature Name | Description</code>. The Keeper supplies the canonical key.</small>
                <?php elseif ($control === 'empty-array-allowed'): ?>
                    <textarea name="<?php echo $this->escAttr($inputName); ?>" rows="4" spellcheck="false"><?php echo $this->escHtml($this->schemaFieldValue($field, $value)); ?></textarea>
                    <small class="gmrexp-reading-room__field-help">This field is canonical but may be empty. Leave it as <code>[]</code> (or blank) when the source specifies no starting equipment. If equipment is defined, enter the existing canonical JSON array here.</small>
                <?php elseif (in_array($field->type(), [FieldDefinition::MAP, FieldDefinition::ARRAY], true)): ?>
                    <textarea name="<?php echo $this->escAttr($inputName); ?>" rows="5" spellcheck="false" required><?php echo $this->escHtml($this->schemaFieldValue($field, $value)); ?></textarea>
                    <small class="gmrexp-reading-room__field-help"><?php echo $field->type() === FieldDefinition::MAP ? 'Enter a JSON object, for example {"walk": 30}.' : 'Enter a JSON array, for example ["Common"].'; ?></small>
                <?php elseif ($field->type() === FieldDefinition::BOOLEAN): ?>
                    <select name="<?php echo $this->escAttr($inputName); ?>" required>
                        <option value="">Choose…</option>
                        <option value="1" <?php echo $value === true ? 'selected' : ''; ?>>Yes</option>
                        <option value="0" <?php echo $value === false ? 'selected' : ''; ?>>No</option>
                    </select>
                <?php elseif (in_array($field->type(), [FieldDefinition::INTEGER, FieldDefinition::NUMBER], true)): ?>
                    <input type="number" name="<?php echo $this->escAttr($inputName); ?>" value="<?php echo $this->escAttr($this->schemaFieldValue($field, $value)); ?>" <?php echo $field->type() === FieldDefinition::NUMBER ? 'step="any"' : 'step="1"'; ?> required>
                <?php else: ?>
                    <input type="text" name="<?php echo $this->escAttr($inputName); ?>" value="<?php echo $this->escAttr($this->schemaFieldValue($field, $value)); ?>" required>
                <?php endif; ?>
            </label>
            <?php
        }
        ?>
        <datalist id="gmrexp-creature-types"><option value="Humanoid"><option value="Fey"><option value="Construct"><option value="Undead"><option value="Monstrosity"><option value="Elemental"><option value="Plant"><option value="Ooze"><option value="Aberration"><option value="Beast"><option value="Celestial"><option value="Dragon"><option value="Fiend"><option value="Giant"></datalist>
        <datalist id="gmrexp-creature-sizes"><option value="Tiny"><option value="Small"><option value="Medium"><option value="Large"><option value="Huge"><option value="Gargantuan"></datalist>
        <?php
        return trim((string) ob_get_clean());
    }

    private function schemaFieldControl(string $type, string $name): string
    {
        if ($type === 'race') {
            return match ($name) {
                'creature_type' => 'creature-type',
                'size' => 'size',
                'speed' => 'walking-speed',
                'languages' => 'string-list',
                'traits' => 'trait-lines',
                default => 'schema',
            };
        }
        if ($type === 'subrace' && $name === 'traits') { return 'trait-lines'; }
        if ($type === 'background') {
            return match ($name) {
                'proficiencies' => 'proficiency-groups',
                'starting_equipment' => 'empty-array-allowed',
                'features' => 'feature-lines',
                default => 'schema',
            };
        }
        if ($type === 'class') {
            return match ($name) {
                'saving_throw_proficiencies' => 'canonical-string-list',
                'proficiencies' => 'proficiency-groups',
                'features' => 'feature-lines',
                'progression' => 'progression-lines',
                default => 'schema',
            };
        }
        if ($type === 'subclass') {
            return match ($name) {
                'features' => 'feature-lines',
                'progression' => 'progression-lines',
                default => 'schema',
            };
        }
        return 'schema';
    }

    private function schemaFriendlyValue(string $control, mixed $value): string
    {
        if ($control === 'size') {
            return is_array($value) && isset($value['value']) && is_string($value['value']) ? $value['value'] : '';
        }
        if ($control === 'walking-speed') {
            return is_array($value) && isset($value['walk']) && is_int($value['walk']) ? (string) $value['walk'] : '';
        }
        if (in_array($control, ['string-list', 'canonical-string-list'], true)) {
            return is_array($value) ? implode(', ', array_values(array_filter($value, 'is_string'))) : '';
        }
        if ($control === 'progression-lines') {
            if (!is_array($value)) { return ''; }
            $lines = [];
            foreach ($value as $row) {
                if (!is_array($row) || array_is_list($row) || !isset($row['level']) || !is_int($row['level'])) { continue; }
                $features = isset($row['features']) && is_array($row['features']) && array_is_list($row['features'])
                    ? array_values(array_filter($row['features'], 'is_string'))
                    : [];
                $lines[] = (string) $row['level'] . ' | ' . implode(', ', $features);
            }
            return implode("\n", $lines);
        }
        if (in_array($control, ['trait-lines', 'feature-lines'], true)) {
            if (!is_array($value)) { return ''; }
            $lines = [];
            foreach ($value as $feature) {
                if (!is_array($feature) || array_is_list($feature)) { continue; }
                $key = isset($feature['key']) && is_string($feature['key']) ? $feature['key'] : '';
                $name = isset($feature['name']) && is_string($feature['name']) ? $feature['name'] : '';
                $description = isset($feature['description']) && is_string($feature['description']) ? $feature['description'] : '';
                if ($key !== '' || $name !== '') { $lines[] = $key . ' | ' . $name . ($description !== '' ? ' | ' . $description : ''); }
            }
            return implode("\n", $lines);
        }
        if ($control === 'proficiency-groups') {
            if (!is_array($value) || array_is_list($value)) { return ''; }
            $lines = [];
            foreach ($value as $group => $entries) {
                if (!is_string($group) || !is_array($entries) || !array_is_list($entries)) { continue; }
                $entries = array_values(array_filter($entries, 'is_string'));
                if ($entries !== []) { $lines[] = $group . ' | ' . implode(', ', $entries); }
            }
            return implode("\n", $lines);
        }
        return is_scalar($value) ? (string) $value : '';
    }

    private function schemaFieldLabel(string $name): string
    {
        return ucwords(str_replace('_', ' ', $name));
    }

    private function schemaFieldValue(FieldDefinition $field, mixed $value): string
    {
        if ($value === null) { return ''; }
        if (in_array($field->type(), [FieldDefinition::MAP, FieldDefinition::ARRAY], true)) {
            return is_array($value)
                ? ((string) (json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: ''))
                : '';
        }
        if ($field->type() === FieldDefinition::BOOLEAN) { return $value === true ? '1' : ($value === false ? '0' : ''); }
        return is_scalar($value) ? (string) $value : '';
    }

    /** @param array<string,mixed> $data */
    private function renderMonsterReviewContract(string $type, array $data): string
    {
        $m = static fn (string $key, mixed $fallback = ''): mixed => $data[$key] ?? $fallback;
        $ac = is_array($m('armour_class', [])) ? $m('armour_class', []) : [];
        $hp = is_array($m('hit_points', [])) ? $m('hit_points', []) : [];
        $speed = is_array($m('speed', [])) ? $m('speed', []) : [];
        $abilities = is_array($m('abilities', [])) ? $m('abilities', []) : [];
        $challenge = is_array($m('challenge', [])) ? $m('challenge', []) : [];
        $list = static fn (mixed $value): string => is_array($value) ? implode(', ', array_values(array_filter($value, 'is_string'))) : '';
        $mapLines = static function (mixed $value): string {
            if (!is_array($value) || array_is_list($value)) { return ''; }
            $lines = [];
            foreach ($value as $key => $number) { if (is_int($number) || is_float($number)) { $lines[] = $key . ' | ' . $number; } }
            return implode("\n", $lines);
        };
        ob_start(); ?>
        <section class="gmrexp-reading-room__schema-requirements gmrexp-reading-room__monster-contract" data-gmrexp-monster-contract<?php echo $type === 'monster' ? '' : ' hidden'; ?>>
            <div class="gmrexp-reading-room__schema-heading"><div><p class="gmrexp-reading-room__book-label">Bestiary contract</p><h4>The Keeper Measures the Monster</h4></div><span class="gmrexp-reading-room__pill">Companion-ready</span></div>
            <p class="gmrexp-reading-room__field-help">These fields mirror the shared Bestiary information already used by the Companion Steward's Workshop. Nothing is guessed: leave a field blank when the source does not establish it. Expansion publication remains separate from Companion publication.</p>
            <div class="gmrexp-reading-room__review-grid">
                <label><span>Size</span><input name="gmrexp_review_monster[size]" value="<?php echo $this->escAttr(is_string($m('size')) ? $m('size') : ''); ?>" placeholder="Medium"></label>
                <label><span>Creature type</span><input name="gmrexp_review_monster[creature_type]" value="<?php echo $this->escAttr(is_string($m('creature_type')) ? $m('creature_type') : ''); ?>" placeholder="Monstrosity"></label>
                <label><span>Alignment</span><input name="gmrexp_review_monster[alignment]" value="<?php echo $this->escAttr(is_string($m('alignment')) ? $m('alignment') : ''); ?>" placeholder="Unaligned"></label>
                <label><span>Challenge Rating</span><input name="gmrexp_review_monster[challenge_rating]" value="<?php echo $this->escAttr(isset($challenge['rating']) && is_scalar($challenge['rating']) ? (string)$challenge['rating'] : ''); ?>" placeholder="3"></label>
                <label><span>Armor Class</span><input type="number" min="0" name="gmrexp_review_monster[ac]" value="<?php echo $this->escAttr(isset($ac['value']) && is_int($ac['value']) ? (string)$ac['value'] : ''); ?>" placeholder="13"></label>
                <label><span>Armor description</span><input name="gmrexp_review_monster[armor_type]" value="<?php echo $this->escAttr(isset($ac['type']) && is_string($ac['type']) ? $ac['type'] : ''); ?>" placeholder="natural armour"></label>
                <label><span>Hit Points</span><input type="number" min="1" name="gmrexp_review_monster[hp]" value="<?php echo $this->escAttr(isset($hp['average']) && is_int($hp['average']) ? (string)$hp['average'] : ''); ?>" placeholder="76"></label>
                <label><span>HP formula</span><input name="gmrexp_review_monster[hp_formula]" value="<?php echo $this->escAttr(isset($hp['formula']) && is_string($hp['formula']) ? $hp['formula'] : ''); ?>" placeholder="9d8 + 36"></label>
                <label><span>Walking Speed</span><input type="number" min="0" name="gmrexp_review_monster[walk]" value="<?php echo $this->escAttr(isset($speed['walk']) && is_int($speed['walk']) ? (string)$speed['walk'] : ''); ?>" placeholder="30"></label>
                <label><span>Climb Speed</span><input type="number" min="0" name="gmrexp_review_monster[climb]" value="<?php echo $this->escAttr(isset($speed['climb']) && is_int($speed['climb']) ? (string)$speed['climb'] : ''); ?>"></label>
                <label><span>Fly Speed</span><input type="number" min="0" name="gmrexp_review_monster[fly]" value="<?php echo $this->escAttr(isset($speed['fly']) && is_int($speed['fly']) ? (string)$speed['fly'] : ''); ?>"></label>
                <label><span>Swim Speed</span><input type="number" min="0" name="gmrexp_review_monster[swim]" value="<?php echo $this->escAttr(isset($speed['swim']) && is_int($speed['swim']) ? (string)$speed['swim'] : ''); ?>"></label>
                <label><span>Burrow Speed</span><input type="number" min="0" name="gmrexp_review_monster[burrow]" value="<?php echo $this->escAttr(isset($speed['burrow']) && is_int($speed['burrow']) ? (string)$speed['burrow'] : ''); ?>"></label>
                <label><span>Proficiency Bonus</span><input type="number" min="0" name="gmrexp_review_monster[proficiency_bonus]" value="<?php echo $this->escAttr(is_int($m('proficiency_bonus')) ? (string)$m('proficiency_bonus') : ''); ?>"></label>
            </div>
            <fieldset class="gmrexp-reading-room__schema-requirements"><legend><strong>Ability Scores</strong></legend><div class="gmrexp-reading-room__review-grid"><?php foreach (['strength'=>'STR','dexterity'=>'DEX','constitution'=>'CON','intelligence'=>'INT','wisdom'=>'WIS','charisma'=>'CHA'] as $ability=>$label): ?><label><span><?php echo $label; ?></span><input type="number" min="0" max="30" name="gmrexp_review_monster[ability_<?php echo $ability; ?>]" value="<?php echo $this->escAttr(isset($abilities[$ability]) && is_int($abilities[$ability]) ? (string)$abilities[$ability] : ''); ?>"></label><?php endforeach; ?></div></fieldset>
            <div class="gmrexp-reading-room__review-grid">
                <label><span>Saving Throws</span><textarea name="gmrexp_review_monster[saving_throws]" rows="3" placeholder="wisdom | 5"><?php echo $this->escHtml($mapLines($m('saving_throws', []))); ?></textarea></label>
                <label><span>Skills</span><textarea name="gmrexp_review_monster[skills]" rows="3" placeholder="stealth | 5"><?php echo $this->escHtml($mapLines($m('skills', []))); ?></textarea></label>
                <?php foreach (['damage_vulnerabilities'=>'Damage Vulnerabilities','damage_resistances'=>'Damage Resistances','damage_immunities'=>'Damage Immunities','condition_immunities'=>'Condition Immunities','languages'=>'Languages'] as $field=>$label): ?><label><span><?php echo $this->escHtml($label); ?></span><input name="gmrexp_review_monster[<?php echo $field; ?>]" value="<?php echo $this->escAttr($list($m($field, []))); ?>" placeholder="comma-separated"></label><?php endforeach; ?>
                <label><span>Senses</span><textarea name="gmrexp_review_monster[senses]" rows="3" placeholder="darkvision | 60&#10;passive_perception | 11"><?php echo $this->escHtml($mapLines($m('senses', []))); ?></textarea></label>
                <label><span>Challenge XP</span><input type="number" min="0" name="gmrexp_review_monster[challenge_xp]" value="<?php echo $this->escAttr(isset($challenge['xp']) && is_int($challenge['xp']) ? (string)$challenge['xp'] : ''); ?>"></label>
            </div>
            <?php foreach (['traits'=>'Special Traits','actions'=>'Actions','bonus_actions'=>'Bonus Actions','reactions'=>'Reactions','legendary_actions'=>'Legendary Actions','mythic_actions'=>'Mythic Features / Actions','lair_actions'=>'Lair Actions'] as $field=>$label): ?><label><span><?php echo $this->escHtml($label); ?></span><textarea name="gmrexp_review_monster[<?php echo $field; ?>]" rows="5" spellcheck="false"><?php echo $this->escHtml($this->schemaFriendlyValue('feature-lines', $m($field, []))); ?></textarea><small class="gmrexp-reading-room__field-help">One entry per line: <code>canonical-key | Name | Description</code>.</small></label><?php endforeach; ?>
            <label><span>Player-safe description</span><textarea name="gmrexp_review_monster[player_description]" rows="3"><?php echo $this->escHtml(is_string($m('player_description')) ? $m('player_description') : ''); ?></textarea></label>
            <label><span>Steward / lore notes</span><textarea name="gmrexp_review_monster[notes]" rows="4"><?php echo $this->escHtml(is_string($m('notes')) ? $m('notes') : ''); ?></textarea></label>
        </section>
        <?php return trim((string) ob_get_clean());
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function mergePostedReviewSchemaData(string $type, array $data): array
    {
        if ($type === 'monster') { $data = $this->mergePostedMonsterReviewData($data); }
        $fields = $this->reviewRequiredFields($type);
        if ($fields === []) { return $data; }

        $posted = $_POST['gmrexp_review_schema'] ?? [];
        if (!is_array($posted)) { $posted = []; }

        foreach ($fields as $field) {
            $name = $field->name();
            if (!array_key_exists($name, $posted)) {
                unset($data[$name]);
                continue;
            }

            $raw = $posted[$name];
            if (!is_string($raw)) {
                throw new ReviewDecisionException(sprintf('Required field "%s" could not be read.', $this->schemaFieldLabel($name)));
            }
            $raw = trim($this->unslash($raw));
            if ($raw === '') {
                if ($field->allowEmpty()) {
                    $data[$name] = $field->type() === FieldDefinition::ARRAY ? [] : ($field->type() === FieldDefinition::MAP ? [] : '');
                } else {
                    unset($data[$name]);
                }
                continue;
            }

            $control = $this->schemaFieldControl($type, $name);
            $data[$name] = match ($control) {
                'creature-type' => $raw,
                'size' => ['value' => $raw],
                'walking-speed' => ['walk' => $this->parseReviewInteger($name, $raw)],
                'string-list', 'canonical-string-list' => $this->parseReviewStringList($name, $raw),
                'progression-lines' => $this->parseReviewProgressionLines($raw),
                'trait-lines' => $this->parseReviewFeatureLines($raw, 'Race traits'),
                'feature-lines' => $this->parseReviewFeatureLines($raw, in_array($type, ['class', 'subclass'], true) ? 'Class features' : 'Background features'),
                'proficiency-groups' => $this->parseReviewProficiencyGroups($raw),
                'empty-array-allowed' => $this->parseReviewJsonCollection($name, $raw, true),
                default => match ($field->type()) {
                    FieldDefinition::STRING => $raw,
                    FieldDefinition::INTEGER => $this->parseReviewInteger($name, $raw),
                    FieldDefinition::NUMBER => $this->parseReviewNumber($name, $raw),
                    FieldDefinition::BOOLEAN => $this->parseReviewBoolean($name, $raw),
                    FieldDefinition::ARRAY => $this->parseReviewJsonCollection($name, $raw, true),
                    FieldDefinition::MAP => $this->parseReviewJsonCollection($name, $raw, false),
                    default => $raw,
                },
            };
        }
        return $data;
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function mergePostedMonsterReviewData(array $data): array
    {
        $posted = $_POST['gmrexp_review_monster'] ?? null;
        if (!is_array($posted)) { return $data; }
        $get = fn (string $key): string => isset($posted[$key]) && is_string($posted[$key]) ? trim($this->unslash($posted[$key])) : '';
        foreach (['size','creature_type','alignment','player_description','notes'] as $field) { $v=$get($field); if ($v !== '') { $data[$field]=$v; } else { unset($data[$field]); } }
        $ac=$get('ac'); $acType=$get('armor_type'); if ($ac !== '') { $data['armour_class']=['value'=>$this->parseReviewInteger('armour_class', $ac)]; if ($acType !== '') { $data['armour_class']['type']=$acType; } } else { unset($data['armour_class']); }
        $hp=$get('hp'); $formula=$get('hp_formula'); if ($hp !== '') { $data['hit_points']=['average'=>$this->parseReviewInteger('hit_points', $hp)]; if ($formula !== '') { $data['hit_points']['formula']=$formula; } } else { unset($data['hit_points']); }
        $speed=[]; foreach (['walk','climb','fly','swim','burrow'] as $mode) { $v=$get($mode); if ($v !== '') { $speed[$mode]=$this->parseReviewInteger('speed_'.$mode,$v); } } if ($speed !== []) { $data['speed']=$speed; } else { unset($data['speed']); }
        $abilities=[]; foreach (['strength','dexterity','constitution','intelligence','wisdom','charisma'] as $ability) { $v=$get('ability_'.$ability); if ($v !== '') { $abilities[$ability]=$this->parseReviewInteger($ability,$v); } } if ($abilities !== []) { $data['abilities']=$abilities; } else { unset($data['abilities']); }
        foreach (['saving_throws','skills','senses'] as $field) { $v=$get($field); if ($v !== '') { $data[$field]=$this->parseReviewNumericLines($field,$v); } else { unset($data[$field]); } }
        foreach (['damage_vulnerabilities','damage_resistances','damage_immunities','condition_immunities','languages'] as $field) { $v=$get($field); if ($v !== '') { $data[$field]=$this->parseReviewStringList($field,$v); } else { unset($data[$field]); } }
        $rating=$get('challenge_rating'); $xp=$get('challenge_xp'); if ($rating !== '') { $data['challenge']=['rating'=>is_numeric($rating) ? (float)$rating : $rating]; if ($xp !== '') { $data['challenge']['xp']=$this->parseReviewInteger('challenge_xp',$xp); } } else { unset($data['challenge']); }
        $pb=$get('proficiency_bonus'); if ($pb !== '') { $data['proficiency_bonus']=$this->parseReviewInteger('proficiency_bonus',$pb); } else { unset($data['proficiency_bonus']); }
        foreach (['traits','actions','bonus_actions','reactions','legendary_actions','mythic_actions','lair_actions'] as $field) { $v=$get($field); if ($v !== '') { $data[$field]=$this->parseReviewFeatureLines($v,'Monster '.str_replace('_',' ',$field)); } else { unset($data[$field]); } }
        return $data;
    }

    /** @return array<string,int|float> */
    private function parseReviewNumericLines(string $field, string $raw): array
    {
        $out=[]; foreach (preg_split('/\\R+/', $raw) ?: [] as $line) { $line=trim($line); if ($line==='') continue; $parts=array_map('trim', explode('|',$line,2)); if (count($parts)!==2 || $parts[0]==='' || !is_numeric($parts[1])) { throw new ReviewDecisionException(sprintf('%s lines must use key | numeric bonus/distance.', $this->schemaFieldLabel($field))); } $out[$parts[0]]=str_contains($parts[1],'.') ? (float)$parts[1] : (int)$parts[1]; } return $out;
    }

    /** @return list<string> */
    private function parseReviewStringList(string $name, string $raw): array
    {
        $values = preg_split('/[\r\n,]+/', $raw) ?: [];
        $values = array_values(array_filter(array_map('trim', $values), static fn (string $value): bool => $value !== ''));
        if ($values === []) {
            throw new ReviewDecisionException(sprintf('%s requires at least one value.', $this->schemaFieldLabel($name)));
        }
        return $values;
    }

    /** @return list<array{key:string,name:string,description?:string}> */
    private function parseReviewFeatureLines(string $raw, string $label): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $features = [];
        foreach ($lines as $index => $line) {
            $line = trim($line);
            if ($line === '') { continue; }
            $parts = array_map('trim', explode('|', $line, 3));
            $key = $parts[0] ?? '';
            $name = $parts[1] ?? '';
            $description = $parts[2] ?? '';
            if ($key === '' || $name === '') {
                throw new ReviewDecisionException(sprintf('%s line %d must use: canonical-key | Name | Description.', $label, $index + 1));
            }
            $feature = ['key' => $key, 'name' => $name];
            if ($description !== '') { $feature['description'] = $description; }
            $features[] = $feature;
        }
        if ($features === []) { throw new ReviewDecisionException(sprintf('%s require at least one Keeper-defined entry.', $label)); }
        return $features;
    }

    /** @return array<string,list<string>> */
    private function parseReviewProficiencyGroups(string $raw): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $groups = [];
        foreach ($lines as $index => $line) {
            $line = trim($line);
            if ($line === '') { continue; }
            $parts = array_map('trim', explode('|', $line, 2));
            $group = $parts[0] ?? '';
            $valuesRaw = $parts[1] ?? '';
            if ($group === '' || $valuesRaw === '') {
                throw new ReviewDecisionException(sprintf('Proficiencies line %d must use: group | Value, Value.', $index + 1));
            }
            $values = array_values(array_filter(array_map('trim', explode(',', $valuesRaw)), static fn (string $value): bool => $value !== ''));
            if ($values === []) {
                throw new ReviewDecisionException(sprintf('Proficiencies group "%s" requires at least one value.', $group));
            }
            if (isset($groups[$group])) {
                throw new ReviewDecisionException(sprintf('Proficiencies group "%s" is duplicated.', $group));
            }
            $groups[$group] = $values;
        }
        if ($groups === []) { throw new ReviewDecisionException('Proficiencies require at least one Keeper-defined group.'); }
        return $groups;
    }

    /** @return list<array{level:int,features?:list<string>}> */
    private function parseReviewProgressionLines(string $raw): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $progression = [];
        $seen = [];
        foreach ($lines as $index => $line) {
            $line = trim($line);
            if ($line === '') { continue; }
            $parts = array_map('trim', explode('|', $line, 2));
            $levelRaw = $parts[0] ?? '';
            $featuresRaw = $parts[1] ?? '';
            if (!preg_match('/^\d+$/', $levelRaw) || (int) $levelRaw < 1) {
                throw new ReviewDecisionException(sprintf('Progression line %d must begin with a positive level: level | feature-key, feature-key.', $index + 1));
            }
            $level = (int) $levelRaw;
            if (isset($seen[$level])) {
                throw new ReviewDecisionException(sprintf('Progression level %d is duplicated.', $level));
            }
            $seen[$level] = true;
            $row = ['level' => $level];
            $features = array_values(array_filter(array_map('trim', explode(',', $featuresRaw)), static fn (string $value): bool => $value !== ''));
            if ($features !== []) { $row['features'] = $features; }
            $progression[] = $row;
        }
        if ($progression === []) {
            throw new ReviewDecisionException('Progression requires at least one Keeper-defined level.');
        }
        return $progression;
    }

    private function parseReviewInteger(string $name, string $raw): int
    {
        if (!preg_match('/^-?\\d+$/', $raw)) {
            throw new ReviewDecisionException(sprintf('%s must be a whole number.', $this->schemaFieldLabel($name)));
        }
        return (int) $raw;
    }

    private function parseReviewNumber(string $name, string $raw): int|float
    {
        if (!is_numeric($raw)) {
            throw new ReviewDecisionException(sprintf('%s must be a number.', $this->schemaFieldLabel($name)));
        }
        return str_contains($raw, '.') ? (float) $raw : (int) $raw;
    }

    private function parseReviewBoolean(string $name, string $raw): bool
    {
        if (!in_array($raw, ['0', '1'], true)) {
            throw new ReviewDecisionException(sprintf('%s must be Yes or No.', $this->schemaFieldLabel($name)));
        }
        return $raw === '1';
    }

    /** @return array<mixed> */
    private function parseReviewJsonCollection(string $name, string $raw, bool $list): array
    {
        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ReviewDecisionException(sprintf('%s must contain valid JSON.', $this->schemaFieldLabel($name)), 0, $exception);
        }

        if (!is_array($decoded)) {
            throw new ReviewDecisionException(sprintf('%s must be a JSON %s.', $this->schemaFieldLabel($name), $list ? 'array' : 'object'));
        }
        if ($list && !array_is_list($decoded)) {
            throw new ReviewDecisionException(sprintf('%s must be a JSON array/list.', $this->schemaFieldLabel($name)));
        }
        if (!$list && array_is_list($decoded) && $decoded !== []) {
            throw new ReviewDecisionException(sprintf('%s must be a JSON object/map.', $this->schemaFieldLabel($name)));
        }
        return $decoded;
    }

    private function renderShelvingTrolley(ReviewSession $session, ?string $baseUrl): string
    {
        $state = $this->reviewQueue?->load();
        $proposal = is_array($state) && isset($state['proposal']) && is_array($state['proposal']) ? $state['proposal'] : null;
        $accepted = count($session->approvedDefinitions());
        $sourceTitle = $session->source()->title();

        ob_start(); ?>
        <section class="gmrexp-reading-room__trolley" aria-labelledby="gmrexp-trolley-heading">
            <div class="gmrexp-reading-room__trolley-heading">
                <div>
                    <p class="gmrexp-reading-room__kicker">Phase V.9 — The Shelving Trolley</p>
                    <h3 id="gmrexp-trolley-heading">Prepare a proposed Almanac</h3>
                    <p>The trolley collects only Keeper-approved definitions. Pending and rejected source records stay off the proposed shelf.</p>
                    <p class="gmrexp-reading-room__field-help"><strong>Proposed ≠ Published.</strong> The trolley prepares assembly state only.</p>
                </div>
                <span class="gmrexp-reading-room__status">Proposal API <?php echo $this->escHtml(AlmanacProposalService::API_VERSION); ?></span>
            </div>

            <?php if ($proposal !== null): ?>
                <?php $manifest = isset($proposal['manifest']) && is_array($proposal['manifest']) ? $proposal['manifest'] : []; ?>
                <div class="gmrexp-reading-room__proposal">
                    <div class="gmrexp-reading-room__proposal-topline">
                        <div><p class="gmrexp-reading-room__book-label">Proposed Almanac</p><h4><?php echo $this->escHtml((string) ($manifest['name'] ?? 'Untitled proposal')); ?></h4><code><?php echo $this->escHtml((string) ($manifest['key'] ?? '')); ?></code></div>
                        <span class="gmrexp-reading-room__pill"><?php echo !empty($proposal['complete_review']) ? 'Review complete' : 'Draft — review incomplete'; ?></span>
                    </div>
                    <div class="gmrexp-reading-room__proposal-facts">
                        <span><strong><?php echo $this->escHtml((string) ($proposal['definition_count'] ?? 0)); ?></strong> approved definitions</span>
                        <span><strong><?php echo $this->escHtml((string) ($proposal['pending_count'] ?? 0)); ?></strong> pending excluded</span>
                        <span><strong><?php echo $this->escHtml((string) ($proposal['rejected_count'] ?? 0)); ?></strong> rejected excluded</span>
                    </div>
                    <?php if (isset($manifest['artwork']) && is_string($manifest['artwork']) && $manifest['artwork'] !== ''): ?><p><strong>Library artwork:</strong> <code><?php echo $this->escHtml($manifest['artwork']); ?></code></p><?php endif; ?>
                    <p class="gmrexp-reading-room__field-help"><strong>Proposed ≠ Published.</strong> This is a persistent assembly preview only. It has not written files, installed an expansion, activated content, or changed the Catalogue.</p>
                </div>
            <?php endif; ?>

            <?php if ($proposal !== null && !empty($proposal['complete_review'])): ?>
                <?php $publishedKey = isset($proposal['manifest']['key']) && is_string($proposal['manifest']['key']) ? $proposal['manifest']['key'] : ''; ?>
                <?php if ($publishedKey !== '' && !$this->library->isInstalled($publishedKey)): ?>
                    <section class="gmrexp-reading-room__proposal gmrexp-reading-room__publication" aria-labelledby="gmrexp-publication-heading">
                        <div class="gmrexp-reading-room__proposal-topline">
                            <div><p class="gmrexp-reading-room__kicker">Phase V.10 — The Keeper Rings the Bell</p><h4 id="gmrexp-publication-heading">Publish this Almanac</h4></div>
                            <span class="gmrexp-reading-room__status">Publication API <?php echo $this->escHtml(AlmanacPublicationService::API_VERSION); ?></span>
                        </div>
                        <p>This is the canonical publication boundary. The complete proposal will be written atomically, passed through the existing Almanac Loader, installed into the Catalogue, and left <strong>inactive</strong> until the Keeper explicitly activates it.</p>
                        <p class="gmrexp-reading-room__field-help"><strong>Published ≠ Active.</strong> If any file, definition, artwork, or loader check fails, the publication is rolled back.</p>
                        <form method="post" enctype="multipart/form-data" action="<?php echo $this->escAttr($this->navigationUrl('review', $baseUrl) . '#gmrexp-publication-heading'); ?>">
                            <?php echo $this->renderReviewNonce(); ?>
                            <input type="hidden" name="<?php echo $this->escAttr(self::ALMANAC_PUBLICATION_SUBMIT_FIELD); ?>" value="1">
                            <?php if (isset($proposal['manifest']['artwork']) && is_string($proposal['manifest']['artwork']) && $proposal['manifest']['artwork'] !== ''): ?>
                                <label><span>Attach Library artwork</span><input type="file" name="<?php echo $this->escAttr(self::ALMANAC_ARTWORK_UPLOAD_FIELD); ?>" accept="image/jpeg,image/png,image/webp,image/gif" required></label>
                                <p class="gmrexp-reading-room__field-help">This image will be copied to <code><?php echo $this->escHtml($proposal['manifest']['artwork']); ?></code> inside the published pack. Maximum 10 MB.</p>
                            <?php endif; ?>
                            <button class="gmrexp-reading-room__button" type="submit">Ring the Bell — Publish Almanac →</button>
                        </form>
                    </section>
                <?php elseif ($publishedKey !== ''): ?>
                    <div class="gmrexp-reading-room__proposal"><strong>The bell has been rung.</strong> <span>This canonical key is already installed in the Library. Publication will not overwrite it.</span></div>
                <?php endif; ?>
            <?php endif; ?>

            <form class="gmrexp-reading-room__trolley-form" method="post" action="<?php echo $this->escAttr($this->navigationUrl('review', $baseUrl) . '#gmrexp-trolley-heading'); ?>">
                <?php echo $this->renderReviewNonce(); ?>
                <input type="hidden" name="<?php echo $this->escAttr(self::ALMANAC_PROPOSAL_SUBMIT_FIELD); ?>" value="1">
                <div class="gmrexp-reading-room__review-grid">
                    <label><span>Almanac name</span><input type="text" name="gmrexp_almanac_name" value="<?php echo $this->escAttr((string) (($proposal['manifest']['name'] ?? null) ?: $sourceTitle)); ?>" required></label>
                    <label><span>Canonical expansion key</span><input type="text" name="gmrexp_almanac_key" value="<?php echo $this->escAttr((string) ($proposal['manifest']['key'] ?? '')); ?>" placeholder="e.g. midnight-menu" required></label>
                </div>
                <div class="gmrexp-reading-room__review-grid">
                    <label><span>Version</span><input type="text" name="gmrexp_almanac_version" value="<?php echo $this->escAttr((string) ($proposal['manifest']['version'] ?? '0.1.0')); ?>" required></label>
                    <label><span>Library artwork path (optional)</span><input type="text" name="gmrexp_almanac_artwork" value="<?php echo $this->escAttr((string) ($proposal['manifest']['artwork'] ?? '')); ?>" placeholder="assets/library-cover.jpg"></label>
                </div>
                <label><span>Expansion description</span><textarea name="gmrexp_almanac_description" rows="3"><?php echo $this->escHtml((string) ($proposal['manifest']['description'] ?? '')); ?></textarea></label>
                <p class="gmrexp-reading-room__field-help">Artwork is a safe relative path inside the future expansion pack. Use a 3:2 landscape cover where possible; the Reading Room crops safely with object-fit: cover. The proposal records the path but does not upload or publish the image yet.</p>
                <button class="gmrexp-reading-room__button" type="submit" <?php echo $accepted === 0 ? 'disabled' : ''; ?>><?php echo $proposal === null ? 'Load the Shelving Trolley' : 'Rebuild Proposed Almanac'; ?> →</button>
                <?php if ($accepted === 0): ?><span class="gmrexp-reading-room__field-help">Accept at least one canonical definition before assembling a proposal.</span><?php endif; ?>
            </form>
        </section>
        <?php return trim((string) ob_get_clean());
    }

    private function buildAlmanacProposal(): void
    {
        if ($this->reviewQueue === null || $this->proposals === null) {
            throw new ReviewDecisionException('The Shelving Trolley is unavailable.');
        }
        $state = $this->reviewQueue->load();
        $session = $this->reviewSessionFromState($state);
        if (!$session instanceof ReviewSession || !is_array($state)) {
            throw new ReviewDecisionException('There is no reviewed source available for the Shelving Trolley.');
        }
        if ($session->approvedDefinitions() === []) {
            throw new ReviewDecisionException('Accept at least one canonical definition before assembling a proposed Almanac.');
        }

        $value = function (string $field): string {
            return isset($_POST[$field]) && is_string($_POST[$field]) ? trim($this->unslash($_POST[$field])) : '';
        };
        $key = $value('gmrexp_almanac_key');
        $name = $value('gmrexp_almanac_name');
        $version = $value('gmrexp_almanac_version');
        $description = $value('gmrexp_almanac_description');
        $artwork = $value('gmrexp_almanac_artwork');
        $metadata = $artwork === '' ? [] : ['artwork' => $artwork];

        $proposal = $this->proposals->propose($session, $key, $name, $version, $description, $metadata);
        $state['proposal'] = $proposal->toArray();
        $this->reviewQueue->save($state);
    }

    private function publishAlmanac(): string
    {
        if ($this->reviewQueue === null || $this->publisher === null) {
            throw new ReviewDecisionException("The Keeper's publication bell is unavailable.");
        }
        $state = $this->reviewQueue->load();
        $proposal = is_array($state) && isset($state['proposal']) && is_array($state['proposal']) ? $state['proposal'] : null;
        if ($proposal === null) {
            throw new ReviewDecisionException('Load the Shelving Trolley before ringing the publication bell.');
        }
        if (empty($proposal['complete_review'])) {
            throw new ReviewDecisionException('Finish the Review Desk before publishing this Almanac.');
        }
        if ($this->almanacStorage === null) {
            throw new ReviewDecisionException('The persistent Keeper Almanac library is unavailable.');
        }

        $upload = isset($_FILES[self::ALMANAC_ARTWORK_UPLOAD_FIELD]) && is_array($_FILES[self::ALMANAC_ARTWORK_UPLOAD_FIELD])
            ? $_FILES[self::ALMANAC_ARTWORK_UPLOAD_FIELD]
            : null;
        $result = $this->publisher->publish($proposal, $this->almanacStorage->ensureKeeperRoot(), $upload);

        // Installation and activation are deliberately separate boundaries.
        $this->library->setActive($result->key(), false);
        $state['published'] = [
            'key' => $result->key(),
            'definition_count' => $result->definitionCount(),
            'published_at' => gmdate('c'),
        ];
        $this->reviewQueue->save($state);
        $this->invalidateRememberedHostPageCache($result->key(), false);
        return $result->key();
    }

    /** @return array{accepted:int,rejected:int,attention:int} */
    private function applyBulkReviewDecision(): array
    {
        if ($this->reviewQueue === null || $this->reviewer === null || $this->importer === null) {
            throw new ReviewDecisionException('The Review Desk is unavailable.');
        }

        $state = $this->reviewQueue->load();
        $session = $this->reviewSessionFromState($state);
        if (!$session instanceof ReviewSession || !is_array($state)) {
            throw new ReviewDecisionException('There is no staged source on the Review Desk.');
        }

        $posted = $_POST[self::REVIEW_BULK_RECORDS_FIELD] ?? [];
        if (!is_array($posted)) { $posted = []; }
        $selected = [];
        foreach ($posted as $recordId) {
            if (!is_string($recordId)) { continue; }
            $recordId = trim($this->unslash($recordId));
            if ($recordId !== '') { $selected[$recordId] = true; }
        }
        if ($selected === []) {
            throw new ReviewDecisionException('Select at least one pending record for bulk review.');
        }

        $action = isset($_POST[self::REVIEW_BULK_ACTION_FIELD]) && is_string($_POST[self::REVIEW_BULK_ACTION_FIELD])
            ? strtolower(trim($this->unslash($_POST[self::REVIEW_BULK_ACTION_FIELD])))
            : '';
        if (!in_array($action, ['accept', 'reject'], true)) {
            throw new ReviewDecisionException('Choose a valid bulk review action.');
        }

        $decisions = isset($state['decisions']) && is_array($state['decisions']) ? $state['decisions'] : [];
        $accepted = 0; $rejected = 0; $attention = 0; $changed = false;

        foreach ($session->items() as $item) {
            if (!isset($selected[$item->recordId()]) || !$item->pending()) { continue; }

            if ($action === 'reject') {
                $decisions[$item->recordId()] = ['action' => 'reject', 'note' => 'Bulk rejected by the Keeper.'];
                $rejected++; $changed = true;
                continue;
            }

            if (!$item->canApproveOriginal()) {
                $attention++;
                continue;
            }

            $decisions[$item->recordId()] = ['action' => 'approve', 'note' => 'Bulk accepted by the Keeper.'];
            $accepted++; $changed = true;
        }

        if ($accepted + $rejected + $attention === 0) {
            throw new ReviewDecisionException('None of the selected records are pending review.');
        }

        if ($changed) {
            $state['decisions'] = $decisions;
            unset($state['proposal']);
            $this->reviewQueue->save($state);
        }

        return ['accepted' => $accepted, 'rejected' => $rejected, 'attention' => $attention];
    }

    private function applyReviewDecision(): void
    {
        if ($this->reviewQueue === null || $this->reviewer === null || $this->importer === null) { throw new ReviewDecisionException('The Review Desk is unavailable.'); }
        $state = $this->reviewQueue->load();
        $session = $this->reviewSessionFromState($state);
        if (!$session instanceof ReviewSession || !is_array($state)) { throw new ReviewDecisionException('There is no staged source on the Review Desk.'); }

        $recordId = isset($_POST[self::REVIEW_RECORD_FIELD]) && is_string($_POST[self::REVIEW_RECORD_FIELD]) ? trim($this->unslash($_POST[self::REVIEW_RECORD_FIELD])) : '';
        $action = isset($_POST[self::REVIEW_ACTION_FIELD]) && is_string($_POST[self::REVIEW_ACTION_FIELD]) ? strtolower(trim($this->unslash($_POST[self::REVIEW_ACTION_FIELD]))) : '';
        $item = null;
        foreach ($session->items() as $candidate) { if ($candidate->recordId() === $recordId) { $item = $candidate; break; } }
        if (!$item instanceof ReviewItem) { throw new ReviewDecisionException('The selected review record does not exist.'); }

        $decisions = isset($state['decisions']) && is_array($state['decisions']) ? $state['decisions'] : [];
        if ($action === 'reset') { unset($decisions[$recordId], $state['proposal']); $state['decisions'] = $decisions; $this->reviewQueue->save($state); return; }

        $note = isset($_POST['gmrexp_review_note']) && is_string($_POST['gmrexp_review_note']) ? trim($this->unslash($_POST['gmrexp_review_note'])) : '';
        if ($action === 'reject') {
            $decisions[$recordId] = ['action' => 'reject', 'note' => $note];
        } elseif ($action === 'approve') {
            if (!$item->canApproveOriginal()) { throw new ReviewDecisionException('This record is unresolved or invalid and must be classified/amended before acceptance.'); }
            $decisions[$recordId] = ['action' => 'approve', 'note' => $note];
        } elseif ($action === 'amend') {
            $type = isset($_POST['gmrexp_review_type']) && is_string($_POST['gmrexp_review_type']) ? trim($this->unslash($_POST['gmrexp_review_type'])) : '';
            $key = isset($_POST['gmrexp_review_key']) && is_string($_POST['gmrexp_review_key']) ? trim($this->unslash($_POST['gmrexp_review_key'])) : '';
            $rawData = isset($_POST['gmrexp_review_data']) && is_string($_POST['gmrexp_review_data']) ? $this->unslash($_POST['gmrexp_review_data']) : '{}';
            $data = json_decode($rawData, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data) || (array_is_list($data) && $data !== [])) { throw new ReviewDecisionException('Advanced content data must be a JSON object/map.'); }
            $name = isset($_POST['gmrexp_review_name']) && is_string($_POST['gmrexp_review_name']) ? trim($this->unslash($_POST['gmrexp_review_name'])) : '';
            $description = isset($_POST['gmrexp_review_description']) && is_string($_POST['gmrexp_review_description']) ? trim($this->unslash($_POST['gmrexp_review_description'])) : '';
            if ($name !== '') { $data['name'] = $name; } else { unset($data['name']); }
            if ($description !== '') { $data['description'] = $description; } else { unset($data['description']); }
            $data = $this->mergePostedReviewSchemaData($type, $data);
            $session->amend($item->reviewId(), $type, $key, $data, $note);
            $decisions[$recordId] = ['action' => 'amend', 'type' => $type, 'key' => $key, 'data' => $data, 'note' => $note];
        } else { throw new ReviewDecisionException('Choose a valid Keeper review action.'); }

        $state['decisions'] = $decisions;
        unset($state['proposal']);
        $this->reviewQueue->save($state);
    }

    /** @param array<string,mixed>|null $state */
    private function reviewSessionFromState(?array $state): ?ReviewSession
    {
        if ($state === null || $this->reviewer === null || $this->importer === null) { return null; }
        $json = isset($state['json']) && is_string($state['json']) ? $state['json'] : '';
        if ($json === '') { return null; }
        $result = $this->importer->stageJson($json);
        if ($result->definitions() === []) { return null; }
        $session = $this->reviewer->open($result);
        $decisions = isset($state['decisions']) && is_array($state['decisions']) ? $state['decisions'] : [];
        foreach ($session->items() as $item) {
            $decision = $decisions[$item->recordId()] ?? null;
            if (!is_array($decision)) { continue; }
            $action = isset($decision['action']) && is_string($decision['action']) ? $decision['action'] : '';
            $note = isset($decision['note']) && is_string($decision['note']) ? $decision['note'] : '';
            try {
                if ($action === 'approve') { $session->approve($item->reviewId(), $note); }
                elseif ($action === 'reject') { $session->reject($item->reviewId(), $note); }
                elseif ($action === 'amend') { $session->amend($item->reviewId(), (string) ($decision['type'] ?? ''), (string) ($decision['key'] ?? ''), is_array($decision['data'] ?? null) ? $decision['data'] : [], $note); }
            } catch (ReviewDecisionException) { /* stale decisions never bypass current validation */ }
        }
        return $session;
    }

    private function reviewPostAttempted(): bool
    {
        return isset($_POST[self::REVIEW_QUEUE_SUBMIT_FIELD]) || isset($_POST[self::REVIEW_BULK_SUBMIT_FIELD]) || isset($_POST[self::REVIEW_DECISION_SUBMIT_FIELD]) || isset($_POST[self::REVIEW_CLEAR_SUBMIT_FIELD]) || isset($_POST[self::ALMANAC_PROPOSAL_SUBMIT_FIELD]) || isset($_POST[self::ALMANAC_PUBLICATION_SUBMIT_FIELD]);
    }

    private function verifyReviewNonce(): bool
    {
        if (!function_exists('wp_verify_nonce')) { return true; }
        $nonce = isset($_POST[self::REVIEW_NONCE_FIELD]) && is_string($_POST[self::REVIEW_NONCE_FIELD]) ? $this->unslash($_POST[self::REVIEW_NONCE_FIELD]) : '';
        return $nonce !== '' && (bool) wp_verify_nonce($nonce, self::REVIEW_NONCE_ACTION);
    }

    private function renderReviewNonce(): string
    {
        if (!function_exists('wp_nonce_field')) { return ''; }
        ob_start(); wp_nonce_field(self::REVIEW_NONCE_ACTION, self::REVIEW_NONCE_FIELD, false); return (string) ob_get_clean();
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
        return '<main class="gmrexp-reading-room gmrexp-reading-room--gate"><section class="gmrexp-reading-room__gate"><p class="gmrexp-reading-room__eyebrow">Great MarketRealm Expansions</p><h1>Administrator access required.</h1><p>The Import Desk and Review Desk are restricted to administrators. Source material cannot be staged or reviewed from this account.</p></section></main>';
    }


    private function renderCatalogueEditor(CatalogueExpansion $expansion): string
    {
        $canManageExpansions = function_exists('current_user_can') ? current_user_can('manage_options') : true;
        if (!$canManageExpansions || $this->almanacStorage === null || !$this->almanacStorage->isKeeperPublished($expansion->key())) {
            return '';
        }

        ob_start(); ?>
        <details class="gmrexp-reading-room__catalogue-editor"><summary>Correct catalogue card</summary>
            <form method="post" enctype="multipart/form-data" action="<?php echo $this->escAttr(function_exists('admin_url') ? admin_url('admin-post.php') : ''); ?>">
                <input type="hidden" name="action" value="<?php echo $this->escAttr(self::METADATA_ACTION); ?>"><input type="hidden" name="expansion" value="<?php echo $this->escAttr($expansion->key()); ?>">
                <?php if (function_exists('wp_nonce_field')) { wp_nonce_field(self::METADATA_NONCE_ACTION); } ?>
                <div class="gmrexp-reading-room__catalogue-fields"><label><span>Name</span><input name="name" value="<?php echo $this->escAttr($expansion->name()); ?>" required></label><label><span>Version</span><input name="version" value="<?php echo $this->escAttr($expansion->version()); ?>" required></label></div>
                <label><span>Short Library summary</span><textarea name="description" maxlength="2000" rows="4"><?php echo $this->escHtml($expansion->description()); ?></textarea></label>
                <p class="gmrexp-reading-room__field-help">Keep this concise: it appears beneath the artwork on Library and Browse cards. Cover artwork is standardised to a 3:2 landscape frame.</p>
                <label><span>Library artwork path</span><input name="artwork" value="<?php echo $this->escAttr((string) $expansion->meta('artwork')); ?>" placeholder="assets/library-cover.png"></label>
                <label><span>Replacement artwork (optional)</span><input type="file" name="<?php echo $this->escAttr(self::METADATA_ARTWORK_UPLOAD_FIELD); ?>" accept="image/jpeg,image/png,image/webp,image/gif"></label>
                <p class="gmrexp-reading-room__field-help">Changing the path moves the existing pack artwork when possible. Attach a replacement image to replace it. Canonical definitions are never edited here.</p>
                <button class="gmrexp-reading-room__button" type="submit">Save catalogue correction →</button>
            </form>
        </details>
        <?php return trim((string) ob_get_clean());
    }

    private function libraryArtworkUrl(CatalogueExpansion $expansion): ?string
    {
        return $this->packArtworkUrl($expansion->key(), $expansion->meta('artwork'));
    }

    private function browseArtworkUrl(BrowseShelfEntry $entry): ?string
    {
        return $this->packArtworkUrl($entry->key(), $entry->meta('artwork'));
    }

    private function packArtworkUrl(string $expansionKey, mixed $artwork): ?string
    {
        if (!is_string($artwork)) { return null; }
        $artwork = trim(str_replace('\\', '/', $artwork));
        if ($artwork === '' || str_starts_with($artwork, '/') || str_contains($artwork, '..') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $artwork)) {
            return null;
        }
        if ($this->almanacStorage === null) { return null; }
        return $this->almanacStorage->artworkUrl($expansionKey, $artwork);
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
        if (function_exists('wp_enqueue_script')) {
            wp_enqueue_script(self::SCRIPT_HANDLE);
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
