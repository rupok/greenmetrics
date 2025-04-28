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
				'nonce'            => wp_create_nonce( 'greenmetrics_get_icon' ),
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
				'iconName'               => 'leaf',
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

		// Get the selected icon's SVG content
		$iconName = $attributes['iconName'];
		$iconSvg = $this->get_icon_svg( $iconName );
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
					isset($attributes['useCustomIcon']) && $attributes['useCustomIcon'] && isset($attributes['customIconUrl']) && !empty($attributes['customIconUrl']) 
						? sprintf('<img src="%s" alt="Custom Icon" style="width:100%%;height:100%%;object-fit:contain;">', esc_url($attributes['customIconUrl']))
						: $attributes['icon_svg']
				) : '',
				$attributes['showText'] ? sprintf(
					'<span style="color:%1$s;font-size:%2$spx;font-family:%3$s;">%4$s</span>',
					esc_attr( $attributes['textColor'] ),
					esc_attr( $attributes['textFontSize'] ),
					esc_attr( isset($attributes['badgeFontFamily']) ? $attributes['badgeFontFamily'] : 'inherit' ),
					esc_html( $attributes['text'] )
				) : '',
				$attributes['showContent'] ? sprintf(
					'<div class="wp-block-greenmetrics-content" style="background-color:%1$s;color:%2$s;transition:all %3$sms ease-in-out;font-family:%4$s;">
						<h3 style="font-family:%4$s;">%5$s</h3>
						<style>
							#greenmetrics-metrics-%8$s .wp-block-greenmetrics-metric:hover {
								background-color: %9$s !important;
							}
						</style>
						<div id="greenmetrics-metrics-%8$s" class="wp-block-greenmetrics-metrics">%6$s</div>
						%7$s
					</div>',
					esc_attr( $attributes['contentBackgroundColor'] ),
					esc_attr( $attributes['contentTextColor'] ),
					esc_attr( $attributes['animationDuration'] ),
					esc_attr( isset($attributes['popoverContentFontFamily']) ? $attributes['popoverContentFontFamily'] : 'inherit' ),
					esc_html( $attributes['contentTitle'] ),
					implode(
						'',
						array_map(
							function ( $metric ) use ( $metrics, $attributes ) {
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
									'<div class="wp-block-greenmetrics-metric" style="background-color:%7$s;">
								<div class="metric-label" style="font-family:%3$s;font-size:%4$spx;">
									<span>%1$s</span>
									<span class="metric-value" style="font-family:%5$s;font-size:%6$spx;background:%8$s;padding:4px 8px;border-radius:4px;">%2$s</span>
								</div>
								</div>',
									esc_html( $label ),
									esc_html( $value ),
									esc_attr( isset($attributes['metricsListFontFamily']) ? $attributes['metricsListFontFamily'] : 'inherit' ),
									esc_attr( isset($attributes['metricsListFontSize']) ? $attributes['metricsListFontSize'] : '14' ),
									esc_attr( isset($attributes['metricsValueFontFamily']) ? $attributes['metricsValueFontFamily'] : 'inherit' ),
									esc_attr( isset($attributes['metricsValueFontSize']) ? $attributes['metricsValueFontSize'] : '14' ),
									esc_attr( isset($attributes['metricsListBgColor']) ? $attributes['metricsListBgColor'] : '#f8f9fa' ),
									esc_attr( isset($attributes['metricsValueBgColor']) ? $attributes['metricsValueBgColor'] : 'rgba(0, 0, 0, 0.04)' )
								);
							},
							$attributes['selectedMetrics']
						)
					),
					$attributes['customContent'] ? sprintf(
						'<div class="wp-block-greenmetrics-custom-content" style="font-family:%2$s;">%1$s</div>',
						wp_kses_post( $attributes['customContent'] ),
						esc_attr( isset($attributes['popoverContentFontFamily']) ? $attributes['popoverContentFontFamily'] : 'inherit' )
					) : '',
					uniqid('metrics-'),
					esc_attr( isset($attributes['metricsListHoverBgColor']) ? $attributes['metricsListHoverBgColor'] : '#f3f4f6' )
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
		// First try to get icon from the icons.php file
		$icons_file = GREENMETRICS_PLUGIN_DIR . 'public/js/blocks/badge/src/icons.php';
		if ( file_exists( $icons_file ) ) {
			include_once $icons_file;
			if ( isset( $icons ) && is_array( $icons ) ) {
				// Get the selected icon's SVG content
				$icon_name = $icon_name ?? 'leaf';
				foreach ( $icons as $icon ) {
					if ( $icon['id'] === $icon_name ) {
						return $icon['svg'];
					}
				}
				
				// Return first icon as fallback
				return $icons[0]['svg'] ?? null;
			}
		}
		
		// If icon isn't found in the file, use the GreenMetrics_Icons class
		return \GreenMetrics\GreenMetrics_Icons::get_icon( $icon_name );
	}

	/**
	 * Get the HTML for an icon
	 *
	 * @param string $icon_name Icon name
	 * @return string Icon HTML
	 */
	private function get_icon_html( $icon_name ) {
		// First try to get icon from the icons.php file
		$icons_file = GREENMETRICS_PLUGIN_DIR . 'public/js/blocks/badge/src/icons.php';
		if ( file_exists( $icons_file ) ) {
			include_once $icons_file;
			if ( isset( $icons ) && is_array( $icons ) ) {
				// Get the selected icon's SVG content
				$icon_name = $icon_name ?? 'leaf';
				foreach ( $icons as $icon ) {
					if ( $icon['id'] === $icon_name ) {
						return $icon['svg'];
					}
				}
				
				// Return first icon as fallback
				return $icons[0]['svg'] ?? '';
			}
		}
		
		// If icon isn't found in the file, use the GreenMetrics_Icons class
		return \GreenMetrics\GreenMetrics_Icons::get_icon( $icon_name );
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
		$icon_size = $settings_manager->get( 'badge_icon_size', '16px' );
		
		// Log settings for debugging
		if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
			greenmetrics_log( 'Badge display settings', array(
				'display_icon' => $display_icon,
				'icon_type' => $icon_type,
				'icon_size' => $icon_size,
				'icon_color' => $icon_color,
				'custom_icon' => $custom_icon
			));
		}
		
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
		$popover_metrics_list_bg_color = $settings_manager->get( 'popover_metrics_list_bg_color', '#f8f9fa' );
		$popover_metrics_list_hover_bg_color = $settings_manager->get( 'popover_metrics_list_hover_bg_color', '#f3f4f6' );
		$popover_content_font = $settings_manager->get( 'popover_content_font', 'inherit' );
		$popover_content_font_size = $settings_manager->get( 'popover_content_font_size', '16px' );
		$popover_metrics_font = $settings_manager->get( 'popover_metrics_font', 'inherit' );
		$popover_metrics_font_size = $settings_manager->get( 'popover_metrics_font_size', '14px' );
		$popover_metrics_label_font_size = $settings_manager->get( 'popover_metrics_label_font_size', '12px' );
		
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
		
		// Build inline styles for the badge and popover
		$button_style = 'background-color: ' . esc_attr( $background_color ) . '; color: ' . esc_attr( $text_color ) . ';';
		$popover_content_style = 'background-color: ' . esc_attr( $popover_bg_color ) . '; color: ' . esc_attr( $popover_text_color ) . '; font-family: ' . esc_attr( $popover_content_font ) . '; font-size: ' . esc_attr( $popover_content_font_size ) . ';';
		$popover_metrics_style = 'color: ' . esc_attr( $popover_metrics_color ) . '; font-family: ' . esc_attr( $popover_metrics_font ) . '; font-size: ' . esc_attr( $popover_metrics_font_size ) . '; background-color: ' . esc_attr( $popover_metrics_bg_color ) . ';';
		$popover_metrics_label_style = 'font-size: ' . esc_attr( $popover_metrics_label_font_size ) . ';';
		$popover_metrics_list_style = 'background-color: ' . esc_attr( $popover_metrics_list_bg_color ) . ';';
		
		// Prepare icon HTML
		$icon_html = '';
		if ( $display_icon && $icon_type ) {
			if ( $icon_type === 'custom' && $custom_icon ) {
				$icon_html = '<img src="' . esc_url( $custom_icon ) . '" alt="Icon" class="leaf-icon" style="width: ' . esc_attr( $icon_size ) . '; height: ' . esc_attr( $icon_size ) . '; fill: ' . esc_attr( $icon_color ) . ';">';
			} else {
				// Try to get the icon using GreenMetrics_Icons class directly
				$icon_svg = \GreenMetrics\GreenMetrics_Icons::get_icon( $icon_type );
				
				// If we couldn't get an icon, try again with the fallback
				if ( empty( $icon_svg ) || $icon_svg === \GreenMetrics\GreenMetrics_Icons::get_icon( 'leaf' ) ) {
					// Log the fallback for debugging
					if ( defined( 'GREENMETRICS_DEBUG' ) && GREENMETRICS_DEBUG ) {
						greenmetrics_log( 'Using fallback icon method for type', $icon_type );
					}
					$icon_svg = $this->get_icon_svg( $icon_type );
				}
				
				$icon_html = '<div class="icon-container" style="color: ' . esc_attr( $icon_color ) . '; display: flex; align-items: center; justify-content: center; width: ' . esc_attr( $icon_size ) . '; height: ' . esc_attr( $icon_size ) . ';">' . $icon_svg . '</div>';
			}
		}
		
		// Print the global badge HTML
		?>
		<div class="<?php echo esc_attr( implode( ' ', $global_classes ) ); ?>">
			<div class="greenmetrics-global-badge-wrapper">
				<style>
					.greenmetrics-global-badge-wrapper .greenmetrics-global-badge-content .greenmetrics-global-badge-metrics .greenmetrics-global-badge-metric:hover {
						background-color: <?php echo esc_attr( $popover_metrics_list_hover_bg_color ); ?> !important;
						transition: background-color 0.2s ease !important;
					}
					.greenmetrics-global-badge-button .icon-container svg {
						width: <?php echo esc_attr( $icon_size ); ?>;
						height: <?php echo esc_attr( $icon_size ); ?>;
						fill: currentColor;
						display: block;
					}
				</style>
				<div class="greenmetrics-global-badge-button <?php echo esc_attr( $size ); ?>" style="<?php echo esc_attr( $button_style ); ?>">
					<?php echo $icon_html; ?>
					<span><?php echo esc_html( $badge_text ); ?></span>
				</div>
				<div class="greenmetrics-global-badge-content" style="<?php echo $popover_content_style; ?>">
					<div class="greenmetrics-global-badge-title"><h3><?php echo esc_html($popover_title); ?></h3></div>
					
					<div class="greenmetrics-global-badge-metrics">
						<?php if (in_array('carbon_footprint', $popover_metrics)) : ?>
						<div class="greenmetrics-global-badge-metric" style="<?php echo $popover_metrics_list_style; ?>">
							<div class="greenmetrics-global-badge-metric-label" style="<?php echo $popover_metrics_label_style; ?>">
								<span><?php esc_html_e('Carbon Footprint', 'greenmetrics'); ?></span>
							</div>
							<div class="greenmetrics-global-badge-metric-value" style="<?php echo $popover_metrics_style; ?>"><?php echo esc_html($carbon_formatted); ?></div>
						</div>
						<?php endif; ?>
						
						<?php if (in_array('energy_consumption', $popover_metrics)) : ?>
						<div class="greenmetrics-global-badge-metric" style="<?php echo $popover_metrics_list_style; ?>">
							<div class="greenmetrics-global-badge-metric-label" style="<?php echo $popover_metrics_label_style; ?>">
								<span><?php esc_html_e('Energy Consumption', 'greenmetrics'); ?></span>
							</div>
							<div class="greenmetrics-global-badge-metric-value" style="<?php echo $popover_metrics_style; ?>"><?php echo esc_html($energy_formatted); ?></div>
						</div>
						<?php endif; ?>
						
						<?php if (in_array('data_transfer', $popover_metrics)) : ?>
						<div class="greenmetrics-global-badge-metric" style="<?php echo $popover_metrics_list_style; ?>">
							<div class="greenmetrics-global-badge-metric-label" style="<?php echo $popover_metrics_label_style; ?>">
								<span><?php esc_html_e('Data Transfer', 'greenmetrics'); ?></span>
							</div>
							<div class="greenmetrics-global-badge-metric-value" style="<?php echo $popover_metrics_style; ?>"><?php echo esc_html($data_formatted); ?></div>
						</div>
						<?php endif; ?>
						
						<?php if (in_array('total_views', $popover_metrics)) : ?>
						<div class="greenmetrics-global-badge-metric" style="<?php echo $popover_metrics_list_style; ?>">
							<div class="greenmetrics-global-badge-metric-label" style="<?php echo $popover_metrics_label_style; ?>">
								<span><?php esc_html_e('Page Views', 'greenmetrics'); ?></span>
							</div>
							<div class="greenmetrics-global-badge-metric-value" style="<?php echo $popover_metrics_style; ?>"><?php echo esc_html($views_formatted); ?></div>
						</div>
						<?php endif; ?>
						
						<?php if (in_array('requests', $popover_metrics)) : ?>
						<div class="greenmetrics-global-badge-metric" style="<?php echo $popover_metrics_list_style; ?>">
							<div class="greenmetrics-global-badge-metric-label" style="<?php echo $popover_metrics_label_style; ?>">
								<span><?php esc_html_e('HTTP Requests', 'greenmetrics'); ?></span>
							</div>
							<div class="greenmetrics-global-badge-metric-value" style="<?php echo $popover_metrics_style; ?>"><?php echo esc_html($requests_formatted); ?></div>
						</div>
						<?php endif; ?>
						
						<?php if (in_array('performance_score', $popover_metrics)) : ?>
						<div class="greenmetrics-global-badge-metric" style="<?php echo $popover_metrics_list_style; ?>">
							<div class="greenmetrics-global-badge-metric-label" style="<?php echo $popover_metrics_label_style; ?>">
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
