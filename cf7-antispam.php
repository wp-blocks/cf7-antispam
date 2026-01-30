<?php
/**
 * Plugin Name: AntiSpam for Contact Form 7
 * Description: A trustworthy antispam plugin for Contact Form 7. Simple but effective.
 * Author: Codekraft
 * Text Domain: cf7-antispam
 * Domain Path: /languages
 * Version: 0.7.4
 * License: GPLv2 or later
 * Requires Plugins: contact-form-7
 *
 * @package cf7-antispam
 */

/* If this file is called directly, abort. */
if ( ! defined( 'WPINC' ) ) {
	die;
}

/* CONSTANTS */
define( 'CF7ANTISPAM_NAME', 'cf7-antispam' );

define( 'CF7ANTISPAM_VERSION', '0.7.4' );

define( 'CF7ANTISPAM_PLUGIN', __FILE__ );

define( 'CF7ANTISPAM_PLUGIN_BASENAME', plugin_basename( CF7ANTISPAM_PLUGIN ) );

define( 'CF7ANTISPAM_PLUGIN_DIR', untrailingslashit( dirname( CF7ANTISPAM_PLUGIN ) ) );

define( 'CF7ANTISPAM_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

define( 'CF7ANTISPAM_LOG_PREFIX', 'CF7A: ' );

if ( ! defined( 'CF7ANTISPAM_DEBUG' ) ) {
	define( 'CF7ANTISPAM_DEBUG', false );
}
if ( ! defined( 'CF7ANTISPAM_DEBUG_EXTENDED' ) ) {
	define( 'CF7ANTISPAM_DEBUG_EXTENDED', false );
}
if ( ! defined( 'CF7ANTISPAM_DNSBL_BENCHMARK' ) ) {
	define( 'CF7ANTISPAM_DNSBL_BENCHMARK', false );
}

if ( ! defined( 'CF7ANTISPAM_PREFIX' ) ) {
	define( 'CF7ANTISPAM_PREFIX', '_cf7a_' );
}
if ( ! defined( 'CF7ANTISPAM_HONEYPOT_CLASS' ) ) {
	define( 'CF7ANTISPAM_HONEYPOT_CLASS', 'fit-the-fullspace' );
}
if ( ! defined( 'CF7ANTISPAM_CYPHER' ) ) {
	define( 'CF7ANTISPAM_CYPHER', 'aes-128-cbc' );
}

if ( ! defined( 'CF7ANTISPAM_GEOIP_KEY' ) ) {
	define( 'CF7ANTISPAM_GEOIP_KEY', false );
}

/**
 * CF7-AntiSpam autoload
 */
require_once CF7ANTISPAM_PLUGIN_DIR . '/vendor/autoload.php';

/**
 * CF7-AntiSpam functions
 */
require_once CF7ANTISPAM_PLUGIN_DIR . '/core/functions.php';

/**
 * The code that runs during plugin activation.
 *
 * @param bool $network_wide - Whether to activate the plugin for the en
 */
function cf7a_activate( bool $network_wide ) {
	\CF7_AntiSpam\Engine\CF7_AntiSpam_Activator::on_activate( $network_wide );
}
register_activation_hook( CF7ANTISPAM_PLUGIN, 'cf7a_activate' );

/**
 * Creating the cf7-antispam tables whenever a new blog is created
 *
 * @param int $blog_id - The ID of the new blog.
 *
 * @since 0.4.5
 */
function cf7a_on_create_blog( int $blog_id ) {
	if ( is_plugin_active_for_network( 'cf7-antispam/cf7-antispam.php' ) ) {
		switch_to_blog( $blog_id );
		\CF7_AntiSpam\Engine\CF7_AntiSpam_Activator::activate();
		restore_current_blog();
	}
}
add_action( 'wpmu_new_blog', 'cf7a_on_create_blog' );

/**
 * Deleting the table whenever a blog is deleted
 *
 * @param array $tables - Array of tables.
 *
 * @since 0.4.5
 */
function cf7a_on_delete_blog( array $tables ): array {
	global $wpdb;
	$tables[] = $wpdb->prefix . 'cf7a_wordlist';
	$tables[] = $wpdb->prefix . 'cf7a_blocklist';
	return $tables;
}
add_filter( 'wpmu_drop_tables', 'cf7a_on_delete_blog' );

/**
 * The code that runs during plugin deactivation.
 */
function cf7a_deactivate() {
	\CF7_AntiSpam\Engine\CF7_AntiSpam_Deactivator::deactivate();
}
register_deactivation_hook( CF7ANTISPAM_PLUGIN, 'cf7a_deactivate' );

/**
 * The code that runs during plugin un-installation.
 */
function cf7a_uninstall() {
	\CF7_AntiSpam\Engine\CF7_AntiSpam_Uninstaller::uninstall();
}
register_uninstall_hook( CF7ANTISPAM_PLUGIN, 'cf7a_uninstall' );


/**
 * Call the integration action to mount our plugin as a component into the integration page
 */
function cf7a_register_service() {
	$integration = WPCF7_Integration::get_instance();
	$integration->add_service(
		'cf7-antispam',
		\CF7_AntiSpam\Core\CF7_Antispam_Service::get_instance()
	);
}
add_action( 'wpcf7_init', 'cf7a_register_service', 2, 0 );

/**
 * Executes CF7-AntiSpam
 */
function cf7a_run() {
	$cf7a = new \CF7_AntiSpam\Core\CF7_AntiSpam();
	$cf7a->run();
}
add_action( 'init', 'cf7a_run', 11 );
