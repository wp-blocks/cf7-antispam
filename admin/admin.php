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

		// the menu item
		new CF7_AntiSpam_Admin_Customizations();

		$tools = new CF7_AntiSpam_Admin_Tools();

		add_filter( 'admin_body_class', array( $this, 'cf7a_body_class' ));

		add_action( 'admin_notices', array( $this, 'cf7a_display_notices' ) );

		add_action( 'admin_menu', array( $this, 'cf7a_admin_menu' ), 10, 0 );

		add_action( 'plugin_action_links_'.CF7ANTISPAM_PLUGIN_BASENAME, array($this, 'cf7a_plugin_settings_link'), 10, 2 );
	}

	public function cf7a_admin_menu() {
		add_submenu_page( 'wpcf7',
			__( 'Antispam', $this->plugin_name ),
			__( 'Antispam', $this->plugin_name ),
			'wpcf7_edit_contact_forms',
			$this->plugin_name,
			array( $this, 'cf7a_admin_dashboard' )
		);
	}

	/**
	 * Add go to settings link on plugin page.
	 *
	 * @since 0.2.2
	 *
	 * @param  array $links Array of plugin action links.
	 * @return array Modified array of plugin action links.
	 */
	public function cf7a_plugin_settings_link( array $links ) {
		$settings_page_link = '<a href="' . admin_url( 'admin.php?page=cf7-antispam' ) . '">' . esc_attr__( 'Antispam Settings', 'cf7-antispam' ) . '</a>';
		array_unshift( $links, $settings_page_link );

		return $links;
	}

	public function cf7a_admin_dashboard() {
		$admin_display = new CF7_AntiSpam_Admin_Display();
		$admin_display->display_dashboard();
	}

	public function cf7a_display_notices() {

		$admin_page = get_current_screen();
		if (false === strpos($admin_page->base, $this->plugin_name )) return;

		$settings_updated = isset( $_REQUEST['settings-updated'] ) ? sanitize_text_field( $_REQUEST['settings-updated'] ) : false;
		if ( $settings_updated === 'true' ) {
			CF7_AntiSpam_Admin_Tools::cf7a_push_notice( __( 'Antispam setting updated with success', 'cf7-antispam' ), "success" );
		}

		if ( false !== ( $notice = get_transient( 'cf7a_notice' )) ) {
			echo $notice;
			delete_transient( 'cf7a_notice' );
		}
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

	public function cf7a_body_class( $classes ) {
		$admin_page = get_current_screen();
		if (false === strpos($admin_page->base, $this->plugin_name )) return $classes;
        return "$classes cf7-antispam-admin";
	}
}
