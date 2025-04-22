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
					'tracking_enabled' => 1,
					'enable_badge'     => 1,
				),
			)
		);

		add_settings_section(
			'greenmetrics_general',
			__( 'General Settings', 'greenmetrics' ),
			array( $this, 'render_settings_section' ),
			'greenmetrics'
		);

		add_settings_field(
			'tracking_enabled',
			__( 'Enable Tracking', 'greenmetrics' ),
			array( $this, 'render_tracking_field' ),
			'greenmetrics',
			'greenmetrics_general',
			array( 'label_for' => 'tracking_enabled' )
		);

		add_settings_field(
			'enable_badge',
			__( 'Display Badge', 'greenmetrics' ),
			array( $this, 'render_badge_field' ),
			'greenmetrics',
			'greenmetrics_general',
			array( 'label_for' => 'enable_badge' )
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

		// Create a new settings array with all values explicitly set to 0 (disabled) by default
		$sanitized = array(
			'tracking_enabled' => 0,
			'enable_badge'     => 0,
		);

		// Only if the checkboxes are checked in the input, set them to 1
		if ( isset( $input['tracking_enabled'] ) && $input['tracking_enabled'] ) {
			$sanitized['tracking_enabled'] = 1;
		}

		if ( isset( $input['enable_badge'] ) && $input['enable_badge'] ) {
			$sanitized['enable_badge'] = 1;
		}

		// Log the result
		if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
			greenmetrics_log( 'Settings sanitize - Sanitized result', $sanitized );
		}

		return $sanitized;
	}

	/**
	 * Render settings section.
	 */
	public function render_settings_section() {
		echo '<p>' . esc_html__( 'Configure the general settings for GreenMetrics.', 'greenmetrics' ) . '</p>';
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
				
				// Redirect back to the same page to avoid form resubmission
				$redirect_url = add_query_arg( 'stats-refreshed', 'true', remove_query_arg( 'settings-updated' ) );
				wp_redirect( $redirect_url );
				exit;
			}
		}
	}
}
