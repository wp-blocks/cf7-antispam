<?php
/**
 * Filter for Endpoint Obfuscation.
 *
 * @since      0.6.6
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core/Filters
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core\Filters;

use CF7_AntiSpam\Core\CF7_AntiSpam;
use CF7_AntiSpam\Core\CF7_Antispam_Blocklist;
use WP_REST_Response;

/**
 * Class Filter_Endpoint_Obfuscation
 */
class Filter_Endpoint_Obfuscation {

	/**
	 * The options of this plugin.
	 *
	 * @var array $options
	 */
	private $options;

	/**
	 * Filter_Endpoint_Obfuscation constructor.
	 */
	public function __construct() {
		$this->options = CF7_AntiSpam::get_options();

		// Always register the blocked-bot REST endpoint (used by both parse_request redirect and decoy trap).
		add_action( 'rest_api_init', array( $this, 'register_blocked_bot_route' ) );

		// Only proceed with obfuscation if the feature is enabled.
		if ( empty( $this->options['obfuscate_cf7_endpoint'] ) ) {
			return;
		}

		// Intercept the request early to rewrite the route internally.
		add_action( 'parse_request', array( $this, 'rewrite_rest_route' ), 1 );

		// Update the frontend JS object.
		add_action( 'wp_enqueue_scripts', array( $this, 'update_frontend_namespace' ), 20 );
	}

	/**
	 * Register the "blocked-bot" REST endpoint.
	 * Any POST to this route will trigger an IP ban and return a 403 Forbidden.
	 */
	public function register_blocked_bot_route() {
		register_rest_route(
			'cf7-antispam/v1',
			'/blocked-bot',
			array(
				'methods'             => \WP_REST_Server::ALLMETHODS,
				'callback'            => array( $this, 'handle_blocked_bot_request' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle any request to the blocked-bot endpoint:
	 * ban the IP and return a 403 Forbidden.
	 *
	 * @return WP_REST_Response
	 */
	public function handle_blocked_bot_request() {
		if ( function_exists( 'cf7a_get_real_ip' ) ) {
			$ip = cf7a_get_real_ip();
			if ( $ip ) {
				CF7_Antispam_Blocklist::cf7a_ban_by_ip(
					$ip,
					array( 'honeyform_trap' ),
					5
				);
				cf7a_log( "Honeyform Bot Trap triggered: banned IP {$ip} via blocked-bot endpoint." );
			}
		}

		return new WP_REST_Response( array( 'code' => 'forbidden' ), 403 );
	}

	/**
	 * Get the configured endpoint slug.
	 *
	 * @return string The endpoint slug.
	 */
	private function get_endpoint_slug() {
		$slug = ! empty( $this->options['cf7a_endpoint_slug'] ) ? $this->options['cf7a_endpoint_slug'] : 'cf7-antispam/v1/secure';
		/**
		 * Filters the endpoint slug.
		 *
		 * @param string $slug The endpoint slug.
		 */
		return apply_filters( 'cf7a_endpoint_slug', $slug );
	}

	/**
	 * Rewrite the REST route internally so WordPress can process it.
	 *
	 * @param \WP $wp The WordPress environment object.
	 */
	public function rewrite_rest_route( $wp ) {
		// Check if this is a REST API request.
		if ( empty( $wp->query_vars['rest_route'] ) ) {
			return;
		}

		$route = $wp->query_vars['rest_route'];

		$old_namespace = '/contact-form-7/v1';
		$new_namespace = '/' . ltrim( $this->get_endpoint_slug(), '/' );

		if ( strpos( $route, $new_namespace ) === 0 ) {
			// If the incoming request uses the NEW obfuscated namespace (Legitimate User) rewrite it back to the original CF7 namespace so the WP REST Server handles it natively.
			$wp->query_vars['rest_route'] = str_replace( $new_namespace, $old_namespace, $route );
		} elseif ( strpos( $route, $old_namespace ) === 0 ) {
			// If the incoming request uses the OLD default namespace directly (Spam Bot) redirect to the blocked-bot handler.
			$wp->query_vars['rest_route'] = '/cf7-antispam/v1/blocked-bot';
		}
	}

	/**
	 * Update the frontend JS object to point to the new endpoint.
	 */
	public function update_frontend_namespace() {
		$new_namespace = trim( $this->get_endpoint_slug(), '/' );

		$custom_script = "
			if ( typeof wpcf7 !== 'undefined' && wpcf7.api ) {
				wpcf7.api.namespace = '{$new_namespace}';
			}
		";

		wp_add_inline_script( 'contact-form-7', $custom_script, 'after' );
	}
}
