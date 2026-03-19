<?php
/**
 * Filter for Bad Words.
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
 * Class Filter_Bad_Words
 */
class Filter_Bad_Words extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Checks for bad words in message.
	 * If the message contains bad words, the spam check is skipped.
	 *
	 * @param array $data The data array.
	 *
	 * @return array The data array.
	 */
	public function process( array $data ): array {
		$options = $data['options'];
		if ( intval( $options['check_bad_words'] ) !== 1 || '' === $data['message'] ) {
			return $data;
		}

		$bad_words          = $options['bad_words_list'] ?? array();
		$message_compressed = CF7_AntiSpam_Rules::cf7a_simplify_text( $data['message'] );

		foreach ( $bad_words as $bad_word ) {
			if ( false !== stripos( $message_compressed, CF7_AntiSpam_Rules::cf7a_simplify_text( $bad_word ) ) ) {
				$data['reasons']['bad_word'][] = $bad_word;
			}
		}

		if ( ! empty( $data['reasons']['bad_word'] ) ) {
			$logged_reasons = implode( ',', $data['reasons']['bad_word'] );
			cf7a_log( "{$data['remote_ip']} has bad word in message " . $logged_reasons, 1 );
		}
		return $data;
	}
}
