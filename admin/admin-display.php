<?php
// this is the plugin configuration page
$cf7a_nonce = CF7_AntiSpam_Admin::cf7a_get_nonce();

$dismissable_banner_class = 'welcome-panel banner dismissable';

$vers = (array) get_user_meta( get_current_user_id(),
	'cf7a_hide_welcome_panel_on', true );

if ( $vers  ) {	$dismissable_banner_class .= ' hidden'; }

?>
<div class="wrap">
  <div class="card">
    <h1><span class="dashicons dashicons-shield-alt"></span>Contact Form 7 - AntiSpam</h1>
    <div class="inside">
      <form method="post" action="<?php echo esc_url( menu_page_url( 'cf7-antispam', false ) ); ?>" id="cf7a_settings">
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
			<?php
			// This prints out all hidden setting fields
			settings_fields( 'cf7_antispam_options' );
			do_settings_sections( 'cf7a-settings' );

			submit_button();
			?>
      </form>
  </div>
</div>

<?php
// prints the blacklisted ip, the rating and some informations
CF7_AntiSpam_Admin_Tools::cf7a_get_blacklisted_table( $cf7a_nonce );

// returns the plugins debug informations
CF7_AntiSpam_Admin_Tools::cf7a_get_debug_info( $cf7a_nonce );
?>

</div>