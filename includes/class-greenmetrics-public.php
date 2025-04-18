<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */

namespace GreenMetrics;

/**
 * The public-facing functionality of the plugin.
 */
class GreenMetrics_Public {
    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
        add_shortcode('greenmetrics_badge', array($this, 'render_badge_shortcode'));
        add_action('init', array($this, 'register_blocks'));
        add_action('wp_ajax_greenmetrics_tracking', array($this, 'handle_tracking'));
        add_action('wp_ajax_nopriv_greenmetrics_tracking', array($this, 'handle_tracking'));
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'greenmetrics-public',
            GREENMETRICS_PLUGIN_URL . 'public/css/greenmetrics-public.css',
            array(),
            GREENMETRICS_VERSION,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function enqueue_scripts() {
        // Get settings
        $options = get_option('greenmetrics_settings');
        $tracking_enabled = isset($options['tracking_enabled']) ? $options['tracking_enabled'] : 0;

        // Get the current page ID
        $page_id = get_queried_object_id();
        if (!$page_id) {
            $page_id = get_the_ID();
        }

        // Ensure we have a valid REST URL
        $rest_url = get_rest_url(null, 'greenmetrics/v1');
        if (!$rest_url) {
            error_log('GreenMetrics: Failed to get REST URL');
            return;
        }

        // Create nonce for tracking
        $tracking_nonce = wp_create_nonce('greenmetrics_tracking');

        // Get plugin base URL
        $plugin_url = plugins_url('', dirname(dirname(__FILE__)));

        // Enqueue public script
        wp_enqueue_script(
            'greenmetrics-public',
            $plugin_url . '/greenmetrics/public/js/greenmetrics-public.js',
            array('jquery'),
            GREENMETRICS_VERSION,
            true
        );

        wp_localize_script('greenmetrics-public', 'greenmetricsPublic', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => $rest_url,
            'nonce' => wp_create_nonce('wp_rest')
        ));
    }

    /**
     * Check if tracking is enabled.
     *
     * @return bool Whether tracking is enabled.
     */
    private function is_tracking_enabled() {
        $options = get_option('greenmetrics_settings');
        return isset($options['tracking_enabled']) && $options['tracking_enabled'];
    }

    /**
     * Render the badge shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_badge_shortcode($atts) {
        $options = get_option('greenmetrics_settings');
        if (!isset($options['enable_badge']) || !$options['enable_badge']) {
            return '';
        }

        // Get settings with defaults
        $settings = array_merge(
            array(
                'carbon_intensity' => 0.475, // Default carbon intensity factor (kg CO2/kWh)
                'energy_per_byte' => 0.000000000072 // Default energy per byte (kWh/byte)
            ),
            is_array($options) ? $options : array()
        );

        // Get stats from tracker
        $tracker = GreenMetrics_Tracker::get_instance();
        $stats = $tracker->get_stats();

        // Calculate metrics based on actual data
        $data_transfer = isset($stats['avg_data_transfer']) ? floatval($stats['avg_data_transfer']) : 0;
        $load_time = isset($stats['avg_load_time']) ? floatval($stats['avg_load_time']) : 0;
        $requests = isset($stats['total_views']) ? intval($stats['total_views']) : 0;

        // Calculate CO2 emissions and energy consumption
        $energy_consumption = $data_transfer * $settings['energy_per_byte'];
        $co2_emissions = $energy_consumption * $settings['carbon_intensity'];

        // Calculate performance score
        $performance_score = 100;
        if ($load_time > 0) {
            $performance_score = max(0, 100 - ($load_time * 10));
        }

        // Parse attributes
        $atts = shortcode_atts(array(
            'position' => '',
            'theme' => '',
            'size' => ''
        ), $atts);

        // Build classes
        $wrapper_classes = array('greenmetrics-badge-wrapper');
        $badge_classes = array('greenmetrics-badge');

        if ($atts['position']) {
            $wrapper_classes[] = esc_attr($atts['position']);
        }
        if ($atts['theme']) {
            $badge_classes[] = esc_attr($atts['theme']);
        }
        if ($atts['size']) {
            $badge_classes[] = esc_attr($atts['size']);
        }

        $wrapper_class = implode(' ', $wrapper_classes);
        $badge_class = implode(' ', $badge_classes);

        // Build HTML
        $html = sprintf(
            '<div class="%1$s">
                <div class="%2$s">
                    <svg class="leaf-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M17,8C8,10,5.9,16.17,3.82,21.34L5.71,22l1-2.3A4.49,4.49,0,0,0,8,20C19,20,22,3,22,3,21,5,14,5.25,9,6.25S2,11.5,2,13.5a6.23,6.23,0,0,0,1.4,3.3L3,19l1.76,1.37A10.23,10.23,0,0,1,4,17C4,16,7,8,17,8Z"/>
                    </svg>
                    <span>Eco-Friendly Site</span>
                </div>
                <div class="greenmetrics-content">
                    <h3>Environmental Impact</h3>
                    <div class="greenmetrics-metrics">
                        <div class="greenmetrics-metric">
                            <div class="greenmetrics-metric-label">
                                <span>Carbon Footprint</span>
                            </div>
                            <div class="greenmetrics-metric-value">%3$s</div>
                        </div>
                        <div class="greenmetrics-metric">
                            <div class="greenmetrics-metric-label">
                                <span>Energy Consumption</span>
                            </div>
                            <div class="greenmetrics-metric-value">%4$s</div>
                        </div>
                        <div class="greenmetrics-metric">
                            <div class="greenmetrics-metric-label">
                                <span>Data Transfer</span>
                            </div>
                            <div class="greenmetrics-metric-value">%5$s</div>
                        </div>
                        <div class="greenmetrics-metric">
                            <div class="greenmetrics-metric-label">
                                <span>Number of Requests</span>
                            </div>
                            <div class="greenmetrics-metric-value">%6$s</div>
                        </div>
                        <div class="greenmetrics-metric">
                            <div class="greenmetrics-metric-label">
                                <span>Performance Score</span>
                            </div>
                            <div class="greenmetrics-metric-value">%7$s</div>
                        </div>
                    </div>
                </div>
            </div>',
            esc_attr($wrapper_class),
            esc_attr($badge_class),
            esc_html(round($co2_emissions, 2) . 'g CO2'),
            esc_html(round($energy_consumption, 2) . ' kWh'),
            esc_html(round($data_transfer / 1024, 2) . ' KB'),
            esc_html($requests),
            esc_html(round($performance_score) . '%')
        );

        return $html;
    }

    /**
     * Register blocks
     */
    public function register_blocks() {
        if (!function_exists('register_block_type')) {
            return;
        }

        // Register the badge block
        register_block_type(
            GREENMETRICS_PLUGIN_DIR . 'public/js/blocks/badge',
            array(
                'render_callback' => array($this, 'render_badge_block')
            )
        );

        // Ensure styles are loaded on the frontend
        wp_enqueue_style(
            'greenmetrics-badge',
            GREENMETRICS_PLUGIN_URL . 'public/js/blocks/badge/build/style-index.css',
            array(),
            GREENMETRICS_VERSION
        );
    }

    /**
     * Render the badge block
     *
     * @param array $attributes Block attributes
     * @return string HTML output
     */
    public function render_badge_block($attributes) {
        $options = get_option('greenmetrics_settings');
        if (!isset($options['enable_badge']) || !$options['enable_badge']) {
            return '';
        }

        // Include and get icons
        $icons_file = GREENMETRICS_PLUGIN_DIR . 'public/js/blocks/badge/src/icons.php';
        if (!file_exists($icons_file)) {
            error_log('GreenMetrics: Icons file not found at ' . $icons_file);
            return '';
        }
        include $icons_file;
        if (!isset($icons) || !is_array($icons)) {
            error_log('GreenMetrics: Icons array not properly defined');
            // Fallback icon
            $iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>';
        } else {
            // Get the selected icon's SVG content
            $iconName = $attributes['iconName'] ?? 'chart-bar';
            error_log('GreenMetrics: Selected icon name: ' . $iconName); // Debug log
            $selectedIcon = null;
            foreach ($icons as $icon) {
                if ($icon['id'] === $iconName) {
                    $selectedIcon = $icon;
                    break;
                }
            }
            if ($selectedIcon) {
                error_log('GreenMetrics: Found icon: ' . $selectedIcon['id']); // Debug log
                $iconSvg = $selectedIcon['svg'];
            } else {
                error_log('GreenMetrics: Icon not found: ' . $iconName); // Debug log
                $iconSvg = $icons[0]['svg'];
            }
        }

        // Get stats from tracker
        $tracker = GreenMetrics_Tracker::get_instance();
        $stats = $tracker->get_stats();

        // Format stats
        $data_transfer = isset($stats['avg_data_transfer']) ? number_format($stats['avg_data_transfer'] / 1024, 2) . ' KB' : '0 KB';
        $load_time = isset($stats['avg_load_time']) ? number_format($stats['avg_load_time'], 2) . ' s' : '0 s';
        $total_views = isset($stats['total_views']) ? number_format($stats['total_views']) : '0';

        // Extract attributes with defaults
        $text = $attributes['text'] ?? 'Eco-Friendly Site';
        $showText = $attributes['showText'] ?? true;
        $textColor = $attributes['textColor'] ?? '#ffffff';
        $textFontSize = $attributes['textFontSize'] ?? 14;
        $alignment = $attributes['alignment'] ?? 'left';
        $backgroundColor = $attributes['backgroundColor'] ?? '#4CAF50';
        $iconColor = $attributes['iconColor'] ?? '#ffffff';
        $fontSize = $attributes['fontSize'] ?? 14;
        $showIcon = $attributes['showIcon'] ?? true;
        $iconSize = $attributes['iconSize'] ?? 20;
        $borderRadius = $attributes['borderRadius'] ?? 4;
        $padding = $attributes['padding'] ?? 8;
        $showContent = $attributes['showContent'] ?? true;
        $contentTitle = $attributes['contentTitle'] ?? 'Environmental Impact';
        $selectedMetrics = $attributes['selectedMetrics'] ?? ['carbon_footprint', 'energy_consumption'];
        $customContent = $attributes['customContent'] ?? '';
        $contentBackgroundColor = $attributes['contentBackgroundColor'] ?? '#ffffff';
        $contentTextColor = $attributes['contentTextColor'] ?? '#333333';
        $animationDuration = $attributes['animationDuration'] ?? 300;
        $position = $attributes['position'] ?? 'bottom-right';
        $theme = $attributes['theme'] ?? 'light';
        $size = $attributes['size'] ?? 'medium';

        // Get settings with defaults
        $settings = array_merge(
            array(
                'carbon_intensity' => 0.475, // Default carbon intensity factor (kg CO2/kWh)
                'energy_per_byte' => 0.000000000072 // Default energy per byte (kWh/byte)
            ),
            is_array($options) ? $options : array()
        );

        // Debug log all attributes
        error_log('GreenMetrics: Block attributes: ' . print_r($attributes, true));

        // Build HTML
        $html = sprintf(
            '<div class="wp-block-greenmetrics-badge">
                <div class="wp-block-greenmetrics-badge-wrapper %1$s">
                    <div class="wp-block-greenmetrics-badge %2$s %3$s" style="background-color:%4$s;color:%5$s;padding:%6$spx;border-radius:%7$spx;font-size:%8$spx;text-align:%9$s;cursor:%10$s">
                        %11$s
                        %12$s
                    </div>
                    %13$s
                </div>
            </div>',
            esc_attr($position),
            esc_attr($theme),
            esc_attr($size),
            esc_attr($backgroundColor),
            esc_attr($textColor),
            esc_attr($padding),
            esc_attr($borderRadius),
            esc_attr($fontSize),
            esc_attr($alignment),
            $showContent ? 'pointer' : 'default',
            $showIcon ? sprintf(
                '<div class="wp-block-greenmetrics-badge__icon" style="width:%1$spx;height:%1$spx;color:%2$s">%3$s</div>',
                esc_attr($iconSize),
                esc_attr($iconColor),
                $iconSvg
            ) : '',
            $showText ? sprintf(
                '<span style="color:%1$s;font-size:%2$spx">%3$s</span>',
                esc_attr($textColor),
                esc_attr($textFontSize),
                esc_html($text)
            ) : '',
            $showContent ? sprintf(
                '<div class="wp-block-greenmetrics-content" style="background-color:%1$s;color:%2$s;transition:all %3$sms ease-in-out">
                    <h3>%4$s</h3>
                    <div class="wp-block-greenmetrics-metrics">%5$s</div>
                    %6$s
                </div>',
                esc_attr($contentBackgroundColor),
                esc_attr($contentTextColor),
                esc_attr($animationDuration),
                esc_html($contentTitle),
                implode('', array_map(function($metric) use ($stats, $settings) {
                    // Calculate metrics based on actual data
                    $data_transfer = isset($stats['avg_data_transfer']) ? floatval($stats['avg_data_transfer']) : 0;
                    $load_time = isset($stats['avg_load_time']) ? floatval($stats['avg_load_time']) : 0;
                    $requests = isset($stats['total_views']) ? intval($stats['total_views']) : 0;

                    // Calculate CO2 emissions and energy consumption using settings with defaults
                    $energy_consumption = $data_transfer * $settings['energy_per_byte'];
                    $co2_emissions = $energy_consumption * $settings['carbon_intensity'];

                    // Calculate performance score
                    $performance_score = 100;
                    if ($load_time > 0) {
                        $performance_score = max(0, 100 - ($load_time * 10));
                    }

                    $label = '';
                    $value = '';
                    switch ($metric) {
                        case 'carbon_footprint':
                            $label = 'Carbon Footprint';
                            $value = round($co2_emissions, 2) . 'g CO2';
                            break;
                        case 'energy_consumption':
                            $label = 'Energy Consumption';
                            $value = round($energy_consumption, 2) . ' kWh';
                            break;
                        case 'data_transfer':
                            $label = 'Data Transfer';
                            $value = round($data_transfer / 1024, 2) . ' KB';
                            break;
                        case 'requests':
                            $label = 'Number of Requests';
                            $value = $requests;
                            break;
                        case 'performance_score':
                            $label = 'Performance Score';
                            $value = round($performance_score) . '%';
                            break;
                    }
                    return sprintf(
                        '<div class="wp-block-greenmetrics-metric">
                            <div class="metric-label">
                                <span>%1$s</span>
                                <span class="metric-value">%2$s</span>
                            </div>
                        </div>',
                        esc_html($label),
                        esc_html($value)
                    );
                }, $selectedMetrics)),
                $customContent ? sprintf(
                    '<div class="wp-block-greenmetrics-custom-content">%s</div>',
                    wp_kses_post($customContent)
                ) : ''
            ) : ''
        );

        return $html;
    }

    /**
     * Enqueue block editor assets
     */
    public function enqueue_editor_assets() {
        $asset_file = include(GREENMETRICS_PLUGIN_DIR . 'public/js/blocks/badge/build/index.asset.php');

        wp_enqueue_script(
            'greenmetrics-badge-editor',
            GREENMETRICS_PLUGIN_URL . 'public/js/blocks/badge/build/index.js',
            array_merge($asset_file['dependencies'], array('wp-blocks', 'wp-element', 'wp-editor')),
            $asset_file['version'],
            true
        );

        wp_enqueue_style(
            'greenmetrics-badge-editor',
            GREENMETRICS_PLUGIN_URL . 'public/js/blocks/badge/build/index.css',
            array('wp-edit-blocks'),
            $asset_file['version']
        );

        // Create nonce for tracking
        $tracking_nonce = wp_create_nonce('greenmetrics_tracking');

        // Localize script for the block editor
        wp_localize_script('greenmetrics-badge-editor', 'greenmetricsTracking', array(
            'nonce' => $tracking_nonce,
            'ajax_url' => admin_url('admin-ajax.php'),
            'action' => 'greenmetrics_tracking'
        ));
    }

    public function handle_tracking() {
        error_log('GreenMetrics: Received tracking request');
        error_log('GreenMetrics: POST data: ' . print_r($_POST, true));

        if (!isset($_POST['nonce'])) {
            error_log('GreenMetrics: No nonce provided');
            wp_send_json_error('No nonce provided');
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'greenmetrics_tracking')) {
            error_log('GreenMetrics: Invalid nonce');
            wp_send_json_error('Invalid nonce');
            return;
        }

        if (!isset($_POST['metrics'])) {
            error_log('GreenMetrics: No metrics provided');
            wp_send_json_error('No metrics provided');
            return;
        }

        $metrics = json_decode(stripslashes($_POST['metrics']), true);
        error_log('GreenMetrics: Decoded metrics: ' . print_r($metrics, true));
        
        if (!is_array($metrics)) {
            error_log('GreenMetrics: Invalid metrics format');
            wp_send_json_error('Invalid metrics format');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'greenmetrics_stats';

        // Calculate carbon footprint (in grams of CO2)
        // Using average of 0.2g CO2 per MB of data transfer
        $carbon_footprint = ($metrics['data_transfer'] / (1024 * 1024)) * 0.2;

        // Calculate energy consumption (in kWh)
        // Using average of 0.0001 kWh per MB of data transfer
        $energy_consumption = ($metrics['data_transfer'] / (1024 * 1024)) * 0.0001;

        // Calculate performance score (0-100)
        // Based on load time and number of requests
        $load_time_score = max(0, 100 - ($metrics['load_time'] * 10));
        $requests_score = max(0, 100 - ($metrics['requests'] * 2));
        $performance_score = ($load_time_score + $requests_score) / 2;

        $result = $wpdb->insert(
            $table_name,
            array(
                'page_id' => get_the_ID(),
                'data_transfer' => $metrics['data_transfer'],
                'load_time' => $metrics['load_time'],
                'requests' => $metrics['requests'],
                'carbon_footprint' => $carbon_footprint,
                'energy_consumption' => $energy_consumption,
                'performance_score' => $performance_score
            ),
            array(
                '%d',
                '%d',
                '%f',
                '%d',
                '%f',
                '%f',
                '%f'
            )
        );

        if ($result === false) {
            error_log('GreenMetrics: Database error: ' . $wpdb->last_error);
            wp_send_json_error('Failed to save metrics: ' . $wpdb->last_error);
            return;
        }

        error_log('GreenMetrics: Metrics saved successfully');
        wp_send_json_success('Metrics saved successfully');
    }
} 