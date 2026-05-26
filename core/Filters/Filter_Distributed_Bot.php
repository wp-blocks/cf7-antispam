<?php
/**
 * Filter for distributed bot detection.
 *
 * @since      0.7.7
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core/Filters
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core\Filters;

use CF7_AntiSpam\Core\Abstract_CF7_AntiSpam_Filter;
use CF7_AntiSpam\Core\CF7_AntiSpam;
use CF7_AntiSpam\Core\CF7_Antispam_Blocklist;

/**
 * Detects distributed botnets by comparing the timestamp scout IP to the submitter IP.
 */
class Filter_Distributed_Bot extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Process distributed bot detection logic.
	 *
	 * @param array $data The data array.
	 *
	 * @return array
	 */
	public function process( array $data ): array {
		$token = $this->get_posted_value( '_cf7a_ip_token' );
		if ( empty( $token ) ) {
			return $data;
		}

		$token = preg_replace( '/[^a-zA-Z0-9]/', '', $token );
		if ( empty( $token ) ) {
			return $data;
		}

		$scout_ip  = get_transient( 'cf7a_ip_token_' . $token );
		$worker_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP ) : false; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( empty( $scout_ip ) || empty( $worker_ip ) || $scout_ip === $worker_ip ) {
			return $data;
		}

		$data['is_spam']                      = true;
		$data['reasons']['distributed_bot'][] = "Scout IP: {$scout_ip} / Worker IP: {$worker_ip}";

		$this->add_permanent_ban( $worker_ip, 'Distributed Bot: Worker' );
		$this->add_permanent_ban( $scout_ip, 'Distributed Bot: Scout' );

		cf7a_log( "Distributed bot detected. Scout IP: {$scout_ip} / Worker IP: {$worker_ip}", 1 );

		return $data;
	}

	/**
	 * Permanently add an IP to the plugin bad IP list.
	 *
	 * @param string $ip     The IP address to ban.
	 * @param string $reason The ban reason.
	 *
	 * @return void
	 */
	private function add_permanent_ban( string $ip, string $reason ): void {
		$ip = filter_var( $ip, FILTER_VALIDATE_IP );

		if ( ! $ip ) {
			return;
		}

		$blocklist = new CF7_Antispam_Blocklist();
		$blocklist->cf7a_add_to_blocklist( $ip, 'banned', $reason );
		CF7_AntiSpam::update_plugin_option( 'bad_ip_list', array( $ip ) );
	}
}
