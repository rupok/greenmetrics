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
        $charset_collate = $wpdb->get_charset_collate();

        // Create stats table
        $table_name = $wpdb->prefix . 'greenmetrics_stats';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            page_id bigint(20) NOT NULL,
            data_transfer bigint(20) NOT NULL,
            load_time int(11) NOT NULL,
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
            'tracking_enabled' => true
        );
        add_option('greenmetrics_settings', $default_options);
    }
} 