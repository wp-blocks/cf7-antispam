<?php
/**
 * Comment Spam Protection class.
 *
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core;

/**
 * Handles WordPress comment spam protection.
 */
class CF7_AntiSpam_Comments {

	/**
	 * The plugin options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
		$this->options = CF7_AntiSpam::get_options();
		// Only run if the feature is enabled
		if ( ! empty( $this->options['cf7_antispam_enable_comment_protection'] ) ) {
			add_action( 'comment_form', array( $this, 'inject_time_field' ) );
			// Increase priority to 99 to ensure we run late and override other plugins
			add_filter( 'preprocess_comment', array( $this, 'check_comment_spam' ), 99 );

			// Register filters for the comment chain once
			add_filter( 'cf7a_comment_spam_check_chain', array( new Filters\Filter_IP_Allowlist(), 'process' ), 5 );
			add_filter( 'cf7a_comment_spam_check_chain', array( new Filters\Filter_Empty_IP(), 'process' ), 10 );
			add_filter( 'cf7a_comment_spam_check_chain', array( new Filters\Filter_Bad_IP(), 'process' ), 10 );
			add_filter( 'cf7a_comment_spam_check_chain', array( new Filters\Filter_Time_Submission(), 'process' ), 10 );
			// Reuses existing time check logic
			add_filter( 'cf7a_comment_spam_check_chain', array( new Filters\Filter_B8_Bayesian(), 'process' ), 20 );
		}
	}

	/**
	 * Inject the hidden time field into the comment form.
	 */
	public function inject_time_field() {
		$prefix = isset( $this->options['cf7a_customizations_prefix'] ) ? sanitize_text_field( $this->options['cf7a_customizations_prefix'] ) : 'cf7a';
		$cipher = $this->options['cf7a_cipher'] ?? 'aes-256-cbc';

		// Use the same helper function as the main plugin to generate the encrypted timestamp
		$timestamp           = time();
		$encrypted_timestamp = cf7a_crypt( $timestamp, $cipher );

		printf(
			'<input type="hidden" name="%s_timestamp" value="%s" />',
			esc_attr( $prefix ),
			esc_attr( $encrypted_timestamp )
		);
	}

	/**
	 * Check the comment for spam.
	 *
	 * @param array $commentdata The comment data.
	 * @return array The comment data (potentially modified).
	 */
	public function check_comment_spam( $commentdata ) {

		// Run the spam check chain
		$spam_data = $this->run_spam_check( $commentdata );

		if ( $spam_data['is_spam'] ) {
			// Force spam status
			add_filter(
				'pre_comment_approved',
				function () {
					return 'spam';
				}
			);

			$this->log_spam( 'Spam detected: ' . wp_json_encode( $spam_data['reasons'] ) );

			// Auto-ban if enabled
			if ( ! empty( $this->options['autostore_bad_ip'] ) && ! empty( $spam_data['remote_ip'] ) ) {
				CF7_Antispam_Blocklist::cf7a_ban_by_ip(
					$spam_data['remote_ip'],
					$spam_data['reasons'],
					$spam_data['spam_score']
				);
			}

			return $commentdata;
		}//end if

		return $commentdata;
	}

	/**
	 * Runs the spam check chain.
	 *
	 * @param array $commentdata The comment data.
	 * @return array The spam data array.
	 */
	private function run_spam_check( $commentdata ) {
		$options = $this->options;

		$remote_ip = cf7a_get_real_ip();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$posted_data = $this->get_posted_comment( $_POST );

		// Initialize spam data structure expected by filters
		$spam_data = array(
			'submission'     => null,
			// Not available for comments
			'options'        => $options,
			'posted_data'    => $posted_data,
			'remote_ip'      => $remote_ip,
			'cf7_remote_ip'  => $remote_ip,
			'emails'         => array( $commentdata['comment_author_email'] ),
			'message'        => $commentdata['comment_content'],
			'mail_tags'      => array(),
			'user_agent'     => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			'spam_score'     => 0,
			'is_spam'        => false,
			'reasons'        => array(),
			'is_allowlisted' => false,
		);

		if ( CF7ANTISPAM_DEBUG_EXTENDED ) {
			cf7a_log( 'New comment from ' . $remote_ip . ' will be processed', 1 );
		}

		// Run the chain
		return apply_filters( 'cf7a_comment_spam_check_chain', $spam_data );
	}

	/**
	 * Log spam detection (if debugging is enabled).
	 *
	 * @param string $reason The reason for detection.
	 */
	private function log_spam( $reason ) {
		if ( function_exists( 'cf7a_log' ) ) {
			cf7a_log( "Comment blocked: $reason", 1 );
		}
	}

	/**
	 * Helper to safely get cleaned POST values for comments.
	 *
	 * @param array $post_data The POST data (usually $_POST).
	 *
	 * @return array The cleaned POST data.
	 */
	private function get_posted_comment( array $post_data ): array {
		$cleaned_data = array();

		// Clean standard WordPress comment fields
		if ( isset( $post_data['author'] ) ) {
			$cleaned_data['author'] = sanitize_text_field( wp_unslash( $post_data['author'] ) );
		}

		if ( isset( $post_data['email'] ) ) {
			$cleaned_data['email'] = sanitize_email( wp_unslash( $post_data['email'] ) );
		}

		if ( isset( $post_data['url'] ) ) {
			$cleaned_data['url'] = esc_url_raw( wp_unslash( $post_data['url'] ) );
		}

		if ( isset( $post_data['comment'] ) ) {
			$cleaned_data['comment'] = sanitize_textarea_field( wp_unslash( $post_data['comment'] ) );
		}

		if ( isset( $post_data['comment_post_ID'] ) ) {
			$cleaned_data['comment_post_ID'] = absint( $post_data['comment_post_ID'] );
		}

		if ( isset( $post_data['comment_parent'] ) ) {
			$cleaned_data['comment_parent'] = absint( $post_data['comment_parent'] );
		}

		// Clean the custom timestamp field injected by the plugin's inject_time_field() method
		$prefix        = isset( $this->options['cf7a_customizations_prefix'] ) ? sanitize_text_field( $this->options['cf7a_customizations_prefix'] ) : 'cf7a';
		$timestamp_key = $prefix . '_timestamp';

		if ( isset( $post_data[ $timestamp_key ] ) ) {
			// The encrypted timestamp is an alphanumeric/base64 string, so sanitize_text_field is appropriate
			$cleaned_data[ $timestamp_key ] = sanitize_text_field( wp_unslash( $post_data[ $timestamp_key ] ) );
		}

		return $cleaned_data;
	}
}
