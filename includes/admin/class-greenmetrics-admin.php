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
			$valid_icons = array( 'leaf', 'tree', 'globe', 'recycle', 'custom' );
			$sanitized['badge_icon_type'] = in_array( $input['badge_icon_type'], $valid_icons ) 
				? $input['badge_icon_type'] 
				: 'leaf';
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
		<input type="checkbox" id="tracking_enabled" name="greenmetrics_settings[tracking_enabled]" value="1" <?php checked( $value, 1 ); ?>>
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
		<input type="checkbox" id="enable_badge" name="greenmetrics_settings[enable_badge]" value="1" <?php checked( $value, 1 ); ?>>
		<label for="enable_badge"><?php esc_html_e( 'Display eco-friendly badge', 'greenmetrics' ); ?></label>
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
		<input type="checkbox" id="display_icon" name="greenmetrics_settings[display_icon]" value="1" <?php checked( $value, 1 ); ?>>
		<label for="display_icon"><?php esc_html_e( 'Display icon', 'greenmetrics' ); ?></label>
		<p class="description"><?php esc_html_e( 'Show an icon next to the badge', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render badge icon type field.
	 */
	public function render_badge_icon_type_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['badge_icon_type'] ) ? $options['badge_icon_type'] : 'leaf';
		?>
		<div class="greenmetrics-icon-selection">
			<select id="badge_icon_type" name="greenmetrics_settings[badge_icon_type]" style="display:none;">
				<option value="leaf" <?php selected( $value, 'leaf' ); ?>><?php esc_html_e( 'Leaf', 'greenmetrics' ); ?></option>
				<option value="tree" <?php selected( $value, 'tree' ); ?>><?php esc_html_e( 'Tree', 'greenmetrics' ); ?></option>
				<option value="globe" <?php selected( $value, 'globe' ); ?>><?php esc_html_e( 'Globe', 'greenmetrics' ); ?></option>
				<option value="recycle" <?php selected( $value, 'recycle' ); ?>><?php esc_html_e( 'Recycle', 'greenmetrics' ); ?></option>
				<option value="custom" <?php selected( $value, 'custom' ); ?>><?php esc_html_e( 'Custom', 'greenmetrics' ); ?></option>
			</select>
			
			<div class="icon-options">
				<div class="icon-option <?php echo $value === 'leaf' ? 'selected' : ''; ?>" data-value="leaf">
					<div class="icon-preview">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 8C8 10 5.9 16.17 3.82 21.34l1.89.66.95-2.3c.48.17 1.02.3 1.58.3C17 20 22 13.46 22 6c0-.55-.06-1.09-.14-1.62C20.18 4.15 18.66 4 17 4V2c1.67 0 3.35.12 5 .34V4c-1.67-.22-3.33-.34-5-.34v2zM2 6c0 7.46 5 14 14.5 14 .56 0 1.1-.13 1.58-.3l.95 2.3 1.89-.66C18.1 16.17 16 10 7 8c0 0-5 0-5 0z" /></svg>
					</div>
					<span><?php esc_html_e( 'Leaf', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'tree' ? 'selected' : ''; ?>" data-value="tree">
					<div class="icon-preview">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22c4.97 0 9-4.03 9-9-4.97 0-9 4.03-9 9zm2.44-9.43h-.44v2h.44c2.32 0 2.49 3.23 2.49 3.23 1.52-1.84 2.63-4.43 1.73-7C17.56 8.37 15.5 7 15.5 7S14.8 9.1 13 9.42v.36c1.32-.18 2.44.11 2.44.11s-1.22 1.91-1 3.68z"/><path d="M12.28 10h-.56v2h.56c2.33 0 2.51 3.45 2.51 3.45 1.55-1.89 2.67-4.63 1.77-7.24-.51-1.46-2.18-3.02-2.18-3.02s-.99 2.18-2.1 2.48V8c1.34-.2 2.55.07 2.55.07s-1.34 1.66-1.14 3.44z"/><path d="M12.63 5.33c-.28.47-1.04 1.68-2 1.87V8.8c1.35-.19 2.97.31 2.97.31S12.69 10.3 12.22 12h.33v-2h-.16c.06-.32.2-.65.44-.97.19.38.39.75.58 1.09l.66-.42c-.18-.28-.33-.57-.46-.85 0 0 .99.17 2.22.5-.27-.5-2.47-4.02-3.2-4.02z"/><path d="M10.45 12h-.43v8.17c.34-.14.66-.34.95-.55L10.45 12zm1.66 4.62c.1.21.19.42.27.63-.16-.19-.31-.39-.46-.57.07-.02.12-.04.19-.06zm1.14-4.62L12.1 17.1c.45-.11.88-.29 1.29-.51l-.14-4.59z"/><path d="M9.3 14.13l-.24 7.14c.24.11.48.19.73.26l-.42-7.8c-.02.14-.05.27-.07.4zm3.33 1.7c-.04-.04-.08-.09-.12-.14.03.05.06.09.09.13.01 0 .02.01.03.01zm-.83-3.83l-.32 7.46c.29.05.58.08.88.08.12 0 .24-.01.36-.02L12 12l-.2 0z"/></svg>
					</div>
					<span><?php esc_html_e( 'Tree', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'globe' ? 'selected' : ''; ?>" data-value="globe">
					<div class="icon-preview">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z" /></svg>
					</div>
					<span><?php esc_html_e( 'Globe', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'recycle' ? 'selected' : ''; ?>" data-value="recycle">
					<div class="icon-preview">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M5.77 7.15L7.2 4.78l1.03-1.71c.39-.65 1.33-.65 1.72 0l1.48 2.46-1.23 2.06-1 1.34-2.43-4.78zm15.95 5.82l-1.6-2.66-3.46 2L18.87 16H21v2l-3.87-7.03zM16 21h1.5l2.05-3.42-3.46-2-1.09 1.84L16 21zm-3.24-3.71l-1.03-1.71-1.43 2.43-2.43 4.78 1.6 2.66 3.46-2 1.03-1.71-1.43-2.45zM13.42 8.5l-1.48-2.46c-.39-.65-1.33-.65-1.72 0L9.22 7.15l-1 1.34 2.43 4.78 1.6-2.66 1.17-2.11zM10.5 14.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" /></svg>
					</div>
					<span><?php esc_html_e( 'Recycle', 'greenmetrics' ); ?></span>
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
		<input type="color" id="badge_icon_color" name="greenmetrics_settings[badge_icon_color]" value="<?php echo esc_attr( $value ); ?>">
		<p class="description"><?php esc_html_e( 'Icon color for the badge.', 'greenmetrics' ); ?></p>
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
		<input type="color" id="badge_background_color" name="greenmetrics_settings[badge_background_color]" value="<?php echo esc_attr( $value ); ?>">
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
		<input type="color" id="badge_text_color" name="greenmetrics_settings[badge_text_color]" value="<?php echo esc_attr( $value ); ?>">
		<p class="description"><?php esc_html_e( 'Text color for the badge.', 'greenmetrics' ); ?></p>
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
	}

	/**
	 * Register and enqueue admin scripts.
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();

		wp_enqueue_script(
			'greenmetrics-admin',
			GREENMETRICS_PLUGIN_URL . 'includes/admin/js/greenmetrics-admin.js',
			array( 'jquery', 'wp-color-picker', 'wp-util' ),
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
			)
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
}
