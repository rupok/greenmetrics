<?php
/**
 * Plugin Name: GreenMetrics
 * Plugin URI: https://getgreenmetrics.com
 * Description: Measure your website's environmental impact with carbon footprint, energy consumption and performance metrics for a more sustainable web.
 * Version: 1.0.0
 * Author: GreenMetrics Team
 * Author URI: https://getgreenmetrics.com
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

// Define debug mode - should be set to false in production
define('GREENMETRICS_DEBUG', true); // Temporarily enabled for debugging

/**
 * Helper function for logging debug messages.
 * Only logs if GREENMETRICS_DEBUG is enabled.
 * 
 * @param string $message The message to log
 * @param mixed $data Optional data to include in the log
 * @param string $level The log level ('info', 'warning', 'error')
 * @return void
 */
function greenmetrics_log($message, $data = null, $level = 'info') {
    if (!defined('GREENMETRICS_DEBUG') || !GREENMETRICS_DEBUG) {
        return;
    }
    
    $log_message = date('[Y-m-d H:i:s]') . " GreenMetrics: $message";
    
    if (null !== $data) {
        // Only do print_r for arrays and objects to improve performance
        if (is_array($data) || is_object($data)) {
            $log_message .= ' - ' . print_r($data, true);
        } else {
            $log_message .= ' - ' . $data;
        }
    }
    
    // Write to plugin's own log file that's easily accessible
    $log_file = GREENMETRICS_PLUGIN_DIR . 'debug.log';
    file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
    
    // Also use error_log for standard WordPress logging
    error_log($log_message);
}

// Autoloader
spl_autoload_register(function ($class) {
    // Only handle classes in our namespace
    $prefix = 'GreenMetrics\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    // Remove namespace prefix to get relative class name
    $relative_class = substr($class, strlen($prefix));

    // Handle subnamespaces
    $parts = explode('\\', $relative_class);
    $class_name = array_pop($parts);
    $subdir = !empty($parts) ? implode('/', $parts) . '/' : '';

    // Build file path
    $file = GREENMETRICS_PLUGIN_DIR . 'includes/' . $subdir . 'class-' . strtolower($class_name) . '.php';
    
    greenmetrics_log('Autoloader: Looking for file', $file);

    if (file_exists($file)) {
        require_once $file;
        
        // Verify class was loaded in debug mode only
        if (!class_exists($class, false)) {
            greenmetrics_log('Autoloader: File loaded but class not found', $class, 'error');
        }
    } else {
        greenmetrics_log('Autoloader: File not found', $file, 'warning');
    }
});

// Initialize the plugin
function greenmetrics_init() {
    // Load text domain
    load_plugin_textdomain('greenmetrics', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // TEMPORARY DEBUG - Reset settings to default
    if (defined('GREENMETRICS_DEBUG') && GREENMETRICS_DEBUG) {
        // Clear any potentially cached version of the settings
        wp_cache_delete('greenmetrics_settings', 'options');
        
        // Get current settings
        $settings = get_option('greenmetrics_settings', array());
        greenmetrics_log('Plugin Initialization - Current settings (before reset)', $settings);
        
        // Force deletion and recreation of option (TEMPORARY for debugging)
        // Uncomment this block to force reset the option
        /*
        delete_option('greenmetrics_settings');
        $default_settings = array(
            'tracking_enabled' => 1,
            'enable_badge' => 1
        );
        update_option('greenmetrics_settings', $default_settings);
        greenmetrics_log('Plugin Initialization - Settings reset to defaults', $default_settings);
        */
        
        // Check settings again
        $settings = get_option('greenmetrics_settings', array());
        greenmetrics_log('Plugin Initialization - Current settings', $settings);
    }

    // Initialize components
    try {
        // Require all class files directly
        require_once GREENMETRICS_PLUGIN_DIR . 'includes/class-greenmetrics-settings-manager.php';
        require_once GREENMETRICS_PLUGIN_DIR . 'includes/admin/class-greenmetrics-admin.php';
        require_once GREENMETRICS_PLUGIN_DIR . 'includes/class-greenmetrics-public.php';
        require_once GREENMETRICS_PLUGIN_DIR . 'includes/class-greenmetrics-tracker.php';
        require_once GREENMETRICS_PLUGIN_DIR . 'includes/class-greenmetrics-rest-api.php';
        
        // Initialize components
        $admin = new \GreenMetrics\Admin\GreenMetrics_Admin();
        $public = new \GreenMetrics\GreenMetrics_Public();
        $tracker = \GreenMetrics\GreenMetrics_Tracker::get_instance();
        $rest_api = new \GreenMetrics\GreenMetrics_Rest_API();

        // Register REST API routes
        add_action('rest_api_init', array($rest_api, 'register_routes'));
        
        greenmetrics_log('All components initialized successfully');
    } catch (Exception $e) {
        // Log error and show admin notice
        error_log('GreenMetrics Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        add_action('admin_notices', function() use ($e) {
            ?>
            <div class="notice notice-error">
                <p><?php echo esc_html('GreenMetrics Error: ' . $e->getMessage()); ?></p>
            </div>
            <?php
        });
    }
}
add_action('plugins_loaded', 'greenmetrics_init');

// Activation hook
register_activation_hook(__FILE__, function() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-greenmetrics-activator.php';
    GreenMetrics\GreenMetrics_Activator::activate();
});

// Also create table if it doesn't exist (for existing installations)
add_action('plugins_loaded', function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'greenmetrics_stats';
    
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        require_once plugin_dir_path(__FILE__) . 'includes/class-greenmetrics-activator.php';
        GreenMetrics\GreenMetrics_Activator::activate();
    }
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-greenmetrics-deactivator.php';
    GreenMetrics\GreenMetrics_Deactivator::deactivate();
}); 