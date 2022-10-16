<?php
class CF7_AntiSpam_Frontend {

	/**
	 * The ID of this plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * The options of this plugin.
	 *
	 * @since    0.1.0
	 * @access   public
	 * @var      array    $options    options of this plugin.
	 */
	private $options;

	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		add_filter( 'wpcf7_form_hidden_fields', array( $this, 'cf7a_add_hidden_fields') , 100, 1 );

		$this->options = CF7_AntiSpam::get_options(); // the plugin options

		if ( isset( $this->options['check_bot_fingerprint'] ) && intval($this->options['check_bot_fingerprint']) === 1 ) {
			add_filter( 'wpcf7_form_hidden_fields', array( $this, 'cf7a_add_bot_fingerprinting' ), 100, 1 );
		}

		if ( isset( $this->options['check_bot_fingerprint_extras'] ) && intval($this->options['check_bot_fingerprint_extras']) === 1 ) {
			add_filter( 'wpcf7_form_hidden_fields', array( $this, 'cf7a_add_bot_fingerprinting_extras' ), 100, 1 );
		}

		if ( isset( $this->options['append_on_submit'] ) && intval($this->options['append_on_submit']) === 1 ) {
			add_filter( 'wpcf7_form_hidden_fields', array( $this, 'cf7a_append_on_submit' ), 100, 1 );
		}

		if ( isset( $this->options['check_honeypot'] ) && intval($this->options['check_honeypot']) === 1 ) {
			add_filter( 'wpcf7_form_elements', array( $this,'cf7a_honeypot_add'), 10, 1  );
		}

		$hook = $this->options['honeyform_position'];
		if ( isset( $this->options['check_honeyform'] ) && intval($this->options['check_honeyform']) === 1 ) {
			if ( !is_admin() && ( defined('REST_REQUEST') && !REST_REQUEST ) ) add_action( $hook, array( $this,'cf7a_honeyform') , 99 );
		}

