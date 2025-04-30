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

		// Create the stats table using the DB Helper with admin notices enabled
		$result = GreenMetrics_DB_Helper::create_stats_table( true );

		// Check if table creation was successful
		if ( is_wp_error( $result ) || ( is_array( $result ) && empty( $result ) ) ) {
			// Table creation failed, log the error
			greenmetrics_log( 'Activator - Table creation failed', $result, 'error' );

			// Add a persistent admin notice that won't be dismissed automatically
			GreenMetrics_Error_Handler::admin_notice(
				__( 'GreenMetrics: Database tables could not be created. The plugin may not function correctly. Please check your server error logs or contact your hosting provider.', 'greenmetrics' ),
				'error',
				false
			);

			// Store the error in an option for later reference
			update_option( 'greenmetrics_db_error', is_wp_error( $result ) ? $result->get_error_message() : 'Unknown error' );

			// Continue with activation to set default options, but tracking will be disabled
			$default_options = self::get_default_options();
			$default_options['tracking_enabled'] = 0; // Disable tracking if table creation failed
			add_option( 'greenmetrics_settings', $default_options );

			return;
		}

		// Check if table exists using DB helper (force a fresh check)
		$table_exists = GreenMetrics_DB_Helper::table_exists( $table_name, true );
		greenmetrics_log( 'Activator - Table exists', $table_exists ? 'Yes' : 'No' );

		if ( $table_exists ) {
			// Force refresh column information and store it in the persistent cache
			// This will reduce the need for repeated introspection during normal operation
			$column_names = GreenMetrics_DB_Helper::get_table_columns( $table_name, true );
			greenmetrics_log( 'Activator - Table columns cached for future use', implode( ', ', $column_names ) );

			// Clear any previous database errors
			delete_option( 'greenmetrics_db_error' );
		} else {
			// This should not happen since we already checked above, but just in case
			greenmetrics_log( 'Activator - Table does not exist after creation check', null, 'error' );

			// Add a persistent admin notice
			GreenMetrics_Error_Handler::admin_notice(
				__( 'GreenMetrics: Database tables could not be verified. The plugin may not function correctly.', 'greenmetrics' ),
				'error',
				false
			);
		}

		// Set default options
		$default_options = self::get_default_options();

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

		// Initialize data manager to create aggregated table
		$data_manager = GreenMetrics_Data_Manager::get_instance();
		greenmetrics_log( 'Activator - Initialized data manager' );

		// Schedule data management tasks
		GreenMetrics_Data_Manager::schedule_data_management();
		greenmetrics_log( 'Activator - Scheduled data management tasks' );

		// Store the current version in the database
		update_option( 'greenmetrics_version', GREENMETRICS_VERSION );
		greenmetrics_log( 'Activator - Version recorded', GREENMETRICS_VERSION );
	}

	/**
	 * Get default plugin options.
	 *
	 * @return array Default options.
	 */
	public static function get_default_options() {
		return array(
			'carbon_intensity' => 0.475,         // Default carbon intensity factor (kg CO2/kWh)
			'energy_per_byte'  => 0.000000000072, // Default energy per byte (kWh/byte)
			'tracking_enabled' => 1,             // Tracking enabled by default
			'enable_badge'     => 0,             // Badge disabled by default
			'badge_position'   => 'bottom-right', // Default badge position
			'badge_theme'      => 'light',       // Default badge theme
			'badge_size'       => 'medium',      // Default badge size
		);
	}
}
