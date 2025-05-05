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

		<!-- Tab Content -->
		<div class="greenmetrics-tabs-content">
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
						<!-- Badge Settings Tab -->
						<div class="greenmetrics-tab-content active" id="tab-badge">
							<!-- Badge Display -->
							<div class="greenmetrics-admin-card settings-card badge-display">
								<h3 class="settings-card-header accordion-trigger" data-card="badge-display">
									<span class="dashicons dashicons-visibility card-icon"></span>
									<?php esc_html_e( 'Badge Display', 'greenmetrics' ); ?>
									<span class="accordion-icon dashicons dashicons-arrow-down-alt2"></span>
								</h3>
								<div class="settings-card-content accordion-content">
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
								<h3 class="settings-card-header accordion-trigger" data-card="badge-appearance">
									<span class="dashicons dashicons-art card-icon"></span>
									<?php esc_html_e( 'Badge Appearance', 'greenmetrics' ); ?>
									<span class="accordion-icon dashicons dashicons-arrow-down-alt2"></span>
								</h3>
								<div class="settings-card-content accordion-content">
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
								<h3 class="settings-card-header accordion-trigger" data-card="popover-content">
									<span class="dashicons dashicons-editor-table card-icon"></span>
									<?php esc_html_e( 'Popover Content', 'greenmetrics' ); ?>
									<span class="accordion-icon dashicons dashicons-arrow-down-alt2"></span>
								</h3>
								<div class="settings-card-content accordion-content">
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
								<h3 class="settings-card-header accordion-trigger" data-card="popover-appearance">
									<span class="dashicons dashicons-admin-appearance card-icon"></span>
									<?php esc_html_e( 'Popover Appearance', 'greenmetrics' ); ?>
									<span class="accordion-icon dashicons dashicons-arrow-down-alt2"></span>
								</h3>
								<div class="settings-card-content accordion-content">
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

					<div class="sticky-submit-container">
						<?php submit_button(); ?>
					</div>
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

<!-- JavaScript variables needed by display-settings.js module -->
<script>
// Define ajaxurl if not already defined (WordPress admin variable)
if (typeof ajaxurl === 'undefined') {
    var ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
}

// Add localized strings for JavaScript
const customIconText = "<?php echo esc_js( __( 'Custom Icon', 'greenmetrics' ) ); ?>";
</script>

<!-- CSS is now loaded from greenmetrics-admin.css -->