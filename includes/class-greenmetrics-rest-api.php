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
        $page_id = $request->get_param('page_id');
        $tracker = GreenMetrics_Tracker::get_instance();
        $stats = $tracker->get_stats($page_id);

        // Get total stats for additional metrics
        $total_stats = GreenMetrics_Tracker::get_total_stats();

        // Calculate metrics based on actual data
        $data_transfer = isset($stats['avg_data_transfer']) ? floatval($stats['avg_data_transfer']) : 0;
        $load_time = isset($stats['avg_load_time']) ? floatval($stats['avg_load_time']) : 0;
        $requests = isset($total_stats['total_requests']) ? intval($total_stats['total_requests']) : 0;

        // Calculate CO2 emissions and energy consumption
        $settings = get_option('greenmetrics_settings', array(
            'carbon_intensity' => 0.475, // Default carbon intensity factor (kg CO2/kWh)
            'energy_per_byte' => 0.000000000072 // Default energy per byte (kWh/byte)
        ));

        $energy_consumption = $data_transfer * $settings['energy_per_byte'];
        $co2_emissions = $energy_consumption * $settings['carbon_intensity'];

        // Calculate performance score
        $performance_score = 100;
        if ($load_time > 0) {
            $performance_score = max(0, 100 - ($load_time * 10));
        }

        return rest_ensure_response(array(
            'co2_emissions' => round($co2_emissions, 2),
            'energy_consumption' => round($energy_consumption, 2),
            'data_transfer' => round($data_transfer, 2),
            'requests' => $requests,
            'performance_score' => round($performance_score)
        ));
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