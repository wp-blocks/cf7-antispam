<?php

namespace CF7_AntiSpam\Admin;

use CF7_AntiSpam\Core\CF7_Antispam_Geoip;

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

/**
 * It creates a class called CF7_AntiSpam_Admin.
 */
class CF7_AntiSpam_Admin_Core {

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
	 * The geoip class
	 *
	 * @since    0.4.6
	 * @access   private
	 * @var      CF7_Antispam_Geoip $geoip
	 */
	private $geoip;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.1.0
	 *
	 * @param      string $plugin_name The name of this plugin.
	 * @param      string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		/* Setting the value of the $plugin_name */
		$this->plugin_name = $plugin_name;

		/* Setting the version of the plugin */
		$this->version = $version;

		/* The menu item */
		new CF7_AntiSpam_Admin_Customizations();

		$this->geoip = new CF7_Antispam_Geoip();
		$this->geoip->cf7a_geo_maybe_download();
	}



	/**
	 * It adds a submenu page to the Contact Form 7 menu in the admin dashboard
	 */
	public function cf7a_admin_menu() {
		add_submenu_page(
			'wpcf7',
			__( 'Antispam', 'cf7-antispam' ),
			__( 'Antispam', 'cf7-antispam' ),
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
		$options = get_option( 'cf7a_options' );
		if ( $options['cf7a_enable'] ) {
			$settings_page_link = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=cf7-antispam' ), esc_html__( 'Antispam Settings', 'cf7-antispam' ) );
		} else {
			$settings_page_link = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=wpcf7-integration' ), esc_html__( 'Activate Contact Form 7 integration', 'cf7-antispam' ) );
		}
		array_unshift( $links, $settings_page_link );

		return $links;
	}

	/**
	 * It creates a new instance of the CF7_AntiSpam_Admin_Display class and then calls the display_dashboard() method on that
	 * instance
	 */
	public function cf7a_admin_dashboard() {
		$admin_display = new CF7_AntiSpam_Admin_Display();
		$admin_display->display_dashboard();
	}

	/**
	 * If the current admin page is not the plugin's admin page, return. Otherwise, if the settings have been updated, display
	 * a success message. Otherwise, if there's a notice in the transient, display it and delete the transient
	 *
	 * @return void
	 */
	public function cf7a_display_notices() {

		/* It checks if the current admin page is the plugin's admin page. If it is not, it returns. */
		$admin_page = get_current_screen();
		if ( false === strpos( $admin_page->base, $this->plugin_name ) ) {
			return;
		}

		/* It checks if the settings have been updated, and if so, it displays a success message. */
		$settings_updated = isset( $_REQUEST['settings-updated'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['settings-updated'] ) ) : false;
		if ( 'true' === $settings_updated ) {
			CF7_AntiSpam_Admin_Tools::cf7a_push_notice( esc_html__( 'Antispam setting updated with success', 'cf7-antispam' ), 'success' );
		}

		/* if there is a notice stored, print it then delete the transient */
		$notice = get_transient( 'cf7a_notice' );
		if ( ! empty( $notice ) ) {
			echo wp_kses(
				$notice,
				array(
					'div'    => array(
						'class' => array(),
					),
					'p'      => array(),
					'strong' => array(),
				)
			);
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
		 * defined in load_admin as all the hooks are defined
		 * in that particular class.
		 *
		 * The load_admin will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_style( $this->plugin_name, CF7ANTISPAM_PLUGIN_URL . '/build/admin-scripts.css', array(), $this->version );
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
		 * defined in load_admin as all the hooks are defined
		 * in that particular class.
		 *
		 * The load_admin will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		$asset = include CF7ANTISPAM_PLUGIN_DIR . '/build/admin-scripts.asset.php';
		wp_register_script( $this->plugin_name, CF7ANTISPAM_PLUGIN_URL . '/build/admin-scripts.js', $asset['dependencies'], $asset['version'], true );
		wp_enqueue_script( $this->plugin_name );

		wp_localize_script(
			$this->plugin_name,
			'cf7a_admin_settings',
			array(
				'alertMessage' => esc_html__( 'Are you sure?', 'cf7-antispam' ),
			)
		);
	}

	/**
	 * If the current admin page is not a Contact Form 7 Anti-Spam page, then return the $classes variable. Otherwise, return
	 * the $classes variable with the string "cf7-antispam-admin" appended to it
	 *
	 * @param string $classes Space-separated list of CSS classes.
	 *
	 * @return string $classes The $classes variable is being returned.
	 */
	public function cf7a_body_class( $classes ) {
		$admin_page = get_current_screen();
		if ( false === strpos( $admin_page->base, $this->plugin_name ) ) {
			return $classes;
		}
		return "$classes cf7-antispam-admin";
	}

	/**
	 * It adds a dashboard widget to the WordPress admin dashboard
	 */
	public function cf7a_dashboard_widget() {
		global $wp_meta_boxes;
		$cf7a_charts = new CF7_AntiSpam_Admin_Charts();
		wp_add_dashboard_widget( 'cf7a-widget', __( 'Stats for CF7 Antispam', 'cf7-antispam' ), array( $cf7a_charts, 'cf7a_flamingo_widget' ) );
	}
}
