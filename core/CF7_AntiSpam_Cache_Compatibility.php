<?php
/**
 * Cache compatibility related stuff
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/includes
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core;

/**
 * A class that handles cache compatibility
 */
class CF7_AntiSpam_Cache_Compatibility {


	/**
	 * The ID of this plugin.
	 *
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * The options of this plugin.
	 *
	 * @access   private
	 * @var      array    $options    options of this plugin.
	 */
	private $options;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version The current version number of the plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->options     = CF7_AntiSpam::get_options();
	}

	/**
	 * Handles loading scripts
	 *
	 * @return void
	 */
	public function setup() {
		// Run this very early in the posted data processing
		add_filter( 'wpcf7_posted_data', array( $this, 'cf7a_refresh_cached_fields' ), 0 );
	}

	/**
	 * Refreshes the cached hidden fields with actual server-side values.
	 * This ensures that even if the form HTML is cached, the validation relies on the actual request data.
	 *
	 * @param array $posted_data The posted data.
	 *
	 * @return array The posted data.
	 */
	public function cf7a_refresh_cached_fields( $posted_data ) {

		$prefix = sanitize_html_class( $this->options['cf7a_customizations_prefix'] );
		$cipher = $this->options['cf7a_cipher'];

		// 1. Refresh IP Address
		// The cached form contains the IP of the first visitor. We overwrite it with the real IP of the current visitor.
		$address_key = $prefix . 'address';
		$real_ip     = cf7a_get_real_ip();

		if ( ! empty( $real_ip ) ) {
			// We MUST update $_POST because CF7_AntiSpam_Filters reads directly from $_POST
			$_POST[ $address_key ] = cf7a_crypt( $real_ip, $cipher );
		}

		// 2. Refresh Language
		if ( isset( $this->options['check_language'] ) && intval( $this->options['check_language'] ) === 1 ) {
			$lang_key           = $prefix . '_language';
			$accept             = empty( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? false : sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) );
			$val                = $accept ? $accept : 'language not detected';
			$_POST[ $lang_key ] = cf7a_crypt( $val, $cipher );
		}

		// 3. Refresh Referrer
		// The cached form has the referrer of the first visitor.
		$referer_key           = $prefix . 'referer';
		$referrer              = ! empty( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		$_POST[ $referer_key ] = cf7a_crypt( $referrer, $cipher );

		// 4. Refresh Protocol
		$protocol_key           = $prefix . 'protocol';
		$protocol               = ! empty( $_SERVER['SERVER_PROTOCOL'] ) ? esc_url_raw( wp_unslash( $_SERVER['SERVER_PROTOCOL'] ) ) : '';
		$_POST[ $protocol_key ] = cf7a_crypt( $protocol, $cipher );

		return $posted_data;
	}
}
