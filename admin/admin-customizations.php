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
		// the menu item
		add_action( 'admin_menu', array( $this, 'cf7a_admin_menu' ), 99, 0 );
		// the plugin main menu
		add_action( 'admin_init', array( $this, 'cf7a_options_init' ) );

		$this->options = $this->get_options(); // the plugin options
	}

	/**
	 * the CF7 AntiSpam upload folder to store csv files
	 * @return string
	 */
	public static function get_options() {
		return get_option( 'cf7a_options' );
	}


	public function cf7a_admin_menu() {

		$addnew = add_submenu_page( 'wpcf7', __( 'Antispam', 'cf7-antispam' ), __( 'Antispam', 'cf7-antispam' ), 'wpcf7_edit_contact_forms', 'wpcf7-antispam', array( $this, 'cf7a_admin_dashboard' ) );

		add_action( 'load-' . $addnew, 'wpcf7_load_contact_form_admin', 10, 0 );
	}

	public function cf7a_admin_dashboard() {
		require CF7ANTISPAM_PLUGIN_DIR . '/admin/admin-display.php';
		print_r("options<br/>");
		print_r($this->options);
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
	}

	public function cf7a_print_section_check_time() {
		print '<p>This test the submission time</p>';
	}

	public function cf7a_print_section_bad_words() {
		print '<p>Check if the mail message contains bad words</p>';
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

		$new_input['check_time'] =  isset( $input['check_time'] ) ? 1 : 0 ;

		if ( isset( $input['check_time_min'] ) ) {
			$new_input['check_time_min'] = intval( $input['check_time_min'] );
		}
		if ( isset( $input['check_time_max'] ) ) {
			$new_input['check_time_max'] = intval( $input['check_time_max'] );
		}


		$new_input['check_bad_words'] =  isset( $input['check_bad_words'] ) ? 1 : 0 ;

		if ( isset( $input['bad_words_list'] ) ) {
			$new_input['bad_words_list'] = explode("\r\n",sanitize_text_field( $input['bad_words_list'] ));
		}

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


}
