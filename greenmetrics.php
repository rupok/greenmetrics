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
 * Requires at least: 5.5
 * Requires PHP: 7.2
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define plugin constants
define( 'GREENMETRICS_VERSION', '1.0.0' );
define( 'GREENMETRICS_PLUGIN_FILE', __FILE__ );
define( 'GREENMETRICS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GREENMETRICS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GREENMETRICS_DEBUG', false ); // Disabled by default for production environments

// Define a constant that can be used for compile-time optimizations
// When true, all logging calls will be completely bypassed
define( 'GREENMETRICS_NO_DEBUG', ! GREENMETRICS_DEBUG );

/**
 * Helper function for logging debug messages.
 * Only logs if GREENMETRICS_DEBUG is enabled and uses WordPress native logging.
 *
 * In production builds, this function does nothing and incurs no performance penalty
 * because the constant GREENMETRICS_NO_DEBUG will be evaluated at "compile time".
 *
 * @param string $message The message to log
 * @param mixed  $data Optional data to include in the log
 * @param string $level The log level ('info', 'warning', 'error')
 * @return void
 */
function greenmetrics_log( $message, $data = null, $level = 'info' ) {
	// This IF statement is evaluated at "compile time" by PHP's optimizer
	// When GREENMETRICS_NO_DEBUG is true, the entire function body is skipped
	if ( GREENMETRICS_NO_DEBUG ) {
		return;
	}

	// Only log when WordPress debugging is enabled
	if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
		return;
	}

	// The code below only runs when debugging is enabled
	$log_message = gmdate( '[Y-m-d H:i:s]' ) . " GreenMetrics ($level): $message";

	if ( null !== $data ) {
		// Format the data as a simple string without using debug functions
		if ( is_array( $data ) || is_object( $data ) ) {
			// Convert complex data to JSON instead of using var_export
			$json_data = wp_json_encode( $data, JSON_PRETTY_PRINT );
			if ( false !== $json_data ) {
				$log_message .= ' - ' . $json_data;
			} else {
				$log_message .= ' - [Complex data that could not be encoded]';
			}
		} else {
			$log_message .= ' - ' . $data;
		}
	}

	// Use WordPress native logging function only
	// This writes to wp-content/debug.log when WP_DEBUG_LOG is enabled
	if ( function_exists( 'wp_debug_log' ) ) {
		wp_debug_log( $log_message );
	} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
		// Fallback to WordPress's built-in logging when wp_debug_log is not available
		// but only when debug logging is explicitly enabled
		if ( function_exists( 'error_log' ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Fallback logging only when WP_DEBUG_LOG is enabled
			error_log( $log_message );
		}
	}
}

