<?php
/**
 * Abstract base test class for \AspireUpdate\Admin_Settings.
 *
 * All \AspireUpdate\Admin_Settings unit tests should inherit from this class.
 */
abstract class AdminSettings_UnitTestCase extends WP_UnitTestCase {
	/**
	 * The Name of the Option.
	 *
	 * @var string
	 */
	protected static $option_name = 'aspireupdate_settings';

	/**
	 * The Slug of the Option's page.
	 *
	 * @var string
	 */
	protected static $options_page = 'aspireupdate-settings';

	/**
	 * Deletes settings before each test runs.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		delete_site_option( self::$option_name );
	}
}
