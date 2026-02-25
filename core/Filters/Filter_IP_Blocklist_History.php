<?php
/**
 * Filter for IP Blocklist History.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core/Filters
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core\Filters;

use CF7_AntiSpam\Core\Abstract_CF7_AntiSpam_Filter;
use CF7_AntiSpam\Core\CF7_Antispam_Blocklist;

/**
 * Class Filter_IP_Blocklist_History
 */
class Filter_IP_Blocklist_History extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Checks if IP is already in the database blocklist history.
	 * If the IP is in the list, the spam check is skipped.
	 *
	 * @param array $data The data array.
	 *
	 * @return array The data array.
	 */
	public function process( array $data ): array {
		$options = $data['options'];
		if ( $data['remote_ip'] && $options['max_attempts'] ) {
			$ip_data        = CF7_Antispam_Blocklist::cf7a_blocklist_get_ip( $data['remote_ip'] );
			$ip_data_status = isset( $ip_data->status ) ? intval( $ip_data->status ) : 0;
			$max_attempts   = intval( $options['max_attempts'] );

			if ( $ip_data_status >= $max_attempts ) {
				++$data['spam_score'];
				$data['is_spam']                = true;
				$data['reasons']['blocklisted'] = $ip_data_status;

				cf7a_log( "The {$data['remote_ip']} has reached max attempts threshold (status: $ip_data_status, max: $max_attempts)", 1 );
			} elseif ( defined( 'CF7ANTISPAM_DEBUG' ) && CF7ANTISPAM_DEBUG && $ip_data_status > 0 ) {
				cf7a_log( sprintf( "The {$data['remote_ip']} has prior history (score $ip_data_status) but still has %d attempts left before reaching max (%d)", $max_attempts - $ip_data_status, $max_attempts ), 1 );
			}
		}
		return $data;
	}
}
