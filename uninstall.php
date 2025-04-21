<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * @package    GreenMetrics
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('greenmetrics_settings');

// Delete any transients
global $wpdb;
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_greenmetrics_%' OR option_name LIKE '%_transient_timeout_greenmetrics_%'");

// Drop the stats table
$table_name = $wpdb->prefix . 'greenmetrics_stats';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Clear any scheduled events
$events = [
    'greenmetrics_daily_cleanup',
    'greenmetrics_weekly_report',
    'greenmetrics_monthly_aggregate'
];

foreach ($events as $event) {
    wp_clear_scheduled_hook($event);
}

// Remove cache
wp_cache_flush();

// Flush rewrite rules
flush_rewrite_rules(); 