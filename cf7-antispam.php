<?php
/*
Plugin Name: AntiSpam for Contact Form 7
Description: A trustworthy antispam plugin for Contact Form 7. Simple but effective.
Author: Codekraft
Text Domain: cf7-antispam
Domain Path: /languages/
Version: 0.2.3
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;

// CONSTANTS
define( 'CF7ANTISPAM_NAME', 'cf7-antispam' );

define( 'CF7ANTISPAM_VERSION', '0.2.4' );

define( 'CF7ANTISPAM_PLUGIN', __FILE__ );

define( 'CF7ANTISPAM_PLUGIN_BASENAME', plugin_basename( CF7ANTISPAM_PLUGIN ) );

define( 'CF7ANTISPAM_PLUGIN_DIR', untrailingslashit( dirname( CF7ANTISPAM_PLUGIN ) ) );

define( 'CF7ANTISPAM_LOG_PREFIX', 'CF7A: ' );

if (!defined('CF7ANTISPAM_DEBUG')) define( 'CF7ANTISPAM_DEBUG', false);
if (!defined('CF7ANTISPAM_DEBUG_EXTENDED')) define( 'CF7ANTISPAM_DEBUG_EXTENDED', false);
if (!defined('CF7ANTISPAM_DNSBL_BENCHMARK')) define( 'CF7ANTISPAM_DNSBL_BENCHMARK', false);

if (!defined('CF7ANTISPAM_PREFIX')) define( 'CF7ANTISPAM_PREFIX', "_cf7a_");
if (!defined('CF7ANTISPAM_HONEYPOT_CLASS')) define( 'CF7ANTISPAM_HONEYPOT_CLASS', "fit-the-fullspace");
if (!defined('CF7ANTISPAM_CYPHER')) define( 'CF7ANTISPAM_CYPHER', "aes-128-cbc");


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
 * The code that runs during plugin un-installation.
 */
function uninstall_cf7_antispam() {
	require_once CF7ANTISPAM_PLUGIN_DIR . '/includes/cf7a-uninstall.php';
	CF7_AntiSpam_Uninstaller::uninstall();
}
register_uninstall_hook(  CF7ANTISPAM_PLUGIN, 'uninstall_cf7_antispam' );


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
