<?php
/**
 * Public REST API related functions.
 *
 * @since      0.6.5
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/includes
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Handles Public REST API endpoints for the CF7 AntiSpam plugin
 */
class CF7_AntiSpam_Public_Rest_Api extends WP_REST_Controller {

	/**
	 * The cf7a rest endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'cf7-antispam/v1';

	/**
	 * The plugin options.
	 *
	 * @var array
	 */
	private array $options;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->options = CF7_AntiSpam::get_options();
		add_action( 'rest_api_init', array( $this, 'cf7a_register_routes' ) );
	}

	/**
	 * Get the current timestamp encrypted for REST API.
	 *
	 * @return WP_REST_Response The response object.
	 */
	public function cf7a_get_timestamp_callback() {
		// Prevent aggressive browser/edge caching
		nocache_headers();

		// Check for a freshly generated timestamp to prevent CPU exhaustion
		$cached_timestamp = get_transient( 'cf7a_public_timestamp' );

		if ( false === $cached_timestamp ) {
			$cipher           = ! empty( $this->options['cf7a_cipher'] ) ? $this->options['cf7a_cipher'] : 'aes-256-cbc';
			$cached_timestamp = cf7a_crypt( time(), $cipher );

			// Cache for 30 seconds to absorb bot floods
			set_transient( 'cf7a_public_timestamp', $cached_timestamp, 30 );
		}

		return rest_ensure_response(
			array(
				'timestamp' => $cached_timestamp,
			)
		);
	}

	/**
	 * Get a distributed-bot token and map it to the request IP.
	 */
	public function cf7a_get_ip_token_callback() {
		// Prevent caching so every user gets a unique token
		nocache_headers();

		$token = bin2hex( random_bytes( 8 ) );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP ) : false;

		if ( $ip ) {
			// 15 minutes is plenty of time for a user to fill out a contact form.
			set_transient( 'cf7a_ip_token_' . $token, $ip, 15 * MINUTE_IN_SECONDS );
		}

		return rest_ensure_response(
			array(
				'token' => $token,
			)
		);
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function cf7a_register_routes() {

		register_rest_route(
			$this->namespace,
			'get-timestamp',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'cf7a_get_timestamp_callback' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'get-ip-token',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'cf7a_get_ip_token_callback' ),
					'permission_callback' => '__return_true',
				),
			)
		);
	}
}
