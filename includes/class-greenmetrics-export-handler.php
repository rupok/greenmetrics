<?php
/**
 * GreenMetrics Export Handler
 *
 * Handles data export functionality for GreenMetrics.
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
 * GreenMetrics Export Handler Class
 *
 * Handles exporting metrics data in various formats.
 *
 * @since      1.0.0
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */
class GreenMetrics_Export_Handler {

	/**
	 * The singleton instance of this class.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      GreenMetrics_Export_Handler    $instance    The singleton instance.
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance of this class.
	 *
	 * @since     1.0.0
	 * @return    GreenMetrics_Export_Handler    The singleton instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 */
	private function __construct() {
		// Private constructor to enforce singleton pattern.
	}

	/**
	 * Export data in the specified format.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Export arguments.
	 * @return   array|WP_Error    Export result or error.
	 */
	public function export_data( $args = array() ) {
		// Default arguments
		$defaults = array(
			'format'           => 'csv',       // Export format: 'csv' or 'json'
			'data_type'        => 'raw',       // Data type: 'raw' or 'aggregated'
			'start_date'       => '',          // Start date for filtering (YYYY-MM-DD)
			'end_date'         => '',          // End date for filtering (YYYY-MM-DD)
			'page_id'          => 0,           // Page ID for filtering (0 for all pages)
			'aggregation_type' => 'daily',     // Aggregation type for aggregated data
			'include_headers'  => true,        // Include headers in CSV export
			'limit'            => 10000,       // Maximum number of records to export
		);

		$args = wp_parse_args( $args, $defaults );

		// Validate arguments
		if ( ! in_array( $args['format'], array( 'csv', 'json' ), true ) ) {
			return new \WP_Error( 'invalid_format', __( 'Invalid export format.', 'greenmetrics' ) );
		}

		if ( ! in_array( $args['data_type'], array( 'raw', 'aggregated' ), true ) ) {
			return new \WP_Error( 'invalid_data_type', __( 'Invalid data type.', 'greenmetrics' ) );
		}

		// Set date range if not provided
		if ( empty( $args['start_date'] ) ) {
			$args['start_date'] = date( 'Y-m-d', strtotime( '-30 days' ) );
		}

		if ( empty( $args['end_date'] ) ) {
			$args['end_date'] = date( 'Y-m-d' );
		}

		// Get data based on type
		if ( 'raw' === $args['data_type'] ) {
			$data = $this->get_raw_data( $args );
		} else {
			$data = $this->get_aggregated_data( $args );
		}

		// Check if data retrieval was successful
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Format data based on requested format
		if ( 'csv' === $args['format'] ) {
			return $this->format_as_csv( $data, $args );
		} else {
			return $this->format_as_json( $data, $args );
		}
	}

	/**
	 * Get raw metrics data.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments.
	 * @return   array|WP_Error    Raw data or error.
	 */
	private function get_raw_data( $args ) {
		global $wpdb;

		// Get table name
		$table_name = $wpdb->prefix . 'greenmetrics_stats';

		// Check if table exists
		if ( ! GreenMetrics_DB_Helper::table_exists( $table_name ) ) {
			return new \WP_Error( 'table_not_found', __( 'Metrics data table not found.', 'greenmetrics' ) );
		}

		// Build query
		$query = "SELECT 
			id,
			page_id,
			data_transfer,
			load_time,
			requests,
			carbon_footprint,
			energy_consumption,
			performance_score,
			created_at
		FROM {$table_name}
		WHERE 1=1";

		$query_args = array();

		// Add date range filter
		if ( ! empty( $args['start_date'] ) ) {
			$query .= " AND created_at >= %s";
			$query_args[] = $args['start_date'] . ' 00:00:00';
		}

		if ( ! empty( $args['end_date'] ) ) {
			$query .= " AND created_at <= %s";
			$query_args[] = $args['end_date'] . ' 23:59:59';
		}

		// Add page filter
		if ( ! empty( $args['page_id'] ) ) {
			$query .= " AND page_id = %d";
			$query_args[] = $args['page_id'];
		}

		// Add order and limit
		$query .= " ORDER BY created_at DESC LIMIT %d";
		$query_args[] = $args['limit'];

		// Prepare and execute query
		$prepared_query = $wpdb->prepare( $query, $query_args );
		$results = $wpdb->get_results( $prepared_query, ARRAY_A );

		// Check for database errors
		if ( $wpdb->last_error ) {
			return new \WP_Error( 'database_error', $wpdb->last_error );
		}

		// Add page titles to results
		$results = $this->add_page_titles( $results );

		return $results;
	}

