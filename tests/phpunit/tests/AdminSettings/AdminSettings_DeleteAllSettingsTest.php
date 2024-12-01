<?php
/**
 * Class AdminSettings_DeleteAllSettingsTest
 *
 * @package AspireUpdate
 */

/**
 * Tests for Admin_Settings::delete_all_settings()
 *
 * @covers \AspireUpdate\Admin_Settings::delete_all_settings
 */
class AdminSettings_DeleteAllSettingsTest extends AdminSettings_UnitTestCase {
	/**
	 * Test that all settings are deleted.
	 */
	public function test_should_delete_all_settings() {
		$settings = [
			'enable'                   => true,
			'api_host'                 => 'the.api.host',
			'api_key'                  => 'tHeApIkEy',
			'enable_debug'             => true,
			'enable_debug_types'       => [ 'response' => true ],
			'disable_ssl_verification' => true,
		];

		$this->assertTrue(
			update_site_option( self::$option_name, $settings ),
			'Settings were not set before the test.'
		);

		$admin_settings = new \AspireUpdate\Admin_Settings();

		$admin_settings->delete_all_settings();
		$this->assertFalse(
			get_site_option( self::$option_name, false ),
			'Settings were not deleted.'
		);
	}
}
