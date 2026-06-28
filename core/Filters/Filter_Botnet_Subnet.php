<?php
/**
 * Filter for Botnet Subnet Protection.
 *
 * @since      0.8.0
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core/Filters
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core\Filters;

use CF7_AntiSpam\Core\Abstract_CF7_AntiSpam_Filter;

/**
 * Class Filter_Botnet_Subnet
 */
class Filter_Botnet_Subnet extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Checks if the IP belongs to a subnet with multiple recently banned IPs.
	 *
	 * @param array $data The data array.
	 *
	 * @return array The data array.
	 */
	public function process( array $data ): array {
		$options = $data['options'];

		if ( empty( $options['botnet_subnet_protection'] ) || empty( $data['remote_ip'] ) ) {
			return $data;
		}

		if ( ! function_exists( 'cf7a_get_c_class_subnet' ) || ! function_exists( 'cf7a_count_banned_ips_in_subnet' ) ) {
			return $data;
		}

		$subnet = cf7a_get_c_class_subnet( $data['remote_ip'] );
		if ( ! $subnet ) {
			return $data;
		}

		$count = cf7a_count_banned_ips_in_subnet( $subnet );

		// Flag as spam if 5 or more IPs from this subnet have been blocked.
		if ( $count >= 5 ) {
			$data['is_spam']                    = true;
			$data['reasons']['botnet_subnet'][] = $subnet . 'x';

			cf7a_log( "The IP {$data['remote_ip']} was blocked due to Botnet Subnet Protection. Banned IPs in subnet {$subnet}x: {$count}", 1 );
		}

		return $data;
	}
}
