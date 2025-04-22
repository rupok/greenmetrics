<?php
/**
 * The tracker functionality of the plugin.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */

namespace GreenMetrics;

// Include required classes
require_once __DIR__ . '/class-greenmetrics-calculator.php';
require_once __DIR__ . '/class-greenmetrics-settings-manager.php';
require_once __DIR__ . '/class-greenmetrics-db-helper.php';

/**
 * The tracker functionality of the plugin.
 */
class GreenMetrics_Tracker {
	/**
	 * The single instance of the class.
	 *
	 * @var GreenMetrics_Tracker
	 */
	private static $instance = null;

	/**
	 * The table name for metrics.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Initialize the class and set its properties.
	 */
	private function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'greenmetrics_stats';

		add_action( 'wp_footer', array( $this, 'inject_tracking_script' ) );

		// These AJAX handlers are deprecated but kept for backward compatibility
		// They will be removed in a future version. Use the REST API instead.
		add_action( 'wp_ajax_greenmetrics_track', array( $this, 'handle_tracking_request' ) );
		add_action( 'wp_ajax_nopriv_greenmetrics_track', array( $this, 'handle_tracking_request' ) );

		greenmetrics_log( 'Tracker initialized', $this->table_name );
	}

	/**
	 * Get the singleton instance.
	 *
	 * @return GreenMetrics_Tracker
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Inject the tracking script into the page footer.
	 */
	public function inject_tracking_script() {
		if ( ! $this->is_tracking_enabled() ) {
			greenmetrics_log( 'Tracking disabled, not injecting script' );
			return;
		}

		// If we're using public.js for tracking, don't inject the tracking script
		if ( defined( 'GREENMETRICS_USE_PUBLIC_JS' ) && GREENMETRICS_USE_PUBLIC_JS ) {
			greenmetrics_log( 'Using public.js for tracking instead of tracking.js' );
			return;
		}

		$settings   = GreenMetrics_Settings_Manager::get_instance()->get();
		$plugin_url = plugins_url( '', dirname( __DIR__ ) );

		greenmetrics_log( 'Injecting tracking script', get_the_ID() );
		?>
		<script>
			window.greenmetricsTracking = {
				enabled: true,
				carbonIntensity: <?php echo esc_js( $settings['carbon_intensity'] ); ?>,
				energyPerByte: <?php echo esc_js( $settings['energy_per_byte'] ); ?>,
				rest_nonce: '<?php echo wp_create_nonce( 'wp_rest' ); ?>',
				rest_url: '<?php echo esc_js( get_rest_url( null, 'greenmetrics/v1' ) ); ?>',
				page_id: <?php echo get_the_ID(); ?>
			};
		</script>
		<script src="<?php echo esc_url( $plugin_url . '/greenmetrics/public/js/greenmetrics-tracking.js' ); ?>"></script>
		<?php
	}

	/**
	 * Handle the tracking request.
	 *
	 * @deprecated 1.1.0 Use the REST API endpoint /greenmetrics/v1/track instead.
	 */
	public function handle_tracking_request() {
		// Log deprecation notice
		greenmetrics_log( 'DEPRECATED: The AJAX endpoint greenmetrics_track is deprecated. Use the REST API endpoint /greenmetrics/v1/track instead.', null, 'warning' );

		if ( ! $this->is_tracking_enabled() ) {
			greenmetrics_log( 'Tracking request rejected - tracking disabled', null, 'warning' );
			wp_send_json_error( 'Tracking is disabled' );
		}

		$data = json_decode( file_get_contents( 'php://input' ), true );
		if ( ! $data ) {
			greenmetrics_log( 'Invalid data in tracking request', null, 'error' );
			wp_send_json_error( 'Invalid data' );
		}

		greenmetrics_log( 'Tracking request received', $data );

		$page_id = get_the_ID();
		if ( ! $page_id ) {
			greenmetrics_log( 'Invalid page ID in tracking request', null, 'error' );
			wp_send_json_error( 'Invalid page ID' );
		}

		// Process the metrics using our common function
		$result = $this->process_and_save_metrics( $page_id, $data );

		if ( $result ) {
			wp_send_json_success();
		} else {
			wp_send_json_error( 'Failed to save metrics' );
		}
	}

	/**
	 * Calculate performance score based on load time.
	 * Uses a logarithmic scale to provide more granular scoring with decimal precision.
	 * Based on similar approaches used in web performance tools.
	 *
	 * @param float $load_time Load time in seconds
	 * @return float Performance score from 0-100 with decimal precision
	 */
	public function calculate_performance_score( $load_time ) {
		// Ignore extremely high load times (likely measurement errors or development-related delays)
		// These can happen during development with browser cache disabled or when dev tools are open
		if ( $load_time > 15 ) {
			greenmetrics_log( 'Ignoring abnormally high load time', $load_time, 'warning' );
			return 75; // Return a reasonable default value
		}

		// Convert to milliseconds for more precision
		$load_time_ms = $load_time * 1000;

		// Define reference values based on web performance standards
		// These values align with general performance expectations
		$fast_threshold_ms = 1500;    // 1.5 seconds - considered fast (scores close to 100)
		$slow_threshold_ms = 5000;    // 5 seconds - considered slow (scores around 50)
		$max_threshold_ms  = 10000;    // 10 seconds - very slow (scores below 20)

		// If load time is extremely fast, give a perfect score
		if ( $load_time_ms <= 500 ) {
			return 100;
		}

		// Apply a logarithmic scale for more granular scoring
		// This creates a curve that drops quickly for slow sites but gives more
		// precision for fast sites in the 90-100 range
		if ( $load_time_ms <= $fast_threshold_ms ) {
			// For fast sites (0-1.5s): subtle scoring from 90-100
			$score = 100 - ( 10 * ( $load_time_ms / $fast_threshold_ms ) );
		} elseif ( $load_time_ms <= $slow_threshold_ms ) {
			// For medium sites (1.5-5s): scoring from 50-90
			$normalized = ( $load_time_ms - $fast_threshold_ms ) / ( $slow_threshold_ms - $fast_threshold_ms );
			$score      = 90 - ( 40 * $normalized );
		} elseif ( $load_time_ms <= $max_threshold_ms ) {
			// For slow sites (5-10s): scoring from 20-50
			$normalized = ( $load_time_ms - $slow_threshold_ms ) / ( $max_threshold_ms - $slow_threshold_ms );
			$score      = 50 - ( 30 * $normalized );
		} else {
			// For extremely slow sites (>10s): scoring from 0-20
			$normalized = min( 1, ( $load_time_ms - $max_threshold_ms ) / $max_threshold_ms );
			$score      = max( 0, 20 - ( 20 * $normalized ) );
		}

		// Ensure score is within 0-100 range with 2 decimal precision
		return round( max( 0, min( 100, $score ) ), 2 );
	}

	/**
	 * Check if tracking is enabled.
	 */
	private function is_tracking_enabled() {
		return GreenMetrics_Settings_Manager::get_instance()->is_enabled( 'tracking_enabled' );
	}

	/**
	 * Get stats for a page or all pages.
	 *
	 * @param int|null $page_id The page ID or null for all pages.
	 * @param bool     $force_refresh Whether to force refresh the cache.
	 * @return array The statistics.
	 */
	public function get_stats( $page_id = null, $force_refresh = false ) {
		global $wpdb;

		if ( $page_id ) {
			greenmetrics_log( 'Getting stats for page ID', $page_id );
		}

		// Attempt to get cached stats
		$cache_key = 'greenmetrics_stats_' . ( $page_id ? $page_id : 'all' );
		$cached_stats = false;
		
		if ( !$force_refresh ) {
			$cached_stats = get_transient( $cache_key );
			if ( false !== $cached_stats ) {
				return $cached_stats;
			}
		}

		greenmetrics_log( 'Querying database for stats' );

		// Get settings to use in SQL calculations
		$settings         = $this->get_settings();
		$carbon_intensity = isset( $settings['carbon_intensity'] ) ? floatval( $settings['carbon_intensity'] ) : 0.475;
		$energy_per_byte  = isset( $settings['energy_per_byte'] ) ? floatval( $settings['energy_per_byte'] ) : 0.000000000072;

		// Format the query with WHERE clause if needed
		$table_name_escaped = esc_sql( $this->table_name );
		$sql                = "SELECT 
            COUNT(*) as total_views,
            SUM(data_transfer) as total_data_transfer,
            AVG(load_time) as avg_load_time,
            SUM(requests) as total_requests,
            /* Calculate valid performance scores directly in SQL */
            AVG(CASE 
                WHEN performance_score BETWEEN 0 AND 100 THEN performance_score 
                ELSE NULL 
            END) as avg_performance_score,
            MIN(CASE 
                WHEN performance_score BETWEEN 0 AND 100 THEN performance_score 
                ELSE NULL 
            END) as min_performance_score,
            MAX(CASE 
                WHEN performance_score BETWEEN 0 AND 100 THEN performance_score 
                ELSE NULL 
            END) as max_performance_score,
            /* Calculate total energy consumption directly in SQL */
            SUM(energy_consumption) as total_energy_consumption,
            /* Calculate total carbon footprint directly in SQL */
            SUM(carbon_footprint) as total_carbon_footprint
        FROM $table_name_escaped";

		if ( $page_id ) {
			$sql .= $wpdb->prepare( ' WHERE page_id = %d', $page_id );
		}

		$stats = $wpdb->get_row( $sql, ARRAY_A );

		if ( ! $stats ) {
			greenmetrics_log( 'No stats found in database', null, 'warning' );
			return array(
				'total_views'              => 0,
				'total_data_transfer'      => 0,
				'avg_load_time'            => 0,
				'median_load_time'         => 0,
				'total_requests'           => 0,
				'avg_performance_score'    => 100, // Default to 100% when no data
				'total_energy_consumption' => 0,
				'total_carbon_footprint'   => 0,
			);
		}

		// Validate metrics to ensure they're all positive numbers
		$total_views         = max( 0, intval( $stats['total_views'] ) );
		$total_data_transfer = max( 0, floatval( $stats['total_data_transfer'] ) );
		$avg_load_time       = max( 0, floatval( $stats['avg_load_time'] ) );
		$total_requests      = max( 0, intval( $stats['total_requests'] ) );

		// Calculate median load time using our new optimized method
		$median_load_time = 0;
		if ( $total_views > 0 ) {
			$median_load_time = $this->get_median_load_time( $page_id );
			// If calculation failed, fall back to average
			if ( null === $median_load_time ) {
				$median_load_time = $avg_load_time;
			}
		}

		// Ensure performance score is a valid percentage
		$avg_performance_score = isset( $stats['avg_performance_score'] ) && null !== $stats['avg_performance_score']
			? floatval( $stats['avg_performance_score'] )
			: 100; // Default to 100 if NULL (which can happen if all records were filtered out)

		if ( $avg_performance_score < 0 || $avg_performance_score > 100 ) {
			// Calculate performance score using load time
			if ( $avg_load_time > 0 ) {
				$performance_score = $this->calculate_performance_score( $avg_load_time );
			} else {
				$performance_score = 100; // Default to 100% when no data
			}
		} else {
			$performance_score = $avg_performance_score;
		}

		// Ensure the score is within the valid range with proper decimal precision
		$performance_score = max( 0, min( 100, floatval( $performance_score ) ) );

		$result = array(
			'total_views'              => $total_views,
			'total_data_transfer'      => $total_data_transfer,
			'avg_load_time'            => $avg_load_time,
			'median_load_time'         => $median_load_time,
			'total_requests'           => $total_requests,
			'avg_performance_score'    => $performance_score,
			'total_energy_consumption' => max( 0, floatval( $stats['total_energy_consumption'] ) ),
			'total_carbon_footprint'   => max( 0, floatval( $stats['total_carbon_footprint'] ) ),
		);

		// Cache the results for 24 hours
		set_transient( $cache_key, $result, DAY_IN_SECONDS );
		
		return $result;
	}

	/**
	 * Calculate median load time efficiently using a single query.
	 * 
	 * Uses a compatible approach for MySQL versions that don't support PERCENTILE_CONT.
	 *
	 * @param int|null $page_id Optional page ID to filter results.
	 * @return float|null Median load time or null if no data.
	 */
	public function get_median_load_time( $page_id = null ) {
		global $wpdb;
		$table_name_escaped = esc_sql( $this->table_name );
		
		// Where clause if filtering by page_id
		$where_clause = $page_id ? $wpdb->prepare( ' WHERE page_id = %d', $page_id ) : '';
		
		// Get count of rows
		$count_sql = "SELECT COUNT(*) FROM $table_name_escaped $where_clause";
		$total_rows = $wpdb->get_var( $count_sql );
		
		if ( !$total_rows ) {
			return null;
		}
		
		// Calculate middle position(s)
		$middle_low = floor( $total_rows / 2 );
		$middle_high = ceil( $total_rows / 2 );
		
		// Get the value(s) at the middle position(s)
		$median_sql = "SELECT AVG(load_time) FROM (
			SELECT load_time FROM $table_name_escaped $where_clause ORDER BY load_time
			LIMIT $middle_low, " . ($middle_high - $middle_low + 1) . "
		) as t";
		
		$median = $wpdb->get_var( $median_sql );
		
		// Ensure we return a valid number or null
		return $median !== false ? (float) $median : null;
	}

	/**
	 * Track page metrics
	 *
	 * @param int $page_id The page ID to track
	 * @return void
	 */

	/**
	 * Handle tracking page from REST API
	 *
	 * @param array $data Tracking data
	 * @return bool Success status
	 */
	public function handle_track_page( $data ) {
		try {
			greenmetrics_log( 'Tracker: handle_track_page called with data', $data );

			if ( ! $this->is_tracking_enabled() ) {
				greenmetrics_log( 'REST tracking rejected - tracking disabled', null, 'warning' );
				return GreenMetrics_Error_Handler::create_error( 'tracking_disabled', 'Tracking is disabled' );
			}

			if ( empty( $data['page_id'] ) || ! isset( $data['data_transfer'] ) || ! isset( $data['load_time'] ) ) {
				greenmetrics_log( 'REST tracking missing required data', $data, 'warning' );
				return GreenMetrics_Error_Handler::create_error( 'invalid_data', 'Missing required data' );
			}

			try {
				$page_id = intval( $data['page_id'] );
				greenmetrics_log( 'REST tracking for page ID', $page_id );

				// Check database setup
				global $wpdb;
				greenmetrics_log( 'Tracker: Database table name', $this->table_name );

				$table_exists = $this->table_exists();
				greenmetrics_log(
					'Tracker: Table exists check',
					array(
						'exists' => $table_exists,
						'result' => $table_exists,
					)
				);

				if ( ! $table_exists ) {
					greenmetrics_log( 'Tracker: Table does not exist! Creating table...' );
					// Attempt to create the table
					require_once plugin_dir_path( __DIR__ ) . 'includes/class-greenmetrics-activator.php';
					\GreenMetrics\GreenMetrics_Activator::activate();
				}

				// Use common method to process and save metrics
				greenmetrics_log( 'Tracker: About to call process_and_save_metrics' );
				$result = $this->process_and_save_metrics( $page_id, $data );
				greenmetrics_log( 'Tracker: process_and_save_metrics result', array( 'success' => $result ) );

				if ( GreenMetrics_Error_Handler::is_error( $result ) ) {
					return $result;
				}

				return GreenMetrics_Error_Handler::success();
			} catch ( \Exception $e ) {
				greenmetrics_log(
					'Exception in REST tracking',
					array(
						'message' => $e->getMessage(),
						'trace'   => $e->getTraceAsString(),
					),
					'error'
				);
				return GreenMetrics_Error_Handler::handle_exception( $e, 'tracking_exception' );
			}
		} catch ( \Exception $e ) {
			greenmetrics_log(
				'Exception in handle_track_page',
				array(
					'message' => $e->getMessage(),
					'trace'   => $e->getTraceAsString(),
				),
				'error'
			);
			return GreenMetrics_Error_Handler::handle_exception( $e, 'tracking_exception' );
		}
	}

	/**
	 * Get plugin settings with defaults
	 *
	 * @return array Settings
	 */
	public function get_settings() {
		$settings = GreenMetrics_Settings_Manager::get_instance()->get();
		greenmetrics_log( 'Using settings', $settings );
		return $settings;
	}

	/**
	 * Handle the tracking request from POST data (legacy format from greenmetrics_tracking action)
	 * This handles tracking requests from the older AJAX endpoint
	 */
	public function handle_tracking_request_from_post() {
		if ( ! $this->is_tracking_enabled() ) {
			greenmetrics_log( 'Tracking request rejected - tracking disabled', null, 'warning' );
			wp_send_json_error( 'Tracking is disabled' );
			return;
		}

		greenmetrics_log( 'Received POST tracking request' );

		if ( ! isset( $_POST['nonce'] ) ) {
			greenmetrics_log( 'No nonce provided', null, 'error' );
			wp_send_json_error( 'No nonce provided' );
			return;
		}

		if ( ! wp_verify_nonce( $_POST['nonce'], 'greenmetrics_tracking' ) ) {
			greenmetrics_log( 'Invalid nonce', null, 'error' );
			wp_send_json_error( 'Invalid nonce' );
			return;
		}

		if ( ! isset( $_POST['metrics'] ) ) {
			greenmetrics_log( 'No metrics provided', null, 'error' );
			wp_send_json_error( 'No metrics provided' );
			return;
		}

		$metrics = json_decode( stripslashes( $_POST['metrics'] ), true );
		greenmetrics_log( 'Decoded metrics from POST', $metrics );

		if ( ! is_array( $metrics ) ) {
			greenmetrics_log( 'Invalid metrics format', null, 'error' );
			wp_send_json_error( 'Invalid metrics format' );
			return;
		}

		// Get page ID - either from metrics or current page
		$page_id = isset( $metrics['page_id'] ) ? intval( $metrics['page_id'] ) : get_the_ID();

		if ( ! $page_id ) {
			greenmetrics_log( 'Invalid page ID in tracking request', null, 'error' );
			wp_send_json_error( 'Invalid page ID' );
			return;
		}

		// Process the metrics
		$result = $this->process_and_save_metrics( $page_id, $metrics );

		if ( $result ) {
			wp_send_json_success( 'Metrics saved successfully' );
		} else {
			wp_send_json_error( 'Failed to save metrics' );
		}
	}

	/**
	 * Process and save metrics for a page.
	 *
	 * @param int   $page_id The page ID.
	 * @param array $metrics The metrics data.
	 * @return bool True if successful, false otherwise.
	 */
	private function process_and_save_metrics( $page_id, $metrics ) {
		global $wpdb;

		greenmetrics_log( 'Processing metrics for page ID: ' . $page_id );

		try {
			if ( ! $page_id || ! is_array( $metrics ) ) {
				greenmetrics_log(
					'Invalid page ID or metrics data',
					array(
						'page_id' => $page_id,
						'metrics' => $metrics,
					),
					'error'
				);
				return GreenMetrics_Error_Handler::create_error( 'invalid_data', 'Invalid page ID or metrics data' );
			}

			// Get settings for calculations
			$settings = GreenMetrics_Settings_Manager::get_instance()->get();

			// Calculate metrics using the Calculator class
			$data_transfer = isset( $metrics['data_transfer'] ) ? floatval( $metrics['data_transfer'] ) : 0;
			$carbon_footprint = GreenMetrics_Calculator::calculate_carbon_emissions( $data_transfer );
			$energy_consumption = GreenMetrics_Calculator::calculate_energy_consumption( $data_transfer );

			// Calculate performance score
			$load_time = isset( $metrics['load_time'] ) ? floatval( $metrics['load_time'] ) : 0;
			$performance_score = $this->calculate_performance_score( $load_time );

			// Prepare data for insertion
			$data = array(
				'page_id'            => $page_id,
				'data_transfer'      => $data_transfer,
				'carbon_footprint'   => $carbon_footprint,
				'energy_consumption' => $energy_consumption,
				'load_time'          => $load_time,
				'performance_score'  => $performance_score,
				'requests'           => isset( $metrics['requests'] ) ? intval( $metrics['requests'] ) : 0,
				'created_at'         => current_time( 'mysql' ),
			);

			// Check if the table exists before attempting to insert
			if ( ! $this->table_exists() ) {
				greenmetrics_log( 'Table does not exist, attempting to create it', $this->table_name );
				// Try to create the table
				GreenMetrics_DB_Helper::create_stats_table();
				
				// Check if table creation was successful
				if ( ! $this->table_exists() ) {
					greenmetrics_log( 'Failed to create table', $this->table_name, 'error' );
					return GreenMetrics_Error_Handler::create_error( 'table_not_found', 'Database table does not exist and could not be created' );
				}
			}

			// Insert a new record for each page view instead of replacing/updating existing ones
			$result = $wpdb->insert( $this->table_name, $data );

			if ( false === $result ) {
				greenmetrics_log(
					'Failed to save metrics',
					array(
						'error' => $wpdb->last_error,
						'query' => $wpdb->last_query,
					),
					'error'
				);
				return GreenMetrics_Error_Handler::create_error( 'database_error', 'Failed to save metrics' );
			}

			// Delete the cache for this page and for all pages to ensure fresh data
			$this->delete_stats_cache($page_id);
			$this->delete_stats_cache(null);  // Delete the 'all' cache
			
			greenmetrics_log( 'Metrics saved successfully for page ID: ' . $page_id );
			return true;
		} catch ( \Exception $e ) {
			greenmetrics_log( 'Exception in process_and_save_metrics', array( 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString() ), 'error' );
			return GreenMetrics_Error_Handler::handle_exception( $e, 'tracking_exception' );
		}
	}

	/**
	 * Delete the stats cache for a page or all pages.
	 *
	 * @param int|null $page_id The page ID or null for all pages.
	 */
	private function delete_stats_cache( $page_id = null ) {
		$cache_key = 'greenmetrics_stats_' . ( $page_id ? $page_id : 'all' );
		delete_transient( $cache_key );
	}

	/**
	 * Check if metrics table exists, using a prepared statement and cached result.
	 *
	 * @return bool True if table exists, false otherwise.
	 */
	private function table_exists(): bool {
		return GreenMetrics_DB_Helper::table_exists( $this->table_name );
	}

	/**
	 * Schedule a daily cron job to refresh stats cache.
	 */
	public static function schedule_daily_cache_refresh() {
		if ( ! wp_next_scheduled( 'greenmetrics_daily_cache_refresh' ) ) {
			wp_schedule_event( time(), 'daily', 'greenmetrics_daily_cache_refresh' );
			greenmetrics_log( 'Daily cache refresh scheduled' );
		}
	}

	/**
	 * Refresh the stats cache for all pages.
	 */
	public static function refresh_stats_cache() {
		$instance = self::get_instance();
		
		// First, delete all caches to ensure they're refreshed
		$instance->delete_stats_cache(null);
		
		// Now force a new database query to refresh the cache
		$instance->get_stats(null, true);
		
		greenmetrics_log( 'Stats cache refreshed' );
	}

	/**
	 * Register the cron job callback.
	 */
	public static function register_cron_job() {
		add_action( 'greenmetrics_daily_cache_refresh', array( __CLASS__, 'refresh_stats_cache' ) );
	}

	/**
	 * Manually trigger the cache refresh.
	 */
	public static function manual_cache_refresh() {
		greenmetrics_log( 'Manual cache refresh started' );
		self::refresh_stats_cache();
		greenmetrics_log( 'Manual cache refresh completed' );
	}
}
