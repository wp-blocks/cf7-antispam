<?php

require_once CF7ANTISPAM_PLUGIN_DIR . '/admin/admin-customizations.php';

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/admin
 * @author     Codekraft Studio <info@codekraft.it>
 */
class CF7_AntiSpam_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * The version of this plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $nonce;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.1.0
	 *
	 * @param      string $plugin_name The name of this plugin.
	 * @param      string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->nonce     = wp_create_nonce('cf7a-nonce');


		// the menu item
		add_action( 'admin_menu', array( $this, 'cf7a_admin_menu' ), 10, 0 );

		$tools = new CF7_AntiSpam_Admin_Tools();

		new CF7_AntiSpam_Admin_Customizations();

		add_action( 'admin_init', array( $tools, 'cf7a_handle_blacklist' ));
	}


	public function cf7a_admin_menu() {
		$addnew = add_submenu_page( 'wpcf7', __( 'Antispam', 'cf7-antispam' ), __( 'Antispam', 'cf7-antispam' ), 'wpcf7_edit_contact_forms', 'cf7-antispam', array( $this, 'cf7a_admin_dashboard' ) );

		add_action( 'load-' . $addnew, 'wpcf7_load_contact_form_admin', 10, 0 );
	}

	public function cf7a_admin_dashboard() {
		require CF7ANTISPAM_PLUGIN_DIR . '/admin/admin-display.php';
	}

	public function cf7a_get_nonce() {
		return $this->nonce;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    0.1.0
	 */
	public function enqueue_styles() {

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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/style.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    0.1.0
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/admin-script.js', array(), $this->version, true );

	}
}