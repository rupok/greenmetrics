<?php
/**
 * The tracker functionality of the plugin.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */

namespace GreenMetrics;

// Include required classes
require_once dirname(__FILE__) . '/class-greenmetrics-calculator.php';
require_once dirname(__FILE__) . '/class-greenmetrics-settings-manager.php';

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
        
        // These AJAX handlers are deprecated but kept for backward compatibility
        // They will be removed in a future version. Use the REST API instead.
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
        
        // If we're using public.js for tracking, don't inject the tracking script
        if (defined('GREENMETRICS_USE_PUBLIC_JS') && GREENMETRICS_USE_PUBLIC_JS) {
            greenmetrics_log('Using public.js for tracking instead of tracking.js');
            return;
        }

        $settings = GreenMetrics_Settings_Manager::get_instance()->get();
        $plugin_url = plugins_url('', dirname(dirname(__FILE__)));
        
        greenmetrics_log('Injecting tracking script', get_the_ID());
        ?>
        <script>
            window.greenmetricsTracking = {
                enabled: true,
                carbonIntensity: <?php echo esc_js($settings['carbon_intensity']); ?>,
                energyPerByte: <?php echo esc_js($settings['energy_per_byte']); ?>,
                rest_nonce: '<?php echo wp_create_nonce('wp_rest'); ?>',
                rest_url: '<?php echo esc_js(get_rest_url(null, 'greenmetrics/v1')); ?>',
                page_id: <?php echo get_the_ID(); ?>
            };
        </script>
        <script src="<?php echo esc_url($plugin_url . '/greenmetrics/public/js/greenmetrics-tracking.js'); ?>"></script>
        <?php
    }

    /**
     * Handle the tracking request.
     * 
     * @deprecated 1.1.0 Use the REST API endpoint /greenmetrics/v1/track instead.
     */
    public function handle_tracking_request() {
        // Log deprecation notice
        greenmetrics_log('DEPRECATED: The AJAX endpoint greenmetrics_track is deprecated. Use the REST API endpoint /greenmetrics/v1/track instead.', null, 'warning');

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
     * Calculate performance score based on load time.
     * Uses a logarithmic scale to provide more granular scoring with decimal precision.
     * Based on similar approaches used in web performance tools.
     * 
     * @param float $load_time Load time in seconds
     * @return float Performance score from 0-100 with decimal precision
     */
    public function calculate_performance_score($load_time) {
        // Ignore extremely high load times (likely measurement errors or development-related delays)
        // These can happen during development with browser cache disabled or when dev tools are open
        if ($load_time > 15) {
            greenmetrics_log('Ignoring abnormally high load time', $load_time, 'warning');
            return 75; // Return a reasonable default value
        }
        
        // Convert to milliseconds for more precision
        $load_time_ms = $load_time * 1000;
        
        // Define reference values based on web performance standards
        // These values align with general performance expectations
        $fast_threshold_ms = 1500;    // 1.5 seconds - considered fast (scores close to 100)
        $slow_threshold_ms = 5000;    // 5 seconds - considered slow (scores around 50)
        $max_threshold_ms = 10000;    // 10 seconds - very slow (scores below 20)
        
        // If load time is extremely fast, give a perfect score
        if ($load_time_ms <= 500) {
            return 100;
        }
        
        // Apply a logarithmic scale for more granular scoring
        // This creates a curve that drops quickly for slow sites but gives more 
        // precision for fast sites in the 90-100 range
        if ($load_time_ms <= $fast_threshold_ms) {
            // For fast sites (0-1.5s): subtle scoring from 90-100
            $score = 100 - (10 * ($load_time_ms / $fast_threshold_ms));
        } elseif ($load_time_ms <= $slow_threshold_ms) {
            // For medium sites (1.5-5s): scoring from 50-90
            $normalized = ($load_time_ms - $fast_threshold_ms) / ($slow_threshold_ms - $fast_threshold_ms);
            $score = 90 - (40 * $normalized);
        } elseif ($load_time_ms <= $max_threshold_ms) {
            // For slow sites (5-10s): scoring from 20-50
            $normalized = ($load_time_ms - $slow_threshold_ms) / ($max_threshold_ms - $slow_threshold_ms);
            $score = 50 - (30 * $normalized);
        } else {
            // For extremely slow sites (>10s): scoring from 0-20
            $normalized = min(1, ($load_time_ms - $max_threshold_ms) / $max_threshold_ms);
            $score = max(0, 20 - (20 * $normalized));
        }
        
        // Ensure score is within 0-100 range with 2 decimal precision
        return round(max(0, min(100, $score)), 2);
    }

    /**
     * Check if tracking is enabled.
     */
    private function is_tracking_enabled() {
        return GreenMetrics_Settings_Manager::get_instance()->is_enabled('tracking_enabled');
    }

    /**
     * Get stats for a page or all pages.
     *
     * @param int|null $page_id The page ID or null for all pages.
     * @return array The statistics.
     */
    public function get_stats($page_id = null) {
        global $wpdb;
        
        // Set flag for detailed debugging
        $do_detailed_debug = true; // Enable detailed logging for diagnostics
        
        if ($page_id) {
            greenmetrics_log('Getting stats for page ID', $page_id);
        } else {
            greenmetrics_log('Getting stats for all pages');
        }

        // Get settings to use in SQL calculations
        $settings = $this->get_settings();
        $carbon_intensity = isset($settings['carbon_intensity']) ? floatval($settings['carbon_intensity']) : 0.475;
        $energy_per_byte = isset($settings['energy_per_byte']) ? floatval($settings['energy_per_byte']) : 0.000000000072;
        
        // Format the query with WHERE clause if needed
        $table_name_escaped = esc_sql($this->table_name);
        $sql = "SELECT 
            COUNT(*) as total_views,
            SUM(data_transfer) as total_data_transfer,
            AVG(load_time) as avg_load_time,
            SUM(requests) as total_requests,
            /* Calculate valid performance scores directly in SQL */
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
            END) as max_performance_score,
            /* Get average energy consumption and multiply by view count for correct totals */
            AVG(energy_consumption) as avg_energy_consumption,
            /* Get average carbon footprint and multiply by view count for correct totals */
            AVG(carbon_footprint) as avg_carbon_footprint
        FROM $table_name_escaped";
        
        if ($page_id) {
            $sql .= $wpdb->prepare(' WHERE page_id = %d', $page_id);
        }

        $stats = $wpdb->get_row($sql, ARRAY_A);

        greenmetrics_log('Raw stats from query', $stats);
        
        // Calculate totals from average values * number of views
        if (isset($stats['total_views']) && $stats['total_views'] > 0) {
            // Get the actual averages from the database
            $avg_energy_per_view = isset($stats['avg_energy_consumption']) ? floatval($stats['avg_energy_consumption']) : 0;
            $avg_carbon_per_view = isset($stats['avg_carbon_footprint']) ? floatval($stats['avg_carbon_footprint']) : 0;
            
            // Simply multiply by the total number of views - no capping or scaling
            $stats['total_energy_consumption'] = $avg_energy_per_view * $stats['total_views'];
            $stats['total_carbon_footprint'] = $avg_carbon_per_view * $stats['total_views'];
            
            greenmetrics_log('Calculated total energy and carbon values from real database averages', [
                'avg_energy_per_view' => $avg_energy_per_view,
                'avg_carbon_per_view' => $avg_carbon_per_view,
                'total_views' => $stats['total_views'],
                'total_energy' => $stats['total_energy_consumption'],
                'total_carbon' => $stats['total_carbon_footprint']
            ]);
        } else {
            // No views yet, so set totals to 0
            $stats['total_energy_consumption'] = 0;
            $stats['total_carbon_footprint'] = 0;
        }
        
        // Get a percentile-based score to avoid extreme outliers affecting the average
        if ($do_detailed_debug) {
            // Log the raw stats for diagnostics but don't modify the returned value
            greenmetrics_log('Performance score diagnostics', [
                'average' => isset($stats['avg_performance_score']) ? $stats['avg_performance_score'] : 'N/A',
                'min' => isset($stats['min_performance_score']) ? $stats['min_performance_score'] : 'N/A',
                'max' => isset($stats['max_performance_score']) ? $stats['max_performance_score'] : 'N/A'
            ]);
            
            // Optimize median calculation using a single query if possible
            // Initialize median calculation variables
            $total_rows = 0;
            $median_position = 0;
            $median_score = null;
            
            // Use a more efficient single query to find the count
            $count_query = "SELECT COUNT(*) FROM $table_name_escaped WHERE performance_score BETWEEN 0 AND 100";
            if ($page_id) {
                $count_query .= $wpdb->prepare(' AND page_id = %d', $page_id);
            }
            $total_rows = $wpdb->get_var($count_query);
            
            if ($total_rows > 0) {
                $median_position = floor($total_rows / 2);
                
                // Single efficient query to get the median
                $median_query = $wpdb->prepare(
                    "SELECT performance_score 
                    FROM $table_name_escaped
                    WHERE performance_score BETWEEN 0 AND 100
                    " . ($page_id ? $wpdb->prepare('AND page_id = %d', $page_id) : '') . "
                    ORDER BY performance_score
                    LIMIT %d, 1",
                    $median_position
                );
                
                $median_score = $wpdb->get_var($median_query);
                
                greenmetrics_log('Median calculation', [
                    'total_rows' => $total_rows,
                    'median_position' => $median_position,
                    'median_score' => $median_score
                ]);
            }
        }

        if (!$stats) {
            greenmetrics_log('No stats found in database', null, 'warning');
            return array(
                'total_views' => 0,
                'total_data_transfer' => 0,
                'avg_load_time' => 0,
                'total_requests' => 0,
                'avg_performance_score' => 100, // Default to 100% when no data
                'total_energy_consumption' => 0,
                'total_carbon_footprint' => 0
            );
        }

        // Validate metrics to ensure they're all positive numbers
        $total_views = max(0, intval($stats['total_views']));
        $total_data_transfer = max(0, floatval($stats['total_data_transfer']));
        $avg_load_time = max(0, floatval($stats['avg_load_time']));
        $total_requests = max(0, intval($stats['total_requests']));
        
        // Get pre-calculated values from SQL
        $total_energy_consumption = max(0, floatval($stats['avg_energy_consumption']) * $total_views);
        $total_carbon_footprint = max(0, floatval($stats['avg_carbon_footprint']) * $total_views);

        // Ensure performance score is a valid percentage
        $avg_performance_score = isset($stats['avg_performance_score']) && $stats['avg_performance_score'] !== null
            ? floatval($stats['avg_performance_score'])
            : 100; // Default to 100 if NULL (which can happen if all records were filtered out)
            
        if ($avg_performance_score < 0 || $avg_performance_score > 100) {
            greenmetrics_log('Invalid performance score from database', $avg_performance_score, 'warning');
            // Calculate performance score using load time
            if ($avg_load_time > 0) {
                $performance_score = $this->calculate_performance_score($avg_load_time);
                greenmetrics_log('Recalculated performance score', $performance_score);
            } else {
                $performance_score = 100; // Default to 100% when no data
                greenmetrics_log('Using default performance score', $performance_score);
            }
        } else {
            $performance_score = $avg_performance_score;
            greenmetrics_log('Using valid performance score from database', $performance_score);
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
            'total_energy_consumption' => $total_energy_consumption,
            'total_carbon_footprint' => $total_carbon_footprint
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

    /**
     * Handle tracking page from REST API
     * 
     * @param array $data Tracking data
     * @return bool Success status
     */
    public function handle_track_page($data) {
        try {
            greenmetrics_log('Tracker: handle_track_page called with data', $data);
            
            if (!$this->is_tracking_enabled()) {
                greenmetrics_log('REST tracking rejected - tracking disabled', null, 'warning');
                return GreenMetrics_Error_Handler::create_error('tracking_disabled', 'Tracking is disabled');
            }

            if (empty($data['page_id']) || !isset($data['data_transfer']) || !isset($data['load_time'])) {
                greenmetrics_log('REST tracking missing required data', $data, 'warning');
                return GreenMetrics_Error_Handler::create_error('invalid_data', 'Missing required data');
            }

            try {
                $page_id = intval($data['page_id']);
                greenmetrics_log('REST tracking for page ID', $page_id);
                
                // Check database setup
                global $wpdb;
                greenmetrics_log('Tracker: Database table name', $this->table_name);
                
                $table_name_escaped = esc_sql($this->table_name);
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name_escaped'");
                greenmetrics_log('Tracker: Table exists check', ['exists' => !empty($table_exists), 'result' => $table_exists]);
                
                if ($table_exists) {
                    $columns = $wpdb->get_results("DESCRIBE $table_name_escaped");
                    $column_names = array_map(function($col) { return $col->Field; }, $columns);
                    greenmetrics_log('Tracker: Table columns', $column_names);
                } else {
                    greenmetrics_log('Tracker: Table does not exist! Creating table...');
                    // Attempt to create the table
                    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-greenmetrics-activator.php';
                    \GreenMetrics\GreenMetrics_Activator::activate();
                }
                
                // Use common method to process and save metrics
                greenmetrics_log('Tracker: About to call process_and_save_metrics');
                $result = $this->process_and_save_metrics($page_id, $data);
                greenmetrics_log('Tracker: process_and_save_metrics result', ['success' => $result]);
                
                if (GreenMetrics_Error_Handler::is_error($result)) {
                    return $result;
                }
                
                return GreenMetrics_Error_Handler::success();
            } catch (\Exception $e) {
                greenmetrics_log('Exception in REST tracking', [
                    'message' => $e->getMessage(), 
                    'trace' => $e->getTraceAsString()
                ], 'error');
                return GreenMetrics_Error_Handler::handle_exception($e, 'tracking_exception');
            }
        } catch (\Exception $e) {
            greenmetrics_log('Exception in handle_track_page', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'error');
            return GreenMetrics_Error_Handler::handle_exception($e, 'tracking_exception');
        }
    }

    /**
     * Get plugin settings with defaults
     *
     * @return array Settings
     */
    public function get_settings() {
        $settings = GreenMetrics_Settings_Manager::get_instance()->get();
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
     * Process and save metrics for a page.
     *
     * @param int $page_id The page ID.
     * @param array $metrics The metrics data.
     * @return bool True if successful, false otherwise.
     */
    private function process_and_save_metrics($page_id, $metrics) {
        global $wpdb;
        
        try {
            greenmetrics_log('process_and_save_metrics: Called with parameters', [
                'page_id' => $page_id,
                'metrics' => $metrics
            ]);
            
            if (!$page_id || !is_array($metrics)) {
                greenmetrics_log('Invalid page ID or metrics data', array('page_id' => $page_id, 'metrics' => $metrics), 'error');
                return GreenMetrics_Error_Handler::create_error('invalid_data', 'Invalid page ID or metrics data');
            }

            // Get settings for calculations
            $settings = GreenMetrics_Settings_Manager::get_instance()->get();
            greenmetrics_log('process_and_save_metrics: Got settings', $settings);
            
            // Calculate metrics using the Calculator class
            $data_transfer = isset($metrics['data_transfer']) ? floatval($metrics['data_transfer']) : 0;
            greenmetrics_log('process_and_save_metrics: Using data_transfer', $data_transfer);
            
            $carbon_footprint = GreenMetrics_Calculator::calculate_carbon_emissions($data_transfer);
            greenmetrics_log('process_and_save_metrics: Calculated carbon_footprint', $carbon_footprint);
            
            $energy_consumption = GreenMetrics_Calculator::calculate_energy_consumption($data_transfer);
            greenmetrics_log('process_and_save_metrics: Calculated energy_consumption', $energy_consumption);
            
            // Calculate performance score
            $load_time = isset($metrics['load_time']) ? floatval($metrics['load_time']) : 0;
            greenmetrics_log('process_and_save_metrics: Using load_time', $load_time);
            
            $performance_score = $this->calculate_performance_score($load_time);
            greenmetrics_log('process_and_save_metrics: Calculated performance_score', $performance_score);
            
            // Prepare data for insertion
            $data = array(
                'page_id' => $page_id,
                'data_transfer' => $data_transfer,
                'carbon_footprint' => $carbon_footprint,
                'energy_consumption' => $energy_consumption,
                'load_time' => $load_time,
                'performance_score' => $performance_score,
                'requests' => isset($metrics['requests']) ? intval($metrics['requests']) : 0,
                'created_at' => current_time('mysql')
            );
            
            greenmetrics_log('process_and_save_metrics: Data prepared for insertion', $data);
            
            // Log the calculated metrics
            greenmetrics_log('Calculated metrics', array(
                'data_transfer' => GreenMetrics_Calculator::format_data_transfer($data_transfer),
                'carbon_footprint' => GreenMetrics_Calculator::format_carbon_emissions($carbon_footprint),
                'energy_consumption' => GreenMetrics_Calculator::format_energy_consumption($energy_consumption),
                'load_time' => GreenMetrics_Calculator::format_load_time($load_time),
                'performance_score' => $performance_score
            ));
            
            // Check if the table exists before attempting to insert
            $table_name_escaped = esc_sql($this->table_name);
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name_escaped'") === null) {
                greenmetrics_log('process_and_save_metrics: Table does not exist', $this->table_name, 'error');
                return GreenMetrics_Error_Handler::create_error('table_not_found', 'Database table does not exist');
            }
            
            // Log the SQL query that would be executed
            $placeholder_sql = "INSERT INTO $table_name_escaped (" . 
                implode(', ', array_keys($data)) . 
                ") VALUES ('" . 
                implode("', '", array_values($data)) . 
                "')";
            greenmetrics_log('process_and_save_metrics: SQL that would be executed (placeholder)', $placeholder_sql);
            
            // Insert a new record for each page view instead of replacing/updating existing ones
            $result = $wpdb->insert($this->table_name, $data);
            greenmetrics_log('process_and_save_metrics: Insert result', $result);
            
            if ($result === false) {
                greenmetrics_log('Failed to save metrics', ['error' => $wpdb->last_error, 'query' => $wpdb->last_query], 'error');
                return GreenMetrics_Error_Handler::create_error('database_error', 'Failed to save metrics');
            }
            
            greenmetrics_log('Metrics saved successfully', array('page_id' => $page_id, 'metrics_id' => $wpdb->insert_id));
            return true;
        } catch (\Exception $e) {
            greenmetrics_log('Exception in process_and_save_metrics', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'error');
            return GreenMetrics_Error_Handler::handle_exception($e, 'tracking_exception');
        }
    }
} 