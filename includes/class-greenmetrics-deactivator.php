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

namespace GreenMetrics;

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

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

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
		// Only clear the events that are actually scheduled by the plugin
		$event_hook = 'greenmetrics_daily_cache_refresh';
		$timestamp  = wp_next_scheduled( $event_hook );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $event_hook );
			greenmetrics_log( "Unscheduled event: $event_hook", null, 'info' );
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
