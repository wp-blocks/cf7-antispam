<?php

class CF7_AntiSpam_Admin_Display {

	/**
	 * The options of this plugin.
	 *
	 * @since    0.1.0
	 * @access   public
	 * @var      array    $options    options of this plugin.
	 */
	public $options;

	/**
	 * It adds actions to the `cf7a_dashboard` hook
	 */
	public function display_dashboard() {
		add_action( 'cf7a_dashboard', array( $this, 'cf7a_display_header' ) );
		add_action( 'cf7a_dashboard', array( $this, 'cf7a_display_content' ), 22 );
		add_action( 'cf7a_dashboard', array( $this, 'cf7a_display_footer' ), 23 );

		add_action( 'cf7a_dashboard', array( $this, 'cf7a_display_debug' ), 30 );

		do_action( 'cf7a_dashboard' );
	}


	/**
	 * It displays the header for the widget.
	 */
	function cf7a_display_header() {
		$html  = '<div class="wrap"><div class="cf7-antispam">';
		$html .= '<h1><span class="dashicons dashicons-shield-alt"></span>Contact Form 7 - AntiSpam</h1>';
		echo $html;
	}

	/**
	 * It displays the content of the widget
	 */
	function cf7a_display_content() {
		CF7_AntiSpam_Admin_Tools::cf7a_handle_actions();
		$dismissible_banner_class = get_user_meta( get_current_user_id(), 'cf7a_hide_welcome_panel_on', true ) ? ' hidden' : '';
		?>
	<div id="welcome-panel" class="card banner dismissible<?php echo $dismissible_banner_class; ?>">
			<div class="inside">
				<a class="welcome-panel-close" href="<?php echo esc_url( add_query_arg( 'action', 'dismiss-banner', menu_page_url( 'cf7-antispam', false ) ) ); ?>"><?php echo esc_html( __( 'Dismiss', 'contact-form-7' ) ); ?></a>
				<?php if ( ! is_plugin_active( 'flamingo/flamingo.php' ) ) { ?>
				<h3><span class="dashicons dashicons-editor-help" aria-hidden="true"></span> <?php echo esc_html( __( 'Before you cry over spilt mail&#8230;', 'contact-form-7' ) ); ?></h3>
				<p><?php echo esc_html( __( 'Contact Form 7 doesn&#8217;t store submitted messages anywhere. Therefore, you may lose important messages forever if your mail server has issues or you make a mistake in mail configuration.', 'contact-form-7' ) ); ?></p>
				<p>
					<?php
					echo sprintf( /* translators: %s: link labeled 'Flamingo' */
						esc_html( __( 'Install a message storage plugin before this happens to you. %s saves all messages through contact forms into the database. Flamingo is a free WordPress plugin created by the same author as Contact Form 7.', 'contact-form-7' ) ),
						wpcf7_link( __( 'https://contactform7.com/save-submitted-messages-with-flamingo/', 'contact-form-7' ), __( 'Flamingo', 'contact-form-7' ) )
					);
					?>
				</p>
				<hr />
				<?php } ?>
				<h3 class="blink"><span class="dashicons dashicons-megaphone" aria-hidden="true"></span> <?php echo esc_html( __( "PLEASE don't forget to add ", 'cf7-antispam' ) ); ?></h3>
				<b><code class="blink"><?php echo esc_html( __( 'flamingo_message: "[your-message]" ', 'cf7-antispam' ) ); ?></code></b>
				<p>
					<?php echo esc_html( __( 'Please replace ', 'cf7-antispam' ) ); ?>
					<b><?php echo esc_attr( __( '[your-message]', 'cf7-antispam' ) ); ?></b>
					<?php echo esc_html( __( ' with the message field used in your form because that is the field scanned with b8. You need add this string to each form ', 'cf7-antispam' ) ); ?>
					<a href='https://contactform7.com/additional-settings/'><?php echo esc_attr( __( 'additional settings section', 'cf7-antispam' ) ); ?></a>
					<?php echo esc_html( __( 'to enable the most advanced protection we can offer! Thank you!', 'cf7-antispam' ) ); ?>
				</p>
			</div>
		</div>

	<div class="card main-options">

	  <h3>Options</h3>
	  <form method="post" action="options.php" id="cf7a_settings">
		  <?php

			// This prints out all hidden setting fields
			settings_fields( 'cf7_antispam_options' );
			do_settings_sections( 'cf7a-settings' );

			submit_button();

			?>
	  </form>

	</div>
		<?php
	}

	/**
	 * It displays the footer for the widget.
	 */
	function cf7a_display_footer() {
		?>
		</div></div>
		<?php
	}

	/**
	 * It prints the blacklisted ip, the rating and some information, returns the plugins debug information and the
	 * plugins debug information
	 */
	function cf7a_display_debug() {

		$tools = new CF7_AntiSpam_Admin_Tools();

		// prints the blacklisted ip, the rating and some information
		$tools->cf7a_get_blacklisted_table();

		// returns the plugins debug information
		$tools->cf7a_advanced_settings();

		// returns the plugins debug information
		$tools->cf7a_get_debug_info();
	}
}
