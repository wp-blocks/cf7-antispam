<?php
/**
 * Abstract class for CF7 AntiSpam filters.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core;

/**
 * Abstract class Abstract_CF7_AntiSpam_Filter.
 *
 * All concrete spam filters must extend this class and implement the check method.
 */
abstract class Abstract_CF7_AntiSpam_Filter {

	/**
	 * Run the filter check.
	 *
	 * @param array $spam_data The spam data context.
	 *
	 * @return array The updated spam data context.
	 */
	public function check( array $spam_data ): array {
		if ( isset( $spam_data['is_allowlisted'] ) && $spam_data['is_allowlisted'] ) {
			return $spam_data;
		}

		if ( isset( $spam_data['is_spam'] ) && $spam_data['is_spam'] ) {
			return $spam_data;
		}

		return $this->process( $spam_data );
	}

	/**
	 * Process the filter logic.
	 *
	 * @param array $spam_data The spam data context.
	 *
	 * @return array The updated spam data context.
	 */
	abstract protected function process( array $spam_data ): array;

	/**
	 * Helper to safely get cleaned POST values.
	 *
	 * @param string  $key      The POST key to retrieve.
	 * @param mixed   $default_key  Default value if key is missing.
	 * @param boolean $sanitize Whether to sanitize the value. Default true.
	 *
	 * @return mixed The (sanitized) value.
	 */
	protected function get_posted_value( $key, $default_key = null, $sanitize = true ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST[ $key ] ) ) {
			return $default_key;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$value = $_POST[ $key ];

		if ( ! $sanitize ) {
			return wp_unslash( $value );
		}

		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', wp_unslash( $value ) );
		}

		return sanitize_text_field( wp_unslash( $value ) );
	}

	/**
	 * Get the prefix from options, with a fallback.
	 *
	 * @param array $options The plugin options.
	 *
	 * @return string The prefix.
	 */
	protected function get_prefix( $options ) {
		return ! empty( $options['cf7a_customizations_prefix'] ) ? sanitize_text_field( $options['cf7a_customizations_prefix'] ) : 'cf7a';
	}
}
