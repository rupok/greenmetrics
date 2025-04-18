<?php
/**
 * Fired during plugin activation.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */

namespace GreenMetrics;

class GreenMetrics_Activator {
    /**
     * Create necessary database tables and set default options.
     */
    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'greenmetrics_stats';
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
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

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Set default options
        $default_options = array(
            'enable_badge' => true,
            'badge_style' => 'light',
            'badge_placement' => 'bottom-right',
            'tracking_enabled' => true,
            'carbon_intensity' => 0.475, // Default carbon intensity in gCO2/kWh
            'energy_per_byte' => 0.000000000072 // Default energy per byte in kWh
        );
        add_option('greenmetrics_settings', $default_options);
    }
} 