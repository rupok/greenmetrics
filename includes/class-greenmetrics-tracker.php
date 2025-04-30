<?php
/**
 * The tracker functionality of the plugin.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */

namespace GreenMetrics;
// phpcs:ignoreFile WordPress.DB.PreparedSQL.InterpolatedNotPrepared

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

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
	 * Handle the tracking request.
	 *
	 * This function processes tracking data from the REST API endpoint.
	 *
	 * @param array $data The tracking data
	 * @return bool Whether the processing was successful
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
	 * Calculate performance score based on load time.
	 * Uses a true logarithmic scale to provide more consistent scoring across all load times.
	 * Based on similar approaches used in web performance tools like Lighthouse and PageSpeed Insights.
	 *
	 * @param float $load_time Load time in seconds
	 * @return float Performance score from 0-100 with decimal precision
	 */
	public function calculate_performance_score( $load_time ) {
		// Handle invalid or zero load times
		if ( ! is_numeric( $load_time ) || $load_time <= 0 ) {
			return 100; // Perfect score for instant loads
		}

		// Convert to milliseconds for more precision
		$load_time_ms = $load_time * 1000;

		// Define reference values based on web performance standards
		$perfect_threshold_ms = 500;   // 0.5 seconds - considered perfect (score 100)
		$fast_threshold_ms    = 1500;  // 1.5 seconds - considered fast (scores ~90)
		$slow_threshold_ms    = 5000;  // 5 seconds - considered slow (scores ~50)
		$very_slow_ms         = 15000; // 15 seconds - very slow (scores ~10)

		// If load time is extremely fast, give a perfect score
		if ( $load_time_ms <= $perfect_threshold_ms ) {
			return 100;
		}

		// Use a true logarithmic scale for more consistent scoring across all load times
		// This approach provides a smooth curve that works well for both fast and slow pages

		// Parameters for the logarithmic function
		$base = 1.5;  // Logarithm base (controls how quickly scores drop)
		$scale = 100; // Maximum score
		$offset = 1;  // Offset to avoid log(0)

		// Calculate score using logarithmic function: score = scale - log_base(time + offset)
		// This creates a curve that drops quickly for slow sites but gives more
		// precision for fast sites in the 90-100 range
		$log_value = log($load_time_ms + $offset) / log($base);
		$score = $scale - (15 * $log_value);

		// Apply additional scaling for very slow pages to ensure more consistent results
		if ( $load_time_ms > $very_slow_ms ) {
			// For extremely slow pages, apply a gentler slope to avoid scores dropping too quickly
			$excess_time = $load_time_ms - $very_slow_ms;
			$penalty = min(40, 5 * log($excess_time / 1000 + 1) / log(10));
			$score = max(1, $score - $penalty);
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

		// For admin dashboard requests, always force refresh to ensure latest data
		if (is_admin() && !wp_doing_ajax()) {
			$force_refresh = true;
		}

		// Attempt to get cached stats
		$cache_key    = 'greenmetrics_stats_' . ( $page_id ? $page_id : 'all' );
		$cached_stats = false;

		if ( ! $force_refresh ) {
			$cached_stats = get_transient( $cache_key );
			if ( false !== $cached_stats ) {
				return $cached_stats;
			}
		}

		// Query database for stats

		// Get settings to use in SQL calculations
		$settings         = $this->get_settings();
		$carbon_intensity = isset( $settings['carbon_intensity'] ) ? floatval( $settings['carbon_intensity'] ) : 0.475;
		$energy_per_byte  = isset( $settings['energy_per_byte'] ) ? floatval( $settings['energy_per_byte'] ) : 0.000000000072;

		$table = esc_sql( $this->table_name );
		if ( $page_id ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$stats = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
						COUNT(*) as total_views,
						SUM(data_transfer) as total_data_transfer,
						AVG(load_time) as avg_load_time,
						AVG(performance_score) as avg_performance_score,
						SUM(requests) as total_requests,
						SUM(energy_consumption) as total_energy_consumption,
						SUM(carbon_footprint) as total_carbon_footprint
					FROM {$table}
					WHERE page_id = %d",
					$page_id
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$stats = $wpdb->get_row(
				"SELECT
					COUNT(*) as total_views,
					SUM(data_transfer) as total_data_transfer,
					AVG(load_time) as avg_load_time,
					AVG(performance_score) as avg_performance_score,
					SUM(requests) as total_requests,
					SUM(energy_consumption) as total_energy_consumption,
					SUM(carbon_footprint) as total_carbon_footprint
				FROM {$table}",
				ARRAY_A
			);
		}

		if ( ! $stats ) {
			// Return default values when no stats are found
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
		$table = esc_sql( $this->table_name );
		if ( $page_id ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total_rows = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE page_id = %d",
					$page_id
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total_rows = $wpdb->get_var(
				"SELECT COUNT(*) FROM {$table}"
			);
		}
		if ( ! $total_rows ) {
			return null;
		}
		$middle_low  = floor( $total_rows / 2 );
		$middle_high = ceil( $total_rows / 2 );
		if ( $page_id ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$median = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT AVG(load_time) FROM (
						SELECT load_time FROM {$table}
						WHERE page_id = %d
						ORDER BY load_time
						LIMIT %d, %d
					) as t",
					$page_id,
					$middle_low,
					$middle_high - $middle_low + 1
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$median = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT AVG(load_time) FROM (
						SELECT load_time FROM {$table}
						ORDER BY load_time
						LIMIT %d, %d
					) as t",
					$middle_low,
					$middle_high - $middle_low + 1
				)
			);
		}
		return $median !== false ? (float) $median : null;
	}

	/**
	 * Get the plugin settings.
	 *
	 * @return array The plugin settings.
	 */
	public function get_settings() {
		return GreenMetrics_Settings_Manager::get_instance()->get();
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

		// Process metrics for page ID

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
			$data_transfer      = isset( $metrics['data_transfer'] ) ? floatval( $metrics['data_transfer'] ) : 0;
			$carbon_footprint   = GreenMetrics_Calculator::calculate_carbon_emissions( $data_transfer );
			$energy_consumption = GreenMetrics_Calculator::calculate_energy_consumption( $data_transfer );

			// Calculate performance score
			$load_time         = isset( $metrics['load_time'] ) ? floatval( $metrics['load_time'] ) : 0;
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

			// Implement more targeted cache invalidation
			// Only delete the cache for this specific page
			$this->delete_stats_cache( $page_id );

			// Check if we should update the "all" cache based on a threshold
			$this->maybe_update_all_cache();

			return true;
		} catch ( \Exception $e ) {
			greenmetrics_log(
				'Exception in process_and_save_metrics',
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
	 * Delete the stats cache for a page or all pages.
	 *
	 * @param int|null $page_id The page ID or null for all pages.
	 */
	private function delete_stats_cache( $page_id = null ) {
		$cache_key = 'greenmetrics_stats_' . ( $page_id ? $page_id : 'all' );
		delete_transient( $cache_key );

		// If we're deleting a specific page cache, also delete any date range caches
		// that might include this page's data
		if ( $page_id ) {
			$this->delete_date_range_caches();
		}
	}

	/**
	 * Delete all date range caches.
	 */
	private function delete_date_range_caches() {
		global $wpdb;

		// Delete all date range transients
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'%_transient_greenmetrics_date_range_%'
			)
		);

		// Also delete the timeout entries
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'%_transient_timeout_greenmetrics_date_range_%'
			)
		);
	}

	/**
	 * Check if we should update the "all" cache based on certain conditions.
	 * This implements a more efficient cache invalidation strategy.
	 */
	private function maybe_update_all_cache() {
		// Get the last time the "all" cache was updated
		$last_update = get_option( 'greenmetrics_all_cache_last_update', 0 );
		$current_time = time();

		// Define thresholds for cache updates
		$update_threshold = 5 * MINUTE_IN_SECONDS; // Update "all" cache at most every 5 minutes

		// Check if enough time has passed since the last update
		if ( $current_time - $last_update > $update_threshold ) {
			// Delete and refresh the "all" cache
			$this->delete_stats_cache( null );

			// Force a refresh of the "all" cache by calling get_stats
			$this->get_stats( null, true );

			// Update the last update timestamp
			update_option( 'greenmetrics_all_cache_last_update', $current_time );

			// Cache refreshed based on time threshold
		} else {
			// Skipping cache refresh (time threshold not met)
		}
	}

	/**
	 * Check if metrics table exists, using a prepared statement and cached result.
	 *
	 * @param bool $force_db_check Whether to force a fresh check from the database.
	 * @return bool True if table exists, false otherwise.
	 */
	private function table_exists(bool $force_db_check = false): bool {
		return GreenMetrics_DB_Helper::table_exists( $this->table_name, $force_db_check );
	}

	/**
	 * Schedule a daily cron job to refresh stats cache.
	 */
	public static function schedule_daily_cache_refresh() {
		if ( ! wp_next_scheduled( 'greenmetrics_daily_cache_refresh' ) ) {
			wp_schedule_event( time(), 'daily', 'greenmetrics_daily_cache_refresh' );
		}
	}

	/**
	 * Refresh the stats cache for all pages.
	 */
	public static function refresh_stats_cache() {
		$instance = self::get_instance();

		// First, delete all caches to ensure they're refreshed
		$instance->delete_stats_cache( null );

		// Now force a new database query to refresh the cache
		$instance->get_stats( null, true );
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
		// No logging needed for this common operation
		self::refresh_stats_cache();
	}

	/**
	 * Get metrics by date range for chart display.
	 *
	 * @param string $start_date Start date (Y-m-d format).
	 * @param string $end_date End date (Y-m-d format).
	 * @param string $interval Interval (day, week, month).
	 * @param bool   $force_refresh Whether to force refresh the cache.
	 * @return array Array of metrics data grouped by date.
	 */
	public function get_metrics_by_date_range( $start_date = null, $end_date = null, $interval = 'day', $force_refresh = false ) {
		global $wpdb;
		$table = esc_sql( $this->table_name );

		// Set default date range if not provided (last 7 days)
		if ( empty( $start_date ) ) {
			$start_date = gmdate( 'Y-m-d', strtotime( '-7 days' ) );
		}
		if ( empty( $end_date ) ) {
			$end_date = gmdate( 'Y-m-d' );
		}

		// For admin dashboard page loads, always force refresh to ensure latest data
		if (is_admin() && !wp_doing_ajax()) {
			$force_refresh = true;
		}

		// Create a cache key based on the parameters
		$cache_key = 'greenmetrics_date_range_' . md5( $start_date . '_' . $end_date . '_' . $interval );

		// Try to get cached results
		if ( ! $force_refresh ) {
			$cached_results = get_transient( $cache_key );
			if ( false !== $cached_results ) {
				return $cached_results;
			}
		}
		// Format dates for SQL query
		$start_date_sql = gmdate( 'Y-m-d 00:00:00', strtotime( $start_date ) );
		$end_date_sql   = gmdate( 'Y-m-d 23:59:59', strtotime( $end_date ) );
		// Group by interval
		$group_by    = 'DATE(created_at)';
		$select_date = 'DATE(created_at) as date';
		if ( $interval === 'week' ) {
			$group_by    = 'YEARWEEK(created_at)';
			$select_date = "STR_TO_DATE(CONCAT(YEARWEEK(created_at),' Sunday'), '%X%V %W') as date";
		} elseif ( $interval === 'month' ) {
			$group_by    = "DATE_FORMAT(created_at, '%Y-%m')";
			$select_date = "DATE_FORMAT(created_at, '%Y-%m-01') as date";
		}
		// Check if we got results
		// Execute inline prepared query for metrics by date range
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT {$select_date},
					COUNT(*) as views,
					SUM(data_transfer) as data_transfer,
					SUM(carbon_footprint) as carbon_footprint,
					SUM(energy_consumption) as energy_consumption,
					SUM(requests) as requests,
					AVG(load_time) as avg_load_time
				FROM {$table}
				WHERE created_at BETWEEN %s AND %s
				GROUP BY {$group_by}
				ORDER BY date ASC",
				$start_date_sql,
				$end_date_sql
			),
			ARRAY_A
		);

		if ( ! $results ) {
			return array();
		}

		// Format results for chart display
		$formatted_results = array(
			'dates'              => array(),
			'carbon_footprint'   => array(),
			'energy_consumption' => array(),
			'data_transfer'      => array(),
			'http_requests'      => array(),
			'page_views'         => array(),
		);

		foreach ( $results as $row ) {
			// Format date for display
			$formatted_date = gmdate( 'M j', strtotime( $row['date'] ) );

			// Add data to the formatted results
			$formatted_results['dates'][]              = $formatted_date;
			$formatted_results['carbon_footprint'][]   = round( floatval( $row['carbon_footprint'] ), 2 );
			$formatted_results['energy_consumption'][] = round( floatval( $row['energy_consumption'] ), 4 );
			$formatted_results['data_transfer'][]      = round( floatval( $row['data_transfer'] ) / 1024, 2 ); // Convert to KB
			$formatted_results['http_requests'][]      = intval( $row['requests'] );
			$formatted_results['page_views'][]         = intval( $row['views'] );
		}

		// Cache the results for 1 hour
		set_transient( $cache_key, $formatted_results, HOUR_IN_SECONDS );

		return $formatted_results;
	}
}
