<?php
class CF7_AntiSpam_Admin_Customizations {

	/**
	 * The options of this plugin.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      array    $options    options of this plugin.
	 */
	public $options;

	public function __construct() {
		// the plugin main menu
		add_action( 'admin_init', array( $this, 'cf7a_options_init' ) );

		$this->options = CF7_AntiSpam::get_options(); // the plugin options
	}

	public function cf7a_options_init() {

		// Group
		register_setting( 'cf7_antispam_options', // Option group
			'cf7a_options', // Option name
			array( $this, 'cf7a_sanitize' ) // Sanitize
		);


		// Section Bot Fingerprint
		add_settings_section( 'cf7a_bot_fingerprint', // ID
			__('Bot Fingerprinting', 'cf7-antispam'), // Title
			array( $this, 'cf7a_print_section_bot_fingerprint' ), // Callback
			'cf7a-settings' // Page
		);

		// Settings bot_fingerprint
		add_settings_field( 'check_bot_fingerprint', // ID
			__('Enable anti-bot checks', 'cf7-antispam'), // Title
			array( $this, 'cf7a_check_bot_fingerprint_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_bot_fingerprint' // Section
		);

		// Settings bot_fingerprint
		add_settings_field( 'check_bot_fingerprint_extras', // ID
			__('Enable anti-bot extra checks', 'cf7-antispam'), // Title
			array( $this, 'cf7a_check_bot_fingerprint_extras_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_bot_fingerprint' // Section
		);

		// Settings bot_fingerprint
		add_settings_field( 'bot_fingerprint_tolerance', // ID
			__('How many checks can fail', 'cf7-antispam'), // Title
			array( $this, 'cf7a_bot_fingerprint_tolerance_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_bot_fingerprint' // Section
		);



		// Section Time Checks
		add_settings_section( 'cf7a_time_elapsed', // ID
			__('Time checks', 'cf7-antispam'), // Title
			array( $this, 'cf7a_print_section_check_time' ), // Callback
			'cf7a-settings' // Page
		);

		// Settings check_time
		add_settings_field( 'check_time', // ID
			__('Check the elapsed Time', 'cf7-antispam'), // Title
			array( $this, 'cf7a_check_time_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_time_elapsed' // Section
		);

		// Settings check_time
		add_settings_field( 'check_time_min', // ID
			__('Minimum elapsed Time', 'cf7-antispam'), // Title
			array( $this, 'cf7a_check_time_min_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_time_elapsed' // Section
		);

		// Settings check_time
		add_settings_field( 'check_time_max', // ID
			__('Maximum Elapsed Time', 'cf7-antispam'), // Title
			array( $this, 'cf7a_check_time_max_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_time_elapsed' // Section
		);



		// Section Bad Words
		add_settings_section( 'cf7a_bad_words', // ID
			__('Bad words', 'cf7-antispam'), // Title
			array( $this, 'cf7a_print_section_bad_words' ), // Callback
			'cf7a-settings' // Page
		);

		// Settings check_time
		add_settings_field( 'check_bad_words', // ID
			__('Check the message for prohibited words', 'cf7-antispam'), // Title
			array( $this, 'cf7a_bad_words_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_bad_words' // Section
		);

		// Settings check_time
		add_settings_field( 'bad_words_list', // ID
			__('Bad words List', 'cf7-antispam'), // Title
			array( $this, 'cf7a_bad_words_list_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_bad_words' // Section
		);



		// Section Bad Email Strings
		add_settings_section( 'cf7a_bad_email_strings', // ID
			__('Bad email strings', 'cf7-antispam'), // Title
			array( $this, 'cf7a_print_section_bad_email_strings' ), // Callback
			'cf7a-settings' // Page
		);

		// Settings check_bad_email_strings
		add_settings_field( 'check_bad_email_strings', // ID
			__('Check the email for prohibited words', 'cf7-antispam'), // Title
			array( $this, 'cf7a_check_bad_email_strings_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_bad_email_strings' // Section
		);

		// Settings bad_email_strings_list
		add_settings_field( 'bad_email_strings_list', // ID
			__('Email prohibited words', 'cf7-antispam'), // Title
			array( $this, 'cf7a_bad_email_strings_list_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_bad_email_strings' // Section
		);



		// Section User Agent
		add_settings_section( 'cf7a_user_agent', // ID
			__('User Agent blacklist', 'cf7-antispam'), // Title
			array( $this, 'cf7a_print_user_agent' ), // Callback
			'cf7a-settings' // Page
		);

		// Enable User Agent Blacklist
		add_settings_field( 'check_bad_user_agent', // ID
			__('Enable User Agent blacklist', 'cf7-antispam'), // Title
			array( $this, 'cf7a_check_user_agent_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_user_agent' // Section
		);

		// User Agent Blacklist list
		add_settings_field( 'bad_user_agent_list', // ID
			__('disallowed user agents', 'cf7-antispam'), // Title
			array( $this, 'cf7a_user_agent_list_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_user_agent' // Section
		);



		// Section DNSBL
		add_settings_section( 'cf7a_dnsbl', // ID
			__('DNS Blacklists', 'cf7-antispam'), // Title
			array( $this, 'cf7a_print_dnsbl' ), // Callback
			'cf7a-settings' // Page
		);

		// Enable DNS Blacklist list
		add_settings_field( 'check_dnsbl', // ID
			__('Check IP on DNS blocklist', 'cf7-antispam'), // Title
			array( $this, 'cf7a_check_dnsbl_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_dnsbl' // Section
		);

		// DNS Blacklist list tolerance
		add_settings_field( 'dnsbl_tolerance', // ID
			__('How many times ip has to be blacklisted by DNSBL to become spam', 'cf7-antispam'), // Title
			array( $this, 'cf7a_dnsbl_tolerance_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_dnsbl' // Section
		);

		// DNS Blacklist server list
		add_settings_field( 'dnsbl_list', // ID
			__('DNS blocklist servers', 'cf7-antispam'), // Title
			array( $this, 'cf7a_dnsbl_list_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_dnsbl' // Section
		);



		// Section b8
		add_settings_section( 'cf7a_b8', // ID
			__('B8 statistical "Bayesian" spam filter', 'cf7-antispam'), // Title
			array( $this, 'cf7a_print_b8' ), // Callback
			'cf7a-settings' // Page
		);

		// Enable b8
		add_settings_field( 'enable_b8', // ID
			__('Enable b8', 'cf7-antispam'), // Title
			array( $this, 'cf7a_enable_b8_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_b8' // Section
		);

		// Settings b8_threshold
		add_settings_field( 'b8_threshold', // ID
			__('b8 spam threshold', 'cf7-antispam'), // Title
			array( $this, 'cf7a_b8_threshold_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_b8' // Section
		);
	}

	public function cf7a_print_section_bot_fingerprint() {
		printf( '<p>' . esc_html__("Enable some extra check to detect bot activity", 'cf7-amntispam') . '</p>' );
	}
	public function cf7a_print_section_check_time() {
		printf( '<p>' . esc_html__("This test the submission time", 'cf7-amntispam') . '</p>' );
	}

	public function cf7a_print_section_bad_words() {
		printf( '<p>' . esc_html__("Check if the mail message contains bad words", 'cf7-amntispam') . '</p>' );
	}

	public function cf7a_print_section_bad_email_strings() {
		printf( '<p>' . esc_html__("Check if the sender mail contains a prohibited text", 'cf7-amntispam') . '</p>' );
	}

	public function cf7a_print_user_agent() {
		printf( '<p>' . esc_html__("Check the User Agent if is listed into blacklist", 'cf7-amntispam') . '</p>' );
	}

	public function cf7a_print_dnsbl() {
		printf( '<p>' . esc_html__("Check sender ip on DNS Blacklists", 'cf7-amntispam') . '</p>' );
	}

	public function cf7a_print_b8() {
		printf( '<p>' . esc_html__("Tells you whether a text is spam or not, using statistical text analysis of the text message", 'cf7-amntispam') . '</p>' );
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 * @return array $new_input sanitized input
	 */
	public function cf7a_sanitize( $input ) {

		// get the existing options
		$new_input = $this->options;

		// bot fingerprint
		$new_input['check_bot_fingerprint'] =  isset( $input['check_bot_fingerprint'] ) ? 1 : 0 ;
		$new_input['check_bot_fingerprint_extras'] =  isset( $input['check_bot_fingerprint_extras'] ) ? 1 : 0 ;

		$new_input['bot_fingerprint_tolerance'] =  isset( $input['bot_fingerprint_tolerance'] ) ? intval( $input['bot_fingerprint_tolerance'] ) : 2 ;


		// elapsed time
		$new_input['check_time'] =  isset( $input['check_time'] ) ? 1 : 0 ;

		if ( isset( $input['check_time_min'] ) ) {
			$new_input['check_time_min'] = intval( $input['check_time_min'] );
		}
		if ( isset( $input['check_time_max'] ) ) {
			$new_input['check_time_max'] = intval( $input['check_time_max'] );
		}


		// bad words
		$new_input['check_bad_words'] =  isset( $input['check_bad_words'] ) ? 1 : 0 ;

		if ( isset( $input['bad_words_list'] ) ) {
			$new_input['bad_words_list'] = explode("\r\n",sanitize_textarea_field( $input['bad_words_list'] ));
		}


		// email strings
		$new_input['check_bad_email_strings'] =  isset( $input['check_bad_email_strings'] ) ? 1 : 0 ;

		if ( isset( $input['bad_email_strings_list'] ) ) {
			$new_input['bad_email_strings_list'] = explode("\r\n",sanitize_textarea_field( $input['bad_email_strings_list'] ));
		}


		// user_agent
		$new_input['check_bad_user_agent'] =  isset( $input['check_bad_user_agent'] ) ? 1 : 0 ;

		if ( isset( $input['bad_user_agent_list'] ) ) {
			$new_input['bad_user_agent_list'] = explode("\r\n",sanitize_textarea_field( $input['bad_user_agent_list'] ));
		}


		// dnsbl
		$new_input['check_dnsbl'] =  isset( $input['check_dnsbl'] ) ? 1 : 0 ;

		$new_input['dnsbl_tolerance'] =  isset( $input['dnsbl_tolerance'] ) ? intval( $input['dnsbl_tolerance'] ) : 2 ;

		if ( isset( $input['dnsbl_list'] ) ) {
			$new_input['dnsbl_list'] = explode("\r\n",sanitize_textarea_field( $input['dnsbl_list'] ));
		}


		// b8
		$new_input['enable_b8'] =  isset( $input['enable_b8'] ) ? 1 : 0 ;

		$threshold = floatval($input['b8_threshold']);
		$new_input['b8_threshold'] = ($threshold > 0 && $threshold < 1) ? $threshold : 1;

		// store the sanitized options
		return $new_input;
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function cf7a_check_bot_fingerprint_callback() {
		printf(
			'<input type="checkbox" id="check_bot_fingerprint" name="cf7a_options[check_bot_fingerprint]" %s />',
			isset( $this->options['check_bot_fingerprint'] ) && $this->options['check_bot_fingerprint'] == 1 ? 'checked="true"' : ''
		);
	}
	public function cf7a_check_bot_fingerprint_extras_callback() {
		printf(
			'<input type="checkbox" id="check_bot_fingerprint_extras" name="cf7a_options[check_bot_fingerprint_extras]" %s />',
			isset( $this->options['check_bot_fingerprint_extras'] ) && $this->options['check_bot_fingerprint_extras'] == 1 ? 'checked="true"' : ''
		);
	}
	public function cf7a_bot_fingerprint_tolerance_callback() {
		printf(
			'<input type="number" id="bot_fingerprint_tolerance" name="cf7a_options[bot_fingerprint_tolerance]" value="%s" min="1" step="1" />',
			isset( $this->options['bot_fingerprint_tolerance'] ) ? esc_attr( $this->options['bot_fingerprint_tolerance']) : 'none'
		);
	}


	public function cf7a_check_time_callback() {
		printf(
			'<input type="checkbox" id="check_time" name="cf7a_options[check_time]" %s />',
			isset( $this->options['check_time'] ) && $this->options['check_time'] == 1 ? 'checked="true"' : ''
		);
	}
	public function cf7a_check_time_min_callback() {
		printf(
			'<input type="number" id="check_time_min" name="cf7a_options[check_time_min]" value="%s" />',
			isset( $this->options['check_time_min'] ) ? esc_attr( $this->options['check_time_min']) : 'none'
		);
	}
	public function cf7a_check_time_max_callback() {
		printf(
			'<input type="number" id="check_time_max" name="cf7a_options[check_time_max]" value="%s" />',
			isset( $this->options['check_time_max'] ) ? esc_attr( $this->options['check_time_max']) : 'none'
		);
	}


	public function cf7a_bad_words_callback() {
		printf(
			'<input type="checkbox" id="check_bad_words" name="cf7a_options[check_bad_words]" %s />',
			isset( $this->options['check_bad_words'] ) && $this->options['check_bad_words'] == 1 ? 'checked="true"' : ''
		);
	}
	public function cf7a_bad_words_list_callback() {
		printf(
			'<textarea id="bad_words_list" name="cf7a_options[bad_words_list]" />%s</textarea>',
			isset( $this->options['bad_words_list'] ) && is_array($this->options['bad_words_list']) ? implode("\r\n", $this->options['bad_words_list'] ) : ''
		);
	}


	public function cf7a_check_bad_email_strings_callback() {
		printf(
			'<input type="checkbox" id="check_bad_email_strings" name="cf7a_options[check_bad_email_strings]" %s />',
			isset( $this->options['check_bad_email_strings'] ) && $this->options['check_bad_email_strings'] == 1 ? 'checked="true"' : ''
		);
	}
	public function cf7a_bad_email_strings_list_callback() {
		printf(
			'<textarea id="bad_email_strings_list" name="cf7a_options[bad_email_strings_list]" />%s</textarea>',
			isset( $this->options['bad_email_strings_list'] ) && is_array($this->options['bad_email_strings_list']) ? implode("\r\n", $this->options['bad_email_strings_list'] ) : ''
		);
	}


	public function cf7a_check_user_agent_callback() {
		printf(
			'<input type="checkbox" id="check_bad_user_agent" name="cf7a_options[check_bad_user_agent]" %s />',
			isset( $this->options['check_bad_user_agent'] ) && $this->options['check_bad_user_agent'] == 1 ? 'checked="true"' : ''
		);
	}
	public function cf7a_user_agent_list_callback() {
		printf(
			'<textarea id="bad_user_agent_list" name="cf7a_options[bad_user_agent_list]" />%s</textarea>',
			isset( $this->options['bad_user_agent_list'] ) && is_array($this->options['bad_user_agent_list']) ? implode("\r\n", $this->options['bad_user_agent_list'] ) : ''
		);
	}


	public function cf7a_check_dnsbl_callback() {
		printf(
			'<input type="checkbox" id="check_dnsbl" name="cf7a_options[check_dnsbl]" %s />',
			isset( $this->options['check_dnsbl'] ) && $this->options['check_dnsbl'] == 1 ? 'checked="true"' : ''
		);
	}
	public function cf7a_dnsbl_tolerance_callback() {
		printf(
			'<input type="number" id="dnsbl_tolerance" name="cf7a_options[dnsbl_tolerance]" value="%s"  min="1" step="1" />',
			isset( $this->options['dnsbl_tolerance'] ) ? esc_attr( $this->options['dnsbl_tolerance']) : 'none'
		);
	}
	public function cf7a_dnsbl_list_callback() {
		printf(
			'<textarea id="dnsbl_list" name="cf7a_options[dnsbl_list]" />%s</textarea>',
			isset( $this->options['dnsbl_list'] ) && is_array($this->options['dnsbl_list']) ? implode("\r\n", $this->options['dnsbl_list'] ) : ''
		);
	}


	public function cf7a_enable_b8_callback() {
		printf(
			'<input type="checkbox" id="enable_b8" name="cf7a_options[enable_b8]" %s />',
			isset( $this->options['enable_b8'] ) && $this->options['enable_b8'] == 1 ? 'checked="true"' : ''
		);
	}
	public function cf7a_b8_threshold_callback() {
		printf(
			'<input type="number" id="b8_threshold" name="cf7a_options[b8_threshold]" value="%s" min="0" max="1" step="0.01" /> <small>(0-1)</small>',
			isset( $this->options['b8_threshold'] ) ? esc_attr( $this->options['b8_threshold']) : 'none'
		);
	}
}
