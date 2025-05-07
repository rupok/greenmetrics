<?php
/**
 * Settings manager class.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */

namespace GreenMetrics;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    exit;
}

/**
 * The settings manager handles all plugin settings access
 */
class GreenMetrics_Settings_Manager {

    /**
     * The option name in the WordPress database
     */
    private $option_name = 'greenmetrics_settings';

    /**
     * The single instance of the class.
     *
     * @var GreenMetrics_Settings_Manager
     */
    private static $instance = null;

    /**
     * Cached settings to avoid multiple database queries
     */
    private $settings_cache = null;

    /**
     * Default settings values
     */
    private $defaults = array(
        /* Carbon & Energy */
        'carbon_intensity'                   => 0.475,
        'energy_per_byte'                    => 0.000000000072,

        /* Tracking & Display */
        'tracking_enabled'                   => 1,
        'enable_badge'                       => 1,
        'display_icon'                       => 1,
        'badge_position'                     => 'bottom-right',
        'badge_theme'                        => 'light',
        'badge_size'                         => 'medium',
        'badge_text'                         => 'Eco-Friendly Site',
        'badge_icon_type'                    => 'leaf',
        'badge_custom_icon'                  => '',
        'badge_background_color'             => '#4CAF50',
        'badge_text_color'                   => '#ffffff',
        'badge_icon_color'                   => '#ffffff',

        /* Popup Settings */
        'enable_popup'                       => 0,
        'popup_delay'                        => 3,
        'popup_session_views'                => 1,
        'popup_title'                        => 'Environmental Impact',
        'popup_content'                      => '',

        /* Data Management */
        'data_management_enabled'            => 1,
        'aggregation_age'                    => 30,
        'aggregation_type'                   => 'daily',
        'retention_period'                   => 90,
        'require_aggregation_before_pruning' => 1,

        /* Email Reporting */
        'email_reporting_enabled'            => 0,
        'email_reporting_frequency'          => 'weekly',
        'email_reporting_day'                => 1,
        'email_reporting_recipients'         => '',
        'email_reporting_subject'            => 'GreenMetrics Weekly Report for [site_name]',
        'email_reporting_include_stats'      => 1,
        'email_reporting_include_chart'      => 1,
        'email_reporting_header'             => '',
        'email_reporting_footer'             => '',
        'email_reporting_css'                => '',
        'email_template_style'               => 'default',
        'email_color_primary'                => '#4CAF50',
        'email_color_secondary'              => '#f9f9f9',
        'email_color_accent'                 => '#333333',
        'email_color_text'                   => '#333333',
        'email_color_background'             => '#ffffff',
    );

    /**
     * Initialize the class.
     */
    private function __construct() {
        greenmetrics_log( 'Settings manager initialized' );
    }

    /**
     * Get the singleton instance.
     *
     * @return GreenMetrics_Settings_Manager
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the full settings array or individual setting.
     *
     * @param string|null $key     Optional. Specific setting key.
     * @param mixed       $default Default if key not set.
     * @return mixed|array
     */
    public function get( $key = null, $default = null ) {
        if ( null === $this->settings_cache ) {
            $this->settings_cache = get_option( $this->option_name, array() );
        }
        $settings = array_merge( $this->defaults, $this->settings_cache );
        if ( null !== $key ) {
            return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
        }
        return $settings;
    }

    /**
     * Update a single setting.
     *
     * @param string $key   Setting key.
     * @param mixed  $value Setting value.
     * @return bool
     */
    public function update( $key, $value ) {
        $settings = $this->get();
        $settings[ $key ] = $this->sanitize_setting_value( $key, $value );
        $result = update_option( $this->option_name, $settings );
        if ( $result ) {
            $this->settings_cache = $settings;
            greenmetrics_log( 'Setting updated', array( $key => $settings[ $key ] ) );
        } else {
            greenmetrics_log( 'Failed to update setting', array( $key => $settings[ $key ] ), 'error' );
        }
        return $result;
    }

    /**
     * Update multiple settings.
     *
     * @param array $settings Settings array.
     * @return bool|WP_Error
     */
    public function update_batch( $settings ) {
        if ( ! is_array( $settings ) ) {
            greenmetrics_log( 'Invalid settings array', $settings, 'error' );
            return GreenMetrics_Error_Handler::create_error( 'invalid_settings', 'Invalid settings array' );
        }
        $current = $this->get();
        $sanitized = array();
        foreach ( $settings as $k => $v ) {
            $sanitized[ $k ] = $this->sanitize_setting_value( $k, $v );
        }
        $updated = array_merge( $current, $sanitized );
        $result  = update_option( $this->option_name, $updated );
        if ( $result ) {
            $this->settings_cache = $updated;
            greenmetrics_log( 'Settings batch updated', $sanitized );
            return GreenMetrics_Error_Handler::success();
        }
        greenmetrics_log( 'Failed to update settings batch', $sanitized, 'error' );
        return GreenMetrics_Error_Handler::create_error( 'update_failed', 'Failed to update settings' );
    }

