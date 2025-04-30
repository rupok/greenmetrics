<?php
/**
 * Fired during plugin activation.
 *
 * @link       https://example.com/greenmetrics
 * @since      1.0.0
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */

namespace GreenMetrics;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use GreenMetrics\GreenMetrics_DB_Helper;

class GreenMetrics_Activator {
	/**
	 * Create necessary database tables and set default options.
	 */
	public static function activate() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'greenmetrics_stats';

		greenmetrics_log( 'Activator - Creating tables' );

		// Create the stats table using the DB Helper
		$result = GreenMetrics_DB_Helper::create_stats_table();

		// Check if table exists using DB helper (force a fresh check)
		$table_exists = GreenMetrics_DB_Helper::table_exists( $table_name, true );
		greenmetrics_log( 'Activator - Table exists', $table_exists ? 'Yes' : 'No' );

		if ( $table_exists ) {
			// Force refresh column information and store it in the persistent cache
			// This will reduce the need for repeated introspection during normal operation
			$column_names = GreenMetrics_DB_Helper::get_table_columns( $table_name, true );
			greenmetrics_log( 'Activator - Table columns cached for future use', implode( ', ', $column_names ) );
		}

		// Set default options
		$default_options = array(
			'carbon_intensity' => 0.475,         // Default carbon intensity factor (kg CO2/kWh)
			'energy_per_byte'  => 0.000000000072, // Default energy per byte (kWh/byte)
			'tracking_enabled' => 1,             // Tracking enabled by default
			'enable_badge'     => 0,                 // Badge disabled by default
			'badge_position'   => 'bottom-right',  // Default badge position
			'badge_theme'      => 'light',            // Default badge theme
			'badge_size'       => 'medium',             // Default badge size
		);

		$existing_options = get_option( 'greenmetrics_settings', array() );

		if ( empty( $existing_options ) ) {
			greenmetrics_log( 'Activator - Setting default options' );
			add_option( 'greenmetrics_settings', $default_options );
		} else {
			greenmetrics_log( 'Activator - Options already exist, not overwriting' );
		}

		// Schedule the daily cache refresh
		GreenMetrics_Tracker::schedule_daily_cache_refresh();
		greenmetrics_log( 'Activator - Scheduled daily cache refresh' );

		// Store the current version in the database
		update_option( 'greenmetrics_version', GREENMETRICS_VERSION );
		greenmetrics_log( 'Activator - Version recorded', GREENMETRICS_VERSION );
	}
}
