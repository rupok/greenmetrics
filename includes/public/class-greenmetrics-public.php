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
        if (!isset($settings['enable_badge']) || $settings['enable_badge'] != 1) {
            return '';
        }

        $attributes = shortcode_atts(array(
            'size' => 'medium',
            'style' => 'light',
            'placement' => 'bottom-right'
        ), $atts);

        ob_start();
        include GREENMETRICS_PLUGIN_DIR . 'public/partials/greenmetrics-badge.php';
        return ob_get_clean();
    }
} 