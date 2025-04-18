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
        add_shortcode('greenmetrics_badge', array($this, 'render_badge_shortcode'));
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
        wp_enqueue_script(
            'greenmetrics-public',
            GREENMETRICS_PLUGIN_URL . 'public/js/greenmetrics-public.js',
            array('jquery'),
            GREENMETRICS_VERSION,
            true
        );

        wp_localize_script('greenmetrics-public', 'greenmetricsPublic', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('greenmetrics_public_nonce')
        ));
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

        // Get stats from tracker
        $tracker = GreenMetrics_Tracker::get_instance();
        $stats = $tracker->get_stats();

        // Format stats
        $data_transfer = isset($stats['avg_data_transfer']) ? number_format($stats['avg_data_transfer'] / 1024, 2) . ' KB' : '0 KB';
        $load_time = isset($stats['avg_load_time']) ? number_format($stats['avg_load_time'], 2) . ' s' : '0 s';
        $total_views = isset($stats['total_views']) ? number_format($stats['total_views']) : '0';

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
                                <span>Data Transfer</span>
                            </div>
                            <div class="greenmetrics-metric-value">%3$s</div>
                        </div>
                        <div class="greenmetrics-metric">
                            <div class="greenmetrics-metric-label">
                                <span>Load Time</span>
                            </div>
                            <div class="greenmetrics-metric-value">%4$s</div>
                        </div>
                        <div class="greenmetrics-metric">
                            <div class="greenmetrics-metric-label">
                                <span>Total Views</span>
                            </div>
                            <div class="greenmetrics-metric-value">%5$s</div>
                        </div>
                    </div>
                </div>
            </div>',
            esc_attr($wrapper_class),
            esc_attr($badge_class),
            esc_html($data_transfer),
            esc_html($load_time),
            esc_html($total_views)
        );

        return $html;
    }
} 