<?php
// this is the plugin configuration page
?>
<div class="wrap">
	<div class="card">
		<h1><span class="dashicons dashicons-shield-alt"></span>Contact Form 7 - AntiSpam</h1>
		<div class="inside">
			<form method="post" action="options.php" id="cf7a_settings">
        <div class="banner dismissable" style="background-color: #f3f3f3; border: 1px solid #ddd; padding: 10px; margin: 10px 0">
          <h3><span class="dashicons dashicons-editor-help" aria-hidden="true"></span> <?php echo esc_html( __( "Before you cry over spilt mail&#8230;", 'contact-form-7' ) ); ?></h3>
          <p><?php echo esc_html( __( "Contact Form 7 doesn&#8217;t store submitted messages anywhere. Therefore, you may lose important messages forever if your mail server has issues or you make a mistake in mail configuration.", 'contact-form-7' ) ); ?></p>
          <p><?php
	          echo sprintf(
	          /* translators: %s: link labeled 'Flamingo' */
		          esc_html( __( 'Install a message storage plugin before this happens to you. %s saves all messages through contact forms into the database. Flamingo is a free WordPress plugin created by the same author as Contact Form 7.', 'contact-form-7' ) ),
		          wpcf7_link(
			          __( 'https://contactform7.com/save-submitted-messages-with-flamingo/', 'contact-form-7' ),
			          __( 'Flamingo', 'contact-form-7' )
		          )
	          );
	          ?></p>
          <b><p><?php echo esc_html( __( "And don't forget to add also flamingo_message: \"[your-message]\" to your message to get the full functionality of this plugin", 'cf7-antispam' ) ); ?></p></b>
        </div>
			<?php
				// This prints out all hidden setting fields
				settings_fields( 'cf7_antispam_options' );
				do_settings_sections( 'cf7a-settings' ) ;

				submit_button();
			?>
			</form>
		</div>
	</div>
  <div class="card">
    <h4><?php _e('IP Blacklist') ?></h4>
    <p><?php CF7_AntiSpam_Admin_Tools::cf7a_get_blacklisted_table(); ?></p>
  </div>



<?php if (WP_DEBUG) {
	echo '<div class="card">';
	echo '<h3>'.__('Debug info').'</h3>';
	echo '<p>'.__('If you see this box it is because wp_debug is active!').'</p>';

	echo '<pre>' . htmlentities(print_r(get_option( 'cf7a_options' ), true)) . '</pre>';
	echo '</div>';
}


echo '</div>';