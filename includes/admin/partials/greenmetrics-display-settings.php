<?php
/**
 * Display settings admin template.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get settings
$settings = get_option(
	'greenmetrics_settings',
	array(
		'enable_badge'                        => 0,
		'display_icon'                        => 1,
		'badge_position'                      => 'bottom-right',
		'badge_size'                          => 'medium',
		'badge_text'                          => 'Eco-Friendly Site',
		'badge_icon_type'                     => 'leaf',
		'badge_custom_icon'                   => '',
		'badge_background_color'              => '#4CAF50',
		'badge_text_color'                    => '#ffffff',
		'badge_icon_color'                    => '#ffffff',
		'badge_icon_size'                     => '16px',
		'badge_theme'                         => 'light',
		'popover_title'                       => 'Environmental Impact',
		'popover_bg_color'                    => '#ffffff',
		'popover_text_color'                  => '#333333',
		'popover_metrics_color'               => '#4CAF50',
		'popover_metrics_bg_color'            => 'rgba(0, 0, 0, 0.05)',
		'popover_metrics_list_bg_color'       => '#f8f9fa',
		'popover_metrics_list_hover_bg_color' => '#f3f4f6',
		'popover_content_font'                => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif',
		'popover_content_font_size'           => '16px',
		'popover_metrics_font'                => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif',
		'popover_metrics_font_size'           => '15px',
		'popover_metrics_label_font_size'     => '14px',
		'popover_custom_content'              => '',
		'popover_metrics'                     => array(
			'carbon_footprint',
			'energy_consumption',
			'data_transfer',
			'total_views',
			'requests',
			'performance_score',
		),
	)
);
?>

<div class="wrap">
	<div class="greenmetrics-admin-container">
		<div class="greenmetrics-admin-header">
			<div class="header-content">
				<img src="<?php echo esc_url( GREENMETRICS_PLUGIN_URL . 'includes/admin/img/greenmetrics-icon.png' ); ?>" alt="<?php esc_attr_e( 'GreenMetrics Icon', 'greenmetrics' ); ?>" />
				<h1><?php esc_html_e( 'GreenMetrics - Display Settings', 'greenmetrics' ); ?></h1>
			</div>

			<span class="version">
			<?php
			/* translators: %s: Plugin version number */
			echo esc_html( sprintf( __( 'GreenMetrics v%s', 'greenmetrics' ), GREENMETRICS_VERSION ) );
			?>
			</span>
		</div>

		<!-- Tab Navigation -->
		<div class="greenmetrics-tabs-nav">
			<ul class="greenmetrics-tabs-list">
				<li class="greenmetrics-tab-item active" data-tab="badge">
					<span class="dashicons dashicons-visibility"></span>
					<?php esc_html_e( 'Badge Settings', 'greenmetrics' ); ?>
				</li>
				<li class="greenmetrics-tab-item" data-tab="popover">
					<span class="dashicons dashicons-editor-table"></span>
					<?php esc_html_e( 'Popover Settings', 'greenmetrics' ); ?>
				</li>
				<li class="greenmetrics-tab-item" data-tab="instructions">
					<span class="dashicons dashicons-info"></span>
					<?php esc_html_e( 'Usage Instructions', 'greenmetrics' ); ?>
				</li>
			</ul>
		</div>

		<div class="greenmetrics-admin-content-wrapper">
			<!-- Left Column: Settings Form -->
			<div class="greenmetrics-admin-settings-column">
				<form method="post" action="options.php" id="greenmetrics-display-settings-form">
					<?php
					settings_fields( 'greenmetrics_settings' );

					// Get admin instance if not already available
					if ( ! isset( $this ) || ! method_exists( $this, 'render_badge_field' ) ) {
						global $greenmetrics_admin;
						$admin = $greenmetrics_admin;
					} else {
						$admin = $this;
					}
					?>

					<!-- Tab Content -->
					<div class="greenmetrics-tabs-content">
						<!-- Badge Settings Tab -->
						<div class="greenmetrics-tab-content active" id="tab-badge">
							<!-- Badge Display -->
							<div class="greenmetrics-admin-card settings-card badge-display">
								<h3 class="settings-card-header">
									<span class="dashicons dashicons-visibility card-icon"></span>
									<?php esc_html_e( 'Badge Display', 'greenmetrics' ); ?>
								</h3>
								<div class="settings-card-content">
									<div class="form-field">
										<label for="enable_badge"><?php esc_html_e( 'Display Badge', 'greenmetrics' ); ?></label>
										<?php $admin->render_badge_field(); ?>
									</div>

									<div class="form-field">
										<label for="badge_position"><?php esc_html_e( 'Badge Position', 'greenmetrics' ); ?></label>
										<?php $admin->render_badge_position_field(); ?>
									</div>

									<div class="form-field">
										<label for="badge_size"><?php esc_html_e( 'Badge Size', 'greenmetrics' ); ?></label>
										<?php $admin->render_badge_size_field(); ?>
									</div>
								</div>
							</div>

							<!-- Badge Appearance -->
							<div class="greenmetrics-admin-card settings-card badge-appearance">
								<h3 class="settings-card-header">
									<span class="dashicons dashicons-art card-icon"></span>
									<?php esc_html_e( 'Badge Appearance', 'greenmetrics' ); ?>
								</h3>
								<div class="settings-card-content">
									<div class="form-field">
										<label for="badge_text"><?php esc_html_e( 'Badge Text', 'greenmetrics' ); ?></label>
										<?php $admin->render_badge_text_field(); ?>
									</div>

									<div class="form-field">
										<label for="badge_background_color"><?php esc_html_e( 'Background Color', 'greenmetrics' ); ?></label>
										<?php $admin->render_badge_background_color_field(); ?>
									</div>

									<div class="form-field">
										<label for="badge_text_color"><?php esc_html_e( 'Text Color', 'greenmetrics' ); ?></label>
										<?php $admin->render_badge_text_color_field(); ?>
									</div>

									<div class="form-field">
										<label for="display_icon"><?php esc_html_e( 'Display Icon', 'greenmetrics' ); ?></label>
										<?php $admin->render_display_icon_field(); ?>
									</div>

									<div class="form-field icon-settings">
										<label for="badge_icon_type"><?php esc_html_e( 'Choose Icon', 'greenmetrics' ); ?></label>
										<?php $admin->render_badge_icon_type_field(); ?>
									</div>

									<div class="form-field custom-icon-field">
										<label for="badge_custom_icon"><?php esc_html_e( 'Custom Icon', 'greenmetrics' ); ?></label>
										<?php $admin->render_badge_custom_icon_field(); ?>
									</div>

									<div class="form-field icon-settings">
										<label for="badge_icon_color"><?php esc_html_e( 'Icon Color', 'greenmetrics' ); ?></label>
										<?php $admin->render_badge_icon_color_field(); ?>
									</div>

									<div class="form-field icon-settings">
										<label for="badge_icon_size"><?php esc_html_e( 'Icon Size', 'greenmetrics' ); ?></label>
										<?php $admin->render_badge_icon_size_field(); ?>
									</div>
								</div>
							</div>
						</div>

						<!-- Popover Settings Tab -->
						<div class="greenmetrics-tab-content" id="tab-popover">
							<!-- Popover Content -->
							<div class="greenmetrics-admin-card settings-card popover-content">
								<h3 class="settings-card-header">
									<span class="dashicons dashicons-editor-table card-icon"></span>
									<?php esc_html_e( 'Popover Content', 'greenmetrics' ); ?>
								</h3>
								<div class="settings-card-content">
									<div class="form-field">
										<label for="popover_title"><?php esc_html_e( 'Content Title', 'greenmetrics' ); ?></label>
										<?php $admin->render_popover_title_field(); ?>
									</div>

									<div class="form-field">
										<label for="popover_metrics"><?php esc_html_e( 'Metrics to Display', 'greenmetrics' ); ?></label>
										<?php $admin->render_popover_metrics_field(); ?>
									</div>

									<div class="form-field">
										<label for="popover_custom_content"><?php esc_html_e( 'Custom Content', 'greenmetrics' ); ?></label>
										<?php $admin->render_popover_custom_content_field(); ?>
									</div>
								</div>
							</div>

							<!-- Popover Appearance -->
							<div class="greenmetrics-admin-card settings-card popover-appearance">
								<h3 class="settings-card-header">
									<span class="dashicons dashicons-admin-appearance card-icon"></span>
									<?php esc_html_e( 'Popover Appearance', 'greenmetrics' ); ?>
								</h3>
								<div class="settings-card-content">
									<div class="form-field">
										<label for="popover_bg_color"><?php esc_html_e( 'Content Background Color', 'greenmetrics' ); ?></label>
										<?php $admin->render_popover_bg_color_field(); ?>
									</div>

									<div class="form-field">
										<label for="popover_text_color"><?php esc_html_e( 'Content Text Color', 'greenmetrics' ); ?></label>
										<?php $admin->render_popover_text_color_field(); ?>
									</div>

									<div class="form-field">
										<label for="popover_metrics_color"><?php esc_html_e( 'Metrics Text Color', 'greenmetrics' ); ?></label>
										<?php $admin->render_popover_metrics_color_field(); ?>
									</div>

									<div class="form-field">
										<label for="popover_metrics_bg_color"><?php esc_html_e( 'Metrics Background Color', 'greenmetrics' ); ?></label>
										<?php $admin->render_popover_metrics_bg_color_field(); ?>
									</div>

									<div class="form-field">
										<label for="popover_metrics_list_bg_color"><?php esc_html_e( 'Metrics List Background', 'greenmetrics' ); ?></label>
										<?php $admin->render_popover_metrics_list_bg_color_field(); ?>
									</div>

									<div class="form-field">
										<label for="popover_metrics_list_hover_bg_color"><?php esc_html_e( 'Metrics List Hover Background', 'greenmetrics' ); ?></label>
										<?php $admin->render_popover_metrics_list_hover_bg_color_field(); ?>
									</div>

									<div class="form-field">
										<label for="popover_content_font"><?php esc_html_e( 'Content Font Family', 'greenmetrics' ); ?></label>
										<?php $admin->render_popover_content_font_field(); ?>
									</div>

									<div class="form-field">
										<label for="popover_content_font_size_number"><?php esc_html_e( 'Content Font Size', 'greenmetrics' ); ?></label>
										<?php $admin->render_popover_content_font_size_field(); ?>
									</div>

									<div class="form-field">
										<label for="popover_metrics_font"><?php esc_html_e( 'Metrics Font Family', 'greenmetrics' ); ?></label>
										<?php $admin->render_popover_metrics_font_field(); ?>
									</div>

									<div class="form-field">
										<label for="popover_metrics_font_size_number"><?php esc_html_e( 'Metrics Font Size', 'greenmetrics' ); ?></label>
										<?php $admin->render_popover_metrics_font_size_field(); ?>
									</div>

									<div class="form-field">
										<label for="popover_metrics_label_font_size_number"><?php esc_html_e( 'Metrics Label Font Size', 'greenmetrics' ); ?></label>
										<?php $admin->render_popover_metrics_label_font_size_field(); ?>
									</div>
								</div>
							</div>
						</div>

						<!-- Instructions Tab -->
						<div class="greenmetrics-tab-content" id="tab-instructions">
							<div class="greenmetrics-admin-card">
								<h2><?php esc_html_e( 'Usage Instructions', 'greenmetrics' ); ?></h2>

								<div class="guide-card">
									<h3><?php esc_html_e( 'Automatic Badge Display', 'greenmetrics' ); ?></h3>
									<p><?php esc_html_e( 'When enabled in the settings, the badge will automatically appear on all pages of your site in the position you select.', 'greenmetrics' ); ?></p>
								</div>

								<div class="guide-card">
									<h3><?php esc_html_e( 'Using Shortcode', 'greenmetrics' ); ?></h3>
									<p><?php esc_html_e( 'You can also add the badge to specific locations using the shortcode:', 'greenmetrics' ); ?></p>
									<div class="code-block">
										<code>[greenmetrics_badge]</code>
									</div>

									<p><?php esc_html_e( 'The shortcode can include custom attributes to override the default settings:', 'greenmetrics' ); ?></p>
									<ul class="attributes-list">
										<li><code>position="top-left|top-right|bottom-left|bottom-right"</code></li>
										<li><code>size="small|medium|large"</code></li>
										<li><code>text="Your custom badge text"</code></li>
										<li><code>background_color="#hexcolor"</code></li>
										<li><code>text_color="#hexcolor"</code></li>
										<li><code>icon_color="#hexcolor"</code></li>
									</ul>

									<p><?php esc_html_e( 'Example with custom attributes:', 'greenmetrics' ); ?></p>
									<div class="code-block">
										<code>[greenmetrics_badge position="top-right" size="large" text="Green Website"]</code>
									</div>
								</div>

								<div class="guide-card">
									<h3><?php esc_html_e( 'Using Block Editor', 'greenmetrics' ); ?></h3>
									<p><?php esc_html_e( 'Add the GreenMetrics badge using the Block Editor in your page or post:', 'greenmetrics' ); ?></p>
									<ol>
										<li><?php esc_html_e( 'Edit a page or post using the Block Editor', 'greenmetrics' ); ?></li>
										<li><?php esc_html_e( 'Click the "+" button to add a new block', 'greenmetrics' ); ?></li>
										<li><?php esc_html_e( 'Search for "GreenMetrics"', 'greenmetrics' ); ?></li>
										<li><?php esc_html_e( 'Select the "GreenMetrics Badge" block', 'greenmetrics' ); ?></li>
										<li><?php esc_html_e( 'Customize the badge appearance using the block settings sidebar', 'greenmetrics' ); ?></li>
									</ol>

									<div class="tip-box">
										<p><strong><?php esc_html_e( 'Tip:', 'greenmetrics' ); ?></strong> <?php esc_html_e( 'The Block Editor provides a visual way to customize the badge with the same options available in the shortcode.', 'greenmetrics' ); ?></p>
									</div>
								</div>
							</div>
						</div>
					</div>

					<?php submit_button(); ?>
				</form>
			</div>

			<!-- Right Column: Preview -->
			<div class="greenmetrics-admin-preview-column">
				<div class="greenmetrics-admin-preview-sticky">
					<!-- Live Preview Section -->
					<div class="greenmetrics-admin-card badge-preview">
						<h2><?php esc_html_e( 'Preview', 'greenmetrics' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Preview how your badge and popover content will appear on your website:', 'greenmetrics' ); ?></p>

						<!-- Badge Preview -->
						<div class="preview-section">
							<h3 style="padding: 10px 0; text-align: center;"><?php esc_html_e( 'Badge', 'greenmetrics' ); ?></h3>
							<div class="badge-preview-panel">
								<div id="badge-preview-container" style="position: absolute;" class="<?php echo esc_attr( isset( $settings['badge_position'] ) ? $settings['badge_position'] : 'bottom-right' ); ?>">
									<div class="greenmetrics-badge <?php echo esc_attr( isset( $settings['badge_size'] ) ? $settings['badge_size'] : 'medium' ); ?>" style="
										background-color: <?php echo esc_attr( isset( $settings['badge_background_color'] ) ? $settings['badge_background_color'] : '#4CAF50' ); ?>;
										color: <?php echo esc_attr( isset( $settings['badge_text_color'] ) ? $settings['badge_text_color'] : '#ffffff' ); ?>;
									">
										<?php if ( isset( $settings['display_icon'] ) && $settings['display_icon'] ) : ?>
											<div class="icon-container" style="color: <?php echo esc_attr( isset( $settings['badge_icon_color'] ) ? $settings['badge_icon_color'] : '#ffffff' ); ?>;">
												<?php
												$icon_type   = isset( $settings['badge_icon_type'] ) ? $settings['badge_icon_type'] : 'leaf';
												$custom_icon = isset( $settings['badge_custom_icon'] ) ? $settings['badge_custom_icon'] : '';
												$icon_size   = isset( $settings['badge_icon_size'] ) ? $settings['badge_icon_size'] : '16px';

												if ( $icon_type === 'custom' && ! empty( $custom_icon ) ) {
													echo '<img src="' . esc_url( $custom_icon ) . '" alt="' . esc_attr__( 'Custom Icon', 'greenmetrics' ) . '" style="width: ' . esc_attr( $icon_size ) . '; height: ' . esc_attr( $icon_size ) . ';">';
												} else {
													// Get the icon from GreenMetrics_Icons class
													$icon_html = \GreenMetrics\GreenMetrics_Icons::get_icon( $icon_type );
													echo '<div style="width: ' . esc_attr( $icon_size ) . '; height: ' . esc_attr( $icon_size ) . ';">' . wp_kses_post( $icon_html ) . '</div>';
												}
												?>
											</div>
										<?php endif; ?>
										<span><?php echo esc_html( isset( $settings['badge_text'] ) ? $settings['badge_text'] : __( 'Eco-Friendly Site', 'greenmetrics' ) ); ?></span>
									</div>
								</div>
							</div>
						</div>

						<!-- Popover Content Preview -->
						<div class="preview-section">
							<h3 style="padding: 10px 0; text-align: center;"><?php esc_html_e( 'Popover', 'greenmetrics' ); ?></h3>
							<div class="popover-preview-panel">
								<div id="popover-preview-container" style="
									margin: 20px auto;
									min-width: 300px;
									max-width: 300px;
									padding: 24px;
									border-radius: 4px;
									box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
									background-color: <?php echo isset( $settings['popover_bg_color'] ) ? esc_attr( $settings['popover_bg_color'] ) : '#ffffff'; ?>;
									color: <?php echo isset( $settings['popover_text_color'] ) ? esc_attr( $settings['popover_text_color'] ) : '#333333'; ?>;
									font-family: <?php echo isset( $settings['popover_content_font'] ) ? esc_attr( $settings['popover_content_font'] ) : 'inherit'; ?>;
									font-size: <?php echo isset( $settings['popover_content_font_size'] ) ? esc_attr( $settings['popover_content_font_size'] ) : '16px'; ?>;
								">
									<h3 style="margin: 0 0 16px; font-size: 16px; font-weight: 600; color: <?php echo isset( $settings['popover_text_color'] ) ? esc_attr( $settings['popover_text_color'] ) : '#333333'; ?>;">
										<?php echo isset( $settings['popover_title'] ) ? esc_html( $settings['popover_title'] ) : esc_html__( 'Environmental Impact', 'greenmetrics' ); ?>
									</h3>

									<div class="greenmetrics-global-badge-metrics" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
										<?php
										// Sample metrics data for preview
										$metrics_data = array(
											'carbon_footprint' => array(
												'label' => __( 'Carbon Footprint', 'greenmetrics' ),
												'value' => '0.024g CO2',
											),
											'energy_consumption' => array(
												'label' => __( 'Energy Consumption', 'greenmetrics' ),
												'value' => '0.32 Wh',
											),
											'data_transfer' => array(
												'label' => __( 'Data Transfer', 'greenmetrics' ),
												'value' => '1.24 MB',
											),
											'total_views' => array(
												'label' => __( 'Page Views', 'greenmetrics' ),
												'value' => '1,542',
											),
											'requests'    => array(
												'label' => __( 'HTTP Requests', 'greenmetrics' ),
												'value' => '24',
											),
											'performance_score' => array(
												'label' => __( 'Performance Score', 'greenmetrics' ),
												'value' => '92.5%',
											),
										);

										$popover_metrics = isset( $settings['popover_metrics'] ) ? $settings['popover_metrics'] : array(
											'carbon_footprint',
											'energy_consumption',
											'data_transfer',
											'total_views',
											'requests',
											'performance_score',
										);

										// Show selected metrics
										foreach ( $popover_metrics as $metric_key ) {
											if ( isset( $metrics_data[ $metric_key ] ) ) {
												?>
												<div class="greenmetrics-global-badge-metric" data-metric="<?php echo esc_attr( $metric_key ); ?>" style="display: flex; flex-direction: row; justify-content: space-between; align-items: center; gap: 16px; padding: 10px; background-color: <?php echo isset( $settings['popover_metrics_list_bg_color'] ) ? esc_attr( $settings['popover_metrics_list_bg_color'] ) : 'transparent'; ?>; border-radius: 12px; transition: all 0.2s ease;">
													<div class="greenmetrics-global-badge-metric-label" style="color: #666; font-size: 15px;">
														<span><?php echo esc_html( $metrics_data[ $metric_key ]['label'] ); ?></span>
													</div>
													<div class="greenmetrics-global-badge-metric-value" style="font-weight: 500; color: #4CAF50; font-size: 15px; background: rgba(0, 0, 0, 0.04); padding: 4px 8px; border-radius: 4px;">
														<?php echo esc_html( $metrics_data[ $metric_key ]['value'] ); ?>
													</div>
												</div>
												<?php
											}
										}
										?>
									</div>

									<?php if ( isset( $settings['popover_custom_content'] ) && ! empty( $settings['popover_custom_content'] ) ) : ?>
										<div class="greenmetrics-global-badge-custom-content" style="margin-top: 15px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,0.1);">
											<?php echo wp_kses_post( $settings['popover_custom_content'] ); ?>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>


	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// Define ajaxurl if not already defined (WordPress admin variable)
	if (typeof ajaxurl === 'undefined') {
		var ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
	}

	// Add localized strings for JavaScript
	const customIconText = "<?php echo esc_js( __( 'Custom Icon', 'greenmetrics' ) ); ?>";

	// Tab functionality
	$('.greenmetrics-tab-item').on('click', function() {
		const tabId = $(this).data('tab');

		// Update active tab
		$('.greenmetrics-tab-item').removeClass('active');
		$(this).addClass('active');

		// Show selected tab content
		$('.greenmetrics-tab-content').removeClass('active');
		$('#tab-' + tabId).addClass('active');

		// Store the active tab in localStorage
		localStorage.setItem('greenmetrics_display_settings_active_tab', tabId);
	});

	// Restore active tab from localStorage if available
	const activeTab = localStorage.getItem('greenmetrics_display_settings_active_tab');
	if (activeTab) {
		$('.greenmetrics-tab-item[data-tab="' + activeTab + '"]').trigger('click');
	}

	// Handle form submission
	$('#greenmetrics-display-settings-form').on('submit', function() {
		// Store the form data in localStorage before submitting
		localStorage.setItem('greenmetrics_display_settings_submitted', 'true');
	});

	// Check if we need to show a notice after form submission
	if (localStorage.getItem('greenmetrics_display_settings_submitted') === 'true') {
		// Display a success notice
		if (typeof GreenMetricsErrorHandler !== 'undefined' &&
			typeof GreenMetricsErrorHandler.displayAdminNotice === 'function') {
			GreenMetricsErrorHandler.displayAdminNotice(
				'Display settings saved successfully!',
				'success',
				true
			);
		}

		// Clear the flag
		localStorage.removeItem('greenmetrics_display_settings_submitted');
	}

	// Update badge and popover preview when settings change
	$('#enable_badge, #badge_position, #badge_size, #badge_text, #badge_background_color, #badge_text_color, ' +
		'#display_icon, #badge_icon_type, #badge_icon_color, #badge_icon_size, #badge_custom_icon, ' +
		'#popover_title, #popover_custom_content, #popover_bg_color, #popover_text_color, #popover_metrics_color, ' +
		'#popover_metrics_bg_color, #popover_content_font, #popover_metrics_font, #popover_metrics_list_bg_color, ' +
		'#popover_content_font_size_number, #popover_metrics_font_size_number, #popover_metrics_label_font_size_number, ' +
		'#popover_metrics_list_hover_bg_color')
	.on('change input', function() {
		updatePreview();
	});

	// Toggle icon-related fields based on Display Icon checkbox
	$('#display_icon').on('change', function() {
		const isChecked = $(this).is(':checked');
		// Toggle visibility of icon-related settings
		if (isChecked) {
			$('.form-field.icon-settings').show();
			// Show custom icon field only if "custom" is selected
			if ($('#badge_icon_type').val() === 'custom') {
				$('.form-field.custom-icon-field').addClass('visible');
			}
		} else {
			$('.form-field.icon-settings').hide();
			$('.form-field.custom-icon-field').removeClass('visible');
		}
		updatePreview();
	});

	// Toggle custom icon field based on icon type selection
	$('#badge_icon_type').on('change', function() {
		if ($(this).val() === 'custom') {
			$('.form-field.custom-icon-field').addClass('visible');
		} else {
			$('.form-field.custom-icon-field').removeClass('visible');
		}
		updatePreview();
	});

	// Handle font size number input changes
	$('#popover_content_font_size_number, #popover_metrics_font_size_number, #popover_metrics_label_font_size_number').on('change input', function() {
		// Update hidden field value
		var targetId = $(this).attr('id').replace('_number', '');
		$('#' + targetId).val($(this).val() + 'px');
		updatePreview();
	});

	// Listen for checkbox changes in metrics
	$('input[name="greenmetrics_settings[popover_metrics][]"]').on('change', function() {
		updatePreview();
	});

	// Initialize font size input fields
	function initFontSizeFields() {
		// Set the number input value from the hidden field
		$('#popover_content_font_size_number').val(parseInt($('#popover_content_font_size').val()));
		$('#popover_metrics_font_size_number').val(parseInt($('#popover_metrics_font_size').val()));
		$('#popover_metrics_label_font_size_number').val(parseInt($('#popover_metrics_label_font_size').val()));
	}

	// Function to update the preview
	function updatePreview() {
		const badgeText = $('#badge_text').val();
		const backgroundColor = $('#badge_background_color').val();
		const textColor = $('#badge_text_color').val();
		const displayIcon = $('#display_icon').is(':checked');
		const iconType = $('#badge_icon_type').val();
		const iconColor = $('#badge_icon_color').val();
		const iconSize = $('#badge_icon_size').val();
		const customIcon = $('#badge_custom_icon').val();
		const badgePosition = $('#badge_position').val();
		const badgeSize = $('#badge_size').val();

		// Update badge position
		$('#badge-preview-container').attr('class', badgePosition);

		// Update badge size
		$('.greenmetrics-badge').attr('class', 'greenmetrics-badge ' + badgeSize);

		// Update the badge text and colors
		$('.greenmetrics-badge span').text(badgeText);
		$('.greenmetrics-badge').css({
			'background-color': backgroundColor,
			'color': textColor
		});

		// Update icon
		const $iconContainer = $('.icon-container');
		if (displayIcon) {
			$iconContainer.show();
			$iconContainer.css('color', iconColor);

			if (iconType === 'custom' && customIcon) {
				// For custom icons, use the uploaded image
				$iconContainer.html('<img src="' + customIcon + '" alt="' + customIconText + '" style="width: ' + iconSize + '; height: ' + iconSize + ';">');
			} else {
				// For predefined icons, get them from the server
				getIconSvg(iconType, function(svgContent) {
					// Make sure SVG uses currentColor for proper color inheritance
					if (!svgContent.includes('fill="currentColor"')) {
						svgContent = svgContent.replace(/<svg/, '<svg fill="currentColor"');
					}
					$iconContainer.html('<div style="width: ' + iconSize + '; height: ' + iconSize + ';">' + svgContent + '</div>');
				});
			}
		} else {
			$iconContainer.hide();
		}

		// Get popover settings
		const popoverTitle = $('#popover_title').val();
		const popoverBgColor = $('#popover_bg_color').val();
		const popoverTextColor = $('#popover_text_color').val();
		const popoverMetricsColor = $('#popover_metrics_color').val();
		const popoverMetricsBgColor = $('#popover_metrics_bg_color').val();
		const popoverContentFont = $('#popover_content_font').val();
		const popoverContentFontSize = $('#popover_content_font_size').val();
		const popoverMetricsFont = $('#popover_metrics_font').val();
		const popoverMetricsFontSize = $('#popover_metrics_font_size').val();
		const popoverMetricsLabelFontSize = $('#popover_metrics_label_font_size').val();
		const popoverMetricsListBgColor = $('#popover_metrics_list_bg_color').val();
		const popoverMetricsListHoverBgColor = $('#popover_metrics_list_hover_bg_color').val();
		const popoverCustomContent = $('#popover_custom_content').val();

		// Update popover title
		$('#popover-preview-container h3').text(popoverTitle);

		// Update popover container styling
		$('#popover-preview-container').css({
			'background-color': popoverBgColor,
			'color': popoverTextColor,
			'font-family': popoverContentFont,
			'font-size': popoverContentFontSize
		});

		// Update metric values styling
		$('.greenmetrics-global-badge-metric-value').css({
			'color': popoverMetricsColor,
			'font-family': popoverMetricsFont,
			'font-size': popoverMetricsFontSize,
			'background': popoverMetricsBgColor
		});

		// Update metric labels styling
		$('.greenmetrics-global-badge-metric-label').css({
			'font-size': popoverMetricsLabelFontSize
		});

		// Update metric list item styling
		$('.greenmetrics-global-badge-metric').css({
			'background-color': popoverMetricsListBgColor
		});

		// Get selected metrics
		const selectedMetrics = [];
		$('input[name="greenmetrics_settings[popover_metrics][]"]:checked').each(function() {
			selectedMetrics.push($(this).val());
		});

		// Show/hide metrics based on selection
		$('.greenmetrics-global-badge-metric').each(function() {
			const metricKey = $(this).data('metric');
			$(this).toggle(selectedMetrics.includes(metricKey));
		});

		// Apply hover styles
		// Add hover style dynamically for better preview
		const styleId = 'greenmetrics-preview-hover-style';
		if ($('#' + styleId).length === 0) {
			$('head').append('<style id="' + styleId + '"></style>');
		}
		$('#' + styleId).html('.greenmetrics-global-badge-metric:hover { background-color: ' + popoverMetricsListHoverBgColor + ' !important; }');

		// Update popover custom content
		if (popoverCustomContent) {
			if ($('.greenmetrics-global-badge-custom-content').length === 0) {
				$('#popover-preview-container').append('<div class="greenmetrics-global-badge-custom-content" style="margin-top: 15px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,0.1);"></div>');
			}
			$('.greenmetrics-global-badge-custom-content').html(popoverCustomContent);
		} else {
			$('.greenmetrics-global-badge-custom-content').remove();
		}
	}

	// Initialize
	initFontSizeFields();

	// Initialize color pickers properly
	$('.greenmetrics-color-picker').wpColorPicker({
		change: function(event, ui) {
			// Trigger change event after color is picked
			setTimeout(function() {
				$(event.target).val(ui.color.toString()).trigger('change');
				updatePreview();
			}, 100);
		},
		clear: function(event) {
			setTimeout(function() {
				updatePreview();
			}, 100);
		}
	});

	// Function to get icon SVG using the backend GreenMetrics_Icons class
	function getIconSvg(iconType, callback) {
		// Call our endpoint to get the SVG content
		jQuery.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'greenmetrics_get_icon',
				icon_type: iconType,
				nonce: greenmetricsAdmin.nonce
			},
			success: function(response) {
				if (response.success && response.data) {
					callback(response.data);
				} else {
					console.error('Failed to get icon SVG:', response);
					callback(''); // Return empty string on error
				}
			},
			error: function(error) {
				console.error('AJAX error when getting icon:', error);
				callback(''); // Return empty string on error
			}
		});
	}

	// Set initial state of icon fields based on Display Icon checkbox
	$('#display_icon').trigger('change');

	// Then update the preview
	updatePreview();
});
</script>

