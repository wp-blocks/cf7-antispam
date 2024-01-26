<?php

namespace CF7_AntiSpam\Engine;

/**
 * Fired during Uninstall.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/includes
 * @author     Codekraft Studio <info@codekraft.it>
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class CF7_AntiSpam_Uninstaller {

	/**
	 * It deletes all the blacklisted ip
	 *
	 * @return bool - The result of the query.
	 */
	public static function cf7a_clean_blacklist() {
		global $wpdb;
		$r = $wpdb->query( "TRUNCATE TABLE `{$wpdb->prefix}cf7a_blacklist`" );
		return ! is_wp_error( $r );
	}

	/**
	 * It uninstalls the plugin, then reinstall it
	 */
	public static function cf7a_full_reset() {
		self::uninstall( false );
		CF7_AntiSpam_Activator::install();

		// reset options
		update_option( 'cf7a_db_version', '1' );
		CF7_AntiSpam_Activator::update_options( true );

		return true;
	}

	/**
	 * It deletes the plugin's database tables and options
	 *
	 * @return bool
	 */
	protected static function cf7a_plugin_drop_tables() {
		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'cf7a_wordlist' );
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'cf7a_blacklist' );

		$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . "postmeta WHERE `meta_key` = '_cf7a_b8_classification'" );
	}

	/**
	 * It deletes the plugin's database tables and options
	 *
	 * @return bool
	 */
	protected static function cf7a_plugin_drop_options() {
		delete_option( 'cf7a_db_version' );
		delete_option( 'cf7a_options' );
		delete_option( 'cf7a_geodb_update' );

		delete_metadata( 'user', 0, 'cf7a_hide_welcome_panel_on', '', true );

		/* unschedule cf7a events */
		$timestamp = wp_next_scheduled( 'cf7a_cron' );
		if ( $timestamp ) {
			wp_clear_scheduled_hook( 'cf7a_cron' );
		}

		cf7a_log( 'plugin uninstalled' );
		return true;
	}

	/**
	 * Fires the right uninstall routine between single and multisite installations
	 *
	 * @param bool $force If set to true, the cf7-antispam database and options tables delete will be forced otherwise it will be skipped.
	 */
	public static function uninstall( $force = true ) {
		if ( ( defined( CF7ANTISPAM_DEBUG_EXTENDED ) && CF7ANTISPAM_DEBUG_EXTENDED === true ) || $force === false ) {
			cf7a_log( 'CONTACT FORM 7 ANTISPAM - constant "CF7ANTISPAM_DEBUG_EXTENDED" is set so options and database will NOT be deleted.' );
			return false;
		} else {
			global $wpdb;

			$is_multisite = is_multisite() || ( defined( 'WP_INSTALLING_NETWORK' ) && WP_INSTALLING_NETWORK );

			if ( $is_multisite ) {
				// Get all blogs in the network and uninstall the plugin on each one.
				$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

				foreach ( $blog_ids as $blog_id ) {
					switch_to_blog( $blog_id );

					// Remove tables and options.
					self::cf7a_plugin_drop_tables();
					self::cf7a_plugin_drop_options();

					restore_current_blog();
				}
			}

			// Always remove the main site database tables and options.
			self::cf7a_plugin_drop_tables();
			self::cf7a_plugin_drop_options();
		}
	}

}
