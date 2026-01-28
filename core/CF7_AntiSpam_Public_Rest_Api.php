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

use WP_REST_Controller;
use WP_REST_Server;

/**
 * Handles Public REST API endpoints for the CF7 AntiSpam plugin
 */
class CF7_AntiSpam_Public_Rest_Api extends WP_REST_Controller {

	/**
	 * The namespace of this controller's route.
	 *
	 * @since    0.6.5
	 * @access   protected
	 * @var      string    $namespace    The namespace of this controller's route.
	 */
	protected $namespace = 'cf7-antispam/v1';

	/**
	 * The options of this plugin.
	 *
	 * @since    0.6.5
	 * @access   private
	 * @var      array    $options    options of this plugin.
	 */
	private array $options;

	/**
	 * CF7_AntiSpam_Public_Rest_Api constructor.
	 *
	 * @since    0.6.5
	 */
	public function __construct() {

		/* the plugin options */
		$this->options = CF7_AntiSpam::get_options();

		/* register the routes */
		add_action( 'rest_api_init', array( $this, 'cf7a_register_routes' ) );
	}

	/**
	 * Get the current timestamp encrypted for REST API.
	 *
	 * @since    0.6.5
	 * @param    \WP_REST_Request $request Full data about the request.
	 * @return   \WP_REST_Response
	 */
	public function cf7a_get_timestamp_callback( $request ) {
		$cipher = ! empty( $this->options['cf7a_cipher'] ) ? $this->options['cf7a_cipher'] : 'aes-256-cbc';
		return rest_ensure_response(
			array(
				'timestamp' => cf7a_crypt( time(), $cipher ),
				'cypher'    => $cipher,
			)
		);
	}

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since    0.6.5
	 */
	public function cf7a_register_routes() {

		register_rest_route(
			$this->namespace,
			'get-timestamp',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'cf7a_get_timestamp_callback' ),
					'permission_callback' => '__return_true',
				),
			)
		);
	}
}
