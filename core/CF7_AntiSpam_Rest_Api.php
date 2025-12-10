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

use CF7_AntiSpam\Engine\CF7_AntiSpam_Activator;
use CF7_AntiSpam\Engine\CF7_AntiSpam_Uninstaller;
use CF7_AntiSpam\Engine\CF7_AntiSpam_Updater;
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
	 * @param    mixed  $value   The value to validate
	 * @param    string $type   The expected type
	 * @return   mixed|WP_Error
	 */
	private function cf7a_validate_param( $value, $type = 'string' ) {

		switch ( $type ) {
			case 'nonce':
				return wp_verify_nonce( $value, 'cf7a-nonce' );

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
				esc_html__( 'You cannot view the plugin status.', 'cf7-antispam' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}

	public function cf7a_download_geoip_db( $request ) {
		/** verify nonce */
		if ( ! wp_verify_nonce( $request['nonce'], 'cf7a-nonce' ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce', 'cf7-antispam' ),
				)
			);
		}

		$geoip = new CF7_AntiSpam_Geoip();
		$res   = $geoip->force_download();

		if ( ! $res ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Error: unable to download GeoIP database', 'cf7-antispam' ),
				)
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'GeoIP database downloaded successfully', 'cf7-antispam' ),
			)
		);
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
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response A response object or a WP_Error object. The response object contains the message.
	 */
	public function cf7a_resend_message( $request ) {
		/** verify nonce */
		if ( ! wp_verify_nonce( $request['nonce'], 'cf7a-nonce' ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce', 'cf7-antispam' ),
				)
			);
		}

		$mail_id = intval( $request['id'] );

		if ( $mail_id > 1 ) {
			$cf7a_flamingo = new CF7_AntiSpam_Flamingo();
			$r             = $cf7a_flamingo->cf7a_resend_mail( $mail_id );

			if ( ! $r['success'] ) {
				/* translators: %s is the mail id. */
				return rest_ensure_response(
					array(
						'success' => false,
						'message' => sprintf( __( 'Error: unable to resend email with id %s.', 'cf7-antispam' ), $mail_id ) . ' ' . $r['message'],
						'log'     => $r['log'],
					)
				);
			}

			if ( $r ) {
				return rest_ensure_response(
					array(
						'success' => true,
						'message' => $r['message'],
					)
				);
			}
		}

		return rest_ensure_response(
			array(
				'success' => false,
				/* translators: %s is the mail id. */
				'message' => sprintf( __( 'Ops! something went wrong... unable to resend email with id %s', 'cf7-antispam' ), $mail_id ),
			)
		);
	}

	/**
	 * Force update the dictionary.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response A response object or a WP_Error object. The response object contains the message.
	 */
	public function cf7a_force_update( $request ) {
		/** verify nonce */
		if ( ! wp_verify_nonce( $request['nonce'], 'cf7a-nonce' ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce', 'cf7-antispam' ),
				)
			);
		}

		/* Update the plugin database */
		$updater = new CF7_AntiSpam_Updater( CF7ANTISPAM_VERSION, $this->options );
		$res     = $updater->may_do_updates();

		/* Update the plugin options */
		CF7_AntiSpam_Activator::update_options();

		// if update failed
		if ( ! $res ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Nothing to update', 'cf7-antispam' ),
				)
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Contact Form 7 Antispam Options and Database updated successfully!', 'cf7-antispam' ),
			)
		);
	}

	/**
	 * Reset the blocklist.
	 *
	 * @since    0.6.5
	 * @param    WP_REST_Request $request Full data about the request.
	 * @return   WP_REST_Response
	 */
	public function cf7a_reset_blacklist( $request ) {
		/** verify nonce */
		if ( ! wp_verify_nonce( $request['nonce'], 'cf7a-nonce' ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce', 'cf7-antispam' ),
				)
			);
		}

		/* uninstall class contains the database utility functions */
		$r = CF7_AntiSpam_Uninstaller::cf7a_clean_blacklist();

		if ( $r ) {
			return rest_ensure_response(
				array(
					'success' => true,
					'message' => __( 'Success: ip blocklist cleaned', 'cf7-antispam' ),
				)
			);
		} else {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Error: unable to clean blocklist. Please refresh and try again!', 'cf7-antispam' ),
				)
			);
		}
	}

	/**
	 * Reset the dictionary.
	 *
	 * @since    0.6.5
	 * @param    WP_REST_Request $request Full data about the request.
	 * @return   WP_REST_Response
	 */
	public function cf7a_reset_dictionary( $request ) {
		/** verify nonce */
		if ( ! wp_verify_nonce( $request['nonce'], 'cf7a-nonce' ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce', 'cf7-antispam' ),
				)
			);
		}

		/* uninstall class contains the database utility functions */
		$r = CF7_AntiSpam_Flamingo::cf7a_reset_dictionary();

		if ( $r ) {
			return rest_ensure_response(
				array(
					'success' => true,
					'message' => __( 'b8 dictionary reset successful', 'cf7-antispam' ),
				)
			);
		} else {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Something goes wrong while deleting b8 dictionary. Please refresh and try again!', 'cf7-antispam' ),
				)
			);
		}
	}

	/**
	 * Full reset of the plugin.
	 *
	 * @since    0.6.5
	 * @param    WP_REST_Request $request Full data about the request.
	 * @return   WP_REST_Response
	 */
	public function cf7a_full_reset( $request ) {
		/** verify nonce */
		if ( ! wp_verify_nonce( $request['nonce'], 'cf7a-nonce' ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce', 'cf7-antispam' ),
				)
			);
		}

		/* uninstall class contains the database utility functions */
		$r = CF7_AntiSpam_Uninstaller::cf7a_full_reset();

		if ( $r ) {
			return rest_ensure_response(
				array(
					'success' => true,
					'message' => __( 'CF7 AntiSpam fully reinitialized with success. You need to rebuild B8 manually if needed', 'cf7-antispam' ),
				)
			);
		} else {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Ops! something went wrong... Please refresh and try again!', 'cf7-antispam' ),
				)
			);
		}
	}

	/**
	 * Rebuild the dictionary.
	 *
	 * @since    0.6.5
	 * @param    WP_REST_Request $request Full data about the request.
	 * @return   WP_REST_Response
	 */
	public function cf7a_rebuild_dictionary( $request ) {
		/** verify nonce */
		if ( ! wp_verify_nonce( $request['nonce'], 'cf7a-nonce' ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce', 'cf7-antispam' ),
				)
			);
		}

		$r = CF7_AntiSpam_Flamingo::cf7a_rebuild_dictionary();

		if ( $r ) {
			return rest_ensure_response(
				array(
					'success' => true,
					'message' => __( 'b8 dictionary rebuild successful', 'cf7-antispam' ),
				)
			);
		} else {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Something goes wrong while rebuilding b8 dictionary. Please refresh and try again!', 'cf7-antispam' ),
				)
			);
		}
	}


	/**
	 * Unban a single IP by ID.
	 *
	 * @since    0.6.5
	 * @param    WP_REST_Request $request Full data about the request.
	 * @return   WP_REST_Response
	 */
	public function cf7a_unban_ip( $request ) {
		/** verify nonce */
		if ( ! wp_verify_nonce( $request['nonce'], 'cf7a-nonce' ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce', 'cf7-antispam' ),
				)
			);
		}

		$unban_id = intval( $request['id'] );

		if ( $unban_id <= 0 ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid ID', 'cf7-antispam' ),
				)
			);
		}

		$blacklist = new CF7_Antispam_Blacklist();
		$r         = $blacklist->cf7a_unban_by_id( $unban_id );

		if ( $r ) {
			return rest_ensure_response(
				array(
					'success' => true,
					/* translators: %s is the ip address. */
					'message' => sprintf( __( 'Success: ip %s unbanned', 'cf7-antispam' ), $unban_id ),
				)
			);
		} else {
			return rest_ensure_response(
				array(
					'success' => false,
					/* translators: %s is the ip address. */
					'message' => sprintf( __( 'Error: unable to unban %s', 'cf7-antispam' ), $unban_id ),
				)
			);
		}
	}

	/**
	 * Ban forever a single IP by ID.
	 *
	 * @since    0.6.5
	 * @param    WP_REST_Request $request Full data about the request.
	 * @return   WP_REST_Response
	 */
	public function cf7a_ban_forever( $request ) {
		/** verify nonce */
		if ( ! wp_verify_nonce( $request['nonce'], 'cf7a-nonce' ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce', 'cf7-antispam' ),
				)
			);
		}

		$ban_id = intval( $request['id'] );

		if ( $ban_id <= 0 ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid ID', 'cf7-antispam' ),
				)
			);
		}

		$blacklist = new CF7_Antispam_Blacklist();
		$result    = $blacklist->cf7a_ban_forever( $ban_id );

		return rest_ensure_response( $result );
	}

	/**
	 * Export blocklist as CSV.
	 *
	 * @since    0.6.5
	 * @param    WP_REST_Request $request Full data about the request.
	 * @return   WP_REST_Response
	 */
	public function cf7a_export_blacklist( $request ) {
		/** verify nonce */
		if ( ! wp_verify_nonce( $request['nonce'], 'cf7a-nonce' ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce', 'cf7-antispam' ),
				)
			);
		}

		$blacklist   = new CF7_Antispam_Blacklist();
		$export_data = $blacklist->cf7a_export_blacklist();

		return rest_ensure_response(
			array(
				'success'  => true,
				'message'  => __( 'Blocklist exported successfully', 'cf7-antispam' ),
				'filetype' => $export_data['filetype'],
				'filename' => $export_data['filename'],
				'data'     => $export_data['data'],
			)
		);
	}

	/**
	 * Helper method to get blocklist data.
	 * This should call the actual method that retrieves the blocklist from database.
	 *
	 * @since    0.6.5
	 * @return   array
	 */
	private function cf7a_get_blacklist_data() {
		$blacklist = new CF7_Antispam_Blacklist();
		return $blacklist->cf7a_get_blacklist_data();
	}


	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since    0.6.5
	 */
	public function cf7a_register_routes() {

		register_rest_route(
			$this->namespace,
			'force-geoip-download',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'cf7a_download_geoip_db' ),
					'permission_callback' => array( $this, 'cf7a_get_permissions_check' ),
					'args'                => array(
						'nonce' => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => function ( $param ) {
								return $this->cf7a_validate_param( $param, 'nonce' );
							},
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'status',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'cf7a_get_status' ),
					'permission_callback' => array( $this, 'cf7a_get_permissions_check' ),
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
						'id'    => array(
							'required'          => true,
							'type'              => 'integer',
							'validate_callback' => function ( $param ) {
								return $this->cf7a_validate_param( $param, 'int' );
							},
						),
						'nonce' => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => function ( $param ) {
								return $this->cf7a_validate_param( $param, 'nonce' );
							},
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'force-update',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'cf7a_force_update' ),
					'permission_callback' => array( $this, 'cf7a_get_permissions_check' ),
					'args'                => array(
						'nonce' => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => function ( $param ) {
								return $this->cf7a_validate_param( $param, 'nonce' );
							},
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'reset-blocklist',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'cf7a_reset_blacklist' ),
					'permission_callback' => array( $this, 'cf7a_get_permissions_check' ),
					'args'                => array(
						'nonce' => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => function ( $param ) {
								return $this->cf7a_validate_param( $param, 'nonce' );
							},
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'reset-dictionary',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'cf7a_reset_dictionary' ),
					'permission_callback' => array( $this, 'cf7a_get_permissions_check' ),
					'args'                => array(
						'nonce' => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => function ( $param ) {
								return $this->cf7a_validate_param( $param, 'nonce' );
							},
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'full-reset',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'cf7a_full_reset' ),
					'permission_callback' => array( $this, 'cf7a_get_permissions_check' ),
					'args'                => array(
						'nonce' => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => function ( $param ) {
								return $this->cf7a_validate_param( $param, 'nonce' );
							},
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'rebuild-dictionary',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'cf7a_rebuild_dictionary' ),
					'permission_callback' => array( $this, 'cf7a_get_permissions_check' ),
					'args'                => array(
						'nonce' => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => function ( $param ) {
								return $this->cf7a_validate_param( $param, 'nonce' );
							},
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'unban-ip',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'cf7a_unban_ip' ),
					'permission_callback' => array( $this, 'cf7a_get_permissions_check' ),
					'args'                => array(
						'id'    => array(
							'required'          => true,
							'type'              => 'integer',
							'validate_callback' => function ( $param ) {
								return $this->cf7a_validate_param( $param, 'int' );
							},
						),
						'nonce' => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => function ( $param ) {
								return $this->cf7a_validate_param( $param );
							},
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'ban-forever',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'cf7a_ban_forever' ),
					'permission_callback' => array( $this, 'cf7a_get_permissions_check' ),
					'args'                => array(
						'id'    => array(
							'required'          => true,
							'type'              => 'integer',
							'validate_callback' => function ( $param ) {
								return $this->cf7a_validate_param( $param, 'int' );
							},
						),
						'nonce' => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => function ( $param ) {
								return $this->cf7a_validate_param( $param );
							},
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'get-blocklist',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'cf7a_get_blacklist_data' ),
					'permission_callback' => array( $this, 'cf7a_get_permissions_check' ),
					'args'                => array(
						'nonce' => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => function ( $param ) {
								return $this->cf7a_validate_param( $param );
							},
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'export-blocklist',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'cf7a_export_blacklist' ),
					'permission_callback' => array( $this, 'cf7a_get_permissions_check' ),
					'args'                => array(
						'nonce' => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => function ( $param ) {
								return $this->cf7a_validate_param( $param );
							},
						),
					),
				),
			)
		);
	}
}
