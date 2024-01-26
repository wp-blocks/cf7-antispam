<?php

namespace CF7_AntiSpam\Core;

use WP_Query;
use WPCF7_ContactForm;
use WPCF7_Submission;
use Flamingo_Inbound_Message;
/**
 * Flamingo related functions.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/includes
 * @author     Codekraft Studio <info@codekraft.it>
 */

/**
 * A class that is used to connect this plugin with Flamingo
 */
class CF7_AntiSpam_Flamingo {

	/**
	 * It checks the database for any stored emails that have been sent by Contact Form 7, and if it finds any, it adds them
	 * to the Flamingo database
	 */
	public function cf7a_flamingo_on_install() {
		$this->cf7a_flamingo_analyze_stored_mails();
	}

	/**
	 * It gets all the Flamingo inbound posts, and for each one, it gets the content of the post, and then it uses the b8
	 * classifier to classify the content as spam or ham
	 */
	public static function cf7a_flamingo_analyze_stored_mails() {

		/* get all the flamingo inbound post and classify them */
		$args = array(
			'post_type'      => 'flamingo_inbound',
			'posts_per_page' => -1,
			'post_status'    => array( 'publish', 'flamingo-spam' ),
		);

		$query = new WP_Query( $args );

		$b8 = new CF7_AntiSpam_B8();

		while ( $query->have_posts() ) :
			$query->the_post();

			$post_id = get_the_ID();

			$flamingo_post = new Flamingo_Inbound_Message( $post_id );

			$message = self::cf7a_get_mail_field( $flamingo_post, 'message' );

			if ( $message ) {
				if ( ! $flamingo_post->spam ) {
					$b8->cf7a_b8_learn_ham( $message );
				} else {
					$b8->cf7a_b8_learn_spam( $message );
				}

				update_post_meta( $post_id, '_cf7a_b8_classification', $b8->cf7a_b8_classify( $message, true ) );
			} else {
				cf7a_log( "Flamingo post $post_id seems empty, so can't be analyzed", 1 );
			}
		endwhile;

		return true;
	}

	/**
	 * It adds a column to the Flamingo spam folder, and when you mark a message as spam or ham, it learns from it
	 */
	public function cf7a_d8_flamingo_classify() {
		$req_action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : false;
		$req_save   = isset( $_REQUEST['save'] ) ? sanitize_key( wp_unslash( $_REQUEST['save'] ) ) : false;
		$req_status = isset( $_REQUEST['inbound']['status'] ) ? sanitize_key( wp_unslash( $_REQUEST['inbound']['status'] ) ) : false;
		$req_id     = isset( $_REQUEST['post'] ) ? intval( $_REQUEST['post'] ) : false;

		if ( $req_action && ( 'spam' === $req_action || 'unspam' === $req_action || 'save' === $req_action ) ) {
			if ( 'save' === $req_action && 'Update' === $req_save ) {
				$action = 'spam' === $req_status ? 'spam' : 'ham';
			}

			if ( 'spam' === $req_action ) {
				$action = 'spam';
			} elseif ( 'unspam' === $req_action ) {
				$action = 'ham';
			}

			if ( isset( $action ) ) {
				$options = get_option( 'cf7a_options' );

				$b8 = new CF7_AntiSpam_B8();
				foreach ( (array) $req_id as $post_id ) {
					$flamingo_post = new Flamingo_Inbound_Message( $post_id );
					wp_verify_nonce( 'flamingo-update-inbound_' . $flamingo_post->id() );

					/* get the message from flamingo mail */
					$message = $this->cf7a_get_mail_field( $flamingo_post, 'message' );

					if ( empty( $message ) ) {
						update_post_meta( $flamingo_post->id(), '_cf7a_b8_classification', 'none' );

						/* translators: %s - the post id. */
						cf7a_log( sprintf( __( "%s has no message text so can't be analyzed", 'cf7-antispam' ), $post_id ), 1 );
					} else {
						$rating = ! empty( $flamingo_post->meta['_cf7a_b8_classification'] ) ? $flamingo_post->meta['_cf7a_b8_classification'] : $b8->cf7a_b8_classify( $message );

						$filters = new CF7_AntiSpam_Filters();

						if ( ! $flamingo_post->spam && 'spam' === $action ) {
							$b8->cf7a_b8_unlearn_ham( $message );
							$b8->cf7a_b8_learn_spam( $message );

							if ( $options['autostore_bad_ip'] ) {
								$filters->cf7a_ban_by_ip( $flamingo_post->meta['remote_ip'], 'flamingo ban' );
							}
						} elseif ( $flamingo_post->spam && 'ham' === $action ) {
							$b8->cf7a_b8_unlearn_spam( $message );
							$b8->cf7a_b8_learn_ham( $message );

							if ( $options['autostore_bad_ip'] ) {
								$filters->cf7a_unban_by_ip( $flamingo_post->meta['remote_ip'] );
							}
						}

						$rating_after = $b8->cf7a_b8_classify( $message, true );

						update_post_meta( $flamingo_post->id(), '_cf7a_b8_classification', $rating_after );

						cf7a_log(
							CF7ANTISPAM_LOG_PREFIX . sprintf(
							/* translators: %1$s is the mail "from" field (the sender). %2$s spam/ham. %3$s and %4$s the rating of the processed email (like 0.6/1) */
								__( 'b8 has learned this e-mail from %1$s was %2$s - score before/after: %3$f/%4$f', 'cf7-antispam' ),
								$flamingo_post->from_email,
								$action,
								$rating,
								$rating_after
							),
							1
						);
					}
				}
			}
		}
	}