// Autoloader
spl_autoload_register(
	function ( $class ) {
		// Only handle classes in our namespace
		$prefix = 'GreenMetrics\\';
		if ( strpos( $class, $prefix ) !== 0 ) {
			return;
		}

		// Remove namespace prefix to get relative class name
		$relative_class = substr( $class, strlen( $prefix ) ); // e.g., Admin\GreenMetrics_Admin

		// Split the relative class name into parts
		$parts = explode( '\\', $relative_class ); // e.g., ['Admin', 'GreenMetrics_Admin']

		// The last part is the class name
		$class_name_raw = array_pop( $parts ); // e.g., 'GreenMetrics_Admin'

		// The remaining parts form the subdirectory path (convert to lowercase)
		$subdir = ! empty( $parts ) ? strtolower( implode( '/', $parts ) ) . '/' : ''; // e.g., 'admin/'

		// Convert the raw class name (e.g., GreenMetrics_Admin) to lowercase kebab-case (greenmetrics-admin)
		$class_name_kebab = strtolower( str_replace( '_', '-', $class_name_raw ) );

		// Build the full file path
		$file = GREENMETRICS_PLUGIN_DIR . 'includes/' . $subdir . 'class-' . $class_name_kebab . '.php';

		// No logging here - autoloader runs frequently and logging is unnecessary
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

// The custom autoloader above handles these, so manual requires are redundant.

// Initialize the plugin
function greenmetrics_init() {
	// Use a static flag to ensure this function only runs once
	static $initialized = false;

	if ($initialized) {
		return;
	}

	$initialized = true;

	// Note: load_plugin_textdomain() is not needed for WordPress.org plugins since WordPress 4.6
	// WordPress automatically loads translations for plugins using the plugin slug 'greenmetrics'

	// Explicitly load the Icons class to avoid autoloading issues
	require_once GREENMETRICS_PLUGIN_DIR . 'includes/class-greenmetrics-icons.php';

	// Explicitly load the Formatter class to ensure it's available everywhere
	require_once GREENMETRICS_PLUGIN_DIR . 'includes/class-greenmetrics-formatter.php';

	// Explicitly load the Export Handler class
	require_once GREENMETRICS_PLUGIN_DIR . 'includes/class-greenmetrics-export-handler.php';

	// Explicitly load the Import Handler class
	require_once GREENMETRICS_PLUGIN_DIR . 'includes/class-greenmetrics-import-handler.php';

	// Explicitly load the Email Reporter class
	require_once GREENMETRICS_PLUGIN_DIR . 'includes/class-greenmetrics-email-reporter.php';

	// Explicitly load the Chart Generator class
	require_once GREENMETRICS_PLUGIN_DIR . 'includes/class-greenmetrics-chart-generator.php';

	// Explicitly load the Email Report History class
	require_once GREENMETRICS_PLUGIN_DIR . 'includes/class-greenmetrics-email-report-history.php';

	// Explicitly load the SVG Sanitizer class
	require_once GREENMETRICS_PLUGIN_DIR . 'includes/class-greenmetrics-svg-sanitizer.php';

	// Debug and development settings should never run in production
	if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		// Log current settings in development environments only
		$settings = get_option( 'greenmetrics_settings', array() );
		greenmetrics_log( 'Plugin Initialization - Current settings', $settings );
	}

	// Initialize components
	try {
		// Initialize components
		$admin        = new \GreenMetrics\Admin\GreenMetrics_Admin();
		$public       = new \GreenMetrics\GreenMetrics_Public();
		$tracker      = \GreenMetrics\GreenMetrics_Tracker::get_instance();
		$rest_api     = new \GreenMetrics\GreenMetrics_Rest_API();
		$data_manager = \GreenMetrics\GreenMetrics_Data_Manager::get_instance();

		// Initialize the SVG Sanitizer
		\GreenMetrics\GreenMetrics_SVG_Sanitizer::get_instance();

		// Schedule data management tasks
		\GreenMetrics\GreenMetrics_Data_Manager::schedule_data_management();
		\GreenMetrics\GreenMetrics_Data_Manager::register_cron_job();

		// Schedule email reporting tasks
		\GreenMetrics\GreenMetrics_Email_Reporter::schedule_email_reporting();
		\GreenMetrics\GreenMetrics_Email_Reporter::register_cron_job();
	} catch ( Exception $e ) {
		// Log error and show admin notice
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			if ( function_exists( 'wp_debug_log' ) ) {
				wp_debug_log( 'GreenMetrics Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString() );
			}
		}

		add_action(
			'admin_notices',
			function () use ( $e ) {
				?>
			<div class="notice notice-error">
				<p><?php echo esc_html( 'GreenMetrics Error: ' . $e->getMessage() ); ?></p>
			</div>
				<?php
			}
		);
	}
}
add_action( 'plugins_loaded', 'greenmetrics_init' );

// Activation hook
register_activation_hook(
	__FILE__,
	function () {
		GreenMetrics\GreenMetrics_Activator::activate();
	}
);

// Also create table if it doesn't exist (for existing installations)
add_action(
	'plugins_loaded',
	function () {
		static $upgrade_checked = false;

		if ($upgrade_checked) {
			return;
		}

		$upgrade_checked = true;
		\GreenMetrics\GreenMetrics_Upgrader::check_for_upgrades();
	}
);

// Deactivation hook
register_deactivation_hook(
	__FILE__,
	function () {
		GreenMetrics\GreenMetrics_Deactivator::deactivate();
	}
);
