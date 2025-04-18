<?php
/**
 * Plugin Name: GreenMetrics
 * Plugin URI: https://github.com/yourusername/greenmetrics
 * Description: Track and reduce your website's environmental impact by monitoring carbon emissions and data transfer.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: greenmetrics
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('GREENMETRICS_VERSION', '1.0.0');
define('GREENMETRICS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GREENMETRICS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'GreenMetrics\\';
    $base_dir = GREENMETRICS_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Initialize the plugin
function greenmetrics_init() {
    // Load text domain
    load_plugin_textdomain('greenmetrics', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Initialize components
    $admin = new \GreenMetrics\Admin\GreenMetrics_Admin();
    $public = new \GreenMetrics\GreenMetrics_Public();
    $tracker = \GreenMetrics\GreenMetrics_Tracker::get_instance();
    $rest_api = new \GreenMetrics\GreenMetrics_Rest_API();

    // Register REST API routes
    add_action('rest_api_init', array($rest_api, 'register_routes'));
}
add_action('plugins_loaded', 'greenmetrics_init');

// Activation hook
register_activation_hook(__FILE__, function() {
    // Create necessary database tables
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}greenmetrics_metrics (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        page_id bigint(20) NOT NULL,
        data_transfer float NOT NULL,
        load_time float NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY page_id (page_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Set default options
    add_option('greenmetrics_settings', array(
        'tracking_enabled' => 1,
        'enable_badge' => 1
    ));
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Clean up if needed
}); 