<?php
/**
 * Class AdminSettings_TheSettingsPageTest
 *
 * @package AspireUpdate
 */

/**
 * Tests for Admin_Settings::the_settings_page()
 *
 * @covers \AspireUpdate\Admin_Settings::the_settings_page
 */
class AdminSettings_TheSettingsPageTest extends AdminSettings_UnitTestCase {
	/**
	 * Test that the settings page is output.
	 */
	public function test_should_output_settings_page() {
		$admin_settings = new AspireUpdate\Admin_Settings();
		$actual         = get_echo( [ $admin_settings, 'the_settings_page' ] );

		$this->assertStringContainsString( 'AspireUpdate Settings', $actual );
	}
}
