<?php

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
		require_once CF7ANTISPAM_PLUGIN_DIR . '/includes/cf7a-uninstall.php';
		self::uninstall( true );

		require_once CF7ANTISPAM_PLUGIN_DIR . '/includes/cf7a-activator.php';
		CF7_AntiSpam_Activator::install();

		return true;
	}

	/**
	 * It deletes the plugin's database tables and options
	 *
	 * @param bool $force If set to true, the cf7-antispam database and options tables delete will be forced.
	 */
	public static function uninstall( $force = false ) {

		if ( CF7ANTISPAM_DEBUG_EXTENDED && ! $force ) {

			cf7a_log( 'CONTACT FORM 7 ANTISPAM - constant "CF7ANTISPAM_DEBUG_EXTENDED" is set so options and database will NOT be deleted.' );
			return false;

		} else {

			global $wpdb;

			$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'cf7a_wordlist' );
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'cf7a_blacklist' );

			$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . "postmeta WHERE `meta_key` = '_cf7a_b8_classification'" );

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

	}

}
