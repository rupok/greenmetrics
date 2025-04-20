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
        $this->table_name = $wpdb->prefix . 'greenmetrics_stats';
        
        add_action('wp_footer', array($this, 'inject_tracking_script'));
        add_action('wp_ajax_greenmetrics_track', array($this, 'handle_tracking_request'));
        add_action('wp_ajax_nopriv_greenmetrics_track', array($this, 'handle_tracking_request'));
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

        $settings = $this->get_settings();
        $plugin_url = plugins_url('', dirname(dirname(__FILE__)));
        ?>
        <script>
            window.greenmetricsTracking = {
                enabled: true,
                carbonIntensity: <?php echo esc_js($settings['carbon_intensity']); ?>,
                energyPerByte: <?php echo esc_js($settings['energy_per_byte']); ?>,
                nonce: '<?php echo wp_create_nonce('greenmetrics_tracking'); ?>',
                ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
                page_id: <?php echo get_the_ID(); ?>
            };
        </script>
        <script src="<?php echo esc_url($plugin_url . '/greenmetrics/public/js/greenmetrics-tracking.js'); ?>"></script>
        <?php
    }

    /**
     * Handle the tracking request.
     */
    public function handle_tracking_request() {
        if (!$this->is_tracking_enabled()) {
            wp_send_json_error('Tracking is disabled');
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            wp_send_json_error('Invalid data');
        }

        error_log('GreenMetrics Debug - Tracking Request Data: ' . print_r($data, true));

        $page_id = get_the_ID();
        if (!$page_id) {
            wp_send_json_error('Invalid page ID');
        }

        // Calculate additional metrics
        $settings = get_option('greenmetrics_settings');
        error_log('GreenMetrics Debug - Tracking Settings: ' . print_r($settings, true));

        $carbon_footprint = $this->calculate_carbon_footprint($data['data_transfer'], $settings['carbon_intensity']);
        $energy_consumption = $this->calculate_energy_consumption($data['data_transfer'], $settings['energy_per_byte']);
        $performance_score = $this->calculate_performance_score($data['load_time']);

        error_log('GreenMetrics Debug - Calculated Metrics:');
        error_log('Data Transfer: ' . $data['data_transfer']);
        error_log('Carbon Footprint: ' . $carbon_footprint);
        error_log('Energy Consumption: ' . $energy_consumption);
        error_log('Performance Score: ' . $performance_score);

        global $wpdb;
        $table_name = $wpdb->prefix . 'greenmetrics_stats';
        $result = $wpdb->insert(
            $table_name,
            array(
                'page_id' => $page_id,
                'data_transfer' => $data['data_transfer'],
                'load_time' => $data['load_time'],
                'requests' => $data['requests'],
                'carbon_footprint' => $carbon_footprint,
                'energy_consumption' => $energy_consumption,
                'performance_score' => $performance_score
            ),
            array('%d', '%d', '%f', '%d', '%f', '%f', '%d')
        );

        if ($result === false) {
            error_log('GreenMetrics Debug - Database Error: ' . $wpdb->last_error);
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        }

        error_log('GreenMetrics Debug - Data inserted successfully');
        wp_send_json_success();
    }

    /**
     * Calculate carbon footprint in grams of CO2.
     */
    private function calculate_carbon_footprint($data_transfer, $carbon_intensity) {
        $energy = $this->calculate_energy_consumption($data_transfer, 0.000000000072);
        return $energy * $carbon_intensity;
    }

    /**
     * Calculate energy consumption in kWh.
     */
    private function calculate_energy_consumption($data_transfer, $energy_per_byte) {
        return $data_transfer * $energy_per_byte;
    }

    /**
     * Calculate performance score based on load time.
     */
    private function calculate_performance_score($load_time) {
        if ($load_time <= 1) return 100;
        if ($load_time <= 2) return 90;
        if ($load_time <= 3) return 80;
        if ($load_time <= 4) return 70;
        if ($load_time <= 5) return 60;
        return 50;
    }

    /**
     * Check if tracking is enabled.
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
                SUM(data_transfer) as total_data_transfer,
                AVG(load_time) as avg_load_time,
                SUM(requests) as total_requests,
                AVG(performance_score) as avg_performance_score
            FROM {$this->table_name}
            {$where}
        ", ARRAY_A);

        error_log('GreenMetrics Debug - Raw Stats: ' . print_r($stats, true));

        if (!$stats) {
            error_log('GreenMetrics Debug - No stats found in database');
            return array(
                'total_views' => 0,
                'total_data_transfer' => 0,
                'avg_load_time' => 0,
                'total_requests' => 0,
                'avg_performance_score' => 0
            );
        }

        // Get settings with defaults
        $options = get_option('greenmetrics_settings', array());
        error_log('GreenMetrics Debug - Raw Options: ' . print_r($options, true));

        $settings = array_merge(
            array(
                'carbon_intensity' => 0.475, // Default carbon intensity factor (kg CO2/kWh)
                'energy_per_byte' => 0.000000000072 // Default energy per byte (kWh/byte)
            ),
            is_array($options) ? $options : array()
        );

        error_log('GreenMetrics Debug - Settings: ' . print_r($settings, true));

        // Calculate current CO2 and energy based on total data transfer
        $energy_consumption = $stats['total_data_transfer'] * $settings['energy_per_byte'];
        $carbon_footprint = $energy_consumption * $settings['carbon_intensity'] * 1000; // Convert kg to g

        error_log('GreenMetrics Debug - Calculations:');
        error_log('Total Data Transfer: ' . $stats['total_data_transfer']);
        error_log('Energy per Byte: ' . $settings['energy_per_byte']);
        error_log('Energy Consumption: ' . $energy_consumption);
        error_log('Carbon Intensity: ' . $settings['carbon_intensity']);
        error_log('Carbon Footprint: ' . $carbon_footprint);

        return array(
            'total_views' => intval($stats['total_views']),
            'total_data_transfer' => floatval($stats['total_data_transfer']),
            'avg_load_time' => floatval($stats['avg_load_time']),
            'total_requests' => intval($stats['total_requests']),
            'avg_performance_score' => floatval($stats['avg_performance_score']),
            'total_energy_consumption' => floatval($energy_consumption),
            'total_carbon_footprint' => floatval($carbon_footprint)
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

    public function get_settings() {
        $options = get_option('greenmetrics_settings', array());
        return array(
            'carbon_intensity' => isset($options['carbon_intensity']) ? $options['carbon_intensity'] : 0.5,
            'energy_per_byte' => isset($options['energy_per_byte']) ? $options['energy_per_byte'] : 0.000001,
            'tracking_enabled' => isset($options['tracking_enabled']) ? $options['tracking_enabled'] : 0
        );
    }
} 