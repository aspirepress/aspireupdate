<?php
/**
 * Abstract base test class for \AspireUpdate\Debug.
 *
 * All \AspireUpdate\Debug unit tests should inherit from this class.
 */
abstract class Debug_UnitTestCase extends WP_UnitTestCase {
	/**
	 * The path to the log file.
	 *
	 * @var string
	 */
	protected static $log_file;

	/**
	 * Previously created filesystems.
	 *
	 * @var array
	 */
	protected static $filesystems = [];

	/**
	 * The original value of $wp_filesystem before any tests run.
	 *
	 * @var WP_Filesystem_Base|null|false False if not already set.
	 */
	protected static $default_filesystem;

	/**
	 * Gets the log file's path, and deletes if it exists before any tests run.
	 * Backs up the default filesystem.
	 *
	 * @return void
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		$get_file_path = new ReflectionMethod(
			'AspireUpdate\Debug',
			'get_file_path'
		);

		$get_file_path->setAccessible( true );
		self::$log_file = $get_file_path->invoke( null );
		$get_file_path->setAccessible( false );

		if ( file_exists( self::$log_file ) ) {
			unlink( self::$log_file );
		}

		if ( isset( $GLOBALS['wp_filesystem'] ) ) {
			self::$default_filesystem = $GLOBALS['wp_filesystem'];
		} else {
			self::$default_filesystem = false;
		}
	}

	/**
	 * Filters the filesystem method before each test runs.
	 *
	 * Filters are removed in the tear_down() parent method.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		add_filter(
			'filesystem_method',
			static function () {
				return 'direct';
			}
		);
	}

	/**
	 * Delete the log file and restores the filesystem after each test runs.
	 *
	 * @return void
	 */
	public function tear_down() {
		if ( file_exists( self::$log_file ) ) {
			unlink( self::$log_file );
		}

		if ( false === self::$default_filesystem ) {
			unset( $GLOBALS['wp_filesystem'] );
		} else {
			$GLOBALS['wp_filesystem'] = self::$default_filesystem;
		}

		parent::tear_down();
	}

	/**
	 * Creates a fake filesystem.
	 *
	 * @param bool|null $exists      Whether paths should exist.
	 *                               Default null uses default implemenation.
	 * @param bool|null $is_readable Whether paths should be readable.
	 *                               Default null uses default implemenation.
	 * @param bool|null $is_writable Whether paths should be writable.
	 *                               Default null uses default implemenation.
	 */
	public function get_fake_filesystem( $exists = null, $is_readable = null, $is_writable = null ) {
		$hash = ( null === $exists ? '-1' : (int) $exists ) . ',' .
				( null === $is_readable ? '-1' : (int) $is_readable ) . ',' .
				( null === $is_writable ? '-1' : (int) $is_writable );

		if ( ! isset( self::$filesystems[ $hash ] ) ) {
			self::$filesystems[ $hash ] = new AP_FakeFilesystem( $exists, $is_readable, $is_writable );
		}

		return self::$filesystems[ $hash ];
	}
}