    /**
     * Sanitize single setting value.
     *
     * @param string $key   Setting key.
     * @param mixed  $value Raw value.
     * @return mixed Sanitized.
     */
    private function sanitize_setting_value( $key, $value ) {
        // Boolean fields
        $boolean_fields = array(
            'tracking_enabled', 'enable_badge', 'display_icon', 'enable_popup',
            'data_management_enabled', 'require_aggregation_before_pruning',
            'email_reporting_enabled', 'email_reporting_include_stats', 'email_reporting_include_chart',
        );
        if ( in_array( $key, $boolean_fields, true ) ) {
            return absint( $value ) ? 1 : 0;
        }

        // Select fields
        $selects = array(
            'badge_position'           => array( 'bottom-right','bottom-left','top-right','top-left' ),
            'badge_size'               => array( 'small','medium','large' ),
            'badge_theme'              => array( 'light','dark' ),
            'aggregation_type'         => array( 'daily','weekly','monthly' ),
            'email_reporting_frequency'=> array( 'daily','weekly','monthly' ),
            'email_template_style'     => array( 'default','minimal','modern','eco' ),
        );
        if ( isset( $selects[ $key ] ) ) {
            return in_array( $value, $selects[ $key ], true ) ? $value : reset( $selects[ $key ] );
        }

        // Text fields
        $text_fields = array( 'badge_text', 'popup_title', 'popover_title', 'popover_content_font', 'popover_metrics_font', 'email_reporting_subject' );
        if ( in_array( $key, $text_fields, true ) ) {
            return sanitize_text_field( $value );
        }

        // HTML fields
        $html_fields = array( 'email_reporting_header', 'email_reporting_footer', 'email_reporting_css', 'popup_content' );
        if ( in_array( $key, $html_fields, true ) ) {
            if ( 'email_reporting_css' === $key ) {
                return wp_strip_all_tags( $value );
            }
            $allowed = array(
                'a' => array( 'href'=>array(), 'title'=>array(), 'target'=>array(), 'rel'=>array(), 'class'=>array(), 'style'=>array() ),
                'br'=>array(), 'em'=>array(), 'strong'=>array(), 'span'=>array( 'class'=>array(), 'style'=>array() ),
                'p'=>array( 'class'=>array(), 'style'=>array() ), 'div'=>array( 'class'=>array(), 'style'=>array(), 'id'=>array() ),
            );
            return wp_kses( $value, $allowed );
        }

        // URL field
        if ( 'badge_custom_icon' === $key ) {
            return esc_url_raw( $value );
        }

        // Icon type
        if ( 'badge_icon_type' === $key ) {
            $icons = array( 'leaf','tree','globe','recycle','chart-bar','chart-line','chart-pie','analytics','performance','energy','water','eco','nature','sustainability','custom' );
            return in_array( $value, $icons, true ) ? $value : 'leaf';
        }

        // Color fields
        $color_fields = array(
            'badge_background_color','badge_text_color','badge_icon_color',
            'popover_bg_color','popover_text_color','popover_metrics_color',
            'popover_metrics_list_bg_color','popover_metrics_list_hover_bg_color',
            'email_color_primary','email_color_secondary','email_color_accent','email_color_text','email_color_background'
        );
        if ( in_array( $key, $color_fields, true ) ) {
            return sanitize_hex_color( $value );
        }

        // Numeric fields
        $numeric_fields = array(
            'carbon_intensity'    => array('min'=>0,'max'=>10,'default'=>0.475,'precision'=>3),
            'energy_per_byte'     => array('min'=>0,'max'=>0.0000001,'default'=>0.000000000072,'precision'=>12),
            'aggregation_age'     => array('min'=>1,'max'=>365,'default'=>30,'precision'=>0),
            'retention_period'    => array('min'=>1,'max'=>3650,'default'=>90,'precision'=>0),
            'email_reporting_day' => array('min'=>0,'max'=>31,'default'=>1,'precision'=>0),
            'popup_delay'         => array('min'=>0,'max'=>30,'default'=>3,'precision'=>0),
            'popup_session_views' => array('min'=>1,'max'=>10,'default'=>1,'precision'=>0),
        );
        if ( isset( $numeric_fields[ $key ] ) ) {
            $conf = $numeric_fields[ $key ];
            $num  = is_numeric( $value ) ? floatval( $value ) : $conf['default'];
            $num  = max( $conf['min'], min( $conf['max'], $num ) );
            return $conf['precision'] ? round( $num, $conf['precision'] ) : intval( $num );
        }

        // Fallback
        if ( is_string( $value ) ) {
            return sanitize_text_field( $value );
        }
        return $value;
    }

    /**
     * Sanitize all settings (Settings API).
     *
     * @param array $input Raw input.
     * @return array Sanitized.
     */
    public function sanitize_settings( $input ) {
        if ( ! is_array( $input ) ) {
            return $this->get();
        }
        $sanitized = $this->get();
        foreach ( $input as $k => $v ) {
            $sanitized[ $k ] = $this->sanitize_setting_value( $k, $v );
        }
        return $sanitized;
    }

    /**
     * Reset all settings to defaults.
     *
     * @return bool
     */
    public function reset() {
        $result = update_option( $this->option_name, $this->defaults );
        if ( $result ) {
            $this->settings_cache = $this->defaults;
            greenmetrics_log( 'Settings reset to defaults' );
        } else {
            greenmetrics_log( 'Failed to reset settings to defaults', null, 'error' );
        }
        return $result;
    }

    /**
     * Get default settings.
     *
     * @return array
     */
    public function get_defaults() {
        return $this->defaults;
    }

    /**
     * Check if a feature is enabled.
     *
     * @param string $feature Feature key.
     * @return bool
     */
    public function is_enabled( $feature ) {
        $val = $this->get( $feature, 0 );
        return ( $val === 1 || $val === '1' );
    }
}