<style>
/* Tab Styles */
.greenmetrics-tabs-nav {
	margin-bottom: 20px;
}

.greenmetrics-tabs-list {
	display: flex;
	list-style: none;
	margin: 0;
	padding: 0;
	border-bottom: 1px solid #ccc;
}

.greenmetrics-tab-item {
	padding: 12px 20px;
	margin: 0;
	cursor: pointer;
	font-weight: 500;
	color: #555;
	background-color: #f8f8f8;
	border: 1px solid #ccc;
	border-bottom: none;
	margin-right: 5px;
	border-radius: 4px 4px 0 0;
	display: flex;
	align-items: center;
}

.greenmetrics-tab-item .dashicons {
	margin-right: 8px;
	vertical-align: middle;
}

.greenmetrics-tab-item:hover {
	background-color: #f0f0f0;
}

.greenmetrics-tab-item.active {
	background-color: #fff;
	color: #333;
	border-bottom: 1px solid #fff;
	margin-bottom: -1px;
}

.greenmetrics-tab-content {
	display: none;
}

.greenmetrics-tab-content.active {
	display: block;
}

/* Preview Styles */
.preview-container {
	border-radius: 4px;
}

#badge-preview-container {
	padding: 15px;
}

#badge-preview-container.top-left {
	top: 0;
	left: 0;
}

