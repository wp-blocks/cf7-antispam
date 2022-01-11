<?php

class CF7_AntiSpam_Admin_Tools {

	public static function cf7a_push_notice($message = "generic", $type = "error", $dismissible = true) {
		$class = "notice notice-$type";
		$class .= $dismissible ? ' is-dismissible' : '';
		$notice = sprintf( '<div class="%s"><p>%s</p></div>', esc_attr( $class ), esc_html( $message ) );
		set_transient( 'cf7a_notice', $notice );
	}

	public static function cf7a_format_status($rank) {
		$color = 200 - ($rank * 2);
		$color =  $color < 0 ? 0 : $color;
		return "<span class='ico' style='background-color: rgba(250,$color,0)'>$rank</span>";
	}

	public static function cf7a_handle_actions() {

		$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : false;
		$url    = esc_url( menu_page_url( 'cf7-antispam', false ) );

		if ( $action === 'dismiss-banner' ) {
			update_user_meta( get_current_user_id(), 'cf7a_hide_welcome_panel_on', 1 );
			wp_redirect( $url );
			exit();
		}

		$req_nonce = isset($_REQUEST['cf7a-nonce']) ? wp_verify_nonce( $_REQUEST['cf7a-nonce'], 'cf7a-nonce' ) : null;

		if ( $req_nonce ) {

			$filter = new CF7_AntiSpam_filters();

			// Ban a single ID (related to ip)
			if ( substr( $action, 0, 6 ) === "unban_" ) {

				$unban_id = intval(substr( $action, 6 ));

				$r = $filter->cf7a_unban_by_id( $unban_id );

				if (!is_wp_error($r)) {
					CF7_AntiSpam_Admin_Tools::cf7a_push_notice( __( "Success: ip $unban_id unbanned", 'cf7-antispam' ), "success" );
					wp_redirect( $url );
				} else {
					CF7_AntiSpam_Admin_Tools::cf7a_push_notice( __( "Error: unable to unban $unban_id", 'cf7-antispam' ) );
					wp_redirect( $url );
				}
			}

			// Purge the blacklist
			if ( $action === 'reset-blacklist' ) {

				$r = $filter->cf7a_clean_blacklist();

				if (!is_wp_error($r)) {
					CF7_AntiSpam_Admin_Tools::cf7a_push_notice( __( 'Success: ip blacklist cleaned', 'cf7-antispam' ), "success" );
					wp_redirect( $url );
				} else {
					CF7_AntiSpam_Admin_Tools::cf7a_push_notice( __( 'Error: unable to clean blacklist. Please refresh and try again!', 'cf7-antispam' ) );
					wp_redirect( $url );
				}
			}


			// Reset Dictionary
			if ( $action === 'reset-dictionary' ) {

				$r = $filter->cf7a_reset_dictionary();

				if (!is_wp_error($r)) {
					CF7_AntiSpam_Admin_Tools::cf7a_push_notice( __( 'b8 dictionary reset successful', 'cf7-antispam' ), "success" );
					wp_redirect( $url );
				} else {
					CF7_AntiSpam_Admin_Tools::cf7a_push_notice( __( 'Something goes wrong while deleting b8 dictionary. Please refresh and try again!', 'cf7-antispam' ) );
					wp_redirect( $url );
				}
			}

			// Rebuild Dictionary
			if ( $action === 'rebuild-dictionary' ) {

				$r = $filter->cf7a_rebuild_dictionary();

				if (!is_wp_error($r)) {
					CF7_AntiSpam_Admin_Tools::cf7a_push_notice( __( 'b8 dictionary rebuild successful', 'cf7-antispam' ), "success" );
					wp_redirect( $url );
				} else {
					CF7_AntiSpam_Admin_Tools::cf7a_push_notice( __( 'Something goes wrong while rebuilding b8 dictionary. Please refresh and try again!', 'cf7-antispam' ) );
					wp_redirect( $url );
				}
			}


			if ( $action === 'cf7a-full-reset' ) {

				$r = $filter->cf7a_full_reset();

				if (!is_wp_error($r)) {
					CF7_AntiSpam_Admin_Tools::cf7a_push_notice( __( 'CF7 AntiSpam fully reinitialized with success', 'cf7-antispam' ), "success" );
					wp_redirect( $url );
				} else {
					CF7_AntiSpam_Admin_Tools::cf7a_push_notice( __( 'Ops! something went wrong... Please refresh and try again!', 'cf7-antispam' ) );
					wp_redirect( $url );
				}

			}

			if ( substr( $action, 0, 12 ) === "cf7a_resend_" ) {

				$mail_id = (int) substr( $action, 12 );

				$refer = $_SERVER['HTTP_REFERER'];

				if ($mail_id > 1) {

					$r = $filter->cf7a_resend_mail( $mail_id );

					if ( ! is_wp_error( $r ) ) {
						CF7_AntiSpam_Admin_Tools::cf7a_push_notice( __( 'CF7 AntiSpam email '.$mail_id.' sent with success', 'cf7-antispam' ), "success" );
						wp_redirect( $refer );
					}
				}

				CF7_AntiSpam_Admin_Tools::cf7a_push_notice( __( 'Ops! something went wrong... unable to resend '.$mail_id.' email', 'cf7-antispam' ) );
				wp_redirect( $refer );

			}

		}

	}

