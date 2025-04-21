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
        
        // Forward AJAX tracking requests to the tracker
        // This is deprecated and will be removed in a future version
        add_action('wp_ajax_greenmetrics_tracking', array($this, 'forward_tracking_request'));
        add_action('wp_ajax_nopriv_greenmetrics_tracking', array($this, 'forward_tracking_request'));
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
        
        greenmetrics_log('Enqueuing public scripts with settings', [
            'tracking_enabled' => $tracking_enabled,
            'options' => $options
        ]);

        // Get the current page ID
        $page_id = get_queried_object_id();
        if (!$page_id) {
            $page_id = get_the_ID();
        }

        // Ensure we have a valid REST URL
        $rest_url = get_rest_url(null, 'greenmetrics/v1');
        if (!$rest_url) {
            greenmetrics_log('Failed to get REST URL', null, 'error');
            return;
        }

        // Create nonce for REST API
        $rest_nonce = wp_create_nonce('wp_rest');

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

        // Localize script with essential data - use consistent parameter names
        wp_localize_script('greenmetrics-public', 'greenmetricsPublic', array(
            'ajax_url' => admin_url('admin-ajax.php'), // Keep for backward compatibility
            'rest_url' => $rest_url,
            'rest_nonce' => $rest_nonce, // Use only one parameter name
            'tracking_enabled' => $tracking_enabled ? true : false,
            'page_id' => $page_id
        ));
        
        greenmetrics_log('Public scripts enqueued successfully with REST nonce', $rest_nonce);
    }

    /**
     * Check if tracking is enabled.
     *
     * @return bool Whether tracking is enabled.
     */
    private function is_tracking_enabled() {
        return GreenMetrics_Settings_Manager::get_instance()->is_enabled('tracking_enabled');
    }

    /**
     * Add REST URL to script localization
     *
     * @param array $data The existing data.
     * @return array The data with REST URL added.
     */
    public function add_rest_url($data) {
        $data['rest_url'] = get_rest_url(null, 'greenmetrics/v1');
        
        if (!$data['rest_url']) {
            greenmetrics_log('Failed to get REST URL', null, 'error');
        }
        
        return $data;
    }

    /**
     * Render the badge shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_badge_shortcode($atts) {
        $settings_manager = GreenMetrics_Settings_Manager::get_instance();
        greenmetrics_log('Badge shortcode - Settings retrieved', $settings_manager->get());
        
        // Check if badge is enabled
        if (!$settings_manager->is_enabled('enable_badge')) {
            greenmetrics_log('Badge display disabled by settings', [
                'enable_badge' => $settings_manager->get('enable_badge'),
                'value_type' => gettype($settings_manager->get('enable_badge'))
            ]);
            return '';
        }

        // Parse attributes
        $atts = shortcode_atts(array(
            'position' => $settings_manager->get('badge_position', 'bottom-right'),
            'theme' => $settings_manager->get('badge_theme', 'light'),
            'size' => $settings_manager->get('badge_size', 'medium')
        ), $atts);

        // Get metrics data
        $metrics = $this->get_metrics_data();
        
        greenmetrics_log('Shortcode metrics', $metrics);

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

        // Format values with proper precision using formatting methods from the Calculator class
        $carbon_formatted = GreenMetrics_Calculator::format_carbon_emissions($metrics['carbon_footprint']);
        $energy_formatted = GreenMetrics_Calculator::format_energy_consumption($metrics['energy_consumption']);
        $data_formatted = GreenMetrics_Calculator::format_data_transfer($metrics['data_transfer']);
        $views_formatted = number_format($metrics['total_views']);
        $requests_formatted = number_format($metrics['requests']);
        $score_formatted = number_format($metrics['performance_score'], 2) . '%';

        greenmetrics_log('Formatted metrics values', [
            'carbon' => $carbon_formatted,
            'energy' => $energy_formatted,
            'data' => $data_formatted
        ]);

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
                                <span>Page Views</span>
                            </div>
                            <div class="greenmetrics-metric-value">%6$s</div>
                        </div>
                        <div class="greenmetrics-metric">
                            <div class="greenmetrics-metric-label">
                                <span>HTTP Requests</span>
                            </div>
                            <div class="greenmetrics-metric-value">%7$s</div>
                        </div>
                        <div class="greenmetrics-metric">
                            <div class="greenmetrics-metric-label">
                                <span>Performance Score</span>
                            </div>
                            <div class="greenmetrics-metric-value">%8$s</div>
                        </div>
                    </div>
                </div>
            </div>',
            esc_attr($wrapper_class),
            esc_attr($badge_class),
            esc_html($carbon_formatted),
            esc_html($energy_formatted),
            esc_html($data_formatted),
            esc_html($views_formatted),
            esc_html($requests_formatted),
            esc_html($score_formatted)
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
        $settings_manager = GreenMetrics_Settings_Manager::get_instance();
        
        // IMPORTANT: For blocks, we ALWAYS display the badge regardless of the global enable_badge setting.
        // This is different from shortcodes which respect the global setting.
        // The logic is: if a user manually adds a block to their content, they want to see it
        // regardless of the global setting which controls automatic placement.
        greenmetrics_log('Block rendering - ALWAYS ignoring enable_badge setting', [
            'current_setting' => $settings_manager->get('enable_badge')
        ]);

        // Set default values for attributes
        $attributes = wp_parse_args($attributes, array(
            'text' => 'Eco-Friendly Site',
            'alignment' => 'left',
            'backgroundColor' => '#4CAF50',
            'textColor' => '#ffffff',
            'iconColor' => '#ffffff',
            'showIcon' => true,
            'iconName' => 'chart-bar',
            'iconSize' => 20,
            'borderRadius' => 4,
            'padding' => 8,
            'showContent' => true,
            'contentTitle' => 'Environmental Impact',
            'selectedMetrics' => ['carbon_footprint', 'energy_consumption'],
            'customContent' => '',
            'contentBackgroundColor' => '#ffffff',
            'contentTextColor' => '#333333',
            'contentPadding' => 15,
            'animationDuration' => 300,
            'showText' => true,
            'textFontSize' => 14,
            'position' => 'bottom-right',
            'theme' => 'light',
            'size' => 'medium'
        ));

        // Include and get icons
        $icons_file = GREENMETRICS_PLUGIN_DIR . 'public/js/blocks/badge/src/icons.php';
        if (!file_exists($icons_file)) {
            error_log('GreenMetrics: Icons file not found at ' . $icons_file);
            return '';
        }
        include $icons_file;
        if (!isset($icons) || !is_array($icons)) {
            error_log('GreenMetrics: Icons array not properly defined');
            return '';
        }

        // Get the selected icon's SVG content
        $iconName = $attributes['iconName'];
        $selectedIcon = null;
        foreach ($icons as $icon) {
            if ($icon['id'] === $iconName) {
                $selectedIcon = $icon;
                break;
            }
        }
        $iconSvg = $selectedIcon ? $selectedIcon['svg'] : $icons[0]['svg'];

        // Get metrics data
        $metrics = $this->get_metrics_data();

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
            esc_attr($attributes['position']),
            esc_attr($attributes['theme']),
            esc_attr($attributes['size']),
            esc_attr($attributes['backgroundColor']),
            esc_attr($attributes['textColor']),
            esc_attr($attributes['padding']),
            esc_attr($attributes['borderRadius']),
            esc_attr($attributes['textFontSize']),
            esc_attr($attributes['alignment']),
            $attributes['showContent'] ? 'pointer' : 'default',
            $attributes['showIcon'] ? sprintf(
                '<div class="wp-block-greenmetrics-badge__icon" style="width:%1$spx;height:%1$spx;color:%2$s">%3$s</div>',
                esc_attr($attributes['iconSize']),
                esc_attr($attributes['iconColor']),
                $iconSvg
            ) : '',
            $attributes['showText'] ? sprintf(
                '<span style="color:%1$s;font-size:%2$spx">%3$s</span>',
                esc_attr($attributes['textColor']),
                esc_attr($attributes['textFontSize']),
                esc_html($attributes['text'])
            ) : '',
            $attributes['showContent'] ? sprintf(
                '<div class="wp-block-greenmetrics-content" style="background-color:%1$s;color:%2$s;transition:all %3$sms ease-in-out">
                    <h3>%4$s</h3>
                    <div class="wp-block-greenmetrics-metrics">%5$s</div>
                    %6$s
                </div>',
                esc_attr($attributes['contentBackgroundColor']),
                esc_attr($attributes['contentTextColor']),
                esc_attr($attributes['animationDuration']),
                esc_html($attributes['contentTitle']),
                implode('', array_map(function($metric) use ($metrics) {
                    $label = '';
                    $value = '';
                    switch ($metric) {
                        case 'carbon_footprint':
                            $label = 'Carbon Footprint';
                            $value = GreenMetrics_Calculator::format_carbon_emissions($metrics['carbon_footprint']);
                            break;
                        case 'energy_consumption':
                            $label = 'Energy Consumption';
                            $value = GreenMetrics_Calculator::format_energy_consumption($metrics['energy_consumption']);
                            break;
                        case 'data_transfer':
                            $label = 'Data Transfer';
                            $value = GreenMetrics_Calculator::format_data_transfer($metrics['data_transfer']);
                            break;
                        case 'views':
                            $label = 'Page Views';
                            $value = number_format($metrics['total_views']);
                            break;
                        case 'http_requests':
                            $label = 'HTTP Requests';
                            $value = number_format($metrics['requests']);
                            break;
                        case 'performance_score':
                            $label = 'Performance Score';
                            $value = number_format($metrics['performance_score'], 2) . '%';
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
                }, $attributes['selectedMetrics'])),
                $attributes['customContent'] ? sprintf(
                    '<div class="wp-block-greenmetrics-custom-content">%s</div>',
                    wp_kses_post($attributes['customContent'])
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

        // Create nonce for REST API
        $rest_nonce = wp_create_nonce('wp_rest');
        $rest_url = get_rest_url(null, 'greenmetrics/v1');

        // Localize script for the block editor
        wp_localize_script('greenmetrics-badge-editor', 'greenmetricsTracking', array(
            'rest_nonce' => $rest_nonce,
            'rest_url' => $rest_url,
            'ajax_url' => admin_url('admin-ajax.php'), // Keep for backward compatibility
            'nonce' => $rest_nonce, // Keep for backward compatibility
            'action' => 'greenmetrics_tracking' // Keep for backward compatibility
        ));
    }

    /**
     * Forward tracking requests to the tracker class to avoid duplicate code
     * This maintains backward compatibility with existing JavaScript files
     * 
     * @deprecated 1.1.0 Use the REST API endpoint /greenmetrics/v1/track instead.
     */
    public function forward_tracking_request() {
        greenmetrics_log('Forwarding tracking request to tracker class');
        
        // Get the tracker instance
        $tracker = GreenMetrics_Tracker::get_instance();
        
        // Forward the request to the tracker's handler
        $tracker->handle_tracking_request_from_post();
        
        // The tracker will handle the response, so we don't need to do anything else
        exit;
    }

    /**
     * Get metrics data for the current page
     *
     * @return array Metrics data
     */
    private function get_metrics_data() {
        greenmetrics_log('Starting get_metrics_data');
        
        // Get stats from tracker - all calculations are now done efficiently in SQL
        $tracker = GreenMetrics_Tracker::get_instance();
        $stats = $tracker->get_stats();
        greenmetrics_log('Stats from tracker', $stats);

        // Extract and validate the data needed for display
        // No need to recalculate these values as they're already properly calculated in the tracker
        $result = array(
            'data_transfer' => isset($stats['total_data_transfer']) ? floatval($stats['total_data_transfer']) : 0,
            'load_time' => isset($stats['avg_load_time']) ? floatval($stats['avg_load_time']) : 0,
            'requests' => isset($stats['total_requests']) ? intval($stats['total_requests']) : 0,
            'total_views' => isset($stats['total_views']) ? intval($stats['total_views']) : 0,
            'energy_consumption' => isset($stats['total_energy_consumption']) ? floatval($stats['total_energy_consumption']) : 0,
            'carbon_footprint' => isset($stats['total_carbon_footprint']) ? floatval($stats['total_carbon_footprint']) : 0,
            'performance_score' => isset($stats['avg_performance_score']) ? floatval($stats['avg_performance_score']) : 100
        );
        
        // Log metrics data summary
        $metrics_summary = [
            'data_transfer' => $result['data_transfer'],
            'energy_consumption' => $result['energy_consumption'],
            'carbon_footprint' => $result['carbon_footprint'],
            'performance_score' => $result['performance_score']
        ];
        greenmetrics_log('Metrics data summary', $metrics_summary);
        
        return $result;
    }

    /**
     * Get SVG content for an icon
     *
     * @param string $icon_name Icon name
     * @return string|null SVG content or null if not found
     */
    private function get_icon_svg($icon_name) {
        // Include and get icons
        $icons_file = GREENMETRICS_PLUGIN_DIR . 'public/js/blocks/badge/src/icons.php';
        if (!file_exists($icons_file)) {
            greenmetrics_log('Icons file not found', $icons_file, 'error');
            return null;
        }
        include $icons_file;
        if (!isset($icons) || !is_array($icons)) {
            greenmetrics_log('Icons array not properly defined', null, 'error');
            return null;
        }

        // Get the selected icon's SVG content
        $icon_name = $icon_name ?? 'chart-bar';
        foreach ($icons as $icon) {
            if ($icon['id'] === $icon_name) {
                return $icon['svg'];
            }
        }

        // Return first icon as fallback
        return $icons[0]['svg'] ?? null;
    }

    private function get_icon_html($icon_name) {
        // Include and get icons
        $icons_file = GREENMETRICS_PLUGIN_DIR . 'public/js/blocks/badge/src/icons.php';
        if (!file_exists($icons_file)) {
            greenmetrics_log('Icons file not found', $icons_file, 'error');
            return '';
        }
        include $icons_file;
        if (!isset($icons) || !is_array($icons)) {
            greenmetrics_log('Icons array not properly defined', null, 'error');
            return '';
        }

        // Get the selected icon's SVG content
        $icon_name = $icon_name ?? 'chart-bar';
        foreach ($icons as $icon) {
            if ($icon['id'] === $icon_name) {
                return $icon['svg'];
            }
        }

        // Return first icon as fallback
        return $icons[0]['svg'] ?? '';
    }

    /**
     * Inject the tracking script into the page footer.
     */
    public function inject_tracking_script() {
        if (!$this->is_tracking_enabled()) {
            greenmetrics_log('Tracking disabled, not injecting script');
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
} 