<?php
/**
 * Filter for B8 Bayesian.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core/Filters
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core\Filters;

use CF7_AntiSpam\Core\Abstract_CF7_AntiSpam_Filter;
use CF7_AntiSpam\Core\CF7_AntiSpam_B8;

/**
 * Class Filter_B8_Bayesian
 */
class Filter_B8_Bayesian extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Checks B8 Bayesian Filter.
	 *
	 * @param array $data The data array.
	 *
	 * @return array The data array.
	 */
	public function process( array $data ): array {
		// Even if requested "at the end", we usually skip B8 if the user is explicitly Allowlisted.
		if ( $data['is_allowlisted'] ) {
			return $data;
		}

		$options = $data['options'];

		// There is no reason to check B8 if the ip was already blocklisted
		if ( isset( $data['reasons']['blocklisted'] ) ) {
			cf7a_log( "Submission failed for {$data['remote_ip']}, this ip was already blocklisted", 1 );
			return $data;
		}

		// Ensure $text is a string or return $data or, If there is no message, skip B8
		if ( ! isset( $data['message'] ) || ! is_string( $data['message'] ) ) {
			return $data;
		}

		$text = stripslashes( $data['message'] );

		if ( empty( trim( $text ) ) ) {
			cf7a_log( "Skipping B8 for {$data['remote_ip']}: message is empty", 1 );
			return $data;
		}

		// log the result of the pre-checks
		if ( $data['is_spam'] ) {
			cf7a_log( "Submission failed for {$data['remote_ip']}, spam detected with score {$data['spam_score']} - message: {$text}", 1 );
		}

		// Ensure B8 is enabled and there is a message to check
		if ( $options['enable_b8'] ) {
			$b8_threshold    = floatval( $options['b8_threshold'] );
			$b8_threshold    = $b8_threshold > 0 && $b8_threshold < 1 ? $b8_threshold : 1;
			$score_detection = floatval( $options['score']['_detection'] );

			// Store the spam score before B8
			$was_spam_before_b8 = $data['spam_score'] >= 1;

			$cf7a_b8 = new CF7_AntiSpam_B8();
			$rating  = round( $cf7a_b8->cf7a_b8_classify( $text ), 2 );

			// If the rating is high, add to spam score
			if ( $rating >= $b8_threshold ) {
				$data['reasons']['b8'] = $rating;
				$data['spam_score']   += $score_detection;
				$data['is_spam']       = true;
				cf7a_log( "B8 rating $rating / 1", 1 );
			}

			// LEARNING LOGIC:
			// Use the accumulated spam_score from previous filters to decide how to teach B8.
			if ( $was_spam_before_b8 ) {
				// Only learn spam if OTHER filters flagged it (not B8 itself)
				cf7a_log( "{$data['remote_ip']} detected as spam by filters (score {$data['spam_score']}), learning as SPAM.", 1 );
				$cf7a_b8->cf7a_b8_learn_spam( $text );
			} elseif ( $rating < $b8_threshold * 0.5 && 0 === $data['spam_score'] ) {
				// Only learn as ham if COMPLETELY clean (no warnings at all)
				cf7a_log( "B8 detected spamminess of $rating (below threshold) and no filter warnings, learning as HAM.", 1 );
				$cf7a_b8->cf7a_b8_learn_ham( $text );
			}
		}//end if
		return $data;
	}
}
