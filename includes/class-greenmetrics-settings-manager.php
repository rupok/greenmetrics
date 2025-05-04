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
		'carbon_intensity'                  => 0.475,         // Default carbon intensity factor (kg CO2/kWh)
		'energy_per_byte'                   => 0.000000000072, // Default energy per byte (kWh/byte)
		'tracking_enabled'                  => 1,             // Enable tracking by default
		'enable_badge'                      => 1,             // Enable badge display by default
		'display_icon'                      => 1,             // Display icon by default
		'badge_position'                    => 'bottom-right', // Default badge position
		'badge_theme'                       => 'light',       // Default badge theme
		'badge_size'                        => 'medium',      // Default badge size
		'badge_text'                        => 'Eco-Friendly Site', // Default badge text
		'badge_icon_type'                   => 'leaf',        // Default icon type
		'badge_custom_icon'                 => '',            // Custom icon path (empty by default)
		'badge_background_color'            => '#4CAF50',     // Default badge background color
		'badge_text_color'                  => '#ffffff',     // Default badge text color
		'badge_icon_color'                  => '#ffffff',     // Default badge icon color
		'enable_popup'                      => 0,             // Disable popup by default
		'popup_delay'                       => 3,             // Default popup delay (seconds)
		'popup_session_views'               => 1,             // Show popup after X page views per session
		'popup_title'                       => 'Environmental Impact', // Default popup title
		'popup_content'                     => '',            // Default popup content

		// Data management settings
		'data_management_enabled'           => 1,             // Enable data management by default
		'aggregation_age'                   => 30,            // Aggregate data older than 30 days
		'aggregation_type'                  => 'daily',       // Aggregate by day by default
		'retention_period'                  => 90,            // Keep individual records for 90 days
		'require_aggregation_before_pruning'=> 1,             // Only prune data that has been aggregated

		// Email reporting settings
		'email_reporting_enabled'           => 0,             // Disable email reporting by default
		'email_reporting_frequency'         => 'weekly',      // Send reports weekly by default
		'email_reporting_day'               => 1,             // Monday for weekly reports, 1st day for monthly
		'email_reporting_recipients'        => '',            // Default to admin email (empty means use admin email)
		'email_reporting_subject'           => 'GreenMetrics Weekly Report for [site_name]', // Default email subject
		'email_reporting_include_stats'     => 1,             // Include statistics in email by default
		'email_reporting_include_chart'     => 1,             // Include chart in email by default
		'email_reporting_header'            => '',            // Custom email header (empty means use default)
		'email_reporting_footer'            => '',            // Custom email footer (empty means use default)
		'email_reporting_css'               => '',            // Custom email CSS (empty means use default)
		'email_template_style'              => 'default',     // Default email template style
		'email_color_primary'               => '#4CAF50',     // Default primary color for email template
		'email_color_secondary'             => '#f9f9f9',     // Default secondary color for email template
		'email_color_accent'                => '#333333',     // Default accent color for email template
		'email_color_text'                  => '#333333',     // Default text color for email template
		'email_color_background'            => '#ffffff',     // Default background color for email template
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
		$sanitized        = $current_settings;

		// Ensure input is an array
		if ( ! is_array( $input ) ) {
			greenmetrics_log( 'Invalid settings input (not an array)', $input, 'error' );
			return $current_settings;
		}

		// Sanitize boolean settings
		$boolean_fields = array(
			'tracking_enabled',
			'enable_badge',
			'display_icon',
			'data_management_enabled',
			'require_aggregation_before_pruning',
			'email_reporting_enabled',
			'email_reporting_include_stats',
			'email_reporting_include_chart',
		);

		foreach ( $boolean_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$sanitized[ $field ] = absint( $input[ $field ] ) ? 1 : 0;
			}
		}

		// Sanitize select fields with defined options
		if ( isset( $input['badge_position'] ) ) {
			$valid_positions             = array( 'bottom-right', 'bottom-left', 'top-right', 'top-left' );
			$sanitized['badge_position'] = in_array( $input['badge_position'], $valid_positions, true )
				? $input['badge_position']
				: 'bottom-right';
		}

		if ( isset( $input['badge_size'] ) ) {
			$valid_sizes             = array( 'small', 'medium', 'large' );
			$sanitized['badge_size'] = in_array( $input['badge_size'], $valid_sizes, true )
				? $input['badge_size']
				: 'medium';
		}

		if ( isset( $input['badge_theme'] ) ) {
			$valid_themes             = array( 'light', 'dark' );
			$sanitized['badge_theme'] = in_array( $input['badge_theme'], $valid_themes, true )
				? $input['badge_theme']
				: 'light';
		}

		if ( isset( $input['aggregation_type'] ) ) {
			$valid_types = array( 'daily', 'weekly', 'monthly' );
			$sanitized['aggregation_type'] = in_array( $input['aggregation_type'], $valid_types, true )
				? $input['aggregation_type']
				: 'daily';
		}

		if ( isset( $input['email_reporting_frequency'] ) ) {
			$valid_frequencies = array( 'daily', 'weekly', 'monthly' );
			$sanitized['email_reporting_frequency'] = in_array( $input['email_reporting_frequency'], $valid_frequencies, true )
				? $input['email_reporting_frequency']
				: 'weekly';
		}

		if ( isset( $input['email_template_style'] ) ) {
			$valid_templates = array( 'default', 'minimal', 'modern', 'eco' );
			$sanitized['email_template_style'] = in_array( $input['email_template_style'], $valid_templates, true )
				? $input['email_template_style']
				: 'default';
		}

		// Sanitize text fields
		$text_fields = array(
			'badge_text',
			'popover_title',
			'popover_content_font',
			'popover_metrics_font',
			'email_reporting_subject',
		);

		// Sanitize HTML fields (allow some HTML tags)
		$html_fields = array(
			'email_reporting_header',
			'email_reporting_footer',
			'email_reporting_css',
		);

		foreach ( $text_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$sanitized[ $field ] = sanitize_text_field( $input[ $field ] );
			}
		}

		// Allow specific HTML tags for template fields
		$allowed_html = array(
			'a'      => array(
				'href'   => array(),
				'title'  => array(),
				'target' => array(),
				'style'  => array(),
				'class'  => array(),
			),
			'br'     => array(),
			'em'     => array(),
			'strong' => array(),
			'span'   => array(
				'style' => array(),
				'class' => array(),
			),
			'div'    => array(
				'style' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'p'      => array(
				'style' => array(),
				'class' => array(),
			),
			'h1'     => array(
				'style' => array(),
				'class' => array(),
			),
			'h2'     => array(
				'style' => array(),
				'class' => array(),
			),
			'h3'     => array(
				'style' => array(),
				'class' => array(),
			),
			'h4'     => array(
				'style' => array(),
				'class' => array(),
			),
			'img'    => array(
				'src'    => array(),
				'alt'    => array(),
				'width'  => array(),
				'height' => array(),
				'style'  => array(),
				'class'  => array(),
			),
			'table'  => array(
				'width'       => array(),
				'cellspacing' => array(),
				'cellpadding' => array(),
				'border'      => array(),
				'style'       => array(),
				'class'       => array(),
			),
			'tr'     => array(
				'style' => array(),
				'class' => array(),
			),
			'td'     => array(
				'style'   => array(),
				'class'   => array(),
				'colspan' => array(),
				'rowspan' => array(),
			),
			'th'     => array(
				'style'   => array(),
				'class'   => array(),
				'colspan' => array(),
				'rowspan' => array(),
			),
		);

		foreach ( $html_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				if ( $field === 'email_reporting_css' ) {
					// For CSS, just strip out potentially harmful content
					$sanitized[ $field ] = wp_strip_all_tags( $input[ $field ] );
				} else {
					// For HTML fields, allow specific tags
					$sanitized[ $field ] = wp_kses( $input[ $field ], $allowed_html );
				}
			}
		}

		// Sanitize URL fields
		if ( isset( $input['badge_custom_icon'] ) ) {
			$sanitized['badge_custom_icon'] = esc_url_raw( $input['badge_custom_icon'] );
		}

		// Sanitize icon type
		if ( isset( $input['badge_icon_type'] ) ) {
			$valid_icons = array(
				'leaf',
				'tree',
				'globe',
				'recycle',
				'chart-bar',
				'chart-line',
				'chart-pie',
				'analytics',
				'performance',
				'energy',
				'water',
				'eco',
				'nature',
				'sustainability',
				'custom',
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
			'popover_metrics_list_hover_bg_color',
			'email_color_primary',
			'email_color_secondary',
			'email_color_accent',
			'email_color_text',
			'email_color_background',
		);

		foreach ( $color_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$color_value = trim( $input[ $field ] );

				// Handle rgba colors with enhanced validation
				if ( preg_match( '/^rgba\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3}),\s*([0-9.]+)\)$/', $color_value, $matches ) ) {
					// Validate each RGB component is between 0-255
					$r = intval( $matches[1] );
					$g = intval( $matches[2] );
					$b = intval( $matches[3] );
					$a = floatval( $matches[4] );

					// Ensure RGB values are within valid range
					$r = max( 0, min( 255, $r ) );
					$g = max( 0, min( 255, $g ) );
					$b = max( 0, min( 255, $b ) );

					// Ensure alpha is between 0 and 1
					$a = max( 0, min( 1, $a ) );

					// Reconstruct the validated rgba string
					$sanitized[ $field ] = "rgba($r, $g, $b, $a)";
				} elseif ( preg_match( '/^rgb\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3})\)$/', $color_value, $matches ) ) {
					// Handle rgb colors
					$r = intval( $matches[1] );
					$g = intval( $matches[2] );
					$b = intval( $matches[3] );

					// Ensure RGB values are within valid range
					$r = max( 0, min( 255, $r ) );
					$g = max( 0, min( 255, $g ) );
					$b = max( 0, min( 255, $b ) );

					// Reconstruct the validated rgb string
					$sanitized[ $field ] = "rgb($r, $g, $b)";
				} elseif ( preg_match( '/^#([a-fA-F0-9]{3}){1,2}$/', $color_value ) ) {
					// Handle hex colors (3 or 6 digits)
					$sanitized[ $field ] = $color_value;
				} elseif ( in_array( $color_value, array( 'transparent', 'initial', 'inherit', 'currentColor' ), true ) ) {
					// Handle special CSS color keywords
					$sanitized[ $field ] = $color_value;
				} else {
					// For any other format, use WordPress sanitize_hex_color
					$sanitized_color = sanitize_hex_color( $color_value );

					// If sanitize_hex_color returns empty (invalid), use default color
					if ( empty( $sanitized_color ) ) {
						// Use field-specific defaults
						switch ( $field ) {
							case 'badge_background_color':
								$sanitized[ $field ] = '#4CAF50';
								break;
							case 'badge_text_color':
							case 'badge_icon_color':
								$sanitized[ $field ] = '#ffffff';
								break;
							case 'popover_bg_color':
								$sanitized[ $field ] = '#ffffff';
								break;
							case 'popover_text_color':
								$sanitized[ $field ] = '#333333';
								break;
							case 'popover_metrics_color':
								$sanitized[ $field ] = '#4CAF50';
								break;
							case 'popover_metrics_list_bg_color':
								$sanitized[ $field ] = '#f9f9f9';
								break;
							case 'popover_metrics_list_hover_bg_color':
								$sanitized[ $field ] = '#f0f0f0';
								break;
							case 'email_color_primary':
								$sanitized[ $field ] = '#4CAF50';
								break;
							case 'email_color_secondary':
								$sanitized[ $field ] = '#f9f9f9';
								break;
							case 'email_color_accent':
								$sanitized[ $field ] = '#333333';
								break;
							case 'email_color_text':
								$sanitized[ $field ] = '#333333';
								break;
							case 'email_color_background':
								$sanitized[ $field ] = '#ffffff';
								break;
							default:
								$sanitized[ $field ] = '#000000';
						}
					} else {
						$sanitized[ $field ] = $sanitized_color;
					}
				}

				// Log sanitization for debugging
				if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
					greenmetrics_log(
						'Color sanitization',
						array(
							'field' => $field,
							'input' => $input[ $field ],
							'output' => $sanitized[ $field ]
						)
					);
				}
			}
		}

		// Sanitize size fields
		$size_fields = array(
			'badge_icon_size',
			'popover_content_font_size',
			'popover_metrics_font_size',
			'popover_metrics_label_font_size',
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

		// Sanitize numeric fields with enhanced validation
		$numeric_fields = array(
			'carbon_intensity' => array(
				'min' => 0,
				'max' => 10,
				'default' => 0.475,
				'precision' => 3,
			),
			'energy_per_byte' => array(
				'min' => 0,
				'max' => 0.0000001,
				'default' => 0.000000000072,
				'precision' => 12,
			),
			'aggregation_age' => array(
				'min' => 1,
				'max' => 365,
				'default' => 30,
				'precision' => 0,
			),
			'retention_period' => array(
				'min' => 1,
				'max' => 3650, // 10 years
				'default' => 90,
				'precision' => 0,
			),
			'email_reporting_day' => array(
				'min' => 0,
				'max' => 31, // 0-6 for days of week, 1-31 for days of month
				'default' => 1,
				'precision' => 0,
			),
		);

		// Special handling for email recipients
		if ( isset( $input['email_reporting_recipients'] ) ) {
			$recipients = sanitize_text_field( $input['email_reporting_recipients'] );

			// If empty, it will use the admin email
			if ( empty( $recipients ) ) {
				$sanitized['email_reporting_recipients'] = '';
			} else {
				// Split by commas and validate each email
				$emails = explode( ',', $recipients );
				$valid_emails = array();

				foreach ( $emails as $email ) {
					$email = trim( $email );
					if ( is_email( $email ) ) {
						$valid_emails[] = $email;
					}
				}

				// Join valid emails back with commas
				$sanitized['email_reporting_recipients'] = implode( ', ', $valid_emails );
			}
		}

		foreach ( $numeric_fields as $field => $constraints ) {
			if ( isset( $input[ $field ] ) ) {
				// Convert to float and validate
				$value = floatval( $input[ $field ] );

				// Apply constraints
				if ( $value < $constraints['min'] || $value > $constraints['max'] || !is_numeric( $input[ $field ] ) ) {
					// If value is out of range or not numeric, use default
					$sanitized[ $field ] = $constraints['default'];

					// Log invalid input
					if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
						greenmetrics_log(
							'Invalid numeric input',
							array(
								'field' => $field,
								'input' => $input[ $field ],
								'sanitized' => $sanitized[ $field ],
								'reason' => 'Value out of range or not numeric',
							),
							'warning'
						);
					}
				} else {
					// Round to specified precision
					$sanitized[ $field ] = round( $value, $constraints['precision'] );

					// Log sanitization for debugging
					if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
						greenmetrics_log(
							'Numeric sanitization',
							array(
								'field' => $field,
								'input' => $input[ $field ],
								'output' => $sanitized[ $field ]
							)
						);
					}
				}
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
				'performance_score',
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
			// Define a more restrictive set of allowed HTML tags and attributes
			$allowed_html = array(
				'a'      => array(
					'href'   => array(),
					'title'  => array(),
					'target' => array( '_blank', '_self' ), // Restrict target values
					'rel'    => array( 'nofollow', 'noreferrer', 'noopener' ), // Restrict rel values
					'class'  => array(),
				),
				'p'      => array(
					'class' => array(),
					'style' => array(), // Allow basic styling
				),
				'span'   => array(
					'class' => array(),
					'style' => array(), // Allow basic styling
				),
				'strong' => array(),
				'em'     => array(),
				'br'     => array(),
				'small'  => array(),
				'ul'     => array(
					'class' => array(),
				),
				'ol'     => array(
					'class' => array(),
				),
				'li'     => array(
					'class' => array(),
				),
			);

			// First sanitize with wp_kses to remove any disallowed HTML
			$sanitized_content = wp_kses( $input['popover_custom_content'], $allowed_html );

			// Additional sanitization for style attributes to prevent XSS
			$sanitized_content = preg_replace_callback(
				'/<([a-z][a-z0-9]*)[^>]*?style=["\']([^"\']*)["\'][^>]*?>/i',
				function( $matches ) {
					$tag = $matches[1];
					$style = $matches[2];

					// Only allow specific CSS properties
					$allowed_css_properties = array(
						'color', 'background-color', 'font-size', 'font-weight',
						'text-align', 'margin', 'padding', 'text-decoration'
					);

					// Parse the style attribute
					$styles = explode( ';', $style );
					$sanitized_styles = array();

					foreach ( $styles as $style_rule ) {
						$style_rule = trim( $style_rule );
						if ( empty( $style_rule ) ) {
							continue;
						}

						// Split into property and value
						$parts = explode( ':', $style_rule, 2 );
						if ( count( $parts ) !== 2 ) {
							continue;
						}

						$property = trim( $parts[0] );
						$value = trim( $parts[1] );

						// Check if property is allowed
						if ( in_array( $property, $allowed_css_properties, true ) ) {
							// Additional validation for values to prevent CSS injection
							if ( $property === 'color' || $property === 'background-color' ) {
								// Only allow hex colors, rgb, rgba, and named colors
								if ( preg_match( '/^(#[a-f0-9]{3,6}|rgb\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*\)|rgba\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*,\s*[0-9.]+\s*\)|[a-z-]+)$/i', $value ) ) {
									$sanitized_styles[] = $property . ':' . $value;
								}
							} elseif ( $property === 'font-size' ) {
								// Only allow specific units and reasonable sizes
								if ( preg_match( '/^(\d+)(px|em|rem|%)$/', $value, $size_matches ) ) {
									$size = intval( $size_matches[1] );
									$unit = $size_matches[2];

									// Limit size based on unit
									if ( $unit === 'px' && $size >= 8 && $size <= 36 ) {
										$sanitized_styles[] = $property . ':' . $value;
									} elseif ( ($unit === 'em' || $unit === 'rem') && $size >= 0.5 && $size <= 3 ) {
										$sanitized_styles[] = $property . ':' . $value;
									} elseif ( $unit === '%' && $size >= 50 && $size <= 200 ) {
										$sanitized_styles[] = $property . ':' . $value;
									}
								}
							} elseif ( in_array( $property, array( 'margin', 'padding' ), true ) ) {
								// Only allow specific units and reasonable sizes for spacing
								if ( preg_match( '/^(\d+)(px|em|rem|%)(\s+(\d+)(px|em|rem|%))*$/', $value ) ) {
									$sanitized_styles[] = $property . ':' . $value;
								}
							} elseif ( $property === 'text-align' ) {
								// Only allow specific values
								if ( in_array( $value, array( 'left', 'right', 'center', 'justify' ), true ) ) {
									$sanitized_styles[] = $property . ':' . $value;
								}
							} elseif ( $property === 'font-weight' ) {
								// Only allow specific values
								if ( in_array( $value, array( 'normal', 'bold', 'bolder', 'lighter', '100', '200', '300', '400', '500', '600', '700', '800', '900' ), true ) ) {
									$sanitized_styles[] = $property . ':' . $value;
								}
							} elseif ( $property === 'text-decoration' ) {
								// Only allow specific values
								if ( in_array( $value, array( 'none', 'underline', 'overline', 'line-through' ), true ) ) {
									$sanitized_styles[] = $property . ':' . $value;
								}
							}
						}
					}

					// Rebuild the tag with sanitized style attribute
					if ( ! empty( $sanitized_styles ) ) {
						return '<' . $tag . ' style="' . esc_attr( implode( '; ', $sanitized_styles ) ) . '">';
					} else {
						return '<' . $tag . '>';
					}
				},
				$sanitized_content
			);

			// Limit the length of the custom content
			$max_length = 1000; // Set a reasonable maximum length
			if ( strlen( $sanitized_content ) > $max_length ) {
				$sanitized_content = substr( $sanitized_content, 0, $max_length );

				// Log truncation
				if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
					greenmetrics_log(
						'Custom content truncated',
						array(
							'original_length' => strlen( $input['popover_custom_content'] ),
							'truncated_length' => $max_length,
						),
						'warning'
					);
				}
			}

			$sanitized['popover_custom_content'] = $sanitized_content;

			// Log sanitization for debugging
			if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
				greenmetrics_log(
					'Custom content sanitization',
					array(
						'input_length' => strlen( $input['popover_custom_content'] ),
						'output_length' => strlen( $sanitized['popover_custom_content'] ),
					)
				);
			}
		}

		// Log the sanitized output for debugging
		if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
			greenmetrics_log( 'Settings Manager: Sanitized output', $sanitized );
		}

		return $sanitized;
	}
}
