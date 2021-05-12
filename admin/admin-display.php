<?php

echo '<div class="wrap"><div class="card"><h1><span class="dashicons dashicons-shield-alt"></span>Contact Form 7 - AntiSpam</h1>';
echo '<div class="inside"><form method="post" action="options.php">';

// This prints out all hidden setting fields
settings_fields( 'cf7_antispam_options' );
do_settings_sections( 'cf7a-settings' ) ;

submit_button();
echo '</form></div></div></div>';

print_r("options<br/>");
var_dump(get_option( 'cf7a_options' ));
