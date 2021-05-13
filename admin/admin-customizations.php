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

		$this->options = $this->get_options(); // the plugin options
	}

	/**
	 * the CF7 AntiSpam options
	 * @return string
	 */
	public static function get_options() {
		return get_option( 'cf7a_options' );
	}


	public function cf7a_options_init() {

		// Group
		register_setting( 'cf7_antispam_options', // Option group
			'cf7a_options', // Option name
			array( $this, 'cf7a_sanitize' ) // Sanitize
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
			__('Check the message for prohibited words', 'cf7-antispam'), // Title
			array( $this, 'cf7a_check_bad_email_strings_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_bad_email_strings' // Section
		);

		// Settings bad_email_strings_list
		add_settings_field( 'bad_email_strings_list', // ID
			__('Bad words List', 'cf7-antispam'), // Title
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

		// Settings check_time
		add_settings_field( 'b8_threshold', // ID
			__('b8 spam threshold', 'cf7-antispam'), // Title
			array( $this, 'cf7a_b8_threshold_callback' ), // Callback
			'cf7a-settings', // Page
			'cf7a_b8' // Section
		);
	}

	public function cf7a_print_section_check_time() {
		print '<p>This test the submission time</p>';
	}

	public function cf7a_print_section_bad_words() {
		print '<p>Check if the mail message contains bad words</p>';
	}

	public function cf7a_print_section_bad_email_strings() {
		print '<p>Check if the sender mail contains a prohibited text</p>';
	}


	public function cf7a_print_user_agent() {
		print '<p>Check the User Agent if is listed into blacklist</p>';
	}

	public function cf7a_print_dnsbl() {
		print '<p>Check sender ip on DNS Blacklists</p>';
	}

	public function cf7a_print_b8() {
		print '<p>tells you whether a text is spam or not, using statistical text analysis of the text message</p>';
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 * @return array $new_input sanitized input
	 */
	public function cf7a_sanitize( $input ) {

		// get the existing options
		// $new_input = $this->options;

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