	public static function cf7a_get_blacklisted_table() {

		global $wpdb;
		$blacklisted = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}cf7a_blacklist ORDER BY `status` DESC LIMIT 1000" );

		if ( $blacklisted ) {

			$html = '<div id="blacklist-section"  class="cf7-antispam card"><h3>' . __( 'IP Blacklist' ) . '</h3><div class="widefat blacklist-table">';
			foreach ( $blacklisted as $row ) {

				// the row url
				$url = wp_nonce_url( add_query_arg( "action", "unban_" . $row->id, menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce' );

				$meta = unserialize($row->meta);

				// the row
				$html .= '<div class="row">';
				$html .= sprintf( "<div class='status'>%s</div>", self::cf7a_format_status( $row->status ) );
				$html .= sprintf( '<div><p class="ip">%s<small class="actions"> <a href="%s">[unban ip]</a></small></p>', $row->ip, esc_url( $url ) );
				$html .= sprintf( "<span class='data ellipsis'>%s</span></div>", cf7a_compress_array($meta['reason'], 1)  );
				$html .= "</div>";

			}
			$html .= '</div></div>';

			echo $html;
		}
	}

	public static function cf7a_advanced_settings() {

		// the header
		$html = printf('<div id="advanced-setting-card" class="cf7-antispam card"><h3><span class="dashicons dashicons-shortcode"></span> %s</h3><p>%s</p>',
			__('Advanced settings', 'cf7-antispam'),
			__('Use them if you know what you are doing!', 'cf7-antispam')
		);

		// output the button to remove all the entries in the blacklist database
		$html .= printf('<hr/><h3>%s</h3><p>%s</p>',
			__('Blacklist Reset', 'cf7-antispam'),
			__('If you need to remove or reset the whole blacklist data on your server.', 'cf7-antispam')
		);
		$url = wp_nonce_url( add_query_arg("action", "reset-blacklist", menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce'  );
		$html .= printf('<pre><button class="button cf7a_alert" data-href="%s">%s</button></pre>', esc_url( $url ), __('Remove all blacklisted IP', 'cf7-antispam') );


		// output the button to remove all the words into dictionary
		$html .= printf('<hr/><h3>%s</h3><p>%s</p>',
			__('Dictionary Reset', 'cf7-antispam'),
			__('Use only if you need to reset the whole b8 dictionary.', 'cf7-antispam')
		);
		$url = wp_nonce_url( add_query_arg("action", "reset-dictionary", menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce'  );
		$html .= printf('<pre><button class="button cf7a_alert" data-href="%s">%s</button></pre>', esc_url( $url ), __('Reset b8 dictionary', 'cf7-antispam') );

		// output the button to rebuild b8 dictionary
		$html .= printf('<hr/><h3>%s</h3><p>%s</p>',
			__('Rebuid Dictionary', 'cf7-antispam'),
			__('Reanalyze all the Flamingo inbound emails (you may need to reset dictionary before).', 'cf7-antispam')
		);
		$url = wp_nonce_url( add_query_arg("action", "rebuild-dictionary", menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce'  );
		$html .= printf('<pre><button class="button cf7a_alert" data-href="%s">%s</button></pre>', esc_url( $url ), __('Rebuild b8 dictionary', 'cf7-antispam') );


		// output the button to full reset cf7a
		$html .= printf('<hr/><h3>%s</h3><p>%s</p>',
			__('Full Reset', 'cf7-antispam'),
			__('Fully reinitialize cf7-antispam plugin database and options', 'cf7-antispam')
		);
		$url = wp_nonce_url( add_query_arg("action", "cf7a-full-reset", menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce'  );
		$html .= printf('<pre><button class="button cf7a_alert" data-href="%s">%s</button></pre>', esc_url( $url ), __('FULL RESET', 'cf7-antispam') );


		$html .= printf('</div>');

		return $html;

	}

	public static function cf7a_get_debug_info() {

		if (WP_DEBUG || CF7ANTISPAM_DEBUG) {

			$options = CF7_AntiSpam::get_options();

			// the header
			$html = printf('<div id="debug-info" class="cf7-antispam card"><h3><span class="dashicons dashicons-shortcode"></span> %s</h3><p>%s</p>',
				__('Debug info', 'cf7-antispam'),
				__('(...If you can see this panel WP_DEBUG or CF7ANTISPAM_DEBUG are true)', 'cf7-antispam')
			);

			if (CF7ANTISPAM_DEBUG) $html .= printf('<p class="debug">%s</p>',
				'<code>CF7ANTISPAM_DEBUG</code> ' . esc_html(__('is enabled', 'cf7-antispam'))
			);
			if (CF7ANTISPAM_DEBUG_EXTENDED) $html .= printf('<p class="debug">%s</p>',
				'<code>CF7ANTISPAM_DEBUG_EXTENDED</code> ' . esc_html(__('is enabled', 'cf7-antispam'))
			);

			// output the options
			$html .= printf('<hr/><h3>%s</h3>', __('Options debug', 'cf7-antispam') );
			$html .= printf('<p>%s</p><pre>%s</pre>',
				__('Those are the options of this plugin', 'cf7-antispam'),
				htmlentities(print_r($options, true))
			);

			$html .= printf('</div>');

			return $html;
		}
	}
}
