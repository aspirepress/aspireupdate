<?php
/**
 * Class AdminSettings_SanitizeSettingsTest
 *
 * @package AspireUpdate
 */

/**
 * Tests for Admin_Settings::sanitize_settings()
 *
 * @covers \AspireUpdate\Admin_Settings::sanitize_settings
 */
class AdminSettings_SanitizeSettingsTest extends AdminSettings_UnitTestCase {
	/**
	 * Test that the setting is sanitized.
	 *
	 * @dataProvider data_enable_values_to_sanitize
	 * @dataProvider data_api_key_values_to_sanitize
	 * @dataProvider data_api_host_values_to_sanitize
	 * @dataProvider data_api_host_other_values_to_sanitize
	 * @dataProvider data_enable_debug_values_to_sanitize
	 * @dataProvider data_enable_debug_type_values_to_sanitize
	 * @dataProvider data_disable_ssl_verification_values_to_sanitize
	 *
	 * @param string $setting     The setting's name.
	 * @param mixed  $unsanitized The setting's unsanitized value.
	 * @param mixed  $expected    The setting's expected sanitized value.
	 */
	public function test_should_sanitize_setting( $setting, $unsanitized, $expected ) {
		$admin_settings = new AspireUpdate\Admin_Settings();
		$actual         = $admin_settings->sanitize_settings( [ $setting => $unsanitized ] );

		$this->assertIsArray(
			$actual,
			'The return value is not an array.'
		);

		$this->assertArrayHasKey(
			$setting,
			$actual,
			"The sanitized settings do not include {$setting}."
		);

		$this->assertSame(
			$expected,
			$actual[ $setting ],
			"The sanitized value for {$setting} does not match the expected value."
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_enable_values_to_sanitize() {
		return $this->get_int_datasets( 'enable' );
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_api_key_values_to_sanitize() {
		return $this->get_text_datasets( 'api_key' );
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_api_host_values_to_sanitize() {
		return $this->get_text_datasets( 'api_host' );
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_api_host_other_values_to_sanitize() {
		return $this->get_text_datasets( 'api_host_other' );
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_enable_debug_values_to_sanitize() {
		return $this->get_int_datasets( 'enable_debug' );
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_enable_debug_type_values_to_sanitize() {
		$text_datasets = $this->get_text_datasets( 'enable_debug_type' );

		$datasets = [];
		foreach ( $text_datasets as $name => $dataset ) {
			// Test as multi-element arrays.
			$dataset['unsanitized'] = [
				$dataset['unsanitized'],
				$dataset['unsanitized'],
			];
			$dataset['expected']    = [
				$dataset['expected'],
				$dataset['expected'],
			];

			$datasets[ $name ] = $dataset;
		}

		$datasets['enable_debug_type as (bool) false'] = [
			'setting'     => 'enable_debug_type',
			'unsanitized' => false,
			'expected'    => [],
		];

		$datasets['enable_debug_type as (bool) true'] = [
			'setting'     => 'enable_debug_type',
			'unsanitized' => true,
			'expected'    => [],
		];

		$datasets['enable_debug_type as NULL'] = [
			'setting'     => 'enable_debug_type',
			'unsanitized' => null,
			'expected'    => [],
		];

		$datasets['enable_debug_type as an empty string'] = [
			'setting'     => 'enable_debug_type',
			'unsanitized' => '',
			'expected'    => [],
		];

		$datasets['enable_debug_type as a non-empty string'] = [
			'setting'     => 'enable_debug_type',
			'unsanitized' => 'not empty',
			'expected'    => [],
		];

		$datasets['enable_debug_type as (int) 0'] = [
			'setting'     => 'enable_debug_type',
			'unsanitized' => 0,
			'expected'    => [],
		];

		$datasets['enable_debug_type as (int) 1'] = [
			'setting'     => 'enable_debug_type',
			'unsanitized' => 1,
			'expected'    => [],
		];

		$datasets['enable_debug_type as (int) -1'] = [
			'setting'     => 'enable_debug_type',
			'unsanitized' => -1,
			'expected'    => [],
		];

		$datasets['enable_debug_type as (float) 0.0'] = [
			'setting'     => 'enable_debug_type',
			'unsanitized' => 0.0,
			'expected'    => [],
		];

		$datasets['enable_debug_type as (float) 1.0'] = [
			'setting'     => 'enable_debug_type',
			'unsanitized' => 1.0,
			'expected'    => [],
		];

		$datasets['enable_debug_type as (float) -1.0'] = [
			'setting'     => 'enable_debug_type',
			'unsanitized' => -1.0,
			'expected'    => [],
		];

		$datasets['enable_debug_type as (float) -0.0'] = [
			'setting'     => 'enable_debug_type',
			'unsanitized' => -0.0,
			'expected'    => [],
		];

		$datasets['enable_debug_type as INF'] = [
			'setting'     => 'enable_debug_type',
			'unsanitized' => INF,
			'expected'    => [],
		];

		$datasets['enable_debug_type as NAN'] = [
			'setting'     => 'enable_debug_type',
			'unsanitized' => NAN,
			'expected'    => [],
		];

		$datasets['enable_debug_type as an empty object'] = [
			'setting'     => 'enable_debug_type',
			'unsanitized' => new stdClass(),
			'expected'    => [],
		];

		$datasets['enable_debug_type as a non-empty object'] = [
			'setting'     => 'enable_debug_type',
			'unsanitized' => new stdClass(),
			'expected'    => [],
		];

		return $datasets;
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_disable_ssl_verification_values_to_sanitize() {
		return $this->get_int_datasets( 'disable_ssl_verification' );
	}

	/**
	 * Gets the datasets for integer values.
	 *
	 * @param string $setting The name of the setting.
	 * @return array[] Datasets.
	 */
	private function get_int_datasets( $setting ) {
		return [
			// One.
			"{$setting} as (bool) true"        => [
				'setting'     => $setting,
				'unsanitized' => true,
				'expected'    => 1,
			],
			"{$setting} as a non-empty string" => [
				'setting'     => $setting,
				'unsanitized' => ' ',
				'expected'    => 1,
			],
			"{$setting} as (string) '1'"       => [
				'setting'     => $setting,
				'unsanitized' => '1',
				'expected'    => 1,
			],
			"{$setting} as (float) 1.0"        => [
				'setting'     => $setting,
				'unsanitized' => 1.0,
				'expected'    => 1,
			],
			"{$setting} as (float) -1.0"       => [
				'setting'     => $setting,
				'unsanitized' => -1.0,
				'expected'    => 1,
			],
			"{$setting} as a populated array"  => [
				'setting'     => $setting,
				'unsanitized' => [ 0 ],
				'expected'    => 1,
			],
			"{$setting} as a populated object" => [
				'setting'     => $setting,
				'unsanitized' => (object) [ $setting => 0 ],
				'expected'    => 1,
			],
			"{$setting} as an empty object"    => [
				'setting'     => $setting,
				'unsanitized' => new stdClass(),
				'expected'    => 1,
			],
			"{$setting} as INF"                => [
				'setting'     => $setting,
				'unsanitized' => INF,
				'expected'    => 1,
			],
			"{$setting} as NAN"                => [
				'setting'     => $setting,
				'unsanitized' => NAN,
				'expected'    => 1,
			],

			// Zero.
			"{$setting} as (bool) false"       => [
				'setting'     => $setting,
				'unsanitized' => false,
				'expected'    => 0,
			],
			"{$setting} as an empty string"    => [
				'setting'     => $setting,
				'unsanitized' => '',
				'expected'    => 0,
			],
			"{$setting} as NULL"               => [
				'setting'     => $setting,
				'unsanitized' => null,
				'expected'    => 0,
			],
			"{$setting} as (string) '0'"       => [
				'setting'     => $setting,
				'unsanitized' => '0',
				'expected'    => 0,
			],
			"{$setting} as (float) 0.0"        => [
				'setting'     => $setting,
				'unsanitized' => 0.0,
				'expected'    => 0,
			],
			"{$setting} as (float) -0.0"       => [
				'setting'     => $setting,
				'unsanitized' => -0.0,
				'expected'    => 0,
			],
			"{$setting} as an empty array"     => [
				'setting'     => $setting,
				'unsanitized' => [],
				'expected'    => 0,
			],
		];
	}

	/**
	 * Gets the datasets for text values.
	 *
	 * @param string $setting The name of the setting.
	 * @return array[] Datasets.
	 */
	public function get_text_datasets( $setting ) {
		return [
			// As-is.
			"{$setting} with single quotes"              => [
				'setting'     => $setting,
				'unsanitized' => "'value'",
				'expected'    => "'value'",
			],
			"{$setting} with double quotes"              => [
				'setting'     => $setting,
				'unsanitized' => '"value"',
				'expected'    => '"value"',
			],
			"{$setting} with HTTPS protocol"             => [
				'setting'     => $setting,
				'unsanitized' => 'https://www.example.org',
				'expected'    => 'https://www.example.org',
			],

			// Changed.
			"{$setting} with HTML tags"                  => [
				'setting'     => $setting,
				'unsanitized' => '<p><strong>value</strong></p>',
				'expected'    => 'value',
			],
			"{$setting} with percent-encoded characters" => [
				'setting'     => $setting,
				'unsanitized' => '%3A%2F%3F%23v%5B%5D%40%21a%24%26%27%28l%29%2A%2B%2Cu%3B%3D%25%20e',
				'expected'    => 'value',
			],

			// Empty.
			"{$setting} as an empty string"              => [
				'setting'     => $setting,
				'unsanitized' => '',
				'expected'    => '',
			],
			"{$setting} as an array"                     => [
				'setting'     => $setting,
				'unsanitized' => [],
				'expected'    => '',
			],
			"{$setting} as an object"                    => [
				'setting'     => $setting,
				'unsanitized' => new stdClass(),
				'expected'    => '',
			],
			"{$setting} as a string with only spaces"    => [
				'setting'     => $setting,
				'unsanitized' => "\r\n\t ",
				'expected'    => '',
			],
			"{$setting} with JavaScript"                 => [
				'setting'     => $setting,
				'unsanitized' => '<script>alert("Hello, World!")</script>',
				'expected'    => '',
			],
		];
	}
}