	/**
	 * Get aggregated metrics data.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments.
	 * @return   array|WP_Error    Aggregated data or error.
	 */
	private function get_aggregated_data( $args ) {
		global $wpdb;

		// Get table name
		$table_name = $wpdb->prefix . 'greenmetrics_aggregated_stats';

		// Check if table exists
		if ( ! GreenMetrics_DB_Helper::table_exists( $table_name ) ) {
			return new \WP_Error( 'table_not_found', __( 'Aggregated metrics data table not found.', 'greenmetrics' ) );
		}

		// Build query
		$query = "SELECT 
			id,
			page_id,
			date_start,
			date_end,
			aggregation_type,
			views,
			total_data_transfer,
			avg_data_transfer,
			total_load_time,
			avg_load_time,
			total_requests,
			avg_requests,
			total_carbon_footprint,
			avg_carbon_footprint,
			total_energy_consumption,
			avg_energy_consumption,
			avg_performance_score,
			created_at
		FROM {$table_name}
		WHERE 1=1";

		$query_args = array();

		// Add aggregation type filter
		if ( ! empty( $args['aggregation_type'] ) ) {
			$query .= " AND aggregation_type = %s";
			$query_args[] = $args['aggregation_type'];
		}

		// Add date range filter
		if ( ! empty( $args['start_date'] ) ) {
			$query .= " AND date_start >= %s";
			$query_args[] = $args['start_date'] . ' 00:00:00';
		}

		if ( ! empty( $args['end_date'] ) ) {
			$query .= " AND date_end <= %s";
			$query_args[] = $args['end_date'] . ' 23:59:59';
		}

		// Add page filter
		if ( ! empty( $args['page_id'] ) ) {
			$query .= " AND page_id = %d";
			$query_args[] = $args['page_id'];
		}

		// Add order and limit
		$query .= " ORDER BY date_start DESC LIMIT %d";
		$query_args[] = $args['limit'];

		// Prepare and execute query
		$prepared_query = $wpdb->prepare( $query, $query_args );
		$results = $wpdb->get_results( $prepared_query, ARRAY_A );

		// Check for database errors
		if ( $wpdb->last_error ) {
			return new \WP_Error( 'database_error', $wpdb->last_error );
		}

		// Add page titles to results
		$results = $this->add_page_titles( $results );

		return $results;
	}

	/**
	 * Add page titles to results.
	 *
	 * @since    1.0.0
	 * @param    array    $results    Query results.
	 * @return   array    Results with page titles.
	 */
	private function add_page_titles( $results ) {
		if ( empty( $results ) ) {
			return array();
		}

		// Get unique page IDs
		$page_ids = array_unique( wp_list_pluck( $results, 'page_id' ) );

		// Get page titles
		$page_titles = array();
		foreach ( $page_ids as $page_id ) {
			$page_titles[ $page_id ] = get_the_title( $page_id );
			
			// If no title, use the permalink or "Unknown"
			if ( empty( $page_titles[ $page_id ] ) ) {
				$permalink = get_permalink( $page_id );
				$page_titles[ $page_id ] = $permalink ? $permalink : __( 'Unknown', 'greenmetrics' );
			}
		}

		// Add page titles to results
		foreach ( $results as &$result ) {
			$result['page_title'] = isset( $page_titles[ $result['page_id'] ] ) ? $page_titles[ $result['page_id'] ] : __( 'Unknown', 'greenmetrics' );
		}

		return $results;
	}

	/**
	 * Format data as CSV.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Data to format.
	 * @param    array    $args    Export arguments.
	 * @return   array    Formatted data.
	 */
	private function format_as_csv( $data, $args ) {
		if ( empty( $data ) ) {
			return array(
				'content' => '',
				'filename' => 'greenmetrics-export-empty.csv',
				'type' => 'text/csv',
			);
		}

		// Start output buffer
		ob_start();

		// Create a file pointer
		$output = fopen( 'php://output', 'w' );

		// Add headers if requested
		if ( $args['include_headers'] ) {
			fputcsv( $output, array_keys( $data[0] ) );
		}

		// Add data rows
		foreach ( $data as $row ) {
			fputcsv( $output, $row );
		}

		// Get buffer contents and clean buffer
		$content = ob_get_clean();

		// Generate filename
		$date_suffix = date( 'Y-m-d' );
		$type_suffix = $args['data_type'];
		$filename = "greenmetrics-{$type_suffix}-{$date_suffix}.csv";

		return array(
			'content' => $content,
			'filename' => $filename,
			'type' => 'text/csv',
		);
	}

	/**
	 * Format data as JSON.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Data to format.
	 * @param    array    $args    Export arguments.
	 * @return   array    Formatted data.
	 */
	private function format_as_json( $data, $args ) {
		// Generate filename
		$date_suffix = date( 'Y-m-d' );
		$type_suffix = $args['data_type'];
		$filename = "greenmetrics-{$type_suffix}-{$date_suffix}.json";

		// Format JSON with pretty print
		$content = wp_json_encode( $data, JSON_PRETTY_PRINT );

		return array(
			'content' => $content,
			'filename' => $filename,
			'type' => 'application/json',
		);
	}

	/**
	 * Stream file download to browser.
	 *
	 * @since    1.0.0
	 * @param    array    $file    File data.
	 */
	public function stream_download( $file ) {
		// Set headers for download
		header( 'Content-Type: ' . $file['type'] );
		header( 'Content-Disposition: attachment; filename="' . $file['filename'] . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Output file content
		echo $file['content'];
		exit;
	}
}
