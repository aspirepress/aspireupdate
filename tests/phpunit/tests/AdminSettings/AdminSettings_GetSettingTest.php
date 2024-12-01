<?php
/**
 * Class AdminSettings_GetSettingTest
 *
 * @package AspireUpdate
 */

/**
 * Tests for Admin_Settings::get_setting()
 *
 * These tests cause constants to be defined.
 * They must run in separate processes and must not preserve global state.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @covers \AspireUpdate\Admin_Settings::get_setting
 */
class AdminSettings_GetSettingTest extends AdminSettings_UnitTestCase {
	/**
	 * Test that the default 'api_host' value is retrieved.
	 *
	 * @covers \AspireUpdate\Admin_Settings::get_default_settings
	 */
	public function test_should_get_default_api_host() {
		$admin_settings = new \AspireUpdate\Admin_Settings();
		$actual         = $admin_settings->get_setting( 'api_host' );

		$this->assertIsString( $actual, 'The API host value is not a string.' );
		$this->assertNotEmpty( $actual, 'The API host value is empty. ' );
	}

	/**
	 * Test that 'api_host' is set to 'api_host_other' when provided.
	 */
	public function test_should_set_api_host_to_api_host_other() {
		$expected     = 'other.api.org';
		$new_settings = [
			'api_host'       => 'other',
			'api_host_other' => $expected,
		];

		update_site_option( self::$option_name, $new_settings );

		$this->assertSame(
			$new_settings,
			get_site_option( self::$option_name, false )
		);

		$admin_settings = new \AspireUpdate\Admin_Settings();

		$this->assertSame(
			$expected,
			$admin_settings->get_setting( 'api_host', false )
		);
	}

	/**
	 * Test that the wp-config.php constant value takes priority.
	 *
	 * @dataProvider data_different_values_for_options_and_constants
	 *
	 * @covers \AspireUpdate\Admin_Settings::get_settings_from_config_file
	 *
	 * @param string $option_name    The option's name.
	 * @param string $option_value   The option's value.
	 * @param string $constant_name  The constant's name.
	 * @param string $constant_value The constant's value.
	 */
	public function test_should_prioritize_constant_value( $option_name, $option_value, $constant_name, $constant_value ) {
		// Set the value in the database.
		update_option( 'aspireupdate_settings', [ $option_name => $option_value ] );

		// Set the constant. This should take priority.
		define( $constant_name, $constant_value );

		$admin_settings = new \AspireUpdate\Admin_Settings();
		$actual         = $admin_settings->get_setting( $option_name );

		$this->assertSame( $actual, $constant_value );
	}

	/**
	 * Data provider with different valeus for options and constants.
	 *
	 * @return array[]
	 */
	public function data_different_values_for_options_and_constants() {
		return [
			'AP_ENABLE'      => [
				'option_name'    => 'enable',
				'option_value'   => '0',
				'constant_name'  => 'AP_ENABLE',
				'constant_value' => '1',
			],
			'AP_HOST'        => [
				'option_name'    => 'api_host',
				'option_value'   => 'the.option.value',
				'constant_name'  => 'AP_HOST',
				'constant_value' => 'the.constant.value',
			],
			'AP_API_KEY'     => [
				'option_name'    => 'api_key',
				'option_value'   => 'tHeOpTiOnVaLuE',
				'constant_name'  => 'AP_API_KEY',
				'constant_value' => 'tHeCoNsTaNtVaLuE',
			],
			'AP_DEBUG'       => [
				'option_name'    => 'enable_debug',
				'option_value'   => '0',
				'constant_name'  => 'AP_DEBUG',
				'constant_value' => '1',
			],
			'AP_DEBUG_TYPES' => [
				'option_name'    => 'enable_debug_type',
				'option_value'   => [ 'the-option-value' ],
				'constant_name'  => 'AP_DEBUG_TYPES',
				'constant_value' => [ 'the-constant-value' ],
			],
			'AP_DISABLE_SSL' => [
				'option_name'    => 'disable_ssl_verification',
				'option_value'   => '0',
				'constant_name'  => 'AP_DISABLE_SSL',
				'constant_value' => '1',
			],
		];
	}
}
