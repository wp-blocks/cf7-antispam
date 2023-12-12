<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Cf7_Antispam
 */

// Debug settings for parity with WordPress Core's PHPUnit tests.
if ( ! defined( 'LOCAL_WP_DEBUG_LOG' ) ) {
	define( 'LOCAL_WP_DEBUG_LOG', true );
}
if ( ! defined( 'LOCAL_WP_DEBUG_DISPLAY' ) ) {
	define( 'LOCAL_WP_DEBUG_DISPLAY', true );
}
if ( ! defined( 'LOCAL_SCRIPT_DEBUG' ) ) {
	define( 'LOCAL_SCRIPT_DEBUG', true );
}
if ( ! defined( 'LOCAL_WP_ENVIRONMENT_TYPE' ) ) {
	define( 'LOCAL_WP_ENVIRONMENT_TYPE', 'local' );
}

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/../cf7-antispam.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );


/**
 * Adds a wp_die handler for use during tests.
 *
 * If bootstrap.php triggers wp_die, it will not cause the script to fail. This
 * means that tests will look like they passed even though they should have
 * failed. So we throw an exception if WordPress dies during test setup. This
 * way the failure is observable.
 *
 * @param string|WP_Error $message The error message.
 *
 * @throws Exception When a `wp_die()` occurs.
 */
function fail_if_died( $message ) {
	if ( is_wp_error( $message ) ) {
		$message = $message->get_error_message();
	}

	throw new Exception( 'WordPress died: ' . $message );
}
tests_add_filter( 'wp_die_handler', 'fail_if_died' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

// Use existing behavior for wp_die during actual test execution.
remove_filter( 'wp_die_handler', 'fail_if_died' );

// pre-load add_filter if it's not already loaded by PHPUnit
if (!function_exists('add_filter')) {
	tests_add_filter('add_filter', 'fail_if_died');
}


