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
					'badge_position'         => 'bottom-right',
					'badge_size'             => 'medium',
					'badge_text'             => 'Eco-Friendly Site',
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

		// Display Settings Section
		add_settings_section(
			'greenmetrics_display',
			__( 'Display Settings', 'greenmetrics' ),
			array( $this, 'render_display_section' ),
			'greenmetrics'
		);

		add_settings_field(
			'enable_badge',
			__( 'Display Badge', 'greenmetrics' ),
			array( $this, 'render_badge_field' ),
			'greenmetrics',
			'greenmetrics_display',
			array( 'label_for' => 'enable_badge' )
		);

		add_settings_field(
			'badge_position',
			__( 'Badge Position', 'greenmetrics' ),
			array( $this, 'render_badge_position_field' ),
			'greenmetrics',
			'greenmetrics_display',
			array( 'label_for' => 'badge_position' )
		);
		
		add_settings_field(
			'badge_size',
			__( 'Badge Size', 'greenmetrics' ),
			array( $this, 'render_badge_size_field' ),
			'greenmetrics',
			'greenmetrics_display',
			array( 'label_for' => 'badge_size' )
		);

		add_settings_field(
			'badge_text',
			__( 'Badge Text', 'greenmetrics' ),
			array( $this, 'render_badge_text_field' ),
			'greenmetrics',
			'greenmetrics_display',
			array( 'label_for' => 'badge_text' )
		);

		add_settings_field(
			'badge_background_color',
			__( 'Background Color', 'greenmetrics' ),
			array( $this, 'render_badge_background_color_field' ),
			'greenmetrics',
			'greenmetrics_display',
			array( 'label_for' => 'badge_background_color' )
		);

		add_settings_field(
			'badge_text_color',
			__( 'Text Color', 'greenmetrics' ),
			array( $this, 'render_badge_text_color_field' ),
			'greenmetrics',
			'greenmetrics_display',
			array( 'label_for' => 'badge_text_color' )
		);

		add_settings_field(
			'badge_icon_color',
			__( 'Icon Color', 'greenmetrics' ),
			array( $this, 'render_badge_icon_color_field' ),
			'greenmetrics',
			'greenmetrics_display',
			array( 'label_for' => 'badge_icon_color' )
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

		$current_settings = get_option( 'greenmetrics_settings', array() );

		if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
			greenmetrics_log( 'Settings sanitize - Current settings', $current_settings );
		}

		// Create a new settings array with defaults
		$sanitized = array(
			'tracking_enabled'       => 0,
			'enable_badge'           => 0,
			'badge_position'         => 'bottom-right',
			'badge_size'             => 'medium',
			'badge_text'             => 'Eco-Friendly Site',
			'badge_background_color' => '#4CAF50',
			'badge_text_color'       => '#ffffff',
			'badge_icon_color'       => '#ffffff',
		);

		// Sanitize checkboxes
		if ( isset( $input['tracking_enabled'] ) && $input['tracking_enabled'] ) {
			$sanitized['tracking_enabled'] = 1;
		}

		if ( isset( $input['enable_badge'] ) && $input['enable_badge'] ) {
			$sanitized['enable_badge'] = 1;
		}

		// Sanitize text and dropdown fields
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
	 * Register the JavaScript for the admin area.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			'greenmetrics-admin',
			GREENMETRICS_PLUGIN_URL . 'includes/admin/js/greenmetrics-admin.js',
			array( 'jquery', 'wp-util' ),
			GREENMETRICS_VERSION,
			true
		);

		wp_localize_script(
			'greenmetrics-admin',
			'greenmetricsAdmin',
			array(
				'rest_url'   => esc_url_raw( rest_url() ),
				'rest_nonce' => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	/**
	 * Render admin page.
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include GREENMETRICS_PLUGIN_DIR . 'includes/admin/partials/greenmetrics-admin-display.php';
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
