<?php
/* The above class is a PHP integration for the Contact Form 7 plugin that provides antispam
functionality. */

/**
 * Contact Form 7 Integration.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/includes
 * @author     Codekraft Studio <info@codekraft.it>
 */





/**
 * Integration class from Contact Form 7
 */

use WPCF7_Service as GlobalWPCF7_Service;

if (!class_exists('WPCF7_Service')) {
	return;
}
/**
 * This Extention represents the skeleton of the integration API
 */

class WPCF7_Antispam extends GlobalWPCF7_Service
{

	private static $instance;

	public $options;

	public static function get_instance()
	{
		if (empty(self::$instance)) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	public function __construct()
	{
		$this->options = CF7_AntiSpam::get_options();

		if (isset($_POST['cf7a_submit'])) {
			$this->options['cf7a_enable'] = !$this->options['cf7a_enable'];
			CF7_AntiSpam::update_plugin_options($this->options);
			echo '<div class="updated"><p>Settings saved.</p></div>';
		} 
		/**
		 * Call the options otherwise the plugin will break in integration
		 */

		$integration = 'cf7-antispam';
		add_action('load-' . $integration, array($this, 'wpcf7_load_integration_page'), 10, 0);
	}

	/**
	 * The function returns the title "CF7-Antispam" with the description "Contact Form 7 Antispam" in the
	 * specified language.
	 *
	 * @return string "CF7-Antispam" with the translation "Contact Form 7 Antispam".
	 */
	public function get_title()
	{
		return __('Antispam', 'Contact Form 7 Antispam');
	}

	/**
	 * The function checks if a certain option called "enabled" is set to true.
	 *
	 * @return bool value of the 'enabled' key in the  array.
	 */
	public function is_active()
	{

		return $this->options['cf7a_enable'];
	}

	/**
	 * The function "get_categories" returns an array containing the category "email_services".
	 *
	 * @return array containing the string 'email_services' is being returned.
	 */
	public function get_categories()
	{
		return array('spam_protection');
	}

	/**
	 * The function "icon" echoes an SVG icon wrapped in a div with the class "integration-icon".
	 */
	public function icon()
	{
		$allowed_html = array(
			'svg'    => array(
				'xmlns'   => true,
				'id'      => true,
				'viewbox' => true,
				'width'   => true,
				'height'  => true,
			),
			'defs'   => array(),
			'style'  => array(),
			'g'      => array(
				'id' => true,
			),
			'circle' => array(
				'cx'        => true,
				'cy'        => true,
				'r'         => true,
				'class'     => true,
				'transform' => true,
			),
			'path'   => array(
				'd'     => true,
				'class' => true,
				'fill'  => true,
			),
			'rect'   => array(
				'width'  => true,
				'height' => true,
				'x'      => true,
				'y'      => true,
				'class'  => true,
				'rx'     => true,
				'ry'     => true,
			),
		);
		echo '<div class="integration-icon">' . wp_kses(file_get_contents(CF7ANTISPAM_PLUGIN_DIR . '/assets/icon-original.svg'), $allowed_html) . '</div>';
	}

	/**
	 * The function returns a link to the WordPress plugin "cf7-antispam" on the WordPress.org website.
	 *
	 * @return string link to the WordPress plugin "cf7-antispam" on the WordPress.org website.
	 */
	public function link()
	{
		return wpcf7_link(
			'https://wordpress.org/plugins/cf7-antispam/',
			'cf7-antispam'
		);
	}

	public function admin_notice($message = '')
	{

	}

	/**
	 * The function `menu_page_url` generates a URL for a specific menu page with additional query
	 * parameters.
	 *
	 * @param args The `` parameter is an optional array that allows you to add additional query
	 * parameters to the URL. These query parameters can be used to pass data or settings to the page that
	 * the URL points to.
	 *
	 * @return string URL with query parameters.
	 */
	protected function menu_page_url($args = '')
	{
		$args = wp_parse_args($args, array());

		$url = menu_page_url('wpcf7-integration', false);
		$url = add_query_arg(array('service' => 'cf7-antispam'), $url);

		if (!empty($args)) {
			$url = add_query_arg($args, $url);
		}

		return $url;
	}


	/**
	 * The function checks if the action is "setup" and the request method is "POST", and if so, it
	 * performs some actions and redirects the user.
	 *
	 * @param action The "action" parameter is used to determine the specific action that needs to be
	 * performed. In this code snippet, if the value of the "action" parameter is "setup", it will executehttp://two.wordpress.test/wp-admin/tools.php
	 * the code inside the if statement.
	 */
	public function load($action = '')
	{
		if (!empty($_SERVER['REQUEST_METHOD'])) {
			if ('setup' == $action && 'POST' == $_SERVER['REQUEST_METHOD']) {
				//check_admin_referer('cf7-antispam-setup');

				if (!empty($_POST['reset'])) {
					$redirect_to = $this->menu_page_url('action=setup');
					wp_safe_redirect($redirect_to);
					exit();
				}
			}
		}
	}



	/**
	 * The `display` function is used to display information about the Antispam plugin and provide options for
	 * setup integration.
	 *
	 * @param string The "action" parameter is used to determine the specific action to be performed in the
	 * "display" function. It is a string that can have two possible values:
	 */
	public function display($action = '')
	{
		echo sprintf(
			'<p>%s</p>',
			esc_html__('Antispam for Contact Form 7 is a free plugin’.'
				. 'It blocks bots from flooding your mailbox, without tedious configuration and without captcha, '
				. 'CF7-AntiSpam uses different in and off page bots traps and an auto-learning mechanism based on a statistical “Bayesian” spam filter called B8'
				. 'Its recomended to install ', 'contact-form-7')
				. '<a href="https://wordpress.org/plugins/flamingo/">Flamingo</a>'
				. esc_html__(' which will provide additional controls and a dashboard widget.', 'contact-form-7')
		);


		echo sprintf(
			'<p><strong>%s</strong></p>',
			// phpcs:ignore
			wpcf7_link(
				esc_html__('https://wordpress.org/plugins/cf7-antispam/', 'contact-form-7'),
				esc_html__('CF7-Antispam', 'contact-form-7')
			)
		);

		if ($this->is_active()) {
			echo sprintf(
				'<p class="dashicons-before dashicons-yes">%s</p>',
				esc_html(__('CF7-Antispam is active on this site.', 'contact-form-7'))
			);
		}
	
		// Get the current checkbox status from the options
		$checked = $this->options['cf7a_enable'] ? 'Disable' : 'Enable';
		// Display the form
		echo '<div class="wrap">';
		echo '<h2>Checkbox Settings</h2>';
		echo '<form method="post" action="">';
		echo '<input type="submit" name="cf7a_submit" class="button button-primary" value="' . $checked . '">';
		if($this->options['cf7a_enable']  === true){
			echo '<a class="button" href="' . $_SERVER["PHP_SELF"] . '?page=cf7-antispam">Settings Page</a>'; 
		} 
		echo '</form>';
		echo '</div>';

	}

	public function display_setup()
	{
		// Check if the form is submitted
		if (isset($_POST['cf7a_submit'])) {
			// Verify nonce

				// Update the checkbox value in the options
				$this->options['cf7a_enable'] = !empty($_POST['check']) ? true : false;
				CF7_AntiSpam::update_plugin_options($this->options);
				echo '<div class="updated"><p>Settings saved.</p></div>';
				echo "<script type='text/javascript'>
        window.location=document.location.href;
        </script>";

		}
	
		// Get the current checkbox status from the options
		$checked = $this->options['cf7a_enable'] ? 'checked' : '';
	
		// Display the form
		echo '<div class="wrap">';
		echo '<h2>Checkbox Settings</h2>';
		echo '<form method="post" action="">';
		echo '<label for="check">Enable CF7 AntiSpam:</label>';
		echo '<input type="checkbox" id="check" name="check" ' . $checked . '>';
		echo '<input type="submit" name="cf7a_submit" class="button button-primary" value="Save Settings">';
		echo '</form>';
		echo '</div>';
	}



}