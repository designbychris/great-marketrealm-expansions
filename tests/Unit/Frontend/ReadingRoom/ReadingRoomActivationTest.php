<?php
namespace GreatMarketrealmExpansions\Tests\Unit\Frontend\ReadingRoom;

use GreatMarketrealmExpansions\Catalogue\Catalogue;
use GreatMarketrealmExpansions\Content\ContentRegistry;
use GreatMarketrealmExpansions\Expansions\ExpansionPack;
use GreatMarketrealmExpansions\Expansions\ExpansionRegistry;
use GreatMarketrealmExpansions\Frontend\ReadingRoom\ReadingRoomAccess;
use GreatMarketrealmExpansions\Frontend\ReadingRoom\ReadingRoomNavigation;
use GreatMarketrealmExpansions\Frontend\ReadingRoom\ReadingRoomPage;
use GreatMarketrealmExpansions\Library\InMemoryActivationStore;
use GreatMarketrealmExpansions\Library\Library;
use PHPUnit\Framework\TestCase;

final class ReadingRoomActivationTest extends TestCase
{
    private Library $library;
    private ReadingRoomPage $page;

    protected function setUp(): void
    {
        $_POST = [];
        $_GET = [];

        $expansions = new ExpansionRegistry();
        $content = new ContentRegistry();
        $expansions->add(new ExpansionPack('fixture-book', 'Fixture Book'));

        $catalogue = new Catalogue($expansions, $content);
        $this->library = new Library($catalogue, new InMemoryActivationStore());
        $this->page = new ReadingRoomPage(
            $catalogue,
            $this->library,
            new ReadingRoomAccess(),
            new ReadingRoomNavigation()
        );
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_GET = [];
    }

    public function test_activation_action_name_is_stable(): void
    {
        self::assertSame(
            'gmrexp_reading_room_activation',
            ReadingRoomPage::ACTIVATION_ACTION
        );
    }

    public function test_activation_nonce_action_name_is_stable(): void
    {
        self::assertSame(
            'gmrexp_reading_room_activation',
            ReadingRoomPage::ACTIVATION_NONCE_ACTION
        );
    }

    public function test_browse_renders_deactivate_control_for_active_pack(): void
    {
        $html = $this->page->render('browse', 'https://example.test/expansions/');

        self::assertStringContainsString('Library activation', $html);
        self::assertStringContainsString('name="action" value="gmrexp_reading_room_activation"', $html);
        self::assertStringContainsString('name="expansion" value="fixture-book"', $html);
        self::assertStringContainsString('name="active" value="0"', $html);
        self::assertStringContainsString('>Deactivate</button>', $html);
    }

    public function test_browse_renders_activate_control_for_inactive_pack(): void
    {
        $this->library->setActive('fixture-book', false);

        $html = $this->page->render('browse', 'https://example.test/expansions/');

        self::assertStringContainsString('name="active" value="1"', $html);
        self::assertStringContainsString('>Activate</button>', $html);
        self::assertStringContainsString('remains installed and canonical', $html);
    }

    public function test_handler_deactivates_installed_pack(): void
    {
        $_POST = [
            'expansion' => 'fixture-book',
            'active' => '0',
        ];

        $this->page->handleActivation();

        self::assertFalse($this->library->isActive('fixture-book'));
    }

    public function test_handler_activates_installed_pack(): void
    {
        $this->library->setActive('fixture-book', false);
        $_POST = [
            'expansion' => 'fixture-book',
            'active' => '1',
        ];

        $this->page->handleActivation();

        self::assertTrue($this->library->isActive('fixture-book'));
    }

    public function test_handler_treats_only_literal_one_as_active(): void
    {
        $_POST = [
            'expansion' => 'fixture-book',
            'active' => 'yes',
        ];

        $this->page->handleActivation();

        self::assertFalse($this->library->isActive('fixture-book'));
    }

    public function test_handler_normalizes_expansion_key_before_library_lookup(): void
    {
        $_POST = [
            'expansion' => ' Fixture Book ',
            'active' => '0',
        ];

        $this->page->handleActivation();

        self::assertFalse($this->library->isActive('fixture-book'));
    }

    public function test_handler_ignores_unknown_expansion_without_mutating_known_pack(): void
    {
        $_POST = [
            'expansion' => 'missing-book',
            'active' => '0',
        ];

        $this->page->handleActivation();

        self::assertTrue($this->library->isActive('fixture-book'));
    }

    public function test_handler_ignores_empty_expansion_key(): void
    {
        $_POST = [
            'expansion' => '',
            'active' => '0',
        ];

        $this->page->handleActivation();

        self::assertTrue($this->library->isActive('fixture-book'));
    }

    public function test_activation_does_not_remove_pack_from_catalogue(): void
    {
        $_POST = [
            'expansion' => 'fixture-book',
            'active' => '0',
        ];

        $this->page->handleActivation();

        self::assertTrue($this->library->isInstalled('fixture-book'));
        self::assertFalse($this->library->isActive('fixture-book'));
    }

    public function test_activation_does_not_change_reading_room_route_contract(): void
    {
        self::assertSame('1.0.0', ReadingRoomPage::ROUTE_VERSION);
    }
}
