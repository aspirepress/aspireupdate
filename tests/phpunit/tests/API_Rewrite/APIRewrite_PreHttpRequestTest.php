<?php
/**
 * Class APIRewrite_PreHttpRequestTest
 *
 * @package AspireUpdate
 */

/**
 * Tests for API_Rewrite::pre_http_request()
 *
 * @covers \AspireUpdate\API_Rewrite::pre_http_request
 */
class APIRewrite_PreHttpRequestTest extends WP_UnitTestCase {
	/**
	 * Original theme directory.
	 *
	 * @var string
	 */
	private static $orig_theme_dir;

	/**
	 * Set up the test theme directory before any tests run.
	 *
	 * @return void
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		self::$orig_theme_dir            = $GLOBALS['wp_theme_directories'];
		$GLOBALS['wp_theme_directories'] = [ realpath( dirname( __DIR__, 2 ) . '/data/themes' ) ];

		wp_clean_themes_cache();
		unset( $GLOBALS['wp_themes'] );
	}

	/**
	 * Restore the original theme directory after all tests run.
	 *
	 * @return void
	 */
	public static function tear_down_after_class() {
		$GLOBALS['wp_theme_directories'] = self::$orig_theme_dir;

		wp_clean_themes_cache();
		unset( $GLOBALS['wp_themes'] );

		parent::tear_down_after_class();
	}

	/**
	 * Test that no request is performed when the redirected host is an empty string.
	 */
	public function test_should_not_perform_request_when_redirected_host_is_an_empty_string() {
		$request = new MockAction();
		add_filter( 'pre_http_request', [ $request, 'filter' ] );

		$api_rewrite = new AspireUpdate\API_Rewrite( '', false, '' );
		$api_rewrite->pre_http_request( false, [], '' );

		$this->assertSame( 0, $request->get_call_count() );
	}

	/**
	 * Test that no request is performed when the default host and redirected host are the same.
	 */
	public function test_should_not_perform_request_when_default_host_and_redirected_host_are_the_same() {
		$request = new MockAction();
		add_filter( 'pre_http_request', [ $request, 'filter' ] );

		$default_host = $this->get_default_host();
		$api_rewrite  = new AspireUpdate\API_Rewrite( $default_host, false, '' );

		$api_rewrite->pre_http_request( false, [], $default_host );

		$this->assertSame( 0, $request->get_call_count() );
	}

	/**
	 * Test that the request's original SSL verification is respected when SSL is not forcibly disabled.
	 */
	public function test_should_respect_the_original_ssl_verification_when_ssl_is_not_forcibly_disabled() {
		$actual = '';

		add_filter(
			'pre_http_request',
			static function ( $response, $parsed_args ) use ( &$actual ) {
				$actual = $parsed_args['sslverify'];
				return $response;
			},
			PHP_INT_MAX,
			2
		);

		$api_rewrite = new AspireUpdate\API_Rewrite( 'https://my.api.org', false, '' );
		$api_rewrite->pre_http_request(
			false,
			[ 'sslverify' => 'original_sslverify_value' ],
			'https://' . $this->get_default_host()
		);

		$this->assertSame( 'original_sslverify_value', $actual );
	}

	/**
	 * Test that disabling SSL is respected.
	 */
	public function test_should_respect_disabling_ssl() {
		$actual = '';

		add_filter(
			'pre_http_request',
			static function ( $response, $parsed_args ) use ( &$actual ) {
				$actual = $parsed_args['sslverify'];
				return $response;
			},
			PHP_INT_MAX,
			2
		);

		$api_rewrite = new AspireUpdate\API_Rewrite( 'https://my.api.org', true, '' );
		$api_rewrite->pre_http_request(
			false,
			[ 'sslverify' => true ],
			'https://' . $this->get_default_host()
		);

		$this->assertFalse( $actual );
	}

	/**
	 * Test that the default host is replaced with the redirected host.
	 */
	public function test_should_replace_default_host_with_redirected_host() {
		$actual = '';

		add_filter(
			'pre_http_request',
			static function ( $response, $parsed_args, $url ) use ( &$actual ) {
				$actual = $url;
				return $response;
			},
			PHP_INT_MAX,
			3
		);

		$api_rewrite = new AspireUpdate\API_Rewrite( 'https://my.api.org', true, '' );
		$api_rewrite->pre_http_request( false, [], 'https://' . $this->get_default_host() );

		$this->assertMatchesRegularExpression( '/my\.api\.org/', $actual );
	}

