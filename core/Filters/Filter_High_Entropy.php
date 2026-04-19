<?php
/**
 * Filter for High Entropy (Gibberish) Strings.
 *
 * @since      [YOUR_NEXT_VERSION]
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core/Filters
 */

namespace CF7_AntiSpam\Core\Filters;

use CF7_AntiSpam\Core\Abstract_CF7_AntiSpam_Filter;

/**
 * Class Filter_High_Entropy
 */
class Filter_High_Entropy extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Checks for high-entropy/gibberish (unnatural consecutive consonants or extreme long single words).
	 *
	 * @param array $data The data array.
	 *
	 * @return array The data array.
	 */
	public function process( array $data ): array {
		// Ensure there is a message to check
		if ( empty( $data['message'] ) ) {
			return $data;
		}

		$options = $data['options'];
		if ( intval( $options['check_high_entropy'] ) !== 1 ) {
			return $data;
		}

		$message_clean = trim( $data['message'] );

		if ( empty( $message_clean ) ) {
			return $data;
		}

		$is_spam = false;
		$reasons = array();

		$min_words  = isset( $options['high_entropy_min_words'] ) ? intval( $options['high_entropy_min_words'] ) : 5;
		$consonants = isset( $options['high_entropy_consecutive_consonants'] ) ? intval( $options['high_entropy_consecutive_consonants'] ) : 6;

		// The email should have a minimum number of words
		if ( str_word_count( $message_clean ) < $min_words ) {
			$is_spam   = true;
			$reasons[] = 'single_long_gibberish_word';
		}

		// Unnatural consecutive consonants (e.g. 6 or more in a row)
		if ( preg_match( '/[bcdfghjklmnpqrstvwxyz]{' . $consonants . ',}/i', $message_clean ) ) {
			$is_spam   = true;
			$reasons[] = 'high_entropy_consonants';
		}

		if ( $is_spam ) {
			// Apply a strict penalty for gibberish
			$data['reasons']['high_entropy'] = $reasons;

			if ( is_array( $data['reasons']['high_entropy'] ) && ! empty( $data['reasons']['high_entropy'] ) ) {
				$reason_string = implode( ',', $data['reasons']['high_entropy'] );
				cf7a_log( "The ip address {$data['remote_ip']} sent a message matching high entropy/gibberish patterns: {$reason_string}", 1 );
			}
		}

		return $data;
	}
}
