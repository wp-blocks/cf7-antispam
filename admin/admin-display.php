<?php
// this is the plugin configuration page
?>
<div class="wrap">
	<div class="card">
		<h1><span class="dashicons dashicons-shield-alt"></span>Contact Form 7 - AntiSpam</h1>
		<div class="inside">
			<form method="post" action="options.php" id="cf7a_settings">
			<?php
				// This prints out all hidden setting fields
				settings_fields( 'cf7_antispam_options' );
				do_settings_sections( 'cf7a-settings' ) ;

				submit_button();
			?>
			</form>
		</div>
	</div>

<?php if (WP_DEBUG) {
	echo '<div class="card">';
	echo '<h3>'.__('Debug info').'</h3>';
	echo '<p>'.__('If you see this box it is because wp_debug is active!').'</p>';

	echo '<pre>' . htmlentities(print_r(get_option( 'cf7a_options' ), true)) . '</pre>';
	echo '</div>';
}
echo '</div>';