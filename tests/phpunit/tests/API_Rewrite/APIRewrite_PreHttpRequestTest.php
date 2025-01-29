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
	 * Test that no request is performed when the redirected host is an empty string.
	 */
	public function test_should_not_perform_request_when_redirected_host_is_an_empty_string() {
		$request = new MockAction();
		add_filter( 'pre_http_request', [ $request, 'filter' ] );

		$api_rewrite = new AspireUpdate\API_Rewrite( '', false, '' );
		$api_rewrite->pre_http_request( [], [], '' );

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

		$api_rewrite->pre_http_request( [], [], $default_host );

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

		$api_rewrite = new AspireUpdate\API_Rewrite( 'my.api.org', false, '' );
		$api_rewrite->pre_http_request(
			[],
			[ 'sslverify' => 'original_sslverify_value' ],
			$this->get_default_host()
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

		$api_rewrite = new AspireUpdate\API_Rewrite( 'my.api.org', true, '' );
		$api_rewrite->pre_http_request(
			[],
			[ 'sslverify' => true ],
			$this->get_default_host()
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

		$api_rewrite = new AspireUpdate\API_Rewrite( 'my.api.org', true, '' );
		$api_rewrite->pre_http_request( [], [], $this->get_default_host() );

		$this->assertMatchesRegularExpression( '/my\.api\.org\?cache_buster=[0-9]+/', $actual );
	}

	/**
	 * Test that the API Key is added to the Authorization header.
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
		$api_rewrite = new AspireUpdate\API_Rewrite( 'my.api.org', true, $api_key );
		$api_rewrite->pre_http_request( [], [], $this->get_default_host() );

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