		if ( (isset( $this->options['check_honeypot'] ) && intval($this->options['check_honeypot']) === 1 ) ||
		     (isset( $this->options['check_honeyform'] ) && intval($this->options['check_honeyform']) === 1) ) {
			add_action( 'wp_footer', array( $this,'cf7a_add_honeypot_css'), 11  );
		}
	}

	/**
	 * @param $form_elements
	 *
	 * @return string
	 */
	public function cf7a_honeypot_add( $form_elements ) {

		$html = new DOMDocument();
		$html->encoding = 'utf-8';
		$html->loadHTML( mb_convert_encoding( force_balance_tags($form_elements), 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED );

		$inputs  = $html->getelementsbytagname( 'input' );
		$parents = array();
		$clones  = array();

		$input_names = sanitize_html_class($this->options['honeypot_input_names']);
		$input_class = sanitize_html_class($this->options['cf7a_customizations_class']);

		// get the inputs data
		if ( $inputs && $inputs->length > 0 ) {
			// to be on the save side it can be a good idea to store the name of the input (to avoid duplicates)
			for ( $i = 0; $i < count( $inputs ); $i ++ ) {
				if ( $inputs->item( $i )->getAttribute( 'type' ) === 'text' ) {

					$parents[] = $inputs->item( $i )->parentNode;
					$clones[]  = $inputs->item( $i )->cloneNode();

					$inputs->item( $i )->setAttribute( 'tabindex', '' );
					$inputs->item( $i )->setAttribute( 'class', $inputs->item( $i )->getAttribute( 'class' ) );
				}
			}
		}

		$honeypot_default_names = array('name','email','address','zip','town','phone','credit-card','ship-address', 'billing_company','billing_city', 'billing_country', 'email-address');

		// duplicate the inputs into honeypots
		foreach ( $parents as $k => $parent ) {
			// TODO: it needs an internal list if the user has inserted few names
			$honeypot_names = isset($input_names[$k]) ? $input_names[$k] : $honeypot_default_names[$k - count($input_names)];
			$clones[$k]->setAttribute( 'name', $honeypot_names );
			$clones[$k]->setAttribute( 'autocomplete', 'fill' );
			$clones[$k]->setAttribute( 'tabindex', '-1' );
			$clones[$k]->setAttribute( 'class', $clones[$k]->getAttribute( 'class' ) . ' '.$input_class.' autocomplete input' );
			$parent->appendChild( $clones[ $k ] );
		}

		return $html->saveHTML();
	}

	public function cf7a_honeyform( $content ) {

		$form_class = sanitize_html_class($this->options['cf7a_customizations_class']);
		$form_post_id = 0;

		$args = array( 'post_type' => 'wpcf7_contact_form', 'posts_per_page' => 1);
		$loop = new WP_Query( $args );
		while ( $loop->have_posts() ) : $loop->the_post();
			$form_post_id = get_the_ID();
		endwhile;

		$WPCF7 = WPCF7_ContactForm::get_template();

		static $global_count = 0;
		$global_count += 1;

		$unit_tag = sprintf( 'wpcf7-f%1$d-p%2$d-o%3$d',
			$form_post_id,
			get_the_ID(),
			$global_count
		);

		$url = add_query_arg( array() );
		if ( $frag = strstr( $url, '#' ) ) {
			$url = substr( $url, 0, -strlen( $frag ) );
		}
		$url .= '#' . $unit_tag;

		$lang_tag = str_replace( '_', '-', $WPCF7->locale() );

		$hidden_fields = array(
			'_wpcf7' => $form_post_id,
			'_wpcf7_version' => WPCF7_VERSION,
			'_wpcf7_locale' => $WPCF7->locale(),
			'_wpcf7_unit_tag' => $unit_tag,
			'_wpcf7_posted_data_hash' => "",
			'_wpcf7_'.$form_class => ""
		);

		if ( in_the_loop() ) {
			$hidden_fields['_wpcf7_container_post'] = (int) get_the_ID();
		}

		if ( $WPCF7->nonce_is_active() && is_user_logged_in() ) {
			$hidden_fields['_wpnonce'] = wpcf7_create_nonce();
		}

		$hidden_fields_html = '';

		foreach ( $hidden_fields as $name => $value ) {
			$hidden_fields_html .= sprintf(
                '<input type="hidden" name="%1$s" value="%2$s" />',
                esc_attr( $name ), esc_attr( $value ) ) . "\n";
		}

		$html = sprintf( '<div %s>',
			wpcf7_format_atts( array(
				'role' => 'form',
				'class' => 'wpcf7',
				'id' => $unit_tag,
				( get_option( 'html_type' ) == 'text/html' ) ? 'lang' : 'xml:lang'
				=> $lang_tag,
				'dir' => wpcf7_is_rtl( $WPCF7->locale() ) ? 'rtl' : 'ltr'
			) )
		);

		$html .= $WPCF7->screen_reader_response();

		$atts = array(
			'action' => esc_url( $url ),
			'method' => 'post',
			'class' => 'wpcf7-form init',
			'enctype' => wpcf7_enctype_value( '' ),
			'autocomplete' => true,
			'novalidate' => wpcf7_support_html5() ? 'novalidate' : '',
			'data-status' => 'init',
			'locale' => $WPCF7->locale(),
		);
		$atts = wpcf7_format_atts( $atts );


		$html .= sprintf( '<form %s>', $atts ) . "\n";
		$html .= '<div style="display: none;">' . "\n" . $hidden_fields_html . '</div>' . "\n";
        $html .= $WPCF7->replace_all_form_tags();
		$html .= $WPCF7->form_response_output();
		$html .= '</form></div>';
		$html = html_entity_decode($html, ENT_COMPAT, 'UTF-8');

		wp_reset_query();

		echo '<div><div class="wpcf7-form"><div class="' . $form_class . '"><div>' . $html . "</div></div></div></div>" . $content;
	}

	public function cf7a_add_honeypot_css() {
		$form_class = sanitize_html_class($this->options['cf7a_customizations_class']);
		echo '<style>body div .wpcf7-form .'.$form_class.'{position:absolute;margin-left:-999em;}</style>';
	}

	public function cf7a_add_hidden_fields( $fields ) {

        // the base hidden field prefix
        $prefix = sanitize_html_class( $this->options['cf7a_customizations_prefix'] );

        // add the language if required
        if ( intval( $this->options['check_language'] ) == 1 ) {
            $fields[$prefix . '_language'] = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ?
                cf7a_crypt( $_SERVER['HTTP_ACCEPT_LANGUAGE'], $this->options['cf7a_cipher'] ) :
                cf7a_crypt( 'language not detected', $this->options['cf7a_cipher'] );
        }

        // add the timestamp if required
        if ( intval( $this->options['check_time'] ) == 1 ) {
            $fields[$prefix . '_timestamp'] = cf7a_crypt( time(), $this->options['cf7a_cipher'] );
        }

        // add the default hidden fields
        return array_merge( $fields, array(
            $prefix . 'version' => '1.0',
            $prefix . 'address'  => cf7a_crypt( cf7a_get_real_ip(), $this->options['cf7a_cipher'] ),
            $prefix . 'referer'  => cf7a_crypt( !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'no referer', $this->options['cf7a_cipher'] )
        ) );
    }

	public function cf7a_add_bot_fingerprinting( $fields ) {

		$prefix = sanitize_html_class($this->options['cf7a_customizations_prefix']);

		return array_merge( $fields, array(
			$prefix.'bot_fingerprint' => cf7a_crypt( time(), $this->options['cf7a_cipher'] )
		));
	}

	public function cf7a_add_bot_fingerprinting_extras( $fields ) {

		$prefix = sanitize_html_class($this->options['cf7a_customizations_prefix']);

		return array_merge( $fields, array(
			$prefix.'bot_fingerprint_extras' => false
		));
	}

	public function cf7a_append_on_submit( $fields ) {

		$prefix = sanitize_html_class($this->options['cf7a_customizations_prefix']);

		return array_merge( $fields, array(
			$prefix.'append_on_submit' => false
		));
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    0.1.0
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

		wp_register_script($this->plugin_name, plugin_dir_url( __FILE__ ) . 'src/dist/script.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script($this->plugin_name);

		wp_localize_script($this->plugin_name, "cf7a_settings", array(
			"prefix" => $this->options['cf7a_customizations_prefix'],
			"disableReload" => $this->options['cf7a_disable_reload'],
			'version' => cf7a_crypt( CF7ANTISPAM_VERSION, $this->options['cf7a_cipher'] )
		));
	}
}
