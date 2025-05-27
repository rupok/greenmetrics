<?php
/**
 * The REST API functionality of the plugin.
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
 * The REST API functionality of the plugin.
 */
class GreenMetrics_Rest_API {
	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

		greenmetrics_log( 'REST API initialized' );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		register_rest_route(
			'greenmetrics/v1',
			'/metrics',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_stats' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args'                => array(
					'page_id' => array(
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => function ( $param ) {
							return absint( $param ); },
					),
					'force_refresh' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => 'false',
					),
				),
			)
		);

		// Add export endpoint
		register_rest_route(
			'greenmetrics/v1',
			'/export',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'export_data' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args'                => array(
					'format' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => 'csv',
						'enum'              => array( 'csv', 'json', 'pdf' ),
					),
					'data_type' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => 'raw',
						'enum'              => array( 'raw', 'aggregated' ),
					),
					'start_date' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'end_date' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'page_id' => array(
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => function ( $param ) {
							return absint( $param ); },
					),
					'aggregation_type' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => 'daily',
						'enum'              => array( 'daily', 'weekly', 'monthly' ),
					),
					'download' => array(
						'required'          => false,
						'type'              => 'boolean',
						'default'           => true,
					),
				),
			)
		);

		// Add import endpoint
		register_rest_route(
			'greenmetrics/v1',
			'/import',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'import_data' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args'                => array(
					'data_type' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => 'raw',
						'enum'              => array( 'raw', 'aggregated' ),
					),
					'duplicate_action' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => 'skip',
						'enum'              => array( 'skip', 'replace', 'merge' ),
					),
				),
			)
		);

		// Add a new endpoint for metrics by date range
		register_rest_route(
			'greenmetrics/v1',
			'/metrics-by-date',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_metrics_by_date' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args'                => array(
					'start_date' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'end_date'   => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'interval'   => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => 'day',
					),
					'force_refresh' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => 'false',
					),
				),
			)
		);

		register_rest_route(
			'greenmetrics/v1',
			'/track',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'track_page' ),
				'permission_callback' => array( $this, 'check_tracking_permission' ),
				'args'                => array(
					'page_id'       => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => function ( $param ) {
							return absint( $param ); },
					),
					'data_transfer' => array(
						'required'          => true,
						'type'              => 'number',
						'sanitize_callback' => function ( $param ) {
							return floatval( $param ); },
					),
					'load_time'     => array(
						'required'          => true,
						'type'              => 'number',
						'sanitize_callback' => function ( $param ) {
							return floatval( $param ); },
					),
					'requests'      => array(
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => function ( $param ) {
							return absint( $param ); },
					),
				),
			)
		);

		// REST routes registered
	}

	/**
	 * Permission callback to check if the user can manage options.
	 *
	 * @return bool True if the user has 'manage_options' capability.
	 */
	public function check_admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Permission callback to verify the nonce for tracking requests.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return bool|\WP_Error True if the nonce is valid, WP_Error otherwise.
	 */
	public function check_tracking_permission( $request ) {
		// Try multiple possible header variations to be more robust
		$nonce = $request->get_header( 'X-WP-Nonce' );

		// If not found, try lowercase version (which is what get_header might convert it to)
		if ( ! $nonce ) {
			$nonce = $request->get_header( 'x-wp-nonce' );
		}

		// If still not found, try with underscores (PHP converts hyphens to underscores in $_SERVER)
		if ( ! $nonce ) {
			$nonce = $request->get_header( 'x_wp_nonce' );
		}

		// If no nonce found in any of the expected headers
		if ( ! $nonce ) {
			return new \WP_Error(
				'rest_missing_nonce',
				esc_html__( 'Security verification failed: Nonce is missing. Please refresh the page and try again.', 'greenmetrics' ),
				array( 'status' => 401 )
			);
		}

		// Verify the nonce
		$result = wp_verify_nonce( $nonce, 'wp_rest' );
		if ( ! $result ) {
			return new \WP_Error(
				'rest_invalid_nonce',
				esc_html__( 'Security verification failed: Invalid nonce. Please refresh the page and try again.', 'greenmetrics' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Get statistics.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response The response object.
	 */
	public function get_stats( $request ) {
		try {
			$page_id = $request->get_param( 'page_id' );
			$force_refresh = $request->get_param( 'force_refresh' ) === 'true';

			$tracker = GreenMetrics_Tracker::get_instance();
			$stats   = $tracker->get_stats( $page_id, $force_refresh );

			if ( ! is_array( $stats ) ) {
				greenmetrics_log( 'REST: Failed to retrieve statistics', null, 'error' );
				return GreenMetrics_Error_Handler::create_error(
					'invalid_stats',
					'Failed to retrieve statistics',
					array(),
					500
				);
			}

			// Extract and validate values from the stats array
			// These values are already validated in the tracker's get_stats method
			$total_views              = intval( $stats['total_views'] );
			$total_carbon_footprint   = floatval( $stats['total_carbon_footprint'] );
			$total_energy_consumption = floatval( $stats['total_energy_consumption'] );
			$total_data_transfer      = floatval( $stats['total_data_transfer'] );
			$total_requests           = intval( $stats['total_requests'] );
			$avg_performance_score    = floatval( $stats['avg_performance_score'] );
			$avg_load_time            = floatval( $stats['avg_load_time'] );
			$median_load_time         = isset( $stats['median_load_time'] ) ? floatval( $stats['median_load_time'] ) : $avg_load_time;

			// Calculate per-view averages only if there are views
			$avg_carbon_footprint   = $total_views > 0 ? $total_carbon_footprint / $total_views : 0;
			$avg_energy_consumption = $total_views > 0 ? $total_energy_consumption / $total_views : 0;
			$avg_data_transfer      = $total_views > 0 ? $total_data_transfer / $total_views : 0;
			$avg_requests           = $total_views > 0 ? $total_requests / $total_views : 0;

			// Convert data transfer from bytes to KB for display
			$avg_data_transfer_kb   = $avg_data_transfer / 1024;
			$total_data_transfer_kb = $total_data_transfer / 1024;

			// Get page-specific metrics
			$pages = $tracker->get_page_metrics($force_refresh);

			// Format the response to include both total and average metrics
			$response = array(
				// Totals
				'total_views'              => $total_views,
				'total_carbon_footprint'   => round( $total_carbon_footprint, 2 ),
				'total_energy_consumption' => round( $total_energy_consumption, 4 ),
				'total_data_transfer'      => round( $total_data_transfer_kb, 2 ),
				'total_requests'           => $total_requests,

				// Averages (per page view)
				'avg_carbon_footprint'     => round( $avg_carbon_footprint, 2 ),
				'avg_energy_consumption'   => round( $avg_energy_consumption, 6 ),
				'avg_data_transfer'        => round( $avg_data_transfer_kb, 2 ),
				'avg_requests'             => round( $avg_requests, 1 ),
				'avg_load_time'            => round( $avg_load_time, 3 ),
				'median_load_time'         => round( $median_load_time, 3 ),
				'performance_score'        => round( $avg_performance_score, 2 ),

				// Standard metrics for consistency throughout the codebase
				'carbon_footprint'         => round( $total_carbon_footprint, 2 ),
				'energy_consumption'       => round( $total_energy_consumption, 4 ),
				'data_transfer'            => round( $total_data_transfer_kb, 2 ),
				'requests'                 => $total_requests,

				// Page-specific metrics
				'pages'                    => $pages,
			);

			return rest_ensure_response($response);
		} catch ( \Exception $e ) {
			greenmetrics_log( 'REST: Error retrieving statistics', $e->getMessage(), 'error' );
			return GreenMetrics_Error_Handler::handle_exception(
				$e,
				'stats_error',
				'Error retrieving statistics: ' . $e->getMessage(),
				500
			);
		}
	}

	/**
	 * Track page metrics.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response The response object.
	 */
	public function track_page( $request ) {
		try {
			$options = get_option( 'greenmetrics_settings' );

			if ( ! isset( $options['tracking_enabled'] ) || ! $options['tracking_enabled'] ) {
				return GreenMetrics_Error_Handler::create_error(
					'tracking_disabled',
					'Tracking is disabled',
					array(),
					403
				);
			}

			// Collect all parameters
			$data = array(
				'page_id'       => $request->get_param( 'page_id' ),
				'data_transfer' => $request->get_param( 'data_transfer' ),
				'load_time'     => $request->get_param( 'load_time' ),
			);

			// Add optional requests parameter if present
			if ( $request->has_param( 'requests' ) ) {
				$data['requests'] = $request->get_param( 'requests' );
			}

			$tracker = GreenMetrics_Tracker::get_instance();
			$success = $tracker->handle_track_page( $data );

			if ( ! $success ) {
				return GreenMetrics_Error_Handler::create_error(
					'tracking_failed',
					'Failed to track page metrics',
					array(),
					500
				);
			}

			return rest_ensure_response( GreenMetrics_Error_Handler::success( true ) );
		} catch ( \Exception $e ) {
			greenmetrics_log(
				'REST: Exception in track_page',
				array(
					'message' => $e->getMessage(),
					'trace'   => $e->getTraceAsString(),
				),
				'error'
			);
			return GreenMetrics_Error_Handler::handle_exception(
				$e,
				'tracking_exception',
				'Exception tracking page: ' . $e->getMessage(),
				500
			);
		}
	}

	/**
	 * Get metrics data by date range.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response The response object.
	 */
	public function get_metrics_by_date( $request ) {
		try {
			$start_date = $request->get_param( 'start_date' );
			$end_date   = $request->get_param( 'end_date' );
			$interval   = $request->get_param( 'interval' ) ?: 'day';

			// Get the tracker instance
			$tracker = GreenMetrics_Tracker::get_instance();

			// Check if we should force refresh the cache
			$force_refresh = $request->get_param( 'force_refresh' ) === 'true';



			// Get metrics by date range with improved caching
			$metrics = $tracker->get_metrics_by_date_range( $start_date, $end_date, $interval, $force_refresh );

			if ( ! is_array( $metrics ) ) {
				greenmetrics_log( 'REST API: Invalid metrics returned from tracker', array(
					'metrics' => $metrics,
					'is_array' => is_array( $metrics )
				), 'error' );

				return GreenMetrics_Error_Handler::create_error(
					'invalid_metrics',
					'Failed to retrieve metrics by date range',
					array(),
					500
				);
			}



			return rest_ensure_response( $metrics );
		} catch ( \Exception $e ) {
			greenmetrics_log( 'REST: Error retrieving metrics by date range', $e->getMessage(), 'error' );
			return GreenMetrics_Error_Handler::handle_exception(
				$e,
				'metrics_error',
				'Error retrieving metrics by date range: ' . $e->getMessage(),
				500
			);
		}
	}

	/**
	 * Import data.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error The response object or error.
	 */
	public function import_data( $request ) {
		try {
			// Verify that the request is properly authenticated
			if ( ! current_user_can( 'manage_options' ) ) {
				return GreenMetrics_Error_Handler::create_error(
					'permission_denied',
					__( 'You do not have permission to import data.', 'greenmetrics' ),
					array(),
					403
				);
			}

			// Check if file was uploaded
			if ( empty( $_FILES ) || ! isset( $_FILES['import_file'] ) ) {
				greenmetrics_log( 'REST: Import error - No file uploaded', $_FILES, 'error' );
				return GreenMetrics_Error_Handler::create_error(
					'no_file',
					__( 'No file was uploaded.', 'greenmetrics' ),
					array(),
					400
				);
			}

			// Check for file upload errors
			if ( isset( $_FILES['import_file']['error'] ) && $_FILES['import_file']['error'] !== UPLOAD_ERR_OK ) {
				$error_code = isset( $_FILES['import_file']['error'] ) ? intval( $_FILES['import_file']['error'] ) : 0;
				$error_message = $this->get_file_upload_error_message( $error_code );
				greenmetrics_log( 'REST: Import error - File upload error', array(
					'error_code' => $error_code,
					'error_message' => $error_message
				), 'error' );

				return GreenMetrics_Error_Handler::create_error(
					'file_upload_error',
					$error_message,
					array(),
					400
				);
			}

			// Get import parameters
			$data_type = $request->get_param( 'data_type' );
			$duplicate_action = $request->get_param( 'duplicate_action' );

			// Prepare import arguments
			$args = array(
				'data_type' => $data_type,
				'duplicate_action' => $duplicate_action,
			);

			// Get import handler instance
			$import_handler = GreenMetrics_Import_Handler::get_instance();

			// Sanitize and validate the uploaded file data
			$sanitized_file = array();
			if ( isset( $_FILES['import_file'] ) ) {
				// Only copy the necessary fields and sanitize them
				$sanitized_file['name'] = isset( $_FILES['import_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['import_file']['name'] ) ) : '';
				$sanitized_file['type'] = isset( $_FILES['import_file']['type'] ) ? sanitize_text_field( wp_unslash( $_FILES['import_file']['type'] ) ) : '';
				$sanitized_file['tmp_name'] = isset( $_FILES['import_file']['tmp_name'] ) ? sanitize_text_field( wp_unslash( $_FILES['import_file']['tmp_name'] ) ) : '';
				$sanitized_file['error'] = isset( $_FILES['import_file']['error'] ) ? intval( $_FILES['import_file']['error'] ) : 0;
				$sanitized_file['size'] = isset( $_FILES['import_file']['size'] ) ? intval( $_FILES['import_file']['size'] ) : 0;
			}

			// Import data
			$result = $import_handler->import_data( $sanitized_file, $args );

			// Check for errors
			if ( is_wp_error( $result ) ) {
				greenmetrics_log( 'REST: Import error - Import handler error', array(
					'error_code' => $result->get_error_code(),
					'error_message' => $result->get_error_message()
				), 'error' );

				return GreenMetrics_Error_Handler::create_error(
					$result->get_error_code(),
					$result->get_error_message(),
					array(),
					400
				);
			}

			// Return success response
			return rest_ensure_response( array(
				'success' => true,
				'message' => $result['message'],
				'data' => $result,
			) );
		} catch ( \Exception $e ) {
			greenmetrics_log( 'REST: Import error - Exception', $e->getMessage(), 'error' );
			return GreenMetrics_Error_Handler::handle_exception(
				$e,
				'import_error',
				'Error importing data: ' . $e->getMessage(),
				500
			);
		}
	}

	/**
	 * Get file upload error message.
	 *
	 * @param int $error_code The error code.
	 * @return string The error message.
	 */
	private function get_file_upload_error_message( $error_code ) {
		switch ( $error_code ) {
			case UPLOAD_ERR_INI_SIZE:
				return __( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'greenmetrics' );
			case UPLOAD_ERR_FORM_SIZE:
				return __( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', 'greenmetrics' );
			case UPLOAD_ERR_PARTIAL:
				return __( 'The uploaded file was only partially uploaded.', 'greenmetrics' );
			case UPLOAD_ERR_NO_FILE:
				return __( 'No file was uploaded.', 'greenmetrics' );
			case UPLOAD_ERR_NO_TMP_DIR:
				return __( 'Missing a temporary folder.', 'greenmetrics' );
			case UPLOAD_ERR_CANT_WRITE:
				return __( 'Failed to write file to disk.', 'greenmetrics' );
			case UPLOAD_ERR_EXTENSION:
				return __( 'A PHP extension stopped the file upload.', 'greenmetrics' );
			default:
				return __( 'Unknown upload error.', 'greenmetrics' );
		}
	}

	/**
	 * Export data.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error The response object or error.
	 */
	public function export_data( $request ) {
		try {
			// Verify that the request is properly authenticated
			if ( ! current_user_can( 'manage_options' ) ) {
				return GreenMetrics_Error_Handler::create_error(
					'permission_denied',
					__( 'You do not have permission to export data.', 'greenmetrics' ),
					array(),
					403
				);
			}

			// Get export parameters
			$format = $request->get_param( 'format' );
			$data_type = $request->get_param( 'data_type' );
			$start_date = $request->get_param( 'start_date' );
			$end_date = $request->get_param( 'end_date' );
			$page_id = $request->get_param( 'page_id' );
			$aggregation_type = $request->get_param( 'aggregation_type' );
			$download = $request->get_param( 'download' );

			// Prepare export arguments
			$args = array(
				'format' => $format,
				'data_type' => $data_type,
				'start_date' => $start_date,
				'end_date' => $end_date,
				'page_id' => $page_id,
				'aggregation_type' => $aggregation_type,
			);

			// Get export handler instance
			$export_handler = GreenMetrics_Export_Handler::get_instance();

			// Export data
			$result = $export_handler->export_data( $args );

			// Check for errors
			if ( is_wp_error( $result ) ) {
				return GreenMetrics_Error_Handler::create_error(
					$result->get_error_code(),
					$result->get_error_message(),
					array(),
					500
				);
			}

			// If download is requested, stream the file
			if ( $download ) {
				$export_handler->stream_download( $result );
				exit; // This will end the request
			}

			// Otherwise, return the result as JSON
			return rest_ensure_response( array(
				'success' => true,
				'data' => $result,
			) );
		} catch ( \Exception $e ) {
			greenmetrics_log( 'REST: Export error', $e->getMessage(), 'error' );
			return GreenMetrics_Error_Handler::handle_exception(
				$e,
				'export_error',
				'Error exporting data: ' . $e->getMessage(),
				500
			);
		}
	}
}
