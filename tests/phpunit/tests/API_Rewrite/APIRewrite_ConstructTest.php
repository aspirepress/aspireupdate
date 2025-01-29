<?php
/**
 * Class APIRewrite_ConstructTest
 *
 * @package AspireUpdate
 */

/**
 * Tests for API_Rewrite::__construct()
 *
 * @covers \AspireUpdate\API_Rewrite::__construct
 */
class APIRewrite_ConstructTest extends WP_UnitTestCase {
	/**
	 * Test that hooks are added.
	 *
	 * @dataProvider data_hooks_and_methods
	 *
	 * @string $hook   The hook's name.
	 * @string $method The method to hook.
	 */
	public function test_should_add_hooks( $hook, $method ) {
		$api_rewrite = new AspireUpdate\API_Rewrite( 'debug', false, '' );
		$this->assertIsInt( has_action( $hook, [ $api_rewrite, $method ] ) );
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_hooks_and_methods() {
		return [
			'pre_http_request -> pre_http_request' => [
				'hook'   => 'pre_http_request',
				'method' => 'pre_http_request',
			],
		];
	}

	/**
	 * Test that properties are set to the expected value.
	 *
	 * @dataProvider data_properties_and_values
	 *
	 * @param string $property_name  The property's name.
	 * @param string $passed_value   The value passed to the constructor.
	 * @param string $expected_value The expected stored value after processing.
	 */
	public function test_should_set_properties( $property_name, $passed_value, $expected_value ) {
		$api_rewrite = new AspireUpdate\API_Rewrite(
			'redirected_host' === $property_name ? $passed_value : 'debug',
			'disable_ssl' === $property_name ? $passed_value : false,
			''
		);

		if ( '%DEFAULT_HOST%' === $expected_value ) {
			static $default_host_value;

			if ( ! $default_host_value ) {
				$api_rewrite_reflection = new ReflectionClass( 'AspireUpdate\API_Rewrite' );
				$default_host_value     = $api_rewrite_reflection->getDefaultProperties()['default_host'];
			}

			$expected_value = $default_host_value;
		}

		$property = new ReflectionProperty( $api_rewrite, $property_name );
		$property->setAccessible( true );
		$actual_value = $property->getValue( $api_rewrite );
		$property->setAccessible( false );

		$this->assertSame( $expected_value, $actual_value );
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_properties_and_values() {
		return [
			'redirected_host as "debug" (should be the default host)' => [
				'property_name'  => 'redirected_host',
				'passed_value'   => 'debug',
				'expected_value' => '%DEFAULT_HOST%',
			],
			'redirected_host in mixed case (should convert to lowercase)' => [
				'property_name'  => 'redirected_host',
				'passed_value'   => 'mY.aPi.OrG',
				'expected_value' => 'my.api.org',
			],
			'malformed redirected_host (should be stored as-is)' => [
				'property_name'  => 'redirected_host',
				'passed_value'   => 'my#api..org/https://',
				'expected_value' => 'my#api..org/https://',
			],
			'disable_ssl as (bool) true'  => [
				'property_name'  => 'disable_ssl',
				'passed_value'   => true,
				'expected_value' => true,
			],
			'disable_ssl as (bool) false' => [
				'property_name'  => 'disable_ssl',
				'passed_value'   => false,
				'expected_value' => false,
			],
		];
	}
}
