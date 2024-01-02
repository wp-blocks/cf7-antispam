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
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/includes
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core;

use CF7_AntiSpam\Admin\CF7_AntiSpam_Admin_Core;
use CF7_AntiSpam\Admin\CF7_AntiSpam_Admin_Tools;
use CF7_AntiSpam\Engine\CF7_AntiSpam_Activator;

/**
 * It sets the version, plugin name, and options. It loads
 * the dependencies, sets the locale, updates the plugin, and loads the admin and frontend areas
 */
class CF7_AntiSpam {

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
	 * The options of this plugin.
	 *
	 * @since    0.1.0
	 * @access   public
	 * @var      array    $options    options of this plugin.
	 */
	private $options;


	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      CF7_AntiSpam_Loader   $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The constructor function is called when the plugin is loaded. It sets the version, plugin name, and options. It loads
	 * the dependencies, sets the locale, updates the plugin, and loads the admin and frontend areas
	 */
	public function __construct() {
		if ( defined( 'CF7ANTISPAM_VERSION' ) ) {
			$this->version = CF7ANTISPAM_VERSION;
		} else {
			$this->version = '0.0.1';
		}

		$this->plugin_name = CF7ANTISPAM_NAME;
		$this->options     = $this->get_options(); /* the plugin options */

		/* the php files */
		$this->load_dependencies();

		/* the i18n */
		$this->set_locale();

		/* the update / install stuff */
		if ( empty( $this->options['cf7a_version'] ) || $this->version !== $this->options['cf7a_version'] ) {

			/* the php files */
			$this->update();

			if ( get_transient( 'cf7a_activation' ) ) {
				if ( defined( 'FLAMINGO_VERSION' ) ) {
					$cf7a_flamingo = new CF7_AntiSpam_Flamingo();
					$cf7a_flamingo->cf7a_flamingo_on_install();
				}
				delete_transient( 'cf7a_activation' );
			}
		}

		/* the antispam service */
		$this->load_antispam();

		/* the admin area */
		$this->load_admin();

		/* the frontend area */
		$this->load_frontend();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the CF7_AntiSpam_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    0.2.4
	 * @access   protected
	 */
	protected function update() {
		do_action( 'cf7a_update' );
		CF7_AntiSpam_Activator::update_options();
	}

	/**
	 * It loads the plugin's dependencies
	 */
	private function load_dependencies() {
		$this->loader = new CF7_AntiSpam_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the CF7_AntiSpam_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    0.1.0
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
	 * @since    0.1.0
	 * @access   private
	 */
	private function load_antispam() {

		/* the spam filter */
		$plugin_antispam = new CF7_AntiSpam_Filters();

		/* the spam filter */
		$this->loader->add_filter( 'wpcf7_spam', $plugin_antispam, 'cf7a_spam_filter', 8 );

		/* the unspam routine */
		add_action( 'cf7a_cron', array( $plugin_antispam, 'cf7a_cron_unban' ) );

		if ( defined( 'FLAMINGO_VERSION' ) ) {
			$cf7a_flamingo = new CF7_AntiSpam_Flamingo();

			/* if flamingo is defined the mail will be analyzed after flamingo has stored */
			add_action( 'wpcf7_after_flamingo', array( $cf7a_flamingo, 'cf7a_flamingo_store_additional_data' ), 11 );

			/* remove honeypot fields before store into database */
			add_action( 'wpcf7_after_flamingo', array( $cf7a_flamingo, 'cf7a_flamingo_remove_honeypot' ), 12 );
		}

		if ( ! empty( $this->options['enable_geoip_download'] ) ) {
			$geo = new CF7_Antispam_Geoip();

			add_action( 'cf7a_geoip_update_db', array( $geo, 'cf7a_geoip_download_database' ) );
		}

		if ( defined( 'CF7_SMTP_NAME' ) ) {
			add_filter( 'cf7_smtp_report_mailbody', array( $this, 'spam_mail_report' ), 10, 2 );
		}
	}

	/**
	 * Register all the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 */
	private function load_admin() {
		if ( is_admin() ) {

			/* It handles the actions that are triggered by the user */
			$tools = new CF7_AntiSpam_Admin_Tools();
			add_action( 'admin_init', array( $tools, 'cf7a_handle_actions' ), 1 );

			/* the admin area */
			$plugin_admin = new CF7_AntiSpam_Admin_Core( $this->get_plugin_name(), $this->get_version() );

			/* add the admin menu */
			$this->loader->add_action( 'admin_menu', $plugin_admin, 'cf7a_admin_menu' );

			/* adds a class to the cf7-antispam admin area */
			$this->loader->add_filter( 'admin_body_class', $plugin_admin, 'cf7a_body_class' );

			/* scripts and styles enqueue */
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

			/* in the plugin page add "go to settings" near the enable plugin button  */
			$this->loader->add_action( 'plugin_action_links_' . CF7ANTISPAM_PLUGIN_BASENAME, $plugin_admin, 'cf7a_plugin_settings_link', 10, 2 );

			/* displays admin notices */
			$this->loader->add_action( 'admin_notices', $plugin_admin, 'cf7a_display_notices' );

			/* if flamingo is enabled use submitted spam / ham to feed d8 */
			if ( defined( 'FLAMINGO_VERSION' ) ) {
				$cf7a_flamingo = new CF7_AntiSpam_Flamingo();
				/* the action that handles the spam and ham requests and pass the mail message to b8 */
				add_action( 'load-flamingo_page_flamingo_inbound', array( $cf7a_flamingo, 'cf7a_d8_flamingo_classify' ), 9, 0 );

				$this->loader->add_action( 'wp_dashboard_setup', $plugin_admin, 'cf7a_dashboard_widget' );

				/* adds the custom table columns*/
				add_filter( 'manage_flamingo_inbound_posts_columns', array( $cf7a_flamingo, 'flamingo_columns' ) );
				add_action( 'manage_flamingo_inbound_posts_custom_column', array( $cf7a_flamingo, 'flamingo_d8_column' ), 10, 2 );
				add_action( 'manage_flamingo_inbound_posts_custom_column', array( $cf7a_flamingo, 'flamingo_resend_column' ), 11, 2 );
			}
		}
	}

	/**
	 * Register all the hooks related to the frontend area functionality
	 * of the plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 */
	private function load_frontend() {
		if ( ! is_admin() ) {
			global $post;
			$plugin_frontend = new CF7_AntiSpam_Frontend( $this->get_plugin_name(), $this->get_version() );

			/* It adds hidden fields to the form */
			$this->loader->add_filter( 'wpcf7_form_hidden_fields', $plugin_frontend, 'cf7a_add_hidden_fields', 1 );
			$this->loader->add_filter( 'wpcf7_config_validator_available_error_codes', $plugin_frontend, 'cf7a_remove_cf7_error_message', 10, 2 );

			/* adds the javascript script to frontend */
			$this->loader->add_action( 'wp_footer', $plugin_frontend, 'enqueue_scripts' );

			if ( $post ) {
				$this->options['check_bot_fingerprint'] = apply_filters( $this->options['check_bot_fingerprint'], $post->ID );
			}

			/* It adds a hidden field to the form with a unique value that is encrypted with a cipher */
			if ( isset( $this->options['check_bot_fingerprint'] ) && intval( $this->options['check_bot_fingerprint'] ) === 1 ) {
				$this->loader->add_filter( 'wpcf7_form_hidden_fields', $plugin_frontend, 'cf7a_add_bot_fingerprinting', 100 );
			}

			/* It adds a new field to the form, which is a hidden field that will be populated with the bot fingerprinting extras */
			if ( isset( $this->options['check_bot_fingerprint_extras'] ) && intval( $this->options['check_bot_fingerprint_extras'] ) === 1 ) {
				$this->loader->add_filter( 'wpcf7_form_hidden_fields', $plugin_frontend, 'cf7a_add_bot_fingerprinting_extras', 100 );
			}

			/* It adds a new field to the form, called `cf7a_append_on_submit`, and sets it to false */
			if ( isset( $this->options['append_on_submit'] ) && intval( $this->options['append_on_submit'] ) === 1 ) {
				$this->loader->add_filter( 'wpcf7_form_hidden_fields', $plugin_frontend, 'cf7a_append_on_submit', 100 );
			}

			/* It takes the form elements, clones the text inputs, adds a class to the cloned inputs, and adds the cloned inputs to the form */
			if ( isset( $this->options['check_honeypot'] ) && intval( $this->options['check_honeypot'] ) === 1 ) {
				$this->loader->add_filter( 'wpcf7_form_elements', $plugin_frontend, 'cf7a_honeypot_add' );
			}

			/* It gets the form, formats it, and then echoes it out */
			if ( isset( $this->options['check_honeyform'] ) && intval( $this->options['check_honeyform'] ) === 1 ) {
				$this->loader->add_filter( 'the_content', $plugin_frontend, 'cf7a_honeyform', 99 );
			}

			/* Checking if the user has selected the option to protect the user's identity. If they have, it will call the function to protect the user's identity. */
			if ( isset( $this->options['identity_protection_user'] ) && intval( $this->options['identity_protection_user'] ) === 1 ) {
				$plugin_frontend->cf7a_protect_user();
			}

			/* It removes the WordPress version from the header, removes the REST API link from the header, removes headers that disposes information */
			if ( isset( $this->options['identity_protection_wp'] ) && intval( $this->options['identity_protection_wp'] ) === 1 ) {
				$this->loader->add_filter( 'wp_headers', $plugin_frontend, 'cf7a_protect_wp', 999 );
			}

			/* Will check if the form has been submitted more than once, blocking all emails that were sent after the first one for a period of 5 seconds */
			if ( isset( $this->options['mailbox_protection_multiple_send'] ) && intval( $this->options['mailbox_protection_multiple_send'] ) === 1 ) {
				$this->loader->add_action( 'wpcf7_before_send_mail', $plugin_frontend, 'cf7a_check_resend', 9, 3 );
			}

			/* It adds a CSS style to the page that hides the honeypot field */
			if (
				( isset( $this->options['check_honeypot'] ) && 1 === intval( $this->options['check_honeypot'] ) ) || ( isset( $this->options['check_honeyform'] ) && 1 === intval( $this->options['check_honeyform'] ) )
			) {
				$this->loader->add_action( 'wp_footer', $plugin_frontend, 'cf7a_add_honeypot_css', 11 );
			}
		}
	}

	/**
	 * Run the loader to execute all the hooks with WordPress.
	 *
	 * @since    0.1.0
	 */
	public function run() {
		if ( defined( 'WPCF7_VERSION' ) ) {
			$this->loader->run();
		} else {
			add_action(
				'admin_notices',
				function () {
					printf(
						'<div class="notice notice-info"><p>%s<a href="%s">%s</a>%s</p></div>',
						esc_html__( 'CF7 AntiSpam need ', 'cf7-antispam' ),
						esc_url_raw( 'https://wordpress.org/plugins/contact-form-7/' ),
						esc_html__( 'Contact Form 7', 'cf7-antispam' ),
						esc_html__( ' installed and enabled in order to work.', 'cf7-antispam' )
					);
				}
			);
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
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
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
	 * CF7 AntiSpam options
	 *
	 * @return array the plugin options
	 */
	public static function get_options() {
		return get_option( 'cf7a_options' );
	}

	/**
	 * CF7 AntiSpam update options function
	 *
	 * @since 0.4.0
	 *
	 * @param  array $options the plugin options.
	 * @return bool
	 */
	public static function update_plugin_options( $options ) {
		return update_option( 'cf7a_options', $options );
	}

	/**
	 * CF7 AntiSpam a function to update a single option
	 *
	 * @since 0.4.0
	 *
	 * @param string $option the option that you need to change.
	 * @param mixed  $value the new option value.
	 *
	 * @return bool
	 */
	public static function update_plugin_option( $option, $value ) {
		$plugin_options = self::get_options();

		if ( isset( $plugin_options[ $option ] ) ) {
			if ( is_string( $value ) ) {
				/* if the value is a string sanitize and replace the option */
				$plugin_options[ $option ] = sanitize_text_field( trim( $value ) );
			} else {
				/* if the value is an array sanitize each element then merge into option */
				$new_values = array();
				foreach ( $value as $array_value ) {
					$new_values[] = trim( (string) $array_value );
				}
				$plugin_options[ $option ] = array_unique( array_merge( $plugin_options[ $option ], $new_values ) );
			}

			return self::update_plugin_options( $plugin_options );
		}

		return false;
	}

	public function spam_mail_report( $mail_body, $last_report_timestamp ) {
		global $wpdb;

		$all  = $wpdb->get_var(
			"SELECT COUNT(*) AS cnt
			 FROM {$wpdb->prefix}posts
			 WHERE post_status = 'flamingo-spam';"
		);
		$last = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) AS cnt
		 	 FROM {$wpdb->prefix}posts
		 	 WHERE post_date_gmt >= FROM_UNIXTIME( %d )
			 AND post_status = 'flamingo-spam';",
				$last_report_timestamp
			)
		);

		$mail_body .= __( \sprintf( '<p>%s overall spam attempts, %s since last report</p>', $all, $last ), 'cf7-antispam' );

		return $mail_body;
	}
}
