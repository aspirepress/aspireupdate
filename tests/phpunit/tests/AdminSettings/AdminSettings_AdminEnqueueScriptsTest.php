<?php
/**
 * Class AdminSettings_AdminEnqueueScriptsTest
 *
 * @package AspireUpdate
 */

/**
 * Tests for Admin_Settings::admin_enqueue_scripts()
 *
 * @covers \AspireUpdate\Admin_Settings::admin_enqueue_scripts
 */
class AdminSettings_AdminEnqueueScriptsTest extends AdminSettings_UnitTestCase {

	/**
	 * Dequeues assets before each test runs.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		wp_dequeue_style( 'aspire_update_settings_css' );
		wp_dequeue_script( 'aspire_update_settings_js' );
	}
	/**
	 * Test that the stylesheet is enqueued on the settings screen.
	 */
	public function test_should_enqueue_style() {
		$admin_settings = new AspireUpdate\Admin_Settings();

		if ( is_multisite() ) {
			$hook = 'index_page_aspireupdate-settings';
		} else {
			$hook = 'dashboard_page_aspireupdate-settings';
		}

		$admin_settings->admin_enqueue_scripts( $hook );

		$this->assertTrue( wp_style_is( 'aspire_update_settings_css' ) );
	}

	/**
	 * Test that the stylesheet is not enqueued on other screens.
	 */
	public function test_should_not_enqueue_style() {
		$admin_settings = new AspireUpdate\Admin_Settings();

		if ( is_multisite() ) {
			$hook = 'plugins-network';
		} else {
			$hook = 'plugins';
		}

		$admin_settings->admin_enqueue_scripts( $hook );

		$this->assertFalse( wp_style_is( 'aspire_update_settings_css' ) );
	}

	/**
	 * Test that the script is enqueued and localized on the settings screen.
	 */
	public function test_should_enqueue_and_localize_script() {
		$admin_settings = new AspireUpdate\Admin_Settings();

		if ( is_multisite() ) {
			$hook = 'index_page_aspireupdate-settings';
		} else {
			$hook = 'dashboard_page_aspireupdate-settings';
		}

		$admin_settings->admin_enqueue_scripts( $hook );

		$this->assertTrue(
			wp_script_is( 'aspire_update_settings_js' ),
			'The script is not enqueued.'
		);

		$this->assertNotEmpty(
			$GLOBALS['wp_scripts']->get_data( 'aspire_update_settings_js', 'data' ),
			'The script is not localized.'
		);
	}

	/**
	 * Test that the script is not enqueued on other screens.
	 */
	public function test_should_not_enqueue_script() {
		$admin_settings = new AspireUpdate\Admin_Settings();

		if ( is_multisite() ) {
			$hook = 'plugins-network';
		} else {
			$hook = 'plugins';
		}

		$admin_settings->admin_enqueue_scripts( $hook );

		$this->assertFalse( wp_script_is( 'aspire_update_settings_js' ) );
	}
}
