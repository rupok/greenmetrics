<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */

namespace GreenMetrics;

/**
 * The public-facing functionality of the plugin.
 */
class GreenMetrics_Public {
	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_shortcode( 'greenmetrics_badge', array( $this, 'render_badge_shortcode' ) );
		add_action( 'init', array( $this, 'register_blocks' ) );
		
		// Add global badge to footer if enabled in settings
		add_action( 'wp_footer', array( $this, 'display_global_badge' ) );

		// Forward AJAX tracking requests to the tracker
		// This is deprecated and will be removed in a future version
		add_action( 'wp_ajax_greenmetrics_tracking', array( $this, 'forward_tracking_request' ) );
		add_action( 'wp_ajax_nopriv_greenmetrics_tracking', array( $this, 'forward_tracking_request' ) );
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
		// Get settings
		$options          = get_option( 'greenmetrics_settings' );
		$tracking_enabled = isset( $options['tracking_enabled'] ) ? $options['tracking_enabled'] : 0;

		greenmetrics_log(
			'Enqueuing public scripts with settings',
			array(
				'tracking_enabled' => $tracking_enabled,
				'options'          => $options,
			)
		);

		// Get the current page ID
		$page_id = get_queried_object_id();
		if ( ! $page_id ) {
			$page_id = get_the_ID();
		}

		// Ensure we have a valid REST URL
		$rest_url = get_rest_url( null, 'greenmetrics/v1' );
		if ( ! $rest_url ) {
			greenmetrics_log( 'Failed to get REST URL', null, 'error' );
			return;
		}

		// Create nonce for REST API
		$rest_nonce = wp_create_nonce( 'wp_rest' );

		// Get plugin base URL
		$plugin_url = plugins_url( '', dirname( __DIR__ ) );

		// Enqueue public script
		wp_enqueue_script(
			'greenmetrics-public',
			$plugin_url . '/greenmetrics/public/js/greenmetrics-public.js',
			array( 'jquery' ),
			GREENMETRICS_VERSION,
			true
		);

		// Localize script with essential data - use consistent parameter names
		wp_localize_script(
			'greenmetrics-public',
			'greenmetricsPublic',
			array(
				'ajax_url'         => admin_url( 'admin-ajax.php' ), // Keep for backward compatibility
				'rest_url'         => $rest_url,
				'rest_nonce'       => $rest_nonce, // Use only one parameter name
				'tracking_enabled' => $tracking_enabled ? true : false,
				'page_id'          => $page_id,
			)
		);

