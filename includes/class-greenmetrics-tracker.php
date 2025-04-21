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
        
        greenmetrics_log('Tracker initialized', $this->table_name);
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
            greenmetrics_log('Tracking disabled, not injecting script');
            return;
        }

        $settings = $this->get_settings();
        $plugin_url = plugins_url('', dirname(dirname(__FILE__)));
        
        greenmetrics_log('Injecting tracking script', get_the_ID());
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
            greenmetrics_log('Tracking request rejected - tracking disabled', null, 'warning');
            wp_send_json_error('Tracking is disabled');
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            greenmetrics_log('Invalid data in tracking request', null, 'error');
            wp_send_json_error('Invalid data');
        }

        greenmetrics_log('Tracking request received', $data);

        $page_id = get_the_ID();
        if (!$page_id) {
            greenmetrics_log('Invalid page ID in tracking request', null, 'error');
            wp_send_json_error('Invalid page ID');
        }

        // Process the metrics using our common function
        $result = $this->process_and_save_metrics($page_id, $data);
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to save metrics');
        }
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
     * Uses a logarithmic scale to provide more granular scoring with decimal precision.
     * Based on similar approaches used in web performance tools.
     * 
     * @param float $load_time Load time in seconds
     * @return float Performance score from 0-100 with decimal precision
     */
    public function calculate_performance_score($load_time) {
        // Convert to milliseconds for more precision
        $load_time_ms = $load_time * 1000;
        
        // Define reference values based on web performance standards
        // These values align with general performance expectations
        $fast_threshold_ms = 1000;    // 1 second - considered fast (scores close to 100)
        $slow_threshold_ms = 5000;    // 5 seconds - considered slow (scores around 50)
        $max_threshold_ms = 10000;    // 10 seconds - very slow (scores below 20)
        
        // If load time is extremely fast, give a perfect score
        if ($load_time_ms <= 100) {
            return 100;
        }
        
        // Apply a logarithmic scale for more granular scoring
        // This creates a curve that drops quickly for slow sites but gives more 
        // precision for fast sites in the 90-100 range
        if ($load_time_ms <= $fast_threshold_ms) {
            // For fast sites (0-1s): subtle scoring from 90-100
            $score = 100 - (10 * ($load_time_ms / $fast_threshold_ms));
        } elseif ($load_time_ms <= $slow_threshold_ms) {
            // For medium sites (1-5s): scoring from 50-90
            $normalized = ($load_time_ms - $fast_threshold_ms) / ($slow_threshold_ms - $fast_threshold_ms);
            $score = 90 - (40 * $normalized);
        } else {
            // For slow sites (5s+): scoring from 0-50 with diminishing returns
            $normalized = min(1, ($load_time_ms - $slow_threshold_ms) / ($max_threshold_ms - $slow_threshold_ms));
            $score = 50 - (50 * $normalized);
        }
        
        // Ensure score is within valid range and has decimal precision
        return max(0, min(100, round($score, 2)));
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
            greenmetrics_log('Getting stats for page ID', $page_id);
        } else {
            greenmetrics_log('Getting stats for all pages');
        }

        // For troubleshooting database issues, we'll keep more detailed logging
        // but only during active debugging
        $do_detailed_debug = true; // Set to true to find source of corrupted data
        if ($do_detailed_debug) {
            // Check for problematic performance_score values
            $invalid_scores_sql = "SELECT id, page_id, performance_score 
                                FROM {$this->table_name} 
                                WHERE performance_score > 100 OR performance_score < 0
                                LIMIT 10";
            $invalid_scores = $wpdb->get_results($invalid_scores_sql, ARRAY_A);
            if (!empty($invalid_scores)) {
                greenmetrics_log('Found invalid performance scores in database:', $invalid_scores, 'warning');
                
                // Perform data cleanup if needed
                // This is a safe way to fix corrupted data while preserving records
                $cleanup_sql = "UPDATE {$this->table_name} 
                                SET performance_score = 
                                    CASE 
                                        WHEN performance_score > 100 THEN 100 
                                        WHEN performance_score < 0 THEN 0
                                        ELSE performance_score
                                    END
                                WHERE performance_score > 100 OR performance_score < 0";
                $cleanup_result = $wpdb->query($cleanup_sql);
                if ($cleanup_result !== false) {
                    greenmetrics_log('Cleaned up invalid performance scores', [
                        'rows_updated' => $cleanup_result
                    ]);
                }
            } else {
                greenmetrics_log('No invalid performance scores found in database');
            }
            
            $raw_load_time_sql = "SELECT AVG(load_time) as raw_avg_load_time FROM {$this->table_name}";
            $raw_load_time_result = $wpdb->get_var($raw_load_time_sql);
            greenmetrics_log('Raw load time average', $raw_load_time_result);
            
            // Get some sample values to understand what's in the database
            $sample_values_sql = "SELECT id, page_id, load_time, performance_score FROM {$this->table_name} ORDER BY id DESC LIMIT 5";
            $sample_values = $wpdb->get_results($sample_values_sql, ARRAY_A);
            greenmetrics_log('Recent records', $sample_values);
            
            // Get some insights into score distribution
            $distribution_sql = "
                SELECT 
                    CASE 
                        WHEN performance_score BETWEEN 0 AND 50 THEN '0-50'
                        WHEN performance_score BETWEEN 50.01 AND 70 THEN '50-70'
                        WHEN performance_score BETWEEN 70.01 AND 90 THEN '70-90'
                        WHEN performance_score BETWEEN 90.01 AND 100 THEN '90-100'
                        ELSE 'invalid' 
                    END as score_range,
                    COUNT(*) as count_value
                FROM {$this->table_name}
                GROUP BY score_range
                ORDER BY FIELD(score_range, '0-50', '50-70', '70-90', '90-100', 'invalid')
            ";
            $distribution = $wpdb->get_results($distribution_sql, ARRAY_A);
            if ($wpdb->last_error) {
                greenmetrics_log('Error in performance score distribution query', $wpdb->last_error, 'error');
            } else {
                greenmetrics_log('Performance score distribution', $distribution);
            }
            
            // Get the 10 lowest scores to identify potential issues
            $lowest_scores_sql = "
                SELECT id, page_id, load_time, performance_score, created_at
                FROM {$this->table_name}
                WHERE performance_score BETWEEN 0 AND 100
                ORDER BY performance_score ASC
                LIMIT 10
            ";
            $lowest_scores = $wpdb->get_results($lowest_scores_sql, ARRAY_A);
            if ($wpdb->last_error) {
                greenmetrics_log('Error in lowest scores query', $wpdb->last_error, 'error');
            } else {
                greenmetrics_log('Lowest performance scores', $lowest_scores);
            }
        }

        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_views,
                SUM(data_transfer) as total_data_transfer,
                AVG(load_time) as avg_load_time,
                SUM(requests) as total_requests,
                AVG(CASE 
                    WHEN performance_score BETWEEN 0 AND 100 THEN performance_score 
                    ELSE NULL 
                END) as avg_performance_score,
                MIN(CASE 
                    WHEN performance_score BETWEEN 0 AND 100 THEN performance_score 
                    ELSE NULL 
                END) as min_performance_score,
                MAX(CASE 
                    WHEN performance_score BETWEEN 0 AND 100 THEN performance_score 
                    ELSE NULL 
                END) as max_performance_score
            FROM {$this->table_name}
            {$where}
        ", ARRAY_A);

        greenmetrics_log('Raw stats from query', $stats);
        
        // Get a percentile-based score to avoid extreme outliers affecting the average
        if ($do_detailed_debug) {
            // Log the raw stats for diagnostics but don't modify the returned value
            greenmetrics_log('Performance score diagnostics', [
                'average' => isset($stats['avg_performance_score']) ? $stats['avg_performance_score'] : 'N/A',
                'min' => isset($stats['min_performance_score']) ? $stats['min_performance_score'] : 'N/A',
                'max' => isset($stats['max_performance_score']) ? $stats['max_performance_score'] : 'N/A'
            ]);
            
            // Get median performance score (more representative than average in some cases)
            // Using a simpler approach that's more compatible with MySQL/MariaDB
            $wpdb->query("SET @rownum:=0");
            $count_query = "SELECT COUNT(*) FROM {$this->table_name} WHERE performance_score BETWEEN 0 AND 100";
            $total_rows = $wpdb->get_var($count_query);
            
            if ($total_rows > 0) {
                $median_position = floor($total_rows / 2);
                
                $median_query = "
                    SELECT performance_score 
                    FROM {$this->table_name}
                    WHERE performance_score BETWEEN 0 AND 100
                    ORDER BY performance_score
                    LIMIT {$median_position}, 1
                ";
                
                $median_score = $wpdb->get_var($median_query);
                greenmetrics_log('Median calculation', [
                    'total_rows' => $total_rows,
                    'median_position' => $median_position,
                    'median_score' => $median_score
                ]);
            } else {
                $median_score = null;
            }
        }

        if (!$stats) {
            greenmetrics_log('No stats found in database', null, 'warning');
            return array(
                'total_views' => 0,
                'total_data_transfer' => 0,
                'avg_load_time' => 0,
                'total_requests' => 0,
                'avg_performance_score' => 100 // Default to 100% when no data
            );
        }

        // Get settings with defaults
        $options = get_option('greenmetrics_settings', array());
        $settings = array_merge(
            array(
                'carbon_intensity' => 0.475, // Default carbon intensity factor (kg CO2/kWh)
                'energy_per_byte' => 0.000000000072 // Default energy per byte (kWh/byte)
            ),
            is_array($options) ? $options : array()
        );

        // Validate metrics to ensure they're all positive numbers
        $total_views = max(0, intval($stats['total_views']));
        $total_data_transfer = max(0, floatval($stats['total_data_transfer']));
        $avg_load_time = max(0, floatval($stats['avg_load_time']));
        $total_requests = max(0, intval($stats['total_requests']));

        // Calculate current CO2 and energy based on total data transfer
        $energy_consumption = $total_data_transfer * $settings['energy_per_byte'];
        $carbon_footprint = $energy_consumption * $settings['carbon_intensity'] * 1000; // Convert kg to g

        // Ensure performance score is a valid percentage
        $performance_score = isset($stats['avg_performance_score']) && $stats['avg_performance_score'] !== null
            ? floatval($stats['avg_performance_score'])
            : 100; // Default to 100 if NULL (which can happen if all records were filtered out)
            
        if ($performance_score > 100 || $performance_score <= 0 || !is_numeric($performance_score) || is_nan($performance_score)) {
            greenmetrics_log('Invalid performance score, recalculating', $performance_score, 'warning');
            if ($avg_load_time > 0) {
                $performance_score = max(0, min(100, 100 - ($avg_load_time * 10)));
            } else {
                $performance_score = 100; // Default to 100% if no load time data
            }
        }

        // Additional validation to ensure we return a valid float
        if (!is_numeric($performance_score) || is_nan($performance_score)) {
            greenmetrics_log('Performance score is still invalid after recalculation, using default', $performance_score, 'error');
            $performance_score = 100;
        }
        
        // Check if we should use the median score instead - FOR DIAGNOSTIC ONLY
        if ($do_detailed_debug && isset($median_score) && $median_score !== null) {
            // Only log the difference, don't actually change the reported score
            if (abs($median_score - $performance_score) > 5) {
                greenmetrics_log('Note: Large difference between median and average', [
                    'average' => $performance_score,
                    'median' => $median_score,
                    'difference' => abs($median_score - $performance_score),
                    'using' => 'average (original)'  // We're sticking with the average
                ]);
            }
        }
        
        // Ensure the score is within the valid range with proper decimal precision
        $performance_score = max(0, min(100, floatval($performance_score)));

        $result = array(
            'total_views' => $total_views,
            'total_data_transfer' => $total_data_transfer,
            'avg_load_time' => $avg_load_time,
            'total_requests' => $total_requests,
            'avg_performance_score' => $performance_score,
            'total_energy_consumption' => floatval($energy_consumption),
            'total_carbon_footprint' => floatval($carbon_footprint)
        );
        
        greenmetrics_log('Final calculation results', $result);
        return $result;
    }

    /**
     * Track page metrics
     *
     * @param int $page_id The page ID to track
     * @return void
     */
    public function track_page($page_id) {
        try {
            greenmetrics_log('Tracking page ID', $page_id);
            // TODO: Implement actual tracking logic
            // This will be called when a page is loaded
        } catch (Exception $e) {
            greenmetrics_log('Error in track_page', $e->getMessage() . "\n" . $e->getTraceAsString(), 'error');
        }
    }

    /**
     * Handle tracking page from REST API
     * 
     * @param array $data Tracking data
     * @return bool Success status
     */
    public function handle_track_page($data) {
        if (!$this->is_tracking_enabled()) {
            greenmetrics_log('REST tracking rejected - tracking disabled', null, 'warning');
            return false;
        }

        if (empty($data['page_id']) || !isset($data['data_transfer']) || !isset($data['load_time'])) {
            greenmetrics_log('REST tracking missing required data', $data, 'warning');
            return false;
        }

        try {
            $page_id = intval($data['page_id']);
            greenmetrics_log('REST tracking for page ID', $page_id);
            
            // Use common method to process and save metrics
            return $this->process_and_save_metrics($page_id, $data);
        } catch (\Exception $e) {
            greenmetrics_log('Exception in REST tracking', $e->getMessage(), 'error');
            return false;
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

        greenmetrics_log('Getting total stats from', $table_name);

        $stats = $wpdb->get_row(
            "SELECT 
                SUM(data_transfer) as total_data_transfer,
                SUM(co2_emissions) as total_co2_emissions,
                COUNT(*) as total_views
            FROM $table_name"
        );

        if ($stats) {
            greenmetrics_log('Total stats found', $stats);
            return array(
                'data_transfer' => $stats->total_data_transfer,
                'co2_emissions' => $stats->total_co2_emissions,
                'total_views' => $stats->total_views
            );
        }

        greenmetrics_log('No total stats found, returning defaults', null, 'warning');
        return array(
            'data_transfer' => 0,
            'co2_emissions' => 0,
            'total_views' => 0
        );
    }

    /**
     * Get plugin settings with defaults
     *
     * @return array Settings
     */
    public function get_settings() {
        $options = get_option('greenmetrics_settings', array());
        $settings = array(
            'carbon_intensity' => isset($options['carbon_intensity']) ? $options['carbon_intensity'] : 0.5,
            'energy_per_byte' => isset($options['energy_per_byte']) ? $options['energy_per_byte'] : 0.000001,
            'tracking_enabled' => isset($options['tracking_enabled']) ? $options['tracking_enabled'] : 0
        );
        
        greenmetrics_log('Using settings', $settings);
        
        return $settings;
    }

    /**
     * Handle the tracking request from POST data (legacy format from greenmetrics_tracking action)
     * This handles tracking requests from the older AJAX endpoint
     */
    public function handle_tracking_request_from_post() {
        if (!$this->is_tracking_enabled()) {
            greenmetrics_log('Tracking request rejected - tracking disabled', null, 'warning');
            wp_send_json_error('Tracking is disabled');
            return;
        }

        greenmetrics_log('Received POST tracking request');
        
        if (!isset($_POST['nonce'])) {
            greenmetrics_log('No nonce provided', null, 'error');
            wp_send_json_error('No nonce provided');
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'greenmetrics_tracking')) {
            greenmetrics_log('Invalid nonce', null, 'error');
            wp_send_json_error('Invalid nonce');
            return;
        }

        if (!isset($_POST['metrics'])) {
            greenmetrics_log('No metrics provided', null, 'error');
            wp_send_json_error('No metrics provided');
            return;
        }

        $metrics = json_decode(stripslashes($_POST['metrics']), true);
        greenmetrics_log('Decoded metrics from POST', $metrics);
        
        if (!is_array($metrics)) {
            greenmetrics_log('Invalid metrics format', null, 'error');
            wp_send_json_error('Invalid metrics format');
            return;
        }

        // Get page ID - either from metrics or current page
        $page_id = isset($metrics['page_id']) ? intval($metrics['page_id']) : get_the_ID();
        
        if (!$page_id) {
            greenmetrics_log('Invalid page ID in tracking request', null, 'error');
            wp_send_json_error('Invalid page ID');
            return;
        }

        // Process the metrics
        $result = $this->process_and_save_metrics($page_id, $metrics);
        
        if ($result) {
            wp_send_json_success('Metrics saved successfully');
        } else {
            wp_send_json_error('Failed to save metrics');
        }
    }

    /**
     * Process and save metrics to database
     * This is a common function used by both tracking methods
     * 
     * @param int $page_id The page ID
     * @param array $metrics The metrics to process
     * @return bool Success status
     */
    private function process_and_save_metrics($page_id, $metrics) {
        // Validate and sanitize all inputs
        $page_id = intval($page_id);
        
        // Validate input data with detailed logging
        greenmetrics_log('Processing metrics for page', $page_id);
        
        // Check if metrics contains data_transfer
        if (!isset($metrics['data_transfer'])) {
            greenmetrics_log('Missing data_transfer in metrics', $metrics, 'warning');
            $data_transfer = 0;
        } else {
            $data_transfer = max(0, intval($metrics['data_transfer']));
        }
        
        // Check if metrics contains load_time
        if (!isset($metrics['load_time'])) {
            greenmetrics_log('Missing load_time in metrics', $metrics, 'warning');
            $load_time = 0;
        } else {
            $load_time = max(0, floatval($metrics['load_time']));
        }
        
        // Check if metrics contains requests
        if (!isset($metrics['requests'])) {
            greenmetrics_log('Missing requests in metrics', $metrics, 'warning');
            $requests = 0;
        } else {
            $requests = max(0, intval($metrics['requests']));
        }
        
        // Get settings with defaults
        $settings = $this->get_settings();
        
        // Calculate metrics if they weren't provided
        if (isset($metrics['carbon_footprint']) && isset($metrics['energy_consumption'])) {
            // Use the provided values
            $carbon_footprint = floatval($metrics['carbon_footprint']);
            $energy_consumption = floatval($metrics['energy_consumption']);
            greenmetrics_log('Using provided carbon and energy values', [
                'carbon' => $carbon_footprint,
                'energy' => $energy_consumption
            ]);
        } else {
            // Calculate them
            $carbon_footprint = $this->calculate_carbon_footprint($data_transfer, $settings['carbon_intensity']);
            $energy_consumption = $this->calculate_energy_consumption($data_transfer, $settings['energy_per_byte']);
            greenmetrics_log('Calculated carbon and energy values', [
                'carbon' => $carbon_footprint,
                'energy' => $energy_consumption
            ]);
        }
        
        // Calculate performance score if not provided
        if (isset($metrics['performance_score'])) {
            $performance_score = floatval($metrics['performance_score']);
            greenmetrics_log('Using provided performance score', $performance_score);
        } else {
            $performance_score = $this->calculate_performance_score($load_time);
            greenmetrics_log('Calculated performance score', $performance_score);
        }

        // Log metrics in a consolidated format
        $metrics_data = [
            'page_id' => $page_id,
            'data_transfer' => $data_transfer,
            'load_time' => $load_time,
            'requests' => $requests,
            'carbon_footprint' => $carbon_footprint,
            'energy_consumption' => $energy_consumption,
            'performance_score' => $performance_score
        ];
        greenmetrics_log('Final metrics to be saved', $metrics_data);

        global $wpdb;
        $table_name = $wpdb->prefix . 'greenmetrics_stats';
        $result = $wpdb->insert(
            $table_name,
            array(
                'page_id' => $page_id,
                'data_transfer' => $data_transfer,
                'load_time' => $load_time,
                'requests' => $requests,
                'carbon_footprint' => $carbon_footprint,
                'energy_consumption' => $energy_consumption,
                'performance_score' => $performance_score
            ),
            array('%d', '%d', '%f', '%d', '%f', '%f', '%f')
        );

        if ($result === false) {
            greenmetrics_log('Database error', $wpdb->last_error, 'error');
            return false;
        }

        greenmetrics_log('Metrics saved successfully for page_id: ' . $page_id);
        return true;
    }
} 