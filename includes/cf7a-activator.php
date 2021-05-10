<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/includes
 * @author     Codekraft Studio <info@codekraft.it>
 */
class CF7_AntiSpam_Activator {

	/**
	 * Script that runs when the plugin is installed
	 *
	 *
	 * @since    1.0.0
	 */
	public static function install() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// create the term database
		$cf7a_database = "CREATE TABLE " . $wpdb->prefix . "cf7_antispam_wordlist (
		  `token` varchar(255) character set utf8 collate utf8_bin NOT NULL,
		  `count_ham` int unsigned default NULL,
		  `count_spam` int unsigned default NULL,
		  PRIMARY KEY (`token`)
		) $charset_collate;";

		$cf7a_database_version = "INSERT INTO " . $wpdb->prefix . "cf7_antispam_wordlist (`token`, `count_ham`) VALUES ('b8*dbversion', '3');";
		$cf7a_database_texts = "INSERT INTO " . $wpdb->prefix . "cf7_antispam_wordlist (`token`, `count_ham`, `count_spam`) VALUES ('b8*texts', '0', '0');";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( $cf7a_database );
		dbDelta( $cf7a_database_version );
		dbDelta( $cf7a_database_texts );
	}

 	public static function activate() {

		// https://codex.wordpress.org/Creating_Tables_with_Plugins
		$installed_ver = get_option( "cf7a_db_version" );

		if ( !$installed_ver ) {
			self::install();
			update_option( "cf7a_db_version", '1' );
		}

		/* If the options do not exist then create them*/
		if ( false == get_option( 'cf7a_options' ) ) {
			add_option( 'cf7a_options', array(
				"check_time" => true,
				"check_bad_words" => true,
				"check_bad_email_strings" => true,
				"check_dnsbl" => true,
				"enable_b8" => true,
				"check_time_min" => 3,
				"check_time_max" => 3600,
				"bad_words_list" => array(
					'viagra',
					'bitcoin'
				),
				"bad_email_strings_list" => array(
					str_replace( array( 'http://', 'https://' ), "", get_site_url() ) // check if the mail sender has the same domain of the website, in this case in this case it is an attempt to circumvent the defences
				),
				"dnsbl_list" => array(
					// ipv4 dnsbl
					"dnsbl-1.uceprotect.net",
					"dnsbl-2.uceprotect.net",
					// "dnsbl-3.uceprotect.net", // too much aggressive, use with caution
					"dnsbl.sorbs.net",
					"spam.dnsbl.sorbs.net",
					"zen.spamhaus.org",
					"bl.spamcop.net",
					"b.barracudacentral.org",
					"dnsbl.dronebl.org",
					"ips.backscatterer.org",
					// ipv6 dnsbl
					"bogons.cymru.com",
					"bl.ipv6.spameatingmonkey.net",
				),
			) );
		}
	}

}