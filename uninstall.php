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

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options
delete_option( 'greenmetrics_settings' );
delete_option( 'greenmetrics_table_columns' );
delete_option( 'greenmetrics_version' );

// Delete any transients
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Necessary cleanup during uninstallation
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
		'%_transient_greenmetrics_%',
		'%_transient_timeout_greenmetrics_%'
	)
);

// Drop the stats tables
$tables_to_drop = array(
	$wpdb->prefix . 'greenmetrics_stats',
	$wpdb->prefix . 'greenmetrics_aggregated_stats',
	$wpdb->prefix . 'greenmetrics_email_reports'
);

foreach ( $tables_to_drop as $table_name ) {
	// Escape table name for safety
	$table_name_escaped = esc_sql( $table_name );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Necessary schema change during uninstallation
	$wpdb->query( "DROP TABLE IF EXISTS `{$table_name_escaped}`" );
}

// Clear any scheduled events
$events = array(
	'greenmetrics_daily_cache_refresh',
);

foreach ( $events as $event ) {
	wp_clear_scheduled_hook( $event );
}

// Remove cache
wp_cache_flush();

// Flush rewrite rules
flush_rewrite_rules();
