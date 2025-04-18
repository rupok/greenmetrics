<?php

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('greenmetrics_tracking_enabled');
delete_option('greenmetrics_carbon_intensity');
delete_option('greenmetrics_server_location');
delete_option('greenmetrics_data_retention_days');

// Drop the custom table
global $wpdb;
$table_name = $wpdb->prefix . 'greenmetrics_emissions';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Clear any scheduled events
wp_clear_scheduled_hook('greenmetrics_cleanup_old_data'); 