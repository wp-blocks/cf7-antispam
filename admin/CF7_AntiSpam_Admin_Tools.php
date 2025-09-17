<?php

namespace CF7_AntiSpam\Admin;

use CF7_AntiSpam\Core\CF7_AntiSpam;
use CF7_AntiSpam\Core\CF7_AntiSpam_Filters;
use CF7_AntiSpam\Core\CF7_AntiSpam_Flamingo;
use CF7_AntiSpam\Engine\CF7_AntiSpam_Uninstaller;

/**
 * The plugin admin tools
 *
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/admin_tools
 * @author     Codekraft Studio <info@codekraft.it>
 */

/**
 *  It creates a class called CF7_AntiSpam_Admin_Tools.
 */
class CF7_AntiSpam_Admin_Tools {

	/**
	 * It sets a transient with the name of `cf7a_notice` and the value of the notice
	 *
	 * @param string  $message The message you want to display.
	 * @param string  $type error, warning, success, info.
	 * @param boolean $dismissible when the notice needs the close button.
	 */
	public static function cf7a_push_notice( $message = 'generic', $type = 'error', $dismissible = true ) {
		$class  = "notice notice-$type";
		$class .= $dismissible ? ' is-dismissible' : '';
		$notice = sprintf( '<div class="%s"><p>%s</p></div>', esc_attr( $class ), esc_html( $message ) );
		set_transient( 'cf7a_notice', $notice );
	}

