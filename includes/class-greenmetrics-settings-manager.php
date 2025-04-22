<?php
/**
 * Centralized settings manager for the GreenMetrics plugin.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */

namespace GreenMetrics;

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
}
