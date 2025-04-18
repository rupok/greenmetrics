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

            // Format the response to match what the JavaScript expects
            return rest_ensure_response(array(
                'total_views' => intval($stats['total_views']),
                'avg_data_transfer' => round(floatval($stats['avg_data_transfer']), 2),
                'min_data_transfer' => 0, // These could be added to the tracker query if needed
                'max_data_transfer' => 0,
                'avg_load_time' => round(floatval($stats['avg_load_time']), 2),
                'min_load_time' => 0,
                'max_load_time' => 0,
                'co2_emissions' => round(floatval($stats['avg_carbon_footprint']), 2),
                'energy_consumption' => round(floatval($stats['avg_energy_consumption']), 2),
                'requests' => intval($stats['avg_requests']),
                'performance_score' => round(floatval($stats['avg_performance_score']))
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