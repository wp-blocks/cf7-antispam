<?php

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

			$flamingo_post = new Flamingo_Inbound_Message( $query->queried_object_id );

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
	}

	/**
	 * It adds a column to the Flamingo spam folder, and when you mark a message as spam or ham, it learns from it
	 */
	public function cf7a_d8_flamingo_classify() {
		$req_action = isset( $_REQUEST['action'] ) ? esc_attr( $_REQUEST['action'] ) : false;
		$req_save   = isset( $_REQUEST['save'] ) ? esc_attr( $_REQUEST['save'] ) : false;
		$req_status = isset( $_REQUEST['inbound']['status'] ) ? esc_attr( $_REQUEST['inbound']['status'] ) : false;

		if ( $req_action && ( 'spam' === $req_action || 'unspam' === $req_action || 'save' === $req_action ) ) {

			if ( 'save' === $req_action && 'Update' === $req_save ) {
				$action = 'spam' === $req_status ? 'spam' : 'ham';
			} elseif ( 'spam' === $req_action ) {
				$action = 'spam';
			} elseif ( 'unspam' === $req_action ) {
				$action = 'ham';
			}

			$options = get_option( 'cf7a_options' );

			$b8 = new CF7_AntiSpam_B8();

			if ( isset( $action ) ) {
				foreach ( (array) $_REQUEST['post'] as $post_id ) {

					$flamingo_post = new Flamingo_Inbound_Message( $post_id );

					cf7a_log( get_the_terms( $post_id, 'taxonomy' ) );
					cf7a_log( get_the_terms( $post_id, 'flamingo_inbound_channel' ) );

					$results = get_post_meta( $post_id );

					cf7a_log( $results );

					/* get the form tax using the slug we find in the flamingo message */
					$channel = isset( $post_meta['channel'] ) ?
						get_term( $flamingo_post->channel, 'flamingo_inbound_channel' ) :
						get_term_by( 'slug', $flamingo_post->channel, 'flamingo_inbound_channel' );

					cf7a_log( 'channel' );
					cf7a_log( $channel );

					/* get the message from flamingo mail */
					$message = $this->cf7a_get_mail_field( $flamingo_post, 'message' );

					if ( empty( $message ) ) {

						update_post_meta( $flamingo_post->id(), '_cf7a_b8_classification', 'none' );

						/* translators: %s - the post id. */
						cf7a_log( sprintf( __( "%s has no message text so can't be analyzed", 'cf7-antispam' ), $post_id ), 1 );

					} else {

						$rating = $b8->cf7a_b8_classify( $message );

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
							/* translators: %1$s is the mail "from" field (the sender). %2$s spam/ham. %3$s and %4$s the rating of the processed email */
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
	 * @param Flamingo_Inbound_Message $flamingo_post - a flamingo post id.
	 * @param string                   $field - the field we are looking for.
	 *
	 * @return false|string The requested field from the form.
	 */
	private static function cf7a_get_mail_field( $flamingo_post, $field ) {

		// if ( ! empty( $flamingo_post->fields ) && isset( $flamingo_post->meta['message_field'] ) && isset($flamingo_post->fields[ $flamingo_post->meta['message_field'] ])) {
		// return $flamingo_post->fields[ $flamingo_post->meta['message_field'] ];
		// }

		if ( isset( $form->slug ) ) {
			/* get the post where are stored the form data */
			$form_post = get_page_by_path( $form->slug, '', 'wpcf7_contact_form' );

			/* get the additional setting of the form */
			$additional_settings = isset( $form_post->ID ) ? self::cf7a_get_mail_additional_data( $form_post->ID ) : null;

			/* if the field we are looking for return it */
			if ( ! empty( $additional_settings ) && $flamingo_post->fields[ $additional_settings[ $field ] ] ) {
				return stripslashes( $flamingo_post->fields[ $additional_settings[ $field ] ] );
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

		/* get the meta fields */
		$flamingo_meta = get_post_meta( $mail_id, '_meta', true );

		/* get form fields data */
		$flamingo_fields = get_post_meta( $mail_id, '_fields', true );
		if ( ! empty( $flamingo_fields ) ) {
			foreach ( (array) $flamingo_fields as $key => $value ) {
				$meta_key = sanitize_key( '_field_' . $key );

				if ( metadata_exists( 'post', $mail_id, $meta_key ) ) {
					$value                   = get_post_meta( $mail_id, $meta_key, true );
					$flamingo_fields[ $key ] = $value;
				}
			}
		}

		if ( ! $flamingo_meta['message_field'] || empty( $flamingo_fields[ $flamingo_meta['message_field'] ] ) ) {
			return true;
		}

		$post_data = get_post( $mail_id );

		/* init mail */
		$subject   = $flamingo_meta['subject'];
		$sender    = $flamingo_data->from;
		$body      = $flamingo_fields[ $flamingo_meta['message_field'] ];
		$recipient = $flamingo_meta['recipient'];

		$headers  = "From: $sender\n";
		$headers .= "Content-Type: text/html\n";
		$headers .= "X-WPCF7-Content-Type: text/html\n";
		$headers .= "Reply-To: $sender <$recipient>\n";

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
					preg_match( '/flamingo_(.*)(?=:): "\[(.*)]"/', $line, $matches );
					$additional_settings[ $matches[1] ] = $matches[2];
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

		/* get the contact form data mail data */
		$cf = $submission->get_contact_form();

		/* form additional settings */
		$additional_settings = self::cf7a_get_mail_additional_data( $result['contact_form_id'] );

		/* update post meta and add the cf7-antispam customized tags form_id and message_field */
		$stored_fields = get_post_meta( $result['flamingo_inbound_id'], '_meta', true );
		update_post_meta( $result['flamingo_inbound_id'], '_meta', array_merge( $stored_fields, array(
			'form_id'       => $result['contact_form_id'],
			'message_field' => $additional_settings['message']
		) ) );

		/* then is time to classify the mail with b8 */
		if ( ! empty( $additional_settings ) && isset( $posted_data[ $additional_settings['message'] ] ) ) {

			$b8 = new CF7_AntiSpam_B8();

			$text   = stripslashes( $posted_data[ $additional_settings['message'] ] );
			$rating = ! empty( $text ) ? $b8->cf7a_b8_classify( $text ) : 'none';

			update_post_meta( $result['flamingo_inbound_id'], '_cf7a_b8_classification', round( $rating, 2 ) );
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
				cf7a_format_rating( floatval( $classification ) ),
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
}
