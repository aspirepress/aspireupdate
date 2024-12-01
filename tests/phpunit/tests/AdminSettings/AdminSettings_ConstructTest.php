<?php
/**
 * Class AdminSettings_ConstructTest
 *
 * @package AspireUpdate
 */

/**
 * Tests for Admin_Settings::__construct()
 *
 * @covers \AspireUpdate\Admin_Settings::__construct
 */
class AdminSettings_ConstructTest extends AdminSettings_UnitTestCase {
	/**
	 * Test that hooks are added.
	 *
	 * @dataProvider data_hooks_and_methods
	 *
	 * @string $hook   The hook's name.
	 * @string $method The method to hook.
	 */
	public function test_should_add_hooks( $hook, $method ) {
		$admin_settings = new AspireUpdate\Admin_Settings();
		$this->assertIsInt( has_action( $hook, [ $admin_settings, $method ] ) );
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_hooks_and_methods() {
		return [
			'admin_init -> reset_settings'    => [
				'hook'   => 'admin_init',
				'method' => 'reset_settings',
			],
			'admin_init -> register_settings' => [
				'hook'   => 'admin_init',
				'method' => 'register_settings',
			],
			'admin_init -> update_settings'   => [
				'hook'   => 'admin_init',
				'method' => 'register_settings',
			],
			'admin_enqueue_scripts -> admin_enqueue_scripts' => [
				'hook'   => 'admin_enqueue_scripts',
				'method' => 'admin_enqueue_scripts',
			],
		];
	}

	/**
	 * Test that single-site hooks are added in single-site.
	 *
	 * @dataProvider data_single_site_hooks_and_methods
	 *
	 * @group ms-excluded
	 *
	 * @string $hook   The hook's name.
	 * @string $method The method to hook.
	 */
	public function test_should_add_single_site_hooks_in_single_site( $hook, $method ) {
		$admin_settings = new AspireUpdate\Admin_Settings();
		$this->assertIsInt( has_action( $hook, [ $admin_settings, $method ] ) );
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_single_site_hooks_and_methods() {
		return [
			'admin_menu -> register_admin_menu' => [
				'hook'   => 'admin_menu',
				'method' => 'register_admin_menu',
			],
			'admin_notices -> admin_notices'    => [
				'hook'   => 'admin_notices',
				'method' => 'admin_notices',
			],
		];
	}

	/**
	 * Test that multisite hooks are added in multisite.
	 *
	 * @dataProvider data_multisite_hooks_and_methods
	 *
	 * @group ms-required
	 *
	 * @string $hook   The hook's name.
	 * @string $method The method to hook.
	 */
	public function test_should_add_multisite_hooks_in_multisite( $hook, $method ) {
		$admin_settings = new AspireUpdate\Admin_Settings();
		$this->assertIsInt( has_action( $hook, [ $admin_settings, $method ] ) );
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_multisite_hooks_and_methods() {
		return [
			'network_admin_menu -> register_admin_menu' => [
				'hook'   => 'network_admin_menu',
				'method' => 'register_admin_menu',
			],
			'network_admin_notices -> admin_notices'    => [
				'hook'   => 'network_admin_notices',
				'method' => 'admin_notices',
			],
			'network_admin_edit_aspireupdate-settings -> update_settings' => [
				'hook'   => 'network_admin_edit_aspireupdate-settings',
				'method' => 'update_settings',
			],
		];
	}
}
