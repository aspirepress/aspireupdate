<?php
/**
 * Class AdminSettings_ResetSettingsTest
 *
 * @package AspireUpdate
 */

/**
 * Tests for Admin_Settings::reset_settings()
 *
 * @covers \AspireUpdate\Admin_Settings::reset_settings
 */
class AdminSettings_ResetSettingsTest extends AdminSettings_UnitTestCase {
	/**
	 * Test that settings are not reset when $_GET['reset'] is not set.
	 */
	public function test_should_not_reset_settings_when_get_reset_is_not_set() {
		unset( $_GET['reset'] );

		$settings = [ 'api_host' => 'the.option.value' ];
		update_site_option( self::$option_name, $settings );

		$admin_settings = new \AspireUpdate\Admin_Settings();
		$admin_settings->reset_settings();

		$this->assertSame( $settings, get_site_option( self::$option_name ) );
	}

	/**
	 * Test that a redirect is not performed when $_GET['reset'] is not set.
	 */
	public function test_should_not_redirect_when_get_reset_is_not_set() {
		unset( $_GET['reset'] );

		$settings = [ 'api_host' => 'the.option.value' ];
		update_site_option( self::$option_name, $settings );

		$redirect = new MockAction();
		add_filter( 'wp_redirect', [ $redirect, 'filter' ] );

		$admin_settings = new \AspireUpdate\Admin_Settings();
		$admin_settings->reset_settings();

		$this->assertSame( 0, $redirect->get_call_count() );
	}

	/**
	 * Test that settings are not reset when $_GET['reset'] is set to an incorrect value.
	 */
	public function test_should_not_reset_settings_when_get_reset_is_set_to_an_incorrect_value() {
		$_GET['reset'] = 'incorrect_value';
		$settings      = [ 'api_host' => 'the.option.value' ];
		update_site_option( self::$option_name, $settings );

		$admin_settings = new \AspireUpdate\Admin_Settings();
		$admin_settings->reset_settings();

		unset( $_GET['reset'] );

		$this->assertSame( $settings, get_site_option( self::$option_name ) );
	}

	/**
	 * Test that a redirect is not performed when $_GET['reset'] is set to an incorrect value.
	 */
	public function test_should_not_redirect_when_get_reset_is_set_to_an_incorrect_value() {
		$_GET['reset'] = 'incorrect_value';
		$settings      = [ 'api_host' => 'the.option.value' ];
		update_site_option( self::$option_name, $settings );

		$redirect = new MockAction();
		add_filter( 'wp_redirect', [ $redirect, 'filter' ] );

		$admin_settings = new \AspireUpdate\Admin_Settings();
		$admin_settings->reset_settings();

		unset( $_GET['reset'] );

		$this->assertSame( 0, $redirect->get_call_count() );
	}

	/**
	 * Test that settings are not reset when $_GET['reset-nonce'] is not set.
	 */
	public function test_should_not_reset_settings_when_get_resetnonce_is_not_set() {
		unset( $_GET['reset-nonce'] );

		$_GET['reset'] = 'reset';
		$settings      = [ 'api_host' => 'the.option.value' ];
		update_site_option( self::$option_name, $settings );

		$admin_settings = new \AspireUpdate\Admin_Settings();
		$admin_settings->reset_settings();

		unset( $_GET['reset'] );

		$this->assertSame( $settings, get_site_option( self::$option_name ) );
	}

	/**
	 * Test that settings are not reset when nonce verification fails.
	 */
	public function test_should_not_reset_settings_when_nonce_verification_fails() {
		$_GET['reset']       = 'reset';
		$_GET['reset-nonce'] = 'an_invalid_value';

		$settings = [ 'api_host' => 'the.option.value' ];
		update_site_option( self::$option_name, $settings );

		$admin_settings = new \AspireUpdate\Admin_Settings();
		$admin_settings->reset_settings();

		unset( $_GET['reset'], $_GET['reset-nonce'] );

		$this->assertSame( $settings, get_site_option( self::$option_name ) );
	}

	/**
	 * Test that a redirect is not performed when nonce verification fails.
	 */
	public function test_should_not_redirect_settings_when_nonce_verification_fails() {
		$_GET['reset']       = 'reset';
		$_GET['reset-nonce'] = 'an_invalid_value';

		$settings = [ 'api_host' => 'the.option.value' ];
		update_site_option( self::$option_name, $settings );

		$redirect = new MockAction();
		add_filter( 'wp_redirect', [ $redirect, 'filter' ] );

		$admin_settings = new \AspireUpdate\Admin_Settings();
		$admin_settings->reset_settings();

		unset( $_GET['reset'], $_GET['reset-nonce'] );

		$this->assertSame( 0, $redirect->get_call_count() );
	}

	/**
	 * Test that settings are reset when reset requirements are met.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_should_reset_settings_when_reset_requirements_are_met() {
		$_GET['reset']       = 'reset';
		$_GET['reset-nonce'] = wp_create_nonce( 'aspireupdate-reset-nonce' );

		$settings = [ 'api_host' => 'the.option.value' ];
		update_site_option( self::$option_name, $settings );

		$admin_settings = new \AspireUpdate\Admin_Settings();
		$admin_settings->reset_settings();

		unset( $_GET['reset'], $_GET['reset-nonce'] );

		$this->assertNotSame( $settings, get_site_option( self::$option_name ) );
	}

	/**
	 * Test that a redirect is performed when reset requirements are met.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_should_redirect_when_reset_requirements_are_met() {
		$_GET['reset']       = 'reset';
		$_GET['reset-nonce'] = wp_create_nonce( 'aspireupdate-reset-nonce' );

		$settings = [ 'api_host' => 'the.option.value' ];
		update_site_option( self::$option_name, $settings );

		$redirect = new MockAction();
		add_filter( 'wp_redirect', [ $redirect, 'filter' ] );

		$admin_settings = new \AspireUpdate\Admin_Settings();
		$admin_settings->reset_settings();

		unset( $_GET['reset'], $_GET['reset-nonce'] );

		$this->assertSame( 1, $redirect->get_call_count() );
	}
}