#badge-preview-container.top-right {
	top: 0;
	right: 0;
}

#badge-preview-container.bottom-left {
	bottom: 0;
	left: 0;
}

#badge-preview-container.bottom-right {
	bottom: 0;
	right: 0;
}

.greenmetrics-badge {
	display: inline-flex;
	align-items: center;
	padding: 8px 12px;
	border-radius: 4px;
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
	box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.greenmetrics-badge.small {
	font-size: 12px;
	padding: 4px 8px;
}

.greenmetrics-badge.medium {
	font-size: 14px;
	padding: 8px 16px;
}

.greenmetrics-badge.large {
	font-size: 16px;
	padding: 12px 24px;
}

.greenmetrics-badge .icon-container {
	display: inline-flex;
	margin-right: 6px;
}

.icon-option svg {
	width: 36px;
	height: 36px;
}

.greenmetrics-admin-card {
	background: #fff;
	border-radius: 4px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.1);
	padding: 20px;
	margin-bottom: 20px;
}

.greenmetrics-global-badge-metrics {
	margin-top: 10px;
}

/* Improved styling for the metrics selection checkboxes */
.metrics-checkboxes {
	display: grid;
	grid-template-columns: repeat(2, 1fr);
	gap: 10px;
	padding: 15px;
	background: #f9f9f9;
	border: 1px solid #ddd;
	border-radius: 4px;
}

.metrics-checkbox-label {
	display: flex;
	align-items: center;
}

.metrics-checkbox-label input {
	margin-right: 8px;
}
</style>