	/**
	 * It gets the message content from a Flamingo post
	 *
	 * @warning this work only if the flamingo message has the channel stored,
	 * usually the contact form the contact form and its shortcode must be
	 * configured properly (you can figure out from how in flamingo inbound you have two items e.g. Contact Form 7 / form name).
	 *
	 * @param Flamingo_Inbound_Message $flamingo_post - a flamingo post id.
	 * @param string                   $field - the field we are looking for.
	 *
	 * @return false|string The requested field from the form.
	 */
	private static function cf7a_get_mail_field( $flamingo_post, $field ) {

		/* get the form tax using the slug we find in the flamingo message */
		$channel = isset( $flamingo_post->meta['channel'] ) ?
			get_term( $flamingo_post->channel, 'flamingo_inbound_channel' ) :
			get_term_by( 'slug', $flamingo_post->channel, 'flamingo_inbound_channel' );

		if ( isset( $channel->slug ) ) {
			/* get the post where are stored the form data */
			$form_post = get_page_by_path( $channel->slug, '', 'wpcf7_contact_form' );

			/* get the additional setting of the form */
			$additional_settings = isset( $form_post->ID ) ? self::cf7a_get_mail_additional_data( $form_post->ID ) : null;

			if ( 'message' !== $field ) {
				if ( ! empty( $additional_settings ) && ! empty( $additional_settings[ $field ] ) && ! empty( $flamingo_post->fields[ $additional_settings[ $field ] ] ) ) {
					return esc_html( $flamingo_post->fields[ $additional_settings[ $field ] ] );
				}
			} else {
				/* the message field could be multiple */
				$message_meta = isset( $additional_settings[ $field ] ) ? $additional_settings[ $field ] : false;
				$message      = cf7a_maybe_split_mail_meta( $flamingo_post->fields, $message_meta, ' ' );

				if ( ! empty( $message ) ) {
					return esc_html( $message );
				}
			}
		}

		if ( 'message' === $field ) {
			cf7a_log( 'Original contact form slug not found for flamingo post id ' . $flamingo_post->id() . '. please check your contact form 7 shortcode / settings', 2 );

			/* the message field could be multiple */
			$message = ! empty( $flamingo_post->meta['message_field'] ) ? cf7a_maybe_split_mail_meta( $flamingo_post->fields, $flamingo_post->meta['message_field'], ' ' ) : '';

			if ( ! empty( $message ) ) {
				return esc_html( $message );
			}
		}

		return false;
	}

