<?php

namespace CF7_AntiSpam\Admin;

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
	public static function cf7a_push_notice( string $message = 'generic', string $type = 'error', bool $dismissible = true ) {
		$class  = "notice notice-$type";
		$class .= $dismissible ? ' is-dismissible' : '';
		$notice = sprintf( '<div class="%s"><p>%s</p></div>', esc_attr( $class ), esc_html( $message ) );
		set_transient( 'cf7a_notice', $notice );
	}

	/**
	 * It exports the blocklist
	 */
	public static function cf7a_export_blacklist() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$blacklisted = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY `status` DESC', $wpdb->prefix . 'cf7a_blacklist' ) );
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
		if ( ! $action ) {
			return;
		}

		$req_nonce = isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), $action );
		if ( ! $req_nonce ) {
			return;
		}

		$url = esc_url( menu_page_url( 'cf7-antispam', false ) );
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

	/**
	 * It sends an email to the admin
	 *
	 * @param string $subject the mail message subject
	 * @param string $recipient the mail recipient
	 * @param string $body the mail message content
	 * @param string $sender the mail message sender
	 */
	public function send_email_to_admin( string $subject, string $recipient, string $body, string $sender ) {
		/**
		 * Filter cf7-antispam before resend an email who was spammed
		 *
		 * @param string $body the mail message content
		 * @param string $sender the mail message sender
		 * @param string $subject the mail message subject
		 * @param string $recipient the mail recipient
		 *
		 * @returns string the mail body content
		 */
		$body = apply_filters( 'cf7a_before_resend_email', $body, $sender, $subject, $recipient );

		// Set up headers correctly
		$site_name  = get_bloginfo( 'name' );
		$from_email = get_option( 'admin_email' );

		$headers  = "From: {$site_name} <{$from_email}>\n";
		$headers .= "Content-Type: text/html\n";
		$headers .= "X-WPCF7-Content-Type: text/html\n";
		$headers .= "Reply-To: {$sender}\n";

		/* send the email */
		return wp_mail( $recipient, $subject, $body, $headers );
	}
}
