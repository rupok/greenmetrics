<?php
/**
 * The REST API functionality of the plugin.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */

namespace GreenMetrics;

/**
 * The REST API functionality of the plugin.
 */
class GreenMetrics_Rest_API {
    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
        
        greenmetrics_log('REST API initialized');
    }

    /**
     * Register REST API routes.
     */
    public function register_routes() {
        register_rest_route('greenmetrics/v1', '/metrics', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_stats'),
            'permission_callback' => '__return_true',
            'args' => array(
                'page_id' => array(
                    'required' => false,
                    'type' => 'integer',
                    'sanitize_callback' => function($param) { return absint($param); }
                )
            )
        ));

        register_rest_route('greenmetrics/v1', '/track', array(
            'methods' => 'POST',
            'callback' => array($this, 'track_page'),
            'permission_callback' => '__return_true',
            'args' => array(
                'page_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => function($param) { return absint($param); }
                ),
                'data_transfer' => array(
                    'required' => true,
                    'type' => 'number',
                    'sanitize_callback' => function($param) { return floatval($param); }
                ),
                'load_time' => array(
                    'required' => true,
                    'type' => 'number',
                    'sanitize_callback' => function($param) { return floatval($param); }
                ),
                'requests' => array(
                    'required' => false,
                    'type' => 'integer',
                    'sanitize_callback' => function($param) { return absint($param); }
                )
            )
        ));
        
        greenmetrics_log('REST routes registered');
    }

    /**
     * Get statistics.
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function get_stats($request) {
        try {
            $page_id = $request->get_param('page_id');
            
            greenmetrics_log('REST: Getting stats for page_id', $page_id ? $page_id : 'all pages');
            
            $tracker = GreenMetrics_Tracker::get_instance();
            $stats = $tracker->get_stats($page_id);

            if (!is_array($stats)) {
                greenmetrics_log('REST: Failed to retrieve statistics', null, 'error');
                return new \WP_Error(
                    'invalid_stats',
                    'Failed to retrieve statistics',
                    array('status' => 500)
                );
            }

            // Extract and validate values from the stats array
            // These values are already validated in the tracker's get_stats method
            $total_views = intval($stats['total_views']);
            $total_carbon_footprint = floatval($stats['total_carbon_footprint']);
            $total_energy_consumption = floatval($stats['total_energy_consumption']);
            $total_data_transfer = floatval($stats['total_data_transfer']);
            $total_requests = intval($stats['total_requests']);
            $avg_performance_score = floatval($stats['avg_performance_score']);

            // Calculate per-view averages only if there are views
            $avg_carbon_footprint = $total_views > 0 ? $total_carbon_footprint / $total_views : 0;
            $avg_energy_consumption = $total_views > 0 ? $total_energy_consumption / $total_views : 0;
            $avg_data_transfer = $total_views > 0 ? $total_data_transfer / $total_views : 0;
            $avg_requests = $total_views > 0 ? $total_requests / $total_views : 0;

            // Convert data transfer from bytes to KB for display
            $avg_data_transfer_kb = $avg_data_transfer / 1024;
            $total_data_transfer_kb = $total_data_transfer / 1024;

            greenmetrics_log('REST: Stats response prepared', [
                'views' => $total_views,
                'carbon' => round($total_carbon_footprint, 2),
                'data' => round($total_data_transfer_kb, 2) . 'KB'
            ]);

            // Format the response to include both total and average metrics
            return rest_ensure_response(array(
                // Totals
                'total_views' => $total_views,
                'total_carbon_footprint' => round($total_carbon_footprint, 2),
                'total_energy_consumption' => round($total_energy_consumption, 4),
                'total_data_transfer' => round($total_data_transfer_kb, 2),
                'total_requests' => $total_requests,
                
                // Averages (per page view)
                'avg_carbon_footprint' => round($avg_carbon_footprint, 2),
                'avg_energy_consumption' => round($avg_energy_consumption, 6),
                'avg_data_transfer' => round($avg_data_transfer_kb, 2),
                'avg_requests' => round($avg_requests, 1),
                'performance_score' => round($avg_performance_score, 2),
                
                // Standard metrics for consistency throughout the codebase
                'carbon_footprint' => round($total_carbon_footprint, 2),
                'energy_consumption' => round($total_energy_consumption, 4),
                'data_transfer' => round($total_data_transfer_kb, 2),
                'requests' => $total_requests
            ));
        } catch (\Exception $e) {
            greenmetrics_log('REST: Error retrieving statistics', $e->getMessage(), 'error');
            return new \WP_Error(
                'stats_error',
                'Error retrieving statistics: ' . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Track page metrics.
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function track_page($request) {
        try {
            // Add detailed logging of what we're doing
            greenmetrics_log('REST: track_page method called', [
                'request_params' => $request->get_params(),
                'method' => $request->get_method(),
                'headers' => $request->get_headers()
            ]);

            $options = get_option('greenmetrics_settings');
            greenmetrics_log('REST: Settings retrieved', $options);

            if (!isset($options['tracking_enabled']) || !$options['tracking_enabled']) {
                greenmetrics_log('REST: Tracking request denied - tracking is disabled', null, 'warning');
                return new \WP_Error(
                    'tracking_disabled',
                    'Tracking is disabled',
                    array('status' => 403)
                );
            }

            // Collect all parameters
            $data = array(
                'page_id' => $request->get_param('page_id'),
                'data_transfer' => $request->get_param('data_transfer'),
                'load_time' => $request->get_param('load_time')
            );
            
            // Add optional requests parameter if present
            if ($request->has_param('requests')) {
                $data['requests'] = $request->get_param('requests');
            }
            
            greenmetrics_log('REST: Full request received', $request->get_params());
            greenmetrics_log('REST: Tracking page', [
                'ID' => $data['page_id'],
                'data' => round($data['data_transfer']/1024, 2) . 'KB',
                'load_time' => round($data['load_time'], 2) . 'ms',
                'requests' => isset($data['requests']) ? $data['requests'] : 'not set'
            ]);

            $tracker = GreenMetrics_Tracker::get_instance();
            greenmetrics_log('REST: About to call handle_track_page');
            $success = $tracker->handle_track_page($data);
            greenmetrics_log('REST: handle_track_page result', ['success' => $success]);

            if (!$success) {
                greenmetrics_log('REST: Failed to track page metrics', null, 'error');
                return new \WP_Error(
                    'tracking_failed',
                    'Failed to track page metrics',
                    array('status' => 500)
                );
            }

            return rest_ensure_response(array('success' => true));
        } catch (\Exception $e) {
            greenmetrics_log('REST: Exception in track_page', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'error');
            return new \WP_Error(
                'tracking_exception',
                'Exception tracking page: ' . $e->getMessage(),
                array('status' => 500)
            );
        }
    }
}