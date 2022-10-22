<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class CF7_AntiSpam_Uninstaller {

	/**
	 * It deletes the plugin's database tables and options
	 *
	 * @param string $force If set to true, the cf7-antispam database and options tables delete will be forced.
	 */
	public static function uninstall( $force = false ) {

		if ( CF7ANTISPAM_DEBUG_EXTENDED && ! $force ) {

			cf7a_log( 'CONTACT FORM 7 ANTISPAM - constant "CF7ANTISPAM_DEBUG_EXTENDED" is set so options and database will NOT be deleted.' );
			return false;

		} else {

			global $wpdb;

			$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'cf7a_wordlist' );
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'cf7a_blacklist' );

			delete_option( 'cf7a_db_version' );
			delete_option( 'cf7a_options' );
			delete_option( 'cf7a_geodb_update' );

			delete_user_meta( get_current_user_id(), 'cf7a_hide_welcome_panel_on' );

			$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . "postmeta WHERE `meta_key` = '_cf7a_b8_classification'" );

			/* unschedule cf7a events */
			$timestamp = wp_next_scheduled( 'cf7a_cron' );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'cf7a_cron' );
			}

			cf7a_log( 'plugin uninstalled' );
			return true;
		}

	}

}
