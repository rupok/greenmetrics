<?php
/**
 * The tracker functionality of the plugin.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */

namespace GreenMetrics;

/**
 * The tracker functionality of the plugin.
 */
class GreenMetrics_Tracker {
    /**
     * The single instance of the class.
     *
     * @var GreenMetrics_Tracker
     */
    private static $instance = null;

    /**
     * The table name for metrics.
     *
     * @var string
     */
    private $table_name;

    /**
     * Initialize the class and set its properties.
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'greenmetrics_metrics';
        
        add_action('wp_footer', array($this, 'inject_tracking_script'));
        add_action('wp_ajax_greenmetrics_track_page', array($this, 'handle_track_page'));
        add_action('wp_ajax_nopriv_greenmetrics_track_page', array($this, 'handle_track_page'));
    }

    /**
     * Get the singleton instance.
     *
     * @return GreenMetrics_Tracker
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inject the tracking script into the page footer.
     */
    public function inject_tracking_script() {
        if (!$this->is_tracking_enabled()) {
            return;
        }

        if (!is_admin()) {
            wp_enqueue_script(
                'greenmetrics-tracking',
                GREENMETRICS_PLUGIN_URL . 'public/js/greenmetrics-tracking.js',
                array('jquery'),
                GREENMETRICS_VERSION,
                true
            );
            
            $settings = get_option('greenmetrics_settings', array());
            
            wp_localize_script('greenmetrics-tracking', 'greenmetricsTracking', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('greenmetrics_track_page'),
                'page_id' => get_the_ID(),
                'tracking_enabled' => isset($settings['tracking_enabled']) ? $settings['tracking_enabled'] : 1
            ));
        }
    }

    /**
     * Handle tracking request.
     *
     * @param array $data The tracking data.
     * @return bool Whether the tracking was successful.
     */
    public function handle_track_page($data) {
        if (!isset($data['page_id'], $data['data_transfer'], $data['load_time'])) {
            return false;
        }

        global $wpdb;

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'page_id' => (int) $data['page_id'],
                'data_transfer' => (float) $data['data_transfer'],
                'load_time' => (float) $data['load_time'],
                'created_at' => current_time('mysql')
            ),
            array('%d', '%f', '%f', '%s')
        );

        return false !== $result;
    }

    /**
     * Check if tracking is enabled.
     *
     * @return bool Whether tracking is enabled.
     */
    private function is_tracking_enabled() {
        $settings = get_option('greenmetrics_settings');
        return isset($settings['tracking_enabled']) && $settings['tracking_enabled'];
    }

    /**
     * Get statistics.
     *
     * @param int|null $page_id Optional. The page ID to filter by.
     * @return array The statistics.
     */
    public function get_stats($page_id = null) {
        global $wpdb;

        $where = '';
        if ($page_id) {
            $where = $wpdb->prepare('WHERE page_id = %d', $page_id);
        }

        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_views,
                AVG(data_transfer) as avg_data_transfer,
                AVG(load_time) as avg_load_time
            FROM {$this->table_name}
            {$where}
        ", ARRAY_A);

        return $stats ?: array(
            'total_views' => 0,
            'avg_data_transfer' => 0,
            'avg_load_time' => 0
        );
    }

    /**
     * Track page metrics
     *
     * @param int $page_id The page ID to track
     * @return void
     */
    public function track_page($page_id) {
        try {
            if (GREENMETRICS_DEBUG) {
                error_log("GreenMetrics: Tracking page ID: " . $page_id);
            }
            // TODO: Implement actual tracking logic
            // This will be called when a page is loaded
        } catch (Exception $e) {
            if (GREENMETRICS_DEBUG) {
                error_log("GreenMetrics Error in track_page: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
            }
        }
    }

    /**
     * Get total statistics for all pages.
     *
     * @return array Total statistics.
     */
    public static function get_total_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'greenmetrics_emissions';

        $stats = $wpdb->get_row(
            "SELECT 
                SUM(data_transfer) as total_data_transfer,
                SUM(co2_emissions) as total_co2_emissions,
                COUNT(*) as total_views
            FROM $table_name"
        );

        if ($stats) {
            return array(
                'data_transfer' => $stats->total_data_transfer,
                'co2_emissions' => $stats->total_co2_emissions,
                'total_views' => $stats->total_views
            );
        }

        return array(
            'data_transfer' => 0,
            'co2_emissions' => 0,
            'total_views' => 0
        );
    }
} 