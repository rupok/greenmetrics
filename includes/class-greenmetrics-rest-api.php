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

		greenmetrics_log( 'REST routes registered' );
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

		// Log the headers for debugging in development environments
		if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
			greenmetrics_log( 'REST API Headers', $request->get_headers(), 'info' );
		}

		// If no nonce found in any of the expected headers
		if ( ! $nonce ) {
			greenmetrics_log( 'REST API Nonce Verification Failed: No nonce provided', null, 'warning' );
			return new \WP_Error(
				'rest_missing_nonce',
				esc_html__( 'Security verification failed: Nonce is missing. Please refresh the page and try again.', 'greenmetrics' ),
				array( 'status' => 401 )
			);
		}

		// Verify the nonce
		$result = wp_verify_nonce( $nonce, 'wp_rest' );
		if ( ! $result ) {
			greenmetrics_log( 'REST API Nonce Verification Failed: Invalid nonce', array( 'provided_nonce' => $nonce ), 'warning' );
			return new \WP_Error(
				'rest_invalid_nonce',
				esc_html__( 'Security verification failed: Invalid nonce. Please refresh the page and try again.', 'greenmetrics' ),
				array( 'status' => 403 )
			);
		}

		// Nonce verification successful
		greenmetrics_log( 'REST API Nonce Verification Successful', null, 'info' );
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

			greenmetrics_log( 'REST: Getting stats for page_id', $page_id ? $page_id : 'all pages' );
			greenmetrics_log( 'REST: Force refresh', $force_refresh ? 'yes' : 'no' );

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

			greenmetrics_log(
				'REST: Stats response prepared',
				array(
					'views'  => $total_views,
					'carbon' => round( $total_carbon_footprint, 2 ),
					'data'   => round( $total_data_transfer_kb, 2 ) . 'KB',
				)
			);

			// Format the response to include both total and average metrics
			return rest_ensure_response(
				array(
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
				)
			);
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
			// Add detailed logging of what we're doing
			greenmetrics_log(
				'REST: track_page method called',
				array(
					'request_params' => $request->get_params(),
					'method'         => $request->get_method(),
					'headers'        => $request->get_headers(),
				)
			);

			$options = get_option( 'greenmetrics_settings' );
			greenmetrics_log( 'REST: Settings retrieved', $options );

			if ( ! isset( $options['tracking_enabled'] ) || ! $options['tracking_enabled'] ) {
				greenmetrics_log( 'REST: Tracking request denied - tracking is disabled', null, 'warning' );
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

			greenmetrics_log( 'REST: Full request received', $request->get_params() );
			greenmetrics_log(
				'REST: Tracking page',
				array(
					'ID'        => $data['page_id'],
					'data'      => round( $data['data_transfer'] / 1024, 2 ) . 'KB',
					'load_time' => round( $data['load_time'], 2 ) . 'ms',
					'requests'  => isset( $data['requests'] ) ? $data['requests'] : 'not set',
				)
			);

			$tracker = GreenMetrics_Tracker::get_instance();
			greenmetrics_log( 'REST: About to call handle_track_page' );
			$success = $tracker->handle_track_page( $data );
			greenmetrics_log( 'REST: handle_track_page result', array( 'success' => $success ) );

			if ( ! $success ) {
				greenmetrics_log( 'REST: Failed to track page metrics', null, 'error' );
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

			greenmetrics_log(
				'REST: Getting metrics by date range',
				array(
					'start'    => $start_date,
					'end'      => $end_date,
					'interval' => $interval,
				)
			);

			// Get the tracker instance
			$tracker = GreenMetrics_Tracker::get_instance();

			// Check if we should force refresh the cache
			$force_refresh = $request->get_param( 'force_refresh' ) === 'true';

			// Get metrics by date range with improved caching
			$metrics = $tracker->get_metrics_by_date_range( $start_date, $end_date, $interval, $force_refresh );

			if ( ! $metrics || ! is_array( $metrics ) ) {
				greenmetrics_log( 'REST: Failed to retrieve metrics by date range', null, 'error' );
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
}