	/**
	 * Test that cache buster is added to URL when AP_DEBUG_BYPASS_CACHE is true.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @covers \AspireUpdate\API_Rewrite::add_cache_buster
	 */
	public function test_should_add_cache_buster_when_debug_bypass_cache_is_true() {
		define( 'AP_DEBUG_BYPASS_CACHE', true );

		$actual = '';

		add_filter(
			'pre_http_request',
			static function ( $response, $parsed_args, $url ) use ( &$actual ) {
				$actual = $url;
				return $response;
			},
			PHP_INT_MAX,
			3
		);

		$api_rewrite = new AspireUpdate\API_Rewrite( 'https://my.api.org', true, '' );
		$api_rewrite->pre_http_request( false, [], 'https://' . $this->get_default_host() );

		$this->assertMatchesRegularExpression( '/\?cache_buster=[0-9]+/', $actual );
	}

	/**
	 * Test that cache buster is _not_ added to URL when AP_DEBUG_BYPASS_CACHE is false.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_should_not_add_cache_buster_when_debug_bypass_cache_is_false() {
		define( 'AP_DEBUG_BYPASS_CACHE', false );

		$actual = '';

		add_filter(
			'pre_http_request',
			static function ( $response, $parsed_args, $url ) use ( &$actual ) {
				$actual = $url;
				return $response;
			},
			PHP_INT_MAX,
			3
		);

		$api_rewrite = new AspireUpdate\API_Rewrite( 'https://my.api.org', true, '' );
		$api_rewrite->pre_http_request( false, [], 'https://' . $this->get_default_host() );

		$this->assertStringNotContainsString( 'cache_buster=', $actual );
	}

	/**
	 * Test that the API Key is added to the Authorization header.
	 *
	 * @covers \AspireUpdate\API_Rewrite::add_authorization_header
	 */
	public function test_should_add_api_key_to_authorization_header_when_present() {
		$actual = [];

		add_filter(
			'pre_http_request',
			static function ( $response, $parsed_args ) use ( &$actual ) {
				$actual = $parsed_args;
				return $response;
			},
			10,
			2
		);

		$api_key     = 'MY_API_KEY';
		$api_rewrite = new AspireUpdate\API_Rewrite( 'https://my.api.org', true, $api_key );
		$api_rewrite->pre_http_request( false, [], 'https://' . $this->get_default_host() );

		$this->assertIsArray(
			$actual,
			'Parsed arguments is not an array.'
		);

		$this->assertArrayHasKey(
			'headers',
			$actual,
			'The "headers" key is not present.'
		);

		$this->assertIsArray(
			$actual['headers'],
			'The "headers" value is not an array.'
		);

		$this->assertArrayHasKey(
			'Authorization',
			$actual['headers'],
			'There is no authorization header.'
		);

		$this->assertIsString(
			$actual['headers']['Authorization'],
			'The authorization header is not a string.'
		);

		$this->assertSame(
			"Bearer $api_key",
			$actual['headers']['Authorization'],
			'The authorization header is wrong.'
		);
	}

