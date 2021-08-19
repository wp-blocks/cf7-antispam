<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class CF7_AntiSpam_Uninstaller {

	public static function uninstall() {

		if (!CF7ANTISPAM_DEBUG_EXTENDED) {

			global $wpdb;

			$wpdb->query( "DROP TABLE IF EXISTS ". $wpdb->prefix ."cf7a_wordlist" );
			$wpdb->query( "DROP TABLE IF EXISTS ". $wpdb->prefix ."cf7a_blacklist" );

			delete_option("cf7a_db_version");
			delete_option("cf7a_options");

			delete_user_meta( get_current_user_id(), 'cf7a_hide_welcome_panel_on');

			$wpdb->query( "DELETE FROM " . $wpdb->prefix . "postmeta WHERE `meta_key` = '_cf7a_b8_classification'" );

			error_log(print_r(CF7ANTISPAM_LOG_PREFIX.'plugin uninstalled',true));

		} else {

			error_log(print_r('CONTACT FORM 7 ANTISPAM - "CF7ANTISPAM_DEBUG_EXTENDED = true" so options and database will NOT be deleted.',true));

		}

	}

}
