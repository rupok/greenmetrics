<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/public
 */

namespace GreenMetrics\Public;

class GreenMetrics_Public {
    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        greenmetrics_log('Public class initialized');
        $this->init_hooks();
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     */
    private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('greenmetrics_badge', array($this, 'green_badge_shortcode'));
        
        // Add tracking script if enabled
        $settings = get_option('greenmetrics_settings', array());
        if (isset($settings['tracking_enabled']) && $settings['tracking_enabled']) {
            add_action('wp_footer', array($this, 'add_tracking_script'));
            greenmetrics_log('Tracking enabled, adding tracking script to footer');
        } else {
            greenmetrics_log('Tracking disabled, not adding tracking script');
        }
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
        
        greenmetrics_log('Public styles enqueued');
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
            false
        );
        
        greenmetrics_log('Public scripts enqueued');
    }

    /**
     * Add tracking script to footer
     */
    public function add_tracking_script() {
        if (!is_admin()) {
            wp_enqueue_script(
                'greenmetrics-tracking',
                GREENMETRICS_PLUGIN_URL . 'public/js/greenmetrics-tracking.js',
                array('jquery'),
                GREENMETRICS_VERSION,
                true
            );
            
            wp_localize_script('greenmetrics-tracking', 'greenmetricsTracking', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('greenmetrics_track_page'),
                'page_id' => get_the_ID()
            ));
            
            greenmetrics_log('Tracking script added to page', get_the_ID());
        }
    }

    /**
     * Green badge shortcode handler.
     *
     * @param array $atts Shortcode attributes.
     * @return string The badge HTML.
     */
    public function green_badge_shortcode($atts) {
        $settings = get_option('greenmetrics_settings', array());
        greenmetrics_log('Public Badge shortcode - Settings retrieved', $settings);
        
        // Check if badge is enabled - handle both string '1' and integer 1
        $badge_enabled = isset($settings['enable_badge']) && 
                         ($settings['enable_badge'] === 1 || $settings['enable_badge'] === '1');
        
        if (!$badge_enabled) {
            greenmetrics_log('Badge display disabled by settings - Public class', [
                'enable_badge' => isset($settings['enable_badge']) ? $settings['enable_badge'] : 'not set',
                'badge_enabled' => $badge_enabled,
                'value_type' => isset($settings['enable_badge']) ? gettype($settings['enable_badge']) : 'undefined'
            ]);
            return '';
        }

        $attributes = shortcode_atts(array(
            'size' => 'medium',
            'style' => 'light',
            'placement' => 'bottom-right'
        ), $atts);
        
        greenmetrics_log('Rendering badge with attributes', $attributes);

        ob_start();
        include GREENMETRICS_PLUGIN_DIR . 'public/partials/greenmetrics-badge.php';
        return ob_get_clean();
    }
} 