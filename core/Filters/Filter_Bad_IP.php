<?php
/**
 * Filter for Bad IP.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core/Filters
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core\Filters;

use CF7_AntiSpam\Core\Abstract_CF7_AntiSpam_Filter;

/**
 * Class Filter_Bad_IP
 */
class Filter_Bad_IP extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Checks against local bad IP list.
	 * If the IP is in the list, the spam check is skipped.
	 *
	 * @param array $data The data array.
	 *
	 * @return array The data array.
	 */
	public function process( array $data ): array {
		$options     = $data['options'];
		$bad_ip_list = isset( $options['bad_ip_list'] ) ? $options['bad_ip_list'] : array();

		if ( intval( $options['check_bad_ip'] ) === 1 && $data['remote_ip'] ) {
			foreach ( $bad_ip_list as $bad_ip ) {
				$bad_ip = filter_var( $bad_ip, FILTER_VALIDATE_IP );
				// Use strict equality to avoid partial matches (e.g., 1.2.3.4 matching 1.2.3.40)
				if ( $bad_ip && $data['remote_ip'] === $bad_ip ) {
					++$data['spam_score'];
					$data['is_spam']             = true;
					$data['reasons']['bad_ip'][] = $bad_ip;
				}
			}

			if ( ! empty( $data['reasons']['bad_ip'] ) && is_array( $data['reasons']['bad_ip'] ) ) {
				$ip_string                 = implode( ', ', $data['reasons']['bad_ip'] );
				$data['reasons']['bad_ip'] = $ip_string;
				// Flatten for log
				cf7a_log( "The ip address {$data['remote_ip']} is listed into bad ip list (contains $ip_string)", 1 );
			}
		}
		return $data;
	}
}
