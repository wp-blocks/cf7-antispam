<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class CF7_AntiSpam_Uninstaller {

	public static function uninstall() {

		if (CF7ANTISPAM_DEBUG) error_log(print_r(CF7ANTISPAM_LOG_PREFIX.'plugin uninstalled',true));

		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS ". $wpdb->prefix ."cf7a_wordlist" );
		$wpdb->query( "DROP TABLE IF EXISTS ". $wpdb->prefix ."cf7a_blacklist" );

		delete_option("cf7a_db_version");
		delete_option("cf7a_options");
	}

}