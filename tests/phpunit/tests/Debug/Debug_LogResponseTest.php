<?php
/**
 * Class Debug_LogResponseTest
 *
 * @package AspireUpdate
 */

/**
 * Tests for Debug::log_response()
 *
 * These tests cause constants to be defined.
 * They must run in separate processes and must not preserve global state.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @covers \AspireUpdate\Debug::log_response
 */
class Debug_LogResponseTest extends Debug_UnitTestCase {
	/**
	 * Test that nothing is written to the log file when debugging is disabled.
	 */
	public function test_should_not_write_to_log_file_when_debugging_is_disabled() {
		define( 'AP_DEBUG', false );
		define( 'AP_DEBUG_TYPES', [ 'request', 'response', 'string' ] );

		AspireUpdate\Debug::log_response( 'Test log message.' );

		$this->assertFileDoesNotExist( self::$log_file );
	}

	/**
	 * Test that nothing is written to the log file when debug types are not an array.
	 */
	public function test_should_not_write_to_log_file_when_debug_types_are_not_an_array() {
		define( 'AP_DEBUG', true );
		define( 'AP_DEBUG_TYPES', 'response' );

		AspireUpdate\Debug::log_response( 'Test log message.' );

		$this->assertFileDoesNotExist( self::$log_file );
	}

	/**
	 * Test that nothing is written to the log file when response debugging is disabled.
	 */
	public function test_should_not_write_to_log_file_when_response_debugging_is_disabled() {
		define( 'AP_DEBUG', true );
		define( 'AP_DEBUG_TYPES', [ 'request', 'string' ] );

		AspireUpdate\Debug::log_response( 'Test log message.' );

		$this->assertFileDoesNotExist( self::$log_file );
	}

	/**
	 * Test that the message is written to the log file.
	 *
	 * @dataProvider data_debug_types
	 *
	 * @param array $debug_types An array of enabled debug types.
	 */
	public function test_should_write_to_log_file( $debug_types ) {
		define( 'AP_DEBUG', true );
		define( 'AP_DEBUG_TYPES', $debug_types );

		$message = 'Test log message.';

		AspireUpdate\Debug::log_response( $message );

		$this->assertFileExists(
			self::$log_file,
			'The log file was created.'
		);

		$this->assertStringContainsString(
			$message,
			file_get_contents( self::$log_file ),
			'The message was not logged.'
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_debug_types() {
		return [
			'just "response"'                        => [
				'debug_types' => [ 'response' ],
			],
			'"response" at the start of the array"'  => [
				'debug_types' => [ 'response', 'request' ],
			],
			'"response" in the middle of the array"' => [
				'debug_types' => [ 'string', 'response', 'request' ],
			],
			'"response" at the end of the array"'    => [
				'debug_types' => [ 'string', 'request', 'response' ],
			],
		];
	}
}
