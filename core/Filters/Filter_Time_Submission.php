<?php
/**
 * Filter for Time Submission.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core/Filters
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core\Filters;

use CF7_AntiSpam\Core\Abstract_CF7_AntiSpam_Filter;

/**
 * Class Filter_Time_Submission
 */
class Filter_Time_Submission extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Checks Time of submission.
	 * If the time does not match, the spam check is skipped.
	 *
	 * @param array $data The data array.
	 *
	 * @return array The data array.
	 */
	public function process( array $data ): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$options = $data['options'];
		if ( intval( $options['check_time'] ) !== 1 ) {
			return $data;
		}

		$prefix = $this->get_prefix( $options );

		$score_time      = floatval( $options['score']['_time'] );
		$score_detection = floatval( $options['score']['_detection'] );

		// The right way to do this is BEFORE decrypting and THEN sanitize, because sanitized data are stripped of any special characters
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$timestamp        = isset( $_POST[ $prefix . '_timestamp' ] ) ? intval( cf7a_decrypt( $_POST[ $prefix . '_timestamp' ], $options['cf7a_cipher'] ) ) : 0;
		$time_now         = time();
		$time_elapsed_min = intval( $options['check_time_min'] );
		$time_elapsed_max = intval( $options['check_time_max'] );

		if ( ! $timestamp ) {
			$data['spam_score']          += $score_detection;
			$data['reasons']['timestamp'] = 'missing field';
			cf7a_log( "The {$data['remote_ip']} ip _timestamp field is missing", 1 );
		} else {
			$time_elapsed = $time_now - $timestamp;

			if ( 0 !== $time_elapsed_min && $time_elapsed < $time_elapsed_min ) {
				$data['spam_score']                 += $score_time;
				$data['reasons']['min_time_elapsed'] = $time_elapsed;
				cf7a_log( "The {$data['remote_ip']} ip took too little time ($time_elapsed s)", 1 );
			}

			if ( 0 !== $time_elapsed_max && $time_elapsed > $time_elapsed_max ) {
				$data['spam_score']                 += $score_time;
				$data['reasons']['max_time_elapsed'] = $time_elapsed;
				cf7a_log( "The {$data['remote_ip']} ip took too much time ($time_elapsed s)", 1 );
			}
		}
		return $data;
	}
}
