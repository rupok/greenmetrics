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
define( 'GREENMETRICS_NO_DEBUG', !GREENMETRICS_DEBUG );

/**
 * Helper function for logging debug messages.
 * Only logs if GREENMETRICS_DEBUG is enabled.
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
	if (GREENMETRICS_NO_DEBUG) {
		return;
	}
	
	// The code below only runs when debugging is enabled
	$log_message = date( '[Y-m-d H:i:s]' ) . " GreenMetrics: $message";

	if ( null !== $data ) {
		// Only do print_r for arrays and objects to improve performance
		if ( is_array( $data ) || is_object( $data ) ) {
			$log_message .= ' - ' . print_r( $data, true );
		} else {
			$log_message .= ' - ' . $data;
		}
	}

	// Write to a log file in wp-content directory which is typically writable
	$log_file = WP_CONTENT_DIR . '/greenmetrics-debug.log';
	file_put_contents( $log_file, $log_message . PHP_EOL, FILE_APPEND );

	// Also use error_log for standard WordPress logging
	error_log( $log_message );
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

		greenmetrics_log( 'Autoloader: Looking for file', $file );

		if ( file_exists( $file ) ) {
			require_once $file;

			// Verify class was loaded in debug mode only
			if ( ! class_exists( $class, false ) ) {
				greenmetrics_log( 'Autoloader: File loaded but class not found', $class, 'error' );
			}
		} else {
			greenmetrics_log( 'Autoloader: File not found', $file, 'warning' );
		}
	}
);

// The custom autoloader above handles these, so manual requires are redundant.

// Initialize the plugin
function greenmetrics_init() {
	// Load text domain
	load_plugin_textdomain( 'greenmetrics', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// Explicitly load the Icons class to avoid autoloading issues
	require_once GREENMETRICS_PLUGIN_DIR . 'includes/class-greenmetrics-icons.php';

	// Debug and development settings should never run in production
	if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		// Log current settings in development environments only
		$settings = get_option( 'greenmetrics_settings', array() );
		greenmetrics_log( 'Plugin Initialization - Current settings', $settings );
	}

	// Initialize components
	try {
		// Initialize components
		$admin    = new \GreenMetrics\Admin\GreenMetrics_Admin();
		$public   = new \GreenMetrics\GreenMetrics_Public();
		$tracker  = \GreenMetrics\GreenMetrics_Tracker::get_instance();
		$rest_api = new \GreenMetrics\GreenMetrics_Rest_API();

		greenmetrics_log( 'All components initialized successfully' );
	} catch ( Exception $e ) {
		// Log error and show admin notice
		error_log( 'GreenMetrics Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString() );
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
