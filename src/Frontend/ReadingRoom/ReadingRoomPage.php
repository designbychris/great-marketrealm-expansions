<?php
namespace GreatMarketrealmExpansions\Frontend\ReadingRoom;

defined('ABSPATH') || exit;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Library\Library;

final class ReadingRoomPage
{
    public const SHORTCODE = 'great_marketrealm_expansions';
    public const QUERY_VAR = 'gmrexp_reading_room';
    public const STYLE_HANDLE = 'gmrexp-reading-room';
    public const ROUTE_VERSION = '1.0.0';

    public function __construct(
        private Catalogue $catalogue,
        private Library $library,
        private ReadingRoomAccess $access,
        private ReadingRoomNavigation $navigation
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
            add_action('template_redirect', [$this, 'maybeRenderRoute']);
            add_action('wp_enqueue_scripts', [$this, 'registerAssets']);
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

    /** @param array<string,mixed>|string $attributes */
    public function shortcode(array|string $attributes = []): string
    {
        $section = 'library';

        if (is_array($attributes) && isset($attributes['section']) && is_string($attributes['section'])) {
            $section = $attributes['section'];
        }

        $this->enqueueAssets();

        return $this->render($section);
    }

    public function render(string $section = 'library'): string
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

            <?php echo $this->renderNavigation($section); ?>

            <?php if ($section !== 'library'): ?>
                <?php echo $this->renderPlaceholder($section); ?>
            <?php else: ?>
                <section class="gmrexp-reading-room__section" aria-labelledby="gmrexp-library-heading">
                    <div class="gmrexp-reading-room__section-heading">
                        <div>
                            <p class="gmrexp-reading-room__kicker">Current shelf</p>
                            <h2 id="gmrexp-library-heading">Your Library</h2>
                        </div>
                        <span class="gmrexp-reading-room__status">Reading only in V.1</span>
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
                                        <span class="gmrexp-reading-room__compatibility" data-status="<?php echo $this->escAttr($report->status()); ?>">
                                            <?php echo $this->escHtml(ucfirst($report->status())); ?>
                                        </span>
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
                    <p>Browse, Import Desk, and Review Desk now have stable routes and navigation positions. Their workflows remain deliberately closed until their own phases.</p>
                </section>
            <?php endif; ?>

            <footer class="gmrexp-reading-room__footer">
                <p><strong>Reading Room rule:</strong> the interface displays state; the underlying APIs continue to own its meaning.</p>
            </footer>
        </main>
        <?php
        return trim((string) ob_get_clean());
    }

    private function renderNavigation(string $current): string
    {
        ob_start();
        ?>
        <nav class="gmrexp-reading-room__nav" aria-label="Reading Room">
            <ul>
                <?php foreach ($this->navigation->items() as $section => $item): ?>
                    <li>
                        <a
                            href="<?php echo $this->escAttr($this->urlFor($section)); ?>"
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

    private function renderPlaceholder(string $section): string
    {
        $item = $this->navigation->items()[$section];
        ob_start();
        ?>
        <section class="gmrexp-reading-room__section gmrexp-reading-room__placeholder" aria-labelledby="gmrexp-placeholder-heading">
            <p class="gmrexp-reading-room__kicker">Reserved desk</p>
            <h2 id="gmrexp-placeholder-heading"><?php echo $this->escHtml($item['label']); ?></h2>
            <p>This route is intentionally stable from V.1 onward, but its workflow has not opened yet. No placeholder action mutates Catalogue, Library, Import, Review, or Migration state.</p>
            <a class="gmrexp-reading-room__back" href="<?php echo $this->escAttr($this->urlFor('library')); ?>">Return to Your Library</a>
        </section>
        <?php
        return trim((string) ob_get_clean());
    }

    private function renderLoginRequired(): string
    {
        $loginUrl = '#';
        if (function_exists('wp_login_url')) {
            $loginUrl = wp_login_url($this->urlFor('library'));
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
