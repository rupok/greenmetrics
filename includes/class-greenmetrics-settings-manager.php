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
	die;
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
		'carbon_intensity'        => 0.475,         // Default carbon intensity factor (kg CO2/kWh)
		'energy_per_byte'         => 0.000000000072, // Default energy per byte (kWh/byte)
		'tracking_enabled'        => 0,             // Tracking disabled by default
		'enable_badge'            => 0,             // Badge disabled by default
		'display_icon'            => 1,             // Display icon enabled by default
		'badge_position'          => 'bottom-right', // Default badge position
		'badge_theme'             => 'light',       // Default badge theme
		'badge_size'              => 'medium',      // Default badge size
		'badge_text'              => 'Eco-Friendly Site', // Default badge text
		'badge_icon_type'         => 'leaf',        // Default icon type
		'badge_custom_icon'       => '',            // Custom icon path (empty by default)
		'badge_background_color'  => '#4CAF50',     // Default badge background color
		'badge_text_color'        => '#ffffff',     // Default badge text color
		'badge_icon_color'        => '#ffffff',     // Default badge icon color
	);

	/**
	 * Initialize the class.
	 */
	private function __construct() {
		// Private constructor to enforce singleton
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
	 * Get the full settings array or individual setting
	 *
	 * @param string|null $key Optional. The specific setting key to retrieve.
	 * @param mixed       $default Default value if setting doesn't exist
	 * @return mixed|array The setting value or entire settings array
	 */
	public function get( $key = null, $default = null ) {
		// Get settings from cache or database
		if ( null === $this->settings_cache ) {
			$this->settings_cache = get_option( $this->option_name, array() );
		}

		// Ensure all defaults are set
		$settings = array_merge( $this->defaults, $this->settings_cache );

		// Return single setting if key is provided
		if ( null !== $key ) {
			return isset( $settings[ $key ] ) ? $settings[ $key ] : ( null !== $default ? $default : null );
		}

		// Return all settings
		return $settings;
	}

	/**
	 * Update a specific setting
	 *
	 * @param string $key The setting key
	 * @param mixed  $value The setting value
	 * @return bool Success status
	 */
	public function update( $key, $value ) {
		$settings         = $this->get();
		$settings[ $key ] = $value;

		$result = update_option( $this->option_name, $settings );

		if ( $result ) {
			// Update cache
			$this->settings_cache = $settings;
			greenmetrics_log( 'Setting updated', array( $key => $value ) );
		} else {
			greenmetrics_log( 'Failed to update setting', array( $key => $value ), 'error' );
		}

		return $result;
	}

	/**
	 * Update multiple settings at once
	 *
	 * @param array $settings Settings array (key => value)
	 * @return bool Success status
	 */
	public function update_batch( $settings ) {
		if ( ! is_array( $settings ) ) {
			greenmetrics_log( 'Invalid settings array', $settings, 'error' );
			return GreenMetrics_Error_Handler::create_error( 'invalid_settings', 'Invalid settings array' );
		}

		$current_settings = $this->get();
		$updated_settings = array_merge( $current_settings, $settings );

		$result = update_option( $this->option_name, $updated_settings );

		if ( $result ) {
			// Update cache
			$this->settings_cache = $updated_settings;
			greenmetrics_log( 'Settings batch updated', $settings );
			return GreenMetrics_Error_Handler::success();
		} else {
			greenmetrics_log( 'Failed to update settings batch', $settings, 'error' );
			return GreenMetrics_Error_Handler::create_error( 'update_failed', 'Failed to update settings' );
		}
	}

	/**
	 * Reset all settings to defaults
	 *
	 * @return bool Success status
	 */
	public function reset() {
		$result = update_option( $this->option_name, $this->defaults );

		if ( $result ) {
			// Update cache
			$this->settings_cache = $this->defaults;
			greenmetrics_log( 'Settings reset to defaults' );
		} else {
			greenmetrics_log( 'Failed to reset settings to defaults', null, 'error' );
		}

		return $result;
	}

	/**
	 * Get default settings
	 *
	 * @return array Default settings
	 */
	public function get_defaults() {
		return $this->defaults;
	}

	/**
	 * Check if a specific feature is enabled
	 *
	 * @param string $feature The feature to check (e.g., 'tracking_enabled', 'enable_badge')
	 * @return bool Whether the feature is enabled
	 */
	public function is_enabled( $feature ) {
		$value = $this->get( $feature, 0 );
		// Handle both integer 1 and string '1' as true
		return ( $value === 1 || $value === '1' );
	}

	/**
	 * Sanitize all settings
	 *
	 * @param array $input The unsanitized settings.
	 * @return array The sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		// Log the raw input for debugging
		if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
			greenmetrics_log( 'Settings Manager: Sanitizing input', $input );
		}

		// Get current settings to preserve values not present in current form
		$current_settings = $this->get();
		$sanitized = $current_settings;

		// Ensure input is an array
		if ( ! is_array( $input ) ) {
			greenmetrics_log( 'Invalid settings input (not an array)', $input, 'error' );
			return $current_settings;
		}

		// Sanitize boolean settings
		$boolean_fields = array(
			'tracking_enabled',
			'enable_badge',
			'display_icon'
		);

		foreach ( $boolean_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$sanitized[ $field ] = absint( $input[ $field ] ) ? 1 : 0;
			}
		}

		// Sanitize select fields with defined options
		if ( isset( $input['badge_position'] ) ) {
			$valid_positions = array( 'bottom-right', 'bottom-left', 'top-right', 'top-left' );
			$sanitized['badge_position'] = in_array( $input['badge_position'], $valid_positions, true ) 
				? $input['badge_position'] 
				: 'bottom-right';
		}
		
		if ( isset( $input['badge_size'] ) ) {
			$valid_sizes = array( 'small', 'medium', 'large' );
			$sanitized['badge_size'] = in_array( $input['badge_size'], $valid_sizes, true ) 
				? $input['badge_size'] 
				: 'medium';
		}

		if ( isset( $input['badge_theme'] ) ) {
			$valid_themes = array( 'light', 'dark' );
			$sanitized['badge_theme'] = in_array( $input['badge_theme'], $valid_themes, true ) 
				? $input['badge_theme'] 
				: 'light';
		}

		// Sanitize text fields
		$text_fields = array(
			'badge_text',
			'popover_title',
			'popover_content_font',
			'popover_metrics_font'
		);

		foreach ( $text_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$sanitized[ $field ] = sanitize_text_field( $input[ $field ] );
			}
		}

		// Sanitize URL fields
		if ( isset( $input['badge_custom_icon'] ) ) {
			$sanitized['badge_custom_icon'] = esc_url_raw( $input['badge_custom_icon'] );
		}

		// Sanitize icon type
		if ( isset( $input['badge_icon_type'] ) ) {
			$valid_icons = array( 
				'leaf', 'tree', 'globe', 'recycle', 'chart-bar', 'chart-line', 
				'chart-pie', 'analytics', 'performance', 'energy', 'water', 
				'eco', 'nature', 'sustainability', 'custom' 
			);
			
			$sanitized['badge_icon_type'] = in_array( $input['badge_icon_type'], $valid_icons, true ) 
				? $input['badge_icon_type'] 
				: 'leaf';
		}

		// Sanitize color fields
		$color_fields = array(
			'badge_background_color',
			'badge_text_color',
			'badge_icon_color',
			'popover_bg_color',
			'popover_text_color',
			'popover_metrics_color',
			'popover_metrics_list_bg_color',
			'popover_metrics_list_hover_bg_color'
		);

		foreach ( $color_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				// Handle rgba colors
				if ( preg_match( '/^rgba\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3}),\s*([0-9.]+)\)$/', $input[ $field ] ) ) {
					$sanitized[ $field ] = $input[ $field ];
				} else {
					// Handle regular hex colors
					$sanitized[ $field ] = sanitize_hex_color( $input[ $field ] );
				}
			}
		}

		// Sanitize size fields
		$size_fields = array(
			'badge_icon_size',
			'popover_content_font_size',
			'popover_metrics_font_size',
			'popover_metrics_label_font_size'
		);

		foreach ( $size_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$value = sanitize_text_field( $input[ $field ] );
				
				// Check if the value already has 'px' suffix, otherwise add it
				if ( strpos( $value, 'px' ) === false && strpos( $value, 'em' ) === false && strpos( $value, 'rem' ) === false ) {
					$value = intval( $value ) . 'px';
				}
				
				// Ensure the font size is within reasonable bounds (8px to 36px)
				if ( preg_match( '/^(\d+)px$/', $value, $matches ) ) {
					$numeric_size = intval( $matches[1] );
					if ( $numeric_size < 8 ) {
						$value = '8px';
					} elseif ( $numeric_size > 36 ) {
						$value = '36px';
					} else {
						$value = $numeric_size . 'px';
					}
				}
				
				$sanitized[ $field ] = $value;
			}
		}

		// Sanitize numeric fields
		$numeric_fields = array(
			'carbon_intensity',
			'energy_per_byte'
		);

		foreach ( $numeric_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$sanitized[ $field ] = floatval( $input[ $field ] );
			}
		}

		// Sanitize metrics array
		if ( isset( $input['popover_metrics'] ) && is_array( $input['popover_metrics'] ) ) {
			$valid_metrics = array(
				'carbon_footprint',
				'energy_consumption',
				'data_transfer',
				'total_views',
				'requests',
				'performance_score'
			);
			
			$sanitized_metrics = array();
			foreach ( $input['popover_metrics'] as $metric ) {
				$metric = sanitize_text_field( $metric );
				if ( in_array( $metric, $valid_metrics, true ) ) {
					$sanitized_metrics[] = $metric;
				}
			}
			
			$sanitized['popover_metrics'] = ! empty( $sanitized_metrics ) ? $sanitized_metrics : $valid_metrics;
		}

		// Sanitize custom content (HTML allowed with specific tags)
		if ( isset( $input['popover_custom_content'] ) ) {
			$allowed_html = array(
				'a'      => array(
					'href'   => array(),
					'title'  => array(),
					'target' => array(),
					'rel'    => array(),
					'class'  => array(),
				),
				'p'      => array(
					'class' => array(),
				),
				'span'   => array(
					'class' => array(),
				),
				'strong' => array(),
				'em'     => array(),
				'br'     => array(),
				'small'  => array(),
			);
			
			$sanitized['popover_custom_content'] = wp_kses( $input['popover_custom_content'], $allowed_html );
		}

		// Log the sanitized output for debugging
		if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
			greenmetrics_log( 'Settings Manager: Sanitized output', $sanitized );
		}

		return $sanitized;
	}
}