	/**
	 * Test that the Accept header is added for JSON.
	 *
	 * @dataProvider data_accept_json_paths
	 *
	 * @covers \AspireUpdate\API_Rewrite::add_accept_json_header
	 *
	 * @param string $path The path to add to the URL.
	 */
	public function test_should_add_accept_header_with_json( $path ) {
		$actual = [];

		add_filter(
			'pre_http_request',
			static function ( $response, $parsed_args ) use ( &$actual ) {
				$actual = $parsed_args;
				return $response;
			},
			10,
			2
		);

		$api_rewrite = new AspireUpdate\API_Rewrite( 'https://my.api.org', true, 'MY_API_KEY' );
		$api_rewrite->pre_http_request(
			false,
			[],
			untrailingslashit( 'https://' . $this->get_default_host() ) . $path
		);

		$this->assertIsArray(
			$actual,
			'Parsed arguments is not an array.'
		);

		$this->assertArrayHasKey(
			'headers',
			$actual,
			'The "headers" key is not present.'
		);

		$this->assertIsArray(
			$actual['headers'],
			'The "headers" value is not an array.'
		);

		$this->assertArrayHasKey(
			'Accept',
			$actual['headers'],
			'There is no accept header.'
		);

		$this->assertIsString(
			$actual['headers']['Accept'],
			'The accept header is not a string.'
		);

		$this->assertSame(
			'application/json',
			$actual['headers']['Accept'],
			'The accept header is wrong.'
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_accept_json_paths() {
		return [
			'a PHP file'                                  => [
				'path' => '/files/file.php',
			],
			'a PHP file with a period'                    => [
				'path' => '/files/file.file.php',
			],
			'no extension, a period and a trailing slash' => [
				'path' => '/files/file.file/',
			],
			'no extension and a trailing slash'           => [
				'path' => '/files/file/',
			],
		];
	}

	/**
	 * Test that the Accept header is not added for JSON when a non-PHP file
	 * is requested.
	 *
	 * @dataProvider data_do_not_accept_json_paths
	 *
	 * @covers \AspireUpdate\API_Rewrite::add_accept_json_header
	 *
	 * @param string $path The path to add to the URL.
	 */
	public function test_should_not_add_accept_header_with_json( $path ) {
		$actual = [];

		add_filter(
			'pre_http_request',
			static function ( $response, $parsed_args ) use ( &$actual ) {
				$actual = $parsed_args;
				return $response;
			},
			10,
			2
		);

		$api_rewrite = new AspireUpdate\API_Rewrite( 'https://my.api.org', true, 'MY_API_KEY' );
		$api_rewrite->pre_http_request(
			false,
			[],
			'https://' . $this->get_default_host() . $path
		);

		$this->assertIsArray(
			$actual,
			'Parsed arguments is not an array.'
		);

		$this->assertArrayHasKey(
			'headers',
			$actual,
			'The "headers" key is not present.'
		);

		$this->assertIsArray(
			$actual['headers'],
			'The "headers" value is not an array.'
		);

		$this->assertArrayNotHasKey(
			'Accept',
			$actual['headers'],
			'There should not be an accept header.'
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_do_not_accept_json_paths() {
		return [
			'a ZIP extension'   => [
				'path' => '/file.zip',
			],
			'an HTML extension' => [
				'path' => '/index.html',
			],
			'a PDF extension'   => [
				'path' => '/file.pdf',
			],
			'a JPG extension'   => [
				'path' => '/file.jpg',
			],
			'a PNG extension'   => [
				'path' => '/file.png',
			],
			'a GIF extension'   => [
				'path' => '/file.gif',
			],
			'a WEBP extension'  => [
				'path' => '/file.webp',
			],
			'a WEBM extension'  => [
				'path' => '/file.webm',
			],
			'no trailing slash' => [
				'path' => '/file',
			],
		];
	}

	/**
	 * Test that the headers array is created if it does not already exist when adding
	 * the Accept header.
	 *
	 * @covers \AspireUpdate\API_Rewrite::add_accept_json_header
	 */
	public function test_should_create_headers_array_if_it_does_not_already_exist_when_adding_the_accept_header() {
		$actual = [];

		add_filter(
			'pre_http_request',
			static function ( $response, $parsed_args ) use ( &$actual ) {
				$actual = $parsed_args;
				return $response;
			},
			10,
			2
		);

		// No API key ensures no Authorization header will be already set.
		$api_rewrite = new AspireUpdate\API_Rewrite( 'https://my.api.org', true, '' );
		$api_rewrite->pre_http_request(
			false,
			[],
			'https://' . $this->get_default_host() . '/file.php'
		);

		$this->assertIsArray(
			$actual,
			'Parsed arguments is not an array.'
		);

		$this->assertArrayHasKey(
			'headers',
			$actual,
			'The "headers" key is not present.'
		);

		$this->assertIsArray(
			$actual['headers'],
			'The "headers" value is not an array.'
		);
	}

	/**
	 * Test that non-API assets are removed from update check requests.
	 *
	 * @dataProvider data_api_and_non_api_assets
	 *
	 * @covers \AspireUpdate\API_Rewrite::get_request_type
	 * @covers \AspireUpdate\API_Rewrite::get_asset_type
	 * @covers \AspireUpdate\API_Rewrite::get_non_api_assets
	 *
	 * @param string $url      The URL to test.
	 * @param array  $plugins  An array of test plugin headers, keyed on each plugin's filepath relative to the plugin's directory.
	 * @param array  $themes   An array of test theme headers, keyed on each theme's slug.
	 * @param array  $expected The keys of the expected assets to be sent in the request after removing non-API assets.
	 */
	public function test_should_remove_non_api_assets_from_update_request( $url, $plugins, $themes, $expected ) {
		wp_cache_set( 'plugins', [ '' => $plugins ], 'plugins' );

		$arguments = [];
		add_filter(
			'pre_http_request',
			static function ( $url, $parsed_args ) use ( &$arguments ) {
				$arguments = $parsed_args;
				return $url;
			},
			10,
			2
		);

		$api_rewrite = new AspireUpdate\API_Rewrite( 'https://my.api.org', false, '' );
		$api_rewrite->pre_http_request(
			false,
			[
				'body' => [
					'plugins' => wp_json_encode( [ 'plugins' => $plugins ] ),
					'themes'  => wp_json_encode( [ 'themes' => $themes ] ),
				],
			],
			'https://' . $this->get_default_host() . $url
		);

		wp_cache_delete( 'plugins', 'plugins' );

		// Check the outer shape of the request's arguments value.
		$this->assertIsArray( $arguments, "The request's arguments are not an array." );
		$this->assertArrayHasKey( 'body', $arguments, "There is no 'body' key in the request's arguments." );
		$this->assertIsArray( $arguments['body'], "The request's 'body' value is not an array." );

		// Check that the 'body' value has asset keys.
		$this->assertArrayHasKey( 'plugins', $arguments['body'], "The request's body does not have a 'plugins' key." );
		$this->assertArrayHasKey( 'themes', $arguments['body'], "The request's body does not have a 'themes' key." );

		// Check that the 'plugins' and 'themes' values are JSON-encoded arrays.
		$this->assertIsString( $arguments['body']['plugins'], "The request's 'plugins' value is not a string." );
		$this->assertIsString( $arguments['body']['themes'], "The request's 'themes' value is not a string." );

		$plugins = json_decode( $arguments['body']['plugins'], true );
		$themes  = json_decode( $arguments['body']['themes'], true );

		$this->assertIsArray( $plugins, "The request's 'plugins' value did not decode to an array." );
		$this->assertIsArray( $themes, "The request's 'themes' value did not decode to an array." );

		// Check that the 'plugins' and 'themes' arrays have 'plugins' and 'themes' sub-keys which are arrays.
		$this->assertArrayHasKey( 'plugins', $plugins, "The request's 'plugins' array does not have a 'plugins' key." );
		$this->assertArrayHasKey( 'themes', $themes, "The request's 'themes' array does not have a 'themes' key." );
		$this->assertIsArray( $plugins['plugins'], "The request's 'plugins' array's 'plugins' sub-value is not an array." );
		$this->assertIsArray( $themes['themes'], "The request's 'themes' array's 'themes' sub-value is not an array." );

		// Check that the values of the 'plugins' and 'themes' sub-arrays are as expected.
		$this->assertSame(
			$expected['plugins'],
			array_keys( $plugins['plugins'] ),
			"The request's 'plugins' array's 'plugins' sub-value contains an unexpected list of plugins."
		);

		$this->assertSame(
			$expected['themes'],
			array_keys( $themes['themes'] ),
			"The request's 'themes' array's 'themes' sub-value contains an unexpected list of themes."
		);
	}

	/**
	 * Test that non-API assets are not removed from non-update requests.
	 *
	 * @dataProvider data_api_and_non_api_assets
	 *
	 * @param string $url      The URL to test.
	 * @param array  $plugins  An array of test plugin headers, keyed on each plugin's filepath relative to the plugin's directory.
	 * @param array  $themes   An array of test theme headers, keyed on each theme's slug.
	 * @param array  $expected The keys of the expected assets to be left in the response after removing non-API assets.
	 */
	public function test_should_not_remove_assets_for_non_update_requests( $url, $plugins, $themes, $expected ) {
		$body = wp_json_encode(
			[
				'plugins' => [ 'plugins' => $plugins ],
				'themes'  => [ 'themes' => $themes ],
			]
		);

		add_filter(
			'pre_http_request',
			static function () use ( $body ) {
				return [
					'body'     => $body,
					'response' => [
						'code' => 200,
					],
				];
			}
		);

		wp_cache_set( 'plugins', [ '' => $plugins ], 'plugins' );

		$api_rewrite = new AspireUpdate\API_Rewrite( 'https://my.api.org', false, '' );
		$actual      = $api_rewrite->pre_http_request(
			false,
			[ 'body' => $body ],
			'https://' . $this->get_default_host() . '/plugins/info/1.0/'
		);

		wp_cache_delete( 'plugins', 'plugins' );

		// Check the outer shape of the response's value.
		$this->assertIsArray( $actual, 'The response is not an array.' );
		$this->assertArrayHasKey( 'body', $actual, "The response array has no 'body' key." );

		$this->assertSame( $body, $actual['body'], "The request's 'body' was changed unexpectedly." );
	}

	/**
	 * Test that non-API assets are removed from update check responses.
	 *
	 * @dataProvider data_api_and_non_api_assets
	 *
	 * @covers \AspireUpdate\API_Rewrite::get_request_type
	 * @covers \AspireUpdate\API_Rewrite::get_asset_type
	 * @covers \AspireUpdate\API_Rewrite::get_non_api_assets
	 *
	 * @param string $url      The URL to test.
	 * @param array  $plugins  An array of test plugin headers, keyed on each plugin's filepath relative to the plugin's directory.
	 * @param array  $themes   An array of test theme headers, keyed on each theme's slug.
	 * @param array  $expected The keys of the expected assets to be left in the response after removing non-API assets.
	 */
	public function test_should_remove_non_api_assets_from_update_response( $url, $plugins, $themes, $expected ) {
		add_filter(
			'pre_http_request',
			static function () use ( $plugins, $themes ) {
				return [
					'body'     => wp_json_encode(
						[
							'plugins' => $plugins,
							'themes'  => $themes,
						]
					),
					'response' => [
						'code' => 200,
					],
				];
			}
		);

		wp_cache_set( 'plugins', [ '' => $plugins ], 'plugins' );
		$api_rewrite = new AspireUpdate\API_Rewrite( 'https://my.api.org', false, '' );
		$actual      = $api_rewrite->pre_http_request(
			false,
			[
				'body' => [
					'plugins' => wp_json_encode( [ 'plugins' => $plugins ] ),
					'themes'  => wp_json_encode( [ 'themes' => $themes ] ),
				],
			],
			'https://' . $this->get_default_host() . $url
		);

		wp_cache_delete( 'plugins', 'plugins' );

		// Check the outer shape of the response's value.
		$this->assertIsArray( $actual, 'The response is not an array.' );
		$this->assertArrayHasKey( 'body', $actual, "The response array has no 'body' key." );
		$this->assertIsString( $actual['body'], "The response's 'body' value is not a string." );

		$body = json_decode( $actual['body'], true );
		$this->assertIsArray( $body, "The response's 'body' value did not decode to an array." );

		// Check that the 'body' value has asset keys whose values decode to arrays.
		$this->assertArrayHasKey( 'plugins', $body, "The response's 'body' value does not have a 'plugins' key." );
		$this->assertArrayHasKey( 'themes', $body, "The response's 'body' value does not have a 'themes' key." );
		$this->assertIsArray( $body['plugins'], "The response's 'plugins' value did not decode to an array." );
		$this->assertIsArray( $body['themes'], "The response's 'themes' value did not decode to an array." );

		// Check that the values of the 'plugins' and 'themes' are as expected.
		$this->assertSame(
			$expected['plugins'],
			array_keys( $body['plugins'] ),
			"The response's 'plugins' value contains an unexpected list of plugins."
		);

		$this->assertSame(
			$expected['themes'],
			array_keys( $body['themes'] ),
			"The response's 'themes' value contains an unexpected list of themes."
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_api_and_non_api_assets() {
		return [
			'a plugins update check with a mixture of non-API plugins, API plugins, non-API themes, and API themes' => [
				'url'      => '/plugins/update-check/1.0/',
				'plugins'  => [
					'non-api-plugin/non-api-plugin.php' => [
						'Name'      => 'Non-API Plugin',
						'UpdateURI' => 'https://another.api.org',
					],
					'api-plugin/api-plugin.php'         => [
						'Name' => 'API Plugin',
					],
					'non-api-plugin-2/non-api-plugin-2.php' => [
						'Name'      => 'Non-API Plugin 2',
						'UpdateURI' => 'https://yet-another.api.org',
					],
				],
				'themes'   => [
					'api-block-theme'       => [
						'Name' => 'API Block Theme',
					],
					'api-classic-theme'     => [
						'Name' => 'API Classic Theme',
					],
					'non-api-block-theme'   => [
						'Name'      => 'Non-API Block Theme',
						'UpdateURI' => 'https://another.api.org',
					],
					'non-api-classic-theme' => [
						'Name'      => 'Non-API Classic Theme',
						'UpdateURI' => 'https://yet-another.api.org',
					],
				],
				'expected' => [
					'plugins' => [
						'api-plugin/api-plugin.php',
					],
					'themes'  => [
						'api-block-theme',
						'api-classic-theme',
						'non-api-block-theme',
						'non-api-classic-theme',
					],
				],
			],
			'a themes update check with a mixture of non-API plugins, API plugins, non-API themes, and API themes' => [
				'url'      => '/themes/update-check/1.0/',
				'plugins'  => [
					'non-api-plugin/non-api-plugin.php' => [
						'Name'      => 'Non-API Plugin',
						'UpdateURI' => 'https://another.api.org',
					],
					'api-plugin/api-plugin.php'         => [
						'Name' => 'API Plugin',
					],
					'non-api-plugin-2/non-api-plugin-2.php' => [
						'Name'      => 'Non-API Plugin 2',
						'UpdateURI' => 'https://yet-another.api.org',
					],
				],
				'themes'   => [
					'api-block-theme'       => [
						'Name' => 'API Block Theme',
					],
					'api-classic-theme'     => [
						'Name' => 'API Classic Theme',
					],
					'non-api-block-theme'   => [
						'Name'      => 'Non-API Block Theme',
						'UpdateURI' => 'https://another.api.org',
					],
					'non-api-classic-theme' => [
						'Name'      => 'Non-API Classic Theme',
						'UpdateURI' => 'https://yet-another.api.org',
					],
				],
				'expected' => [
					'plugins' => [
						'non-api-plugin/non-api-plugin.php',
						'api-plugin/api-plugin.php',
						'non-api-plugin-2/non-api-plugin-2.php',
					],
					'themes'  => [
						'api-block-theme',
						'api-classic-theme',
					],
				],
			],
		];
	}

	/**
	 * Test that AspireUpdate is removed from information responses.
	 *
	 * @covers \AspireUpdate\API_Rewrite::get_request_type
	 * @covers \AspireUpdate\API_Rewrite::get_asset_type
	 */
	public function test_should_remove_aspireupdate_from_info_response() {
		add_filter(
			'pre_http_request',
			static function () {
				return [
					'body'     => wp_json_encode(
						[
							'plugins' => [
								'akismet/akismet.php'   => [
									'name' => 'Akismet',
									'slug' => 'akismet',
								],
								'aspireupdate/aspire-update.php' => [
									'name' => 'AspireUpdate',
									'slug' => 'aspireupdate',
								],
								'hello-dolly/hello.php' => [
									'name' => 'Hello Dolly',
									'slug' => 'hello-dolly',
								],
								'fakeaspireupdate/fake-aspire-update.php' => [
									'name' => 'Fake AspireUpdate',
									'slug' => 'aspireupdate',
								],
							],
						]
					),
					'response' => [
						'code' => 200,
					],
				];
			}
		);

		$api_rewrite = new AspireUpdate\API_Rewrite( 'https://my.api.org', false, '' );
		$response    = $api_rewrite->pre_http_request(
			false,
			[],
			'https://' . $this->get_default_host() . '/plugins/info/1.0/'
		);

		// Check the outer shape of the response's value.
		$this->assertIsArray( $response, 'The response is not an array.' );
		$this->assertArrayHasKey( 'body', $response, "The response array has no 'body' key." );
		$this->assertIsString( $response['body'], "The response's 'body' value is not a string." );

		$body = json_decode( $response['body'], true );
		$this->assertIsArray( $body, "The response's 'body' value did not decode to an array." );

		// Check that the 'body' value has a 'plugins' key whose value decodes to an array.
		$this->assertArrayHasKey( 'plugins', $body, "The response's 'body' value does not have a 'plugins' key." );
		$this->assertIsArray( $body['plugins'], "The response's 'plugins' value is not an array." );

		// Check that the canonical AspireUpdate is not included.
		$this->assertNotContains(
			'aspireupdate/aspire-update.php',
			array_keys( $body['plugins'] ),
			"The response's 'plugins' value contains AspireUpdate."
		);

		// Check that a fake AspireUpdate using the same slug is not included.
		$this->assertNotContains(
			'fakeaspireupdate/fake-aspire-update.php',
			array_keys( $body['plugins'] ),
			"The response's 'plugins' value contains a fake AspireUpdate."
		);

		// Check that non-AspireUpdate plugins are included.
		$this->assertSame(
			[ 'akismet/akismet.php', 'hello-dolly/hello.php' ],
			array_keys( $body['plugins'] ),
			"The response's 'plugins' value contains an unexpected list of plugins."
		);
	}

	/**
	 * Test that a WP_Error object is not returned for some response codes.
	 *
	 * @dataProvider data_response_codes_that_should_not_error
	 *
	 * @param int $response_code The response code.
	 */
	public function test_should_not_return_wp_error_for_some_response_codes( $response_code ) {
		add_filter(
			'pre_http_request',
			static function () use ( $response_code ) {
				return [
					'response' => [
						'code'    => $response_code,
						'message' => 'Test response code',
					],
				];
			},
			10,
			2
		);

		$api_rewrite = new AspireUpdate\API_Rewrite( 'https://my.api.org', true, '' );
		$actual      = $api_rewrite->pre_http_request(
			false,
			[],
			$this->get_default_host() . '/file.php'
		);

		$this->assertNotInstanceOf(
			'WP_Error',
			$actual,
			'A WP_Error object was returned.'
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_response_codes_that_should_not_error() {
		return [
			'200' => [
				'response_code' => 200,
			],
			'404' => [
				'response_code' => 404,
			],
		];
	}

	/**
	 * Test that a WP_Error object is returned for other HTTP response codes.
	 *
	 * @dataProvider data_response_codes_that_should_error
	 *
	 * @param int $response_code The response code.
	 */
	public function test_should_return_wp_error_for_other_response_codes( $response_code ) {
		add_filter(
			'pre_http_request',
			static function () {
				return [
					'response' => [
						'code'    => 401,
						'message' => 'Unauthorized.',
					],
				];
			},
			10,
			2
		);

		$api_rewrite = new AspireUpdate\API_Rewrite( 'https://my.api.org', true, '' );
		$actual      = $api_rewrite->pre_http_request(
			false,
			[],
			'https://' . $this->get_default_host() . '/file.php'
		);

		$this->assertInstanceOf(
			'WP_Error',
			$actual,
			'A WP_Error object was not returned.'
		);

		$this->assertSame(
			'failed_request',
			$actual->get_error_code(),
			'The wrong error code was returned.'
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_response_codes_that_should_error() {
		$datasets = [];

		$exceptions = [ 200, 404 ];
		for ( $i = 100; $i < 600; ++$i ) {
			if ( ! in_array( $i, $exceptions, true ) ) {
				$datasets[ $i ] = [ 'response_code' => $i ];
			}
		}

		return $datasets;
	}

	/**
	 * Test that a WP_Error object is returned for a redirected_host that's an invalid URL.
	 */
	public function test_should_return_wp_error_for_redirected_host_that_is_an_invalid_url() {
		$api_rewrite = new AspireUpdate\API_Rewrite( 'my.api.org', true, '' );
		$actual      = $api_rewrite->pre_http_request(
			false,
			[],
			'https://' . $this->get_default_host() . '/file.php'
		);

		$this->assertInstanceOf(
			'WP_Error',
			$actual,
			'A WP_Error object was not returned.'
		);

		$this->assertSame(
			'invalid_host',
			$actual->get_error_code(),
			'The wrong error code was returned.'
		);
	}

	/**
	 * Test that API rewriting is skipped when the 'AP_COMPATIBILITY'
	 * constant has 'skip_rewriting_on_existing_response' set to false.
	 *
	 * This test causes constants to be defined.
	 * It must run in separate processes and must not preserve global state.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_should_not_skip_api_rewriting_when_the_ap_compatibility_constant_has_skip_rewriting_on_existing_response_set_to_false() {
		define( 'AP_ENABLE', true );
		define( 'AP_COMPATIBILITY', [ 'skip_rewriting_on_existing_response' => false ] );

		$default_host = 'https://' . $this->get_default_host();

		add_filter(
			'pre_http_request',
			static function ( $response, $parsed_args, $url ) use ( $default_host ) {
				if ( $default_host === $url ) {
					return [ 'body' => 'Test Response' ];
				}

				return $response;
			},
			0,
			3
		);

		new \AspireUpdate\API_Rewrite( 'my.api.org', true, '' );
		$response = wp_remote_get( $default_host );
		$body     = wp_remote_retrieve_body( $response );

		$this->assertNotSame( 'Test Response', $body );
	}

	/**
	 * Test that API rewriting is skipped when the 'skip_rewriting_on_existing_response'
	 * option is disabled.
	 *
	 * This test causes constants to be defined.
	 * It must run in separate processes and must not preserve global state.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_should_not_skip_api_rewriting_when_the_skip_rewriting_on_existing_response_option_is_disabled() {
		update_site_option(
			'aspireupdate_settings',
			[
				'enable'        => 1,
				'compatibility' => [
					'skip_rewriting_on_existing_response' => 0,
				],
			]
		);

		$default_host = 'https://' . $this->get_default_host();

		add_filter(
			'pre_http_request',
			static function ( $response, $parsed_args, $url ) use ( $default_host ) {
				if ( $default_host === $url ) {
					return [ 'body' => 'Test Response' ];
				}

				return $response;
			},
			0,
			3
		);

		new \AspireUpdate\API_Rewrite( 'my.api.org', true, '' );
		$response = wp_remote_get( $default_host );
		$body     = wp_remote_retrieve_body( $response );

		$this->assertNotSame( 'Test Response', $body );
	}

	/**
	 * Test that API rewriting is skipped when the 'AP_COMPATIBILITY'
	 * constant has 'skip_rewriting_on_existing_response' set to true.
	 *
	 * This test causes constants to be defined.
	 * It must run in separate processes and must not preserve global state.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_should_skip_api_rewriting_when_the_ap_compatibility_constant_has_skip_rewriting_on_existing_response_set_to_true() {
		define( 'AP_ENABLE', true );
		define( 'AP_COMPATIBILITY', [ 'skip_rewriting_on_existing_response' => true ] );

		$default_host = 'https://' . $this->get_default_host();

		add_filter(
			'pre_http_request',
			static function ( $response, $parsed_args, $url ) use ( $default_host ) {
				if ( $default_host === $url ) {
					return [ 'body' => 'Test Response' ];
				}

				return $response;
			},
			0,
			3
		);

		new \AspireUpdate\API_Rewrite( 'my.api.org', true, '' );
		$response = wp_remote_get( $default_host );
		$body     = wp_remote_retrieve_body( $response );

		$this->assertSame( 'Test Response', $body );
	}

	/**
	 * Test that API rewriting is skipped when the 'skip_rewriting_on_existing_response'
	 * option is enabled.
	 *
	 * This test causes constants to be defined.
	 * It must run in separate processes and must not preserve global state.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_should_skip_api_rewriting_when_the_skip_rewriting_on_existing_response_option_is_enabled() {
		update_site_option(
			'aspireupdate_settings',
			[
				'enable'        => 1,
				'compatibility' => [
					'skip_rewriting_on_existing_response' => 1,
				],
			]
		);

		$default_host = 'https://' . $this->get_default_host();

		add_filter(
			'pre_http_request',
			static function ( $response, $parsed_args, $url ) use ( $default_host ) {
				if ( $default_host === $url ) {
					return [ 'body' => 'Test Response' ];
				}

				return $response;
			},
			0,
			3
		);

		new \AspireUpdate\API_Rewrite( 'my.api.org', true, '' );
		$response = wp_remote_get( $default_host );
		$body     = wp_remote_retrieve_body( $response );

		$this->assertSame( 'Test Response', $body );
	}

	/**
	 * Gets the default host.
	 *
	 * @return string The default host.
	 */
	private function get_default_host() {
		static $default_host;

		if ( ! $default_host ) {
			$reflection   = new ReflectionClass( 'AspireUpdate\API_Rewrite' );
			$default_host = $reflection->getDefaultProperties()['default_host'];
		}

		return $default_host;
	}
}
