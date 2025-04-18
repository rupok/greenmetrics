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
    // Debug logging
    error_log('GreenMetrics Autoloader: Attempting to load class: ' . $class);

    // Only handle classes in our namespace
    $prefix = 'GreenMetrics\\';
    if (strpos($class, $prefix) !== 0) {
        error_log('GreenMetrics Autoloader: Class ' . $class . ' is not in our namespace');
        return;
    }

    // Remove namespace prefix to get relative class name
    $relative_class = substr($class, strlen($prefix));
    error_log('GreenMetrics Autoloader: Relative class name: ' . $relative_class);

    // Handle subnamespaces
    $parts = explode('\\', $relative_class);
    $class_name = array_pop($parts);
    $subdir = !empty($parts) ? implode('/', $parts) . '/' : '';

    // Build file path
    $file = GREENMETRICS_PLUGIN_DIR . 'includes/' . $subdir . 'class-' . strtolower($class_name) . '.php';
    error_log('GreenMetrics Autoloader: Looking for file: ' . $file);

    if (file_exists($file)) {
        error_log('GreenMetrics Autoloader: Found file: ' . $file);
        require_once $file;
        
        // Verify class was loaded
        if (class_exists($class, false)) {
            error_log('GreenMetrics Autoloader: Successfully loaded class: ' . $class);
        } else {
            error_log('GreenMetrics Autoloader: File loaded but class ' . $class . ' not found');
        }
    } else {
        error_log('GreenMetrics Autoloader: File not found: ' . $file);
    }
});

// Initialize the plugin
function greenmetrics_init() {
    // Load text domain
    load_plugin_textdomain('greenmetrics', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Initialize components
    try {
        // Require all class files directly
        error_log('GreenMetrics: Loading admin class...');
        require_once GREENMETRICS_PLUGIN_DIR . 'includes/admin/class-greenmetrics-admin.php';
        
        error_log('GreenMetrics: Loading public class...');
        require_once GREENMETRICS_PLUGIN_DIR . 'includes/class-greenmetrics-public.php';
        
        error_log('GreenMetrics: Loading tracker class...');
        require_once GREENMETRICS_PLUGIN_DIR . 'includes/class-greenmetrics-tracker.php';
        
        error_log('GreenMetrics: Loading REST API class...');
        require_once GREENMETRICS_PLUGIN_DIR . 'includes/class-greenmetrics-rest-api.php';
        
        error_log('GreenMetrics: Initializing admin...');
        $admin = new \GreenMetrics\Admin\GreenMetrics_Admin();
        
        error_log('GreenMetrics: Initializing public...');
        $public = new \GreenMetrics\GreenMetrics_Public();
        
        error_log('GreenMetrics: Initializing tracker...');
        $tracker = \GreenMetrics\GreenMetrics_Tracker::get_instance();
        
        error_log('GreenMetrics: Initializing REST API...');
        $rest_api = new \GreenMetrics\GreenMetrics_Rest_API();

        // Register REST API routes
        add_action('rest_api_init', array($rest_api, 'register_routes'));
        error_log('GreenMetrics: All components initialized successfully.');
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
    // Clean up if needed
}); 