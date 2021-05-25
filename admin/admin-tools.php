<?php

class CF7_AntiSpam_Admin_Tools {

	public static function cf7a_push_notice($message = "generic", $type = "error", $dismissible = true) {
		$class = "notice notice-$type";
		$class .= $dismissible ? ' is-dismissible' : '';
		return sprintf( '<div class="%s"><p>%s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	public static function cf7a_format_status($rank) {
		$color = 200 - ($rank * 2);
		$color =  $color < 0 ? 0 : $color;
		return "<span class='ico' style='background-color: rgba(250,$color,0)'>$rank</span>";
	}

	public static function cf7a_handle_blacklist() {

		$req_nonce = isset($_REQUEST['cf7a-nonce']) ? wp_verify_nonce( $_REQUEST['cf7a-nonce'], 'cf7a-nonce' ) : null;
		$action = isset($_REQUEST['action']) ? sanitize_text_field($_REQUEST['action']) : false;

		$url = esc_url( menu_page_url( 'cf7-antispam', false ) );

		if ( $req_nonce ) {

			$filter = new CF7_AntiSpam_filters();

			// Ban a single ID (related to ip)
			if ( substr( $action, 0, 6 ) === "unban_" ) {

				$unban_id = intval(substr( $action, 6 ));

				$r = $filter->cf7a_unban_by_id( $unban_id );

				if (!is_wp_error($r)) {
					wp_redirect( add_query_arg('action', 'success', $url ));
				} else {
					wp_redirect( add_query_arg('action', 'fail', $url ));
				}

				exit();

			}

			// Purge the blacklist
			if ( $action === 'clean-blacklist' ) {

				$r = $filter->cf7a_clean_blacklist();

				if (!is_wp_error($r)) {
					wp_redirect( add_query_arg('action', 'success', $url ));
				} else {
					wp_redirect( add_query_arg('action', 'fail', $url ));
				}

				exit();
			}

		}

	}

	public static function cf7a_get_blacklisted_table() {

		global $wpdb;
		$blacklisted = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}cf7a_blacklist ORDER BY `status` DESC LIMIT 1000" );

		if ( $blacklisted ) {

			$html = '<div class="cf7-antispam card"><h3>' . __( 'IP Blacklist' ) . '</h3><div class="widefat blacklist-table">';
			foreach ( $blacklisted as $row ) {

				// the row url
				$url = wp_nonce_url( add_query_arg( "action", "unban_" . $row->id, menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce' );

				$meta = unserialize($row->meta);

				// the row
				$html .= '<div class="row">';
				$html .= sprintf( "<div class='status'>%s</div>", self::cf7a_format_status( $row->status ) );
				$html .= sprintf( '<div><p class="ip">%s<small class="actions"> <a href="%s">[unban ip]</a></small></p>', $row->ip, esc_url( $url ) );
				$html .= sprintf( "<span class='data ellipsis'>%s</span></div>", cf7a_compress_array($meta['reason'], 1)  );
				//$html .= sprintf( print_r($meta, true)  );
				$html .= "</div>";
			}
			$html .= '</div></div>';

			echo $html;
		}
	}

	public static function cf7a_get_debug_info() {

		if (WP_DEBUG || CF7ANTISPAM_DEBUG) {

			$options = CF7_AntiSpam::get_options();

			$url = wp_nonce_url( add_query_arg("action", "clean-blacklist", menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce'  );

			$html = printf('<div>');

			// the header
			$html .= printf('<div class="cf7-antispam card"><h3><span class="dashicons dashicons-shortcode"></span> %s</h3><p>%s</p>',
				__('Debug info', 'cf7-antispam'),
				__('(...If you can see this panel WP_DEBUG or CF7ANTISPAM_DEBUG are true)', 'cf7-antispam')
			);

			if (CF7ANTISPAM_DEBUG) $html .= printf('<p class="debug">%s</p>',
				'<code>CF7ANTISPAM_DEBUG</code> ' . esc_html(__('is enabled', 'cf7-antispam'))
			);
			if (CF7ANTISPAM_DEBUG_EXTENDED) $html .= printf('<p class="debug">%s</p>',
				'<code>CF7ANTISPAM_DEBUG_EXTENDED</code> ' . esc_html(__('is enabled', 'cf7-antispam'))
			);

			// output the button to remove all the entries in the blacklist database
			$html .= printf('<hr/><h3>%s</h3><p>%s</p>',
				__('Blacklist Reset', 'cf7-antispam'),
				__('If you need to remove or reset the whole blacklist data on your server', 'cf7-antispam')
			);

			// output the button to remove all the entries in the blacklist database
			$html .= printf('<pre><a class="button" href="%s">%s</a></pre>', esc_url( $url ), __('Remove all blacklisted IP from database', 'cf7-antispam') );

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