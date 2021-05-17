<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class CF7_AntiSpam_Deactivator {

	public static function deactivate() {

		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS ". $wpdb->prefix ."cf7a_wordlist" );
		$wpdb->query( "DROP TABLE IF EXISTS ". $wpdb->prefix ."cf7a_blacklist" );

		delete_option("cf7a_db_version");
		delete_option("cf7a_options");
	}

}