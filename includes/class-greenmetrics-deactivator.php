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
     * Clean up plugin data on deactivation.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Clear any scheduled events
        wp_clear_scheduled_hook('greenmetrics_cleanup_old_data');
        
        // Note: We don't delete the database table or options here
        // as that would cause data loss. Instead, we provide an uninstall.php
        // file for complete removal if needed.
        flush_rewrite_rules();
    }
} 