	/**
	 * It takes a Flamingo mail ID, gets the data from the Flamingo database, and then resends the email.
	 *
	 * @param int $mail_id The ID of the mail to resend.
	 *
	 * @return bool|mixed|null
	 */
	public function cf7a_resend_mail( $mail_id ) {
		$flamingo_data = new Flamingo_Inbound_Message( $mail_id );

		if ( ! empty( $flamingo_data->meta['message_field'] ) && ! empty( $flamingo_data->fields[ $flamingo_data->meta['message_field'] ] ) ) {
			$message = $flamingo_data->fields[ $flamingo_data->meta['message_field'] ];
		}

		if ( empty( $message ) ) {
			$message = self::cf7a_get_mail_field( $flamingo_data, 'message' );
		}

		if ( empty( $message ) ) {
			return 'empty';
		}

		/* the mail data */
		$sender  = $flamingo_data->from;
		$subject = $flamingo_data->subject;
		$body    = $message;

		// get the form id from the meta
		$form_id = $flamingo_data->meta['form_id'];

		// TODO: we are skipping the mail_2 for now

		// Get the mail recipient
		$form       = WPCF7_ContactForm::get_instance( $form_id );
		$form_props = $form->get_properties();
		$recipient  = $form_props['mail']['recipient'];
		if ( $form_props['mail']['recipient'] || ! empty( $flamingo_data->meta['recipient'] ) ) {
			if ( ! filter_var( $recipient, FILTER_VALIDATE_EMAIL ) || ! empty( $recipient ) ) {
				if ( '[_site_admin_email]' === $recipient ) {
					$recipient = $flamingo_data->meta['site_admin_email'];
				} elseif ( '[_post_author]' === $recipient ) {
					$recipient = get_option( 'post_author_email' ); // check this, not sure 🤔
				} else {
					$recipient = get_option( 'admin_email' );
				}
			}
		}

		/**
		 * Filter cf7-antispam before resend an email who was spammed
		 *
		 * @param string $body the mail message content
		 * @param string  $sender  the mail message sender
		 * @param string  $subject  the mail message subject
		 *
		 * @returns string the mail body content
		 */
		$body = apply_filters( 'cf7a_before_resend_email', $body, $sender, $subject );

		$headers  = "From: {$recipient}\n";
		$headers .= "Content-Type: text/html\n";
		$headers .= "X-WPCF7-Content-Type: text/html\n";
		$headers .= "Reply-To: $sender\n";

		/* send the email */
		return wp_mail( $recipient, $subject, $body, $headers );
	}

	/**
	 * It takes the form ID as a parameter and returns an array of the additional settings for that form
	 *
	 * @param int $form_post_id The ID of the form post.
	 *
	 * @return array|false The additional settings of the form.
	 */
	public static function cf7a_get_mail_additional_data( $form_post_id ) {

		/* get the additional setting of the form */
		$form_additional_settings = get_post_meta( $form_post_id, '_additional_settings', true );

		if ( ! empty( $form_additional_settings ) ) {
			$lines = explode( "\n", $form_additional_settings );

			$additional_settings = array();

			/* extract the flamingo_key = value; */
			foreach ( $lines as $line ) {
				if ( substr( trim( $line ), 0, 9 ) === 'flamingo_' ) {
					$matches = array();
					if ( preg_match( '/flamingo_(.*)(?=:): "\[(.*)]"/', $line, $matches ) ) {
						$additional_settings[ $matches[1] ] = $matches[2];
					}
				}
			}

			return $additional_settings;
		}

		return false;
	}

	/**
	 * Using the id of the newly stored flamingo email set the classification meta to that post
	 *
	 * @param array $result - The mail data.
	 *
	 * @return bool|void
	 */
	public function cf7a_flamingo_store_additional_data( $result ) {
		$submission = WPCF7_Submission::get_instance();

		if ( ! $submission ) {
			return true;
		}

		$posted_data = $submission->get_posted_data();

		/* form additional settings */
		$additional_settings = self::cf7a_get_mail_additional_data( $result['contact_form_id'] );

		/* this is a real monkey patching to remove the "] [" */
		$message_list = sanitize_text_field( implode( ' ', explode( '] [', $additional_settings['message'] ) ) );

		/* update post meta and add the cf7-antispam customized tags form_id and message_field */
		$stored_fields = (array) get_post_meta( $result['flamingo_inbound_id'], '_meta', true );
		update_post_meta(
			$result['flamingo_inbound_id'],
			'_meta',
			array_merge(
				$stored_fields,
				array(
					'form_id'       => $result['contact_form_id'],
					'message_field' => $message_list,
				)
			)
		);

		/* then is time to classify the mail with b8 */
		if ( ! empty( $posted_data ) && ! empty( $message_list ) ) {
			$message = cf7a_maybe_split_mail_meta( $posted_data, $message_list, ' ' );

			$b8 = new CF7_AntiSpam_B8();

			$rating = ! empty( $message ) ? round( $b8->cf7a_b8_classify( $message ), 2 ) : 'none';

			update_post_meta( $result['flamingo_inbound_id'], '_cf7a_b8_classification', is_numeric( $rating ) ? round( $rating, 2 ) : $rating );
		}
	}

