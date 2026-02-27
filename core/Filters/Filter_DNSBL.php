<?php
/**
 * Filter for DNS Blocklist.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core/Filters
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core\Filters;

use CF7_AntiSpam\Core\Abstract_CF7_AntiSpam_Filter;
use CF7_AntiSpam\Core\CF7_AntiSpam_Rules;

/**
 * Class Filter_DNSBL
 */
class Filter_DNSBL extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Checks DNS Blocklist.
	 * If the IP is in the list, the spam check is skipped.
	 *
	 * @param array $data The data array.
	 *
	 * @return array The data array.
	 */
	public function process( array $data ): array {
		$options = $data['options'];
		if ( intval( $options['check_dnsbl'] ) !== 1 || ! $data['remote_ip'] ) {
			return $data;
		}

		$reverse_ip = '';

		if ( filter_var( $data['remote_ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$reverse_ip = CF7_AntiSpam_Rules::cf7a_reverse_ipv4( $data['remote_ip'] );
		} elseif ( filter_var( $data['remote_ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$reverse_ip = CF7_AntiSpam_Rules::cf7a_reverse_ipv6( $data['remote_ip'] );
		}

		if ( isset( $options['dnsbl_list'] ) && is_array( $options['dnsbl_list'] ) ) {
			foreach ( $options['dnsbl_list'] as $dnsbl ) {
				if ( CF7_AntiSpam_Rules::cf7a_check_dnsbl( $reverse_ip, $dnsbl ) ) {
					$data['reasons']['dnsbl'][] = $dnsbl;
				}
			}
		}

		if ( ! empty( $data['reasons']['dnsbl'] ) ) {
			$logged_reasons = implode( ', ', $data['reasons']['dnsbl'] );
			cf7a_log( "{$data['remote_ip']} is listed in DNSBL ({$logged_reasons})", 1 );
		}
		return $data;
	}
}
