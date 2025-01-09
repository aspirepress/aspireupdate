<?php
/**
 * Class Debug_ClearTest
 *
 * @package AspireUpdate
 */

/**
 * Tests for Debug::clear()
 *
 * @covers \AspireUpdate\Debug::clear
 */
class Debug_ClearTest extends Debug_UnitTestCase {
	/**
	 * Test that a WP_Error object is returned when the filesystem isn't available.
	 *
	 * @covers \AspireUpdate\Debug::init_filesystem
	 * @covers \AspireUpdate\Debug::verify_filesystem
	 */
	public function test_should_return_wp_error_when_filesystem_is_not_available() {
		add_filter( 'filesystem_method', '__return_false' );

		$this->assertWPError(
			AspireUpdate\Debug::clear(),
			'A WP_Error object was not returned.'
		);

		$this->assertFileDoesNotExist(
			self::$log_file,
			'The log file was created.'
		);
	}

	/**
	 * Test that a WP_Error object is returned when the log file doesn't exist.
	 *
	 * @covers \AspireUpdate\Debug::init_filesystem
	 * @covers \AspireUpdate\Debug::verify_filesystem
	 * @covers \AspireUpdate\Debug::get_file_path
	 */
	public function test_should_return_wp_error_when_log_file_does_not_exist() {
		$this->assertWPError(
			AspireUpdate\Debug::clear(),
			'A WP_Error object was not returned.'
		);

		$this->assertFileDoesNotExist(
			self::$log_file,
			'The log file was created.'
		);
	}

	/**
	 * Test that a WP_Error object is returned when the log file isn't writable.
	 *
	 * @covers \AspireUpdate\Debug::init_filesystem
	 * @covers \AspireUpdate\Debug::verify_filesystem
	 * @covers \AspireUpdate\Debug::get_file_path
	 */
	public function test_should_return_wp_error_when_log_file_is_not_writable() {
		global $wp_filesystem;

		// Backup and replace the filesystem object.
		$wp_filesystem = $this->get_fake_filesystem( true, true, false );

		$actual = AspireUpdate\Debug::clear();

		$this->assertWPError(
			$actual,
			'A WP_Error was not returned.'
		);

		$this->assertFileDoesNotExist(
			self::$log_file,
			'The log file was created.'
		);
	}

	/**
	 * Test that the log file is cleared.
	 */
	public function test_should_clear_log_file() {
		file_put_contents(
			self::$log_file,
			"First line\r\nSecond line\r\nThird line"
		);

		$this->assertFileExists(
			self::$log_file,
			'The log file was not created before testing.'
		);

		AspireUpdate\Debug::clear();

		$this->assertFileExists(
			self::$log_file,
			'The log file was deleted.'
		);

		$this->assertSame(
			'',
			file_get_contents( self::$log_file ),
			'The log file was not cleared.'
		);
	}
}
