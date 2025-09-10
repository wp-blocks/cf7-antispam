<?php

namespace CF7_AntiSpam\Core;

/**
 * REST API related functions.
 *
 * @since      0.6.5
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/includes
 * @author     Codekraft Studio <info@codekraft.it>
 */
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Handles REST API endpoints for the CF7 AntiSpam plugin
 */
class CF7_AntiSpam_Rest_Api extends WP_REST_Controller {

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
	 * CF7_AntiSpam_Rest_Api constructor.
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
	 * Validate and sanitize API parameters.
	 *
	 * @since    0.6.5
	 * @param    mixed $value   The value to validate
	 * @param    string $type   The expected type
	 * @return   mixed|WP_Error
	 */
	private function cf7a_validate_param( $value, $type = 'string' ) {

		switch ( $type ) {
			case 'string':
				return is_string( $value ) ? sanitize_text_field( $value ) : new WP_Error( 'invalid_param', 'Parameter must be a string' );

			case 'int':
				return is_numeric( $value ) ? intval( $value ) : new WP_Error( 'invalid_param', 'Parameter must be an integer' );

			case 'bool':
				return is_bool( $value ) || in_array( $value, array( 'true', 'false', '1', '0' ), true );

			default:
				return $value;
		}
	}

	/**
	 * Check if a given request has access to read plugin data.
	 *
	 * @since    0.6.5
	 * @param    WP_REST_Request $request Full data about the request.
	 * @return   WP_Error|bool
	 */
	public function cf7a_get_permissions_check( $request ) {

		/* check if user can manage options */
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You cannot view the plugin status.' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}

	/**
	 * Get plugin status information.
	 *
	 * @since    0.6.5
	 * @param    WP_REST_Request $request Full data about the request.
	 * @return   WP_Error|WP_REST_Response
	 */
	public function cf7a_get_status( $request ) {

		$data = array(
			'plugin_version' => CF7ANTISPAM_VERSION,
			'status'         => $this->options['cf7a_enable'] ? 'enabled' : 'disabled',
			'timestamp'      => current_time( 'timestamp' ),
		);

		return rest_ensure_response( $data );
	}

	/**
	 * Resend a specific email.
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response A response object or a WP_Error object. The response object contains the message.
	 */
	public function cf7a_resend_message( $request ) {

		$mail_id = (int) substr( $request['id'], 12 );

		if ( $mail_id > 1 ) {
			$cf7a_flamingo = new CF7_AntiSpam_Flamingo();
			$r             = $cf7a_flamingo->cf7a_resend_mail( $mail_id );

			if ( 'empty' === $r ) {
				/* translators: %s is the mail id. */
				return rest_ensure_response( array( 'message' => sprintf( __( 'Email id %s has an empty body', 'success cf7-antispam' ), $mail_id ) ) );
			}

			if ( $r ) {
				/* translators: %s is the mail id. */
				return rest_ensure_response( array( 'message' => sprintf( __( 'Email id %s sent with success', 'success cf7-antispam' ), $mail_id ) ) );
			}
		}

		/* translators: %s is the mail id. */
		return rest_ensure_response( array( 'message' => sprintf( __( 'Ops! something went wrong... unable to resend email with id %s', 'error cf7-antispam' ), $mail_id ) ) );
	}

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since    0.6.5
	 */
	public function cf7a_register_routes() {

		register_rest_route(
			$this->namespace,
			'status',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'cf7a_get_status' ),
					'permission_callback' => array( $this, 'cf7a_get_status_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'resend_message',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'cf7a_resend_message' ),
					'permission_callback' => array( $this, 'cf7a_get_permissions_check' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => function( $param ) {
								return $this->cf7a_validate_param( $param );
							},
						),
						'nonce' => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => function( $param ) {
								return $this->cf7a_validate_param( $param );
							},
						)
					),
				),
			)
		);
	}
}