	/**
	 * It removes the honeypot field from the Flamingo database
	 *
	 * @param array $result The result of the submission.
	 *
	 * @return bool|int
	 */
	public function cf7a_flamingo_remove_honeypot( $result ) {
		$options = get_option( 'cf7a_options', array() );

		if ( isset( $options['check_honeypot'] ) && intval( $options['check_honeypot'] ) === 1 ) {
			$submission             = WPCF7_Submission::get_instance();
			$honeypot_default_names = get_honeypot_input_names( $options['honeypot_input_names'] );

			if ( ! $submission ) {
				return true;
			}

			$posted_data = $submission->get_posted_data();

			$fields = array();

			foreach ( $posted_data as $key => $field ) {

				/* if a honeypot field was found into posted data delete it */
				if ( in_array( $key, $honeypot_default_names, true ) && empty( $field ) ) {
					delete_post_meta( $result['flamingo_inbound_id'], '_field_' . $key );
				} else {
					$fields[ $key ] = null;
				}
			}

			return update_post_meta( $result['flamingo_inbound_id'], '_fields', $fields );
		}

		return true;
	}


	/**
	 * FLAMINGO CUSTOMIZATIONS
	 *
	 * It adds two columns to the Flamingo plugin.
	 *
	 * @param array $columns The original array of columns.
	 *
	 * @return array The new columns set for flamingo inbound page
	 */
	public static function flamingo_columns( $columns ) {
		return array_merge(
			$columns,
			array(
				'd8'     => __( 'D8 classification', 'cf7-antispam' ),
				'resend' => __( 'CF7-AntiSpam actions', 'cf7-antispam' ),
			)
		);
	}

	/**
	 * It adds a new column to the Flamingo Inbound Messages list table, and populates it with the value of the custom field
	 * `_cf7a_b8_classification`
	 *
	 * @param string $column The name of the column to display.
	 * @param int    $post_id The post ID of the post being displayed.
	 */
	public static function flamingo_d8_column( $column, $post_id ) {
		$classification = get_post_meta( $post_id, '_cf7a_b8_classification', true );
		if ( 'd8' === $column ) {
			echo wp_kses(
				/* translators: none is a label, please keep it short! thanks! */
				cf7a_format_rating( 'none' === $classification ? esc_html__( 'none', 'cf7-antispam' ) : floatval( $classification ) ),
				array(
					'span' => array(
						'class' => true,
						'style' => true,
					),
					'b'    => array(),
				)
			);
		}
	}

	/**
	 * It adds a new column to the CF7 admin page, and adds a button to each row of the table
	 *
	 * @param string $column The name of the column.
	 * @param int    $post_id The post ID of the post being displayed.
	 */
	public static function flamingo_resend_column( $column, $post_id ) {
		if ( 'resend' === $column ) {
			$url = wp_nonce_url( add_query_arg( 'action', 'cf7a_resend_' . $post_id, menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce' );
			printf(
				'<a class="button cf7a_alert" data-href="%s" data-message="%s">%s</a>',
				esc_url_raw( $url ),
				esc_html__( 'Are you sure?', 'cf7-antispam' ),
				esc_html__( 'Resend Email', 'cf7-antispam' )
			);
		}
	}



	/**
	 * It resets the database table that stores the spam and ham words
	 *
	 * @return bool - The result of the query.
	 */
	public static function cf7a_reset_dictionary() {
		global $wpdb;
		$r = $wpdb->query( "TRUNCATE TABLE `{$wpdb->prefix}cf7a_wordlist`" );

		if ( ! is_wp_error( $r ) ) {
			$wpdb->query( 'INSERT INTO `' . $wpdb->prefix . "cf7a_wordlist` (`token`, `count_ham`) VALUES ('b8*dbversion', '3');" );
			$wpdb->query( 'INSERT INTO `' . $wpdb->prefix . "cf7a_wordlist` (`token`, `count_ham`, `count_spam`) VALUES ('b8*texts', '0', '0');" );
			return true;
		}
		return false;
	}

	/**
	 * It deletes all the _cf7a_b8_classification metadata from the database
	 */
	public static function cf7a_reset_b8_classification() {
		global $wpdb;
		$r = $wpdb->query( 'DELETE FROM ' . $wpdb->prefix . "postmeta WHERE `meta_key` = '_cf7a_b8_classification'" );
		return ( ! is_wp_error( $r ) );
	}

	/**
	 * It resets the dictionary and classification, then analyzes all the stored mails
	 *
	 * @return bool - The return value is the number of mails that were analyzed.
	 */
	public static function cf7a_rebuild_dictionary() {
		if ( self::cf7a_reset_dictionary() ) {
			if ( self::cf7a_reset_b8_classification() ) {
				return self::cf7a_flamingo_analyze_stored_mails();
			}
		}
		return false;
	}
}
