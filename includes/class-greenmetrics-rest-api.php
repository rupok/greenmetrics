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
                    'sanitize_callback' => 'absint'
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
                    'sanitize_callback' => 'absint'
                ),
                'data_transfer' => array(
                    'required' => true,
                    'type' => 'number',
                    'sanitize_callback' => 'floatval'
                ),
                'load_time' => array(
                    'required' => true,
                    'type' => 'number',
                    'sanitize_callback' => 'floatval'
                )
            )
        ));
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
            $tracker = GreenMetrics_Tracker::get_instance();
            $stats = $tracker->get_stats($page_id);

            if (!is_array($stats)) {
                return new \WP_Error(
                    'invalid_stats',
                    'Failed to retrieve statistics',
                    array('status' => 500)
                );
            }

            // Make sure we have the necessary values
            $total_views = intval($stats['total_views']);
            $total_carbon_footprint = floatval($stats['total_carbon_footprint']);
            $total_energy_consumption = floatval($stats['total_energy_consumption']);
            $total_data_transfer = floatval($stats['total_data_transfer']);
            $total_requests = intval($stats['total_requests']);
            $avg_performance_score = floatval($stats['avg_performance_score']);

            // Ensure performance score is within bounds (0-100)
            if ($avg_performance_score > 100 || $avg_performance_score < 0) {
                $avg_performance_score = 0;
                if ($stats['avg_load_time'] > 0) {
                    // Calculate a reasonable performance score based on load time
                    $avg_performance_score = max(0, min(100, 100 - ($stats['avg_load_time'] * 10)));
                }
            }

            // Calculate averages per page view (if there are views)
            $avg_carbon_footprint = $total_views > 0 ? $total_carbon_footprint / $total_views : 0;
            $avg_energy_consumption = $total_views > 0 ? $total_energy_consumption / $total_views : 0;
            $avg_data_transfer = $total_views > 0 ? $total_data_transfer / $total_views : 0;
            $avg_requests = $total_views > 0 ? $total_requests / $total_views : 0;

            // Convert data transfer from bytes to KB
            $avg_data_transfer_kb = $avg_data_transfer / 1024;
            $total_data_transfer_kb = $total_data_transfer / 1024;

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
                'performance_score' => round($avg_performance_score),
                
                // For backwards compatibility with existing JS
                'co2_emissions' => round($total_carbon_footprint, 2),
                'energy_consumption' => round($total_energy_consumption, 4),
                'requests' => $total_requests
            ));
        } catch (\Exception $e) {
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
        $options = get_option('greenmetrics_settings');
        if (!isset($options['tracking_enabled']) || !$options['tracking_enabled']) {
            return new \WP_Error(
                'tracking_disabled',
                'Tracking is disabled',
                array('status' => 403)
            );
        }

        $data = array(
            'page_id' => $request->get_param('page_id'),
            'data_transfer' => $request->get_param('data_transfer'),
            'load_time' => $request->get_param('load_time')
        );

        $tracker = GreenMetrics_Tracker::get_instance();
        $success = $tracker->handle_track_page($data);

        if (!$success) {
            return new \WP_Error(
                'tracking_failed',
                'Failed to track page metrics',
                array('status' => 500)
            );
        }

        return rest_ensure_response(array('success' => true));
    }
}