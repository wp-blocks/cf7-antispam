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

		// TODO: change with honeypot
		if ( isset( $this->options['check_honeypot'] ) && intval($this->options['check_honeypot']) === 1 ) {
			add_filter( 'wpcf7_form_elements', array( $this,'cf7a_honeypot_add'), 10, 1  );
		}

		if ( isset( $this->options['check_bot_fingerprint'] ) && intval($this->options['check_bot_fingerprint']) === 1 ) {
			add_filter( 'wpcf7_form_hidden_fields', array( $this, 'cf7a_add_bot_fingerprinting' ), 100, 1 );
		}

		if ( isset( $this->options['check_bot_fingerprint_extras'] ) && intval($this->options['check_bot_fingerprint_extras']) === 1 ) {
			add_filter( 'wpcf7_form_hidden_fields', array( $this, 'cf7a_add_bot_fingerprinting_extras' ), 100, 1 );
		}
	}

	/**
	 * @param $form_elements
	 *
	 * @return string
	 */
	public function cf7a_honeypot_add( $form_elements ) {

		$html = new DOMDocument();
		$html->loadHTML( $form_elements, LIBXML_HTML_NODEFDTD );

		$inputs  = $html->getelementsbytagname( 'input' );
		$parents = array();
		$clones  = array();

		$input_names = sanitize_html_class($this->options['honeypot_input_names']);
		$input_class = sanitize_html_class($this->options['cf7a_customizations_class']);

		//create the style
		$style = $html->createElement('style');
		$style->textContent = '.'.$input_class.'{position:absolute;margin-left:-999em}';
		$html->appendChild($style);

		// get the inputs data
		if ( $inputs && $inputs->length > 0 ) {
			// to be on the save side it can be a good idea to store the name of the input (to avoid duplicates)
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

	public function cf7a_add_hidden_fields( $fields ) {

		// the base hidden field prefix
		$prefix = sanitize_html_class($this->options['cf7a_customizations_prefix']);

		// add the timestamp id required
		$fields = intval($this->options['check_time']) ?
			array_merge( $fields, array( $prefix.'_timestamp' => cf7a_crypt(time()) ) ) :
			$fields;

		// add the default hidden fields
		return array_merge( $fields, array(
			$prefix.'_version' => cf7a_crypt(CF7ANTISPAM_VERSION),
			$prefix.'address' => cf7a_crypt(cf7a_get_real_ip())
		));
	}

	public function cf7a_add_bot_fingerprinting( $fields ) {

		$class = sanitize_html_class($this->options['cf7a_customizations_prefix']);

		return array_merge( $fields, array(
			$class.'bot_fingerprint' => wp_hash_password(time())
		));
	}

	public function cf7a_add_bot_fingerprinting_extras( $fields ) {

		$class = sanitize_html_class($this->options['cf7a_customizations_prefix']);

		return array_merge( $fields, array(
			$class.'bot_fingerprint_extras' => false
		));
	}

	public function append_on_submit( $fields ) {

		$class = sanitize_html_class($this->options['append_on_submit']);

		return array_merge( $fields, array(
			$class.'append_on_submit' => false
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

		wp_register_script($this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/script.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script($this->plugin_name);

		wp_localize_script($this->plugin_name, "cf7a_settings", array(
			"prefix" => $this->options['cf7a_customizations_prefix']
		));
	}
}