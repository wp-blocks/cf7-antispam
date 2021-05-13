<?php
class CF7_AntiSpam_Frontend {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * The options of this plugin.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      array    $options    options of this plugin.
	 */
	private $options;

	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		add_filter( 'wpcf7_form_hidden_fields', array( $this, 'cf7a_add_hidden_fields') , 100, 1 );

		$this->options = CF7_AntiSpam::get_options(); // the plugin options

		if (isset($this->options['check_bot_fingerprint'])) {
			add_filter( 'wpcf7_form_hidden_fields', array( $this, 'cf7a_add_bot_fingerprinting') , 100, 1 );
			add_action( "wp_enqueue_scripts" , 'enqueue_script' , 1);
		}
	}

	public function cf7a_add_hidden_fields( $fields ) {

		$timestamp = time();

		$ip = get_real_ip();

		return array_merge( $fields, array(
			'_wpcf7a_version' => $this->version,
			'_wpcf7a_real_sender_ip' => cf7a_crypt($ip),
			'_wpcf7a_form_creation_timestamp' => cf7a_crypt($timestamp),
		));
	}

	public function cf7a_add_bot_fingerprinting( $fields ) {
		return array_merge( $fields, array(
			'_wpcf7a_bot_fingerprint' => wp_hash_password(time())
		));
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in load_admin as all of the hooks are defined
		 * in that particular class.
		 *
		 * The load_admin will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/script.js', array( 'jquery' ), $this->version, true );
	}
}