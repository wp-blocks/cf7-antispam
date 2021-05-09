<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/includes
 * @author     Codekraft Studio <info@codekraft.it>
 */
class CF7_AntiSpam {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      CF7_AntiSpam_Loader   $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	public function __construct() {
		if ( defined( 'CF7ANTISPAM_VERSION' ) ) {
			$this->version = CF7ANTISPAM_VERSION;
		} else {
			$this->version = '1.0.0';
		}

		$this->plugin_name = 'contact-form-7-antispam';

		$this->load_dependencies();
		$this->set_locale();

		// the admin area
		if( is_admin() ){
			$this->load_admin();
		} else {
			$this->load_frontend();
		}
	}

	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once CF7ANTISPAM_PLUGIN_DIR . '/includes/cf7a-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once CF7ANTISPAM_PLUGIN_DIR . '/includes/cf7a-i18n.php';

		/**
		 * The class responsible for defining frontend functionality
		 * of the plugin.
		 */
		require_once CF7ANTISPAM_PLUGIN_DIR . '/includes/cf7a-frontend.php';

		/**
		 * The class responsible for defining antispam functionality
		 * of the plugin.
		 */
		require_once CF7ANTISPAM_PLUGIN_DIR . '/includes/cf7a-antispam.php';

		/**
		 * The class responsible for defining admin backend functionality
		 * of the plugin.
		 */
		require_once CF7ANTISPAM_PLUGIN_DIR . '/admin/admin-tools.php';
		require_once CF7ANTISPAM_PLUGIN_DIR . '/admin/admin.php';

		$this->loader = new CF7_AntiSpam_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the CF7_AntiSpam_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new CF7_AntiSpam_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_admin() {

		if (is_admin()) {
			new CF7_AntiSpam_Admin( $this->get_plugin_name(), $this->get_version() );
			new CF7_AntiSpam_Admin_Tools();
		}
	}

	/**
	 * Register all of the hooks related to the frontend area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_frontend() {
		new CF7_AntiSpam_Frontend();
	}

	/**
	 * Register all of the hooks related to the frontend area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_antispam() {
		new CF7_AntiSpam_Frontend();
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		if ( defined( 'WPCF7_VERSION' ) ) {
			$this->loader->run();
		} else {
			add_action('admin_notices', function () {
				echo CF7_AntiSpam_Admin_Tools::cf7a_push_notice( __("CF7 AntiSpam needs Contact Form 7 Activated in order to work", "cf7-antispam" ) );
			});
		}
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    CF7_AntiSpam_Loader Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}