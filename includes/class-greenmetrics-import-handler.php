<?php
/**
 * GreenMetrics Import Handler
 *
 * Handles data import functionality for GreenMetrics.
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
 * GreenMetrics Import Handler Class.
 *
 * This class handles the import of metrics data from CSV or JSON files.
 *
 * @since      1.0.0
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 * @author     Nazmul H. Rupok
 */
class GreenMetrics_Import_Handler {

	/**
	 * The singleton instance of this class.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      GreenMetrics_Import_Handler    $instance    The singleton instance.
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance of this class.
	 *
	 * @since    1.0.0
	 * @return   GreenMetrics_Import_Handler    The singleton instance.
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
	 * @access   private
	 */
	private function __construct() {
		// Private constructor to enforce singleton pattern.
	}

	/**
	 * Import data from a file.
	 *
	 * @since    1.0.0
	 * @param    array    $file    The uploaded file data.
	 * @param    array    $args    Import arguments.
	 * @return   array|WP_Error    Import result or error.
	 */
	public function import_data( $file, $args = array() ) {
		// Default arguments
		$defaults = array(
			'data_type'        => 'raw',       // Data type: 'raw' or 'aggregated'
			'duplicate_action' => 'skip',      // How to handle duplicates: 'skip', 'replace', or 'merge'
		);

		$args = wp_parse_args( $args, $defaults );

		// Validate file
		$validation = $this->validate_import_file( $file );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Determine file format from extension
		$file_path = $file['tmp_name'];
		$file_name = $file['name'];
		$file_ext = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );

