<?php
/**
 * Class Debug_LogTest
 *
 * @package AspireUpdate
 */

/**
 * Tests for Debug::log()
 *
 * @covers \AspireUpdate\Debug::log
 */
class Debug_LogTest extends Debug_UnitTestCase {
	/**
	 * Test that nothing is written to the log file when the filesystem isn't available.
	 *
	 * @covers \AspireUpdate\Debug::init_filesystem
	 * @covers \AspireUpdate\Debug::verify_filesystem
	 */
	public function test_should_not_write_to_log_file_when_filesystem_is_not_available() {
		add_filter( 'filesystem_method', '__return_false' );

		AspireUpdate\Debug::log( 'Test log message.' );

		$this->assertFileDoesNotExist(
			self::$log_file,
			'The log file was created.'
		);
	}

	/**
	 * Test that the log file is created when it doesn't already exist.
	 */
	public function test_should_create_log_file_if_it_does_not_already_exist() {
		$this->assertFileDoesNotExist(
			self::$log_file,
			'The log file already exists before testing.'
		);

		$message = 'Test log message.';

		AspireUpdate\Debug::log( $message );

		$this->assertFileExists(
			self::$log_file,
			'The log file was not created.'
		);
	}

	/**
	 * Test that the message is added to the log file.
	 *
	 * @covers \AspireUpdate\Debug::format_message
	 */
	public function test_should_add_message_to_log_file() {
		$this->assertFileDoesNotExist(
			self::$log_file,
			'The log file already exists before testing.'
		);

		$message = 'Test log message.';

		AspireUpdate\Debug::log( $message );

		$this->assertFileExists(
			self::$log_file,
			'The log file was not created.'
		);

		$this->assertStringContainsString(
			$message,
			file_get_contents( self::$log_file ),
			'The message was not added.'
		);
	}

	/**
	 * Test that the message is prepended to an existing log file.
	 *
	 * @covers \AspireUpdate\Debug::format_message
	 */
	public function test_should_add_message_to_an_existing_log_file() {
		$existing_content = 'An existing log file.';
		file_put_contents( self::$log_file, $existing_content );

		$message = 'Test log message.';

		AspireUpdate\Debug::log( $message );

		$this->assertFileExists(
			self::$log_file,
			'The log file was not created.'
		);

		$this->assertStringContainsString(
			"$message\n$existing_content",
			file_get_contents( self::$log_file ),
			'The message was not prepended to the log file.'
		);
	}

	/**
	 * Test that the message is prefixed with the timestamp.
	 *
	 * @covers \AspireUpdate\Debug::format_message
	 */
	public function test_should_prefix_message_with_timestamp() {
		AspireUpdate\Debug::log( 'Test log message.' );

		$this->assertFileExists(
			self::$log_file,
			'The log file was not created.'
		);

		$this->assertMatchesRegularExpression(
			'/^\[[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\]/',
			file_get_contents( self::$log_file ),
			'The message was not prefixed with the timestamp.'
		);
	}

	/**
	 * Test that the message is prefixed with its type.
	 *
	 * @dataProvider data_message_types
	 *
	 * @covers \AspireUpdate\Debug::format_message
	 *
	 * @param string $type The type of message.
	 */
	public function test_should_prefix_message_with_type( $type ) {
		$message = 'Test log message.';

		AspireUpdate\Debug::log( $message, $type );

		$this->assertFileExists(
			self::$log_file,
			'The log file was not created.'
		);

		$this->assertStringContainsString(
			'[' . strtoupper( $type ) . ']: ' . $message,
			file_get_contents( self::$log_file ),
			'The message was not prefixed with its type.'
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_message_types() {
		return $this->text_array_to_dataprovider(
			[
				'string',
				'request',
				'response',
				'custom',
			]
		);
	}

	/**
	 * Test that array and object messages are expanded.
	 *
	 * @dataProvider data_arrays_and_objects
	 *
	 * @covers \AspireUpdate\Debug::format_message
	 *
	 * @param array|object $message The message.
	 */
	public function test_should_expand_array_or_object_messages( $message ) {
		AspireUpdate\Debug::log( $message );

		$this->assertFileExists(
			self::$log_file,
			'The log file was not created.'
		);

		$this->assertStringContainsString(
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			print_r( $message, true ),
			file_get_contents( self::$log_file ),
			'The array message was not expanded.'
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_arrays_and_objects() {
		return [
			'an array'                     => [
				'message' => [],
			],
			'a non-empty array'            => [
				'message' => [ 'First line', 'Second line', 'Third line' ],
			],
			'an object with no properties' => [
				'message' => (object) [],
			],
			'an object with properties'    => [
				'message' => (object) [ 'First line', 'Second line', 'Third line' ],
			],
		];
	}
}
