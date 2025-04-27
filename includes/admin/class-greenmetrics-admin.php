<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/admin
 */

namespace GreenMetrics\Admin;

/**
 * The admin-specific functionality of the plugin.
 */
class GreenMetrics_Admin {
	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_notices', array( $this, 'show_settings_update_notice' ) );
		add_action( 'admin_init', array( $this, 'handle_refresh_stats' ) );
		
		// AJAX handlers
		add_action( 'wp_ajax_greenmetrics_refresh_stats', array( $this, 'handle_refresh_stats' ) );
		add_action( 'wp_ajax_greenmetrics_get_icon', array( $this, 'handle_get_icon' ) );
		add_action( 'wp_ajax_nopriv_greenmetrics_get_icon', array( $this, 'handle_get_icon' ) );
	}

	/**
	 * Display notices for settings updates
	 */
	public function show_settings_update_notice() {
		// Display notice for settings update
		if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) {
			// Log the current settings after update
			if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
				$settings = get_option( 'greenmetrics_settings', array() );
				greenmetrics_log( 'Settings updated via WP Settings API', $settings );
			}

			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully!', 'greenmetrics' ) . '</p></div>';
		}
		
		// Display notice for stats refresh
		if ( isset( $_GET['stats-refreshed'] ) && $_GET['stats-refreshed'] === 'true' ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Statistics refreshed successfully!', 'greenmetrics' ) . '</p></div>';
		}
	}

	/**
	 * Add admin menu items.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'GreenMetrics', 'greenmetrics' ),
			__( 'GreenMetrics', 'greenmetrics' ),
			'manage_options',
			'greenmetrics',
			array( $this, 'render_admin_page' ),
			'dashicons-chart-area',
			30
		);
		
		// Add submenu that points to the main page (Dashboard)
		add_submenu_page(
			'greenmetrics',
			__( 'GreenMetrics Dashboard', 'greenmetrics' ),
			__( 'Dashboard', 'greenmetrics' ),
			'manage_options',
			'greenmetrics',
			array( $this, 'render_admin_page' )
		);
		
		// Add submenu for Display Settings
		add_submenu_page(
			'greenmetrics',
			__( 'Display Settings', 'greenmetrics' ),
			__( 'Display Settings', 'greenmetrics' ),
			'manage_options',
			'greenmetrics_display',
			array( $this, 'render_display_settings_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting(
			'greenmetrics_settings',
			'greenmetrics_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(
					'tracking_enabled'       => 1,
					'enable_badge'           => 1,
					'display_icon'           => 1,
					'badge_position'         => 'bottom-right',
					'badge_size'             => 'medium',
					'badge_text'             => 'Eco-Friendly Site',
					'badge_icon_type'        => 'leaf',
					'badge_custom_icon'      => '',
					'badge_background_color' => '#4CAF50',
					'badge_text_color'       => '#ffffff',
					'badge_icon_color'       => '#ffffff',
					'badge_icon_size'        => '16px',
					'popover_title'          => 'Environmental Impact',
					'popover_metrics'        => array('carbon_footprint', 'energy_consumption', 'data_transfer', 'total_views', 'requests', 'performance_score'),
					'popover_custom_content' => '',
					'popover_bg_color'       => '#ffffff',
					'popover_text_color'     => '#333333',
					'popover_metrics_color'  => '#4CAF50',
					'popover_metrics_bg_color' => 'rgba(0, 0, 0, 0.05)',
					'popover_content_font'   => 'inherit',
					'popover_content_font_size' => '16px',
					'popover_metrics_font'   => 'inherit',
					'popover_metrics_font_size' => '14px',
					'popover_metrics_label_font_size' => '12px',
					'popover_metrics_list_bg_color' => 'transparent',
					'popover_metrics_list_hover_bg_color' => '#f3f4f6',
				),
			)
		);

		// Tracking Settings Section
		add_settings_section(
			'greenmetrics_tracking',
			__( 'Tracking Settings', 'greenmetrics' ),
			array( $this, 'render_tracking_section' ),
			'greenmetrics'
		);

		add_settings_field(
			'tracking_enabled',
			__( 'Enable Tracking', 'greenmetrics' ),
			array( $this, 'render_tracking_field' ),
			'greenmetrics',
			'greenmetrics_tracking',
			array( 'label_for' => 'tracking_enabled' )
		);
		
		// Add Statistics Cache to Settings section (this doesn't have actual settings fields)

		// Display Settings Section - register under a different page
		add_settings_section(
			'greenmetrics_display',
			__( 'Display Settings', 'greenmetrics' ),
			array( $this, 'render_display_section' ),
			'greenmetrics_display' // Changed to a different page
		);

		add_settings_field(
			'enable_badge',
			__( 'Display Badge', 'greenmetrics' ),
			array( $this, 'render_badge_field' ),
			'greenmetrics_display',
			'greenmetrics_display',
			array( 'label_for' => 'enable_badge' )
		);

		add_settings_field(
			'display_icon',
			__( 'Display Icon', 'greenmetrics' ),
			array( $this, 'render_display_icon_field' ),
			'greenmetrics_display',
			'greenmetrics_display',
			array( 'label_for' => 'display_icon' )
		);

		add_settings_field(
			'badge_icon_type',
			__( 'Choose Icon', 'greenmetrics' ),
			array( $this, 'render_badge_icon_type_field' ),
			'greenmetrics_display',
			'greenmetrics_display',
			array( 'label_for' => 'badge_icon_type' )
		);

		add_settings_field(
			'badge_custom_icon',
			__( 'Custom Icon', 'greenmetrics' ),
			array( $this, 'render_badge_custom_icon_field' ),
			'greenmetrics_display',
			'greenmetrics_display',
			array( 'label_for' => 'badge_custom_icon' )
		);

		add_settings_field(
			'badge_icon_color',
			__( 'Icon Color', 'greenmetrics' ),
			array( $this, 'render_badge_icon_color_field' ),
			'greenmetrics_display',
			'greenmetrics_display',
			array( 'label_for' => 'badge_icon_color' )
		);

		add_settings_field(
			'badge_icon_size',
			__( 'Icon Size', 'greenmetrics' ),
			array( $this, 'render_badge_icon_size_field' ),
			'greenmetrics_display',
			'greenmetrics_display',
			array( 'label_for' => 'badge_icon_size' )
		);

		add_settings_field(
			'badge_position',
			__( 'Badge Position', 'greenmetrics' ),
			array( $this, 'render_badge_position_field' ),
			'greenmetrics_display',
			'greenmetrics_display',
			array( 'label_for' => 'badge_position' )
		);
		
		add_settings_field(
			'badge_size',
			__( 'Badge Size', 'greenmetrics' ),
			array( $this, 'render_badge_size_field' ),
			'greenmetrics_display',
			'greenmetrics_display',
			array( 'label_for' => 'badge_size' )
		);

		add_settings_field(
			'badge_text',
			__( 'Badge Text', 'greenmetrics' ),
			array( $this, 'render_badge_text_field' ),
			'greenmetrics_display',
			'greenmetrics_display',
			array( 'label_for' => 'badge_text' )
		);

		add_settings_field(
			'badge_background_color',
			__( 'Background Color', 'greenmetrics' ),
			array( $this, 'render_badge_background_color_field' ),
			'greenmetrics_display',
			'greenmetrics_display',
			array( 'label_for' => 'badge_background_color' )
		);

		add_settings_field(
			'badge_text_color',
			__( 'Text Color', 'greenmetrics' ),
			array( $this, 'render_badge_text_color_field' ),
			'greenmetrics_display',
			'greenmetrics_display',
			array( 'label_for' => 'badge_text_color' )
		);

		// Adding popover content customization fields
		add_settings_section(
			'greenmetrics_popover_content',
			__( 'Popover Content Settings', 'greenmetrics' ),
			array( $this, 'render_popover_content_section' ),
			'greenmetrics_display'
		);

		add_settings_field(
			'popover_title',
			__( 'Content Title', 'greenmetrics' ),
			array( $this, 'render_popover_title_field' ),
			'greenmetrics_display',
			'greenmetrics_popover_content',
			array( 'label_for' => 'popover_title' )
		);

		add_settings_field(
			'popover_metrics',
			__( 'Metrics to Display', 'greenmetrics' ),
			array( $this, 'render_popover_metrics_field' ),
			'greenmetrics_display',
			'greenmetrics_popover_content',
			array( 'label_for' => 'popover_metrics' )
		);

		add_settings_field(
			'popover_custom_content',
			__( 'Custom Content', 'greenmetrics' ),
			array( $this, 'render_popover_custom_content_field' ),
			'greenmetrics_display',
			'greenmetrics_popover_content',
			array( 'label_for' => 'popover_custom_content' )
		);

		add_settings_field(
			'popover_bg_color',
			__( 'Content Background Color', 'greenmetrics' ),
			array( $this, 'render_popover_bg_color_field' ),
			'greenmetrics_display',
			'greenmetrics_popover_content',
			array( 'label_for' => 'popover_bg_color' )
		);

		add_settings_field(
			'popover_text_color',
			__( 'Content Text Color', 'greenmetrics' ),
			array( $this, 'render_popover_text_color_field' ),
			'greenmetrics_display',
			'greenmetrics_popover_content',
			array( 'label_for' => 'popover_text_color' )
		);

		add_settings_field(
			'popover_metrics_color',
			__( 'Metrics Text Color', 'greenmetrics' ),
			array( $this, 'render_popover_metrics_color_field' ),
			'greenmetrics_display',
			'greenmetrics_popover_content',
			array( 'label_for' => 'popover_metrics_color' )
		);

		add_settings_field(
			'popover_metrics_list_bg_color',
			__( 'Metrics List Background Color', 'greenmetrics' ),
			array( $this, 'render_popover_metrics_list_bg_color_field' ),
			'greenmetrics_display',
			'greenmetrics_popover_content',
			array( 'label_for' => 'popover_metrics_list_bg_color' )
		);

		add_settings_field(
			'popover_metrics_list_hover_bg_color',
			__( 'Metrics List Hover Background Color', 'greenmetrics' ),
			array( $this, 'render_popover_metrics_list_hover_bg_color_field' ),
			'greenmetrics_display',
			'greenmetrics_popover_content',
			array( 'label_for' => 'popover_metrics_list_hover_bg_color' )
		);

		add_settings_field(
			'popover_metrics_bg_color',
			__( 'Metrics Background Color', 'greenmetrics' ),
			array( $this, 'render_popover_metrics_bg_color_field' ),
			'greenmetrics_display',
			'greenmetrics_popover_content',
			array( 'label_for' => 'popover_metrics_bg_color' )
		);

		add_settings_field(
			'popover_content_font',
			__( 'Content Font Family', 'greenmetrics' ),
			array( $this, 'render_popover_content_font_field' ),
			'greenmetrics_display',
			'greenmetrics_popover_content',
			array( 'label_for' => 'popover_content_font' )
		);

		add_settings_field(
			'popover_content_font_size',
			__( 'Content Font Size', 'greenmetrics' ),
			array( $this, 'render_popover_content_font_size_field' ),
			'greenmetrics_display',
			'greenmetrics_popover_content',
			array( 'label_for' => 'popover_content_font_size' )
		);

		add_settings_field(
			'popover_metrics_font',
			__( 'Metrics Font Family', 'greenmetrics' ),
			array( $this, 'render_popover_metrics_font_field' ),
			'greenmetrics_display',
			'greenmetrics_popover_content',
			array( 'label_for' => 'popover_metrics_font' )
		);

		add_settings_field(
			'popover_metrics_label_font_size',
			__( 'Metrics Label Font Size', 'greenmetrics' ),
			array( $this, 'render_popover_metrics_label_font_size_field' ),
			'greenmetrics_display',
			'greenmetrics_popover_content',
			array( 'label_for' => 'popover_metrics_label_font_size' )
		);

		add_settings_field(
			'popover_metrics_font_size',
			__( 'Metrics Value Font Size', 'greenmetrics' ),
			array( $this, 'render_popover_metrics_font_size_field' ),
			'greenmetrics_display',
			'greenmetrics_popover_content',
			array( 'label_for' => 'popover_metrics_font_size' )
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input The input settings.
	 * @return array The sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		// Log the raw input for debugging
		if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
			greenmetrics_log( 'Settings sanitize - Raw input', $input );
		}

		// Get current settings to preserve values not present in current form
		$current_settings = get_option( 'greenmetrics_settings', array() );

		if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
			greenmetrics_log( 'Settings sanitize - Current settings', $current_settings );
		}

		// Determine which page is being saved by checking the HTTP referer
		$is_display_page = false;
		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			$referer = sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
			$is_display_page = ( strpos( $referer, 'page=greenmetrics_display' ) !== false );
		}

		// Start with current settings and update only those which are present in the input
		$sanitized = $current_settings;

		// Set defaults if settings are empty
		if ( empty( $sanitized ) ) {
			$sanitized = array(
				'tracking_enabled'       => 0,
				'enable_badge'           => 0,
				'display_icon'           => 1,
				'badge_position'         => 'bottom-right',
				'badge_size'             => 'medium',
				'badge_text'             => 'Eco-Friendly Site',
				'badge_icon_type'        => 'leaf',
				'badge_custom_icon'      => '',
				'badge_background_color' => '#4CAF50',
				'badge_text_color'       => '#ffffff',
				'badge_icon_color'       => '#ffffff',
				'badge_icon_size'        => '16px',
			);
		}

		// Sanitize checkboxes (checkboxes are not included in $_POST if unchecked)
		// Update main tracking setting only if we're on the main settings page
		if ( !$is_display_page ) {
			$sanitized['tracking_enabled'] = isset( $input['tracking_enabled'] ) ? 1 : 0;
		}

		// Update display settings only if we're on the display settings page
		if ( $is_display_page ) {
			$sanitized['enable_badge'] = isset( $input['enable_badge'] ) ? 1 : 0;
			$sanitized['display_icon'] = isset( $input['display_icon'] ) ? 1 : 0;
		}

		// Sanitize text and dropdown fields if they are present in the input
		if ( isset( $input['badge_position'] ) ) {
			$valid_positions = array( 'bottom-right', 'bottom-left', 'top-right', 'top-left' );
			$sanitized['badge_position'] = in_array( $input['badge_position'], $valid_positions ) 
				? $input['badge_position'] 
				: 'bottom-right';
		}
		
		if ( isset( $input['badge_size'] ) ) {
			$valid_sizes = array( 'small', 'medium', 'large' );
			$sanitized['badge_size'] = in_array( $input['badge_size'], $valid_sizes ) 
				? $input['badge_size'] 
				: 'medium';
		}

		if ( isset( $input['badge_text'] ) ) {
			$sanitized['badge_text'] = sanitize_text_field( $input['badge_text'] );
		}

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
				'custom' 
			);
			
			// Log icon type for debugging
			if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
				greenmetrics_log( 'Icon type before sanitizing', $input['badge_icon_type'] );
				greenmetrics_log( 'Valid icons', $valid_icons );
			}
			
			$sanitized['badge_icon_type'] = in_array( $input['badge_icon_type'], $valid_icons ) 
				? $input['badge_icon_type'] 
				: 'leaf';
				
			// Log final icon type for debugging
			if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
				greenmetrics_log( 'Icon type after sanitizing', $sanitized['badge_icon_type'] );
			}
		}

		if ( isset( $input['badge_custom_icon'] ) ) {
			$sanitized['badge_custom_icon'] = esc_url_raw( $input['badge_custom_icon'] );
		}

		// Sanitize color fields
		if ( isset( $input['badge_background_color'] ) ) {
			$sanitized['badge_background_color'] = sanitize_hex_color( $input['badge_background_color'] );
		}

		if ( isset( $input['badge_text_color'] ) ) {
			$sanitized['badge_text_color'] = sanitize_hex_color( $input['badge_text_color'] );
		}

		if ( isset( $input['badge_icon_color'] ) ) {
			$sanitized['badge_icon_color'] = sanitize_hex_color( $input['badge_icon_color'] );
		}

		// Sanitize icon size
		if ( isset( $input['badge_icon_size'] ) ) {
			$size = intval( $input['badge_icon_size'] );
			$size = max( 8, min( 48, $size ) ); // Limit between 8px and 48px
			$sanitized['badge_icon_size'] = $size . 'px';
		}

		// Sanitize popover content settings
		if ( isset( $input['popover_title'] ) ) {
			$sanitized['popover_title'] = sanitize_text_field( $input['popover_title'] );
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
			
			$sanitized['popover_metrics'] = $sanitized_metrics;
		} else {
			// Default to all metrics if none are selected
			$sanitized['popover_metrics'] = array(
				'carbon_footprint',
				'energy_consumption',
				'data_transfer',
				'total_views',
				'requests',
				'performance_score'
			);
		}

		if ( isset( $input['popover_custom_content'] ) ) {
			$sanitized['popover_custom_content'] = wp_kses_post( $input['popover_custom_content'] );
		}

		if ( isset( $input['popover_bg_color'] ) ) {
			$sanitized['popover_bg_color'] = sanitize_hex_color( $input['popover_bg_color'] );
		}

		if ( isset( $input['popover_text_color'] ) ) {
			$sanitized['popover_text_color'] = sanitize_hex_color( $input['popover_text_color'] );
		}

		if ( isset( $input['popover_metrics_color'] ) ) {
			$sanitized['popover_metrics_color'] = sanitize_hex_color( $input['popover_metrics_color'] );
		}

		if ( isset( $input['popover_metrics_list_bg_color'] ) ) {
			$sanitized['popover_metrics_list_bg_color'] = sanitize_hex_color( $input['popover_metrics_list_bg_color'] );
		}

		if ( isset( $input['popover_metrics_list_hover_bg_color'] ) ) {
			$sanitized['popover_metrics_list_hover_bg_color'] = sanitize_hex_color( $input['popover_metrics_list_hover_bg_color'] );
		}

		if ( isset( $input['popover_metrics_bg_color'] ) ) {
			$sanitized['popover_metrics_bg_color'] = sanitize_hex_color( $input['popover_metrics_bg_color'] );
		}

		if ( isset( $input['popover_content_font'] ) ) {
			$sanitized['popover_content_font'] = sanitize_text_field( $input['popover_content_font'] );
		}

		if ( isset( $input['popover_content_font_size'] ) ) {
			$font_size = sanitize_text_field( $input['popover_content_font_size'] );
			// Check if the value already has 'px' suffix, otherwise add it
			if ( strpos( $font_size, 'px' ) === false ) {
				$font_size = intval( $font_size ) . 'px';
			}
			// Ensure the font size is within reasonable bounds (8px to 36px)
			$numeric_size = intval( $font_size );
			if ( $numeric_size < 8 ) {
				$font_size = '8px';
			} elseif ( $numeric_size > 36 ) {
				$font_size = '36px';
			} else {
				$font_size = $numeric_size . 'px';
			}
			$sanitized['popover_content_font_size'] = $font_size;
		}

		if ( isset( $input['popover_metrics_font'] ) ) {
			$sanitized['popover_metrics_font'] = sanitize_text_field( $input['popover_metrics_font'] );
		}

		if ( isset( $input['popover_metrics_label_font_size'] ) ) {
			$font_size = sanitize_text_field( $input['popover_metrics_label_font_size'] );
			// Check if the value already has 'px' suffix, otherwise add it
			if ( strpos( $font_size, 'px' ) === false ) {
				$font_size = intval( $font_size ) . 'px';
			}
			// Ensure the font size is within reasonable bounds (8px to 36px)
			$numeric_size = intval( $font_size );
			if ( $numeric_size < 8 ) {
				$font_size = '8px';
			} elseif ( $numeric_size > 36 ) {
				$font_size = '36px';
			} else {
				$font_size = $numeric_size . 'px';
			}
			$sanitized['popover_metrics_label_font_size'] = $font_size;
		}

		if ( isset( $input['popover_metrics_font_size'] ) ) {
			$font_size = sanitize_text_field( $input['popover_metrics_font_size'] );
			// Check if the value already has 'px' suffix, otherwise add it
			if ( strpos( $font_size, 'px' ) === false ) {
				$font_size = intval( $font_size ) . 'px';
			}
			// Ensure the font size is within reasonable bounds (8px to 36px)
			$numeric_size = intval( $font_size );
			if ( $numeric_size < 8 ) {
				$font_size = '8px';
			} elseif ( $numeric_size > 36 ) {
				$font_size = '36px';
			} else {
				$font_size = $numeric_size . 'px';
			}
			$sanitized['popover_metrics_font_size'] = $font_size;
		}

		// Log the result
		if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
			greenmetrics_log( 'Settings sanitize - Sanitized result', $sanitized );
		}

		return $sanitized;
	}

	/**
	 * Render tracking settings section.
	 */
	public function render_tracking_section() {
		echo '<p>' . esc_html__( 'Configure tracking settings to collect data about your website\'s environmental impact.', 'greenmetrics' ) . '</p>';
	}

	/**
	 * Render display settings section.
	 */
	public function render_display_section() {
		echo '<p>' . esc_html__( 'Configure how the eco-friendly badge appears on your website.', 'greenmetrics' ) . '</p>';
	}

	/**
	 * Render tracking field.
	 */
	public function render_tracking_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['tracking_enabled'] ) ? $options['tracking_enabled'] : 1;
		?>
		<label class="toggle-switch">
			<input type="checkbox" id="tracking_enabled" name="greenmetrics_settings[tracking_enabled]" value="1" <?php checked( $value, 1 ); ?>>
			<span class="slider"></span>
		</label>
		<label for="tracking_enabled"><?php esc_html_e( 'Enable page tracking', 'greenmetrics' ); ?></label>
		<p class="description"><?php esc_html_e( 'Collect data about page views, load times, and resource usage.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render badge field.
	 */
	public function render_badge_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['enable_badge'] ) ? $options['enable_badge'] : 1;
		?>
		<label class="toggle-switch">
			<input type="checkbox" id="enable_badge" name="greenmetrics_settings[enable_badge]" value="1" <?php checked( $value, 1 ); ?>>
			<span class="slider"></span>
		</label>
		<p class="description"><?php esc_html_e( 'Show an eco-friendly badge on your website to highlight your commitment to sustainability.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render display icon field.
	 */
	public function render_display_icon_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['display_icon'] ) ? $options['display_icon'] : 1;
		?>
		<label class="toggle-switch">
			<input type="checkbox" id="display_icon" name="greenmetrics_settings[display_icon]" value="1" <?php checked( $value, 1 ); ?>>
			<span class="slider"></span>
		</label>
		<p class="description"><?php esc_html_e( 'Show an icon next to the badge', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render badge icon type field.
	 */
	public function render_badge_icon_type_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['badge_icon_type'] ) ? $options['badge_icon_type'] : 'leaf';
		
		// Log the current value for debugging
		if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
			greenmetrics_log( 'Current badge icon type setting', $value );
		}
		?>
		<div class="greenmetrics-icon-selection">
			<select id="badge_icon_type" name="greenmetrics_settings[badge_icon_type]" style="margin-bottom: 15px;">
				<option value="leaf" <?php selected( $value, 'leaf' ); ?>><?php esc_html_e( 'Leaf', 'greenmetrics' ); ?></option>
				<option value="tree" <?php selected( $value, 'tree' ); ?>><?php esc_html_e( 'Tree', 'greenmetrics' ); ?></option>
				<option value="globe" <?php selected( $value, 'globe' ); ?>><?php esc_html_e( 'Globe', 'greenmetrics' ); ?></option>
				<option value="recycle" <?php selected( $value, 'recycle' ); ?>><?php esc_html_e( 'Recycle', 'greenmetrics' ); ?></option>
				<option value="chart-bar" <?php selected( $value, 'chart-bar' ); ?>><?php esc_html_e( 'Chart Bar', 'greenmetrics' ); ?></option>
				<option value="chart-line" <?php selected( $value, 'chart-line' ); ?>><?php esc_html_e( 'Chart Line', 'greenmetrics' ); ?></option>
				<option value="chart-pie" <?php selected( $value, 'chart-pie' ); ?>><?php esc_html_e( 'Chart Pie', 'greenmetrics' ); ?></option>
				<option value="analytics" <?php selected( $value, 'analytics' ); ?>><?php esc_html_e( 'Analytics', 'greenmetrics' ); ?></option>
				<option value="performance" <?php selected( $value, 'performance' ); ?>><?php esc_html_e( 'Performance', 'greenmetrics' ); ?></option>
				<option value="energy" <?php selected( $value, 'energy' ); ?>><?php esc_html_e( 'Energy', 'greenmetrics' ); ?></option>
				<option value="water" <?php selected( $value, 'water' ); ?>><?php esc_html_e( 'Water', 'greenmetrics' ); ?></option>
				<option value="eco" <?php selected( $value, 'eco' ); ?>><?php esc_html_e( 'Eco', 'greenmetrics' ); ?></option>
				<option value="nature" <?php selected( $value, 'nature' ); ?>><?php esc_html_e( 'Nature', 'greenmetrics' ); ?></option>
				<option value="sustainability" <?php selected( $value, 'sustainability' ); ?>><?php esc_html_e( 'Sustainability', 'greenmetrics' ); ?></option>
				<option value="custom" <?php selected( $value, 'custom' ); ?>><?php esc_html_e( 'Custom', 'greenmetrics' ); ?></option>
			</select>
			
			<div class="icon-options">
				<div class="icon-option <?php echo $value === 'leaf' ? 'selected' : ''; ?>" data-value="leaf">
					<div class="icon-preview">
						<?php echo \GreenMetrics\GreenMetrics_Icons::get_icon('leaf'); ?>
					</div>
					<span><?php esc_html_e( 'Leaf', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'tree' ? 'selected' : ''; ?>" data-value="tree">
					<div class="icon-preview">
						<?php echo \GreenMetrics\GreenMetrics_Icons::get_icon('tree'); ?>
					</div>
					<span><?php esc_html_e( 'Tree', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'globe' ? 'selected' : ''; ?>" data-value="globe">
					<div class="icon-preview">
						<?php echo \GreenMetrics\GreenMetrics_Icons::get_icon('globe'); ?>
					</div>
					<span><?php esc_html_e( 'Globe', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'recycle' ? 'selected' : ''; ?>" data-value="recycle">
					<div class="icon-preview">
						<?php echo \GreenMetrics\GreenMetrics_Icons::get_icon('recycle'); ?>
					</div>
					<span><?php esc_html_e( 'Recycle', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'chart-bar' ? 'selected' : ''; ?>" data-value="chart-bar">
					<div class="icon-preview">
						<?php echo \GreenMetrics\GreenMetrics_Icons::get_icon('chart-bar'); ?>
					</div>
					<span><?php esc_html_e( 'Chart Bar', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'chart-line' ? 'selected' : ''; ?>" data-value="chart-line">
					<div class="icon-preview">
						<?php echo \GreenMetrics\GreenMetrics_Icons::get_icon('chart-line'); ?>
					</div>
					<span><?php esc_html_e( 'Chart Line', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'chart-pie' ? 'selected' : ''; ?>" data-value="chart-pie">
					<div class="icon-preview">
						<?php echo \GreenMetrics\GreenMetrics_Icons::get_icon('chart-pie'); ?>
					</div>
					<span><?php esc_html_e( 'Chart Pie', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'analytics' ? 'selected' : ''; ?>" data-value="analytics">
					<div class="icon-preview">
						<?php echo \GreenMetrics\GreenMetrics_Icons::get_icon('analytics'); ?>
					</div>
					<span><?php esc_html_e( 'Analytics', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'performance' ? 'selected' : ''; ?>" data-value="performance">
					<div class="icon-preview">
						<?php echo \GreenMetrics\GreenMetrics_Icons::get_icon('performance'); ?>
					</div>
					<span><?php esc_html_e( 'Performance', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'energy' ? 'selected' : ''; ?>" data-value="energy">
					<div class="icon-preview">
						<?php echo \GreenMetrics\GreenMetrics_Icons::get_icon('energy'); ?>
					</div>
					<span><?php esc_html_e( 'Energy', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'water' ? 'selected' : ''; ?>" data-value="water">
					<div class="icon-preview">
						<?php echo \GreenMetrics\GreenMetrics_Icons::get_icon('water'); ?>
					</div>
					<span><?php esc_html_e( 'Water', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'eco' ? 'selected' : ''; ?>" data-value="eco">
					<div class="icon-preview">
						<?php echo \GreenMetrics\GreenMetrics_Icons::get_icon('eco'); ?>
					</div>
					<span><?php esc_html_e( 'Eco', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'nature' ? 'selected' : ''; ?>" data-value="nature">
					<div class="icon-preview">
						<?php echo \GreenMetrics\GreenMetrics_Icons::get_icon('nature'); ?>
					</div>
					<span><?php esc_html_e( 'Nature', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'sustainability' ? 'selected' : ''; ?>" data-value="sustainability">
					<div class="icon-preview">
						<?php echo \GreenMetrics\GreenMetrics_Icons::get_icon('sustainability'); ?>
					</div>
					<span><?php esc_html_e( 'Sustainability', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'custom' ? 'selected' : ''; ?>" data-value="custom">
					<div class="icon-preview">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" /></svg>
					</div>
					<span><?php esc_html_e( 'Custom', 'greenmetrics' ); ?></span>
				</div>
			</div>
		</div>
		<p class="description"><?php esc_html_e( 'Select an icon to display on the badge.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render badge custom icon field.
	 */
	public function render_badge_custom_icon_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['badge_custom_icon'] ) ? $options['badge_custom_icon'] : '';
		$display = isset( $options['badge_icon_type'] ) && $options['badge_icon_type'] === 'custom' ? 'block' : 'none';
		?>
		<div id="custom-icon-field-wrapper" style="display: <?php echo esc_attr( $display ); ?>;">
			<input type="text" id="badge_custom_icon" name="greenmetrics_settings[badge_custom_icon]" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
			<button type="button" class="button upload-custom-icon"><?php esc_html_e( 'Upload Icon', 'greenmetrics' ); ?></button>
			<p class="description"><?php esc_html_e( 'Upload a custom SVG icon for the badge. For best results, use a square SVG file.', 'greenmetrics' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render badge icon color field.
	 */
	public function render_badge_icon_color_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['badge_icon_color'] ) ? $options['badge_icon_color'] : '#ffffff';
		?>
		<input type="text" id="badge_icon_color" name="greenmetrics_settings[badge_icon_color]" value="<?php echo esc_attr( $value ); ?>" class="greenmetrics-color-picker" data-alpha="true">
		<p class="description"><?php esc_html_e( 'Color of the badge icon.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render badge icon size field.
	 */
	public function render_badge_icon_size_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['badge_icon_size'] ) ? $options['badge_icon_size'] : '16px';
		$numeric_value = intval( $value );
		?>
		<div class="greenmetrics-font-size-wrapper">
			<div class="font-size-control">
				<div class="font-size-input-group">
					<input type="number" 
						id="badge_icon_size_number" 
						min="8" 
						max="48" 
						step="1" 
						value="<?php echo esc_attr( $numeric_value ); ?>" 
						class="font-size-number" 
						onchange="document.getElementById('badge_icon_size').value = this.value + 'px';">
					<div class="font-size-unit">
						<span>px</span>
					</div>
				</div>
				<div class="font-size-arrows">
					<span class="dashicons dashicons-arrow-up-alt2" onclick="incrementFontSize('badge_icon_size_number')"></span>
					<span class="dashicons dashicons-arrow-down-alt2" onclick="decrementFontSize('badge_icon_size_number')"></span>
				</div>
				<input type="hidden" 
					id="badge_icon_size" 
					name="greenmetrics_settings[badge_icon_size]" 
					value="<?php echo esc_attr( $value ); ?>">
			</div>
			<p class="description"><?php esc_html_e( 'Size of the badge icon in pixels.', 'greenmetrics' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render badge position field.
	 */
	public function render_badge_position_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['badge_position'] ) ? $options['badge_position'] : 'bottom-right';
		?>
		<select id="badge_position" name="greenmetrics_settings[badge_position]">
			<option value="bottom-right" <?php selected( $value, 'bottom-right' ); ?>><?php esc_html_e( 'Bottom Right', 'greenmetrics' ); ?></option>
			<option value="bottom-left" <?php selected( $value, 'bottom-left' ); ?>><?php esc_html_e( 'Bottom Left', 'greenmetrics' ); ?></option>
			<option value="top-right" <?php selected( $value, 'top-right' ); ?>><?php esc_html_e( 'Top Right', 'greenmetrics' ); ?></option>
			<option value="top-left" <?php selected( $value, 'top-left' ); ?>><?php esc_html_e( 'Top Left', 'greenmetrics' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Choose where the badge appears on your website.', 'greenmetrics' ); ?></p>
		<?php
	}
	
	/**
	 * Render badge size field.
	 */
	public function render_badge_size_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['badge_size'] ) ? $options['badge_size'] : 'medium';
		?>
		<select id="badge_size" name="greenmetrics_settings[badge_size]">
			<option value="small" <?php selected( $value, 'small' ); ?>><?php esc_html_e( 'Small', 'greenmetrics' ); ?></option>
			<option value="medium" <?php selected( $value, 'medium' ); ?>><?php esc_html_e( 'Medium', 'greenmetrics' ); ?></option>
			<option value="large" <?php selected( $value, 'large' ); ?>><?php esc_html_e( 'Large', 'greenmetrics' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Choose the badge size.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render badge text field.
	 */
	public function render_badge_text_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['badge_text'] ) ? $options['badge_text'] : 'Eco-Friendly Site';
		?>
		<input type="text" id="badge_text" name="greenmetrics_settings[badge_text]" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
		<p class="description"><?php esc_html_e( 'Text displayed on the badge.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render badge background color field.
	 */
	public function render_badge_background_color_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['badge_background_color'] ) ? $options['badge_background_color'] : '#4CAF50';
		?>
		<input type="text" id="badge_background_color" name="greenmetrics_settings[badge_background_color]" value="<?php echo esc_attr( $value ); ?>" class="greenmetrics-color-picker" data-alpha="true">
		<p class="description"><?php esc_html_e( 'Background color of the badge.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render badge text color field.
	 */
	public function render_badge_text_color_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['badge_text_color'] ) ? $options['badge_text_color'] : '#ffffff';
		?>
		<input type="text" id="badge_text_color" name="greenmetrics_settings[badge_text_color]" value="<?php echo esc_attr( $value ); ?>" class="greenmetrics-color-picker" data-alpha="true">
		<p class="description"><?php esc_html_e( 'Text color for the badge.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render popover content section.
	 */
	public function render_popover_content_section() {
		echo '<p>' . esc_html__( 'Configure the content of the popover.', 'greenmetrics' ) . '</p>';
	}

	/**
	 * Render popover title field.
	 */
	public function render_popover_title_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['popover_title'] ) ? $options['popover_title'] : 'Environmental Impact';
		?>
		<input type="text" id="popover_title" name="greenmetrics_settings[popover_title]" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
		<p class="description"><?php esc_html_e( 'Title of the popover content.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render popover metrics field.
	 */
	public function render_popover_metrics_field() {
		$options = get_option( 'greenmetrics_settings' );
		$metrics = isset( $options['popover_metrics'] ) ? $options['popover_metrics'] : array(
			'carbon_footprint',
			'energy_consumption',
			'data_transfer',
			'total_views',
			'requests',
			'performance_score'
		);

		// Available metrics with display names
		$available_metrics = array(
			'carbon_footprint'    => __( 'Carbon Footprint', 'greenmetrics' ),
			'energy_consumption'  => __( 'Energy Consumption', 'greenmetrics' ),
			'data_transfer'       => __( 'Data Transfer', 'greenmetrics' ),
			'total_views'         => __( 'Page Views', 'greenmetrics' ),
			'requests'            => __( 'HTTP Requests', 'greenmetrics' ),
			'performance_score'   => __( 'Performance Score', 'greenmetrics' ),
		);
		?>
		<div class="metrics-checkboxes">
			<?php foreach ( $available_metrics as $metric_key => $metric_label ) : ?>
				<label class="metrics-checkbox-label">
					<input type="checkbox" 
						name="greenmetrics_settings[popover_metrics][]" 
						value="<?php echo esc_attr( $metric_key ); ?>" 
						<?php checked( in_array( $metric_key, $metrics, true ) ); ?>>
					<?php echo esc_html( $metric_label ); ?>
				</label>
			<?php endforeach; ?>
		</div>
		<p class="description"><?php esc_html_e( 'Select which metrics to display in the popover.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render popover custom content field.
	 */
	public function render_popover_custom_content_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['popover_custom_content'] ) ? $options['popover_custom_content'] : '';
		?>
		<textarea id="popover_custom_content" name="greenmetrics_settings[popover_custom_content]" rows="4" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description"><?php esc_html_e( 'Custom content to display in the popover.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render popover background color field.
	 */
	public function render_popover_bg_color_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['popover_bg_color'] ) ? $options['popover_bg_color'] : '#ffffff';
		?>
		<input type="text" id="popover_bg_color" name="greenmetrics_settings[popover_bg_color]" value="<?php echo esc_attr( $value ); ?>" class="greenmetrics-color-picker" data-alpha="true">
		<p class="description"><?php esc_html_e( 'Background color of the popover content.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render popover text color field.
	 */
	public function render_popover_text_color_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['popover_text_color'] ) ? $options['popover_text_color'] : '#333333';
		?>
		<input type="text" id="popover_text_color" name="greenmetrics_settings[popover_text_color]" value="<?php echo esc_attr( $value ); ?>" class="greenmetrics-color-picker" data-alpha="true">
		<p class="description"><?php esc_html_e( 'Text color of the popover content.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render popover metrics color field.
	 */
	public function render_popover_metrics_color_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['popover_metrics_color'] ) ? $options['popover_metrics_color'] : '#4CAF50';
		?>
		<input type="text" id="popover_metrics_color" name="greenmetrics_settings[popover_metrics_color]" value="<?php echo esc_attr( $value ); ?>" class="greenmetrics-color-picker" data-alpha="true">
		<p class="description"><?php esc_html_e( 'Text color of the metrics in the popover.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render popover metrics list background color field.
	 */
	public function render_popover_metrics_list_bg_color_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['popover_metrics_list_bg_color'] ) ? $options['popover_metrics_list_bg_color'] : 'transparent';
		?>
		<input type="text" id="popover_metrics_list_bg_color" name="greenmetrics_settings[popover_metrics_list_bg_color]" value="<?php echo esc_attr( $value ); ?>" class="greenmetrics-color-picker" data-alpha="true">
		<p class="description"><?php esc_html_e( 'Background color of the metric list items in the popover.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render popover metrics list hover background color field.
	 */
	public function render_popover_metrics_list_hover_bg_color_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['popover_metrics_list_hover_bg_color'] ) ? $options['popover_metrics_list_hover_bg_color'] : '#f3f4f6';
		?>
		<input type="text" id="popover_metrics_list_hover_bg_color" name="greenmetrics_settings[popover_metrics_list_hover_bg_color]" value="<?php echo esc_attr( $value ); ?>" class="greenmetrics-color-picker" data-alpha="true">
		<p class="description"><?php esc_html_e( 'Background color of the metric list items when hovered in the popover.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render popover metrics background color field.
	 */
	public function render_popover_metrics_bg_color_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['popover_metrics_bg_color'] ) ? $options['popover_metrics_bg_color'] : 'rgba(0, 0, 0, 0.05)';
		?>
		<input type="text" id="popover_metrics_bg_color" name="greenmetrics_settings[popover_metrics_bg_color]" value="<?php echo esc_attr( $value ); ?>" class="greenmetrics-color-picker" data-alpha="true">
		<p class="description"><?php esc_html_e( 'Background color of the metrics in the popover.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render popover content font field.
	 */
	public function render_popover_content_font_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['popover_content_font'] ) ? $options['popover_content_font'] : 'inherit';
		
		$font_options = array(
			'inherit' => __( 'Theme Default', 'greenmetrics' ),
			'Arial, sans-serif' => 'Arial',
			'Helvetica, Arial, sans-serif' => 'Helvetica',
			'Georgia, serif' => 'Georgia',
			'Times New Roman, Times, serif' => 'Times New Roman',
			'Verdana, Geneva, sans-serif' => 'Verdana',
			'system-ui, sans-serif' => 'System UI',
			'Tahoma, Geneva, sans-serif' => 'Tahoma',
			'Trebuchet MS, sans-serif' => 'Trebuchet MS',
			'Courier New, monospace' => 'Courier New',
			'Palatino, serif' => 'Palatino',
			'Garamond, serif' => 'Garamond',
			'Century Gothic, sans-serif' => 'Century Gothic',
			'sans-serif' => 'Generic Sans-serif',
			'serif' => 'Generic Serif',
			'monospace' => 'Generic Monospace',
		);
		
		?>
		<select id="popover_content_font" name="greenmetrics_settings[popover_content_font]" class="regular-text">
			<?php foreach ( $font_options as $font_value => $font_name ) : ?>
				<option value="<?php echo esc_attr( $font_value ); ?>" <?php selected( $value, $font_value ); ?>><?php echo esc_html( $font_name ); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php esc_html_e( 'Font family for the popover content. "Theme Default" will inherit the font from your theme.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render popover content font size field.
	 */
	public function render_popover_content_font_size_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['popover_content_font_size'] ) ? $options['popover_content_font_size'] : '16px';
		// Remove 'px' for the raw input value
		$numeric_value = intval( $value );
		?>
		<div class="greenmetrics-font-size-wrapper">
			<div class="font-size-control">
				<div class="font-size-input-group">
					<input type="number" 
						id="popover_content_font_size_number" 
						min="8" 
						max="36" 
						step="1" 
						value="<?php echo esc_attr( $numeric_value ); ?>" 
						class="font-size-number" 
						onchange="document.getElementById('popover_content_font_size').value = this.value + 'px';">
					<div class="font-size-unit">
						<span>px</span>
					</div>
				</div>
				<div class="font-size-arrows">
					<span class="dashicons dashicons-arrow-up-alt2" onclick="incrementFontSize('popover_content_font_size_number')"></span>
					<span class="dashicons dashicons-arrow-down-alt2" onclick="decrementFontSize('popover_content_font_size_number')"></span>
				</div>
				<input type="hidden" 
					id="popover_content_font_size" 
					name="greenmetrics_settings[popover_content_font_size]" 
					value="<?php echo esc_attr( $value ); ?>">
			</div>
			<p class="description"><?php esc_html_e( 'Font size for the popover content.', 'greenmetrics' ); ?></p>
		</div>
		<script>
		function incrementFontSize(inputId) {
			const input = document.getElementById(inputId);
			const hiddenInput = document.getElementById(inputId.replace('_number', ''));
			const currentValue = parseInt(input.value) || 0;
			const newValue = Math.min(currentValue + 1, parseInt(input.max));
			input.value = newValue;
			hiddenInput.value = newValue + 'px';
			
			// Trigger change event for preview update
			const event = new Event('change', { bubbles: true });
			input.dispatchEvent(event);
		}
		
		function decrementFontSize(inputId) {
			const input = document.getElementById(inputId);
			const hiddenInput = document.getElementById(inputId.replace('_number', ''));
			const currentValue = parseInt(input.value) || 0;
			const newValue = Math.max(currentValue - 1, parseInt(input.min));
			input.value = newValue;
			hiddenInput.value = newValue + 'px';
			
			// Trigger change event for preview update
			const event = new Event('change', { bubbles: true });
			input.dispatchEvent(event);
		}
		</script>
		<?php
	}

	/**
	 * Render popover metrics font field.
	 */
	public function render_popover_metrics_font_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['popover_metrics_font'] ) ? $options['popover_metrics_font'] : 'inherit';
		
		$font_options = array(
			'inherit' => __( 'Theme Default', 'greenmetrics' ),
			'Arial, sans-serif' => 'Arial',
			'Helvetica, Arial, sans-serif' => 'Helvetica',
			'Georgia, serif' => 'Georgia',
			'Times New Roman, Times, serif' => 'Times New Roman',
			'Verdana, Geneva, sans-serif' => 'Verdana',
			'system-ui, sans-serif' => 'System UI',
			'Tahoma, Geneva, sans-serif' => 'Tahoma',
			'Trebuchet MS, sans-serif' => 'Trebuchet MS',
			'Courier New, monospace' => 'Courier New',
			'Palatino, serif' => 'Palatino',
			'Garamond, serif' => 'Garamond',
			'Century Gothic, sans-serif' => 'Century Gothic',
			'sans-serif' => 'Generic Sans-serif',
			'serif' => 'Generic Serif',
			'monospace' => 'Generic Monospace',
		);
		
		?>
		<select id="popover_metrics_font" name="greenmetrics_settings[popover_metrics_font]" class="regular-text">
			<?php foreach ( $font_options as $font_value => $font_name ) : ?>
				<option value="<?php echo esc_attr( $font_value ); ?>" <?php selected( $value, $font_value ); ?>><?php echo esc_html( $font_name ); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php esc_html_e( 'Font family for the metrics in the popover. "Theme Default" will inherit the font from your theme.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render popover metrics label font size field.
	 */
	public function render_popover_metrics_label_font_size_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['popover_metrics_label_font_size'] ) ? $options['popover_metrics_label_font_size'] : '12px';
		// Remove 'px' for the raw input value
		$numeric_value = intval( $value );
		?>
		<div class="greenmetrics-font-size-wrapper">
			<div class="font-size-control">
				<div class="font-size-input-group">
					<input type="number" 
						id="popover_metrics_label_font_size_number" 
						min="8" 
						max="36" 
						step="1" 
						value="<?php echo esc_attr( $numeric_value ); ?>" 
						class="font-size-number" 
						onchange="document.getElementById('popover_metrics_label_font_size').value = this.value + 'px';">
					<div class="font-size-unit">
						<span>px</span>
					</div>
				</div>
				<div class="font-size-arrows">
					<span class="dashicons dashicons-arrow-up-alt2" onclick="incrementFontSize('popover_metrics_label_font_size_number')"></span>
					<span class="dashicons dashicons-arrow-down-alt2" onclick="decrementFontSize('popover_metrics_label_font_size_number')"></span>
				</div>
				<input type="hidden" 
					id="popover_metrics_label_font_size" 
					name="greenmetrics_settings[popover_metrics_label_font_size]" 
					value="<?php echo esc_attr( $value ); ?>">
			</div>
			<p class="description"><?php esc_html_e( 'Font size for the metric labels in the popover.', 'greenmetrics' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render popover metrics font size field.
	 */
	public function render_popover_metrics_font_size_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['popover_metrics_font_size'] ) ? $options['popover_metrics_font_size'] : '14px';
		// Remove 'px' for the raw input value
		$numeric_value = intval( $value );
		?>
		<div class="greenmetrics-font-size-wrapper">
			<div class="font-size-control">
				<div class="font-size-input-group">
					<input type="number" 
						id="popover_metrics_font_size_number" 
						min="8" 
						max="36" 
						step="1" 
						value="<?php echo esc_attr( $numeric_value ); ?>" 
						class="font-size-number" 
						onchange="document.getElementById('popover_metrics_font_size').value = this.value + 'px';">
					<div class="font-size-unit">
						<span>px</span>
					</div>
				</div>
				<div class="font-size-arrows">
					<span class="dashicons dashicons-arrow-up-alt2" onclick="incrementFontSize('popover_metrics_font_size_number')"></span>
					<span class="dashicons dashicons-arrow-down-alt2" onclick="decrementFontSize('popover_metrics_font_size_number')"></span>
				</div>
				<input type="hidden" 
					id="popover_metrics_font_size" 
					name="greenmetrics_settings[popover_metrics_font_size]" 
					value="<?php echo esc_attr( $value ); ?>">
			</div>
			<p class="description"><?php esc_html_e( 'Font size for the metrics in the popover.', 'greenmetrics' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			'greenmetrics-admin',
			GREENMETRICS_PLUGIN_URL . 'includes/admin/css/greenmetrics-admin.css',
			array(),
			GREENMETRICS_VERSION,
			'all'
		);
		
		// Add inline styles for the font size inputs
		$font_size_styles = '
		.greenmetrics-font-size-wrapper {
			margin-bottom: 15px;
		}
		.font-size-control {
			display: flex;
			align-items: center;
			max-width: 165px;
			position: relative;
			margin-bottom: 5px;
		}
		.font-size-input-group {
			display: flex;
			border: 1px solid #8d96a0;
			border-radius: 4px;
			overflow: hidden;
			flex: 1;
		}
		.font-size-number {
			border: none !important;
			box-shadow: none !important;
			flex: 1;
			text-align: center;
			padding: 0 5px !important;
			min-height: 30px;
			-moz-appearance: textfield;
			width: 65px !important;
			font-size: 13px !important;
		}
		.font-size-number:focus {
			outline: none !important;
		}
		.font-size-number::-webkit-outer-spin-button,
		.font-size-number::-webkit-inner-spin-button {
			-webkit-appearance: none;
			margin: 0;
		}
		.font-size-unit {
			display: flex;
			align-items: center;
			justify-content: center;
			background: #f0f0f1;
			min-width: 30px;
			color: #50575e;
			border-left: 1px solid #8d96a0;
			font-size: 13px;
		}
		.font-size-arrows {
			display: flex;
			flex-direction: column;
			margin-left: 6px;
			height: 30px;
			justify-content: space-between;
		}
		.font-size-arrows .dashicons {
			font-size: 18px;
			height: 15px;
			width: 15px;
			cursor: pointer;
			color: #2271b1;
			transition: color 0.2s ease;
			line-height: 15px;
		}
		.font-size-arrows .dashicons:hover {
			color: #135e96;
		}
		';
		wp_add_inline_style( 'greenmetrics-admin', $font_size_styles );
	}

	/**
	 * Register and enqueue admin scripts.
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_media(); // Add media uploader scripts
		
		// Enqueue Chart.js
		wp_enqueue_script(
			'chart-js',
			GREENMETRICS_PLUGIN_URL . 'includes/admin/js/chart.min.js',
			array(),
			GREENMETRICS_VERSION,
			true
		);
		
		wp_enqueue_script(
			'greenmetrics-admin',
			GREENMETRICS_PLUGIN_URL . 'includes/admin/js/greenmetrics-admin.js',
			array( 'jquery', 'wp-color-picker', 'wp-util', 'chart-js' ),
			GREENMETRICS_VERSION,
			true
		);

		// Add settings for the admin JavaScript
		wp_localize_script(
			'greenmetrics-admin',
			'greenmetricsAdmin',
			array(
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'greenmetrics_admin_nonce' ),
				'refreshMessage'    => __( 'Statistics refreshed successfully.', 'greenmetrics' ),
				'refreshError'      => __( 'Error refreshing statistics.', 'greenmetrics' ),
				'selectIconText'    => __( 'Select or Upload Icon', 'greenmetrics' ),
				'selectIconBtnText' => __( 'Use this Icon', 'greenmetrics' ),
				'customIconText'    => __( 'Custom Icon', 'greenmetrics' ),
				'rest_url'          => get_rest_url( null, 'greenmetrics/v1' ),
				'rest_nonce'        => wp_create_nonce( 'wp_rest' ),
				'loadingText'       => __( 'Loading data...', 'greenmetrics' ),
				'noDataText'        => __( 'No data available for the selected period.', 'greenmetrics' ),
			)
		);

		wp_enqueue_style(
			'greenmetrics-admin',
			GREENMETRICS_PLUGIN_URL . 'includes/admin/css/greenmetrics-admin.css',
			array( 'wp-color-picker' ),
			GREENMETRICS_VERSION,
			'all'
		);
	}

	/**
	 * Render main admin page.
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include GREENMETRICS_PLUGIN_DIR . 'includes/admin/partials/greenmetrics-admin-display.php';
	}

	/**
	 * Render display settings page.
	 */
	public function render_display_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include GREENMETRICS_PLUGIN_DIR . 'includes/admin/partials/greenmetrics-display-settings.php';
	}

	/**
	 * Handle refresh statistics form submission.
	 */
	public function handle_refresh_stats() {
		// Check if the form was submitted and the refresh_stats action was set
		if ( isset( $_POST['action'] ) && 'refresh_stats' === $_POST['action'] ) {
			// Verify nonce
			if ( isset( $_POST['greenmetrics_refresh_nonce'] ) && wp_verify_nonce( $_POST['greenmetrics_refresh_nonce'], 'greenmetrics_refresh_stats' ) ) {
				// Trigger manual cache refresh
				\GreenMetrics\GreenMetrics_Tracker::manual_cache_refresh();
				
				// Redirect back to the same page with a simple parameter
				$redirect_url = add_query_arg( 'stats-refreshed', 'true', remove_query_arg( 'settings-updated' ) );
				wp_redirect( $redirect_url );
				exit;
			}
		}
	}

	/**
	 * Handle AJAX request to get an icon.
	 */
	public function handle_get_icon() {
		// Get the icon type from the request
		$icon_type = isset( $_POST['icon_type'] ) ? sanitize_text_field( $_POST['icon_type'] ) : 'leaf';
		
		// Get the icon HTML
		$icon_html = \GreenMetrics\GreenMetrics_Icons::get_icon( $icon_type );
		
		// Return the icon HTML as a JSON response
		wp_send_json_success( $icon_html );
	}
}
