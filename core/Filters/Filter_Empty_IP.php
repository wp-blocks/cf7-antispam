<?php
/**
 * Filter for Empty IP.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core/Filters
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core\Filters;

use CF7_AntiSpam\Core\Abstract_CF7_AntiSpam_Filter;

/**
 * Class Filter_Empty_IP
 */
class Filter_Empty_IP extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Checks if IP is empty.
	 * If the IP is empty, the spam check is skipped.
	 *
	 * @param array $data The data array.
	 *
	 * @return array The data array.
	 */
	public function process( array $data ): array {
		if ( ! $data['remote_ip'] ) {
			// Fallback to CF7 IP if main is missing, but flag as spam
			$data['remote_ip'] = $data['cf7_remote_ip'] ? $data['cf7_remote_ip'] : null;

			$data['is_spam']            = true;
			$data['reasons']['no_ip'][] = 'Address field empty';

			cf7a_log( "ip address field of {$data['remote_ip']} is empty, this means it has been modified, removed or hacked!", 1 );
		}
		return $data;
	}
}
