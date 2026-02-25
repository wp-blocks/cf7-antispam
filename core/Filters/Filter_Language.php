<?php
/**
 * Filter for Language.
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
 * Class Filter_Language
 */
class Filter_Language extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Checks Language consistency.
	 * If the language does not match, the spam check is skipped.
	 *
	 * @param array $data The data array.
	 *
	 * @return array The data array.
	 */
	public function process( array $data ): array {
		$options = $data['options'];
		if ( intval( $options['check_language'] ) !== 1 ) {
			return $data;
		}

		$prefix          = $this->get_prefix( $options );
		$score_detection = floatval( $options['score']['_detection'] );

		$languages                     = array();
		$languages['browser_language'] = $this->get_posted_value( $prefix . 'browser_language' );

		// The right way to do this is BEFORE decrypting and THEN sanitize, because sanitized data are stripped of any special characters
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing
		$languages['accept_language'] = isset( $_POST[ $prefix . '_language' ] ) ? sanitize_text_field( wp_unslash( cf7a_decrypt( $_POST[ $prefix . '_language' ], $options['cf7a_cipher'] ) ) ) : null;

		if ( empty( $languages['browser_language'] ) ) {
			$data['spam_score']                 += $score_detection;
			$data['reasons']['browser_language'] = 'missing browser language';
		} else {
			$languages_locales    = cf7a_get_browser_languages_locales_array( $languages['browser_language'] );
			$languages['browser'] = $languages_locales['languages'];
		}

		if ( empty( $languages['accept_language'] ) ) {
			$data['spam_score']               += $score_detection;
			$data['reasons']['language_field'] = 'missing language field';
		} else {
			$languages['accept'] = cf7a_get_accept_language_array( $languages['accept_language'] );
		}

		if ( ! empty( $languages['accept'] ) && ! empty( $languages['browser'] ) ) {
			if ( ! array_intersect( $languages['browser'], $languages['accept'] ) ) {
				$data['spam_score']                     += $score_detection;
				$data['reasons']['language_incoherence'] = 'languages detected not coherent';
			}

			$client_languages     = array_unique( array_merge( $languages['browser'], $languages['accept'] ) );
			$languages_allowed    = isset( $options['languages_locales']['allowed'] ) ? CF7_AntiSpam_Rules::cf7a_get_languages_or_locales( $options['languages_locales']['allowed'], 'languages' ) : array();
			$languages_disallowed = isset( $options['languages_locales']['disallowed'] ) ? CF7_AntiSpam_Rules::cf7a_get_languages_or_locales( $options['languages_locales']['disallowed'], 'languages' ) : array();

			$language_disallowed = CF7_AntiSpam_Rules::cf7a_check_languages_locales_allowed( $client_languages, $languages_disallowed, $languages_allowed );

			if ( false === $language_disallowed ) {
				$data['spam_score']                    += $score_detection;
				$data['reasons']['disallowed_language'] = implode( ', ', $client_languages );
			}
		}
		return $data;
	}
}
