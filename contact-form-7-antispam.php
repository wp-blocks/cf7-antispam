<?php
/*
Plugin Name: CF7_AntiSpam
Description: A trustworthy message storage plugin for Contact Form 7.
Author: Takayuki Miyoshi
Text Domain: cf7a
Domain Path: /languages/
Version: 2.2.1
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {die;}


// PLUGIN CONSTANTS
define( 'CF7ANTISPAM_VERSION', '0.0.1' );

define( 'CF7ANTISPAM_PLUGIN', __FILE__ );

define( 'CF7ANTISPAM_PLUGIN_BASENAME',
	plugin_basename( CF7ANTISPAM_PLUGIN ) );

define( 'CF7ANTISPAM_PLUGIN_DIR',
	untrailingslashit( dirname( CF7ANTISPAM_PLUGIN ) ) );

// OPTIONS
if ( ! defined( 'CF7ANTISPAM_security_level' ) ) {
	define( 'CF7ANTISPAM_security_level', "standard" );
}

/**
 * The code that runs during plugin activation.
 */
function activate_cf7_antispam() {
	require_once CF7ANTISPAM_PLUGIN_DIR . '/includes/cf7a-importer-activator.php';
	CF7A_Activator::activate();
}
register_activation_hook( CF7ANTISPAM_PLUGIN, 'activate_cf7_antispam' );

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_cf7_antispam() {
	require_once CF7ANTISPAM_PLUGIN_DIR . '/includes/cf7a-deactivator.php';
	CF7A_Deactivator::deactivate();
}
register_deactivation_hook( CF7ANTISPAM_PLUGIN, 'deactivate_cf7_antispam' );


/**
 * CF7-AntiSpam core functions
 */
require_once CF7ANTISPAM_PLUGIN_DIR . '/includes/cf7a-functions.php';

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require CF7ANTISPAM_PLUGIN_DIR . '/includes/cf7a-core.php';

/**
 * Init
 */
function run_cf7a() {
	$cf7a = new CF7_AntiSpam();
	$cf7a->run();
}

/**
 * Initialize the plugin once all other plugins have finished loading.
 */
add_action( 'init', 'run_cf7a', 0 );