<?php
/*
Plugin Name: Contact Form 7 AntiSpam
Description: A trustworthy antispam plugin for Contact Form 7. Simple but effective.
Author: codekraft
Text Domain: cf7-antispam
Domain Path: /languages/
Version: 0.0.1
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {die;}


// CONSTANTS
define( 'CF7ANTISPAM_VERSION', '0.0.1' );

define( 'CF7ANTISPAM_PLUGIN', __FILE__ );

define( 'CF7ANTISPAM_PLUGIN_BASENAME', plugin_basename( CF7ANTISPAM_PLUGIN ) );

define( 'CF7ANTISPAM_PLUGIN_DIR', untrailingslashit( dirname( CF7ANTISPAM_PLUGIN ) ) );


// OPTIONS
if ( ! defined( 'CF7ANTISPAM_security_level' ) ) {
	define( 'CF7ANTISPAM_security_level', "standard" );
}

// PLUGIN

/**
 * CF7-AntiSpam functions
 */
require_once CF7ANTISPAM_PLUGIN_DIR . '/includes/cf7a-functions.php';

/**
 * The code that runs during plugin activation.
 */
function activate_cf7_antispam() {
	require_once CF7ANTISPAM_PLUGIN_DIR . '/includes/cf7a-activator.php';
	CF7_AntiSpam_Activator::activate();
}
register_activation_hook( CF7ANTISPAM_PLUGIN, 'activate_cf7_antispam' );

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_cf7_antispam() {
	require_once CF7ANTISPAM_PLUGIN_DIR . '/includes/cf7a-deactivator.php';
	CF7_AntiSpam_Deactivator::deactivate();
}
register_deactivation_hook( CF7ANTISPAM_PLUGIN, 'deactivate_cf7_antispam' );


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require CF7ANTISPAM_PLUGIN_DIR . '/includes/cf7a-core.php';

/**
 * Initialize the plugin once all other plugins have finished loading.
 */
function run_cf7a() {
	$cf7a = new CF7_AntiSpam();
	$cf7a->run();
}
add_action( 'init', 'run_cf7a', 11 );