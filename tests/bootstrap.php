<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Cf7_Antispam
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

// Composer autoloader must be loaded before WP_PHPUNIT__DIR will be available
require_once dirname( dirname( __FILE__ ) ) . '/vendor/autoload.php';

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}
// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

tests_add_filter( 'wp_die_handler', 'handle_wp_setup_failure' );

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/cf7-antispam.php';
}

/*
* Load PHPUnit Polyfills for the WP testing suite.
*/
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', __DIR__ . '/../../../vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php' );

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

remove_filter( 'wp_die_handler', 'handle_wp_setup_failure' );

/*
 * Start up the WP testing environment.
 */
require "{$_tests_dir}/includes/bootstrap.php";

require "{$_tests_dir}/includes/test-CF7_AntiSpam_ActivatorTest.php";
