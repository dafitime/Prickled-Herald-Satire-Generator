<?php
// uninstall.php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete options
delete_option('ph_gemini_api_key');
delete_option('ph_training_text');
delete_option('ph_global_last_post_info');
delete_option('ph_satire_version');

// Delete transients
global $wpdb;
$wpdb->query(
    "DELETE FROM $wpdb->options 
     WHERE option_name LIKE '_transient_ph_%' 
     OR option_name LIKE '_transient_timeout_ph_%'"
);

// Delete post meta
$wpdb->query(
    "DELETE FROM $wpdb->postmeta 
     WHERE meta_key LIKE '_ph_%'"
);

// Drop custom table
$table_name = $wpdb->prefix . 'ph_satire_history';
$wpdb->query("DROP TABLE IF EXISTS $table_name");