<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/admin
 */

namespace GreenMetrics\Admin;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Import required classes from other namespaces
use GreenMetrics\GreenMetrics_DB_Helper;
use GreenMetrics\GreenMetrics_Error_Handler;

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
		add_action( 'admin_init', array( $this, 'check_database_errors' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_notices', array( $this, 'show_settings_update_notice' ) );
		add_action( 'admin_init', array( $this, 'handle_refresh_stats' ) );

		// Admin post handlers
		add_action( 'admin_post_greenmetrics_run_data_management', array( $this, 'handle_run_data_management' ) );
		add_action( 'admin_post_greenmetrics_refresh_stats', array( $this, 'handle_refresh_stats_redirect' ) );

		// AJAX handlers
		add_action( 'wp_ajax_greenmetrics_refresh_stats', array( $this, 'handle_refresh_stats' ) );
		add_action( 'wp_ajax_greenmetrics_get_icon', array( $this, 'handle_get_icon' ) );
		add_action( 'wp_ajax_nopriv_greenmetrics_get_icon', array( $this, 'handle_get_icon' ) );
		add_action( 'wp_ajax_greenmetrics_send_test_email', array( $this, 'handle_send_test_email' ) );
		add_action( 'wp_ajax_greenmetrics_get_email_preview', array( $this, 'handle_get_email_preview' ) );
	}

	/**
	 * Display notices for settings updates
	 */
	public function show_settings_update_notice() {
		// We don't need to show a custom notice for settings updates
		// WordPress core already handles this with the "Settings saved." notice

		// But we still log the update if debugging is enabled
		if ( isset( $_GET['settings-updated'] ) && sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// Check nonce is present and valid when handling settings update
			if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'greenmetrics-options' ) ) {
				// Log the issue
				if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
					greenmetrics_log( 'Settings updated but nonce verification failed', null, 'warning' );
				}
			}

			// Log the current settings after update
			if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
				$settings = get_option( 'greenmetrics_settings', array() );
				greenmetrics_log( 'Settings updated via WP Settings API', $settings );
			}
		}

		// Display notice for stats refresh
		if ( isset( $_GET['stats-refreshed'] ) && sanitize_text_field( wp_unslash( $_GET['stats-refreshed'] ) ) === 'true' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Statistics refreshed successfully!', 'greenmetrics' ) . '</p></div>';
		}

		// Display notice for data management tasks
		if ( isset( $_GET['data-management-updated'] ) && sanitize_text_field( wp_unslash( $_GET['data-management-updated'] ) ) === 'true' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// Check if we have aggregation or pruning parameters
			if ( isset( $_GET['aggregation'] ) && sanitize_text_field( wp_unslash( $_GET['aggregation'] ) ) === 'true' ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Data aggregation completed successfully!', 'greenmetrics' ) . '</p></div>';
			}

			if ( isset( $_GET['pruning'] ) && sanitize_text_field( wp_unslash( $_GET['pruning'] ) ) === 'true' ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Data pruning completed successfully!', 'greenmetrics' ) . '</p></div>';
			}
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

		// Add submenu for Advanced Reports
		add_submenu_page(
			'greenmetrics',
			__( 'Advanced Reports', 'greenmetrics' ),
			__( 'Advanced Reports', 'greenmetrics' ),
			'manage_options',
			'greenmetrics_reports',
			array( $this, 'render_reports_page' )
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

		// Add submenu for Data Management
		add_submenu_page(
			'greenmetrics',
			__( 'Data Management', 'greenmetrics' ),
			__( 'Data Management', 'greenmetrics' ),
			'manage_options',
			'greenmetrics_data_management',
			array( $this, 'render_data_management_page' )
		);

		// Add submenu for Email Reporting
		add_submenu_page(
			'greenmetrics',
			__( 'Email Reporting', 'greenmetrics' ),
			__( 'Email Reporting', 'greenmetrics' ),
			'manage_options',
			'greenmetrics_email_reporting',
			array( $this, 'render_email_reporting_page' )
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
				'sanitize_callback' => array( \GreenMetrics\GreenMetrics_Settings_Manager::get_instance(), 'sanitize_settings' ),
				'default'           => \GreenMetrics\GreenMetrics_Settings_Manager::get_instance()->get_defaults(),
			)
		);

		// Tracking Settings Section - moved to data management page
		add_settings_section(
			'greenmetrics_tracking',
			__( 'Tracking Settings', 'greenmetrics' ),
			array( $this, 'render_tracking_section' ),
			'greenmetrics_data_management'
		);

		add_settings_field(
			'tracking_enabled',
			__( 'Enable Tracking', 'greenmetrics' ),
			array( $this, 'render_tracking_field' ),
			'greenmetrics_data_management',
			'greenmetrics_tracking',
			array( 'label_for' => 'tracking_enabled' )
		);

		// Add Statistics Cache to Settings section (this doesn't have actual settings fields)

		// Email Reporting Settings Section
		add_settings_section(
			'greenmetrics_email_reporting',
			__( 'Email Reporting', 'greenmetrics' ),
			array( $this, 'render_email_reporting_section' ),
			'greenmetrics_email_reporting'
		);

		add_settings_field(
			'email_reporting_enabled',
			__( 'Enable Email Reports', 'greenmetrics' ),
			array( $this, 'render_email_reporting_enabled_field' ),
			'greenmetrics_email_reporting',
			'greenmetrics_email_reporting',
			array( 'label_for' => 'email_reporting_enabled' )
		);

		add_settings_field(
			'email_reporting_frequency',
			__( 'Report Frequency', 'greenmetrics' ),
			array( $this, 'render_email_reporting_frequency_field' ),
			'greenmetrics_email_reporting',
			'greenmetrics_email_reporting',
			array( 'label_for' => 'email_reporting_frequency' )
		);

		add_settings_field(
			'email_reporting_day',
			__( 'Report Day', 'greenmetrics' ),
			array( $this, 'render_email_reporting_day_field' ),
			'greenmetrics_email_reporting',
			'greenmetrics_email_reporting',
			array( 'label_for' => 'email_reporting_day' )
		);

		add_settings_field(
			'email_reporting_recipients',
			__( 'Recipients', 'greenmetrics' ),
			array( $this, 'render_email_reporting_recipients_field' ),
			'greenmetrics_email_reporting',
			'greenmetrics_email_reporting',
			array( 'label_for' => 'email_reporting_recipients' )
		);

		add_settings_field(
			'email_reporting_subject',
			__( 'Email Subject', 'greenmetrics' ),
			array( $this, 'render_email_reporting_subject_field' ),
			'greenmetrics_email_reporting',
			'greenmetrics_email_reporting',
			array( 'label_for' => 'email_reporting_subject' )
		);

		add_settings_field(
			'email_reporting_include_stats',
			__( 'Include Statistics', 'greenmetrics' ),
			array( $this, 'render_email_reporting_include_stats_field' ),
			'greenmetrics_email_reporting',
			'greenmetrics_email_reporting',
			array( 'label_for' => 'email_reporting_include_stats' )
		);

		add_settings_field(
			'email_reporting_include_chart',
			__( 'Include Chart', 'greenmetrics' ),
			array( $this, 'render_email_reporting_include_chart_field' ),
			'greenmetrics_email_reporting',
			'greenmetrics_email_reporting',
			array( 'label_for' => 'email_reporting_include_chart' )
		);

		// Email Template Settings
		add_settings_section(
			'greenmetrics_email_template',
			__( 'Email Template', 'greenmetrics' ),
			array( $this, 'render_email_template_section' ),
			'greenmetrics_email_reporting'
		);

		add_settings_field(
			'email_reporting_header',
			__( 'Email Header', 'greenmetrics' ),
			array( $this, 'render_email_reporting_header_field' ),
			'greenmetrics_email_reporting',
			'greenmetrics_email_template',
			array( 'label_for' => 'email_reporting_header' )
		);

		add_settings_field(
			'email_reporting_footer',
			__( 'Email Footer', 'greenmetrics' ),
			array( $this, 'render_email_reporting_footer_field' ),
			'greenmetrics_email_reporting',
			'greenmetrics_email_template',
			array( 'label_for' => 'email_reporting_footer' )
		);

		add_settings_field(
			'email_reporting_css',
			__( 'Custom CSS', 'greenmetrics' ),
			array( $this, 'render_email_reporting_css_field' ),
			'greenmetrics_email_reporting',
			'greenmetrics_email_template',
			array( 'label_for' => 'email_reporting_css' )
		);

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
						<?php echo wp_kses_post( \GreenMetrics\GreenMetrics_Icons::get_icon( 'leaf' ) ); ?>
					</div>
					<span><?php esc_html_e( 'Leaf', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'tree' ? 'selected' : ''; ?>" data-value="tree">
					<div class="icon-preview">
						<?php echo wp_kses_post( \GreenMetrics\GreenMetrics_Icons::get_icon( 'tree' ) ); ?>
					</div>
					<span><?php esc_html_e( 'Tree', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'globe' ? 'selected' : ''; ?>" data-value="globe">
					<div class="icon-preview">
						<?php echo wp_kses_post( \GreenMetrics\GreenMetrics_Icons::get_icon( 'globe' ) ); ?>
					</div>
					<span><?php esc_html_e( 'Globe', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'recycle' ? 'selected' : ''; ?>" data-value="recycle">
					<div class="icon-preview">
						<?php echo wp_kses_post( \GreenMetrics\GreenMetrics_Icons::get_icon( 'recycle' ) ); ?>
					</div>
					<span><?php esc_html_e( 'Recycle', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'chart-bar' ? 'selected' : ''; ?>" data-value="chart-bar">
					<div class="icon-preview">
						<?php echo wp_kses_post( \GreenMetrics\GreenMetrics_Icons::get_icon( 'chart-bar' ) ); ?>
					</div>
					<span><?php esc_html_e( 'Chart Bar', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'chart-line' ? 'selected' : ''; ?>" data-value="chart-line">
					<div class="icon-preview">
						<?php echo wp_kses_post( \GreenMetrics\GreenMetrics_Icons::get_icon( 'chart-line' ) ); ?>
					</div>
					<span><?php esc_html_e( 'Chart Line', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'chart-pie' ? 'selected' : ''; ?>" data-value="chart-pie">
					<div class="icon-preview">
						<?php echo wp_kses_post( \GreenMetrics\GreenMetrics_Icons::get_icon( 'chart-pie' ) ); ?>
					</div>
					<span><?php esc_html_e( 'Chart Pie', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'analytics' ? 'selected' : ''; ?>" data-value="analytics">
					<div class="icon-preview">
						<?php echo wp_kses_post( \GreenMetrics\GreenMetrics_Icons::get_icon( 'analytics' ) ); ?>
					</div>
					<span><?php esc_html_e( 'Analytics', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'performance' ? 'selected' : ''; ?>" data-value="performance">
					<div class="icon-preview">
						<?php echo wp_kses_post( \GreenMetrics\GreenMetrics_Icons::get_icon( 'performance' ) ); ?>
					</div>
					<span><?php esc_html_e( 'Performance', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'energy' ? 'selected' : ''; ?>" data-value="energy">
					<div class="icon-preview">
						<?php echo wp_kses_post( \GreenMetrics\GreenMetrics_Icons::get_icon( 'energy' ) ); ?>
					</div>
					<span><?php esc_html_e( 'Energy', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'water' ? 'selected' : ''; ?>" data-value="water">
					<div class="icon-preview">
						<?php echo wp_kses_post( \GreenMetrics\GreenMetrics_Icons::get_icon( 'water' ) ); ?>
					</div>
					<span><?php esc_html_e( 'Water', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'eco' ? 'selected' : ''; ?>" data-value="eco">
					<div class="icon-preview">
						<?php echo wp_kses_post( \GreenMetrics\GreenMetrics_Icons::get_icon( 'eco' ) ); ?>
					</div>
					<span><?php esc_html_e( 'Eco', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'nature' ? 'selected' : ''; ?>" data-value="nature">
					<div class="icon-preview">
						<?php echo wp_kses_post( \GreenMetrics\GreenMetrics_Icons::get_icon( 'nature' ) ); ?>
					</div>
					<span><?php esc_html_e( 'Nature', 'greenmetrics' ); ?></span>
				</div>
				<div class="icon-option <?php echo $value === 'sustainability' ? 'selected' : ''; ?>" data-value="sustainability">
					<div class="icon-preview">
						<?php echo wp_kses_post( \GreenMetrics\GreenMetrics_Icons::get_icon( 'sustainability' ) ); ?>
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
		$options       = get_option( 'greenmetrics_settings' );
		$value         = isset( $options['badge_icon_size'] ) ? $options['badge_icon_size'] : '16px';
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
		$value   = isset( $options['badge_text'] ) ? $options['badge_text'] : __( 'Eco-Friendly Site', 'greenmetrics' );
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
	 * Render email reporting section.
	 */
	public function render_email_reporting_section() {
		echo '<p>' . esc_html__( 'Configure scheduled email reports to keep track of your website\'s environmental impact.', 'greenmetrics' ) . '</p>';
	}

	/**
	 * Render email reporting enabled field.
	 */
	public function render_email_reporting_enabled_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['email_reporting_enabled'] ) ? $options['email_reporting_enabled'] : 0;
		?>
		<label class="toggle-switch">
			<input type="checkbox" id="email_reporting_enabled" name="greenmetrics_settings[email_reporting_enabled]" value="1" <?php checked( $value, 1 ); ?>>
			<span class="slider"></span>
		</label>
		<p class="description"><?php esc_html_e( 'Enable scheduled email reports with your website\'s environmental metrics.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render email reporting frequency field.
	 */
	public function render_email_reporting_frequency_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['email_reporting_frequency'] ) ? $options['email_reporting_frequency'] : 'weekly';
		?>
		<select id="email_reporting_frequency" name="greenmetrics_settings[email_reporting_frequency]">
			<option value="daily" <?php selected( $value, 'daily' ); ?>><?php esc_html_e( 'Daily', 'greenmetrics' ); ?></option>
			<option value="weekly" <?php selected( $value, 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'greenmetrics' ); ?></option>
			<option value="monthly" <?php selected( $value, 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'greenmetrics' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'How often to send email reports.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render email reporting day field.
	 */
	public function render_email_reporting_day_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['email_reporting_day'] ) ? $options['email_reporting_day'] : 1;
		$frequency = isset( $options['email_reporting_frequency'] ) ? $options['email_reporting_frequency'] : 'weekly';

		// Show different options based on frequency
		if ( $frequency === 'weekly' ) {
			?>
			<select id="email_reporting_day" name="greenmetrics_settings[email_reporting_day]">
				<option value="0" <?php selected( $value, 0 ); ?>><?php esc_html_e( 'Sunday', 'greenmetrics' ); ?></option>
				<option value="1" <?php selected( $value, 1 ); ?>><?php esc_html_e( 'Monday', 'greenmetrics' ); ?></option>
				<option value="2" <?php selected( $value, 2 ); ?>><?php esc_html_e( 'Tuesday', 'greenmetrics' ); ?></option>
				<option value="3" <?php selected( $value, 3 ); ?>><?php esc_html_e( 'Wednesday', 'greenmetrics' ); ?></option>
				<option value="4" <?php selected( $value, 4 ); ?>><?php esc_html_e( 'Thursday', 'greenmetrics' ); ?></option>
				<option value="5" <?php selected( $value, 5 ); ?>><?php esc_html_e( 'Friday', 'greenmetrics' ); ?></option>
				<option value="6" <?php selected( $value, 6 ); ?>><?php esc_html_e( 'Saturday', 'greenmetrics' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'Day of the week to send reports.', 'greenmetrics' ); ?></p>
			<?php
		} elseif ( $frequency === 'monthly' ) {
			?>
			<select id="email_reporting_day" name="greenmetrics_settings[email_reporting_day]">
				<?php for ( $i = 1; $i <= 28; $i++ ) : ?>
					<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $value, $i ); ?>><?php echo esc_html( $i ); ?></option>
				<?php endfor; ?>
			</select>
			<p class="description"><?php esc_html_e( 'Day of the month to send reports (1-28).', 'greenmetrics' ); ?></p>
			<?php
		} else {
			// For daily, we don't need a day selection
			?>
			<input type="hidden" id="email_reporting_day" name="greenmetrics_settings[email_reporting_day]" value="1">
			<p class="description"><?php esc_html_e( 'Reports will be sent daily at midnight.', 'greenmetrics' ); ?></p>
			<?php
		}
	}

	/**
	 * Render email reporting recipients field.
	 */
	public function render_email_reporting_recipients_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['email_reporting_recipients'] ) ? $options['email_reporting_recipients'] : '';
		?>
		<input type="text" id="email_reporting_recipients" name="greenmetrics_settings[email_reporting_recipients]" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
		<p class="description">
			<?php esc_html_e( 'Email addresses to receive reports (comma-separated). Leave empty to use admin email.', 'greenmetrics' ); ?>
			<br>
			<?php esc_html_e( 'Current admin email:', 'greenmetrics' ); ?> <code><?php echo esc_html( get_option( 'admin_email' ) ); ?></code>
		</p>
		<?php
	}

	/**
	 * Render email reporting subject field.
	 */
	public function render_email_reporting_subject_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['email_reporting_subject'] ) ? $options['email_reporting_subject'] : 'GreenMetrics Report for [site_name]';
		?>
		<input type="text" id="email_reporting_subject" name="greenmetrics_settings[email_reporting_subject]" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
		<p class="description">
			<?php esc_html_e( 'Subject line for email reports.', 'greenmetrics' ); ?>
			<br>
			<?php esc_html_e( 'Available placeholders:', 'greenmetrics' ); ?> <code>[site_name]</code>, <code>[date]</code>
		</p>
		<?php
	}

	/**
	 * Render email reporting include stats field.
	 */
	public function render_email_reporting_include_stats_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['email_reporting_include_stats'] ) ? $options['email_reporting_include_stats'] : 1;
		?>
		<label class="toggle-switch">
			<input type="checkbox" id="email_reporting_include_stats" name="greenmetrics_settings[email_reporting_include_stats]" value="1" <?php checked( $value, 1 ); ?>>
			<span class="slider"></span>
		</label>
		<p class="description"><?php esc_html_e( 'Include environmental statistics in the email report.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render email reporting include chart field.
	 */
	public function render_email_reporting_include_chart_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['email_reporting_include_chart'] ) ? $options['email_reporting_include_chart'] : 1;
		?>
		<label class="toggle-switch">
			<input type="checkbox" id="email_reporting_include_chart" name="greenmetrics_settings[email_reporting_include_chart]" value="1" <?php checked( $value, 1 ); ?>>
			<span class="slider"></span>
		</label>
		<p class="description"><?php esc_html_e( 'Include metrics chart in the email report.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render email template section.
	 */
	public function render_email_template_section() {
		echo '<p>' . esc_html__( 'Customize the appearance of your email reports.', 'greenmetrics' ) . '</p>';
	}

	/**
	 * Render email reporting header field.
	 */
	public function render_email_reporting_header_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['email_reporting_header'] ) ? $options['email_reporting_header'] : '';
		?>
		<textarea id="email_reporting_header" name="greenmetrics_settings[email_reporting_header]" rows="4" class="large-text code"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description"><?php esc_html_e( 'Custom HTML to include at the top of the email. Leave empty to use the default header.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render email reporting footer field.
	 */
	public function render_email_reporting_footer_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['email_reporting_footer'] ) ? $options['email_reporting_footer'] : '';
		?>
		<textarea id="email_reporting_footer" name="greenmetrics_settings[email_reporting_footer]" rows="4" class="large-text code"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description"><?php esc_html_e( 'Custom HTML to include at the bottom of the email. Leave empty to use the default footer.', 'greenmetrics' ); ?></p>
		<?php
	}

	/**
	 * Render email reporting CSS field.
	 */
	public function render_email_reporting_css_field() {
		$options = get_option( 'greenmetrics_settings' );
		$value   = isset( $options['email_reporting_css'] ) ? $options['email_reporting_css'] : '';
		?>
		<textarea id="email_reporting_css" name="greenmetrics_settings[email_reporting_css]" rows="6" class="large-text code"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description"><?php esc_html_e( 'Custom CSS to include in the email. This will override the default styles.', 'greenmetrics' ); ?></p>
		<?php
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
			'performance_score',
		);

		// Available metrics with display names
		$available_metrics = array(
			'carbon_footprint'   => __( 'Carbon Footprint', 'greenmetrics' ),
			'energy_consumption' => __( 'Energy Consumption', 'greenmetrics' ),
			'data_transfer'      => __( 'Data Transfer', 'greenmetrics' ),
			'total_views'        => __( 'Page Views', 'greenmetrics' ),
			'requests'           => __( 'HTTP Requests', 'greenmetrics' ),
			'performance_score'  => __( 'Performance Score', 'greenmetrics' ),
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
			'inherit'                       => __( 'Theme Default', 'greenmetrics' ),
			'Arial, sans-serif'             => 'Arial',
			'Helvetica, Arial, sans-serif'  => 'Helvetica',
			'Georgia, serif'                => 'Georgia',
			'Times New Roman, Times, serif' => 'Times New Roman',
			'Verdana, Geneva, sans-serif'   => 'Verdana',
			'system-ui, sans-serif'         => 'System UI',
			'Tahoma, Geneva, sans-serif'    => 'Tahoma',
			'Trebuchet MS, sans-serif'      => 'Trebuchet MS',
			'Courier New, monospace'        => 'Courier New',
			'Palatino, serif'               => 'Palatino',
			'Garamond, serif'               => 'Garamond',
			'Century Gothic, sans-serif'    => 'Century Gothic',
			'sans-serif'                    => 'Generic Sans-serif',
			'serif'                         => 'Generic Serif',
			'monospace'                     => 'Generic Monospace',
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
			'inherit'                       => __( 'Theme Default', 'greenmetrics' ),
			'Arial, sans-serif'             => 'Arial',
			'Helvetica, Arial, sans-serif'  => 'Helvetica',
			'Georgia, serif'                => 'Georgia',
			'Times New Roman, Times, serif' => 'Times New Roman',
			'Verdana, Geneva, sans-serif'   => 'Verdana',
			'system-ui, sans-serif'         => 'System UI',
			'Tahoma, Geneva, sans-serif'    => 'Tahoma',
			'Trebuchet MS, sans-serif'      => 'Trebuchet MS',
			'Courier New, monospace'        => 'Courier New',
			'Palatino, serif'               => 'Palatino',
			'Garamond, serif'               => 'Garamond',
			'Century Gothic, sans-serif'    => 'Century Gothic',
			'sans-serif'                    => 'Generic Sans-serif',
			'serif'                         => 'Generic Serif',
			'monospace'                     => 'Generic Monospace',
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
	 * Check for database errors and display admin notices.
	 */
	public function check_database_errors() {
		// Check for database errors stored in options
		$db_error = get_option( 'greenmetrics_db_error', false );
		if ( $db_error ) {
			\GreenMetrics\GreenMetrics_Error_Handler::admin_notice(
				sprintf(
					/* translators: %s: Database error message */
					__( 'GreenMetrics: Database error detected. Some features may not work correctly. Error: %s', 'greenmetrics' ),
					esc_html( $db_error )
				),
				'error',
				false
			);
		}

		// Check for aggregated table errors
		$aggregated_db_error = get_option( 'greenmetrics_aggregated_db_error', false );
		if ( $aggregated_db_error ) {
			\GreenMetrics\GreenMetrics_Error_Handler::admin_notice(
				sprintf(
					/* translators: %s: Database error message */
					__( 'GreenMetrics: Aggregated data table error detected. Data aggregation features may not work correctly. Error: %s', 'greenmetrics' ),
					esc_html( $aggregated_db_error )
				),
				'warning',
				false
			);
		}

		// Check if tables exist
		global $wpdb;
		$table_name = $wpdb->prefix . 'greenmetrics_stats';
		$table_exists = GreenMetrics_DB_Helper::table_exists( $table_name );

		if ( ! $table_exists && ! $db_error ) {
			// Table doesn't exist but no error is stored - try to create it
			\GreenMetrics\GreenMetrics_Error_Handler::admin_notice(
				__( 'GreenMetrics: Database tables not found. Attempting to create them...', 'greenmetrics' ),
				'warning',
				true
			);

			// Try to create the table
			$result = \GreenMetrics\GreenMetrics_DB_Helper::create_stats_table( true );

			// No need to check the result here as the create_stats_table method will display appropriate notices
		}
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		$screen = get_current_screen();

		// Early return if not on admin page or can't determine screen
		if ( ! $screen ) {
			return;
		}

		// Only load our styles on GreenMetrics plugin pages
		// This includes our plugin settings pages and any page with greenmetrics in the ID
		$current_page = '';
		if ( isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Checking GET parameter only for conditional loading of styles, no data modification.
			$current_page = sanitize_text_field( wp_unslash( $_GET['page'] ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Simply checking if on a plugin page for conditionally loading styles
		if ( strpos( $screen->id, 'greenmetrics' ) === false &&
			empty( $current_page ) &&
			( empty( $current_page ) || strpos( $current_page, 'greenmetrics' ) === false ) ) {
			return;
		}

		// We're on a GreenMetrics page - set flags
		$is_plugin_page    = true;
		$is_dashboard_page = false;

		// Check specifically if we're on the dashboard/stats page
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Simply checking screen ID for dashboard page detection
		if ( strpos( $screen->id, 'greenmetrics-dashboard' ) !== false ||
			( ! empty( $current_page ) && $current_page === 'greenmetrics' ) ) {
			$is_dashboard_page = true;
		}

		wp_enqueue_style(
			'greenmetrics-admin',
			GREENMETRICS_PLUGIN_URL . 'includes/admin/css/greenmetrics-admin.css',
			array( 'wp-color-picker' ),
			GREENMETRICS_VERSION,
			'all'
		);

		// Check if we're on the reports page
		$is_reports_page = false;
		if ( ! empty( $current_page ) && $current_page === 'greenmetrics_reports' ) {
			$is_reports_page = true;

			// Enqueue reports-specific styles
			wp_enqueue_style(
				'greenmetrics-reports',
				GREENMETRICS_PLUGIN_URL . 'includes/admin/css/greenmetrics-reports.css',
				array( 'greenmetrics-admin' ),
				GREENMETRICS_VERSION,
				'all'
			);
		}

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
		// Get current screen to determine which page we're on
		$screen = get_current_screen();

		// Early return if not on admin page or can't determine screen
		if ( ! $screen ) {
			return;
		}

		// Only load our scripts on GreenMetrics plugin pages
		// This includes our plugin settings pages and any page with greenmetrics in the ID
		$current_page = '';
		if ( isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Checking GET parameter only for conditional loading of scripts, no data modification.
			$current_page = sanitize_text_field( wp_unslash( $_GET['page'] ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Simply checking if on a plugin page for conditionally loading scripts
		if ( strpos( $screen->id, 'greenmetrics' ) === false &&
			empty( $current_page ) &&
			( empty( $current_page ) || strpos( $current_page, 'greenmetrics' ) === false ) ) {
			return;
		}

		// We're on a GreenMetrics page - set flags
		$is_plugin_page    = true;
		$is_dashboard_page = false;
		$is_reports_page   = false;
		$is_email_reporting_page = false;

		// Check specifically if we're on the dashboard/stats page
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Simply checking screen ID for dashboard page detection
		if ( strpos( $screen->id, 'greenmetrics-dashboard' ) !== false ||
			( ! empty( $current_page ) && $current_page === 'greenmetrics' ) ) {
			$is_dashboard_page = true;
		}

		// Check if we're on the reports page
		if ( ! empty( $current_page ) && $current_page === 'greenmetrics_reports' ) {
			$is_reports_page = true;
		}

		// Check if we're on the email reporting page
		if ( ! empty( $current_page ) && $current_page === 'greenmetrics_email_reporting' ) {
			$is_email_reporting_page = true;
		}

		// Always load WordPress dependencies on our pages
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_media(); // Add media uploader scripts

		// First load the error handler - needed for all other modules
		wp_enqueue_script(
			'greenmetrics-error-handler',
			GREENMETRICS_PLUGIN_URL . 'includes/admin/js/greenmetrics-admin-modules/error-handler.js',
			array( 'jquery' ),
			GREENMETRICS_VERSION,
			true
		);

		// Then create a common namespace and utility functions - always needed
		wp_enqueue_script(
			'greenmetrics-admin-utils',
			GREENMETRICS_PLUGIN_URL . 'includes/admin/js/greenmetrics-admin-modules/utils.js',
			array( 'jquery', 'greenmetrics-error-handler' ),
			GREENMETRICS_VERSION,
			true
		);

		// Load the configuration module
		wp_enqueue_script(
			'greenmetrics-admin-config',
			GREENMETRICS_PLUGIN_URL . 'includes/admin/js/greenmetrics-admin-modules/config.js',
			array( 'jquery', 'greenmetrics-admin-utils' ),
			GREENMETRICS_VERSION,
			true
		);

		// Load the API module
		wp_enqueue_script(
			'greenmetrics-admin-api',
			GREENMETRICS_PLUGIN_URL . 'includes/admin/js/greenmetrics-admin-modules/api.js',
			array( 'jquery', 'greenmetrics-admin-config', 'greenmetrics-error-handler' ),
			GREENMETRICS_VERSION,
			true
		);

		// Localize script with necessary data
		wp_localize_script(
			'greenmetrics-admin-utils',
			'greenmetricsAdmin',
			array(
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'greenmetrics_admin_nonce' ),
				'refreshMessage'    => __( 'Statistics refreshed successfully.', 'greenmetrics' ),
				'refreshError'      => __( 'Error refreshing statistics.', 'greenmetrics' ),
				'selectIconText'    => __( 'Select or Upload Icon', 'greenmetrics' ),
				'selectIconBtnText' => __( 'Use this Icon', 'greenmetrics' ),
				'customIconText'    => __( 'Custom Icon', 'greenmetrics' ),
				'rest_url'          => get_rest_url( null, 'greenmetrics/v1/' ),
				'rest_nonce'        => wp_create_nonce( 'wp_rest' ),
				'loadingText'       => __( 'Loading data...', 'greenmetrics' ),
				'noDataText'        => __( 'No data available for the selected period.', 'greenmetrics' ),
				'is_dashboard_page' => $is_dashboard_page,
				'is_reports_page'   => $is_reports_page,
				'is_email_reporting_page' => $is_email_reporting_page,
				'is_plugin_page'    => $is_plugin_page,
				'debug'             => defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG,
				'i18n'              => array(
					'sunday'        => __( 'Sunday', 'greenmetrics' ),
					'monday'        => __( 'Monday', 'greenmetrics' ),
					'tuesday'       => __( 'Tuesday', 'greenmetrics' ),
					'wednesday'     => __( 'Wednesday', 'greenmetrics' ),
					'thursday'      => __( 'Thursday', 'greenmetrics' ),
					'friday'        => __( 'Friday', 'greenmetrics' ),
					'saturday'      => __( 'Saturday', 'greenmetrics' ),
					'sending'       => __( 'Sending...', 'greenmetrics' ),
					'sendTestEmail' => __( 'Send Test Email', 'greenmetrics' ),
					'ajaxError'     => __( 'AJAX request failed. Please try again.', 'greenmetrics' ),
				),
			)
		);

		// Load core module - always needed on our pages
		wp_enqueue_script(
			'greenmetrics-admin-core',
			GREENMETRICS_PLUGIN_URL . 'includes/admin/js/greenmetrics-admin-modules/core.js',
			array( 'jquery', 'wp-color-picker', 'wp-util', 'greenmetrics-admin-utils', 'greenmetrics-admin-config', 'greenmetrics-admin-api' ),
			GREENMETRICS_VERSION,
			true
		);

		// Load settings handler module - needed for all settings pages
		wp_enqueue_script(
			'greenmetrics-admin-settings-handler',
			GREENMETRICS_PLUGIN_URL . 'includes/admin/js/greenmetrics-admin-modules/settings-handler.js',
			array( 'jquery', 'greenmetrics-admin-core', 'greenmetrics-error-handler' ),
			GREENMETRICS_VERSION,
			true
		);

		// Load preview module - only needed on plugin settings pages
		// (which we are always on if we got this far and it's not the dashboard)
		if ( ! $is_dashboard_page ) {
			wp_enqueue_script(
				'greenmetrics-admin-preview',
				GREENMETRICS_PLUGIN_URL . 'includes/admin/js/greenmetrics-admin-modules/preview.js',
				array( 'greenmetrics-admin-core', 'greenmetrics-admin-utils', 'greenmetrics-admin-config' ),
				GREENMETRICS_VERSION,
				true
			);
		}

		// Load Chart.js and Chart module - needed on dashboard/stats page and reports page
		if ( $is_dashboard_page || $is_reports_page ) {
			wp_enqueue_script(
				'chart-js',
				GREENMETRICS_PLUGIN_URL . 'includes/admin/js/chart.min.js',
				array(),
				GREENMETRICS_VERSION,
				true
			);

			wp_enqueue_script(
				'greenmetrics-admin-chart',
				GREENMETRICS_PLUGIN_URL . 'includes/admin/js/greenmetrics-admin-modules/chart.js',
				array( 'greenmetrics-admin-core', 'chart-js', 'greenmetrics-admin-utils', 'greenmetrics-admin-config', 'greenmetrics-admin-api' ),
				GREENMETRICS_VERSION,
				true
			);
		}

		// Load dashboard module - only needed on dashboard page
		if ( $is_dashboard_page ) {
			wp_enqueue_script(
				'greenmetrics-admin-dashboard',
				GREENMETRICS_PLUGIN_URL . 'includes/admin/js/greenmetrics-admin-modules/dashboard.js',
				array( 'greenmetrics-admin-core', 'greenmetrics-admin-utils', 'greenmetrics-admin-config', 'greenmetrics-admin-api' ),
				GREENMETRICS_VERSION,
				true
			);
		}

		// Load reports module - only needed on reports page
		if ( $is_reports_page ) {
			wp_enqueue_script(
				'greenmetrics-admin-reports-chart',
				GREENMETRICS_PLUGIN_URL . 'includes/admin/js/greenmetrics-admin-modules/reports-chart.js',
				array( 'greenmetrics-admin-core', 'chart-js', 'greenmetrics-admin-utils', 'greenmetrics-admin-config', 'greenmetrics-admin-api' ),
				GREENMETRICS_VERSION,
				true
			);

			wp_enqueue_script(
				'greenmetrics-admin-reports',
				GREENMETRICS_PLUGIN_URL . 'includes/admin/js/greenmetrics-admin-modules/reports.js',
				array( 'greenmetrics-admin-core', 'chart-js', 'greenmetrics-admin-utils', 'greenmetrics-admin-config', 'greenmetrics-admin-api', 'greenmetrics-admin-reports-chart' ),
				GREENMETRICS_VERSION,
				true
			);
		}

		// Main entry point file - always needed
		$main_dependencies = array( 'greenmetrics-admin-core' );

		// Add module dependencies based on what's loaded
		if ( ! $is_dashboard_page && ! $is_reports_page ) {
			$main_dependencies[] = 'greenmetrics-admin-preview';
		}

		if ( $is_dashboard_page ) {
			$main_dependencies[] = 'greenmetrics-admin-chart';
			$main_dependencies[] = 'greenmetrics-admin-dashboard';
		}

		if ( $is_reports_page ) {
			$main_dependencies[] = 'greenmetrics-admin-chart';
			$main_dependencies[] = 'greenmetrics-admin-reports-chart';
			$main_dependencies[] = 'greenmetrics-admin-reports';
		}

		if ( $is_email_reporting_page ) {
			wp_enqueue_script(
				'greenmetrics-admin-email-reporting',
				GREENMETRICS_PLUGIN_URL . 'includes/admin/js/greenmetrics-admin-modules/email-reporting.js',
				array( 'greenmetrics-admin-core', 'greenmetrics-admin-utils', 'greenmetrics-admin-config' ),
				GREENMETRICS_VERSION,
				true
			);

			$main_dependencies[] = 'greenmetrics-admin-email-reporting';
		}

		wp_enqueue_script(
			'greenmetrics-admin',
			GREENMETRICS_PLUGIN_URL . 'includes/admin/js/greenmetrics-admin.js',
			$main_dependencies,
			GREENMETRICS_VERSION,
			true
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
	 * Render data management page.
	 */
	public function render_data_management_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include GREENMETRICS_PLUGIN_DIR . 'includes/admin/partials/greenmetrics-data-management.php';
	}

	/**
	 * Render advanced reports page.
	 */
	public function render_reports_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include GREENMETRICS_PLUGIN_DIR . 'includes/admin/partials/greenmetrics-reports.php';
	}

	/**
	 * Render email reporting page.
	 */
	public function render_email_reporting_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include GREENMETRICS_PLUGIN_DIR . 'includes/admin/partials/greenmetrics-email-reporting.php';
	}

	/**
	 * Handle sending a test email.
	 */
	public function handle_send_test_email() {
		// Disable direct output to prevent HTML in JSON response
		ob_start();

		try {
			// Debug output
			error_log('Test Email AJAX Request: ' . json_encode($_POST));

			// Check nonce
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'greenmetrics_admin_nonce' ) ) {
				// Clean output buffer
				ob_end_clean();
				wp_send_json_error( array( 'message' => __( 'Security check failed.', 'greenmetrics' ) ) );
				return;
			}

			// Check permissions
			if ( ! current_user_can( 'manage_options' ) ) {
				// Clean output buffer
				ob_end_clean();
				wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions to perform this action.', 'greenmetrics' ) ) );
				return;
			}

			// Now that we've confirmed the AJAX response is working, let's actually send the email

			// Clean output buffer before proceeding
			$output = ob_get_clean();
			if (!empty($output)) {
				error_log('Captured output before email sending: ' . $output);
			}

			// Start a new output buffer for the email sending process
			ob_start();

			// Get the email reporter instance
			$email_reporter = \GreenMetrics\GreenMetrics_Email_Reporter::get_instance();

			// Send the test email
			try {
				// Log that we're about to send the test email
				error_log('Attempting to send test email...');

				$result = $email_reporter->send_test_email();

				// Log the result
				error_log('Test email result: ' . ($result === true ? 'Success' : 'Failed'));

				// If result is a WP_Error, get the error message
				if (is_wp_error($result)) {
					error_log('WP_Error: ' . $result->get_error_message());
				}
			} catch ( \Exception $e ) {
				// Log the exception
				error_log('Exception sending test email: ' . $e->getMessage());

				// Clean output buffer
				ob_end_clean();
				wp_send_json_error( array( 'message' => 'Error sending test email: ' . $e->getMessage() ) );
				return;
			}

			// Clean output buffer
			$output = ob_get_clean();
			if (!empty($output)) {
				error_log('Captured output during email sending: ' . $output);
			}

			if ( $result === true ) {
				wp_send_json_success( array( 'message' => __( 'Test email sent successfully! Please check your inbox.', 'greenmetrics' ) ) );
			} else {
				$error_message = is_wp_error($result) ? $result->get_error_message() : __( 'Failed to send test email. Please check your email settings.', 'greenmetrics' );
				wp_send_json_error( array( 'message' => $error_message ) );
			}
		} catch ( \Exception $e ) {
			// Catch any unexpected exceptions
			// Clean output buffer
			ob_end_clean();
			wp_send_json_error( array( 'message' => 'Unexpected error: ' . $e->getMessage() ) );
		}
	}

	/**
	 * Handle getting an email preview.
	 */
	public function handle_get_email_preview() {
		// Disable direct output to prevent HTML in JSON response
		ob_start();

		try {
			// Debug output
			error_log('Email Preview AJAX Request: ' . json_encode($_POST));

			// Check nonce
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'greenmetrics_admin_nonce' ) ) {
				// Clean output buffer
				ob_end_clean();
				wp_send_json_error( array( 'message' => __( 'Security check failed.', 'greenmetrics' ) ) );
				return;
			}

			// Check permissions
			if ( ! current_user_can( 'manage_options' ) ) {
				// Clean output buffer
				ob_end_clean();
				wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions to perform this action.', 'greenmetrics' ) ) );
				return;
			}

			// Get settings
			$settings = get_option( 'greenmetrics_settings', array() );

			// Override settings with the preview values
			$settings['email_reporting_include_stats'] = isset( $_POST['include_stats'] ) ? (int) $_POST['include_stats'] : 1;
			$settings['email_reporting_include_chart'] = isset( $_POST['include_chart'] ) ? (int) $_POST['include_chart'] : 1;
			$settings['email_reporting_header'] = isset( $_POST['header'] ) ? wp_kses_post( wp_unslash( $_POST['header'] ) ) : '';
			$settings['email_reporting_footer'] = isset( $_POST['footer'] ) ? wp_kses_post( wp_unslash( $_POST['footer'] ) ) : '';
			$settings['email_reporting_css'] = isset( $_POST['custom_css'] ) ? wp_strip_all_tags( wp_unslash( $_POST['custom_css'] ) ) : '';

			// Create a simple fallback content in case of error
			$fallback_content = '<!DOCTYPE html><html><head><title>Email Preview</title></head><body><h1>Simple Email Preview</h1><p>This is a simple fallback preview.</p></body></html>';

			// Get the email content
			try {
				// Try a simple test first
				$content = '<!DOCTYPE html><html><head><title>Email Preview Test</title></head><body><h1>Simple Email Preview Test</h1><p>This is a test preview to check if AJAX is working.</p></body></html>';

				// Only try to generate the real preview if the test works
				if (isset($_POST['full_preview']) && $_POST['full_preview'] == 1) {
					// Get the email reporter instance - only if we need it
					$email_reporter = \GreenMetrics\GreenMetrics_Email_Reporter::get_instance();

					$real_content = $email_reporter->generate_preview_email( $settings );
					if (!empty($real_content)) {
						$content = $real_content;
					}
				}
			} catch ( \Exception $e ) {
				error_log('Error generating preview: ' . $e->getMessage());
				// Clean output buffer
				ob_end_clean();
				wp_send_json_error( array( 'message' => 'Error generating preview: ' . $e->getMessage() ) );
				return;
			}

			// Get the subject
			$subject = isset( $settings['email_reporting_subject'] )
				? $settings['email_reporting_subject']
				: 'GreenMetrics Report for [site_name]';

			$subject = str_replace( '[site_name]', get_bloginfo( 'name' ), $subject );
			$subject = str_replace( '[date]', date_i18n( get_option( 'date_format' ) ), $subject );

			// Get the recipients
			$recipients = isset( $settings['email_reporting_recipients'] ) && ! empty( $settings['email_reporting_recipients'] )
				? $settings['email_reporting_recipients']
				: get_option( 'admin_email' );

			// Clean output buffer
			$output = ob_get_clean();
			if (!empty($output)) {
				error_log('Captured output before JSON response: ' . $output);
			}

			wp_send_json_success( array(
				'content'    => $content,
				'subject'    => $subject,
				'recipients' => $recipients,
			) );
		} catch ( \Exception $e ) {
			// Catch any unexpected exceptions
			// Clean output buffer
			ob_end_clean();
			wp_send_json_error( array( 'message' => 'Unexpected error: ' . $e->getMessage() ) );
		}
	}

	/**
	 * Handle running data management tasks.
	 */
	public function handle_run_data_management() {
		// Check nonce
		if ( ! isset( $_POST['greenmetrics_data_management_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['greenmetrics_data_management_nonce'] ), 'greenmetrics_run_data_management' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'greenmetrics' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'greenmetrics' ) );
		}

		$data_manager = \GreenMetrics\GreenMetrics_Data_Manager::get_instance();
		$settings = \GreenMetrics\GreenMetrics_Settings_Manager::get_instance()->get();
		$message = '';

		// Run aggregation if requested
		if ( isset( $_POST['run_aggregation'] ) && $_POST['run_aggregation'] ) {
			$aggregation_age = isset( $settings['aggregation_age'] ) ? intval( $settings['aggregation_age'] ) : 30;
			$aggregation_type = isset( $settings['aggregation_type'] ) ? $settings['aggregation_type'] : 'daily';

			$result = $data_manager->aggregate_old_data( $aggregation_age, $aggregation_type );

			if ( $result['error'] ) {
				$message = __( 'Error during data aggregation.', 'greenmetrics' );
				add_settings_error( 'greenmetrics_data_management', 'aggregation_error', $message, 'error' );
			} else {
				$message = sprintf(
					/* translators: %d: number of aggregated periods */
					_n( 'Data aggregation completed successfully. %d period was aggregated.', 'Data aggregation completed successfully. %d periods were aggregated.', $result['aggregated'], 'greenmetrics' ),
					$result['aggregated']
				);
				add_settings_error( 'greenmetrics_data_management', 'aggregation_success', $message, 'success' );
			}
		}

		// Run pruning if requested
		if ( isset( $_POST['run_pruning'] ) && $_POST['run_pruning'] ) {
			$retention_period = isset( $settings['retention_period'] ) ? intval( $settings['retention_period'] ) : 90;

			$result = $data_manager->prune_old_data( $retention_period );

			if ( $result['error'] ) {
				$message = __( 'Error during data pruning.', 'greenmetrics' );
				add_settings_error( 'greenmetrics_data_management', 'pruning_error', $message, 'error' );
			} else {
				$message = sprintf(
					/* translators: %d: number of pruned records */
					_n( 'Data pruning completed successfully. %d record was deleted.', 'Data pruning completed successfully. %d records were deleted.', $result['pruned'], 'greenmetrics' ),
					$result['pruned']
				);
				add_settings_error( 'greenmetrics_data_management', 'pruning_success', $message, 'success' );
			}
		}

		// Prepare redirect URL with appropriate parameters
		$redirect_url = admin_url( 'admin.php?page=greenmetrics_data_management' );
		$redirect_url = add_query_arg( 'data-management-updated', 'true', $redirect_url );

		// Add aggregation parameter if aggregation was run
		if ( isset( $_POST['run_aggregation'] ) && $_POST['run_aggregation'] ) {
			$redirect_url = add_query_arg( 'aggregation', 'true', $redirect_url );
		}

		// Add pruning parameter if pruning was run
		if ( isset( $_POST['run_pruning'] ) && $_POST['run_pruning'] ) {
			$redirect_url = add_query_arg( 'pruning', 'true', $redirect_url );
		}

		// Redirect to the data management page
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Handle refresh statistics form submission.
	 */
	public function handle_refresh_stats() {
		// Check if the form was submitted and the refresh_stats action was set
		if ( isset( $_POST['action'] ) && 'refresh_stats' === $_POST['action'] ) {
			// Verify nonce
			if ( isset( $_POST['greenmetrics_refresh_nonce'] ) &&
				wp_verify_nonce(
					sanitize_key( wp_unslash( $_POST['greenmetrics_refresh_nonce'] ) ),
					'greenmetrics_refresh_stats'
				)
			) {
				// Trigger manual cache refresh
				\GreenMetrics\GreenMetrics_Tracker::manual_cache_refresh();

				// Redirect back to the same page with a simple parameter
				$redirect_url = add_query_arg( 'stats-refreshed', 'true', remove_query_arg( 'settings-updated' ) );
				wp_safe_redirect( $redirect_url );
				exit;
			}
		}
	}

	/**
	 * Handle refresh statistics form submission from the data management page.
	 */
	public function handle_refresh_stats_redirect() {
		// Verify nonce
		if ( ! isset( $_POST['greenmetrics_refresh_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['greenmetrics_refresh_nonce'] ), 'greenmetrics_refresh_stats' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'greenmetrics' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'greenmetrics' ) );
		}

		// Trigger manual cache refresh
		\GreenMetrics\GreenMetrics_Tracker::manual_cache_refresh();

		// Add success message
		add_settings_error( 'greenmetrics_data_management', 'stats_refreshed', __( 'Statistics cache refreshed successfully!', 'greenmetrics' ), 'success' );

		// Redirect back to the data management page with stats-refreshed parameter
		wp_safe_redirect( add_query_arg( 'stats-refreshed', 'true', admin_url( 'admin.php?page=greenmetrics_data_management' ) ) );
		exit;
	}

	/**
	 * Handle AJAX request to get an icon.
	 */
	public function handle_get_icon() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) ||
			! wp_verify_nonce(
				sanitize_key( wp_unslash( $_POST['nonce'] ) ),
				'greenmetrics_admin_nonce'
			)
		) {
			wp_send_json_error( 'Security verification failed. Please refresh the page and try again.' );

			// Log the failed nonce verification
			if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
				greenmetrics_log(
					'AJAX get_icon: Nonce verification failed',
					array(
						'request' => $_POST,
					),
					'warning'
				);
			}
			return;
		}

		// Define valid icon types
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
		);

		// Get the icon type from the request with enhanced validation
		$icon_type = isset( $_POST['icon_type'] ) ? sanitize_text_field( wp_unslash( $_POST['icon_type'] ) ) : 'leaf';

		// Validate icon type
		if ( ! in_array( $icon_type, $valid_icons, true ) ) {
			// If invalid, use default icon
			$icon_type = 'leaf';

			// Log the invalid icon type
			if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
				greenmetrics_log(
					'AJAX get_icon: Invalid icon type',
					array(
						'requested_icon' => isset( $_POST['icon_type'] ) ? sanitize_text_field( wp_unslash( $_POST['icon_type'] ) ) : 'none',
						'using_default' => $icon_type,
					),
					'warning'
				);
			}
		}

		// Get the icon HTML
		$icon_html = \GreenMetrics\GreenMetrics_Icons::get_icon( $icon_type );

		// Return the icon HTML as a JSON response
		wp_send_json_success( $icon_html );
	}
}