		greenmetrics_log( 'Public scripts enqueued successfully with REST nonce', $rest_nonce );
	}

	/**
	 * Check if tracking is enabled.
	 *
	 * @return bool Whether tracking is enabled.
	 */
	private function is_tracking_enabled() {
		return GreenMetrics_Settings_Manager::get_instance()->is_enabled( 'tracking_enabled' );
	}

	/**
	 * Add REST URL to script localization
	 *
	 * @param array $data The existing data.
	 * @return array The data with REST URL added.
	 */
	public function add_rest_url( $data ) {
		$data['rest_url'] = get_rest_url( null, 'greenmetrics/v1' );

		if ( ! $data['rest_url'] ) {
			greenmetrics_log( 'Failed to get REST URL', null, 'error' );
		}

		return $data;
	}

	/**
	 * Render the badge shortcode
	 *
	 * @param array $atts Shortcode attributes
	 * @return string HTML output
	 */
	public function render_badge_shortcode( $atts ) {
		$settings_manager = GreenMetrics_Settings_Manager::get_instance();
		greenmetrics_log( 'Badge shortcode - Settings retrieved', $settings_manager->get() );

		// Parse shortcode attributes
		$parsed_atts = shortcode_atts(
			array(
				'position' => $settings_manager->get( 'badge_position', 'bottom-right' ),
				'theme'    => $settings_manager->get( 'badge_theme', 'light' ),
				'size'     => $settings_manager->get( 'badge_size', 'medium' ),
			),
			$atts
		);

		// Render badge without respect to global setting (always show for shortcodes)
		// This matches the block behavior - if someone manually adds the shortcode, they want to see it
		return $this->render_badge( $parsed_atts, false );
	}

	/**
	 * Register blocks
	 */
	public function register_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		// Register the badge block
		register_block_type(
			GREENMETRICS_PLUGIN_DIR . 'public/js/blocks/badge',
			array(
				'render_callback' => array( $this, 'render_badge_block' ),
			)
		);

		// Ensure styles are loaded on the frontend
		wp_enqueue_style(
			'greenmetrics-badge',
			GREENMETRICS_PLUGIN_URL . 'public/js/blocks/badge/build/style-index.css',
			array(),
			GREENMETRICS_VERSION
		);
	}

	/**
	 * Render the badge block
	 *
	 * @param array $attributes Block attributes
	 * @return string HTML output
	 */
	public function render_badge_block( $attributes ) {
		$settings_manager = GreenMetrics_Settings_Manager::get_instance();

		// IMPORTANT: For blocks, we ALWAYS display the badge regardless of the global enable_badge setting.
		// This is different from shortcodes which respect the global setting.
		// The logic is: if a user manually adds a block to their content, they want to see it
		// regardless of the global setting which controls automatic placement.
		greenmetrics_log(
			'Block rendering - ALWAYS ignoring enable_badge setting',
			array(
				'current_setting' => $settings_manager->get( 'enable_badge' ),
			)
		);

		// Set default values for attributes
		$attributes = wp_parse_args(
			$attributes,
			array(
				'text'                   => 'Eco-Friendly Site',
				'alignment'              => 'left',
				'backgroundColor'        => '#4CAF50',
				'textColor'              => '#ffffff',
				'iconColor'              => '#ffffff',
				'showIcon'               => true,
				'iconName'               => 'chart-bar',
				'iconSize'               => 20,
				'borderRadius'           => 4,
				'padding'                => 8,
				'showContent'            => true,
				'contentTitle'           => 'Environmental Impact',
				'selectedMetrics'        => array( 'carbon_footprint', 'energy_consumption' ),
				'customContent'          => '',
				'contentBackgroundColor' => '#ffffff',
				'contentTextColor'       => '#333333',
				'contentPadding'         => 15,
				'animationDuration'      => 300,
				'showText'               => true,
				'textFontSize'           => 14,
				'position'               => 'bottom-right',
				'theme'                  => 'light',
				'size'                   => 'medium',
			)
		);

		// Include and get icons
		$icons_file = GREENMETRICS_PLUGIN_DIR . 'public/js/blocks/badge/src/icons.php';
		if ( ! file_exists( $icons_file ) ) {
			error_log( 'GreenMetrics: Icons file not found at ' . $icons_file );
			return '';
		}
		include $icons_file;
		if ( ! isset( $icons ) || ! is_array( $icons ) ) {
			error_log( 'GreenMetrics: Icons array not properly defined' );
			return '';
		}

		// Get the selected icon's SVG content
		$iconName     = $attributes['iconName'];
		$selectedIcon = null;
		foreach ( $icons as $icon ) {
			if ( $icon['id'] === $iconName ) {
				$selectedIcon = $icon;
				break;
			}
		}
		$iconSvg = $selectedIcon ? $selectedIcon['svg'] : $icons[0]['svg'];
		$attributes['icon_svg'] = $iconSvg;

		// Render badge without respect to global setting (always show for blocks)
		return $this->render_badge( $attributes, false );
	}

	/**
	 * Core badge rendering function used by both shortcode and block
	 *
	 * @param array   $attributes Badge attributes
	 * @param boolean $respect_global_setting Whether to respect the global enable_badge setting
	 * @return string HTML output
	 */
	private function render_badge( $attributes, $respect_global_setting = true ) {
		$settings_manager = GreenMetrics_Settings_Manager::get_instance();
		
		// Check if badge is enabled if we need to respect global setting
		if ( $respect_global_setting && ! $settings_manager->is_enabled( 'enable_badge' ) ) {
			greenmetrics_log(
				'Badge display disabled by settings',
				array(
					'enable_badge' => $settings_manager->get( 'enable_badge' ),
					'value_type'   => gettype( $settings_manager->get( 'enable_badge' ) ),
				)
			);
			return '';
		}

		// Get metrics data
		$metrics = $this->get_metrics_data();
		
		// For the shortcode rendering
		if ( isset( $attributes['position'] ) && isset( $attributes['theme'] ) && isset( $attributes['size'] ) && !isset( $attributes['text'] ) ) {
			// Build classes
			$wrapper_classes = array( 'greenmetrics-badge-wrapper' );
			$badge_classes   = array( 'greenmetrics-badge' );

			if ( $attributes['position'] ) {
				$wrapper_classes[] = esc_attr( $attributes['position'] );
			}
			if ( $attributes['theme'] ) {
				$badge_classes[] = esc_attr( $attributes['theme'] );
			}
			if ( $attributes['size'] ) {
				$badge_classes[] = esc_attr( $attributes['size'] );
			}

			$wrapper_class = implode( ' ', $wrapper_classes );
			$badge_class   = implode( ' ', $badge_classes );

			// Format values with proper precision using formatting methods from the Calculator class
			$carbon_formatted   = GreenMetrics_Calculator::format_carbon_emissions( $metrics['carbon_footprint'] );
			$energy_formatted   = GreenMetrics_Calculator::format_energy_consumption( $metrics['energy_consumption'] );
			$data_formatted     = GreenMetrics_Calculator::format_data_transfer( $metrics['data_transfer'] );
			$views_formatted    = number_format( $metrics['total_views'] );
			$requests_formatted = number_format( $metrics['requests'] );
			$score_formatted    = number_format( $metrics['performance_score'], 2 ) . '%';

			greenmetrics_log(
				'Formatted metrics values',
				array(
					'carbon' => $carbon_formatted,
					'energy' => $energy_formatted,
					'data'   => $data_formatted,
				)
			);

			// Build HTML for shortcode rendering
			$html = sprintf(
				'<div class="%1$s">
					<div class="%2$s">
						<svg class="leaf-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
							<path d="M17,8C8,10,5.9,16.17,3.82,21.34L5.71,22l1-2.3A4.49,4.49,0,0,0,8,20C19,20,22,3,22,3,21,5,14,5.25,9,6.25S2,11.5,2,13.5a6.23,6.23,0,0,0,1.4,3.3L3,19l1.76,1.37A10.23,10.23,0,0,1,4,17C4,16,7,8,17,8Z"/>
						</svg>
						<span>Eco-Friendly Site</span>
					</div>
					<div class="greenmetrics-content">
						<h3>Environmental Impact</h3>
						<div class="greenmetrics-metrics">
							<div class="greenmetrics-metric">
								<div class="greenmetrics-metric-label">
									<span>Carbon Footprint</span>
								</div>
								<div class="greenmetrics-metric-value">%3$s</div>
							</div>
							<div class="greenmetrics-metric">
								<div class="greenmetrics-metric-label">
									<span>Energy Consumption</span>
								</div>
								<div class="greenmetrics-metric-value">%4$s</div>
							</div>
							<div class="greenmetrics-metric">
								<div class="greenmetrics-metric-label">
									<span>Data Transfer</span>
								</div>
								<div class="greenmetrics-metric-value">%5$s</div>
							</div>
							<div class="greenmetrics-metric">
								<div class="greenmetrics-metric-label">
									<span>Page Views</span>
								</div>
								<div class="greenmetrics-metric-value">%6$s</div>
							</div>
							<div class="greenmetrics-metric">
								<div class="greenmetrics-metric-label">
									<span>HTTP Requests</span>
								</div>
								<div class="greenmetrics-metric-value">%7$s</div>
							</div>
							<div class="greenmetrics-metric">
								<div class="greenmetrics-metric-label">
									<span>Performance Score</span>
								</div>
								<div class="greenmetrics-metric-value">%8$s</div>
							</div>
						</div>
					</div>
				</div>',
				esc_attr( $wrapper_class ),
				esc_attr( $badge_class ),
				esc_html( $carbon_formatted ),
				esc_html( $energy_formatted ),
				esc_html( $data_formatted ),
				esc_html( $views_formatted ),
				esc_html( $requests_formatted ),
				esc_html( $score_formatted )
			);
		} else {
			// This is for block rendering
			$html = sprintf(
				'<div class="wp-block-greenmetrics-badge">
					<div class="wp-block-greenmetrics-badge-wrapper %1$s">
						<div class="wp-block-greenmetrics-badge %2$s %3$s" style="background-color:%4$s;color:%5$s;padding:%6$spx;border-radius:%7$spx;font-size:%8$spx;text-align:%9$s;cursor:%10$s">
							%11$s
							%12$s
						</div>
						%13$s
					</div>
				</div>',
				esc_attr( $attributes['position'] ),
				esc_attr( $attributes['theme'] ),
				esc_attr( $attributes['size'] ),
				esc_attr( $attributes['backgroundColor'] ),
				esc_attr( $attributes['textColor'] ),
				esc_attr( $attributes['padding'] ),
				esc_attr( $attributes['borderRadius'] ),
				esc_attr( $attributes['textFontSize'] ),
				esc_attr( $attributes['alignment'] ),
				$attributes['showContent'] ? 'pointer' : 'default',
				$attributes['showIcon'] ? sprintf(
					'<div class="wp-block-greenmetrics-badge__icon" style="width:%1$spx;height:%1$spx;color:%2$s">%3$s</div>',
					esc_attr( $attributes['iconSize'] ),
					esc_attr( $attributes['iconColor'] ),
					$attributes['icon_svg']
				) : '',
				$attributes['showText'] ? sprintf(
					'<span style="color:%1$s;font-size:%2$spx">%3$s</span>',
					esc_attr( $attributes['textColor'] ),
					esc_attr( $attributes['textFontSize'] ),
					esc_html( $attributes['text'] )
				) : '',
				$attributes['showContent'] ? sprintf(
					'<div class="wp-block-greenmetrics-content" style="background-color:%1$s;color:%2$s;transition:all %3$sms ease-in-out">
						<h3>%4$s</h3>
						<div class="wp-block-greenmetrics-metrics">%5$s</div>
						%6$s
					</div>',
					esc_attr( $attributes['contentBackgroundColor'] ),
					esc_attr( $attributes['contentTextColor'] ),
					esc_attr( $attributes['animationDuration'] ),
					esc_html( $attributes['contentTitle'] ),
					implode(
						'',
						array_map(
							function ( $metric ) use ( $metrics ) {
								$label = '';
								$value = '';
								switch ( $metric ) {
									case 'carbon_footprint':
										$label = 'Carbon Footprint';
										$value = GreenMetrics_Calculator::format_carbon_emissions( $metrics['carbon_footprint'] );
										break;
									case 'energy_consumption':
										$label = 'Energy Consumption';
										$value = GreenMetrics_Calculator::format_energy_consumption( $metrics['energy_consumption'] );
										break;
									case 'data_transfer':
										$label = 'Data Transfer';
										$value = GreenMetrics_Calculator::format_data_transfer( $metrics['data_transfer'] );
										break;
									case 'views':
										$label = 'Page Views';
										$value = number_format( $metrics['total_views'] );
										break;
									case 'http_requests':
										$label = 'HTTP Requests';
										$value = number_format( $metrics['requests'] );
										break;
									case 'performance_score':
										$label = 'Performance Score';
										$value = number_format( $metrics['performance_score'], 2 ) . '%';
										break;
								}
								return sprintf(
									'<div class="wp-block-greenmetrics-metric">
								<div class="metric-label">
									<span>%1$s</span>
									<span class="metric-value">%2$s</span>
								</div>
								</div>',
									esc_html( $label ),
									esc_html( $value )
								);
							},
							$attributes['selectedMetrics']
						)
					),
					$attributes['customContent'] ? sprintf(
						'<div class="wp-block-greenmetrics-custom-content">%s</div>',
						wp_kses_post( $attributes['customContent'] )
					) : ''
				) : ''
			);
		}

		return $html;
	}

	/**
	 * Enqueue block editor assets
	 */
	public function enqueue_editor_assets() {
		$asset_file = include GREENMETRICS_PLUGIN_DIR . 'public/js/blocks/badge/build/index.asset.php';

		wp_enqueue_script(
			'greenmetrics-badge-editor',
			GREENMETRICS_PLUGIN_URL . 'public/js/blocks/badge/build/index.js',
			array_merge( $asset_file['dependencies'], array( 'wp-blocks', 'wp-element', 'wp-editor' ) ),
			$asset_file['version'],
			true
		);

		wp_enqueue_style(
			'greenmetrics-badge-editor',
			GREENMETRICS_PLUGIN_URL . 'public/js/blocks/badge/build/index.css',
			array( 'wp-edit-blocks' ),
			$asset_file['version']
		);

		// Create nonce for REST API
		$rest_nonce = wp_create_nonce( 'wp_rest' );
		$rest_url   = get_rest_url( null, 'greenmetrics/v1' );

		// Localize script for the block editor
		wp_localize_script(
			'greenmetrics-badge-editor',
			'greenmetricsTracking',
			array(
				'rest_nonce' => $rest_nonce,
				'rest_url'   => $rest_url,
				'ajax_url'   => admin_url( 'admin-ajax.php' ), // Keep for backward compatibility
				'nonce'      => $rest_nonce, // Keep for backward compatibility
				'action'     => 'greenmetrics_tracking', // Keep for backward compatibility
			)
		);
	}

	/**
	 * Forward tracking requests to the tracker class to avoid duplicate code
	 * This maintains backward compatibility with existing JavaScript files
	 *
	 * @deprecated 1.1.0 Use the REST API endpoint /greenmetrics/v1/track instead.
	 */
	public function forward_tracking_request() {
		greenmetrics_log( 'Forwarding tracking request to tracker class' );

		// Get the tracker instance
		$tracker = GreenMetrics_Tracker::get_instance();

		// Forward the request to the tracker's handler
		$tracker->handle_tracking_request_from_post();

		// The tracker will handle the response, so we don't need to do anything else
		exit;
	}

	/**
	 * Get metrics data for the current page
	 *
	 * @return array Metrics data
	 */
	private function get_metrics_data() {
		greenmetrics_log( 'Starting get_metrics_data' );

		// Get stats from tracker - all calculations are now done efficiently in SQL
		$tracker = GreenMetrics_Tracker::get_instance();
		$stats   = $tracker->get_stats();
		greenmetrics_log( 'Stats from tracker', $stats );

		// Extract and validate the data needed for display
		// No need to recalculate these values as they're already properly calculated in the tracker
		$result = array(
			'data_transfer'      => isset( $stats['total_data_transfer'] ) ? floatval( $stats['total_data_transfer'] ) : 0,
			'load_time'          => isset( $stats['avg_load_time'] ) ? floatval( $stats['avg_load_time'] ) : 0,
			'requests'           => isset( $stats['total_requests'] ) ? intval( $stats['total_requests'] ) : 0,
			'total_views'        => isset( $stats['total_views'] ) ? intval( $stats['total_views'] ) : 0,
			'energy_consumption' => isset( $stats['total_energy_consumption'] ) ? floatval( $stats['total_energy_consumption'] ) : 0,
			'carbon_footprint'   => isset( $stats['total_carbon_footprint'] ) ? floatval( $stats['total_carbon_footprint'] ) : 0,
			'performance_score'  => isset( $stats['avg_performance_score'] ) ? floatval( $stats['avg_performance_score'] ) : 100,
		);

		// Log metrics data summary
		$metrics_summary = array(
			'data_transfer'      => $result['data_transfer'],
			'energy_consumption' => $result['energy_consumption'],
			'carbon_footprint'   => $result['carbon_footprint'],
			'performance_score'  => $result['performance_score'],
		);
		greenmetrics_log( 'Metrics data summary', $metrics_summary );

		return $result;
	}

	/**
	 * Get SVG content for an icon
	 *
	 * @param string $icon_name Icon name
	 * @return string|null SVG content or null if not found
	 */
	private function get_icon_svg( $icon_name ) {
		// Include and get icons
		$icons_file = GREENMETRICS_PLUGIN_DIR . 'public/js/blocks/badge/src/icons.php';
		if ( ! file_exists( $icons_file ) ) {
			greenmetrics_log( 'Icons file not found', $icons_file, 'error' );
			return null;
		}
		include $icons_file;
		if ( ! isset( $icons ) || ! is_array( $icons ) ) {
			greenmetrics_log( 'Icons array not properly defined', null, 'error' );
			return null;
		}

		// Get the selected icon's SVG content
		$icon_name = $icon_name ?? 'chart-bar';
		foreach ( $icons as $icon ) {
			if ( $icon['id'] === $icon_name ) {
				return $icon['svg'];
			}
		}

		// Return first icon as fallback
		return $icons[0]['svg'] ?? null;
	}

	private function get_icon_html( $icon_name ) {
		// Include and get icons
		$icons_file = GREENMETRICS_PLUGIN_DIR . 'public/js/blocks/badge/src/icons.php';
		if ( ! file_exists( $icons_file ) ) {
			greenmetrics_log( 'Icons file not found', $icons_file, 'error' );
			return '';
		}
		include $icons_file;
		if ( ! isset( $icons ) || ! is_array( $icons ) ) {
			greenmetrics_log( 'Icons array not properly defined', null, 'error' );
			return '';
		}

		// Get the selected icon's SVG content
		$icon_name = $icon_name ?? 'chart-bar';
		foreach ( $icons as $icon ) {
			if ( $icon['id'] === $icon_name ) {
				return $icon['svg'];
			}
		}

		// Return first icon as fallback
		return $icons[0]['svg'] ?? '';
	}

	/**
	 * Inject the tracking script into the page footer.
	 */
	public function inject_tracking_script() {
		if ( ! $this->is_tracking_enabled() ) {
			greenmetrics_log( 'Tracking disabled, not injecting script' );
			return;
		}

		$settings   = GreenMetrics_Settings_Manager::get_instance()->get();
		$plugin_url = plugins_url( '', dirname( __DIR__ ) );

		greenmetrics_log( 'Injecting tracking script', get_the_ID() );
		?>
		<script>
			window.greenmetricsTracking = {
				enabled: true,
				carbonIntensity: <?php echo esc_js( $settings['carbon_intensity'] ); ?>,
				energyPerByte: <?php echo esc_js( $settings['energy_per_byte'] ); ?>,
				rest_nonce: '<?php echo wp_create_nonce( 'wp_rest' ); ?>',
				rest_url: '<?php echo esc_js( get_rest_url( null, 'greenmetrics/v1' ) ); ?>',
				page_id: <?php echo get_the_ID(); ?>
			};
		</script>
		<script src="<?php echo esc_url( $plugin_url . '/greenmetrics/public/js/greenmetrics-tracking.js' ); ?>"></script>
		<?php
	}

	/**
	 * Display a global badge in the footer if enabled in settings.
	 * This is different from shortcodes and blocks which require manual placement.
	 */
	public function display_global_badge() {
		$settings_manager = GreenMetrics_Settings_Manager::get_instance();
		
		// Only display if the badge is enabled in global settings
		if ( ! $settings_manager->is_enabled( 'enable_badge' ) ) {
			return;
		}
		
		// Only display on frontend pages, not admin
		if ( is_admin() ) {
			return;
		}
		
		greenmetrics_log( 'Displaying global badge in footer with settings' );
		
		// Get all display settings
		$settings = $settings_manager->get();
		$position = $settings_manager->get( 'badge_position', 'bottom-right' );
		$size = $settings_manager->get( 'badge_size', 'medium' );
		$badge_text = $settings_manager->get( 'badge_text', 'Eco-Friendly Site' );
		$background_color = $settings_manager->get( 'badge_background_color', '#4CAF50' );
		$text_color = $settings_manager->get( 'badge_text_color', '#ffffff' );
		$icon_color = $settings_manager->get( 'badge_icon_color', '#ffffff' );
		$display_icon = $settings_manager->get( 'display_icon', 1 );
		$icon_type = $settings_manager->get( 'badge_icon_type', 'leaf' );
		$custom_icon = $settings_manager->get( 'badge_custom_icon', '' );
		
		// Get popover content settings
		$popover_title = $settings_manager->get( 'popover_title', 'Environmental Impact' );
		$popover_metrics = $settings_manager->get( 'popover_metrics', array(
			'carbon_footprint',
			'energy_consumption',
			'data_transfer',
			'total_views',
			'requests',
			'performance_score'
		));
		$popover_custom_content = $settings_manager->get( 'popover_custom_content', '' );
		$popover_bg_color = $settings_manager->get( 'popover_bg_color', '#ffffff' );
		$popover_text_color = $settings_manager->get( 'popover_text_color', '#333333' );
		$popover_metrics_color = $settings_manager->get( 'popover_metrics_color', '#4CAF50' );
		$popover_metrics_bg_color = $settings_manager->get( 'popover_metrics_bg_color', 'rgba(0, 0, 0, 0.05)' );
		$popover_content_font = $settings_manager->get( 'popover_content_font', 'inherit' );
		$popover_content_font_size = $settings_manager->get( 'popover_content_font_size', '16px' );
		$popover_metrics_font = $settings_manager->get( 'popover_metrics_font', 'inherit' );
		$popover_metrics_font_size = $settings_manager->get( 'popover_metrics_font_size', '14px' );
		
		// Get metrics data for the popover
		$metrics = $this->get_metrics_data();
		
		// Format metrics data
		$carbon_formatted = GreenMetrics_Calculator::format_carbon_emissions( $metrics['carbon_footprint'] );
		$energy_formatted = GreenMetrics_Calculator::format_energy_consumption( $metrics['energy_consumption'] );
		$data_formatted = GreenMetrics_Calculator::format_data_transfer( $metrics['data_transfer'] );
		$views_formatted = number_format( $metrics['total_views'] );
		$requests_formatted = number_format( $metrics['requests'] );
		$score_formatted = number_format( $metrics['performance_score'], 2 ) . '%';
		
		// Build classes for the global badge
		$global_classes = array( 'greenmetrics-global-badge' );
		if ( $position ) {
			$global_classes[] = esc_attr( $position );
		}
		
		$badge_classes = array( 'greenmetrics-global-badge-button' );
		if ( $size ) {
			$badge_classes[] = esc_attr( $size );
		}
		
		$global_class = implode( ' ', $global_classes );
		$badge_class = implode( ' ', $badge_classes );
		
		// Prepare inline styles for colors
		$button_style = sprintf(
			'background-color: %s; color: %s;',
			esc_attr($background_color),
			esc_attr($text_color)
		);
		
		$icon_style = sprintf('color: %s;', esc_attr($icon_color));
		
		// Prepare popover content styles
		$popover_content_style = sprintf(
			'background-color: %s; color: %s; font-family: %s; font-size: %s;',
			esc_attr($popover_bg_color),
			esc_attr($popover_text_color),
			esc_attr($popover_content_font),
			esc_attr($popover_content_font_size)
		);
		
		$popover_metrics_style = sprintf(
			'color: %s; font-family: %s; font-size: %s; background-color: %s;',
			esc_attr($popover_metrics_color),
			esc_attr($popover_metrics_font),
			esc_attr($popover_metrics_font_size),
			esc_attr($popover_metrics_bg_color)
		);
		
		// Output HTML with all dashboard settings applied
		?>
		<div class="<?php echo esc_attr( $global_class ); ?>">
			<div class="greenmetrics-global-badge-wrapper">
				<div class="<?php echo esc_attr( $badge_class ); ?>" style="<?php echo $button_style; ?>">
					<?php if ( $display_icon ) : ?>
						<div class="icon-container" style="<?php echo $icon_style; ?>">
							<?php
							switch ( $icon_type ) {
								case 'leaf':
									echo '<svg class="leaf-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 8C8 10 5.9 16.17 3.82 21.34l1.89.66.95-2.3c.48.17 1.02.3 1.58.3C17 20 22 13.46 22 6c0-.55-.06-1.09-.14-1.62C20.18 4.15 18.66 4 17 4V2c1.67 0 3.35.12 5 .34V4c-1.67-.22-3.33-.34-5-.34v2zM2 6c0 7.46 5 14 14.5 14 .56 0 1.1-.13 1.58-.3l.95 2.3 1.89-.66C18.1 16.17 16 10 7 8c0 0-5 0-5 0z"/></svg>';
									break;
								case 'tree':
									echo '<svg class="leaf-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22c4.97 0 9-4.03 9-9-4.97 0-9 4.03-9 9zm2.44-9.43h-.44v2h.44c2.32 0 2.49 3.23 2.49 3.23 1.52-1.84 2.63-4.43 1.73-7C17.56 8.37 15.5 7 15.5 7S14.8 9.1 13 9.42v.36c1.32-.18 2.44.11 2.44.11s-1.22 1.91-1 3.68z"/><path d="M12.28 10h-.56v2h.56c2.33 0 2.51 3.45 2.51 3.45 1.55-1.89 2.67-4.63 1.77-7.24-.51-1.46-2.18-3.02-2.18-3.02s-.99 2.18-2.1 2.48V8c1.34-.2 2.55.07 2.55.07s-1.34 1.66-1.14 3.44z"/><path d="M12.63 5.33c-.28.47-1.04 1.68-2 1.87V8.8c1.35-.19 2.97.31 2.97.31S12.69 10.3 12.22 12h.33v-2h-.16c.06-.32.2-.65.44-.97.19.38.39.75.58 1.09l.66-.42c-.18-.28-.33-.57-.46-.85 0 0 .99.17 2.22.5-.27-.5-2.47-4.02-3.2-4.02z"/><path d="M10.45 12h-.43v8.17c.34-.14.66-.34.95-.55L10.45 12zm1.66 4.62c.1.21.19.42.27.63-.16-.19-.31-.39-.46-.57.07-.02.12-.04.19-.06zm1.14-4.62L12.1 17.1c.45-.11.88-.29 1.29-.51l-.14-4.59z"/><path d="M9.3 14.13l-.24 7.14c.24.11.48.19.73.26l-.42-7.8c-.02.14-.05.27-.07.4zm3.33 1.7c-.04-.04-.08-.09-.12-.14.03.05.06.09.09.13.01 0 .02.01.03.01zm-.83-3.83l-.32 7.46c.29.05.58.08.88.08.12 0 .24-.01.36-.02L12 12l-.2 0z"/></svg>';
									break;
								case 'globe':
									echo '<svg class="leaf-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>';
									break;
								case 'recycle':
									echo '<svg class="leaf-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M5.77 7.15L7.2 4.78l1.03-1.71c.39-.65 1.33-.65 1.72 0l1.48 2.46-1.23 2.06-1 1.34-2.43-4.78zm15.95 5.82l-1.6-2.66-3.46 2L18.87 16H21v2l-3.87-7.03zM16 21h1.5l2.05-3.42-3.46-2-1.09 1.84L16 21zm-3.24-3.71l-1.03-1.71-1.43 2.43-2.43 4.78 1.6 2.66 3.46-2 1.03-1.71-1.43-2.45zM13.42 8.5l-1.48-2.46c-.39-.65-1.33-.65-1.72 0L9.22 7.15l-1 1.34 2.43 4.78 1.6-2.66 1.17-2.11zM10.5 14.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>';
									break;
								case 'custom':
									if ( $custom_icon ) {
										echo '<img src="' . esc_url( $custom_icon ) . '" alt="Custom Icon" class="leaf-icon" style="width: 20px; height: 20px;">';
									} else {
										echo '<svg class="leaf-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 8C8 10 5.9 16.17 3.82 21.34l1.89.66.95-2.3c.48.17 1.02.3 1.58.3C17 20 22 13.46 22 6c0-.55-.06-1.09-.14-1.62C20.18 4.15 18.66 4 17 4V2c1.67 0 3.35.12 5 .34V4c-1.67-.22-3.33-.34-5-.34v2zM2 6c0 7.46 5 14 14.5 14 .56 0 1.1-.13 1.58-.3l.95 2.3 1.89-.66C18.1 16.17 16 10 7 8c0 0-5 0-5 0z"/></svg>';
									}
									break;
								default:
									echo '<svg class="leaf-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 8C8 10 5.9 16.17 3.82 21.34l1.89.66.95-2.3c.48.17 1.02.3 1.58.3C17 20 22 13.46 22 6c0-.55-.06-1.09-.14-1.62C20.18 4.15 18.66 4 17 4V2c1.67 0 3.35.12 5 .34V4c-1.67-.22-3.33-.34-5-.34v2zM2 6c0 7.46 5 14 14.5 14 .56 0 1.1-.13 1.58-.3l.95 2.3 1.89-.66C18.1 16.17 16 10 7 8c0 0-5 0-5 0z"/></svg>';
							}
							?>
						</div>
					<?php endif; ?>
					<span><?php echo esc_html($badge_text); ?></span>
				</div>
				<div class="greenmetrics-global-badge-content" style="<?php echo $popover_content_style; ?>">
					<div class="greenmetrics-global-badge-title"><h3><?php echo esc_html($popover_title); ?></h3></div>
					
					<div class="greenmetrics-global-badge-metrics">
						<?php if (in_array('carbon_footprint', $popover_metrics)) : ?>
						<div class="greenmetrics-global-badge-metric">
							<div class="greenmetrics-global-badge-metric-label">
								<span><?php esc_html_e('Carbon Footprint', 'greenmetrics'); ?></span>
							</div>
							<div class="greenmetrics-global-badge-metric-value" style="<?php echo $popover_metrics_style; ?>"><?php echo esc_html($carbon_formatted); ?></div>
						</div>
						<?php endif; ?>
						
						<?php if (in_array('energy_consumption', $popover_metrics)) : ?>
						<div class="greenmetrics-global-badge-metric">
							<div class="greenmetrics-global-badge-metric-label">
								<span><?php esc_html_e('Energy Consumption', 'greenmetrics'); ?></span>
							</div>
							<div class="greenmetrics-global-badge-metric-value" style="<?php echo $popover_metrics_style; ?>"><?php echo esc_html($energy_formatted); ?></div>
						</div>
						<?php endif; ?>
						
						<?php if (in_array('data_transfer', $popover_metrics)) : ?>
						<div class="greenmetrics-global-badge-metric">
							<div class="greenmetrics-global-badge-metric-label">
								<span><?php esc_html_e('Data Transfer', 'greenmetrics'); ?></span>
							</div>
							<div class="greenmetrics-global-badge-metric-value" style="<?php echo $popover_metrics_style; ?>"><?php echo esc_html($data_formatted); ?></div>
						</div>
						<?php endif; ?>
						
						<?php if (in_array('total_views', $popover_metrics)) : ?>
						<div class="greenmetrics-global-badge-metric">
							<div class="greenmetrics-global-badge-metric-label">
								<span><?php esc_html_e('Page Views', 'greenmetrics'); ?></span>
							</div>
							<div class="greenmetrics-global-badge-metric-value" style="<?php echo $popover_metrics_style; ?>"><?php echo esc_html($views_formatted); ?></div>
						</div>
						<?php endif; ?>
						
						<?php if (in_array('requests', $popover_metrics)) : ?>
						<div class="greenmetrics-global-badge-metric">
							<div class="greenmetrics-global-badge-metric-label">
								<span><?php esc_html_e('HTTP Requests', 'greenmetrics'); ?></span>
							</div>
							<div class="greenmetrics-global-badge-metric-value" style="<?php echo $popover_metrics_style; ?>"><?php echo esc_html($requests_formatted); ?></div>
						</div>
						<?php endif; ?>
						
						<?php if (in_array('performance_score', $popover_metrics)) : ?>
						<div class="greenmetrics-global-badge-metric">
							<div class="greenmetrics-global-badge-metric-label">
								<span><?php esc_html_e('Performance Score', 'greenmetrics'); ?></span>
							</div>
							<div class="greenmetrics-global-badge-metric-value" style="<?php echo $popover_metrics_style; ?>"><?php echo esc_html($score_formatted); ?></div>
						</div>
						<?php endif; ?>
					</div>
					
					<?php if (!empty($popover_custom_content)) : ?>
						<div class="greenmetrics-global-badge-custom-content" style="margin-top: 15px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,0.1);"><?php echo wp_kses_post($popover_custom_content); ?></div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}
}
