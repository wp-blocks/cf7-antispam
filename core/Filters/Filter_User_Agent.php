<?php
/**
 * Filter for User Agent.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core/Filters
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core\Filters;

use CF7_AntiSpam\Core\Abstract_CF7_AntiSpam_Filter;

/**
 * Class Filter_User_Agent
 */
class Filter_User_Agent extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Checks User Agent.
	 * If the user agent does not match, the spam check is skipped.
	 *
	 * @param array $data The data array.
	 *
	 * @return array The data array.
	 */
	public function process( array $data ): array {
		$options = $data['options'];
		if ( intval( $options['check_bad_user_agent'] ) !== 1 ) {
			return $data;
		}

		$score_detection     = floatval( $options['score']['_detection'] );
		$score_bad_string    = floatval( $options['score']['_bad_string'] );
		$bad_user_agent_list = isset( $options['bad_user_agent_list'] ) ? $options['bad_user_agent_list'] : array();

		if ( ! $data['user_agent'] ) {
			$data['spam_score']           += $score_detection;
			$data['reasons']['user_agent'] = 'empty';
			cf7a_log( "The {$data['remote_ip']} ip user agent is empty", 1 );
		} else {
			foreach ( $bad_user_agent_list as $bad_user_agent ) {
				$bad_user_agent = trim( $bad_user_agent );
				if ( ! empty( $bad_user_agent ) && false !== stripos( strtolower( $data['user_agent'] ), strtolower( $bad_user_agent ) ) ) {
					$data['spam_score']             += $score_bad_string;
					$data['reasons']['user_agent'][] = $bad_user_agent;
				}
			}

			if ( isset( $data['reasons']['user_agent'] ) && is_array( $data['reasons']['user_agent'] ) ) {
				$data['reasons']['user_agent'] = implode( ', ', $data['reasons']['user_agent'] );
				cf7a_log( "The {$data['remote_ip']} ip user agent was listed into bad user agent list", 1 );
			}
		}
		return $data;
	}
}
