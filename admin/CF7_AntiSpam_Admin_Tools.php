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
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$blacklisted = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i ORDER BY `status` DESC", $wpdb->prefix . 'cf7a_blacklist' ) );
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
		$req_nonce = isset( $_REQUEST['cf7a-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['cf7a-nonce'] ) ), 'cf7a-nonce' );
		if ( !$req_nonce ) {
			return;
		}
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
	}
}
