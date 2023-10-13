<?php

use PHPUnit\Framework\TestCase;

class Test_init extends TestCase {

	/**
	 * Admin user object.
	 *
	 * @var WP_User
	 */
	public static $admin;

	/**
	 * Setup before class.
	 *
	 * @param WP_UnitTest_Factory $factory Factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		static::$admin = $factory->user->create_and_get( array( 'role' => 'administrator' ) );
	}

	/**
	 * Setup.
	 */
	public function set_up() {
		parent::set_up();
	}

	/**
	 * Teardown.
	 */
	public function tear_down() {
		parent::tear_down();
	}
}
