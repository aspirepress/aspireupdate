<?php
/**
 * Class AdminSettings_AdminNoticesTest
 *
 * @package AspireUpdate
 */

/**
 * Tests for Admin_Settings::admin_notices()
 *
 * @covers \AspireUpdate\Admin_Settings::admin_notices
 */
class AdminSettings_AdminNoticesTest extends AdminSettings_UnitTestCase {
	/**
	 * Test that the reset notice is not output when the 'aspireupdate-reset' option is not set to (string) "true".
	 */
	public function test_should_not_output_reset_notice_when_aspireupdatereset_option_is_not_set_to_true() {
		update_site_option( 'aspireupdate-reset', 'false' );
		$_GET['reset-success']       = 'success';
		$_GET['reset-success-nonce'] = wp_create_nonce( 'aspireupdate-reset-success-nonce' );

		$admin_settings = new \AspireUpdate\Admin_Settings();
		$actual         = get_echo( [ $admin_settings, 'admin_notices' ] );

		delete_site_option( 'aspireupdate-reset' );
		unset( $_GET['reset-success'], $_GET['reset-success-nonce'] );

		$this->assertStringNotContainsString( 'aspireupdate_settings_reset', $actual );
	}

	/**
	 * Test that the reset notice is not output when $_GET['reset-success'] is not set.
	 */
	public function test_should_not_output_reset_notice_when_get_resetsuccess_is_not_set() {
		update_site_option( 'aspireupdate-reset', 'true' );
		unset( $_GET['reset-success'] );
		$_GET['reset-success-nonce'] = wp_create_nonce( 'aspireupdate-reset-success-nonce' );

		$admin_settings = new \AspireUpdate\Admin_Settings();
		$actual         = get_echo( [ $admin_settings, 'admin_notices' ] );

		delete_site_option( 'aspireupdate-reset' );
		unset( $_GET['reset-success-nonce'] );

		$this->assertStringNotContainsString( 'aspireupdate_settings_reset', $actual );
	}

	/**
	 * Test that the reset notice is not output when $_GET['reset'] is set to an incorrect value.
	 */
	public function test_should_not_output_reset_notice_when_get_resetsuccess_is_set_to_an_incorrect_value() {
		update_site_option( 'aspireupdate-reset', 'true' );
		$_GET['reset-success']       = 'incorrect_value';
		$_GET['reset-success-nonce'] = wp_create_nonce( 'aspireupdate-reset-success-nonce' );

		$admin_settings = new \AspireUpdate\Admin_Settings();
		$actual         = get_echo( [ $admin_settings, 'admin_notices' ] );

		delete_site_option( 'aspireupdate-reset' );
		unset( $_GET['reset-success'], $_GET['reset-success-nonce'] );

		$this->assertStringNotContainsString( 'aspireupdate_settings_reset', $actual );
	}

	/**
	 * Test that the reset notice is not output when $_GET['reset-success-nonce'] is not set.
	 */
	public function test_should_not_output_reset_notice_when_get_resetsuccessnonce_is_not_set() {
		update_site_option( 'aspireupdate-reset', 'true' );
		$_GET['reset-success'] = 'success';
		unset( $_GET['reset-success-nonce'] );

		$admin_settings = new \AspireUpdate\Admin_Settings();
		$actual         = get_echo( [ $admin_settings, 'admin_notices' ] );

		delete_site_option( 'aspireupdate-reset' );
		unset( $_GET['reset-success'] );

		$this->assertStringNotContainsString( 'aspireupdate_settings_reset', $actual );
	}

	/**
	 * Test that the reset notice is not output when nonce verification fails.
	 */
	public function test_should_not_output_reset_notice_when_nonce_verification_fails() {
		update_site_option( 'aspireupdate-reset', 'true' );
		$_GET['reset-success']       = 'success';
		$_GET['reset-success-nonce'] = 'an_invalid_value';

		$admin_settings = new \AspireUpdate\Admin_Settings();
		$actual         = get_echo( [ $admin_settings, 'admin_notices' ] );

		delete_site_option( 'aspireupdate-reset' );
		unset( $_GET['reset-success'], $_GET['reset-success-nonce'] );

		$this->assertStringNotContainsString( 'aspireupdate_settings_reset', $actual );
	}

	/**
	 * Test that the reset notice is output.
	 */
	public function test_should_output_reset_notice() {
		update_site_option( 'aspireupdate-reset', 'true' );
		$_GET['reset-success']       = 'success';
		$_GET['reset-success-nonce'] = wp_create_nonce( 'aspireupdate-reset-success-nonce' );

		$admin_settings = new \AspireUpdate\Admin_Settings();
		$actual         = get_echo( [ $admin_settings, 'admin_notices' ] );

		unset( $_GET['reset-success'], $_GET['reset-success-nonce'] );

		$this->assertStringContainsString(
			'aspireupdate_settings_reset',
			$actual
		);
	}

	/**
	 * Test that the 'aspireupdate-reset' option is deleted.
	 */
	public function test_should_delete_aspireupdatereset_option_after_output() {
		update_site_option( 'aspireupdate-reset', 'true' );
		$_GET['reset-success']       = 'success';
		$_GET['reset-success-nonce'] = wp_create_nonce( 'aspireupdate-reset-success-nonce' );

		$admin_settings = new \AspireUpdate\Admin_Settings();
		get_echo( [ $admin_settings, 'admin_notices' ] );

		unset( $_GET['reset-success'], $_GET['reset-success-nonce'] );

		$this->assertFalse( get_site_option( 'aspireupdate-reset', false ) );
	}

	/**
	 * Test that the saved notice is not output when $_GET['reset-success-nonce'] is not set.
	 */
	public function test_should_not_output_saved_notice_when_get_resetsuccessnonce_is_not_set() {
		unset( $_GET['settings-updated-wpnonce'] );

		$admin_settings = new \AspireUpdate\Admin_Settings();
		$actual         = get_echo( [ $admin_settings, 'admin_notices' ] );

		$this->assertStringNotContainsString( 'aspireupdate_settings_saved', $actual );
	}

	/**
	 * Test that the saved notice is not output when nonce verification fails.
	 */
	public function test_should_not_output_saved_notice_when_nonce_verification_fails() {
		$_GET['settings-updated-wpnonce'] = 'an_invalid_value';

		$admin_settings = new \AspireUpdate\Admin_Settings();
		$actual         = get_echo( [ $admin_settings, 'admin_notices' ] );

		unset( $_GET['settings-updated-wpnonce'] );

		$this->assertStringNotContainsString( 'aspireupdate_settings_saved', $actual );
	}

	/**
	 * Test that the saved notice is output.
	 */
	public function test_should_output_saved_notice() {
		$_GET['settings-updated-wpnonce'] = wp_create_nonce( 'aspireupdate-settings-updated-nonce' );

		$admin_settings = new \AspireUpdate\Admin_Settings();
		$actual         = get_echo( [ $admin_settings, 'admin_notices' ] );

		unset( $_GET['settings-updated-wpnonce'] );

		$this->assertStringContainsString(
			'aspireupdate_settings_saved',
			$actual
		);
	}
}
