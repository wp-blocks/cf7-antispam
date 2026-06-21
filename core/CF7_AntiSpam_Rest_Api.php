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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
			'timestamp'      => wp_date( 'Y-m-d H:i:s' ),
		);

		return rest_ensure_response( $data );
	}

	/**
	 * Get dashboard stats for charts and activity list.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request Full data about the request.
	 * @return   WP_Error|WP_REST_Response
	 */
	public function cf7a_get_dashboard_stats( $request ) {
		$period = $request->get_param( 'period' );
		if ( empty( $period ) ) {
			$period = 'week';
		}

		$date_after = '30 days ago';
		$limit      = 50;

		if ( 'week' === $period ) {
			$date_after = '7 days ago';
			$limit      = 25;
			// max_mail_count default is 25 for widget, 50 for main charts
		}

		// Instantiate the charts admin class to reuse its fetching/formatting logic
		$charts_admin = new \CF7_AntiSpam\Admin\CF7_AntiSpam_Admin_Charts();
		$stats        = $charts_admin->cf7a_get_dashboard_stats_data( $date_after, $limit );

		return rest_ensure_response( $stats );
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

		if ( $mail_id > 0 ) {
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
	 * Import blocklist from CSV.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request Full data about the request.
	 * @return   WP_REST_Response
	 */
	public function cf7a_import_blocklist( $request ) {
		if ( ! wp_verify_nonce( $request['nonce'], 'cf7a-nonce' ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce', 'cf7-antispam' ),
				)
			);
		}

		if ( empty( $_FILES['file'] ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'No file uploaded', 'cf7-antispam' ),
				)
			);
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$file        = $_FILES['file'];
		$wp_filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], array( 'csv' => 'text/csv' ) );
		if ( ! $wp_filetype['ext'] ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid file type. Only CSV allowed.', 'cf7-antispam' ),
				)
			);
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$upload_overrides = array( 'test_form' => false );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$movefile = wp_handle_upload( $_FILES['file'], $upload_overrides );

		if ( $movefile && ! isset( $movefile['error'] ) ) {
			$file_path = $movefile['file'];
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			$handle = fopen( $file_path, 'r' );

			if ( ! $handle ) {
				wp_delete_file( $file_path );
				return rest_ensure_response(
					array(
						'success' => false,
						'message' => __( 'Unable to open file', 'cf7-antispam' ),
					)
				);
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose( $handle );

			$count = $this->cf7a_parse_import_csv( $file_path );

			wp_delete_file( $file_path );

			return rest_ensure_response(
				array(
					'success' => true,
					/* translators: %d is the number of imported IPs. */
					'message' => sprintf( __( '%d IPs imported successfully.', 'cf7-antispam' ), $count ),
				)
			);
		} else {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => $movefile['error'],
				)
			);
		}//end if
	}

	/**
	 * Securely validate if a string is a valid IP or CIDR range.
	 *
	 * @param string $ip_string The IP or CIDR string.
	 * @return bool True if valid, false otherwise.
	 */
	private function cf7a_is_valid_ip_or_cidr( $ip_string ) {
		if ( strpos( $ip_string, '/' ) === false ) {
			return (bool) rest_is_ip_address( $ip_string );
		}

		$parts = explode( '/', $ip_string, 2 );
		if ( count( $parts ) !== 2 ) {
			return false;
		}

		$ip   = $parts[0];
		$mask = $parts[1];

		if ( ! rest_is_ip_address( $ip ) ) {
			return false;
		}

		// Ensure the mask is strictly an integer
		if ( ! is_numeric( $mask ) || strval( intval( $mask ) ) !== strval( $mask ) ) {
			return false;
		}

		$mask_int = intval( $mask );

		// Check bounds for IPv4 and IPv6
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			return $mask_int >= 1 && $mask_int <= 32;
		} elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			return $mask_int >= 1 && $mask_int <= 128;
		}

		return false;
	}

	/**
	 * Parse and process the imported CSV file.
	 *
	 * @since    1.0.0
	 * @param    string $file_path Absolute path to the CSV file.
	 * @return   int    Number of successfully imported/updated IPs.
	 */
	public function cf7a_parse_import_csv( $file_path ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$handle = fopen( $file_path, 'r' );
		if ( ! $handle ) {
			return 0;
		}

			$blocklist    = new CF7_Antispam_Blocklist();
			$count        = 0;
			$is_first     = true;
			$ip_index     = 0;
			$id_index     = -1;
			$status_index = -1;

		// phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
		while ( ( $data = fgetcsv( $handle, 0, ',', '"', '\\' ) ) !== false ) {
			if ( $is_first ) {
				$is_first = false;
				// Check if first column of first row is a valid IP/CIDR
				$first_cell = trim( $data[0] );
				$is_ip      = $this->cf7a_is_valid_ip_or_cidr( $first_cell );

				if ( ! $is_ip ) {
					// It's a header row. Find ID, IP, Status column indices.
					foreach ( $data as $i => $col ) {
						$col_lower = strtolower( trim( $col ) );
						if ( 'ip' === $col_lower ) {
							$ip_index = $i;
						} elseif ( 'id' === $col_lower ) {
							$id_index = $i;
						} elseif ( 'status' === $col_lower ) {
							$status_index = $i;
						}
					}
					continue;
					// skip header row
				}
			}//end if

			if ( ! isset( $data[ $ip_index ] ) ) {
				continue;
			}

			$ip = sanitize_text_field( trim( $data[ $ip_index ] ) );

			if ( empty( $ip ) ) {
				continue;
			}

			// Basic validation for IP or CIDR format
			$is_valid = $this->cf7a_is_valid_ip_or_cidr( $ip );

			if ( ! $is_valid ) {
				continue;
			}

			if ( CF7_Antispam_Blocklist::is_ip_allowlisted( $ip ) ) {
				continue;
			}

			$id     = null;
			$status = 1;

			if ( -1 !== $id_index && isset( $data[ $id_index ] ) && '' !== trim( $data[ $id_index ] ) ) {
				$id = intval( trim( $data[ $id_index ] ) );
			}

			if ( -1 !== $status_index && isset( $data[ $status_index ] ) ) {
				$status_val = sanitize_text_field( trim( $data[ $status_index ] ) );
				if ( '' !== $status_val ) {
					$status = $status_val;
				}
			}

			// Handle permanent ban
			if ( 'permanent' === strtolower( $status ) ) {
				$plugin_options  = CF7_AntiSpam::get_options();
				$current_bad_ips = $plugin_options['bad_ip_list'] ?? array();

				if ( ! in_array( $ip, $current_bad_ips, true ) ) {
					if ( CF7_AntiSpam::update_plugin_option( 'bad_ip_list', array_merge( $current_bad_ips, array( $ip ) ) ) ) {
						if ( $id ) {
							$blocklist->cf7a_unban_by_id( $id );
						}
						++$count;
					}
				} elseif ( $id ) {
					// Already permanently banned, just remove from blocklist table if it's there
					$blocklist->cf7a_unban_by_id( $id );
				}
				continue;
			}

			if ( $id ) {
				$result = $blocklist->cf7a_update_blocklist_by_id( $id, $ip, $status );
			} else {
				$result = $blocklist->cf7a_add_to_blocklist( $ip, $status );
			}

			if ( ! is_wp_error( $result ) && $result ) {
				++$count;
			}
		}//end while

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose( $handle );

			return $count;
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
		$where_clauses = array( "token NOT IN ('b8*texts', 'b8*dbversion')" );
		$params        = array();

		if ( 'spam' === $type ) {
			$where_clauses[] = 'count_spam > 0';
		} elseif ( 'ham' === $type ) {
			$where_clauses[] = 'count_ham > 0';
		}

		if ( ! empty( $search ) ) {
			$where_clauses[] = 'token LIKE %s';
			$params[]        = '%' . $wpdb->esc_like( $search ) . '%';
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
				// Calculate spam probability: spam_count / (spam_count + ham_count)
				// Handle division by zero by treating 0/0 as 0.5 (neutral)
				$order_clause = "CASE
					WHEN (COALESCE(count_spam, 0) + COALESCE(count_ham, 0)) = 0 THEN 0.5
					ELSE COALESCE(count_spam, 0) / (COALESCE(count_spam, 0) + COALESCE(count_ham, 0))
				END {$order}";
				break;
		}

		// Get total count
		$total_params = array_merge( array( $table ), $params );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i WHERE {$where}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				...$total_params
			)
		);

		// Get paginated results
		$words_params = array_merge( array( $table ), $params, array( $per_page, $offset ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$words = $wpdb->get_results(
			$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
				"SELECT token, count_spam, count_ham FROM %i WHERE {$where} ORDER BY {$order_clause} LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				...$words_params
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
	 * Retroactively update blocklist GeoIP data.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request Full data about the request.
	 * @return   WP_REST_Response
	 */
	public function cf7a_update_blocklist_geoip( $request ) {
		/** Verify nonce */
		if ( ! wp_verify_nonce( $request['nonce'], 'cf7a-nonce' ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce', 'cf7-antispam' ),
				)
			);
		}

		$blocklist     = new CF7_Antispam_Blocklist();
		$updated_count = $blocklist->cf7a_retroactive_geoip_update();

		return rest_ensure_response(
			array(
				'success'       => true,
				/* translators: %d is the number of updated IPs. */
				'message'       => sprintf( __( 'Successfully updated %d IPs with GeoIP data', 'cf7-antispam' ), $updated_count ),
				'updated_count' => $updated_count,
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
			'dashboard-stats',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'cf7a_get_dashboard_stats' ),
					'permission_callback' => array( $this, 'cf7a_get_permissions_check' ),
					'args'                => array(
						'period' => array(
							'required' => false,
							'type'     => 'string',
						),
					),
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
								return is_string( $param ) && ! empty( $param );
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
								return is_string( $param ) && ! empty( $param );
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
								return is_string( $param ) && ! empty( $param );
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
								return is_string( $param ) && ! empty( $param );
							},
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'import-blocklist',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'cf7a_import_blocklist' ),
					'permission_callback' => array( $this, 'cf7a_get_permissions_check' ),
					'args'                => array(
						'nonce' => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => function ( $param ) {
								return is_string( $param ) && ! empty( $param );
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

		register_rest_route(
			$this->namespace,
			'update-blocklist-geoip',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'cf7a_update_blocklist_geoip' ),
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
			'blocklist/add',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'cf7a_add_blocklist_entry' ),
					'permission_callback' => array( $this, 'cf7a_get_permissions_check' ),
					'args'                => array(
						'nonce' => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => function ( $param ) {
								return $this->cf7a_validate_param( $param, 'nonce' );
							},
						),
						'list'  => array(
							'required' => true,
							'type'     => 'string',
						),
						'ip'    => array(
							'required' => true,
							'type'     => 'string',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'blocklist/remove',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'cf7a_remove_blocklist_entry' ),
					'permission_callback' => array( $this, 'cf7a_get_permissions_check' ),
					'args'                => array(
						'nonce' => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => function ( $param ) {
								return $this->cf7a_validate_param( $param, 'nonce' );
							},
						),
						'list'  => array(
							'required' => true,
							'type'     => 'string',
						),
						'ip'    => array(
							'required' => true,
							'type'     => 'string',
						),
					),
				),
			)
		);
	}

	/**
	 * Add an IP to manual blocklist or allowlist.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function cf7a_add_blocklist_entry( $request ) {
		if ( ! wp_verify_nonce( $request['nonce'], 'cf7a-nonce' ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce', 'cf7-antispam' ),
				)
			);
		}

		$list = sanitize_key( $request['list'] );
		$ip   = sanitize_text_field( trim( $request['ip'] ) );

		if ( ! in_array( $list, array( 'bad_ip_list', 'ip_allowlist' ), true ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid list specified', 'cf7-antispam' ),
				)
			);
		}

		if ( ! $this->cf7a_is_valid_ip_or_cidr( $ip ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid IP address or CIDR range', 'cf7-antispam' ),
				)
			);
		}

		$options      = CF7_AntiSpam::get_options();
		$current_list = isset( $options[ $list ] ) && is_array( $options[ $list ] ) ? $options[ $list ] : array();

		if ( in_array( $ip, $current_list, true ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'IP address or CIDR range already exists in the list', 'cf7-antispam' ),
				)
			);
		}

		$current_list[]   = $ip;
		$options[ $list ] = array_values( array_unique( $current_list ) );

		if ( CF7_AntiSpam::update_plugin_options( $options ) ) {
			return rest_ensure_response(
				array(
					'success' => true,
					'message' => __( 'Entry added successfully', 'cf7-antispam' ),
				)
			);
		}

		return rest_ensure_response(
			array(
				'success' => false,
				'message' => __( 'Error updating options in the database', 'cf7-antispam' ),
			)
		);
	}

	/**
	 * Remove an IP from manual blocklist or allowlist.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function cf7a_remove_blocklist_entry( $request ) {
		if ( ! wp_verify_nonce( $request['nonce'], 'cf7a-nonce' ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce', 'cf7-antispam' ),
				)
			);
		}

		$list = sanitize_key( $request['list'] );
		$ip   = sanitize_text_field( trim( $request['ip'] ) );

		if ( ! in_array( $list, array( 'bad_ip_list', 'ip_allowlist' ), true ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid list specified', 'cf7-antispam' ),
				)
			);
		}

		$options      = CF7_AntiSpam::get_options();
		$current_list = isset( $options[ $list ] ) && is_array( $options[ $list ] ) ? $options[ $list ] : array();

		$key = array_search( $ip, $current_list, true );
		if ( false === $key ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'IP address or CIDR range not found in the list', 'cf7-antispam' ),
				)
			);
		}

		unset( $current_list[ $key ] );
		$options[ $list ] = array_values( $current_list );

		if ( CF7_AntiSpam::update_plugin_options( $options ) ) {
			return rest_ensure_response(
				array(
					'success' => true,
					'message' => __( 'Entry removed successfully', 'cf7-antispam' ),
				)
			);
		}

		return rest_ensure_response(
			array(
				'success' => false,
				'message' => __( 'Error updating options in the database', 'cf7-antispam' ),
			)
		);
	}
}
