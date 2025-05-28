<?php
/**
 * Data management functionality.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */

namespace GreenMetrics;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class for managing metrics data aggregation and pruning.
 *
 * This class handles the aggregation of older metrics data and pruning of
 * individual records to prevent excessive database growth.
 */
class GreenMetrics_Data_Manager {

	/**
	 * The single instance of the class.
	 *
	 * @var GreenMetrics_Data_Manager
	 */
	private static $instance = null;

	/**
	 * The table name for metrics.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * The table name for aggregated metrics.
	 *
	 * @var string
	 */
	private $aggregated_table_name;

	/**
	 * Initialize the class and set its properties.
	 */
	private function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'greenmetrics_stats';
		$this->aggregated_table_name = $wpdb->prefix . 'greenmetrics_aggregated_stats';

		// Ensure the aggregated stats table exists
		$this->maybe_create_aggregated_table();
	}

	/**
	 * Get the singleton instance.
	 *
	 * @return GreenMetrics_Data_Manager
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Create the aggregated stats table if it doesn't exist.
	 */
	private function maybe_create_aggregated_table() {
		global $wpdb;

		// Check if the table already exists
		if ( GreenMetrics_DB_Helper::table_exists( $this->aggregated_table_name ) ) {
			return;
		}

		greenmetrics_log( 'Creating aggregated stats table' );

		$charset_collate = $wpdb->get_charset_collate();

		// Capture any PHP errors that might occur during table creation
		$previous_error_reporting = error_reporting();
		error_reporting( E_ALL );
		$previous_error_handler = set_error_handler(
			function ( $errno, $errstr, $errfile, $errline ) {
				greenmetrics_log( "PHP Error during aggregated table creation: $errstr", array( 'file' => $errfile, 'line' => $errline ), 'error' );
				return false; // Let the standard error handler continue
			}
		);

		try {
			$sql = "CREATE TABLE IF NOT EXISTS {$this->aggregated_table_name} (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				page_id bigint(20) NOT NULL,
				date_start datetime NOT NULL,
				date_end datetime NOT NULL,
				aggregation_type VARCHAR(10) NOT NULL,
				views int(11) NOT NULL,
				total_data_transfer bigint(20) NOT NULL,
				avg_data_transfer bigint(20) NOT NULL,
				total_load_time float NOT NULL,
				avg_load_time float NOT NULL,
				total_requests int(11) NOT NULL,
				avg_requests float NOT NULL,
				total_carbon_footprint float NOT NULL,
				avg_carbon_footprint float NOT NULL,
				total_energy_consumption float NOT NULL,
				avg_energy_consumption float NOT NULL,
				avg_performance_score float NOT NULL,
				created_at datetime DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY page_id (page_id),
				KEY date_start (date_start),
				KEY aggregation_type (aggregation_type)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$result = dbDelta( $sql );

			// Check for MySQL errors
			if ( ! empty( $wpdb->last_error ) ) {
				$error_message = 'MySQL error during aggregated table creation: ' . $wpdb->last_error;
				greenmetrics_log( $error_message, $wpdb->last_query, 'error' );

				// Show admin notice about the error
				GreenMetrics_Error_Handler::admin_notice(
					sprintf(
						/* translators: %s: Database error message */
						__( 'GreenMetrics: Failed to create aggregated data table. Error: %s', 'greenmetrics' ),
						esc_html( $wpdb->last_error )
					),
					'error',
					false
				);

				// Store the error for reference
				update_option( 'greenmetrics_aggregated_db_error', $wpdb->last_error );
			} else {
				greenmetrics_log( 'Aggregated table creation result', $result );

				// Verify table was actually created
				if ( ! GreenMetrics_DB_Helper::table_exists( $this->aggregated_table_name, true ) ) {
					$error_message = 'Aggregated table creation failed: Table does not exist after dbDelta';
					greenmetrics_log( $error_message, null, 'error' );

					// Show admin notice about the error
					GreenMetrics_Error_Handler::admin_notice(
						__( 'GreenMetrics: Failed to create aggregated data table. Data aggregation will not be available.', 'greenmetrics' ),
						'error',
						false
					);
				} else {
					// Clear any previous errors
					delete_option( 'greenmetrics_aggregated_db_error' );
				}
			}
		} catch ( \Exception $e ) {
			$error_message = 'Exception during aggregated table creation: ' . $e->getMessage();
			greenmetrics_log( $error_message, $e->getTraceAsString(), 'error' );

			// Show admin notice about the error
			GreenMetrics_Error_Handler::admin_notice(
				sprintf(
					/* translators: %s: Exception message */
					__( 'GreenMetrics: Exception during aggregated table creation: %s', 'greenmetrics' ),
					esc_html( $e->getMessage() )
				),
				'error',
				false
			);

			// Store the error for reference
			update_option( 'greenmetrics_aggregated_db_error', $e->getMessage() );
		} finally {
			// Restore previous error handler and reporting level
			if ( $previous_error_handler ) {
				restore_error_handler();
			}
			error_reporting( $previous_error_reporting );
		}
	}

	/**
	 * Schedule the data management cron job.
	 */
	public static function schedule_data_management() {
		// Get the settings
		$settings = GreenMetrics_Settings_Manager::get_instance()->get();

		// Check if data management is enabled
		if ( empty( $settings['data_management_enabled'] ) ) {
			// If disabled, clear any existing scheduled events
			if ( wp_next_scheduled( 'greenmetrics_data_management' ) ) {
				wp_clear_scheduled_hook( 'greenmetrics_data_management' );
				greenmetrics_log( 'Data management cron job unscheduled' );
			}
			return;
		}

		// Schedule the cron job if not already scheduled
		if ( ! wp_next_scheduled( 'greenmetrics_data_management' ) ) {
			// Schedule to run daily at midnight
			wp_schedule_event( strtotime( 'tomorrow midnight' ), 'daily', 'greenmetrics_data_management' );
			greenmetrics_log( 'Data management cron job scheduled' );
		}
	}

	/**
	 * Register the cron job callback.
	 */
	public static function register_cron_job() {
		add_action( 'greenmetrics_data_management', array( __CLASS__, 'run_scheduled_data_management' ) );
	}

	/**
	 * Run the scheduled data management tasks.
	 */
	public static function run_scheduled_data_management() {
		$instance = self::get_instance();

		// Get settings
		$settings = GreenMetrics_Settings_Manager::get_instance()->get();

		// Check if data management is enabled
		if ( empty( $settings['data_management_enabled'] ) ) {
			greenmetrics_log( 'Scheduled data management skipped - feature disabled' );
			return;
		}

		greenmetrics_log( 'Running scheduled data management' );

		// Run aggregation
		$aggregation_age = isset( $settings['aggregation_age'] ) ? intval( $settings['aggregation_age'] ) : 30;
		$aggregation_type = isset( $settings['aggregation_type'] ) ? $settings['aggregation_type'] : 'daily';

		if ( $aggregation_age > 0 ) {
			$instance->aggregate_old_data( $aggregation_age, $aggregation_type );
		}

		// Run pruning
		$retention_period = isset( $settings['retention_period'] ) ? intval( $settings['retention_period'] ) : 90;

		if ( $retention_period > 0 ) {
			$instance->prune_old_data( $retention_period );
		}

		greenmetrics_log( 'Scheduled data management completed' );
	}

	/**
	 * Aggregate old data.
	 *
	 * @param int    $days_old Number of days old the data should be to be aggregated.
	 * @param string $aggregation_type Type of aggregation (daily, weekly, monthly).
	 * @return array Results of the aggregation.
	 */
	public function aggregate_old_data( $days_old = 30, $aggregation_type = 'daily' ) {
		global $wpdb;

		greenmetrics_log( "Aggregating data older than {$days_old} days by {$aggregation_type}" );

		// Calculate the cutoff date
		$cutoff_date = gmdate( 'Y-m-d 00:00:00', strtotime( "-{$days_old} days" ) );

		// Define the date format and group by clause based on aggregation type
		switch ( $aggregation_type ) {
			case 'weekly':
				$date_format = 'DATE(DATE_SUB(created_at, INTERVAL WEEKDAY(created_at) DAY))';
				$group_by = "page_id, YEARWEEK(created_at)";
				$interval = '7 DAY';
				break;
			case 'monthly':
				$date_format = 'DATE_FORMAT(created_at, "%Y-%m-01")';
				$group_by = "page_id, YEAR(created_at), MONTH(created_at)";
				$interval = '1 MONTH';
				break;
			case 'daily':
			default:
				$date_format = 'DATE(created_at)';
				$group_by = "page_id, DATE(created_at)";
				$interval = '1 DAY';
				break;
		}

		// Check if there's data to aggregate
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_name} WHERE created_at < %s",
				$cutoff_date
			)
		);

		if ( ! $count || $count == 0 ) {
			greenmetrics_log( 'No data to aggregate' );
			return array(
				'aggregated' => 0,
				'error' => false,
			);
		}

		// Get the dates that need aggregation
		$dates_to_aggregate = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT {$date_format} as date_start,
				DATE_ADD({$date_format}, INTERVAL {$interval}) as date_end,
				page_id
				FROM {$this->table_name}
				WHERE created_at < %s
				GROUP BY {$group_by}",
				$cutoff_date
			),
			ARRAY_A
		);

		if ( ! $dates_to_aggregate ) {
			greenmetrics_log( 'No dates to aggregate' );
			return array(
				'aggregated' => 0,
				'error' => false,
			);
		}

		greenmetrics_log( 'Found ' . count( $dates_to_aggregate ) . ' date periods to aggregate' );

		$aggregated_count = 0;
		$error = false;

		// Process each date period
		foreach ( $dates_to_aggregate as $period ) {
			$page_id = $period['page_id'];
			$date_start = $period['date_start'];
			$date_end = $period['date_end'];

			// Check if this period is already aggregated
			$already_aggregated = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->aggregated_table_name}
					WHERE page_id = %d AND date_start = %s AND date_end = %s AND aggregation_type = %s",
					$page_id, $date_start, $date_end, $aggregation_type
				)
			);

			if ( $already_aggregated > 0 ) {
				greenmetrics_log( "Period {$date_start} to {$date_end} for page {$page_id} already aggregated, skipping" );
				continue;
			}

			// Aggregate the data for this period
			$aggregated_data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
					COUNT(*) as views,
					SUM(data_transfer) as total_data_transfer,
					AVG(data_transfer) as avg_data_transfer,
					SUM(load_time) as total_load_time,
					AVG(load_time) as avg_load_time,
					SUM(requests) as total_requests,
					AVG(requests) as avg_requests,
					SUM(carbon_footprint) as total_carbon_footprint,
					AVG(carbon_footprint) as avg_carbon_footprint,
					SUM(energy_consumption) as total_energy_consumption,
					AVG(energy_consumption) as avg_energy_consumption,
					AVG(performance_score) as avg_performance_score
					FROM {$this->table_name}
					WHERE page_id = %d
					AND created_at >= %s
					AND created_at < %s",
					$page_id, $date_start, $date_end
				),
				ARRAY_A
			);

			if ( ! $aggregated_data || empty( $aggregated_data['views'] ) ) {
				greenmetrics_log( "No data found for period {$date_start} to {$date_end} for page {$page_id}" );
				continue;
			}

			// Insert the aggregated data
			$insert_data = array(
				'page_id' => $page_id,
				'date_start' => $date_start,
				'date_end' => $date_end,
				'aggregation_type' => $aggregation_type,
				'views' => $aggregated_data['views'],
				'total_data_transfer' => $aggregated_data['total_data_transfer'],
				'avg_data_transfer' => $aggregated_data['avg_data_transfer'],
				'total_load_time' => $aggregated_data['total_load_time'],
				'avg_load_time' => $aggregated_data['avg_load_time'],
				'total_requests' => $aggregated_data['total_requests'],
				'avg_requests' => $aggregated_data['avg_requests'],
				'total_carbon_footprint' => $aggregated_data['total_carbon_footprint'],
				'avg_carbon_footprint' => $aggregated_data['avg_carbon_footprint'],
				'total_energy_consumption' => $aggregated_data['total_energy_consumption'],
				'avg_energy_consumption' => $aggregated_data['avg_energy_consumption'],
				'avg_performance_score' => $aggregated_data['avg_performance_score'],
				'created_at' => current_time( 'mysql' ),
			);

			$result = $wpdb->insert( $this->aggregated_table_name, $insert_data );

			if ( $result === false ) {
				greenmetrics_log( "Error inserting aggregated data: " . $wpdb->last_error, null, 'error' );
				$error = true;
				continue;
			}

			$aggregated_count++;
			greenmetrics_log( "Aggregated data for period {$date_start} to {$date_end} for page {$page_id}" );
		}

		greenmetrics_log( "Aggregation completed: {$aggregated_count} periods aggregated" );

		return array(
			'aggregated' => $aggregated_count,
			'error' => $error,
		);
	}

	/**
	 * Prune old data.
	 *
	 * @param int $days_old Number of days old the data should be to be pruned.
	 * @return array Results of the pruning.
	 */
	public function prune_old_data( $days_old = 90 ) {
		global $wpdb;

		greenmetrics_log( "Pruning data older than {$days_old} days" );

		// Calculate the cutoff date
		$cutoff_date = gmdate( 'Y-m-d 00:00:00', strtotime( "-{$days_old} days" ) );

		// Check if there's data to prune
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_name} WHERE created_at < %s",
				$cutoff_date
			)
		);

		if ( ! $count || $count == 0 ) {
			greenmetrics_log( 'No data to prune' );
			return array(
				'pruned' => 0,
				'error' => false,
			);
		}

		// Get the settings
		$settings = GreenMetrics_Settings_Manager::get_instance()->get();

		// Only prune data that has been aggregated if aggregation is enabled
		if ( ! empty( $settings['require_aggregation_before_pruning'] ) ) {
			greenmetrics_log( 'Pruning only data that has been aggregated' );

			// Get the aggregation type
			$aggregation_type = isset( $settings['aggregation_type'] ) ? $settings['aggregation_type'] : 'daily';

			// Define the date format based on aggregation type
			switch ( $aggregation_type ) {
				case 'weekly':
					$date_format = 'DATE(DATE_SUB(created_at, INTERVAL WEEKDAY(created_at) DAY))';
					$group_by = "YEARWEEK(created_at)";
					break;
				case 'monthly':
					$date_format = 'DATE_FORMAT(created_at, "%Y-%m-01")';
					$group_by = "YEAR(created_at), MONTH(created_at)";
					break;
				case 'daily':
				default:
					$date_format = 'DATE(created_at)';
					$group_by = "DATE(created_at)";
					break;
			}

			// Get dates that have been aggregated
			$aggregated_dates = $wpdb->get_col(
				"SELECT DISTINCT date_start FROM {$this->aggregated_table_name} WHERE aggregation_type = '{$aggregation_type}'"
			);

			if ( empty( $aggregated_dates ) ) {
				greenmetrics_log( 'No aggregated dates found, skipping pruning' );
				return array(
					'pruned' => 0,
					'error' => false,
				);
			}

			// Convert to SQL-friendly format for IN clause
			$dates_in = implode( "','", array_map( 'esc_sql', $aggregated_dates ) );

			// Delete records for dates that have been aggregated and are older than the cutoff
			$result = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$this->table_name}
					WHERE {$date_format} IN ('{$dates_in}')
					AND created_at < %s",
					$cutoff_date
				)
			);
		} else {
			// Delete all records older than the cutoff date
			$result = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$this->table_name} WHERE created_at < %s",
					$cutoff_date
				)
			);
		}

		if ( $result === false ) {
			greenmetrics_log( "Error pruning data: " . $wpdb->last_error, null, 'error' );
			return array(
				'pruned' => 0,
				'error' => true,
			);
		}

		greenmetrics_log( "Pruning completed: {$result} records deleted" );

		return array(
			'pruned' => $result,
			'error' => false,
		);
	}

	/**
	 * Get aggregated stats for a page or all pages.
	 *
	 * @param int|null $page_id The page ID or null for all pages.
	 * @param string   $aggregation_type The aggregation type (daily, weekly, monthly).
	 * @param bool     $force_refresh Whether to force refresh the cache.
	 * @return array The aggregated statistics.
	 */
	public function get_aggregated_stats( $page_id = null, $aggregation_type = 'daily', $force_refresh = false ) {
		global $wpdb;

		// Create a cache key
		$cache_key = 'greenmetrics_aggregated_stats_' . ( $page_id ? $page_id : 'all' ) . '_' . $aggregation_type;

		// Try to get from cache
		if ( ! $force_refresh ) {
			$cached_stats = get_transient( $cache_key );
			if ( false !== $cached_stats ) {
				return $cached_stats;
			}
		}

		// Sanitize table name
		$table_name = esc_sql( $this->aggregated_table_name );

		// Build the query
		$query = "SELECT
			date_start,
			date_end,
			SUM(views) as total_views,
			SUM(total_data_transfer) as total_data_transfer,
			AVG(avg_data_transfer) as avg_data_transfer,
			SUM(total_load_time) as total_load_time,
			AVG(avg_load_time) as avg_load_time,
			SUM(total_requests) as total_requests,
			AVG(avg_requests) as avg_requests,
			SUM(total_carbon_footprint) as total_carbon_footprint,
			AVG(avg_carbon_footprint) as avg_carbon_footprint,
			SUM(total_energy_consumption) as total_energy_consumption,
			AVG(avg_energy_consumption) as avg_energy_consumption,
			AVG(avg_performance_score) as avg_performance_score
		FROM {$table_name}
		WHERE aggregation_type = %s";

		$params = array( $aggregation_type );

		if ( $page_id ) {
			$query .= " AND page_id = %d";
			$params[] = $page_id;
		}

		$query .= " GROUP BY date_start ORDER BY date_start DESC";

		// Execute the query
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is properly prepared with placeholders and parameters
		$results = $wpdb->get_results( $wpdb->prepare( $query, $params ), ARRAY_A );

		if ( ! $results ) {
			return array();
		}

		// Cache the results for 1 hour
		set_transient( $cache_key, $results, HOUR_IN_SECONDS );

		return $results;
	}

	/**
	 * Get the total size of the metrics tables.
	 *
	 * @return array The table sizes in bytes.
	 */
	public function get_table_sizes() {
		global $wpdb;

		$db_name = $wpdb->dbname;

		// Get the size of the main metrics table
		$main_table_size = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(data_length + index_length)
				FROM information_schema.TABLES
				WHERE table_schema = %s
				AND table_name = %s",
				$db_name,
				$this->table_name
			)
		);

		// Get the size of the aggregated metrics table
		$aggregated_table_size = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(data_length + index_length)
				FROM information_schema.TABLES
				WHERE table_schema = %s
				AND table_name = %s",
				$db_name,
				$this->aggregated_table_name
			)
		);

		// Get row counts with proper escaping
		$main_table       = esc_sql( $this->table_name );
		$aggregated_table = esc_sql( $this->aggregated_table_name );

		$main_table_rows       = $wpdb->get_var( "SELECT COUNT(*) FROM {$main_table}" );
		$aggregated_table_rows = $wpdb->get_var( "SELECT COUNT(*) FROM {$aggregated_table}" );

		return array(
			'main_table' => array(
				'size' => $main_table_size ? $main_table_size : 0,
				'rows' => $main_table_rows ? $main_table_rows : 0,
			),
			'aggregated_table' => array(
				'size' => $aggregated_table_size ? $aggregated_table_size : 0,
				'rows' => $aggregated_table_rows ? $aggregated_table_rows : 0,
			),
			'total_size' => ( $main_table_size ? $main_table_size : 0 ) + ( $aggregated_table_size ? $aggregated_table_size : 0 ),
		);
	}

	/**
	 * Format bytes to human-readable format.
	 *
	 * @param int $bytes The size in bytes.
	 * @param int $precision The number of decimal places.
	 * @return string The formatted size.
	 */
	public static function format_bytes( $bytes, $precision = 2 ) {
		return GreenMetrics_Formatter::format_bytes( $bytes, $precision );
	}
}
