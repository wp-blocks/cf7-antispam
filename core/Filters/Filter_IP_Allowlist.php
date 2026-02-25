<?php
/**
 * Filter for IP Allowlist.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core/Filters
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core\Filters;

use CF7_AntiSpam\Core\Abstract_CF7_AntiSpam_Filter;

/**
 * Class Filter_IP_Allowlist
 */
class Filter_IP_Allowlist extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Checks for IP allowlist.
	 * If the IP is allowlisted, the spam check is skipped.
	 *
	 * @param array $data The data array.
	 *
	 * @return array The data array.
	 */
	public function process( array $data ): array {
		$ip_allowlist = $data['options']['ip_allowlist'] ?? array();

		if ( ! empty( $ip_allowlist ) && $data['remote_ip'] ) {
			foreach ( $ip_allowlist as $good_ip ) {
				$good_ip = filter_var( $good_ip, FILTER_VALIDATE_IP );
				// Use strict equality to avoid partial matches (e.g., 1.2.3.4 matching 1.2.3.40)
				if ( $good_ip && $data['remote_ip'] === $good_ip ) {
					$data['is_allowlisted'] = true;
					return $data;
				}
			}
		}
		return $data;
	}
}
