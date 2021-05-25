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

  public function display_dashboard() {
	  add_action( 'cf7a_dashboard', array( $this, 'cf7a_display_header' ), 20 );
	  add_action( 'cf7a_dashboard', array( $this, 'cf7a_display_notices' ), 21 );
	  add_action( 'cf7a_dashboard', array( $this, 'cf7a_display_content' ), 22 );
	  add_action( 'cf7a_dashboard', array( $this, 'cf7a_display_footer' ), 23 );

	  add_action( 'cf7a_dashboard', array( $this, 'cf7a_display_debug' ), 30 );

	  do_action( 'cf7a_dashboard' );
  }


	function cf7a_display_header(){
	  $html  = '<div class="wrap"><div class="cf7-antispam">';
	  $html .= '<h1><span class="dashicons dashicons-shield-alt"></span>Contact Form 7 - AntiSpam</h1>';
	  echo $html;
  }


	function cf7a_display_notices() {

    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : false;

		// admin notices
		if ($action === 'success') {
				echo CF7_AntiSpam_Admin_Tools::cf7a_push_notice(__('Success', 'cf7-antispam'), 'success' );
		} else if ($action === 'fail') {
				echo CF7_AntiSpam_Admin_Tools::cf7a_push_notice(__('Error', 'cf7-antispam'), 'error' );
		}
	}

	function cf7a_display_content() {
    $dismissable_banner_class = 'welcome-panel banner dismissable';

    $vers = (array) get_user_meta( get_current_user_id(),
      'cf7a_hide_welcome_panel_on', true );

    if ( $vers  ) {	$dismissable_banner_class .= ' hidden'; }

    ?>
    <div class="card main-options">

      <div class="<?php echo $dismissable_banner_class ?>" style="background-color: #f3f3f3; border: 1px solid #ddd; padding: 10px; margin: 10px 0">
        <a class="welcome-panel-close" href="<?php echo esc_url( menu_page_url( 'cf7-antispam', false ) ); ?>&dismiss-banner=1"><?php echo esc_html( __( 'Dismiss', 'contact-form-7' ) ); ?></a>
        <h3><span class="dashicons dashicons-editor-help" aria-hidden="true"></span> <?php echo esc_html( __( "Before you cry over spilt mail&#8230;", 'contact-form-7' ) ); ?></h3>
        <p><?php echo esc_html( __( "Contact Form 7 doesn&#8217;t store submitted messages anywhere. Therefore, you may lose important messages forever if your mail server has issues or you make a mistake in mail configuration.", 'contact-form-7' ) ); ?></p>
        <p><?php echo sprintf( /* translators: %s: link labeled 'Flamingo' */
            esc_html( __( 'Install a message storage plugin before this happens to you. %s saves all messages through contact forms into the database. Flamingo is a free WordPress plugin created by the same author as Contact Form 7.', 'contact-form-7' ) ), wpcf7_link( __( 'https://contactform7.com/save-submitted-messages-with-flamingo/', 'contact-form-7' ), __( 'Flamingo', 'contact-form-7' ) ) );
          ?></p>
        <p><?php echo esc_html( __( "And don't forget to add also ", 'cf7-antispam' ) ); ?></p>
        <b><code><?php echo esc_html( __( "flamingo_message: \"[your-message]\" ", 'cf7-antispam' ) ); ?></code></b>
        <p><?php echo esc_html( __( "to your message to get the full functionality of this plugin", 'cf7-antispam' ) ); ?></p>
      </div>

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


	function cf7a_display_debug() {

	  $tools = new CF7_AntiSpam_Admin_Tools();

	  // prints the blacklisted ip, the rating and some informations
	  $tools->cf7a_get_blacklisted_table();

	  // returns the plugins debug informations
	  $tools->cf7a_get_debug_info();
	}



	function cf7a_display_footer() {
		?>
      </div></div>
		<?php
	}


}
