<?php
/**
 * REST API related functions.
 *
 * @since      0.6.5
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/includes
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core;

use CF7_AntiSpam\Engine\CF7_AntiSpam_Activator;
use CF7_AntiSpam\Engine\CF7_AntiSpam_Uninstaller;
use CF7_AntiSpam\Engine\CF7_AntiSpam_Updater;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

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

	/**
	 * Download the GeoIP database.
	 *
	 * @since    0.6.5
	 * @param    WP_REST_Request $request Full data about the request.
	 * @return   WP_REST_Response
	 */
	public function cf7a_download_geoip_db( $request ) {
		/** Verify nonce */
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
			'timestamp'      => date_i18n( 'Y-m-d H:i:s' ),
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
		/** Verify nonce */
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
						/* translators: %s is the mail id. */
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
		}//end if

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
		/** Verify nonce */
		if ( ! wp_verify_nonce( $request['nonce'], 'cf7a-nonce' ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce', 'cf7-antispam' ),
				)
			);
		}

		// Hack the updater option version to force update
		$this->options['cf7a_version'] = '0.0.0';

		// Update the plugin database
		$updater = new CF7_AntiSpam_Updater( CF7ANTISPAM_VERSION, $this->options );
		$res     = $updater->may_do_updates();

		// Update the plugin options
		CF7_AntiSpam_Activator::update_options();

		// if the update fails
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
	public function cf7a_reset_blocklist( $request ) {
		/** Verify nonce */
		if ( ! wp_verify_nonce( $request['nonce'], 'cf7a-nonce' ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce', 'cf7-antispam' ),
				)
			);
		}

		/* uninstall class contains the database utility functions */
		$r = CF7_AntiSpam_Uninstaller::cf7a_clean_blocklist();

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
		/** Verify nonce */
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
		/** Verify nonce */
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
		/** Verify nonce */
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
		/** Verify nonce */
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

		$blocklist = new CF7_Antispam_Blocklist();
		$r         = $blocklist->cf7a_unban_by_id( $unban_id );

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
		/** Verify nonce */
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

		$blocklist = new CF7_Antispam_Blocklist();
		$result    = $blocklist->cf7a_ban_forever( $ban_id );

		return rest_ensure_response( $result );
	}

	/**
	 * Export blocklist as CSV.
	 *
	 * @since    0.6.5
	 * @param    WP_REST_Request $request Full data about the request.
	 * @return   WP_REST_Response
	 */
	public function cf7a_export_blocklist( $request ) {
		/** Verify nonce */
		if ( ! wp_verify_nonce( $request['nonce'], 'cf7a-nonce' ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce', 'cf7-antispam' ),
				)
			);
		}

		$blocklist   = new CF7_Antispam_Blocklist();
		$export_data = $blocklist->cf7a_export_blocklist();

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
	private function cf7a_get_blocklist_data() {
		$blocklist = new CF7_Antispam_Blocklist();
		return $blocklist->cf7a_get_blocklist_data();
	}

	/**
	 * Get wordlist data with pagination and filtering.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request Full data about the request.
	 * @return   WP_REST_Response
	 */
	public function cf7a_get_wordlist( $request ) {
		global $wpdb;

		$page     = isset( $request['page'] ) ? max( 1, intval( $request['page'] ) ) : 1;
		$per_page = isset( $request['per_page'] ) ? min( 100, max( 10, intval( $request['per_page'] ) ) ) : 50;
		$type     = isset( $request['type'] ) ? sanitize_text_field( $request['type'] ) : 'all';
		$search   = isset( $request['search'] ) ? sanitize_text_field( $request['search'] ) : '';
		$orderby  = isset( $request['orderby'] ) ? sanitize_text_field( $request['orderby'] ) : 'measure';
		$order    = isset( $request['order'] ) ? strtoupper( sanitize_text_field( $request['order'] ) ) : 'DESC';
		$offset   = ( $page - 1 ) * $per_page;

		$table = $wpdb->prefix . 'cf7a_wordlist';

		// Build WHERE clause
		$where_clauses = array( "token != 'b8*texts'", "token != 'b8*dbversion'" );

		if ( 'spam' === $type ) {
			$where_clauses[] = 'count_spam > 0';
		} elseif ( 'ham' === $type ) {
			$where_clauses[] = 'count_ham > 0';
		}

		if ( ! empty( $search ) ) {
			$where_clauses[] = $wpdb->prepare( 'token LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' );
		}

		$where = implode( ' AND ', $where_clauses );

		// Validate order params
		if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
			$order = 'DESC';
		}

		$allowed_orderby = array( 'token', 'count_spam', 'count_ham', 'measure' );
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'measure';
		}

		$order_clause = '';
		switch ( $orderby ) {
			case 'token':
				$order_clause = "token {$order}";
				break;
			case 'count_spam':
				$order_clause = "count_spam {$order}";
				break;
			case 'count_ham':
				$order_clause = "count_ham {$order}";
				break;
			case 'measure':
			default:
				$order_clause = "(COALESCE(count_spam, 0) + COALESCE(count_ham, 0)) {$order}";
				break;
		}

		// Get total count
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i WHERE {$where}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$table
			)
		);

		// Get paginated results
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$words = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT token, count_spam, count_ham FROM %i WHERE {$where} ORDER BY {$order_clause} LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$table,
				$per_page,
				$offset
			)
		);

		return rest_ensure_response(
			array(
				'success'     => true,
				'words'       => $words,
				'total'       => intval( $total ),
				'page'        => $page,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total / $per_page ),
			)
		);
	}

	/**
	 * Update a word's spam/ham counts.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request Full data about the request.
	 * @return   WP_REST_Response
	 */
	public function cf7a_update_word( $request ) {
		global $wpdb;

		/** Verify nonce */
		if ( ! wp_verify_nonce( $request['nonce'], 'cf7a-nonce' ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce', 'cf7-antispam' ),
				)
			);
		}

		$token      = isset( $request['token'] ) ? sanitize_text_field( $request['token'] ) : '';
		$count_spam = isset( $request['count_spam'] ) ? max( 0, intval( $request['count_spam'] ) ) : null;
		$count_ham  = isset( $request['count_ham'] ) ? max( 0, intval( $request['count_ham'] ) ) : null;

		if ( empty( $token ) || in_array( $token, array( 'b8*texts', 'b8*dbversion' ), true ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid token', 'cf7-antispam' ),
				)
			);
		}

		$table = $wpdb->prefix . 'cf7a_wordlist';

		$update_data = array();
		if ( null !== $count_spam ) {
			$update_data['count_spam'] = $count_spam;
		}
		if ( null !== $count_ham ) {
			$update_data['count_ham'] = $count_ham;
		}

		if ( empty( $update_data ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'No data to update', 'cf7-antispam' ),
				)
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$table,
			$update_data,
			array( 'token' => $token ),
			array_fill( 0, count( $update_data ), '%d' ),
			array( '%s' )
		);

		// Clear wordlist cache
		wp_cache_delete( 'cf7a_top_spam_words', 'cf7a_wordlist_stats' );
		wp_cache_delete( 'cf7a_top_ham_words', 'cf7a_wordlist_stats' );

		if ( false !== $result ) {
			return rest_ensure_response(
				array(
					'success' => true,
					/* translators: %s is the token. */
					'message' => sprintf( __( 'Word "%s" updated successfully', 'cf7-antispam' ), $token ),
				)
			);
		}

		return rest_ensure_response(
			array(
				'success' => false,
				'message' => __( 'Failed to update word', 'cf7-antispam' ),
			)
		);
	}

	/**
	 * Delete a word from the dictionary.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request Full data about the request.
	 * @return   WP_REST_Response
	 */
	public function cf7a_delete_word( $request ) {
		global $wpdb;

		/** Verify nonce */
		if ( ! wp_verify_nonce( $request['nonce'], 'cf7a-nonce' ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce', 'cf7-antispam' ),
				)
			);
		}

		$token = isset( $request['token'] ) ? sanitize_text_field( $request['token'] ) : '';

		if ( empty( $token ) || in_array( $token, array( 'b8*texts', 'b8*dbversion' ), true ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid token', 'cf7-antispam' ),
				)
			);
		}

		$table = $wpdb->prefix . 'cf7a_wordlist';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$table,
			array( 'token' => $token ),
			array( '%s' )
		);

		// Clear wordlist cache
		wp_cache_delete( 'cf7a_top_spam_words', 'cf7a_wordlist_stats' );
		wp_cache_delete( 'cf7a_top_ham_words', 'cf7a_wordlist_stats' );

		if ( $result ) {
			return rest_ensure_response(
				array(
					'success' => true,
					/* translators: %s is the token. */
					'message' => sprintf( __( 'Word "%s" deleted successfully', 'cf7-antispam' ), $token ),
				)
			);
		}

		return rest_ensure_response(
			array(
				'success' => false,
				'message' => __( 'Failed to delete word', 'cf7-antispam' ),
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
					'callback'            => array( $this, 'cf7a_reset_blocklist' ),
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
					'callback'            => array( $this, 'cf7a_get_blocklist_data' ),
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
					'callback'            => array( $this, 'cf7a_export_blocklist' ),
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

		// Wordlist management routes
		register_rest_route(
			$this->namespace,
			'get-wordlist',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'cf7a_get_wordlist' ),
					'permission_callback' => array( $this, 'cf7a_get_permissions_check' ),
					'args'                => array(
						'page'     => array(
							'required' => false,
							'type'     => 'integer',
							'default'  => 1,
						),
						'per_page' => array(
							'required' => false,
							'type'     => 'integer',
							'default'  => 50,
						),
						'type'     => array(
							'required' => false,
							'type'     => 'string',
							'default'  => 'all',
						),
						'search'   => array(
							'required' => false,
							'type'     => 'string',
							'default'  => '',
						),
						'orderby'  => array(
							'required' => false,
							'type'     => 'string',
							'default'  => 'measure',
						),
						'order'    => array(
							'required' => false,
							'type'     => 'string',
							'default'  => 'DESC',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'update-word',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'cf7a_update_word' ),
					'permission_callback' => array( $this, 'cf7a_get_permissions_check' ),
					'args'                => array(
						'token'      => array(
							'required' => true,
							'type'     => 'string',
						),
						'count_spam' => array(
							'required' => false,
							'type'     => 'integer',
						),
						'count_ham'  => array(
							'required' => false,
							'type'     => 'integer',
						),
						'nonce'      => array(
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
			'delete-word',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'cf7a_delete_word' ),
					'permission_callback' => array( $this, 'cf7a_get_permissions_check' ),
					'args'                => array(
						'token' => array(
							'required' => true,
							'type'     => 'string',
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
	}
}
