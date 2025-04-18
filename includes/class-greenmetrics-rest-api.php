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
        register_rest_route('greenmetrics/v1', '/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_stats'),
            'permission_callback' => array($this, 'check_permission'),
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
     * Check if user has permission to access the API.
     *
     * @return bool Whether the user has permission.
     */
    public function check_permission() {
        return current_user_can('manage_options');
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

        return rest_ensure_response($stats);
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