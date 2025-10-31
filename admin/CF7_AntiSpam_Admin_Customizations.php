<?php

namespace CF7_AntiSpam\Admin;

use CF7_AntiSpam\Core\CF7_AntiSpam;
use CF7_AntiSpam\Core\CF7_Antispam_Geoip;
use WP_Query;
use function cli\err;

/**
 * The plugin settings.
 *
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/admin_customizations
 * @author     Codekraft Studio <info@codekraft.it>
 */

/**
 * Calling the plugin setting class.
 */
class CF7_AntiSpam_Admin_Customizations {


	/**
	 * The options of this plugin.
	 *
	 * @since    0.1.0
	 * @access   public
	 * @var      array    $options    options of this plugin.
	 */
	public $options;

	/**
	 * The GeoIP object
	 *
	 * @since    7.0.0
	 * @access   private
	 * @var      CF7_Antispam_Geoip    $geoip    GeoIP object.
	 */
	private CF7_Antispam_Geoip $geoip;

	/**
	 * The plugin main menu
	 *
	 * The function `__construct()` is called when the class is instantiated.
	 *
	 * The function `cf7a_options_init()` is called when the admin page is loaded.
	 *
	 * The function `get_options()` is called to get the plugin options.
	 *
	 * The class `CF7_Antispam_geoip` is instantiated.
	 */
	public function __construct() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return esc_html__( 'Administrators only', 'cf7-antispam' );
		}

		/* the plugin options */
		$this->options = CF7_AntiSpam::get_options();

		$this->geoip = new CF7_Antispam_Geoip();

		add_action( 'admin_init', array( $this, 'cf7a_options_init' ) );
	}

	/**
	 * It creates the settings page
	 */
	public function cf7a_options_init() {

		/* Group */
		register_setting(
			'cf7_antispam_options',
			'cf7a_options',
			array( $this, 'cf7a_sanitize_options' )
		);

		/* Section Bot Fingerprint */
		add_settings_section(
			'cf7a_auto_blacklist',
			__( 'Ban automatically spammers', 'cf7-antispam' ),
			array( $this, 'cf7a_print_section_auto_blacklist' ),
			'cf7a-settings'
		);

		/* Settings autostore_bad_ip */
		add_settings_field(
			'autostore_bad_ip',
			__( 'Automatic spammer IP Blacklist', 'cf7-antispam' ),
			array( $this, 'cf7a_autostore_bad_ip_callback' ),
			'cf7a-settings',
			'cf7a_auto_blacklist'
		);

		/* Settings check_time */
		add_settings_field(
			'max_attempts',
			__( 'Mail blocked before Ban', 'cf7-antispam' ),
			array( $this, 'cf7a_max_attempts' ),
			'cf7a-settings',
			'cf7a_auto_blacklist'
		);

		/* Unban after */
		add_settings_field(
			'unban_after',
			__( 'Automatic Unban', 'cf7-antispam' ),
			array( $this, 'cf7a_unban_after_callback' ),
			'cf7a-settings',
			'cf7a_auto_blacklist'
		);

		/* Section Bot Fingerprint */
		add_settings_section(
			'cf7a_bot_fingerprint',
			__( 'Bot Fingerprinting', 'cf7-antispam' ),
			array( $this, 'cf7a_print_section_bot_fingerprint' ),
			'cf7a-settings'
		);

		/* Settings bot_fingerprint */
		add_settings_field(
			'check_bot_fingerprint',
			__( 'Enable anti-bot checks', 'cf7-antispam' ),
			array( $this, 'cf7a_check_bot_fingerprint_callback' ),
			'cf7a-settings',
			'cf7a_bot_fingerprint'
		);

		/* Settings bot_fingerprint */
		add_settings_field(
			'check_bot_fingerprint_extras',
			__( 'Enable anti-bot extra checks', 'cf7-antispam' ),
			array( $this, 'cf7a_check_bot_fingerprint_extras_callback' ),
			'cf7a-settings',
			'cf7a_bot_fingerprint'
		);

		/* Settings bot_fingerprint */
		add_settings_field(
			'append_on_submit',
			__( 'Append hidden fields on submit', 'cf7-antispam' ),
			array( $this, 'cf7a_append_on_submit_callback' ),
			'cf7a-settings',
			'cf7a_bot_fingerprint'
		);

		/* Section GEOIP */
		add_settings_section(
			'cf7a_check_geoip',
			__( 'GeoIP', 'cf7-antispam' ),
			array( $this, 'cf7a_check_geoip' ),
			'cf7a-settings'
		);

		/* Settings enable geoip */
		add_settings_field(
			'enable_geoip_download',
			__( 'Enable automatic download', 'cf7-antispam' ),
			array( $this, 'cf7a_enable_geoip_callback' ),
			'cf7a-settings',
			'cf7a_check_geoip'
		);

		/**
		 * The maxmind update key (unless you have defined it). Adds cron job to keep database updated;
		 * https://www.maxmind.com/en/geolite2/signup?lang=en
		 */
		add_settings_field(
			'geoip_dbkey',
			__( 'MaxMind Update Key', 'cf7-antispam' ),
			array( $this, 'cf7a_geoip_key_callback' ),
			'cf7a-settings',
			'cf7a_check_geoip'
		);

		/* Settings upload geoip database */
		if ( empty( $this->geoip->has_license() ) ) {
			add_settings_field(
				'enable_geoip_force_download',
				__( 'Force database download', 'cf7-antispam' ),
				array( $this, 'cf7a_force_download_callback' ),
				'cf7a-settings',
				'cf7a_check_geoip'
			);
		}

		/* Settings upload geoip database */
		if ( empty( $this->geoip->is_automatic_download_enabled() ) ) {
			add_settings_field(
				'enable_geoip_manual_upload',
				__( 'Database manual upload', 'cf7-antispam' ),
				array( $this, 'cf7a_enable_geoip_manual_upload_callback' ),
				'cf7a-settings',
				'cf7a_check_geoip'
			);
		}

		/* Settings enable geoip */
		add_settings_field(
			'check_geoip_enabled',
			__( 'Database available', 'cf7-antispam' ),
			array( $this, 'cf7a_geoip_is_enabled_callback' ),
			'cf7a-settings',
			'cf7a_check_geoip'
		);

		/* Section Language */
		add_settings_section(
			'cf7a_check_language',
			__( 'Language Checks', 'cf7-antispam' ),
			array( $this, 'cf7a_check_language' ),
			'cf7a-settings'
		);

		/* Settings enable browser language check */
		add_settings_field(
			'check_language',
			__( 'Check Browser Language', 'cf7-antispam' ),
			array( $this, 'cf7a_check_browser_language_callback' ),
			'cf7a-settings',
			'cf7a_check_language'
		);

		/* Settings enable geoip check (available only if the geoip is enabled) */
		add_settings_field(
			'check_geo_location',
			__( 'Detect location using GeoIP', 'cf7-antispam' ),
			array( $this, 'cf7a_check_geo_location_callback' ),
			'cf7a-settings',
			'cf7a_check_language'
		);

		/* Settings allowed languages */
		add_settings_field(
			'language_allowed',
			__( 'Allowed browser Languages', 'cf7-antispam' ),
			array( $this, 'cf7a_language_allowed' ),
			'cf7a-settings',
			'cf7a_check_language'
		);

		/* Settings disallowed languages */
		add_settings_field(
			'cf7a_language_disallowed',
			__( 'Disallowed browser Languages', 'cf7-antispam' ),
			array( $this, 'cf7a_language_disallowed' ),
			'cf7a-settings',
			'cf7a_check_language'
		);

		/* Section Time Checks */
		add_settings_section(
			'cf7a_time_elapsed',
			__( 'Time checks', 'cf7-antispam' ),
			array( $this, 'cf7a_print_section_check_time' ),
			'cf7a-settings'
		);

		/* Settings check_time */
		add_settings_field(
			'check_time',
			__( 'Check the elapsed time', 'cf7-antispam' ),
			array( $this, 'cf7a_check_time_callback' ),
			'cf7a-settings',
			'cf7a_time_elapsed'
		);

		/* Settings check_time */
		add_settings_field(
			'check_time_min',
			__( 'Minimum elapsed time', 'cf7-antispam' ),
			array( $this, 'cf7a_check_time_min_callback' ),
			'cf7a-settings',
			'cf7a_time_elapsed'
		);

		/* Settings check_time */
		add_settings_field(
			'check_time_max',
			__( 'Maximum elapsed time', 'cf7-antispam' ),
			array( $this, 'cf7a_check_time_max_callback' ),
			'cf7a-settings',
			'cf7a_time_elapsed'
		);

		/* Section Bad IP */
		add_settings_section(
			'cf7a_bad_ip',
			__( 'Bad IP Address', 'cf7-antispam' ),
			array( $this, 'cf7a_print_section_bad_ip' ),
			'cf7a-settings'
		);

		/* Settings check_bad_ip */
		add_settings_field(
			'check_refer',
			__( 'Check HTTP referrer', 'cf7-antispam' ),
			array( $this, 'cf7a_print_check_refer' ),
			'cf7a-settings',
			'cf7a_bad_ip'
		);

		/* Settings check_bad_ip */
		add_settings_field(
			'check_bad_ip',
			__( 'Check Bad IP Address', 'cf7-antispam' ),
			array( $this, 'cf7a_check_bad_ip_callback' ),
			'cf7a-settings',
			'cf7a_bad_ip'
		);

		/* Settings bad_ip_list */
		add_settings_field(
			'bad_ip_list',
			__( 'Bad IP Address List', 'cf7-antispam' ),
			array( $this, 'cf7a_bad_ip_list_callback' ),
			'cf7a-settings',
			'cf7a_bad_ip'
		);

		/* Settings ip_whitelist */
		add_settings_field(
			'ip_whitelist',
			__( 'IP Whitelist', 'cf7-antispam' ),
			array( $this, 'cf7a_ip_whitelist_callback' ),
			'cf7a-settings',
			'cf7a_bad_ip'
		);

		/* Section Bad Words */
		add_settings_section(
			'cf7a_bad_words',
			__( 'Bad words', 'cf7-antispam' ),
			array( $this, 'cf7a_print_section_bad_words' ),
			'cf7a-settings'
		);

		/* Settings check_bad_words */
		add_settings_field(
			'check_bad_words',
			__( 'Check the message for prohibited words', 'cf7-antispam' ),
			array( $this, 'cf7a_bad_words_callback' ),
			'cf7a-settings',
			'cf7a_bad_words'
		);

		/* Settings bad_words_list */
		add_settings_field(
			'bad_words_list',
			__( 'Bad words List', 'cf7-antispam' ),
			array( $this, 'cf7a_bad_words_list_callback' ),
			'cf7a-settings',
			'cf7a_bad_words'
		);

		/* Section Bad Email Strings */
		add_settings_section(
			'cf7a_bad_email_strings',
			__( 'Bad email strings', 'cf7-antispam' ),
			array( $this, 'cf7a_print_section_bad_email_strings' ),
			'cf7a-settings'
		);

		/* Settings check_bad_email_strings */
		add_settings_field(
			'check_bad_email_strings',
			__( 'Check the email for prohibited words', 'cf7-antispam' ),
			array( $this, 'cf7a_check_bad_email_strings_callback' ),
			'cf7a-settings',
			'cf7a_bad_email_strings'
		);

		/* Settings bad_email_strings_list */
		add_settings_field(
			'bad_email_strings_list',
			__( 'Email prohibited words', 'cf7-antispam' ),
			array( $this, 'cf7a_bad_email_strings_list_callback' ),
			'cf7a-settings',
			'cf7a_bad_email_strings'
		);

		/* Section User Agent */
		add_settings_section(
			'cf7a_user_agent',
			__( 'User Agent blacklist', 'cf7-antispam' ),
			array( $this, 'cf7a_print_user_agent' ),
			'cf7a-settings'
		);

		/* Enable User Agent Blacklist */
		add_settings_field(
			'check_bad_user_agent',
			__( 'Enable User Agent blacklist', 'cf7-antispam' ),
			array( $this, 'cf7a_check_user_agent_callback' ),
			'cf7a-settings',
			'cf7a_user_agent'
		);

		/* User Agent Blacklist list */
		add_settings_field(
			'bad_user_agent_list',
			__( 'Disallowed user agents', 'cf7-antispam' ),
			array( $this, 'cf7a_user_agent_list_callback' ),
			'cf7a-settings',
			'cf7a_user_agent'
		);

		/* Section DNSBL */
		add_settings_section(
			'cf7a_dnsbl',
			__( 'DNS Blacklists', 'cf7-antispam' ),
			array( $this, 'cf7a_print_dnsbl' ),
			'cf7a-settings'
		);

		/* Enable DNS Blacklist list */
		add_settings_field(
			'check_dnsbl',
			__( 'Check IP on DNS blocklist', 'cf7-antispam' ),
			array( $this, 'cf7a_check_dnsbl_callback' ),
			'cf7a-settings',
			'cf7a_dnsbl'
		);

		/* DNS Blacklist server list */
		add_settings_field(
			'dnsbl_list',
			__( 'DNS blocklist servers', 'cf7-antispam' ),
			array( $this, 'cf7a_dnsbl_list_callback' ),
			'cf7a-settings',
			'cf7a_dnsbl'
		);

		/* Section honeypot */
		add_settings_section(
			'cf7a_honeypot',
			__( 'Honeypot', 'cf7-antispam' ),
			array( $this, 'cf7a_print_honeypot' ),
			'cf7a-settings'
		);

		/* Enable honeypot */
		add_settings_field(
			'check_honeypot',
			__( 'Add some fake input inside the form', 'cf7-antispam' ),
			array( $this, 'cf7a_enable_honeypot_callback' ),
			'cf7a-settings',
			'cf7a_honeypot'
		);

		/* honeypot input name */
		add_settings_field(
			'honeypot_input_names',
			__( 'Name for the honeypots inputs[*]', 'cf7-antispam' ),
			array( $this, 'cf7a_honeypot_input_names_callback' ),
			'cf7a-settings',
			'cf7a_honeypot'
		);

		/* Section honeyform */
		add_settings_section(
			'cf7a_honeyform',
			__( 'Honeyform <span class="label alert monospace">[experimental]</span>', 'cf7-antispam' ),
			array( $this, 'cf7a_print_honeyform' ),
			'cf7a-settings'
		);

		/* Enable honeyform */
		add_settings_field(
			'check_honeyform',
			__( 'Add an hidden form inside the page content', 'cf7-antispam' ),
			array( $this, 'cf7a_enable_honeyform_callback' ),
			'cf7a-settings',
			'cf7a_honeyform'
		);

		/* Honeyform position */
		add_settings_field(
			'honeyform_position',
			__( 'Select where the honeyform will be placed', 'cf7-antispam' ),
			array( $this, 'cf7a_honeyform_position_callback' ),
			'cf7a-settings',
			'cf7a_honeyform'
		);

		/* Honeyform excluded pages */
		add_settings_field(
			'honeyform_excluded_pages',
			__( 'Exclude pages', 'cf7-antispam' ),
			array( $this, 'cf7a_honeyform_excluded_pages_callback' ),
			'cf7a-settings',
			'cf7a_honeyform'
		);

		/* Identity Protection */
		add_settings_section(
			'cf7a_mailbox_protection',
			__( 'Mailbox Protection', 'cf7-antispam' ),
			array( $this, 'cf7a_print_mailbox_protection' ),
			'cf7a-settings'
		);

		/* Enable identity_protection */
		add_settings_field(
			'mailbox_protection_multiple_send',
			__( 'Avoid multiple send', 'cf7-antispam' ),
			array( $this, 'cf7a_mailbox_protection_multiple_send_callback' ),
			'cf7a-settings',
			'cf7a_mailbox_protection'
		);

		/* Identity Protection */
		add_settings_section(
			'cf7a_identity_protection',
			__( 'Identity Protection', 'cf7-antispam' ),
			array( $this, 'cf7a_print_identity_protection' ),
			'cf7a-settings'
		);

		/* Enable identity_protection */
		add_settings_field(
			'identity_protection_user',
			__( 'Enforce user protection', 'cf7-antispam' ),
			array( $this, 'cf7a_identity_protection_user_callback' ),
			'cf7a-settings',
			'cf7a_identity_protection'
		);

		/* identity_protection position */
		add_settings_field(
			'identity_protection_wp',
			__( 'Enforce WordPress protection', 'cf7-antispam' ),
			array( $this, 'cf7a_identity_protection_wp_callback' ),
			'cf7a-settings',
			'cf7a_identity_protection'
		);

		/* Section b8 */
		add_settings_section(
			'cf7a_b8',
			__( 'B8 statistical "Bayesian" spam filter', 'cf7-antispam' ),
			array( $this, 'cf7a_print_b8' ),
			'cf7a-settings'
		);

		/* Enable b8 */
		add_settings_field(
			'enable_b8',
			__( 'Enable B8', 'cf7-antispam' ),
			array( $this, 'cf7a_enable_b8_callback' ),
			'cf7a-settings',
			'cf7a_b8'
		);

		/* Settings b8_threshold */
		add_settings_field(
			'b8_threshold',
			__( 'B8 spam threshold', 'cf7-antispam' ),
			array( $this, 'cf7a_b8_threshold_callback' ),
			'cf7a-settings',
			'cf7a_b8'
		);

		/* Section Personalization */
		add_settings_section(
			'cf7a_customizations',
			__( 'Spam filter customizations', 'cf7-antispam' ),
			array( $this, 'cf7a_print_customizations' ),
			'cf7a-settings'
		);

		/* Enable customizations */
		add_settings_field(
			'cf7a_disable_reload',
			__( 'Disable cf7 form reload if the page is cached', 'cf7-antispam' ),
			array( $this, 'cf7a_disable_reload_callback' ),
			'cf7a-settings',
			'cf7a_customizations'
		);

		/* Enable customizations */
		add_settings_field(
			'cf7a_customizations_class',
			__( 'Your unique css class', 'cf7-antispam' ),
			array( $this, 'cf7a_customizations_class_callback' ),
			'cf7a-settings',
			'cf7a_customizations'
		);

		/* Enable customizations */
		add_settings_field(
			'cf7a_customizations_prefix',
			__( 'Your unique fields prefix', 'cf7-antispam' ),
			array( $this, 'cf7a_customizations_prefix_callback' ),
			'cf7a-settings',
			'cf7a_customizations'
		);

		/* Enable customizations */
		add_settings_field(
			'cf7a_cipher',
			__( 'The encryption method', 'cf7-antispam' ),
			array( $this, 'cf7a_customizations_cipher_callback' ),
			'cf7a-settings',
			'cf7a_customizations'
		);

		/* Section advanced settings */
		add_settings_section(
			'cf7a_advanced',
			__( 'Spam Score Rating', 'cf7-antispam' ),
			array( $this, 'cf7a_print_advanced_settings' ),
			'cf7a-settings'
		);

		/* Score Preset */
		add_settings_field(
			'cf7a_score_preset',
			__( 'Anti-spam control level', 'cf7-antispam' ),
			array( $this, 'cf7a_score_preset_callback' ),
			'cf7a-settings',
			'cf7a_advanced'
		);

		/* Enable advanced settings */
		add_settings_field(
			'enable_advanced_settings',
			__( 'Enable advanced settings', 'cf7-antispam' ),
			array( $this, 'cf7a_enable_advanced_settings_callback' ),
			'cf7a-settings',
			'cf7a_advanced'
		);

		/* Section Personalization */
		add_settings_section(
			'cf7a_scoring',
			__( 'Scoring Tweaks (1 = Ban)', 'cf7-antispam' ),
			array( $this, 'cf7a_print_scoring_settings' ),
			'cf7a-settings'
		);

		/* Settings score fingerprinting */
		add_settings_field(
			'score_fingerprinting',
			__( 'Bot fingerprinting score <small>(for each failed test)</small>', 'cf7-antispam' ),
			array( $this, 'cf7a_score_fingerprinting_callback' ),
			'cf7a-settings',
			'cf7a_scoring'
		);

		/* Settings score time */
		add_settings_field(
			'score_time',
			__( 'Time checks score', 'cf7-antispam' ),
			array( $this, 'cf7a_score_time_callback' ),
			'cf7a-settings',
			'cf7a_scoring'
		);

		/* Settings score bad_string */
		add_settings_field(
			'score_bad_string',
			__( 'String found', 'cf7-antispam' ),
			array( $this, 'cf7a_score_bad_string_callback' ),
			'cf7a-settings',
			'cf7a_scoring'
		);

		/* Settings score dnsbl */
		add_settings_field(
			'score_dnsbl',
			__( 'DNSBL score <small>(for each server)</small>', 'cf7-antispam' ),
			array( $this, 'cf7a_score_dnsbl_callback' ),
			'cf7a-settings',
			'cf7a_scoring'
		);

		/* Settings score honeypot */
		add_settings_field(
			'score_honeypot',
			__( 'Honeypot fill score <small>(for each fail)</small>', 'cf7-antispam' ),
			array( $this, 'cf7a_score_honeypot_callback' ),
			'cf7a-settings',
			'cf7a_scoring'
		);

		/* Settings score detection */
		add_settings_field(
			'score_detection',
			__( 'Bot detected', 'cf7-antispam' ),
			array( $this, 'cf7a_score_detection_callback' ),
			'cf7a-settings',
			'cf7a_scoring'
		);

		/* Settings score warn */
		add_settings_field(
			'score_warn',
			__( 'Bot warn', 'cf7-antispam' ),
			array( $this, 'cf7a_score_warn_callback' ),
			'cf7a-settings',
			'cf7a_scoring'
		);
	}

	/**
	 * It prints a paragraph with a description of the section
	 */
	public function cf7a_print_section_auto_blacklist() {
		printf( '<p>' . esc_html__( 'How many failed attempts before being banned', 'cf7-antispam' ) . '</p>' );
		$next = wp_next_scheduled( 'cf7a_cron' );
		if ( $next ) {
			printf(
				'<small class="monospace">%s %s <br/>Server time %s</small>',
				esc_html__( 'Next scheduled unban event:', 'cf7-antispam' ),
				esc_html( wp_date( 'Y-m-d H:i:s', $next ) ),
				esc_html( wp_date( 'Y-m-d H:i:s', time() ) )
			);
		}
	}

	/** It prints the bot_fingerprint info text */
	public function cf7a_print_section_bot_fingerprint() {
		printf(
			'<p>%s</p><p>%s</p>',
			esc_html__( "Fingerprinting is a method used for exploiting data from browser in order to check whether it is a real browser. A script checks software and hardware configuration like screen resolution, 3d support, available fonts and OS version, that usually aren't available for bots.", 'cf7-antispam' ),
			esc_html__( 'The last option, append on submit, causes fingerprinting to take place after the submit button has been pressed, making it even more difficult for a bot to circumvent the protection.', 'cf7-antispam' )
		);
	}

	/** It prints the check_time info text */
	public function cf7a_print_section_check_time() {
		printf(
			'<p>%s</p><p>%s<br/> %s</p><p>%s</p>',
			esc_html__( 'Checks that the form has been submitted within a reasonable timeframe, timestamp is encrypted so any manipulation of the data will result in 0.', 'cf7-antispam' ),
			esc_html__( 'Just set a few seconds as the minimum time (bots usually take 5 seconds at most, usually 3) and as the maximum time I recommend 1 year*.', 'cf7-antispam' ),
			esc_html__( '* A small note.... If you use a caching system for the contact page make sure you set you set the maximum elapsed time at least equal to the cache regeneration.', 'cf7-antispam' ),
			esc_html__( 'Values in seconds, 0 to disable', 'cf7-antispam' )
		);
	}

	/** It prints the geoip info text */
	public function cf7a_check_geoip() {
		printf(
			'<p>%s</p><p>%s <a href="https://www.maxmind.com/en/geolite2/eula">%s</a> %s <a href="https://www.maxmind.com/en/geolite2/signup">%s</a></p> <p>%s</p>',
			esc_html__( 'Detect user location using MaxMind GeoIP2 database.', 'cf7-antispam' ),
			esc_html__( 'In order to enable this functionality you need to agree at  ', 'cf7-antispam' ),
			esc_html__( 'GeoLite2 End User License Agreement', 'cf7-antispam' ),
			esc_html__( 'and sign up ', 'cf7-antispam' ),
			esc_html__( 'GeoLite2 Downloadable Databases', 'cf7-antispam' ),
			esc_html__( 'After registration you will get a key, paste it into the input below and CF7-Antispam will be able to automatically download the updated GeoIP database every month.', 'cf7-antispam' )
		);
		/* if the geo-ip constant was not set recommend to do so */
		if ( ! CF7ANTISPAM_GEOIP_KEY ) {
			printf(
				'<p>%s<br/><code>%s</code></p>',
				esc_html__( 'Recommended - define a key your config.php the key in this way: ', 'cf7-antispam' ),
				// 👇 this is an example of a key definition, isn't define itself.
				"define( 'CF7ANTISPAM_GEOIP_KEY', 'aBcDeFgGhiLmNoPqR' );"
			);
		}
	}

	/** It prints the language info text */
	public function cf7a_check_language() {
		$iso_639  = '<a href="' . esc_url( 'https://www.loc.gov/standards/iso639-2/php/code_list.php' ) . '">ISO 639-1</a>';
		$iso_3166 = '<a href="' . esc_url( 'https://www.iso.org/obp/ui/#search' ) . '">ISO 3166-2</a>';
		printf(
			'<p>%s</p><p>%s<br>%s</p><p>%s<br>%s<br>%s</p>',
			esc_html__( 'Check the user browser language / user keyboard. Add a country code / locale (US), language (en) or both (en-US) each line, in the latter case you must delimit with a "-" (Hyphen) character , you can insert them comma separated and when you save they will be formatted with the standard one per line.', 'cf7-antispam' ),
			esc_html__( 'The browser language detection and country detection are separated, you can choose one of them each line or both.', 'cf7-antispam' ),
			esc_html__( 'The language detection must be lower case, while the country detection upper case.', 'cf7-antispam' ),
			esc_html__( 'If you are unsure please consult these ISO standards:', 'cf7-antispam' ),
			esc_html__( '- Language codes (use ctrl+f for search) ', 'cf7-antispam' ) . wp_kses( $iso_639, array( 'a' => array( 'href' => array() ) ) ),
			esc_html__( '- Country codes (refer to "Alpha-2 code"), ', 'cf7-antispam' ) . wp_kses( $iso_3166, array( 'a' => array( 'href' => array() ) ) )
		);
	}

	/** It prints the bad_ip info text */
	public function cf7a_print_section_bad_ip() {
		printf( '<p>%s</p>', esc_html__( 'After an ip check via the http headers, it is checked that the ip is not blacklisted in the following list, one "bad" ip each line', 'cf7-antispam' ) );
	}

	/** It prints the bad_words info text */
	public function cf7a_print_section_bad_words() {
		printf( '<p>%s</p>', esc_html__( 'Check if the mail message contains "bad" words, all e-mails containing one of these words in the text will be flagged. A bad string per line', 'cf7-antispam' ) );
	}

	/** It prints the bad_email_strings info text */
	public function cf7a_print_section_bad_email_strings() {
		printf( '<p>%s</p>', esc_html__( 'Check if the mail content contains a word and in this case flag this mail, one forbidden word per line', 'cf7-antispam' ) );
	}

	/** It prints the user_agent info text */
	public function cf7a_print_user_agent() {
		printf( '<p>%s</p>', esc_html__( 'Enter a list of forbidden user agents, one per line. When the string match the user agent (or a part of) the mail will be flagged', 'cf7-antispam' ) );
	}

	/** It prints the dnsbl info text */
	public function cf7a_print_dnsbl() {
		printf( '<p>%s</p>', esc_html__( 'Check sender ip on DNS Blacklists, DNSBL are real-time lists of proven/recognised spam addresses. These may include lists of addresses of zombie computers or other machines used to send spam, Internet Service Providers (ISPs) that voluntarily host spammers, BUT they could also be users behind a proxy and that is why the method is no longer 100 per cent reliable. Add a DSNBL server url each line ', 'cf7-antispam' ) );
		/** Translators: %s%s%s - a spam score of xyz will be added  */
		printf( '<p><span class="cf7a-option-notice">%s%s%s</span></p>', esc_html__( '⚠️ Use FEW servers, those you tested reliable, and consider that for each server reporting the ip a spam score of ', 'cf7-antispam' ), floatval( $this->options['score']['_dnsbl'] ), esc_html__( ' will be added to the spam rating, 1 equal spam.', 'cf7-antispam' ) );
		printf(
			'<p>%s<a href="%s" target="_blank">%s</a></p>',
			esc_html__( 'Here a you can find a list of servers: ', 'cf7-antispam' ),
			esc_url( 'https://gist.github.com/search?q=dnsbl+list&ref=searchresults' ),
			esc_url_raw( 'gist.github.com/search?q=dnsbl+list' )
		);
	}

	/** It prints the honeypot info text */
	public function cf7a_print_honeypot() {
		printf(
			'<p>%s<p class="info monospace">[*] %s</p></p>',
			esc_html__( 'the honeypot is a "trap" field that is hidden with css or js from the user but remains visible to bots. Since this fields are automatically added and appended inside the forms with standard names.', 'cf7-antispam' ),
			esc_html__( 'Please check the list below because the name MUST differ from the cf7 tag class names', 'cf7-antispam' )
		);
	}

	/** It prints the honeyform info text */
	public function cf7a_print_honeyform() {
		printf( '<p>%s</p>', esc_html__( "Instead of relying on trap fields, we utilize honeyforms, that are forms specifically designed as deceptive 'trap' forms. Similar to honeypots, these forms are crafted to mislead and catch potential threats.", 'cf7-antispam' ) );
	}

	/** It prints the user protection info text */
	public function cf7a_print_mailbox_protection() {
		$expire = apply_filters( 'cf7a_resend_timeout', 5 );
		printf( '<p>%s</p><p>%s%s</p>', esc_html__( 'When activated, this feature prevents consecutive email deliveries to the user\'s mailbox by imposing delay between each message.', 'cf7-antispam' ), esc_html( $expire ), esc_html__( ' seconds has been set as the resend timeout, check the documentation if you want to change it', 'cf7-antispam' ) );
	}

	/** It prints the user protection info text */
	public function cf7a_print_identity_protection() {
		printf( '<p>%s</p>', esc_html__( 'After monitoring and analysing some bots, I noticed that it is necessary to block the way bots collect (user) data from the website, otherwise protecting the form may have no effect. This also blocks some registrations, spam comments and other attacks', 'cf7-antispam' ) );
	}

	/** It prints the b8 info text */
	public function cf7a_print_b8() {
		printf( '<p>%s</p>', esc_html__( 'Tells you whether a text is spam or not, using statistical text analysis of the text message', 'cf7-antispam' ) );
		printf( '<p>%s</p>', esc_html__( 'The threshold value is the minimum score required for a text to be considered spam. 1 is spam. 0 is not spam. If the threshold value is too low, you may receive false positives, while if it is too high, you may miss some spam.', 'cf7-antispam' ) );
	}

	/** It prints the customizations info */
	public function cf7a_print_customizations() {
		printf(
			'<p>%s</p><p>%s</p>',
			esc_html__( 'RECOMMENDED: Site related configuration', 'cf7-antispam' ),
			esc_html__( "create your own and unique css class and customized fields name. Optionally, you can choose in encryption method. But, After changing cypher do a couple of tests because a small amount of them aren't compatible with the format of the form data.", 'cf7-antispam' )
		);
	}

	/** It prints the scoring settings info */
	public function cf7a_print_scoring_settings() {
		printf( '<p>%s</p>', esc_html__( 'The calculation system of antispam for contact form 7 works like this: each failed test has its own score (shown below where you can refine it to your liking). If the mail at the end of all tests exceeds a value of 1, the mail is considered spam, and is consequently processed by b8, which analyses the text and learns the words of a spam mail.', 'cf7-antispam' ) );
	}

	/** It prints the advanced settings info */
	public function cf7a_print_advanced_settings() {
		printf( '<p>%s</p>', esc_html__( 'In this section you will find some advanced settings to manage the database', 'cf7-antispam' ) );
	}

	/**
	 * It takes an array of strings, removes any empty strings, and returns the array
	 *
	 * @param array $array The array to be cleaned.
	 *
	 * @return array an array of values that are not empty.
	 */
	private function cf7a_remove_empty_from_array( $array ) {
		if ( ! empty( $array ) && is_array( $array ) ) {
			$clean_item_collection = array();
			foreach ( $array as $value ) {
				$value = trim( $value );
				if ( $value ) {
					$clean_item_collection[] = $value;
				}
			}
			return $clean_item_collection;
		}
		return $array;
	}

	/**
	 * It takes a string of comma separated values and line-break separated value then returns an array of those values
	 *
	 * @param string $input - The user input with comma and spaces.
	 *
	 * @return array $new_input - The formatted input.
	 */
	private function cf7a_settings_format_user_input( $input ) {
		$new_input = preg_replace( '/\R/', ',', $input );

		return self::cf7a_remove_empty_from_array( explode( ',', $new_input ) );
	}

	/**
	 * It returns an array of preset scores
	 *
	 * @return array An array of arrays.
	 */
	public function cf7a_get_scores_presets() {
		return array(
			'weak'     => array(
				'_fingerprinting' => 0.1,
				'_time'           => 0.3,
				'_bad_string'     => 0.5,
				'_dnsbl'          => 0.1,
				'_honeypot'       => 0.3,
				'_detection'      => 0.7,
				'_warn'           => 0.3,
			),
			'standard' => array(
				'_fingerprinting' => 0.15,
				'_time'           => 0.5,
				'_bad_string'     => 1,
				'_dnsbl'          => 0.15,
				'_honeypot'       => 0.5,
				'_detection'      => 1,
				'_warn'           => 0.5,
			),
			'secure'   => array(
				'_fingerprinting' => 0.25,
				'_time'           => 1,
				'_bad_string'     => 1,
				'_dnsbl'          => 0.2,
				'_honeypot'       => 1,
				'_detection'      => 5,
				'_warn'           => 1,
			),
		);
	}

	/**
	 * If the user has enabled the GeoIP feature schedule the download of the database, and the GeoIP database is not yet downloaded, download it
	 * if the user has disabled the GeoIP feature, unscheduled the download event
	 *
	 * @param 1|0 $enabled input The input value.
	 */
	public function cf7a_enable_geo( $enabled ) {
		if ( 0 === $enabled ) {
			/* delete the geo db next update stored option and the scheduled event */
			$timestamp = wp_next_scheduled( 'cf7a_geoip_update_db' );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'cf7a_geoip_update_db' );
			}
		} elseif ( $this->geoip->has_license() ) {
			/* Otherwise schedule update / download the database if needed */
			if ($this->geoip->maybe_download() !== false) {
				$this->geoip->schedule_update();
			};
		}
	}

	/**
	 * Handles WP-cron task registrations
	 *
	 * @param array  $input      - The post-input values.
	 * @param string $input_name - The value of the input field.
	 * @param string $cron_task  - The slug of the Post value.
	 * @param array  $schedule   - The schedule list obtained with wp_get_schedules().
	 *
	 * @return array|false the new value that the user has selected
	 */
	private function cf7a_input_cron_schedule( $input, $input_name, $cron_task, $schedule ) {
		$new_value = $this->options[ $input_name ];

		// if the value has changed
		if ( $this->options[ $input_name ] !== $input[ $input_name ] ) {
			// if the user has disabled the cron task
			if ( $input[ $input_name ] === 'disabled' ) {

				/* Get the timestamp for the next event and unschedule it. */
				wp_clear_scheduled_hook( 'cf7a_cron' );
				return 'disabled';
			}

			// if the value is not empty and is a valid schedule
			if ( ! empty( $input[ $input_name ] ) && in_array( $input[ $input_name ], array_keys( $schedule ), true ) ) {
				// if the user has enabled the cron task and selected a schedule
				$new_value = $input[ $input_name ];

				/* delete all the previous scheduled events */
				wp_clear_scheduled_hook( 'cf7a_cron' );

				/* add the new scheduled event */
				$interval_seconds = isset( $schedule[ $new_value ]['interval'] ) ? (int) $schedule[ $new_value ]['interval'] : 0;

				if ( $interval_seconds > 0 ) {
					$next_run = time() + $interval_seconds;

					wp_schedule_event( $next_run, $new_value, $cron_task );
				} else {
					cf7a_log( 'Unable to schedule event for ' . $cron_task );
				}
			}
		}

		// return the new value
		return $new_value;
	}


	private function cf7a_clean_agnostic( $value ) {
		if ( is_bool( $value ) ) {
			$input = boolval( $value );
		} elseif ( is_numeric( $value ) ) {
			$input = floatval( $value );
		} else {
			$input = sanitize_text_field( $value );
		}
		return $input;
	}

	/**
	 * Clean and sanitize a value recursively.
	 *
	 * @param string $key The key of the value to be cleaned.
	 * @param mixed  $value The value to be cleaned.
	 *
	 * @return array|bool|int|string
	 */
	private function cf7a_clean_recursive( $json_data ) {
		$input = array();
		foreach ( $json_data as $key => $value ) {
			if ( is_array( $value ) || is_object( $value ) ) {
				$input[ $key ] = $this->cf7a_clean_recursive( $value );
			} else {
				$input[ $key ] = $this->cf7a_clean_agnostic( $value );
			}
		}
		return $input;
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys.
	 * @return array $options sanitized
	 */
	public function cf7a_sanitize_options( $input ) {
		/* get the import options */
		$new_input = $this->options;

		if ( isset( $_POST['to-import'] ) and isset( $_POST['cf7a-nonce'] ) and wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['cf7a-nonce'] ) ), 'cf7a-nonce' ) ) {

			$to_import = sanitize_text_field(wp_unslash($_POST['to-import'] ) );
			$json_data = json_decode( $to_import );

			if ( ! empty( $json_data ) && is_object( $json_data ) ) {
				$input = $this->cf7a_clean_recursive( $json_data );
				$input['bad_ip_list']                     = implode( ',', $input['bad_ip_list'] );
				$input['ip_whitelist']                    = implode( ',', $input['ip_whitelist'] );
				$input['bad_email_strings_list']          = implode( ',', $input['bad_email_strings_list'] );
				$input['bad_user_agent_list']             = implode( ',', $input['bad_user_agent_list'] );
				$input['dnsbl_list']                      = implode( ',', $input['dnsbl_list'] );
				$input['honeypot_input_names']            = implode( ',', $input['honeypot_input_names'] );
				$input['bad_words_list']                  = implode( ',', $input['bad_words_list'] );
				$input['languages_locales']['allowed']    = implode( ',', $input['languages_locales']['allowed'] );
				$input['languages_locales']['disallowed'] = implode( ',', $input['languages_locales']['disallowed'] );
				$input['cf7a_enabled']                    = 1;
				$input['cf7a_enable']                     = 1;
				$input['cf7a_version']                    = CF7ANTISPAM_VERSION;
			} else {
				cf7a_log( print_r( $to_import, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
				cf7a_log( 'CF7 AntiSpam: The import data is invalid' );
				return $this->options;
			}
		}

		$new_input['cf7a_enabled'] = isset( $input['cf7a_enabled'] ) ? 1 : 0;

		$new_input['cf7a_enable'] = isset( $input['cf7a_enable'] ) ? $input['cf7a_enable'] : $new_input['cf7a_enable'];

		/* bot fingerprint */
		$new_input['check_bot_fingerprint']        = isset( $input['check_bot_fingerprint'] ) ? 1 : 0;
		$new_input['check_bot_fingerprint_extras'] = isset( $input['check_bot_fingerprint_extras'] ) ? 1 : 0;
		$new_input['append_on_submit']             = isset( $input['append_on_submit'] ) ? 1 : 0;

		/* elapsed time */
		$new_input['check_time'] = isset( $input['check_time'] ) ? 1 : 0;

		$new_input['check_time_min'] = isset( $input['check_time_min'] ) ? intval( $input['check_time_min'] ) : 6;
		$new_input['check_time_max'] = isset( $input['check_time_max'] ) ? intval( $input['check_time_max'] ) : ( 60 * 60 * 25 ); /* a day + 1 hour of timeframe to send the mail seems fine :) */

		/**
		 * Checking if the enable_geoip_download is not set (note the name is $new_input but actually is the copy of the stored options)
		 * and the user has chosen to enable the geoip, in this case download the database if needed
		 */
		if ( ! empty( $new_input['enable_geoip_download'] ) ) {
			$this->cf7a_enable_geo( $new_input['enable_geoip_download'] );
		}

		$new_input['enable_geoip_download'] = isset( $input['enable_geoip_download'] ) ? 1 : 0;

		// if the download is disabled, check if the database is uploaded
		if ( ! $new_input['enable_geoip_download'] )  {

			// Get the file name
			if (! empty($_FILES) && !empty($_FILES['geoip_dbfile'])) {
				// Fix for the file type check
				add_filter( 'wp_check_filetype_and_ext', function ( $types, $file, $filename) {
					if( 'tar.gz' === substr( $filename, -6 ) ) {
						$types['ext'] = 'tar.gz';
						$types['type'] = 'application/gzip';
					}
					return $types;
				}, 10, 3 );

				// Validate the uploaded file - The second parameter $overrides enables security
				$upload = wp_handle_upload( $_FILES['geoip_dbfile'], array(
					'test_form' => false,
					'mimes'     => array(
						'mmdb'   => 'application/octet-stream',
						'tar.gz' => 'application/gzip',
					),
				) );

				if ( ! empty( $upload['error'] ) ) {
					// If the file upload failed
					if ( $upload['error'] !== UPLOAD_ERR_NO_FILE ) {
						CF7_AntiSpam_Admin_Tools::cf7a_push_notice(
							sprintf(
								 /* translators: %s is the error message */
								 esc_html__("Error uploading file: %s", 'cf7-antispam' ), $upload['error']
							)
						);
					}
					// Continue
				} else {
					// Upload success
					$temp = $upload["file"];
					$result = $this->geoip->manual_upload( $temp );
					if ( $result ) {
						CF7_AntiSpam_Admin_Tools::cf7a_push_notice(
							esc_html__( 'GeoIP database uploaded successfully.', 'cf7-antispam' )
						);
					} else {
						CF7_AntiSpam_Admin_Tools::cf7a_push_notice(
							esc_html__( 'Error processing the uploaded file.', 'cf7-antispam' )
						);
					}
				}
			}
		}

		$new_input['geoip_dbkey'] = isset( $input['geoip_dbkey'] ) ? sanitize_textarea_field( $input['geoip_dbkey'] ) : false;

		/* browser language check enabled */
		$new_input['check_language'] = ! empty( $input['check_language'] ) ? 1 : 0;

		/* geo-ip location check enabled */
		$new_input['check_geo_location'] = isset( $input['check_geo_location'] ) ? 1 : 0;

		/* languages allowed | disallowed */
		$new_input['languages_locales']['allowed']    = isset( $input['languages_locales']['allowed'] )
			? $this->cf7a_settings_format_user_input( sanitize_textarea_field( $input['languages_locales']['allowed'] ) )
			: array();
		$new_input['languages_locales']['disallowed'] = isset( $input['languages_locales']['disallowed'] )
			? $this->cf7a_settings_format_user_input( sanitize_textarea_field( $input['languages_locales']['disallowed'] ) )
			: array();

		/* max attempts before ban */
		$new_input['max_attempts'] = isset( $input['max_attempts'] ) ? intval( $input['max_attempts'] ) : 3;

		/* auto-ban */
		$new_input['autostore_bad_ip'] = isset( $input['autostore_bad_ip'] ) ? 1 : 0;

		/* auto-unban delay */
		$schedule = wp_get_schedules();

		/* unban after */
		$new_input['unban_after'] = $this->cf7a_input_cron_schedule( $input, 'unban_after', 'cf7a_cron', $schedule );

		/* bad ip */
		$new_input['check_refer']  = isset( $input['check_refer'] ) ? 1 : 0;
		$new_input['check_bad_ip'] = isset( $input['check_bad_ip'] ) ? 1 : 0;
		if ( isset( $input['bad_ip_list'] ) && is_string( $input['bad_ip_list'] ) ) {
			$new_input['bad_ip_list'] = $this->cf7a_settings_format_user_input( sanitize_textarea_field( $input['bad_ip_list'] ) );
		}
		if ( isset( $input['ip_whitelist'] ) && is_string( $input['ip_whitelist'] ) ) {
			$new_input['ip_whitelist'] = $this->cf7a_settings_format_user_input( sanitize_textarea_field( $input['ip_whitelist'] ) );
		}

		/* bad words */
		$new_input['check_bad_words'] = isset( $input['check_bad_words'] ) ? 1 : 0;
		if ( isset( $input['bad_words_list'] ) ) {
			$new_input['bad_words_list'] = $this->cf7a_settings_format_user_input( sanitize_textarea_field( $input['bad_words_list'] ) );
		} else {
			$new_input['bad_words_list'] = array();
		}

		/* email strings */
		$new_input['check_bad_email_strings'] = isset( $input['check_bad_email_strings'] ) ? 1 : 0;
		if ( isset( $input['bad_email_strings_list'] ) ) {
			$new_input['bad_email_strings_list'] = $this->cf7a_settings_format_user_input( sanitize_textarea_field( $input['bad_email_strings_list'] ) );
		} else {
			$new_input['bad_email_strings_list'] = array();
		}

		/* user_agent */
		$new_input['check_bad_user_agent'] = isset( $input['check_bad_user_agent'] ) ? 1 : 0;
		if ( isset( $input['bad_user_agent_list'] ) ) {
			$new_input['bad_user_agent_list'] = $this->cf7a_settings_format_user_input( sanitize_textarea_field( $input['bad_user_agent_list'] ) );
		} else {
			$new_input['bad_user_agent_list'] = array();
		}

		/* dnsbl */
		$new_input['check_dnsbl'] = isset( $input['check_dnsbl'] ) ? 1 : 0;
		if ( isset( $input['dnsbl_list'] ) ) {
			$new_input['dnsbl_list'] = $this->cf7a_settings_format_user_input( sanitize_textarea_field( $input['dnsbl_list'] ) );
		} else {
			$new_input['dnsbl_list'] = array();
		}

		/* honeypot */
		$new_input['check_honeypot'] = isset( $input['check_honeypot'] ) ? 1 : 0;
		if ( isset( $input['honeypot_input_names'] ) ) {
			$new_input['honeypot_input_names'] = $this->cf7a_settings_format_user_input( sanitize_textarea_field( $input['honeypot_input_names'] ) );
		} else {
			$new_input['honeypot_input_names'] = array();
		}

		/* honeyform */
		$new_input['check_honeyform']          = isset( $input['check_honeyform'] ) ? 1 : 0;
		$new_input['honeyform_position']       = ! empty( $input['honeyform_position'] ) ? sanitize_title( $input['honeyform_position'] ) : 'wp_body_open';
		$new_input['honeyform_excluded_pages'] = ! empty( $input['honeyform_excluded_pages'] ) ? cf7a_str_array_to_uint_array( $input['honeyform_excluded_pages'] ) : array();

		/* identity protection */
		$new_input['mailbox_protection_multiple_send'] = isset( $input['mailbox_protection_multiple_send'] ) ? 1 : 0;

		/* identity protection */
		$new_input['identity_protection_user'] = isset( $input['identity_protection_user'] ) ? 1 : 0;
		$new_input['identity_protection_wp']   = isset( $input['identity_protection_wp'] ) ? 1 : 0;

		/* b8 */
		$new_input['enable_b8']    = isset( $input['enable_b8'] ) ? 1 : 0;
		$threshold                 = floatval( $input['b8_threshold'] );
		$new_input['b8_threshold'] = $threshold >= 0 && $threshold < 1 ? $threshold : 1;

		/* Advanced settings */
		$new_input['enable_advanced_settings'] = isset( $input['enable_advanced_settings'] ) ? 1 : 0;
		$score_preset                          = $this->cf7a_get_scores_presets();

		$preset_changed = ( $input['cf7a_score_preset'] !== $this->options['cf7a_score_preset'] );
		$scores_changed = ( $input['score'] != $this->options['score'] );

		/* Scoring: if the preset name is equal to $selected and (the old score is the same of the new one OR the preset score $selected is changed) */
		if ( $preset_changed ) {
			// User selected a different preset - use preset values
			if ( in_array( $input['cf7a_score_preset'], array( 'weak', 'standard', 'secure' ) ) ) {
				$new_input['score']             = $score_preset[ $input['cf7a_score_preset'] ];
				$new_input['cf7a_score_preset'] = $input['cf7a_score_preset'];
			}
		} elseif ( $scores_changed ) {
			// User manually changed scores (preset didn't change) - use custom values
			$new_input['score']['_fingerprinting'] = isset( $input['score']['_fingerprinting'] ) ? floatval( $input['score']['_fingerprinting'] ) : 0.25;
			$new_input['score']['_time']           = isset( $input['score']['_time'] ) ? floatval( $input['score']['_time'] ) : 1;
			$new_input['score']['_bad_string']     = isset( $input['score']['_bad_string'] ) ? floatval( $input['score']['_bad_string'] ) : 1;
			$new_input['score']['_dnsbl']          = isset( $input['score']['_dnsbl'] ) ? floatval( $input['score']['_dnsbl'] ) : 0.2;
			$new_input['score']['_honeypot']       = isset( $input['score']['_honeypot'] ) ? floatval( $input['score']['_honeypot'] ) : 1;
			$new_input['score']['_detection']      = isset( $input['score']['_detection'] ) ? floatval( $input['score']['_detection'] ) : 5;
			$new_input['score']['_warn']           = isset( $input['score']['_warn'] ) ? floatval( $input['score']['_warn'] ) : 1;
			$new_input['cf7a_score_preset']        = 'custom';
		}

		/* Customizations */
		$new_input['cf7a_disable_reload'] = isset( $input['cf7a_disable_reload'] ) ? 1 : 0;

		$input['cf7a_customizations_class']     = sanitize_html_class( $input['cf7a_customizations_class'] );
		$new_input['cf7a_customizations_class'] = ! empty( $input['cf7a_customizations_class'] ) ? sanitize_html_class( $input['cf7a_customizations_class'] ) : CF7ANTISPAM_HONEYPOT_CLASS;

		$input['cf7a_customizations_prefix']     = sanitize_html_class( $input['cf7a_customizations_prefix'] );
		$new_input['cf7a_customizations_prefix'] = ! empty( $input['cf7a_customizations_prefix'] ) ? sanitize_html_class( $input['cf7a_customizations_prefix'] ) : CF7ANTISPAM_PREFIX;

		$input['cf7a_cipher']     = sanitize_html_class( $input['cf7a_cipher'] );
		$new_input['cf7a_cipher'] = ! empty( $input['cf7a_cipher'] ) && in_array( $input['cf7a_cipher'], openssl_get_cipher_methods(), true ) ? $input['cf7a_cipher'] : CF7ANTISPAM_CYPHER;

		/* store the sanitized options */
		return $new_input;
	}

	/**
	 * Utility that generates the options for a select input given an array of values
	 *
	 * @param array  $values - the array of selection options.
	 * @param string $selected - the name of the selected one (if any).
	 *
	 * @return string - the html needed inside the select
	 */
	private function cf7a_generate_options( $values, $selected = '' ) {
		$html = '';
		foreach ( $values as $value ) {
			$html .= sprintf(
				'<option value="%s"%s>%s</option>',
				sanitize_title( $value ),
				$value === $selected ? ' selected' : '',
				$value
			);
		}
		return $html;
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function cf7a_autostore_bad_ip_callback() {
		printf(
			'<input type="checkbox" id="autostore_bad_ip" name="cf7a_options[autostore_bad_ip]" %s />',
			! empty( $this->options['autostore_bad_ip'] ) ? 'checked="true"' : ''
		);
	}

	/**
	 * It creates a text input field with the id of "max_attempts" and the name of "cf7a_options[max_attempts]". The value of
	 * the field is set to the value of the "max_attempts" key in the $this->options array. If the key doesn't exist, the
	 * value is set to 2
	 */
	public function cf7a_max_attempts() {
		printf(
			'<input type="number" id="max_attempts" name="cf7a_options[max_attempts]" value="%s" step="1" />',
			! empty( $this->options['max_attempts'] ) ? esc_attr( $this->options['max_attempts'] ) : 2
		);
	}

	/**
	 * It generates a select box with the options 'disabled', '60sec', '5min', 'hourly', 'twicedaily', 'daily', 'weekly' and
	 * the default value is 'disabled'
	 */
	public function cf7a_unban_after_callback() {
		/* the list of available schedules */
		$schedules         = wp_get_schedules();
		$valid_schedules   = array_keys(
			array_filter(
				$schedules,
				function ( $s ) {
					return ! empty( $s['interval'] ) && $s['interval'] > 0;
				}
			)
		);
		$valid_schedules[] = 'disabled';

		printf(
			'<select id="unban_after" name="cf7a_options[unban_after]">%s</select>',
			wp_kses(
				$this->cf7a_generate_options(
					$valid_schedules,
					! empty( $this->options['unban_after'] ) ? $this->options['unban_after'] : ''
				),
				array(
					'option' => array(
						'value'    => array(),
						'selected' => array(),
					),
				)
			)
		);
	}

	/** It creates the input field "check_bot_fingerprint" */
	public function cf7a_check_bot_fingerprint_callback() {
		printf(
			'<input type="checkbox" id="check_bot_fingerprint" name="cf7a_options[check_bot_fingerprint]" %s />',
			! empty( $this->options['check_bot_fingerprint'] ) ? 'checked="true"' : ''
		);
	}
	/** It creates the input field "check_bot_fingerprint_extras" */
	public function cf7a_check_bot_fingerprint_extras_callback() {
		printf(
			'<input type="checkbox" id="check_bot_fingerprint_extras" name="cf7a_options[check_bot_fingerprint_extras]" %s />',
			! empty( $this->options['check_bot_fingerprint_extras'] ) ? 'checked="true"' : ''
		);
	}
	/** It creates the input field "cf7a_append_on_submit_callback" */
	public function cf7a_append_on_submit_callback() {
		printf(
			'<input type="checkbox" id="append_on_submit" name="cf7a_options[append_on_submit]" %s />',
			! empty( $this->options['append_on_submit'] ) ? 'checked="true"' : ''
		);
	}

	/** It creates the input field "cf7a_check_time_callback" */
	public function cf7a_check_time_callback() {
		printf(
			'<input type="checkbox" id="check_time" name="cf7a_options[check_time]" %s />',
			! empty( $this->options['check_time'] ) ? 'checked="true"' : ''
		);
	}
	/** It creates the input field "cf7a_check_time_min_callback" */
	public function cf7a_check_time_min_callback() {
		printf(
			'<input type="number" id="check_time_min" name="cf7a_options[check_time_min]" value="%s" step="1" />',
			! empty( $this->options['check_time_min'] ) ? esc_attr( $this->options['check_time_min'] ) : 6
		);
	}
	/** It creates the input field "cf7a_check_time_max_callback" */
	public function cf7a_check_time_max_callback() {
		printf(
			'<input type="number" id="check_time_max" name="cf7a_options[check_time_max]" value="%s" step="1" />',
			! empty( $this->options['check_time_max'] ) ? esc_attr( $this->options['check_time_max'] ) : intval( YEAR_IN_SECONDS )
		);
	}

	/** It creates the input field "cf7a_enable_geoip_callback" */
	public function cf7a_enable_geoip_callback() {
		printf(
			'<input type="checkbox" id="enable_geoip_download" name="cf7a_options[enable_geoip_download]" %s />',
			! empty( $this->options['enable_geoip_download'] ) ? 'checked="true"' : ''
		);
	}

	/** Force database download button*/
	public function cf7a_force_download_callback() {
		// the upload button for the database if the download is disabled
		printf( '<input type="button" id="geoip_force_download" class="cf7a_action" data-action="force-geoip-download" data-nonce="%s" value="%s" />',
			wp_create_nonce( 'cf7a-nonce' ),
			esc_attr__( 'Force Download', 'cf7-antispam' )
		);
	}

	/** Database manual upload */
	public function cf7a_enable_geoip_manual_upload_callback() {
		// the upload button for the database if the download is disabled
		echo '<input type="file" id="geoip_dbfile" name="geoip_dbfile" accept=".mmdb,.tar.gz" />';
	}

	/** It creates the input field "cf7a_geodb_update" */
	public function cf7a_geoip_is_enabled_callback() {
		printf( $this->geoip->has_database() ? '✅ ' : '❌ ' );
	}

	/**
	 * It prints out an input field with the value of the option 'geoip_dbkey' if it exists, and if it doesn't exist, it
	 * prints out an empty input field
	 */
	public function cf7a_geoip_key_callback() {
		printf(
			'<input type="text" id="geoip_dbkey" name="cf7a_options[geoip_dbkey]" %s %s/>',
			empty( $this->options['geoip_dbkey'] ) ? '' : 'value="' . esc_attr( $this->options['geoip_dbkey'] ) . '"',
			// phpcs:ignore WordPress.Security.EscapeOutput
			empty( CF7ANTISPAM_GEOIP_KEY ) ? '' : 'disabled placeholder="' . esc_attr__( 'KEY provided', 'cf7-antispam' ) . '"'
		);
	}

	/** It creates the input field "cf7a_check_browser_language" */
	public function cf7a_check_browser_language_callback() {
		printf(
			'<input type="checkbox" id="check_language" name="cf7a_options[check_language]" %s />',
			! empty( $this->options['check_language'] ) ? 'checked="true"' : ''
		);
	}

	/** It creates the input field "cf7a_check_geo_location" */
	public function cf7a_check_geo_location_callback() {
		$geo_disabled = $this->geoip->has_database() ? '' : 'disabled';
		printf(
			'<input type="checkbox" id="check_geo_location" name="cf7a_options[check_geo_location]" %s %s />',
			! empty( $this->options['check_geo_location'] ) ? esc_html( 'checked="true"' ) : '',
			esc_attr( $geo_disabled )
		);
	}

	/** It creates the input field "cf7a_language_allowed" */
	public function cf7a_language_allowed() {
		printf(
			'<textarea id="languages_allowed" name="cf7a_options[languages_locales][allowed]" />%s</textarea>',
			isset( $this->options['languages_locales']['allowed'] ) && is_array( $this->options['languages_locales']['allowed'] ) ? esc_textarea( implode( "\r\n", $this->options['languages_locales']['allowed'] ) ) : ''
		);
	}

	/** It creates the input field "cf7a_language_disallowed" */
	public function cf7a_language_disallowed() {
		printf(
			'<textarea id="languages_disallowed" name="cf7a_options[languages_locales][disallowed]" />%s</textarea>',
			isset( $this->options['languages_locales']['disallowed'] ) && is_array( $this->options['languages_locales']['disallowed'] ) ? esc_textarea( implode( "\r\n", $this->options['languages_locales']['disallowed'] ) ) : ''
		);
	}

	/** It creates the input field "cf7a_print_check_refer" */
	public function cf7a_print_check_refer() {
		printf(
			'<input type="checkbox" id="check_refer" name="cf7a_options[check_refer]" %s />',
			! empty( $this->options['check_refer'] ) ? 'checked="true"' : ''
		);
	}

	/** It creates the input field "cf7a_check_bad_ip" */
	public function cf7a_check_bad_ip_callback() {
		printf(
			'<input type="checkbox" id="check_bad_ip" name="cf7a_options[check_bad_ip]" %s />',
			! empty( $this->options['check_bad_ip'] ) ? 'checked="true"' : ''
		);
	}

	/**
	 * It creates a textarea with the id of "bad_ip_list"
	 */
	public function cf7a_bad_ip_list_callback() {
		printf(
			'<textarea id="bad_ip_list" name="cf7a_options[bad_ip_list]" />%s</textarea>',
			isset( $this->options['bad_ip_list'] ) && is_array( $this->options['bad_ip_list'] ) ? esc_textarea( implode( "\r\n", $this->options['bad_ip_list'] ) ) : ''
		);
	}

	/**
	 * It creates a textarea with the id of "ip whitelist"
	 */
	public function cf7a_ip_whitelist_callback() {
		printf(
			'<textarea id="ip_whitelist" name="cf7a_options[ip_whitelist]" />%s</textarea>',
			isset( $this->options['ip_whitelist'] ) && is_array( $this->options['ip_whitelist'] ) ? esc_textarea( implode( "\r\n", $this->options['ip_whitelist'] ) ) : ''
		);
	}

	/** It creates a checkbox with the id of "check_bad_words" */
	public function cf7a_bad_words_callback() {
		printf(
			'<input type="checkbox" id="check_bad_words" name="cf7a_options[check_bad_words]" %s />',
			! empty( $this->options['check_bad_words'] ) ? 'checked="true"' : ''
		);
	}

	/** It creates a checkbox with the id of "cf7a_bad_words_list" */
	public function cf7a_bad_words_list_callback() {
		printf(
			'<textarea id="bad_words_list" name="cf7a_options[bad_words_list]" />%s</textarea>',
			isset( $this->options['bad_words_list'] ) && is_array( $this->options['bad_words_list'] ) ? esc_textarea( implode( "\r\n", $this->options['bad_words_list'] ) ) : ''
		);
	}

	/** It creates a checkbox with the id of "cf7a_check_bad_email_strings_callback" */
	public function cf7a_check_bad_email_strings_callback() {
		printf(
			'<input type="checkbox" id="check_bad_email_strings" name="cf7a_options[check_bad_email_strings]" %s />',
			! empty( $this->options['check_bad_email_strings'] ) ? 'checked="true"' : ''
		);
	}

	/** It creates a checkbox with the id of "cf7a_bad_email_strings_list_callback" */
	public function cf7a_bad_email_strings_list_callback() {
		printf(
			'<textarea id="bad_email_strings_list" name="cf7a_options[bad_email_strings_list]" />%s</textarea>',
			isset( $this->options['bad_email_strings_list'] ) && is_array( $this->options['bad_email_strings_list'] ) ? esc_textarea( implode( "\r\n", $this->options['bad_email_strings_list'] ) ) : ''
		);
	}

	/** It creates a checkbox with the id of "cf7a_check_user_agent_callback" */
	public function cf7a_check_user_agent_callback() {
		printf(
			'<input type="checkbox" id="check_bad_user_agent" name="cf7a_options[check_bad_user_agent]" %s />',
			! empty( $this->options['check_bad_user_agent'] ) ? 'checked="true"' : ''
		);
	}

	/** It creates a checkbox with the id of "cf7a_user_agent_list_callback" */
	public function cf7a_user_agent_list_callback() {
		printf(
			'<textarea id="bad_user_agent_list" name="cf7a_options[bad_user_agent_list]" />%s</textarea>',
			isset( $this->options['bad_user_agent_list'] ) && is_array( $this->options['bad_user_agent_list'] )
				? esc_textarea( implode( "\r\n", $this->options['bad_user_agent_list'] ) )
				: ''
		);
	}

	/** It creates a checkbox with the id of "cf7a_check_dnsbl_callback" */
	public function cf7a_check_dnsbl_callback() {
		printf(
			'<input type="checkbox" id="check_dnsbl" name="cf7a_options[check_dnsbl]" %s />',
			! empty( $this->options['check_dnsbl'] ) ? 'checked="true"' : ''
		);
	}

	/** It creates a checkbox with the id of "cf7a_dnsbl_list_callback" */
	public function cf7a_dnsbl_list_callback() {
		printf(
			'<textarea id="dnsbl_list" name="cf7a_options[dnsbl_list]" />%s</textarea>',
			isset( $this->options['dnsbl_list'] ) && is_array( $this->options['dnsbl_list'] ) ? esc_textarea( implode( "\r\n", $this->options['dnsbl_list'] ) ) : ''
		);
	}


	/** It creates a checkbox with the id of "cf7a_enable_honeypot_callback" */
	public function cf7a_enable_honeypot_callback() {
		printf(
			'<input type="checkbox" id="check_honeypot" name="cf7a_options[check_honeypot]" %s />',
			! empty( $this->options['check_honeypot'] ) ? 'checked="true"' : ''
		);
	}

	/** It creates a checkbox with the id of "cf7a_honeypot_input_names_callback" */
	public function cf7a_honeypot_input_names_callback() {
		printf(
			'<textarea id="honeypot_input_names" name="cf7a_options[honeypot_input_names]" />%s</textarea>',
			isset( $this->options['honeypot_input_names'] ) && is_array( $this->options['honeypot_input_names'] ) ? esc_textarea( implode( "\r\n", $this->options['honeypot_input_names'] ) ) : ''
		);
	}


	/** It creates a checkbox with the id of "cf7a_enable_honeyform_callback" */
	public function cf7a_enable_honeyform_callback() {
		printf(
			'<input type="checkbox" id="check_honeyform" name="cf7a_options[check_honeyform]" %s />',
			! empty( $this->options['check_honeyform'] ) ? 'checked="true"' : ''
		);
	}

	/** It creates a checkbox with the id of "cf7a_honeyform_position_callback" */
	public function cf7a_honeyform_position_callback() {
		printf(
			'<select id="honeyform_position" name="cf7a_options[honeyform_position]">%s</select>',
			wp_kses(
				$this->cf7a_generate_options( array( 'before content', 'after content' ), isset( $this->options['honeyform_position'] ) ? esc_attr( $this->options['honeyform_position'] ) : '' ),
				array(
					'option' => array(
						'value'    => array(),
						'selected' => array(),
					),
				)
			)
		);
	}

	/**
	 * CF7_AntiSpam_Admin_Customizations.php
	 *
	 * This file contains a function that generates HTML code for a form in the WordPress admin panel.
	 * The form allows the user to select pages to be excluded from the CF7 AntiSpam plugin's functionality.
	 * The function retrieves all pages from the WordPress database and populates two select windows.
	 * The user can add pages from the first dropdown to the second dropdown and remove pages from the second dropdown.
	 * The selected pages are saved as options in the WordPress database.
	 */
	public function cf7a_honeyform_excluded_pages_callback() {
		$args  = array(
			'post_type' => 'page', // change this to the post type you're querying
			'fields'    => 'ids',  // get only ids
			'posts_per_page' => -1 // get all posts
		);
		$query = new WP_Query( $args );

		$options = '';

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$options .= '<option value="' . get_the_ID() . '">' . get_the_title() . '</option>';
			}
		}

		$excluded     = isset( $this->options['honeyform_excluded_pages'] ) ? $this->options['honeyform_excluded_pages'] : array();
		$str_excluded = '';
		if ( is_array( $excluded ) ) {
			foreach ( $excluded as $entry ) {
				$str_excluded .= '<option selected="true" value="' . $entry . '">' . get_the_title( $entry ) . '</option>';
			}
		}
		wp_reset_postdata();
		$allowed_html = array(
			'option' => array(
				'selected' => array(),
				'value'    => array(),
			),
		);
		printf(
			'<div class="honeyform-container">
						 <div class="row">
							  <div class="add">
								<select name="add" multiple class="form-control add-select">
								  %s
								</select>
								<div class="button button-primary honeyform-action add-list">%s ></div>
							  </div>
							  <div class="remove">
								<select id="honeyform_excluded_pages" name="cf7a_options[honeyform_excluded_pages][]" multiple="multiple" class="form-control remove-select" >
								%s
								</select>
								<div class="button button-primary honeyform-action remove-list">< %s</div>
							  </div>
						 </div>
					 </div>',
			wp_kses( $options, $allowed_html ),
			esc_html__( 'Add', 'cf7-antispam' ),
			wp_kses( $str_excluded, $allowed_html ),
			esc_html__( 'Remove', 'cf7-antispam' )
		);
	}

	/** It creates a checkbox with the id of "cf7a_identity_protection_user_callback" */
	public function cf7a_mailbox_protection_multiple_send_callback() {
		printf(
			'<input type="checkbox" id="mailbox_protection_multiple_send" name="cf7a_options[mailbox_protection_multiple_send]" %s />',
			! empty( $this->options['mailbox_protection_multiple_send'] ) ? 'checked="true"' : ''
		);
	}

	/** It creates a checkbox with the id of "cf7a_identity_protection_user_callback" */
	public function cf7a_identity_protection_user_callback() {
		printf(
			'<input type="checkbox" id="identity_protection_user" name="cf7a_options[identity_protection_user]" %s />',
			! empty( $this->options['identity_protection_user'] ) ? 'checked="true"' : ''
		);
	}

	/** It creates a checkbox with the id of "cf7a_identity_protection_user_callback" */
	public function cf7a_identity_protection_wp_callback() {
		printf(
			'<input type="checkbox" id="identity_protection_wp" name="cf7a_options[identity_protection_wp]" %s />',
			! empty( $this->options['identity_protection_wp'] ) ? 'checked="true"' : ''
		);
	}

	/** It creates a checkbox with the id of "cf7a_enable_b8_callback" */
	public function cf7a_enable_b8_callback() {
		printf(
			'<input type="checkbox" id="enable_b8" name="cf7a_options[enable_b8]" %s />',
			! empty( $this->options['enable_b8'] ) ? 'checked="true"' : ''
		);
	}

	/** It creates a checkbox with the id of "cf7a_b8_threshold_callback" */
	public function cf7a_b8_threshold_callback() {
		printf(
			'<input type="number" id="b8_threshold" name="cf7a_options[b8_threshold]" value="%s" min="0" max="1" step="0.01" /> <small>(0-1)</small>',
			isset( $this->options['b8_threshold'] ) ? esc_attr( $this->options['b8_threshold'] ) : 'none'
		);
	}


	/** It creates a checkbox with the id of "cf7a_disable_reload_callback" */
	public function cf7a_disable_reload_callback() {
		printf(
			'<input type="checkbox" id="cf7a_disable_reload" name="cf7a_options[cf7a_disable_reload]" %s />',
			! empty( $this->options['cf7a_disable_reload'] ) ? 'checked="true"' : ''
		);
	}
	/** It creates a checkbox with the id of "cf7a_customizations_class_callback" */
	public function cf7a_customizations_class_callback() {
		printf(
			'<input type="text" id="cf7a_customizations_class" name="cf7a_options[cf7a_customizations_class]" value="%s"/>',
			isset( $this->options['cf7a_customizations_class'] ) ? sanitize_html_class( $this->options['cf7a_customizations_class'] ) : sanitize_html_class( CF7ANTISPAM_HONEYPOT_CLASS )
		);
	}

	/** It creates a checkbox with the id of "cf7a_customizations_prefix_callback" */
	public function cf7a_customizations_prefix_callback() {
		printf(
			'<input type="text" id="cf7a_customizations_prefix" name="cf7a_options[cf7a_customizations_prefix]" value="%s"/>',
			isset( $this->options['cf7a_customizations_prefix'] ) ? sanitize_html_class( $this->options['cf7a_customizations_prefix'] ) : sanitize_html_class( CF7ANTISPAM_PREFIX )
		);
	}

	/** It creates a checkbox with the id of "cf7a_customizations_cipher_callback" */
	public function cf7a_customizations_cipher_callback() {
		if ( ! extension_loaded( 'openssl' ) ) {
			echo 'error: php extension openssl not enabled';
		}
		printf(
			'<select id="cipher" name="cf7a_options[cf7a_cipher]">%s</select>',
			wp_kses(
				$this->cf7a_generate_options(
					openssl_get_cipher_methods(),
					isset( $this->options['cf7a_cipher'] ) ? esc_attr( $this->options['cf7a_cipher'] ) : 'aes-128-cbc'
				),
				array(
					'option' => array(
						'value'    => array(),
						'selected' => array(),
					),
				)
			)
		);
	}


	/** It creates a checkbox with the id of "cf7a_score_fingerprinting_callback" */
	public function cf7a_score_fingerprinting_callback() {
		printf(
			'<input type="number" id="score_fingerprinting" name="cf7a_options[score][_fingerprinting]" value="%s" min="0" max="10" step="0.01" />',
			isset( $this->options['score']['_fingerprinting'] ) ? floatval( $this->options['score']['_fingerprinting'] ) : 0.25
		);
	}

	/** It creates a checkbox with the id of "cf7a_score_time_callback" */
	public function cf7a_score_time_callback() {
		printf(
			'<input type="number" id="score_time" name="cf7a_options[score][_time]" value="%s" min="0" max="10" step="0.01" />',
			isset( $this->options['score']['_time'] ) ? floatval( $this->options['score']['_time'] ) : 1
		);
	}

	/** It creates a checkbox with the id of "cf7a_score_bad_string_callback" */
	public function cf7a_score_bad_string_callback() {
		printf(
			'<input type="number" id="score_bad_string" name="cf7a_options[score][_bad_string]" value="%s" min="0" max="10" step="0.01" />',
			isset( $this->options['score']['_bad_string'] ) ? floatval( $this->options['score']['_bad_string'] ) : 1
		);
	}

	/** It creates a checkbox with the id of "cf7a_score_dnsbl_callback" */
	public function cf7a_score_dnsbl_callback() {
		printf(
			'<input type="number" id="score_dnsbl" name="cf7a_options[score][_dnsbl]" value="%s" min="0" max="10" step="0.01" />',
			isset( $this->options['score']['_dnsbl'] ) ? floatval( $this->options['score']['_dnsbl'] ) : 0.25
		);
	}

	/** It creates a checkbox with the id of "cf7a_score_honeypot_callback" */
	public function cf7a_score_honeypot_callback() {
		printf(
			'<input type="number" id="score_honeypot" name="cf7a_options[score][_honeypot]" value="%s" min="0" max="10" step="0.01" />',
			isset( $this->options['score']['_honeypot'] ) ? floatval( $this->options['score']['_honeypot'] ) : 1
		);
	}
	/** It creates a checkbox with the id of "cf7a_score_warn_callback" */
	public function cf7a_score_warn_callback() {
		printf(
			'<input type="number" id="score_warn" name="cf7a_options[score][_warn]" value="%s" min="0" max="10" step="0.01" />',
			isset( $this->options['score']['_warn'] ) ? floatval( $this->options['score']['_warn'] ) : 1
		);
	}

	/** It creates a checkbox with the id of "cf7a_score_detection_callback" */
	public function cf7a_score_detection_callback() {
		printf(
			'<input type="number" id="score_detection" name="cf7a_options[score][_detection]" value="%s" min="0" max="100" step="0.01" />',
			isset( $this->options['score']['_detection'] ) ? floatval( $this->options['score']['_detection'] ) : 5
		);
	}

	/** It creates a checkbox with the id of "cf7a_enable_advanced_settings_callback" */
	public function cf7a_enable_advanced_settings_callback() {
		printf(
			'<input type="checkbox" id="enable_advanced_settings" name="cf7a_options[enable_advanced_settings]" %s />',
			! empty( $this->options['enable_advanced_settings'] ) ? 'checked="true"' : ''
		);
	}

	/**
	 * It generates a select box with the options 'weak', 'standard', 'secure', and 'custom'
	 */
	public function cf7a_score_preset_callback() {
		$options = ! empty( $this->options['enable_advanced_settings'] )
					|| ( ! empty( $this->options['cf7a_score_preset'] ) && 'custom' === $this->options['cf7a_score_preset'] )
			? array( 'weak', 'standard', 'secure', 'custom' )
			: array( 'weak', 'standard', 'secure' );
		printf(
			'<select id="cf7a_score_preset" name="cf7a_options[cf7a_score_preset]">%s</select>',
			wp_kses(
				$this->cf7a_generate_options(
					$options,
					isset( $this->options['cf7a_score_preset'] ) ? esc_attr( $this->options['cf7a_score_preset'] ) : 'custom'
				),
				array(
					'option' => array(
						'value'    => array(),
						'selected' => array(),
					),
				)
			)
		);
	}
}
