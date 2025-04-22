<?php
/**
 * Fired during plugin deactivation.
 *
 * @link       https://example.com/greenmetrics
 * @since      1.0.0
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 * @author     Your Name <email@example.com>
 */
namespace GreenMetrics;

class GreenMetrics_Deactivator {
	/**
	 * Handle plugin deactivation tasks.
	 *
	 * Performs necessary cleanup when the plugin is deactivated:
	 * - Clear scheduled events
	 * - Flush rewrite rules
	 * - Clean up transients
	 */
	public static function deactivate() {
		// Log deactivation process start
		greenmetrics_log( 'Plugin deactivation started', null, 'info' );

		// Remove scheduled events
		self::clear_scheduled_events();

		// Clear any transients
		self::clear_transients();

		// Flush rewrite rules
		flush_rewrite_rules();

		// Log completed deactivation
		greenmetrics_log( 'Plugin deactivation completed', null, 'info' );
	}

	/**
	 * Clear all scheduled events created by this plugin.
	 */
	private static function clear_scheduled_events() {
		// Get timestamp of next scheduled event if it exists
		$timestamp = wp_next_scheduled( 'greenmetrics_daily_cleanup' );

		// If a timestamp was found, unschedule the event
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'greenmetrics_daily_cleanup' );
			greenmetrics_log( 'Unscheduled daily cleanup event', null, 'info' );
		}

		// Do the same for any other scheduled events
		$scheduled_events = array(
			'greenmetrics_weekly_report',
			'greenmetrics_monthly_aggregate',
		);

		foreach ( $scheduled_events as $event ) {
			$timestamp = wp_next_scheduled( $event );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $event );
				greenmetrics_log( "Unscheduled event: $event", null, 'info' );
			}
		}
	}

	/**
	 * Clear any transients created by this plugin.
	 */
	private static function clear_transients() {
		global $wpdb;

		// Delete all transients with our prefix
		$sql    = $wpdb->prepare(
			"DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
			'%_transient_greenmetrics_%',
			'%_transient_timeout_greenmetrics_%'
		);
		$result = $wpdb->query( $sql );

		greenmetrics_log( 'Cleared plugin transients', $result, 'info' );
	}
}
