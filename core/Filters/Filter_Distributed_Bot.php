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

		/* We want the workers to work in order to get the bigger fish */
		CF7_Antispam_Blocklist::cf7a_ban_by_ip( $worker_ip, array( 'distributed_bot_trap' => 'Distributed Bot: Worker' ), 1 );

		/* We want the scout to be banned permanently and added to the persistent IP list */
		CF7_Antispam_Blocklist::cf7a_ban_forever_and_add_to_list( $scout_ip, 'Distributed Bot: Scout' );

		cf7a_log( "Distributed bot detected. Scout IP: {$scout_ip} / Worker IP: {$worker_ip}", 1 );

		return $data;
	}
}