	public static function cf7a_export_blacklist() {
		global $wpdb;
		$blacklisted = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}cf7a_blacklist ORDER BY `status` DESC" );
		foreach ( $blacklisted as $row ) {
			$meta      = unserialize( $row->meta );
			$row->meta = $meta;
		}
		return $blacklisted;
	}

	/**
	 * It handles the actions that are triggered by the user
	 */
	public function cf7a_handle_actions() {
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : false;
		$url    = esc_url( menu_page_url( 'cf7-antispam', false ) );

		if ( 'dismiss-banner' === $action ) {
			if ( get_user_meta( get_current_user_id(), 'cf7a_hide_welcome_panel_on', true ) ) {
				update_user_meta( get_current_user_id(), 'cf7a_hide_welcome_panel_on', true );
			} else {
				add_user_meta( get_current_user_id(), 'cf7a_hide_welcome_panel_on', true, true );
			}

			wp_safe_redirect( $url );
			exit();
		}

		$req_nonce = isset( $_REQUEST['cf7a-nonce'] ) ? wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['cf7a-nonce'] ) ), 'cf7a-nonce' ) : null;

		if ( $req_nonce ) {

			/* Ban a single ID (related to ip) */
			if ( 'unban_' === substr( $action, 0, 6 ) ) {
				$unban_id = intval( substr( $action, 6 ) );

				$filter = new CF7_AntiSpam_Filters();

				$r = $filter->cf7a_unban_by_id( $unban_id );

				if ( $r ) {
					/* translators: %s is the ip address. */
					self::cf7a_push_notice( sprintf( __( 'Success: ip %s unbanned', 'cf7-antispam' ), $unban_id ), 'success' );
				} else {
					/* translators: %s is the ip address. */
					self::cf7a_push_notice( sprintf( __( 'Error: unable to unban %s', 'cf7-antispam' ), $unban_id ) );
				}

				wp_safe_redirect( $url );
				exit();
			}

			/* Ban forever a single ID */
			if ( 'ban_forever_' === substr( $action, 0, 12 ) ) {
				$filter = new CF7_AntiSpam_Filters();

				$plugin_options = CF7_AntiSpam::get_options();

				$ban_id = intval( substr( $action, 12 ) );
				$ban_ip = $filter->cf7a_blacklist_get_id( $ban_id );

				if ( $ban_ip && ! empty( $plugin_options ) ) {
					if ( CF7_AntiSpam::update_plugin_option( 'bad_ip_list', array_merge( $plugin_options['bad_ip_list'], array( $ban_ip->ip ) ) ) ) {
						$filter->cf7a_unban_by_id( $ban_id );
					}

					self::cf7a_push_notice(
						sprintf(
						/* translators: the %1$s is the user id and %2$s is the ip address. */
							__( 'Ban forever id %1$s (ip %2$s) successful', 'cf7-antispam' ),
							$ban_id,
							! empty( $ban_ip->ip ) ? $ban_ip->ip : 'not available'
						)
					);
				} else {
					self::cf7a_push_notice(
						sprintf(
							/* translators: the %1$s is the user id and %2$s is the ip address. */
							__( 'Error: unable to ban forever id %1$s (ip %2$s)', 'cf7-antispam' ),
							$ban_id,
							! empty( $ban_ip->ip ) ? $ban_ip->ip : 'not available'
						)
					);
				}

				wp_safe_redirect( $url );
				exit();
			}

			if ( 'export-blacklist' === $action ) {

				$blacklist = $this->cf7a_export_blacklist();

				if ( ! empty( $blacklist ) ) {
					// Convert to CSV format with all fields
					$csv = '';

					// Add CSV header
					$csv .= "ID,IP,Status,Meta,Modified,Created\n";

					foreach ( $blacklist as $row ) {
						// Escape CSV values
						$id     = $row->id;
						$ip     = '"' . str_replace( '"', '""', $row->ip ) . '"';
						$status = $row->status ?? '';

						// Handle the metadata array - convert to JSON string for CSV
						$meta = '';
						if ( is_array( $row->meta ) && ! empty( $row->meta ) ) {
							$meta = '"' . str_replace( '"', '""', json_encode( $row->meta, JSON_UNESCAPED_UNICODE ) ) . '"';
						} elseif ( ! empty( $row->meta ) ) {
							$meta = '"' . str_replace( '"', '""', $row->meta ) . '"';
						}

						$modified = '"' . str_replace( '"', '""', $row->modified ?? '' ) . '"';
						$created  = '"' . str_replace( '"', '""', $row->created ?? '' ) . '"';

						// Build CSV row
						$csv .= $id . ',' . $ip . ',' . $status . ',' . $meta . ',' . $modified . ',' . $created . "\n";
					}
				} else {
					// Handle empty blacklist case
					$csv  = "ID,IP,Status,Meta,Modified,Created\n";
					$csv .= "No blacklisted IPs found\n";
				}

				// Set headers for file download
				$filename = 'cf7-antispam-blacklist-' . gmdate( 'Y-m-d-H-i-s' ) . '.csv';

				// Set download headers
				header( 'Content-Type: text/csv; charset=utf-8' );
				header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
				header( 'Content-Length: ' . strlen( $csv ) );
				header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
				header( 'Pragma: no-cache' );
				header( 'Expires: 0' );

				// Output the CSV content
				echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput
				exit();
			}

			/* Purge the blacklist */
			if ( 'reset-blacklist' === $action ) {

				/* uninstall class contains the database utility functions */
				$r = CF7_AntiSpam_Uninstaller::cf7a_clean_blacklist();

				if ( $r ) {
					self::cf7a_push_notice( __( 'Success: ip blacklist cleaned', 'cf7-antispam' ), 'success' );
				} else {
					self::cf7a_push_notice( __( 'Error: unable to clean blacklist. Please refresh and try again!', 'cf7-antispam' ) );
				}
				wp_safe_redirect( $url );
				exit();
			}

			/* Reset Dictionary */
			if ( 'reset-dictionary' === $action ) {

				/* uninstall class contains the database utility functions */
				$r = CF7_AntiSpam_Flamingo::cf7a_reset_dictionary();

				if ( $r ) {
					self::cf7a_push_notice( __( 'b8 dictionary reset successful', 'cf7-antispam' ), 'success' );
				} else {
					self::cf7a_push_notice( __( 'Something goes wrong while deleting b8 dictionary. Please refresh and try again!', 'cf7-antispam' ) );
				}

				wp_safe_redirect( $url );
				exit();
			}

			/* Reset plugin data */
			if ( 'cf7a-full-reset' === $action ) {

				/* uninstall class contains the database utility functions */
				$r = CF7_AntiSpam_Uninstaller::cf7a_full_reset();

				if ( $r ) {
					self::cf7a_push_notice( __( 'CF7 AntiSpam fully reinitialized with success. You need to rebuild B8 manually if needed', 'cf7-antispam' ), 'success' );
				} else {
					self::cf7a_push_notice( __( 'Ops! something went wrong... Please refresh and try again!', 'cf7-antispam' ) );
				}

				wp_safe_redirect( $url );
				exit();
			}

			/* Rebuild Dictionary */
			if ( 'rebuild-dictionary' === $action ) {
				$r = CF7_AntiSpam_Flamingo::cf7a_rebuild_dictionary();

				if ( $r ) {
					self::cf7a_push_notice( __( 'b8 dictionary rebuild successful', 'cf7-antispam' ), 'success' );
				} else {
					self::cf7a_push_notice( __( 'Something goes wrong while rebuilding b8 dictionary. Please refresh and try again!', 'cf7-antispam' ) );
				}

				wp_safe_redirect( $url );
				exit();
			}
		}
	}
}
