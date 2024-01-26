<?php

namespace CF7_AntiSpam\Engine;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/includes
 * @author     Codekraft Studio <info@codekraft.it>
 */

use CF7_AntiSpam\Admin\CF7_AntiSpam_Admin_Tools;

/**
 * It's a class that activates the plugin.
 */
class CF7_AntiSpam_Activator {

	/**
	 * Creating a private static variable called $default_cf7a_options and assigning it an empty array.
	 *
	 * @var array $default_cf7a_options
	 */
	private static $default_cf7a_options = array();

	/**
	 * Creating an array of default options for the plugin.
	 *
	 * @var array $default_cf7a_options_bootstrap
	 */
	private static $default_cf7a_options_bootstrap = array();

	/**
	 * It sets the default options for the plugin.
	 */
	public static function init_vars() {
		self::$default_cf7a_options = array(
			'cf7a_enable'                      => true,
			'cf7a_version'                     => CF7ANTISPAM_VERSION,
			'cf7a_customizations_class'        => CF7ANTISPAM_HONEYPOT_CLASS,
			'cf7a_customizations_prefix'       => CF7ANTISPAM_PREFIX,
			'cf7a_cipher'                      => 'aes-128-cbc',
			'cf7a_score_preset'                => 'weak',
			'cf7a_disable_reload'              => true,
			'check_bot_fingerprint'            => true,
			'check_bot_fingerprint_extras'     => true,
			'append_on_submit'                 => true,
			'check_time'                       => true,
			'check_time_min'                   => 6,
			'check_time_max'                   => YEAR_IN_SECONDS,
			'check_bad_ip'                     => true,
			'autostore_bad_ip'                 => true,
			'max_attempts'                     => 3,
			'unban_after'                      => 'disabled',
			'check_bad_words'                  => true,
			'check_bad_email_strings'          => true,
			'check_bad_user_agent'             => true,
			'check_dnsbl'                      => false,
			'check_refer'                      => true,
			'check_honeypot'                   => true,
			'check_honeyform'                  => false,
			'identity_protection_user'         => false,
			'identity_protection_wp'           => false,
			'enable_geoip_download'            => false,
			'geoip_dbkey'                      => false,
			'check_language'                   => false,
			'check_geo_location'               => false,
			'honeyform_position'               => 'the_content',
			'enable_b8'                        => true,
			'b8_threshold'                     => 0.95,
			'enable_advanced_settings'         => 0,
			'mailbox_protection_multiple_send' => 0,
			'bad_words_list'                   => array(),
			'bad_ip_list'                      => array(),
			'ip_whitelist'                     => array(),
			'bad_email_strings_list'           => array(),
			'bad_user_agent_list'              => array(),
			'dnsbl_list'                       => array(),
			'honeypot_input_names'             => array(),
			'honeyform_excluded_pages'         => array(),
			'languages_locales'                => array(
				'allowed'    => array(),
				'disallowed' => array(),
			),
			'score'                            => array(
				'_fingerprinting' => 0.1,
				'_time'           => 0.3,
				'_bad_string'     => 0.5,
				'_dnsbl'          => 0.1,
				'_honeypot'       => 0.3,
				'_detection'      => 0.7,
				'_warn'           => 0.3,
			),
		);

		self::$default_cf7a_options_bootstrap = array(
			'bad_words_list'         => array(
				'viagra',
				'Earn extra cash',
				'MEET SINGLES',
			),
			'bad_email_strings_list' => array(
				wp_parse_url( get_site_url(), PHP_URL_HOST ),
			),
			'bad_user_agent_list'    => array(
				'bot',
				'puppeteer',
				'phantom',
				'User-Agent',
				'Java',
				'PHP',
			),
			'dnsbl_list'             => array(
				/* ipv4 dnsbl */
				'dnsbl-2.uceprotect.net',
				'dnsbl-3.uceprotect.net',
				'zen.spamhaus.org',
				'b.barracudacentral.org',
				/* ipv6 dnsbl but due to the unlimited number of ipv6 dnsl will have a lower impact */
				'bl.ipv6.spameatingmonkey.net',
			),
			'honeypot_input_names'   => array(
				'name',
				'email',
				'address',
				'zip',
				'town',
				'phone',
				'credit-card',
				'ship-address',
				'billing_company',
				'billing_city',
				'billing_country',
				'email-address',
			),
			'languages_locales'      => array(
				'allowed'    => isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] )
					? cf7a_init_languages_locales_array( sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) )
					: array(),
				'disallowed' => array(),
			),
		);
	}


	/**
	 * Script that runs when the plugin is installed
	 *
	 * @since    0.1.0
	 */
	public static function install() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_wordlist  = $wpdb->prefix . 'cf7a_wordlist';
		$table_blacklist = $wpdb->prefix . 'cf7a_blacklist';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		/* Create the term database  if not available */
		if ( $wpdb->get_var( "SHOW TABLES like '{$table_wordlist}'" ) !== $table_wordlist ) {
			$cf7a_wordlist = 'CREATE TABLE IF NOT EXISTS `' . $table_wordlist . "` (
			  `token` varchar(100) character set utf8 collate utf8_bin NOT NULL,
			  `count_ham` int unsigned default NULL,
			  `count_spam` int unsigned default NULL,
			  PRIMARY KEY (`token`)
			) $charset_collate;";

			dbDelta( $cf7a_wordlist );

			$cf7a_wordlist_version = 'INSERT INTO `' . $wpdb->prefix . "cf7a_wordlist` (`token`, `count_ham`) VALUES ('b8*dbversion', '3');";
			$cf7a_wordlist_texts   = 'INSERT INTO `' . $wpdb->prefix . "cf7a_wordlist` (`token`, `count_ham`, `count_spam`) VALUES ('b8*texts', '0', '0');";

			dbDelta( $cf7a_wordlist_version );
			dbDelta( $cf7a_wordlist_texts );

			cf7a_log( "{$table_wordlist} table creation succeeded", 2 );
		}

		/* Create the blacklist database */
		if ( $wpdb->get_var( "SHOW TABLES like '{$table_blacklist}'" ) !== $table_blacklist ) {
			$cf7a_database = "CREATE TABLE IF NOT EXISTS `{$table_blacklist}` (
				 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				 `ip` varchar(45) NOT NULL,
				 `status` int(10) unsigned DEFAULT NULL,
				 `meta` longtext,
				 PRIMARY KEY (`id`),
	             UNIQUE KEY `id` (`ip`)
			) $charset_collate;";

			dbDelta( $cf7a_database );

			cf7a_log( "{$table_blacklist} table creation succeeded", 2 );
		}
	}

	/**
	 *  Create or Update the CF7 Antispam options
	 *
	 * @param bool $reset_options - whatever to force the reset.
	 */
	public static function update_options( $reset_options = false ) {
		self::init_vars();

		$options = get_option( 'cf7a_options' );

		if ( false === $options || $reset_options ) {

			// Delete all options
			if ( $reset_options === true ) {
				delete_option( 'cf7a_options' );
			}

			/* if the plugin options are missing Init the plugin with the default option + the default settings */
			$new_options = array_merge( self::$default_cf7a_options, self::$default_cf7a_options_bootstrap );

			add_option( 'cf7a_options', $new_options );

		} else {

			/* update the plugin options but add the new options automatically */
			if ( isset( $options['cf7a_version'] ) ) {
				unset( $options['cf7a_version'] );
			}

			/* merge previous options with the updated copy keeping the already selected option as default */
			$new_options = array_merge( self::$default_cf7a_options, $options );

			cf7a_log( 'CF7-antispam plugin options updated', 1 );

			update_option( 'cf7a_options', $new_options );
		}

		cf7a_log( $new_options, 1 );

		CF7_AntiSpam_Admin_Tools::cf7a_push_notice( esc_html__( 'CF7 AntiSpam updated successful! Please flush cache to refresh hidden form data', 'cf7-antispam' ), 'success cf7-antispam' );
	}

	/**
	 *  Activate CF7 Antispam Plugin
	 */
	public static function activate() {
		if ( CF7ANTISPAM_DEBUG ) {
			cf7a_log( 'CF7-Antispam plugin enabled', 1 );
		}

		if ( ! get_option( 'cf7a_db_version' ) ) {
			self::install();
			update_option( 'cf7a_db_version', '1' );
		}

		/* If the options do not exist then create them*/
		self::update_options();

		/* Checks and handles updates on version change */
		$options = get_option( 'cf7a_options' );
		$updater = new \CF7_AntiSpam\Engine\CF7_AntiSpam_Updater( CF7ANTISPAM_VERSION, $options );
		$updater->may_do_updates();

		set_transient( 'cf7a_activation', true );
	}

	/**
	 * Creating tables for all blogs in a WordPress Multisite installation
	 *
	 * @param bool $network_wide - true if multisite, false if not.
	 */
	public static function on_activate( $network_wide ) {
		global $wpdb;

		if ( is_multisite() && $network_wide ) {
			// Get all blogs in the network and activate plugin on each one.
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );
				self::activate();
				restore_current_blog();
			}
		} else {
			self::activate();
		}
	}

}
