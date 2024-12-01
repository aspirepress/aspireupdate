<?php
/**
 * Class AdminSettings_RegisterAdminMenuTest
 *
 * @package AspireUpdate
 */

/**
 * Tests for Admin_Settings::register_admin_menu()
 *
 * These tests cause constants to be defined.
 * They must run in separate processes and must not preserve global state.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @covers \AspireUpdate\Admin_Settings::register_admin_menu
 */
class AdminSettings_RegisterAdminMenuTest extends AdminSettings_UnitTestCase {
	/**
	 * The user ID of an administrator.
	 *
	 * @var int
	 */
	private static $admin_id;

	/**
	 * Create an administrator before any tests run.
	 *
	 * @return void
	 */
	public static function set_up_before_class() {
		self::$admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
	}

	/**
	 * Test that menu is not registered when AP_REMOVE_UI is enabled.
	 */
	public function test_should_not_register_menu_when_ap_remove_ui_is_enabled() {
		global $submenu;
		$original_submenu = $submenu;

		define( 'AP_REMOVE_UI', true );
		wp_set_current_user( self::$admin_id );

		$admin_settings = new \AspireUpdate\Admin_Settings();
		$admin_settings->register_admin_menu();

		$this->assertSame( $original_submenu, $submenu );
	}

	/**
	 * Test that menu is not registered when the user lacks appropriate permissions.
	 */
	public function test_should_not_register_menu_when_user_lacks_appropriate_permissions() {
		global $submenu;
		$original_submenu = $submenu;

		define( 'AP_REMOVE_UI', false );

		$admin_settings = new \AspireUpdate\Admin_Settings();
		$admin_settings->register_admin_menu();

		$this->assertSame( $original_submenu, $submenu );
	}

	/**
	 * Test that menu is registered when AP_REMOVE_UI is disabled.
	 */
	public function test_should_register_menu_when_ap_remove_ui_is_disabled() {
		global $submenu;

		define( 'AP_REMOVE_UI', false );
		wp_set_current_user( self::$admin_id );
		grant_super_admin( self::$admin_id );

		$admin_settings = new \AspireUpdate\Admin_Settings();
		$admin_settings->register_admin_menu();

		$this->assertIsArray(
			$submenu,
			'There are no submenus.'
		);

		$this->assertArrayHasKey(
			'index.php',
			$submenu,
			'There is no dashboard section.'
		);

		$this->assertIsArray(
			$submenu['index.php'],
			'There are no submenus for the dashboard.'
		);

		$last_menu_item = end( $submenu['index.php'] );
		$this->assertSame(
			'aspireupdate-settings',
			$last_menu_item[2],
			'The menu was not registered.'
		);
	}

	/**
	 * Test that AP_REMOVE_UI is defined when not already defined.
	 */
	public function test_should_define_ap_remove_ui_when_not_already_defined() {
		$this->assertFalse(
			defined( 'AP_REMOVE_UI' ),
			'AP_REMOVE_UI is already defined.'
		);

		$admin_settings = new AspireUpdate\Admin_Settings();
		$admin_settings->register_admin_menu();

		$this->assertTrue(
			defined( 'AP_REMOVE_UI' ),
			'AP_REMOVE_UI is not defined.'
		);

		$this->assertFalse(
			AP_REMOVE_UI,
			'AP_REMOVE_UI is not defined as (bool) false.'
		);
	}
}
