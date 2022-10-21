<?php
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
	 * The geoip utils
	 *
	 * @since    0.4.0
	 * @access   public
	 */
	public $geo;

	/**
	 * The function `__construct()` is called when the class is instantiated.
	 *
	 * The function `cf7a_options_init()` is called when the admin page is loaded.
	 *
	 * The function `get_options()` is called to get the plugin options.
	 *
	 * The class `CF7_Antispam_geoip` is instantiated.
	 */
	public function __construct() {
		// the plugin main menu
		add_action( 'admin_init', array( $this, 'cf7a_options_init' ) );

		$this->options = CF7_AntiSpam::get_options(); // the plugin options
		$this->geo     = new CF7_Antispam_geoip; // the plugin options
	}

	public function cf7a_options_init() {

		// Group
		register_setting(
			'cf7_antispam_options', // Option group
			'cf7a_options', // Option name
			array( $this, 'cf7a_sanitize' ) // Sanitize
		);

		// Section Bot Fingerprint
		add_settings_section(
			'cf7a_auto_blacklist', // ID
			__( 'Ban automatically spammers', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_print_section_auto_blacklist' ), // Callback
			'cf7a-settings' // Page
		);

		// Settings autostore_bad_ip
		add_settings_field(
			'autostore_bad_ip', // ID
			__( 'Automatic spammer IP Blacklist', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_autostore_bad_ip_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_auto_blacklist' // Section
		);

		// Settings check_time
		add_settings_field(
			'max_attempts', // ID
			__( 'Mail blocked before Ban', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_max_attempts' ), // Callback
			'cf7a-settings', // Page
			'cf7a_auto_blacklist' // Section
		);

		// unban after
		add_settings_field(
			'unban_after', // ID
			__( 'Automatic Unban', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_unban_after_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_auto_blacklist' // Section
		);

		// Section Bot Fingerprint
		add_settings_section(
			'cf7a_bot_fingerprint', // ID
			__( 'Bot Fingerprinting', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_print_section_bot_fingerprint' ), // Callback
			'cf7a-settings' // Page
		);

		// Settings bot_fingerprint
		add_settings_field(
			'check_bot_fingerprint', // ID
			__( 'Enable anti-bot checks', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_check_bot_fingerprint_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_bot_fingerprint' // Section
		);

		// Settings bot_fingerprint
		add_settings_field(
			'check_bot_fingerprint_extras', // ID
			__( 'Enable anti-bot extra checks', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_check_bot_fingerprint_extras_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_bot_fingerprint' // Section
		);

		// Settings bot_fingerprint
		add_settings_field(
			'append_on_submit', // ID
			__( 'Append hidden fields on submit', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_append_on_submit_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_bot_fingerprint' // Section
		);

		// Section GEOIP
		add_settings_section(
			'cf7a_check_geoip', // ID
			__( 'GeoIP', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_check_geoip' ), // Callback
			'cf7a-settings' // Page
		);

		// Settings enable geoip
		add_settings_field(
			'check_geoip', // ID
			__( 'Enable GeoIP DB Download', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_enable_geoip_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_check_geoip' // Section
		);

		// Settings enable geoip
		add_settings_field(
			'check_geoip_enabled', // ID
			__( 'GeoIP database available', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_geoip_is_enabled_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_check_geoip' // Section
		);

		// The maxmind update key (unless you have defined it). Adds cron job to keep database updated;
		// https://www.maxmind.com/en/geolite2/signup?lang=en
		add_settings_field(
			'geoip_dbkey', // ID
			__( 'MaxMind Update Key', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_geoip_key_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_check_geoip' // Section
		);

		// Section Language
		add_settings_section(
			'cf7a_check_language', // ID
			__( 'Language Checks', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_check_language' ), // Callback
			'cf7a-settings' // Page
		);

		// Settings enable browser language check
		add_settings_field(
			'check_language', // ID
			__( 'Check Browser Language', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_check_browser_language_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_check_language' // Section
		);

		// Settings enable geoip check (available only if the geoip is enabled)
		if ( $this->options['check_geoip'] ) {
			add_settings_field(
				'check_geo_location', // ID
				__( 'Detect location using GeoIP', 'cf7-antispam' ), // Title
				array( $this, 'cf7a_check_geo_location_callback' ), // Callback
				'cf7a-settings', // Page
				'cf7a_check_language' // Section
			);
		}

		// Settings allowed languages
		add_settings_field(
			'language_allowed', // ID
			__( 'Allowed browser Languages', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_language_allowed' ), // Callback
			'cf7a-settings', // Page
			'cf7a_check_language' // Section
		);

		// Settings disallowed languages
		add_settings_field(
			'cf7a_language_disallowed', // ID
			__( 'Disallowed browser Languages', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_language_disallowed' ), // Callback
			'cf7a-settings', // Page
			'cf7a_check_language' // Section
		);

		// Section Time Checks
		add_settings_section(
			'cf7a_time_elapsed', // ID
			__( 'Time checks', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_print_section_check_time' ), // Callback
			'cf7a-settings' // Page
		);

		// Settings check_time
		add_settings_field(
			'check_time', // ID
			__( 'Check the elapsed time', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_check_time_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_time_elapsed' // Section
		);

		// Settings check_time
		add_settings_field(
			'check_time_min', // ID
			__( 'Minimum elapsed time', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_check_time_min_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_time_elapsed' // Section
		);

		// Settings check_time
		add_settings_field(
			'check_time_max', // ID
			__( 'Maximum elapsed time', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_check_time_max_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_time_elapsed' // Section
		);

		// Section Bad IP
		add_settings_section(
			'cf7a_bad_ip', // ID
			__( 'Bad IP Address', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_print_section_bad_ip' ), // Callback
			'cf7a-settings' // Page
		);

		// Settings check_bad_ip
		add_settings_field(
			'check_refer', // ID
			__( 'Check HTTP referrer', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_print_check_refer' ), // Callback
			'cf7a-settings', // Page
			'cf7a_bad_ip' // Section
		);

		// Settings check_bad_ip
		add_settings_field(
			'check_bad_ip', // ID
			__( 'Check the sender IP Address', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_check_bad_ip_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_bad_ip' // Section
		);

		// Settings bad_ip_list
		add_settings_field(
			'bad_ip_list', // ID
			__( 'Bad IP Address List', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_bad_ip_list_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_bad_ip' // Section
		);

		// Section Bad Words
		add_settings_section(
			'cf7a_bad_words', // ID
			__( 'Bad words', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_print_section_bad_words' ), // Callback
			'cf7a-settings' // Page
		);

		// Settings check_bad_words
		add_settings_field(
			'check_bad_words', // ID
			__( 'Check the message for prohibited words', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_bad_words_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_bad_words' // Section
		);

		// Settings bad_words_list
		add_settings_field(
			'bad_words_list', // ID
			__( 'Bad words List', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_bad_words_list_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_bad_words' // Section
		);

		// Section Bad Email Strings
		add_settings_section(
			'cf7a_bad_email_strings', // ID
			__( 'Bad email strings', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_print_section_bad_email_strings' ), // Callback
			'cf7a-settings' // Page
		);

		// Settings check_bad_email_strings
		add_settings_field(
			'check_bad_email_strings', // ID
			__( 'Check the email for prohibited words', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_check_bad_email_strings_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_bad_email_strings' // Section
		);

		// Settings bad_email_strings_list
		add_settings_field(
			'bad_email_strings_list', // ID
			__( 'Email prohibited words', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_bad_email_strings_list_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_bad_email_strings' // Section
		);

		// Section User Agent
		add_settings_section(
			'cf7a_user_agent', // ID
			__( 'User Agent blacklist', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_print_user_agent' ), // Callback
			'cf7a-settings' // Page
		);

		// Enable User Agent Blacklist
		add_settings_field(
			'check_bad_user_agent', // ID
			__( 'Enable User Agent blacklist', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_check_user_agent_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_user_agent' // Section
		);

		// User Agent Blacklist list
		add_settings_field(
			'bad_user_agent_list', // ID
			__( 'Disallowed user agents', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_user_agent_list_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_user_agent' // Section
		);

		// Section DNSBL
		add_settings_section(
			'cf7a_dnsbl', // ID
			__( 'DNS Blacklists', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_print_dnsbl' ), // Callback
			'cf7a-settings' // Page
		);

		// Enable DNS Blacklist list
		add_settings_field(
			'check_dnsbl', // ID
			__( 'Check IP on DNS blocklist', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_check_dnsbl_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_dnsbl' // Section
		);

		// DNS Blacklist server list
		add_settings_field(
			'dnsbl_list', // ID
			__( 'DNS blocklist servers', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_dnsbl_list_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_dnsbl' // Section
		);

		// Section honeypot
		add_settings_section(
			'cf7a_honeypot', // ID
			__( 'Honeypot', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_print_honeypot' ), // Callback
			'cf7a-settings' // Page
		);

		// Enable honeypot
		add_settings_field(
			'check_honeypot', // ID
			__( 'Add some fake input inside the form', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_enable_honeypot_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_honeypot' // Section
		);

		// DNS Blacklist server list
		add_settings_field(
			'honeypot_input_names', // ID
			__( 'Name for the honeypots inputs[*]', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_honeypot_input_names_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_honeypot' // Section
		);

		// Section honeyform
		add_settings_section(
			'cf7a_honeyform', // ID
			__( 'Honeyform <span class="label alert monospace">[experimental]</span>', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_print_honeyform' ), // Callback
			'cf7a-settings' // Page
		);

		// Enable honeyform
		add_settings_field(
			'check_honeyform', // ID
			__( 'Add an hidden form inside the page content', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_enable_honeyform_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_honeyform' // Section
		);

		// Honeyform position
		add_settings_field(
			'honeyform_position', // ID
			__( 'Select where the honeyform will be placed', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_honeyform_position_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_honeyform' // Section
		);

		// Section b8
		add_settings_section(
			'cf7a_b8', // ID
			__( 'B8 statistical "Bayesian" spam filter', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_print_b8' ), // Callback
			'cf7a-settings' // Page
		);

		// Enable b8
		add_settings_field(
			'enable_b8', // ID
			__( 'Enable B8', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_enable_b8_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_b8' // Section
		);

		// Settings b8_threshold
		add_settings_field(
			'b8_threshold', // ID
			__( 'B8 spam threshold', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_b8_threshold_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_b8' // Section
		);

		// Section Personalization
		add_settings_section(
			'cf7a_customizations', // ID
			__( 'Spam filter customizations', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_print_customizations' ), // Callback
			'cf7a-settings' // Page
		);

		// Enable customizations
		add_settings_field(
			'cf7a_disable_reload', // ID
			__( 'Disable cf7 form reload if the page is cached', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_disable_reload_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_customizations' // Section
		);

		// Enable customizations
		add_settings_field(
			'cf7a_customizations_class', // ID
			__( 'Your unique css class', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_customizations_class_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_customizations' // Section
		);

		// Enable customizations
		add_settings_field(
			'cf7a_customizations_prefix', // ID
			__( 'Your unique fields prefix', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_customizations_prefix_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_customizations' // Section
		);

		// Enable customizations
		add_settings_field(
			'cf7a_cipher', // ID
			__( 'The encryption method', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_customizations_cipher_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_customizations' // Section
		);

		// Section advanced settings
		add_settings_section(
			'cf7a_advanced', // ID
			__( 'Enable advanced settings', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_print_advanced_settings' ), // Callback
			'cf7a-settings' // Page
		);

		// Score Preset
		add_settings_field(
			'cf7a_score_preset', // ID
			__( 'Severity of anti-spam control', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_score_preset_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_advanced' // Section
		);

		// Enable advanced settings
		add_settings_field(
			'enable_advanced_settings', // ID
			__( 'Enable advanced settings', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_enable_advanced_settings_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_advanced' // Section
		);

		// Section Personalization
		add_settings_section(
			'cf7a_scoring', // ID
			__( 'Scoring Tweaks (1 = Ban)', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_print_scoring_settings' ), // Callback
			'cf7a-settings' // Page
		);

		// Settings score fingerprinting
		add_settings_field(
			'score_fingerprinting', // ID
			__( 'Bot fingerprinting score <small>(for each failed test)</small>', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_score_fingerprinting_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_scoring' // Section
		);

		// Settings score time
		add_settings_field(
			'score_time', // ID
			__( 'Time checks score', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_score_time_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_scoring' // Section
		);

		// Settings score bad_string
		add_settings_field(
			'score_bad_string', // ID
			__( 'String found', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_score_bad_string_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_scoring' // Section
		);

		// Settings score dnsbl
		add_settings_field(
			'score_dnsbl', // ID
			__( 'DNSBL score <small>(for each server)</small>', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_score_dnsbl_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_scoring' // Section
		);

		// Settings score honeypot
		add_settings_field(
			'score_honeypot', // ID
			__( 'Honeypot fill score <small>(for each fail)</small>', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_score_honeypot_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_scoring' // Section
		);

		// Settings score detection
		add_settings_field(
			'score_detection', // ID
			__( 'Bot detected', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_score_detection_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_scoring' // Section
		);

		// Settings score warn
		add_settings_field(
			'score_warn', // ID
			__( 'Bot warn', 'cf7-antispam' ), // Title
			array( $this, 'cf7a_score_warn_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_scoring' // Section
		);
	}

	public function cf7a_print_section_auto_blacklist() {
		printf( '<p>' . esc_html__( 'How many failed attempts before being banned', 'cf7-antispam' ) . '</p>' );
		if ( wp_next_scheduled( 'cf7a_cron' ) && CF7ANTISPAM_DEBUG ) {
			printf( sprintf( '<small class="monospace">%s%s <br/>Server time %s</small>', esc_html__( 'Next scheduled unban event: ', 'cf7-antispam' ), wp_date( 'Y-m-d H:i:s', wp_next_scheduled( 'cf7a_cron' ) ), wp_date( 'Y-m-d H:i:s', time() ) ) );
		}
	}

	public function cf7a_print_section_bot_fingerprint() {
		printf( '<p>' . esc_html__( "Fingerprinting is a way of exploiting certain data that the browser can provide to check whether it is a real browser. A script checks software and hardware configuration like screen resolution, 3d support, available fonts and OS version, that usually aren't available for bots.", 'cf7-antispam' ) . '</p>' );
		printf( '<p>' . esc_html__( 'The last option, append on submit, causes fingerprinting to take place after the submit button has been pressed, making it even more difficult for a bot to circumvent the protection.', 'cf7-antispam' ) . '</p>' );
	}

	public function cf7a_print_section_check_time() {
		printf( '<p>' . esc_html__( "Check that the e-mail is sent in the 'right' time frame. If the e-mail was sent too quickly or too slowly, the sender is probably not human. Values in seconds", 'cf7-antispam' ) . '</p>' );
	}

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
		// if the geo-ip constant was not set recommend to do so
		if ( ! CF7ANTISPAM_GEOIP_KEY ) {
			printf(
				'<p>%s<br/><code>%s</code></p>',
				esc_html__( 'Recommended - define a key your config.php the key in this way: ', 'cf7-antispam' ),
				"define( 'CF7ANTISPAM_GEOIP_KEY', 'aBcDeFgGhiLmNoPqR' );"
			);
		}
	}

	public function cf7a_check_language() {
		printf( '<p>' . esc_html__( 'Check the user browser language / user keyboard. Add one language code (en-US) or language (en) per line, the language code specifically enables or denies a state while the language enables or denies all language codes beginning with that language. ', 'cf7-antispam' ) . '</p>' );
		printf( '<p>' . esc_html__( 'This method is not as accurate as geo ip because it is based on what is provided by the browser and can easily be bypassed (however, less sophisticated bots do not pass this test)', 'cf7-antispam' ) . '</p>' );
	}

	public function cf7a_print_section_bad_ip() {
		printf( '<p>' . esc_html__( 'Adding a list of forbidden senders per IP address, one "bad" ip each line', 'cf7-antispam' ) . '</p>' );
	}

	public function cf7a_print_section_bad_words() {
		printf( '<p>' . esc_html__( 'Check if the mail message contains "bad" words, all e-mails containing one of these words in the text will be flagged. A bad string per line', 'cf7-antispam' ) . '</p>' );
	}

	public function cf7a_print_section_bad_email_strings() {
		printf( '<p>' . esc_html__( 'Check if the mail content contains a word and in this case flag this mail, one forbidden word per line', 'cf7-antispam' ) . '</p>' );
	}

	public function cf7a_print_user_agent() {
		printf( '<p>' . esc_html__( 'Enter a list of forbidden user agents, one per line. When the string match the user agent (or a part of) the mail will be flagged', 'cf7-antispam' ) . '</p>' );
	}

	public function cf7a_print_dnsbl() {
		printf( '<p>' . esc_html__( 'Check sender ip on DNS Blacklists, DNSBLs are real-time lists of proven/recognised spam addresses. These may include lists of addresses of zombie computers or other machines used to send spam, Internet Service Providers (ISPs) that voluntarily host spammers. A DSNBL server url each line ', 'cf7-antispam' ) . '</p>' );
	}

	public function cf7a_print_honeypot() {
		printf( '<p>' . esc_html__( 'the honeypot is a "trap" field that is hidden with css or js from the user but remains visible to bots. Since this fields are automatically added and appended inside the forms with standard names.', 'cf7-antispam' ) . " <p class='info monospace'>[*] " . esc_html__( 'Please check the list below because the name MUST differ from the cf7 tag class names', 'cf7-antispam' ) . '</p></p>' );
	}

	public function cf7a_print_honeyform() {
		printf( '<p>' . esc_html__( "I'm actually going to propose the honeyform for the first time! Instead of creating trap fields that even my grandfather knows about, I directly create a trap form (much less detectable for bots)", 'cf7-antispam' ) . '</p>' );
	}

	public function cf7a_print_b8() {
		printf( '<p>' . esc_html__( 'Tells you whether a text is spam or not, using statistical text analysis of the text message', 'cf7-antispam' ) . '</p>' );
	}

	public function cf7a_print_customizations() {
		printf( '<p>' . esc_html__( 'RECOMMENDED: create your own and unique css class and customized fields name', 'cf7-antispam' ) . '</p>' );
		printf( '<p>' . esc_html__( "You can also choose in encryption method. But, After changing cypher do a couple of tests because a small amount of them aren't compatible with the format of the form data.", 'cf7-antispam' ) . '</p>' );
	}

	public function cf7a_print_scoring_settings() {
		printf( '<p>' . esc_html__( 'The calculation system of antispam for contact form 7 works like this: each failed test has its own score (shown below where you can refine it to your liking). If the mail at the end of all tests exceeds a value of 1, the mail is considered spam, and is consequently processed by b8, which analyses the text and learns the words of a spam mail.', 'cf7-antispam' ) . '</p>' );
	}

	public function cf7a_print_advanced_settings() {
		printf( '<p>' . esc_html__( 'In this section you will find some advanced settings to manage the database', 'cf7-antispam' ) . '</p>' );
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
				if ( ! is_string( $value ) ) {
					continue;
				}
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
	 * @param string $input - The user input with comma and spaces
	 *
	 * @return array $new_input - The formatted input.
	 */
	private function cf7a_settings_format_user_input( $input ) {
		$new_input = str_replace( "\r\n", ',', $input );
		$new_input = explode( ',', $new_input );

		return self::cf7a_remove_empty_from_array( $new_input );
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 * @return array $new_input sanitized input
	 */
	public function cf7a_sanitize_options( $input ) {

		// get the existing options
		$new_input = $this->options;

		// bot fingerprint
		$new_input['check_bot_fingerprint']        = isset( $input['check_bot_fingerprint'] ) ? 1 : 0;
		$new_input['check_bot_fingerprint_extras'] = isset( $input['check_bot_fingerprint_extras'] ) ? 1 : 0;
		$new_input['append_on_submit']             = isset( $input['append_on_submit'] ) ? 1 : 0;

		// elapsed time
		$new_input['check_time'] = isset( $input['check_time'] ) ? 1 : 0;

		$new_input['check_time_min'] = isset( $input['check_time_min'] ) ? intval( $input['check_time_min'] ) : 6;
		$new_input['check_time_max'] = isset( $input['check_time_max'] ) ? intval( $input['check_time_max'] ) : ( 60 * 60 * 25 ); // a day + 1 hour of timeframe to send the mail seem fine :)

		$check_geoip = ! empty( $input['check_geoip'] );

		// if check_geoip has changed we need also to set or unset the cron download
		$geo = new CF7_Antispam_geoip;

		if ( $check_geoip > 0 && $geo->cf7a_maybe_download_geoip_db() ) {

			$geo->cf7a_geoip_schedule_update( true );

		} elseif ( 0 === $check_geoip ) {

			update_option( 'cf7a_geodb_update', false );

			$timestamp = wp_next_scheduled( 'cf7a_geoip_update_db', array( false ) );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'cf7a_geoip_update_db' );
			}
		}

		$new_input['check_geoip'] = $check_geoip;
		$new_input['geoip_dbkey'] = isset( $input['geoip_dbkey'] ) ? sanitize_textarea_field( $input['geoip_dbkey'] ) : false;

		// browser language check enabled
		$new_input['check_language'] = isset( $input['check_language'] ) ? 1 : 0;
		// geo-ip location check enabled
		$new_input['check_geo_location'] = isset( $input['check_geo_location'] ) ? 1 : 0;
		//languages allowed / disallowed
		if ( ! empty( $input['languages']['allowed'] ) ) {
			$new_input['languages']['allowed'] = $this->cf7a_settings_format_user_input( sanitize_textarea_field( $input['languages']['allowed'] ) );
		} else {
			$new_input['languages']['allowed'] = array();}
		if ( ! empty( $input['languages']['disallowed'] ) ) {
			$new_input['languages']['disallowed'] = $this->cf7a_settings_format_user_input( sanitize_textarea_field( $input['languages']['disallowed'] ) );
		} else {
			$new_input['languages']['disallowed'] = array();}

		// bad ip
		$new_input['check_refer']  = isset( $input['check_refer'] ) ? 1 : 0;
		$new_input['check_bad_ip'] = isset( $input['check_bad_ip'] ) ? 1 : 0;
		if ( isset( $input['bad_ip_list'] ) ) {
			$new_input['bad_ip_list'] = $this->cf7a_settings_format_user_input( sanitize_textarea_field( $input['bad_ip_list'] ) );
		}

		// max attempts before ban
		$new_input['max_attempts'] = isset( $input['max_attempts'] ) ? intval( $input['max_attempts'] ) : 2;

		// auto-ban
		$new_input['autostore_bad_ip'] = isset( $input['autostore_bad_ip'] ) ? 1 : 0;

		// auto-unban delay
		if ( ! empty( $input['unban_after'] ) && in_array( $input['unban_after'], array( '60sec', '5min', 'hourly', 'twicedaily', 'daily', 'weekly' ), true ) ) {

			if ( $this->options['unban_after'] !== $input['unban_after'] ) {
				$new_input['unban_after'] = $input['unban_after'];
				// delete previous scheduled events
				$timestamp = wp_next_scheduled( 'cf7a_cron', array( false ) );
				if ( $timestamp ) {
					wp_unschedule_event( $timestamp, 'cf7a_cron' );
				}

				wp_schedule_event( time(), $new_input['unban_after'], 'cf7a_cron' );
				// add the new scheduled event
			}
		} else {
			// Get the timestamp for the next event.
			$timestamp = wp_next_scheduled( 'cf7a_cron', array( false ) );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'cf7a_cron' );
			}
			$new_input['unban_after'] = 'disabled';
		}

		// bad words
		$new_input['check_bad_words'] = isset( $input['check_bad_words'] ) ? 1 : 0;
		if ( ! empty( $input['bad_words_list'] ) ) {
			$new_input['bad_words_list'] = $this->cf7a_remove_empty_from_array( explode( "\r\n", $input['bad_words_list'] ) );
		}

		// email strings
		$new_input['check_bad_email_strings'] = isset( $input['check_bad_email_strings'] ) ? 1 : 0;
		if ( ! empty( $input['bad_email_strings_list'] ) ) {
			$new_input['bad_email_strings_list'] = $this->cf7a_settings_format_user_input( sanitize_textarea_field( $input['bad_email_strings_list'] ) );
		}

		// user_agent
		$new_input['check_bad_user_agent'] = isset( $input['check_bad_user_agent'] ) ? 1 : 0;
		if ( ! empty( $input['bad_user_agent_list'] ) ) {
			$new_input['bad_user_agent_list'] = $this->cf7a_settings_format_user_input( sanitize_textarea_field( $input['bad_user_agent_list'] ) );
		}

		// dnsbl
		$new_input['check_dnsbl'] = isset( $input['check_dnsbl'] ) ? 1 : 0;
		if ( ! empty( $input['dnsbl_list'] ) ) {
			$new_input['dnsbl_list'] = $this->cf7a_remove_empty_from_array( explode( "\r\n", sanitize_textarea_field( $input['dnsbl_list'] ) ) );
		}

		// honeypot
		$new_input['check_honeypot'] = isset( $input['check_honeypot'] ) ? 1 : 0;
		if ( ! empty( $input['honeypot_input_names'] ) ) {
			$new_input['honeypot_input_names'] = $this->cf7a_settings_format_user_input( sanitize_textarea_field( $input['honeypot_input_names'] ) );
		}

		// honeyform
		$new_input['check_honeyform']    = isset( $input['check_honeyform'] ) ? 1 : 0;
		$new_input['honeyform_position'] = ! empty( $input['honeyform_position'] ) ? sanitize_html_class( $input['honeyform_position'] ) : 'wp_body_open';

		// b8
		$new_input['enable_b8']    = isset( $input['enable_b8'] ) ? 1 : 0;
		$threshold                 = floatval( $input['b8_threshold'] );
		$new_input['b8_threshold'] = ( $threshold >= 0 && $threshold < 1 ) ? $threshold : 1;

		$score_preset = array(
			'weak'     => array(
				'_fingerprinting' => 0.1,
				'_time'           => 0.3,
				'_bad_string'     => 0.5,
				'_dnsbl'          => 0.1,
				'_honeypot'       => 0.3,
				'_detection'      => 0.5,
				'_warn'           => 0.25,
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
			'custom'   => $this->options['cf7a_score_preset'],
		);

		// Scoring
		// if the preset name is equal to $selected and (the old score is the same of the new one OR the preset score $selected is changed)
		if ( 'weak' === $input['cf7a_score_preset'] && ( $input['score'] === $this->options['score'] || $input['cf7a_score_preset'] !== $this->options['cf7a_score_preset'] ) ) {
			$new_input['score']             = $score_preset['weak'];
			$new_input['cf7a_score_preset'] = 'weak';
		} elseif ( 'standard' === $input['cf7a_score_preset'] && ( $input['score'] === $this->options['score'] || $input['cf7a_score_preset'] !== $this->options['cf7a_score_preset'] ) ) {
			$new_input['score']             = $score_preset['standard'];
			$new_input['cf7a_score_preset'] = 'standard';
		} elseif ( 'secure' === $input['cf7a_score_preset'] && ( $input['score'] === $this->options['score'] || $input['cf7a_score_preset'] !== $this->options['cf7a_score_preset'] ) ) {
			$new_input['score']             = $score_preset['secure'];
			$new_input['cf7a_score_preset'] = 'secure';
		} else {
			$new_input['score']['_fingerprinting'] = isset( $input['score']['_fingerprinting'] ) ? floatval( $input['score']['_fingerprinting'] ) : 0.25;
			$new_input['score']['_time']           = isset( $input['score']['_time'] ) ? floatval( $input['score']['_time'] ) : 1;
			$new_input['score']['_bad_string']     = isset( $input['score']['_bad_string'] ) ? floatval( $input['score']['_bad_string'] ) : 1;
			$new_input['score']['_dnsbl']          = isset( $input['score']['_dnsbl'] ) ? floatval( $input['score']['_dnsbl'] ) : 0.2;
			$new_input['score']['_honeypot']       = isset( $input['score']['_honeypot'] ) ? floatval( $input['score']['_honeypot'] ) : 1;
			$new_input['score']['_detection']      = isset( $input['score']['_detection'] ) ? floatval( $input['score']['_detection'] ) : 5;
			$new_input['score']['_warn']           = isset( $input['score']['_warn'] ) ? floatval( $input['score']['_warn'] ) : 1;
			$new_input['cf7a_score_preset']        = 'custom';
		}

		// Advanced settings
		$new_input['enable_advanced_settings'] = isset( $input['enable_advanced_settings'] ) ? 1 : 0;

		// Customizations
		$new_input['cf7a_disable_reload'] = isset( $input['cf7a_disable_reload'] ) ? 1 : 0;

		$input['cf7a_customizations_class']     = sanitize_html_class( $input['cf7a_customizations_class'] );
		$new_input['cf7a_customizations_class'] = ! empty( $input['cf7a_customizations_class'] ) ? sanitize_html_class( $input['cf7a_customizations_class'] ) : CF7ANTISPAM_HONEYPOT_CLASS;

		$input['cf7a_customizations_prefix']     = sanitize_html_class( $input['cf7a_customizations_prefix'] );
		$new_input['cf7a_customizations_prefix'] = ! empty( $input['cf7a_customizations_prefix'] ) ? sanitize_html_class( $input['cf7a_customizations_prefix'] ) : CF7ANTISPAM_PREFIX;

		$input['cf7a_cipher']     = sanitize_html_class( $input['cf7a_cipher'] );
		$new_input['cf7a_cipher'] = ! empty( $input['cf7a_cipher'] ) && in_array( $input['cf7a_cipher'], openssl_get_cipher_methods(), true ) ? $input['cf7a_cipher'] : CF7ANTISPAM_CYPHER;

		// store the sanitized options
		return $new_input;
	}

	/**
	 * utility to generate option select items
	 * @param $values array - the array of selection options
	 * @param $selected - the name of the selected one (if any)
	 *
	 * @return string - the html needed inside the select
	 */
	private function cf7a_generate_options( $values, $selected = '' ) {
		$html = '';
		foreach ( $values as $value ) {
			$sel   = ( $value === $selected ) ? 'selected' : '';
			$html .= "<option value=\"$value\" $sel>$value</option>";
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

	public function cf7a_max_attempts() {
		printf(
			'<input type="number" id="max_attempts" name="cf7a_options[max_attempts]" value="%s" step="1" />',
			! empty( $this->options['max_attempts'] ) ? esc_attr( $this->options['max_attempts'] ) : 2
		);
	}

	public function cf7a_unban_after_callback() {
		printf(
			'<select id="unban_after" name="cf7a_options[unban_after]">%s</select>',
			$this->cf7a_generate_options(
				array( 'disabled', '60sec', '5min', 'hourly', 'twicedaily', 'daily', 'weekly' ),
				! empty( $this->options['unban_after'] ) ? esc_attr( $this->options['unban_after'] ) : 'disabled'
			)
		);
	}

	public function cf7a_check_bot_fingerprint_callback() {
		printf(
			'<input type="checkbox" id="check_bot_fingerprint" name="cf7a_options[check_bot_fingerprint]" %s />',
			! empty( $this->options['check_bot_fingerprint'] ) ? 'checked="true"' : ''
		);
	}
	public function cf7a_check_bot_fingerprint_extras_callback() {
		printf(
			'<input type="checkbox" id="check_bot_fingerprint_extras" name="cf7a_options[check_bot_fingerprint_extras]" %s />',
			! empty( $this->options['check_bot_fingerprint_extras'] ) ? 'checked="true"' : ''
		);
	}
	public function cf7a_append_on_submit_callback() {
		printf(
			'<input type="checkbox" id="append_on_submit" name="cf7a_options[append_on_submit]" %s />',
			! empty( $this->options['append_on_submit'] ) ? 'checked="true"' : ''
		);
	}


	public function cf7a_check_time_callback() {
		printf(
			'<input type="checkbox" id="check_time" name="cf7a_options[check_time]" %s />',
			! empty( $this->options['check_time'] ) ? 'checked="true"' : ''
		);
	}
	public function cf7a_check_time_min_callback() {
		printf(
			'<input type="number" id="check_time_min" name="cf7a_options[check_time_min]" value="%s" step="1" />',
			! empty( $this->options['check_time_min'] ) ? esc_attr( $this->options['check_time_min'] ) : 6
		);
	}

	public function cf7a_check_time_max_callback() {
		printf(
			'<input type="number" id="check_time_max" name="cf7a_options[check_time_max]" value="%s" step="1" />',
			! empty( $this->options['check_time_max'] ) ? esc_attr( $this->options['check_time_max'] ) : 3600 * 48
		);
	}


	public function cf7a_enable_geoip_callback() {
		printf(
			'<input type="checkbox" id="check_geoip" name="cf7a_options[check_geoip]" %s />',
			! empty( $this->options['check_geoip'] ) ? 'checked="true"' : ''
		);
	}

	public function cf7a_geoip_is_enabled_callback() {
		printf( ( get_option( 'cf7a_geodb_update' ) ) ? '✅ ' : '❌ ' );
	}

	public function cf7a_geoip_key_callback() {
		printf(
			'<input type="text" id="geoip_dbkey" name="cf7a_options[geoip_dbkey]" %s %s/>',
			! empty( $this->options['geoip_dbkey'] ) ? 'value="' . esc_textarea( $this->options['geoip_dbkey'] ) . '"' : '',
			empty( CF7ANTISPAM_GEOIP_KEY ) ? '' : ' disabled placeholder="KEY provided"'
		);
	}

	public function cf7a_check_browser_language_callback() {
		printf(
			'<input type="checkbox" id="check_language" name="cf7a_options[check_language]" %s />',
			! empty( $this->options['check_language'] ) ? 'checked="true"' : ''
		);
	}

	public function cf7a_check_geo_location_callback() {
		printf(
			'<input type="checkbox" id="check_geo_location" name="cf7a_options[check_geo_location]" %s />',
			! empty( $this->options['check_geo_location'] ) ? 'checked="true"' : ''
		);
	}

	public function cf7a_language_allowed() {
		printf(
			'<textarea id="languages_allowed" name="cf7a_options[languages][allowed]" />%s</textarea>',
			isset( $this->options['languages']['allowed'] ) && is_array( $this->options['languages']['allowed'] ) ? esc_textarea( implode( "\r\n", $this->options['languages']['allowed'] ) ) : ''
		);
	}

	public function cf7a_language_disallowed() {
		printf(
			'<textarea id="languages_disallowed" name="cf7a_options[languages][disallowed]" />%s</textarea>',
			isset( $this->options['languages']['disallowed'] ) && is_array( $this->options['languages']['disallowed'] ) ? esc_textarea( implode( "\r\n", $this->options['languages']['disallowed'] ) ) : ''
		);
	}


	public function cf7a_print_check_refer() {
		printf(
			'<input type="checkbox" id="check_refer" name="cf7a_options[check_refer]" %s />',
			! empty( $this->options['check_refer'] ) ? 'checked="true"' : ''
		);
	}

	public function cf7a_check_bad_ip_callback() {
		printf(
			'<input type="checkbox" id="check_bad_ip" name="cf7a_options[check_bad_ip]" %s />',
			! empty( $this->options['check_bad_ip'] ) ? 'checked="true"' : ''
		);
	}

	public function cf7a_bad_ip_list_callback() {
		printf(
			'<textarea id="bad_ip_list" name="cf7a_options[bad_ip_list]" />%s</textarea>',
			isset( $this->options['bad_ip_list'] ) && is_array( $this->options['bad_ip_list'] ) ? esc_textarea( implode( "\r\n", $this->options['bad_ip_list'] ) ) : ''
		);
	}


	public function cf7a_bad_words_callback() {
		printf(
			'<input type="checkbox" id="check_bad_words" name="cf7a_options[check_bad_words]" %s />',
			! empty( $this->options['check_bad_words'] ) ? 'checked="true"' : ''
		);
	}

	public function cf7a_bad_words_list_callback() {
		printf(
			'<textarea id="bad_words_list" name="cf7a_options[bad_words_list]" />%s</textarea>',
			isset( $this->options['bad_words_list'] ) && is_array( $this->options['bad_words_list'] ) ? esc_textarea( implode( "\r\n", $this->options['bad_words_list'] ) ) : ''
		);
	}


	public function cf7a_check_bad_email_strings_callback() {
		printf(
			'<input type="checkbox" id="check_bad_email_strings" name="cf7a_options[check_bad_email_strings]" %s />',
			! empty( $this->options['check_bad_email_strings'] ) ? 'checked="true"' : ''
		);
	}

	public function cf7a_bad_email_strings_list_callback() {
		printf(
			'<textarea id="bad_email_strings_list" name="cf7a_options[bad_email_strings_list]" />%s</textarea>',
			isset( $this->options['bad_email_strings_list'] ) && is_array( $this->options['bad_email_strings_list'] ) ? esc_textarea( implode( "\r\n", $this->options['bad_email_strings_list'] ) ) : ''
		);
	}


	public function cf7a_check_user_agent_callback() {
		printf(
			'<input type="checkbox" id="check_bad_user_agent" name="cf7a_options[check_bad_user_agent]" %s />',
			! empty( $this->options['check_bad_user_agent'] ) ? 'checked="true"' : ''
		);
	}

	public function cf7a_user_agent_list_callback() {
		printf(
			'<textarea id="bad_user_agent_list" name="cf7a_options[bad_user_agent_list]" />%s</textarea>',
			isset( $this->options['bad_user_agent_list'] ) && is_array( $this->options['bad_user_agent_list'] ) ? esc_textarea( implode( "\r\n", $this->options['bad_user_agent_list'] ) ) : ''
		);
	}


	public function cf7a_check_dnsbl_callback() {
		printf(
			'<input type="checkbox" id="check_dnsbl" name="cf7a_options[check_dnsbl]" %s />',
			! empty( $this->options['check_dnsbl'] ) ? 'checked="true"' : ''
		);
	}
	public function cf7a_dnsbl_list_callback() {
		printf(
			'<textarea id="dnsbl_list" name="cf7a_options[dnsbl_list]" />%s</textarea>',
			isset( $this->options['dnsbl_list'] ) && is_array( $this->options['dnsbl_list'] ) ? esc_textarea( implode( "\r\n", $this->options['dnsbl_list'] ) ) : ''
		);
	}



	public function cf7a_enable_honeypot_callback() {
		printf(
			'<input type="checkbox" id="check_honeypot" name="cf7a_options[check_honeypot]" %s />',
			! empty( $this->options['check_honeypot'] ) ? 'checked="true"' : ''
		);
	}
	public function cf7a_honeypot_input_names_callback() {
		printf(
			'<textarea id="honeypot_input_names" name="cf7a_options[honeypot_input_names]" />%s</textarea>',
			isset( $this->options['honeypot_input_names'] ) && is_array( $this->options['honeypot_input_names'] ) ? esc_textarea( implode( "\r\n", $this->options['honeypot_input_names'] ) ) : ''
		);
	}



	public function cf7a_enable_honeyform_callback() {
		printf(
			'<input type="checkbox" id="check_honeyform" name="cf7a_options[check_honeyform]" %s />',
			! empty( $this->options['check_honeyform'] ) ? 'checked="true"' : ''
		);
	}

	public function cf7a_honeyform_position_callback() {
		printf(
			'<select id="honeyform_position" name="cf7a_options[honeyform_position]">%s</select>',
			$this->cf7a_generate_options( array( 'wp_body_open', 'the_content', 'wp_footer' ), isset( $this->options['honeyform_position'] ) ? esc_attr( $this->options['honeyform_position'] ) : '' )
		);
	}

	public function cf7a_enable_b8_callback() {
		printf(
			'<input type="checkbox" id="enable_b8" name="cf7a_options[enable_b8]" %s />',
			! empty( $this->options['enable_b8'] ) ? 'checked="true"' : ''
		);
	}

	public function cf7a_b8_threshold_callback() {
		printf(
			'<input type="number" id="b8_threshold" name="cf7a_options[b8_threshold]" value="%s" min="0" max="1" step="0.01" /> <small>(0-1)</small>',
			isset( $this->options['b8_threshold'] ) ? esc_attr( $this->options['b8_threshold'] ) : 'none'
		);
	}




	public function cf7a_disable_reload_callback() {
		printf(
			'<input type="checkbox" id="cf7a_disable_reload" name="cf7a_options[cf7a_disable_reload]" %s />',
			! empty( $this->options['cf7a_disable_reload'] ) ? 'checked="true"' : ''
		);
	}

	public function cf7a_customizations_class_callback() {
		printf(
			'<input type="text" id="cf7a_customizations_class" name="cf7a_options[cf7a_customizations_class]" value="%s"/>',
			isset( $this->options['cf7a_customizations_class'] ) ? sanitize_html_class( $this->options['cf7a_customizations_class'] ) : sanitize_html_class( CF7ANTISPAM_HONEYPOT_CLASS )
		);
	}

	public function cf7a_customizations_prefix_callback() {
		printf(
			'<input type="text" id="cf7a_customizations_prefix" name="cf7a_options[cf7a_customizations_prefix]" value="%s"/>',
			isset( $this->options['cf7a_customizations_prefix'] ) ? sanitize_html_class( $this->options['cf7a_customizations_prefix'] ) : sanitize_html_class( CF7ANTISPAM_PREFIX )
		);
	}

	public function cf7a_customizations_cipher_callback() {
		printf(
			'<select id="cipher" name="cf7a_options[cf7a_cipher]">%s</select>',
			$this->cf7a_generate_options(
				openssl_get_cipher_methods(),
				isset( $this->options['cf7a_cipher'] ) ? esc_attr( $this->options['cf7a_cipher'] ) : 'aes-128-cbc'
			)
		);
	}



	public function cf7a_score_fingerprinting_callback() {
		printf(
			'<input type="number" id="score_fingerprinting" name="cf7a_options[score][_fingerprinting]" value="%s" min="0" max="10" step="0.01" />',
			isset( $this->options['score']['_fingerprinting'] ) ? floatval( $this->options['score']['_fingerprinting'] ) : 0.25
		);
	}

	public function cf7a_score_time_callback() {
		printf(
			'<input type="number" id="score_time" name="cf7a_options[score][_time]" value="%s" min="0" max="10" step="0.01" />',
			isset( $this->options['score']['_time'] ) ? floatval( $this->options['score']['_time'] ) : 1
		);
	}

	public function cf7a_score_bad_string_callback() {
		printf(
			'<input type="number" id="score_bad_string" name="cf7a_options[score][_bad_string]" value="%s" min="0" max="10" step="0.01" />',
			isset( $this->options['score']['_bad_string'] ) ? floatval( $this->options['score']['_bad_string'] ) : 1
		);
	}

	public function cf7a_score_dnsbl_callback() {
		printf(
			'<input type="number" id="score_dnsbl" name="cf7a_options[score][_dnsbl]" value="%s" min="0" max="10" step="0.01" />',
			isset( $this->options['score']['_dnsbl'] ) ? floatval( $this->options['score']['_dnsbl'] ) : 0.25
		);
	}

	public function cf7a_score_honeypot_callback() {
		printf(
			'<input type="number" id="score_honeypot" name="cf7a_options[score][_honeypot]" value="%s" min="0" max="10" step="0.01" />',
			isset( $this->options['score']['_honeypot'] ) ? floatval( $this->options['score']['_honeypot'] ) : 1
		);
	}

	public function cf7a_score_warn_callback() {
		printf(
			'<input type="number" id="score_warn" name="cf7a_options[score][_warn]" value="%s" min="0" max="10" step="0.01" />',
			isset( $this->options['score']['_warn'] ) ? floatval( $this->options['score']['_warn'] ) : 1
		);
	}

	public function cf7a_score_detection_callback() {
		printf(
			'<input type="number" id="score_detection" name="cf7a_options[score][_detection]" value="%s" min="0" max="100" step="0.01" />',
			isset( $this->options['score']['_detection'] ) ? floatval( $this->options['score']['_detection'] ) : 5
		);
	}

	public function cf7a_enable_advanced_settings_callback() {
		printf(
			'<input type="checkbox" id="enable_advanced_settings" name="cf7a_options[enable_advanced_settings]" %s />',
			! empty( $this->options['enable_advanced_settings'] ) ? 'checked="true"' : ''
		);
	}

	public function cf7a_score_preset_callback() {
		$options = ( 1 === $this->options['enable_advanced_settings'] || ( ! empty( $this->options['cf7a_score_preset'] ) && 'custom' === $this->options['cf7a_score_preset'] ) ) ? array( 'weak', 'standard', 'secure', 'custom' ) : array( 'weak', 'standard', 'secure' );
		printf(
			'<select id="cf7a_score_preset" name="cf7a_options[cf7a_score_preset]">%s</select>',
			$this->cf7a_generate_options(
				$options,
				isset( $this->options['cf7a_score_preset'] ) ? esc_attr( $this->options['cf7a_score_preset'] ) : 'custom'
			)
		);
	}
}
