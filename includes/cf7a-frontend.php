<?php
class CF7_AntiSpam_Frontend {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * The options of this plugin.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      array    $options    options of this plugin.
	 */
	private $options;

	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		add_filter( 'wpcf7_form_hidden_fields', array( $this, 'cf7a_add_hidden_fields') , 100, 1 );

		$this->options = CF7_AntiSpam::get_options(); // the plugin options

		// TODO: change with honeypot
		if ( isset( $this->options['enable_honeypot'] ) ) {
			add_filter( 'wpcf7_form_elements', array( $this,'cf7a_honeypot_add'), 10, 1  );
			add_filter( 'wpcf7_spam', array($this, 'cf7_honeypot_verify_response'), 5, 1 );
		}

		if ( isset( $this->options['check_bot_fingerprint'] ) ) {
			add_filter( 'wpcf7_form_hidden_fields', array( $this, 'cf7a_add_bot_fingerprinting' ), 100, 1 );
		}

		if ( isset( $this->options['check_bot_fingerprint_extras'] ) ) {
			add_filter( 'wpcf7_form_hidden_fields', array( $this, 'cf7a_add_bot_fingerprinting_extras' ), 100, 1 );
		}
	}

	public function cf7a_honeypot_add( $form_elements ) {

		$html = new DOMDocument();
		$html->loadHTML( $form_elements, LIBXML_HTML_NODEFDTD );

		$inputs  = $html->getelementsbytagname( 'input' );
		$parents = [];
		$clones  = [];

		// $input_names = $this->options['custom_input_names'];
		$input_names = array('name','email','address','zip','town','phone','credit-card','ship-address', 'billing_company','billing_city', 'billing_country', 'email-address');

		//create the style
		$style = $html->createElement('style');
		$style->textContent = '.fit-the-fullspace {position:absolute;margin-left:-999em}';
		$html->appendChild($style);

		// get the inputs data
		if ( $inputs && $inputs->length > 0 ) {
			for ( $i = 0; $i < count( $inputs ); $i ++ ) {
				if ( $inputs->item( $i )->getAttribute( 'type' ) === 'text' ) {

					$parents[] = $inputs->item( $i )->parentNode;
					$clones[]  = $inputs->item( $i )->cloneNode();

					// $inputs->item( $i )->setAttribute( 'name', cf7a_crypt( $inputs->item( $i )->getAttribute( 'name' ) ) );
					$inputs->item( $i )->setAttribute( 'tabindex', '' );
					$inputs->item( $i )->setAttribute( 'class', $inputs->item( $i )->getAttribute( 'class' ) );
				}
			}
		}

		// duplicate the inputs into honeypots
		foreach ( $parents as $k => $parent ) {
			$name = isset($input_names[$k]) ? $input_names[$k] : $input_names[$k - count([$parent]) ];
			$clones[$k]->setAttribute( 'name', $name );
			$clones[$k]->setAttribute( 'autocomplete', 'fill' );
			$clones[$k]->setAttribute( 'tabindex', '-1' );
			$clones[$k]->setAttribute( 'class', $clones[$k]->getAttribute( 'class' ) . ' fit-the-fullspace autocomplete input' ); //TODO: leave the user able to set a new class
			$parent->appendChild( $clones[ $k ] );
		}

		return $html->saveHTML();
	}

	public function cf7_honeypot_verify_response($spam) {
		if ( $spam ) return $spam;

		$submission = WPCF7_Submission::get_instance();
		$contact_form = $submission->get_contact_form();
		$mail_tags=$contact_form->scan_form_tags();

		// we need only the text tags
		foreach ($mail_tags as $mail_tag) {
			if ( $mail_tag['type'] == 'text' || $mail_tag['type'] == 'text*' ) $mail_tag_text[] = $mail_tag['name'];
		}

		$count = 0;
		$input_names = array('name','email','address','zip','town','phone','credit-card','ship-address', 'billing_company','billing_city', 'billing_country', 'email-address');

		for ($i = 0; $i < count($mail_tag_text); $i++) {

			$_POST[$input_names[$i]] = isset($_POST[$input_names[$i]]) ? 1 : false;

			if ( !$_POST[$input_names[$i]] ) {
				$spam = true;
				$count++;
			}
			unset($_POST[$input_names[$i]]);

			$submission->add_spam_log( array(
				'agent' => 'honeypot',
				'reason' => "the bot has filled $count honeypot input",
			) );
		}

		return $spam;
	}

	public function cf7a_add_hidden_fields( $fields ) {

		$timestamp = time();

		$ip = cf7a_get_real_ip();

		return array_merge( $fields, array(
			'_wpcf7a_version' => $this->version,
			'_wpcf7a_real_sender_ip' => cf7a_crypt($ip),
			'_wpcf7a_form_creation_timestamp' => cf7a_crypt($timestamp),
		));
	}

	public function cf7a_add_bot_fingerprinting( $fields ) {
		return array_merge( $fields, array(
			'_wpcf7a_bot_fingerprint' => wp_hash_password(time())
		));
	}

	public function cf7a_add_bot_fingerprinting_extras( $fields ) {
		return array_merge( $fields, array(
			'_wpcf7a_bot_fingerprint_extras' => false
		));
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in load_admin as all of the hooks are defined
		 * in that particular class.
		 *
		 * The load_admin will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/script.js', array( 'jquery' ), $this->version, true );
	}
}