		// Process file based on format
		if ( 'csv' === $file_ext ) {
			return $this->import_csv( $file_path, $args );
		} elseif ( 'json' === $file_ext ) {
			return $this->import_json( $file_path, $args );
		} else {
			return new \WP_Error( 'invalid_format', __( 'Invalid file format. Only CSV and JSON files are supported.', 'greenmetrics' ) );
		}
	}

	/**
	 * Validate the import file.
	 *
	 * @since    1.0.0
	 * @param    array    $file    The uploaded file data.
	 * @return   bool|WP_Error     True if valid, WP_Error otherwise.
	 */
	public function validate_import_file( $file ) {
		// Check if file was uploaded
		if ( empty( $file ) || ! isset( $file['tmp_name'] ) || empty( $file['tmp_name'] ) ) {
			return new \WP_Error( 'no_file', __( 'No file was uploaded.', 'greenmetrics' ) );
		}

		// Check file size (limit to 10MB)
		$max_size = 10 * 1024 * 1024; // 10MB
		if ( $file['size'] > $max_size ) {
			return new \WP_Error( 'file_too_large', __( 'The uploaded file is too large. Maximum size is 10MB.', 'greenmetrics' ) );
		}

		// Check file extension
		$file_name = $file['name'];
		$file_ext = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
		if ( ! in_array( $file_ext, array( 'csv', 'json' ), true ) ) {
			return new \WP_Error( 'invalid_extension', __( 'Invalid file extension. Only CSV and JSON files are supported.', 'greenmetrics' ) );
		}

		// Check if file is readable
		if ( ! is_readable( $file['tmp_name'] ) ) {
			return new \WP_Error( 'file_not_readable', __( 'The uploaded file could not be read.', 'greenmetrics' ) );
		}

		return true;
	}

	/**
	 * Import data from a CSV file.
	 *
	 * @since    1.0.0
	 * @param    string    $file_path    Path to the CSV file.
	 * @param    array     $args         Import arguments.
	 * @return   array|WP_Error          Import result or error.
	 */
	private function import_csv( $file_path, $args ) {
		// Open the CSV file
		$handle = fopen( $file_path, 'r' );
		if ( false === $handle ) {
			return new \WP_Error( 'file_open_error', __( 'Could not open the CSV file.', 'greenmetrics' ) );
		}

		// Read the header row
		$headers = fgetcsv( $handle );
		if ( false === $headers ) {
			fclose( $handle );
			return new \WP_Error( 'csv_read_error', __( 'Could not read the CSV file header.', 'greenmetrics' ) );
		}

		// Validate headers based on data type
		$validation = $this->validate_headers( $headers, $args['data_type'] );
		if ( is_wp_error( $validation ) ) {
			fclose( $handle );
			return $validation;
		}

		// Read data rows
		$data = array();
		$row_count = 0;
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			// Skip empty rows
			if ( empty( $row ) || count( array_filter( $row ) ) === 0 ) {
				continue;
			}

			// Convert row to associative array
			$data_row = array();
			foreach ( $headers as $index => $header ) {
				if ( isset( $row[ $index ] ) ) {
					$data_row[ $header ] = $row[ $index ];
				}
			}

			$data[] = $data_row;
			$row_count++;
		}

		fclose( $handle );

		// Check if we have any data
		if ( empty( $data ) ) {
			return new \WP_Error( 'no_data', __( 'No valid data found in the CSV file.', 'greenmetrics' ) );
		}

		// Process the data
		$result = $this->process_data_rows( $data, $args );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %d: number of imported rows */
				__( 'Successfully imported %d records.', 'greenmetrics' ),
				$result['imported']
			),
			'imported' => $result['imported'],
			'skipped' => $result['skipped'],
		);
	}

	/**
	 * Import data from a JSON file.
	 *
	 * @since    1.0.0
	 * @param    string    $file_path    Path to the JSON file.
	 * @param    array     $args         Import arguments.
	 * @return   array|WP_Error          Import result or error.
	 */
	private function import_json( $file_path, $args ) {
		// Read the JSON file
		$json_content = file_get_contents( $file_path );
		if ( false === $json_content ) {
			return new \WP_Error( 'file_read_error', __( 'Could not read the JSON file.', 'greenmetrics' ) );
		}

		// Decode JSON
		$data = json_decode( $json_content, true );
		if ( null === $data ) {
			return new \WP_Error( 'json_decode_error', __( 'Could not decode the JSON file.', 'greenmetrics' ) );
		}

		// Check if we have an array of data
		if ( ! is_array( $data ) || empty( $data ) ) {
			return new \WP_Error( 'invalid_json', __( 'The JSON file does not contain valid data.', 'greenmetrics' ) );
		}

		// Validate the first row to check structure
		$first_row = reset( $data );
		if ( ! is_array( $first_row ) ) {
			return new \WP_Error( 'invalid_json_structure', __( 'The JSON file does not contain a valid data structure.', 'greenmetrics' ) );
		}

		// Validate headers based on data type
		$validation = $this->validate_headers( array_keys( $first_row ), $args['data_type'] );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Process the data
		$result = $this->process_data_rows( $data, $args );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %d: number of imported rows */
				__( 'Successfully imported %d records.', 'greenmetrics' ),
				$result['imported']
			),
			'imported' => $result['imported'],
			'skipped' => $result['skipped'],
		);
	}

	/**
	 * Validate headers based on data type.
	 *
	 * @since    1.0.0
	 * @param    array     $headers     The headers to validate.
	 * @param    string    $data_type   The data type ('raw' or 'aggregated').
	 * @return   bool|WP_Error          True if valid, WP_Error otherwise.
	 */
	private function validate_headers( $headers, $data_type ) {
		// Required fields for raw data
		$raw_required = array( 'page_id', 'data_transfer', 'load_time', 'carbon_footprint' );

		// Required fields for aggregated data
		$aggregated_required = array( 'page_id', 'date_start', 'date_end', 'aggregation_type' );

		// Check required fields based on data type
		$required = ( 'raw' === $data_type ) ? $raw_required : $aggregated_required;

		// Convert headers to lowercase for case-insensitive comparison
		$headers_lower = array_map( 'strtolower', $headers );

		// Check if all required fields are present
		$missing = array();
		foreach ( $required as $field ) {
			if ( ! in_array( strtolower( $field ), $headers_lower, true ) ) {
				$missing[] = $field;
			}
		}

		if ( ! empty( $missing ) ) {
			return new \WP_Error(
				'missing_required_fields',
				sprintf(
					/* translators: %s: comma-separated list of field names */
					__( 'The import file is missing required fields: %s', 'greenmetrics' ),
					implode( ', ', $missing )
				)
			);
		}

		return true;
	}

	/**
	 * Process data rows and insert into database.
	 *
	 * @since    1.0.0
	 * @param    array     $data    The data rows to process.
	 * @param    array     $args    Import arguments.
	 * @return   array|WP_Error     Import result or error.
	 */
	private function process_data_rows( $data, $args ) {
		global $wpdb;

		// Get the appropriate table name based on data type
		$table_name = ( 'raw' === $args['data_type'] )
			? $wpdb->prefix . 'greenmetrics_stats'
			: $wpdb->prefix . 'greenmetrics_aggregated_stats';

		// Check if table exists
		if ( ! GreenMetrics_DB_Helper::table_exists( $table_name ) ) {
			return new \WP_Error( 'table_not_found', __( 'Database table not found.', 'greenmetrics' ) );
		}

		// Start transaction
		$wpdb->query( 'START TRANSACTION' );

		try {
			$imported = 0;
			$skipped = 0;

			foreach ( $data as $row ) {
				// Prepare data for insertion
				$insert_data = $this->prepare_row_data( $row, $args['data_type'] );

				// Skip if preparation failed
				if ( is_wp_error( $insert_data ) ) {
					$skipped++;
					continue;
				}

				// Check for duplicates based on unique fields
				$duplicate = $this->check_for_duplicate( $insert_data, $table_name, $args['data_type'] );

				// Handle based on duplicate action
				if ( $duplicate ) {
					if ( 'skip' === $args['duplicate_action'] ) {
						$skipped++;
						continue;
					} elseif ( 'replace' === $args['duplicate_action'] ) {
						// Delete the existing record
						$this->delete_duplicate( $duplicate, $table_name, $args['data_type'] );
					} elseif ( 'merge' === $args['duplicate_action'] ) {
						// Merge with existing record
						$insert_data = $this->merge_records( $insert_data, $duplicate, $args['data_type'] );
						// Delete the existing record
						$this->delete_duplicate( $duplicate, $table_name, $args['data_type'] );
					}
				}

				// Insert the data
				$result = $wpdb->insert( $table_name, $insert_data );

				if ( false === $result ) {
					throw new \Exception( $wpdb->last_error );
				}

				$imported++;
			}

			// Commit transaction
			$wpdb->query( 'COMMIT' );

			// Clear cache
			$this->clear_cache();

			return array(
				'imported' => $imported,
				'skipped' => $skipped,
			);
		} catch ( \Exception $e ) {
			// Rollback transaction on error
			$wpdb->query( 'ROLLBACK' );

			return new \WP_Error( 'import_error', $e->getMessage() );
		}
	}

	/**
	 * Prepare row data for insertion.
	 *
	 * @since    1.0.0
	 * @param    array     $row         The data row.
	 * @param    string    $data_type   The data type ('raw' or 'aggregated').
	 * @return   array|WP_Error         Prepared data or error.
	 */
	private function prepare_row_data( $row, $data_type ) {
		global $wpdb;

		// Initialize prepared data
		$prepared = array();

		// Get the table name based on data type
		$table_name = ( 'raw' === $data_type )
			? $wpdb->prefix . 'greenmetrics_stats'
			: $wpdb->prefix . 'greenmetrics_aggregated_stats';

		// Get the actual table columns to ensure we only include valid fields
		$table_columns = $this->get_table_columns( $table_name );

		if ( empty( $table_columns ) ) {
			return new \WP_Error( 'table_columns_error', __( 'Could not retrieve table columns.', 'greenmetrics' ) );
		}

		// Common fields to sanitize as integers
		$int_fields = array( 'id', 'page_id', 'views', 'total_requests', 'avg_requests' );

		// Common fields to sanitize as floats
		$float_fields = array(
			'data_transfer', 'load_time', 'requests', 'carbon_footprint', 'energy_consumption', 'performance_score',
			'total_data_transfer', 'avg_data_transfer', 'total_load_time', 'avg_load_time',
			'total_carbon_footprint', 'avg_carbon_footprint', 'total_energy_consumption', 'avg_energy_consumption',
			'avg_performance_score'
		);

		// Common fields to sanitize as dates
		$date_fields = array( 'created_at', 'date_start', 'date_end' );

		// Process each field in the row
		foreach ( $row as $key => $value ) {
			// Skip fields that don't exist in the table
			if ( ! in_array( $key, $table_columns, true ) ) {
				continue;
			}

			// Skip empty values for non-required fields
			if ( '' === $value && ! in_array( $key, array( 'page_id' ), true ) ) {
				continue;
			}

			// Sanitize based on field type
			if ( in_array( $key, $int_fields, true ) ) {
				$prepared[ $key ] = absint( $value );
			} elseif ( in_array( $key, $float_fields, true ) ) {
				$prepared[ $key ] = floatval( $value );
			} elseif ( in_array( $key, $date_fields, true ) ) {
				// Ensure date is in MySQL format
				$timestamp = strtotime( $value );
				if ( false === $timestamp ) {
					return new \WP_Error( 'invalid_date', sprintf(
						/* translators: %s: field name */
						__( 'Invalid date format for field: %s', 'greenmetrics' ),
						$key
					) );
				}
				$prepared[ $key ] = date( 'Y-m-d H:i:s', $timestamp );
			} else {
				// Default sanitization for other fields
				$prepared[ $key ] = sanitize_text_field( $value );
			}
		}

		// Ensure required fields are present
		if ( 'raw' === $data_type ) {
			$required = array( 'page_id', 'data_transfer', 'load_time', 'carbon_footprint' );
		} else {
			$required = array( 'page_id', 'date_start', 'date_end', 'aggregation_type' );
		}

		foreach ( $required as $field ) {
			if ( ! isset( $prepared[ $field ] ) ) {
				return new \WP_Error( 'missing_required_field', sprintf(
					/* translators: %s: field name */
					__( 'Missing required field: %s', 'greenmetrics' ),
					$field
				) );
			}
		}

		// Remove ID field if present (will be auto-generated)
		if ( isset( $prepared['id'] ) ) {
			unset( $prepared['id'] );
		}

		// Ensure created_at is set if not present
		if ( ! isset( $prepared['created_at'] ) ) {
			$prepared['created_at'] = current_time( 'mysql' );
		}

		return $prepared;
	}

	/**
	 * Get table columns.
	 *
	 * @since    1.0.0
	 * @param    string    $table_name    The table name.
	 * @return   array                    Array of column names.
	 */
	private function get_table_columns( $table_name ) {
		global $wpdb;

		// Check if we have cached columns
		static $columns_cache = array();

		if ( isset( $columns_cache[ $table_name ] ) ) {
			return $columns_cache[ $table_name ];
		}

		// Get columns from the database
		$columns = array();
		$results = $wpdb->get_results( "SHOW COLUMNS FROM {$table_name}", ARRAY_A );

		if ( ! empty( $results ) ) {
			foreach ( $results as $column ) {
				$columns[] = $column['Field'];
			}
		}

		// Cache the results
		$columns_cache[ $table_name ] = $columns;

		return $columns;
	}

	/**
	 * Check for duplicate records.
	 *
	 * @since    1.0.0
	 * @param    array     $data        The data to check.
	 * @param    string    $table_name  The table name.
	 * @param    string    $data_type   The data type ('raw' or 'aggregated').
	 * @return   array|false            Duplicate record or false if none found.
	 */
	private function check_for_duplicate( $data, $table_name, $data_type ) {
		global $wpdb;

		// Define unique fields based on data type
		if ( 'raw' === $data_type ) {
			// For raw data, check page_id and created_at (within 1 second)
			$query = $wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE page_id = %d AND created_at BETWEEN DATE_SUB(%s, INTERVAL 1 SECOND) AND DATE_ADD(%s, INTERVAL 1 SECOND) LIMIT 1",
				$data['page_id'],
				$data['created_at'],
				$data['created_at']
			);
		} else {
			// For aggregated data, check page_id, date_start, date_end, and aggregation_type
			$query = $wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE page_id = %d AND date_start = %s AND date_end = %s AND aggregation_type = %s LIMIT 1",
				$data['page_id'],
				$data['date_start'],
				$data['date_end'],
				$data['aggregation_type']
			);
		}

		$result = $wpdb->get_row( $query, ARRAY_A );

		return $result ? $result : false;
	}

	/**
	 * Delete a duplicate record.
	 *
	 * @since    1.0.0
	 * @param    array     $duplicate   The duplicate record.
	 * @param    string    $table_name  The table name.
	 * @param    string    $data_type   The data type ('raw' or 'aggregated').
	 * @return   bool                   True on success, false on failure.
	 */
	private function delete_duplicate( $duplicate, $table_name, $data_type ) {
		global $wpdb;

		return $wpdb->delete( $table_name, array( 'id' => $duplicate['id'] ), array( '%d' ) );
	}

	/**
	 * Merge two records.
	 *
	 * @since    1.0.0
	 * @param    array     $new_data    The new data.
	 * @param    array     $existing    The existing data.
	 * @param    string    $data_type   The data type ('raw' or 'aggregated').
	 * @return   array                  The merged data.
	 */
	private function merge_records( $new_data, $existing, $data_type ) {
		global $wpdb;

		// Get the table name based on data type
		$table_name = ( 'raw' === $data_type )
			? $wpdb->prefix . 'greenmetrics_stats'
			: $wpdb->prefix . 'greenmetrics_aggregated_stats';

		// Get the actual table columns to ensure we only include valid fields
		$table_columns = $this->get_table_columns( $table_name );

		// For raw data, prefer the new data for most fields
		if ( 'raw' === $data_type ) {
			// Start with existing data
			$merged = array();

			// Only include fields that exist in the table
			foreach ( $existing as $key => $value ) {
				if ( in_array( $key, $table_columns, true ) ) {
					$merged[ $key ] = $value;
				}
			}

			// Override with new data for most fields
			foreach ( $new_data as $key => $value ) {
				// Skip ID field and fields that don't exist in the table
				if ( 'id' === $key || ! in_array( $key, $table_columns, true ) ) {
					continue;
				}

				$merged[ $key ] = $value;
			}
		} else {
			// For aggregated data, combine metrics
			$merged = array();

			// Only include fields that exist in the table
			foreach ( $existing as $key => $value ) {
				if ( in_array( $key, $table_columns, true ) ) {
					$merged[ $key ] = $value;
				}
			}

			// Make sure views exists in both records
			$existing_views = isset( $existing['views'] ) ? $existing['views'] : 0;
			$new_views = isset( $new_data['views'] ) ? $new_data['views'] : 0;

			// Add view counts
			$merged['views'] = $existing_views + $new_views;

			// Recalculate averages and totals
			$fields = array(
				'data_transfer' => 'total_data_transfer',
				'load_time' => 'total_load_time',
				'requests' => 'total_requests',
				'carbon_footprint' => 'total_carbon_footprint',
				'energy_consumption' => 'total_energy_consumption',
			);

			foreach ( $fields as $avg_field => $total_field ) {
				// Skip fields that don't exist in the table
				if ( ! in_array( $total_field, $table_columns, true ) || ! in_array( 'avg_' . $avg_field, $table_columns, true ) ) {
					continue;
				}

				// Get existing total value (default to 0 if not set)
				$existing_total = isset( $existing[ $total_field ] ) ? $existing[ $total_field ] : 0;

				// Get new total value (default to 0 if not set)
				$new_total = isset( $new_data[ $total_field ] ) ? $new_data[ $total_field ] : 0;

				// Update totals
				$merged[ $total_field ] = $existing_total + $new_total;

				// Recalculate averages
				if ( $merged['views'] > 0 ) {
					$merged[ 'avg_' . $avg_field ] = $merged[ $total_field ] / $merged['views'];
				} else {
					$merged[ 'avg_' . $avg_field ] = 0;
				}
			}

			// Update performance score (weighted average) if it exists in the table
			if ( in_array( 'avg_performance_score', $table_columns, true ) ) {
				$existing_weight = $existing_views;
				$new_weight = $new_views;
				$total_weight = $existing_weight + $new_weight;

				$existing_score = isset( $existing['avg_performance_score'] ) ? $existing['avg_performance_score'] : 0;
				$new_score = isset( $new_data['avg_performance_score'] ) ? $new_data['avg_performance_score'] : 0;

				if ( $total_weight > 0 ) {
					$merged['avg_performance_score'] = (
						( $existing_score * $existing_weight ) +
						( $new_score * $new_weight )
					) / $total_weight;
				} else {
					$merged['avg_performance_score'] = 0;
				}
			}

			// Use the most recent created_at date if it exists in both records
			if ( isset( $new_data['created_at'] ) && isset( $existing['created_at'] ) ) {
				if ( strtotime( $new_data['created_at'] ) > strtotime( $existing['created_at'] ) ) {
					$merged['created_at'] = $new_data['created_at'];
				} else {
					$merged['created_at'] = $existing['created_at'];
				}
			} elseif ( isset( $new_data['created_at'] ) ) {
				$merged['created_at'] = $new_data['created_at'];
			} elseif ( isset( $existing['created_at'] ) ) {
				$merged['created_at'] = $existing['created_at'];
			} else {
				$merged['created_at'] = current_time( 'mysql' );
			}
		}

		// Ensure ID is preserved
		if ( isset( $existing['id'] ) ) {
			$merged['id'] = $existing['id'];
		}

		return $merged;
	}

	/**
	 * Clear cache after import.
	 *
	 * @since    1.0.0
	 */
	private function clear_cache() {
		// Clear transients related to metrics
		$tracker = GreenMetrics_Tracker::get_instance();
		if ( method_exists( $tracker, 'clear_cache' ) ) {
			$tracker->clear_cache();
		}

		// Delete specific transients
		$transients = array(
			'greenmetrics_stats_',
			'greenmetrics_metrics_by_date_',
		);

		global $wpdb;
		foreach ( $transients as $transient_prefix ) {
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . $transient_prefix ) . '%'
			) );

			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_timeout_' . $transient_prefix ) . '%'
			) );
		}
	}
}
