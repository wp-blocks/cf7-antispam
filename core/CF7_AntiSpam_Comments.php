<?php
/**
 * Comment Spam Protection class.
 *
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

			// Hooks for status transitions (Learning/Unlearning)
			add_action( 'transition_comment_status', array( $this, 'on_comment_status_transition' ), 10, 3 );

			// Hooks for the Admin UI
			add_filter( 'manage_edit-comments_columns', array( $this, 'add_comment_columns' ) );
			add_action( 'manage_comments_custom_column', array( $this, 'display_comment_columns' ), 10, 2 );
			add_filter( 'comment_text', array( $this, 'display_spam_reasons' ), 10, 2 );

			// Register filters for the comment chain once
			add_filter( 'cf7a_comment_spam_check_chain', array( new Filters\Filter_IP_Allowlist(), 'process' ), 5 );
			add_filter( 'cf7a_comment_spam_check_chain', array( new Filters\Filter_Empty_IP(), 'process' ), 10 );
			add_filter( 'cf7a_comment_spam_check_chain', array( new Filters\Filter_Bad_IP(), 'process' ), 10 );
			add_filter( 'cf7a_comment_spam_check_chain', array( new Filters\Filter_Time_Submission(), 'process' ), 10 );
			// Reuses existing time check logic
			add_filter( 'cf7a_comment_spam_check_chain', array( new Filters\Filter_B8_Bayesian(), 'process' ), 20 );
		}//end if
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

		// Promote to spam if the accumulated score meets the threshold (mirrors main orchestrator logic).
		if ( ! $spam_data['is_spam'] && $spam_data['spam_score'] >= 1 ) {
			$spam_data['is_spam'] = true;
		}

		if ( $spam_data['is_spam'] ) {
			// Force spam status
			add_filter(
				'pre_comment_approved',
				function () {
					return 'spam';
				}
			);

			$reasons_string = cf7a_compress_array( $spam_data['reasons'] );
			$this->log_spam( 'Spam detected: ' . $reasons_string );

			// Store reasons and classification for the UI
			add_action(
				'comment_post',
				function ( $comment_id ) use ( $spam_data, $reasons_string ) {
					update_comment_meta( $comment_id, '_cf7a_spam_reasons', $reasons_string );
					update_comment_meta( $comment_id, '_cf7a_b8_classification', $spam_data['spam_score'] );
				}
			);

			// Auto-ban if enabled
			if ( ! empty( $this->options['autostore_bad_ip'] ) && ! empty( $spam_data['remote_ip'] ) ) {
				CF7_Antispam_Blocklist::cf7a_ban_by_ip(
					$spam_data['remote_ip'],
					$spam_data['reasons'],
					$spam_data['spam_score']
				);
			}
		} else {
			// Even if not spam, store the B8 rating for ham
			add_action(
				'comment_post',
				function ( $comment_id ) use ( $spam_data ) {
					update_comment_meta( $comment_id, '_cf7a_b8_classification', $spam_data['spam_score'] );
				}
			);
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

	/**
	 * Handles learning when a comment is manually moved to/from spam.
	 *
	 * @param string $new_status The new status for the comment.
	 * @param string $old_status The previous status for the comment.
	 * @param object $comment    The comment object.
	 */
	public function on_comment_status_transition( $new_status, $old_status, $comment ) {
		if ( $new_status === $old_status ) {
			return;
		}

		$b8      = new CF7_AntiSpam_B8();
		$message = $comment->comment_content;

		if ( 'spam' === $new_status ) {
			$b8->cf7a_b8_unlearn_ham( $message );
			$b8->cf7a_b8_learn_spam( $message );
		} elseif ( 'approved' === $new_status && 'spam' === $old_status ) {
			$b8->cf7a_b8_unlearn_spam( $message );
			$b8->cf7a_b8_learn_ham( $message );
		}

		// Update rating after transition
		$new_rating = $b8->cf7a_b8_classify( $message, true );
		update_comment_meta( $comment->comment_ID, '_cf7a_b8_classification', $new_rating );
	}

	/**
	 * Add custom columns to the comments list.
	 *
	 * @param array $columns The existing columns.
	 * @return array The columns with custom columns added.
	 */
	public function add_comment_columns( $columns ) {
		$columns['cf7a_rating'] = __( 'B8 Rating', 'cf7-antispam' );
		return $columns;
	}

	/**
	 * Render the custom comment columns.
	 *
	 * @param string $column     The column name.
	 * @param int    $comment_id The comment ID.
	 */
	public function display_comment_columns( $column, $comment_id ) {
		if ( 'cf7a_rating' === $column ) {
			$rating = get_comment_meta( $comment_id, '_cf7a_b8_classification', true );
			echo cf7a_format_rating( $rating ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Append ban reasons to the comment text if it's marked as spam.
	 *
	 * @param string $text    The original comment text.
	 * @param object $comment The comment object.
	 * @return string The modified comment text.
	 */
	public function display_spam_reasons( $text, $comment ) {
		if ( is_admin() && 'spam' === $comment->comment_approved ) {
			$reasons = get_comment_meta( $comment->comment_ID, '_cf7a_spam_reasons', true );
			if ( $reasons ) {
				$text .= sprintf(
					'<div class="cf7a-spam-reason" style="color: #d63638; margin-top: 5px; font-size: 0.9em;"><strong>%s:</strong> %s</div>',
					esc_html__( 'Ban Reasons', 'cf7-antispam' ),
					esc_html( $reasons )
				);
			}
		}
		return $text;
	}
}
