<?php

function my_simple_plugin_menu() {
    add_menu_page(
        'Simple Plugin Settings',
        'Simple Plugin',
        'manage_options',
        'my-simple-plugin',
        'my_simple_plugin_page'
    );
}
add_action('admin_menu', 'my_simple_plugin_menu');

// Render the plugin settings page
function my_simple_plugin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    // Check for and process GET request
    if (isset($_GET['toggle_option']) && wp_verify_nonce($_GET['_wpnonce'], 'toggle_option_nonce')) {
        $enabled = get_option('my_simple_plugin_enabled', 0);
        update_option('my_simple_plugin_enabled', $enabled ? 0 : 1);
    }

    // Retrieve the current option value
    $enabled = get_option('my_simple_plugin_enabled', 0);

    ?>
    <div class="wrap">
        <h2>My Simple Plugin Settings</h2>
        <p>Option Status: <?php echo $enabled ? 'Enabled' : 'Disabled'; ?></p>
        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=my-simple-plugin&toggle_option=true'), 'toggle_option_nonce'); ?>">
            <?php echo $enabled ? 'Disable Option' : 'Enable Option'; ?>
        </a>
    </div>
    <?php
}