<?php
/**
 * Class AdminSettings_GetInstanceTest
 *
 * @package AspireUpdate
 */

/**
 * Tests for Admin_Settings::get_instance()
 *
 * These tests rely on the method not being called during earlier test runs.
 * They must run in separate processes and must not preserve global state.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @covers \AspireUpdate\Admin_Settings::get_instance
 */
class AdminSettings_GetInstanceTest extends AdminSettings_UnitTestCase {
	/**
	 * Test that the same instance is retrieved.
	 */
	public function test_should_get_the_same_instance() {
		$this->assertSame(
			AspireUpdate\Admin_Settings::get_instance(),
			AspireUpdate\Admin_Settings::get_instance()
		);
	}
}
