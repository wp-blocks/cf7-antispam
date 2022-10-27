<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Cf7_Antispam
 */

// Composer autoloader must be loaded before WP_PHPUNIT__DIR will be available
require_once dirname( __FILE__ ) . '../../vendor/autoload.php';

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = getenv( 'WP_PHPUNIT__DIR' );
}

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/functions.php";

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

/*
* Load PHPUnit Polyfills for the WP testing suite.
*/
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __FILE__ ) . '../../vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php' );

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( __FILE__ ) . '../../cf7-antispam.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * If WordPress dies, throw an exception.
 *
 * @param message The message to display to the user.
 */

function handle_wp_setup_failure( $message ) {
	if ( is_wp_error( $message ) ) {
		$message = $message->get_error_message();
	}

	throw new Exception( 'WordPress died: ' . $message );
}
tests_add_filter( 'wp_die_handler', 'handle_wp_setup_failure' );

/*
 * Start up the WP testing environment.
 */
require dirname( __FILE__ ) . '../../tests/phpunit/bootstrap.php';

remove_filter( 'wp_die_handler', 'handle_wp_setup_failure' );

require "{$_tests_dir}/includes/test-CF7_AntiSpam_ActivatorTest.php";
