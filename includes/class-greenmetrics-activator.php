<?php
/**
 * Fired during plugin activation.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */

namespace GreenMetrics;

use GreenMetrics\GreenMetrics_DB_Helper;

class GreenMetrics_Activator {
	/**
	 * Create necessary database tables and set default options.
	 */
	public static function activate() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'greenmetrics_stats';

		greenmetrics_log( 'Activator - Creating tables' );

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            page_id bigint(20) NOT NULL,
            data_transfer bigint(20) NOT NULL,
            load_time float NOT NULL,
            requests int(11) NOT NULL,
            carbon_footprint float NOT NULL,
            energy_consumption float NOT NULL,
            performance_score float NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY page_id (page_id)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$result = dbDelta( $sql );

		greenmetrics_log( 'Activator - Table creation result', $result );

		// Check if table exists using DB helper
		$table_exists = GreenMetrics_DB_Helper::table_exists( $table_name );
		greenmetrics_log( 'Activator - Table exists', $table_exists ? 'Yes' : 'No' );

		if ( $table_exists ) {
			// Get table columns from DB helper
			$column_names = GreenMetrics_DB_Helper::get_table_columns( $table_name );
			greenmetrics_log( 'Activator - Table columns', implode( ', ', $column_names ) );
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

		// Store the current version in the database
		update_option( 'greenmetrics_version', GREENMETRICS_VERSION );
		greenmetrics_log( 'Activator - Version recorded', GREENMETRICS_VERSION );
	}
}
