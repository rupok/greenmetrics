<?php
/**
 * Email reporting admin template.
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
		'email_reporting_enabled'       => 0,
		'email_reporting_frequency'     => 'weekly',
		'email_reporting_day'           => 1,
		'email_reporting_recipients'    => '',
		'email_reporting_subject'       => 'GreenMetrics Weekly Report for [site_name]',
		'email_reporting_include_stats' => 1,
		'email_reporting_include_chart' => 1,
	)
);

// Get email reporter instance
$email_reporter = \GreenMetrics\GreenMetrics_Email_Reporter::get_instance();

// Get tracker instance for stats preview
$tracker = \GreenMetrics\GreenMetrics_Tracker::get_instance();
$stats   = $tracker->get_stats();

?>
<div class="wrap">
	<div class="greenmetrics-admin-container email-reporting-page">
		<div class="greenmetrics-admin-header">
			<div class="header-content">
				<img src="<?php echo esc_url( GREENMETRICS_PLUGIN_URL . 'includes/admin/img/greenmetrics-icon.png' ); ?>" alt="<?php esc_attr_e( 'GreenMetrics Icon', 'greenmetrics' ); ?>" />
				<h1><?php esc_html_e( 'GreenMetrics - Email Reporting', 'greenmetrics' ); ?></h1>
			</div>

			<span class="version">
			<?php
			/* translators: %s: Plugin version number */
			echo esc_html( sprintf( __( 'GreenMetrics v%s', 'greenmetrics' ), GREENMETRICS_VERSION ) );
			?>
			</span>
		</div>

	<?php settings_errors(); ?>
	<?php wp_nonce_field( 'greenmetrics_admin_nonce', 'greenmetrics_nonce' ); ?>

	<!-- Tab Navigation -->
	<div class="greenmetrics-tabs-nav">
		<ul class="greenmetrics-tabs-list">
			<li class="greenmetrics-tab-item active" data-tab="settings">
				<span class="dashicons dashicons-admin-settings"></span>
				<?php esc_html_e( 'Email Report Settings', 'greenmetrics' ); ?>
			</li>
			<li class="greenmetrics-tab-item" data-tab="templates">
				<span class="dashicons dashicons-admin-appearance"></span>
				<?php esc_html_e( 'Email Templates', 'greenmetrics' ); ?>
			</li>
			<li class="greenmetrics-tab-item" data-tab="history">
				<span class="dashicons dashicons-email-alt"></span>
				<?php esc_html_e( 'Email History', 'greenmetrics' ); ?>
			</li>
		</ul>
	</div>

	<!-- Tab Content -->
	<div class="greenmetrics-tabs-content">
		<!-- Settings Tab -->
		<div class="greenmetrics-tab-content active" id="tab-settings">
			<div class="greenmetrics-admin-content-wrapper">
				<!-- Left Column: Settings Form -->
				<div class="greenmetrics-admin-settings-column">
					<div class="greenmetrics-admin-card">
						<h2><?php esc_html_e( 'Email Report Settings', 'greenmetrics' ); ?></h2>
						<p><?php esc_html_e( 'Configure scheduled email reports to keep track of your website\'s environmental impact.', 'greenmetrics' ); ?></p>

				<form method="post" action="options.php" class="email-settings-form">
					<?php settings_fields( 'greenmetrics_settings' ); ?>

					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Email Reports', 'greenmetrics' ); ?></th>
							<td>
								<label class="toggle-switch">
									<input type="checkbox" id="email_reporting_enabled" name="greenmetrics_settings[email_reporting_enabled]" value="1" <?php checked( isset( $settings['email_reporting_enabled'] ) ? $settings['email_reporting_enabled'] : 0, 1 ); ?>>
									<span class="slider"></span>
								</label>
								<p class="description"><?php esc_html_e( 'Enable scheduled email reports with your website\'s environmental metrics.', 'greenmetrics' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Report Frequency', 'greenmetrics' ); ?></th>
							<td>
								<select id="email_reporting_frequency" name="greenmetrics_settings[email_reporting_frequency]">
									<option value="daily" <?php selected( isset( $settings['email_reporting_frequency'] ) ? $settings['email_reporting_frequency'] : 'weekly', 'daily' ); ?>><?php esc_html_e( 'Daily', 'greenmetrics' ); ?></option>
									<option value="weekly" <?php selected( isset( $settings['email_reporting_frequency'] ) ? $settings['email_reporting_frequency'] : 'weekly', 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'greenmetrics' ); ?></option>
									<option value="monthly" <?php selected( isset( $settings['email_reporting_frequency'] ) ? $settings['email_reporting_frequency'] : 'weekly', 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'greenmetrics' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'How often to send email reports.', 'greenmetrics' ); ?></p>
							</td>
						</tr>
						<tr id="email_reporting_day_row">
							<th scope="row"><?php esc_html_e( 'Report Day', 'greenmetrics' ); ?></th>
							<td>
								<?php
								$frequency = isset( $settings['email_reporting_frequency'] ) ? $settings['email_reporting_frequency'] : 'weekly';
								$day_value = isset( $settings['email_reporting_day'] ) ? $settings['email_reporting_day'] : 1;

								// Add a hidden field to store the saved value for JavaScript
								echo '<input type="hidden" id="email_reporting_day_saved_value" value="' . esc_attr($day_value) . '">';

								if ( $frequency === 'weekly' ) :
								?>
									<select id="email_reporting_day" name="greenmetrics_settings[email_reporting_day]">
										<option value="0" <?php selected( $day_value, 0 ); ?>><?php esc_html_e( 'Sunday', 'greenmetrics' ); ?></option>
										<option value="1" <?php selected( $day_value, 1 ); ?>><?php esc_html_e( 'Monday', 'greenmetrics' ); ?></option>
										<option value="2" <?php selected( $day_value, 2 ); ?>><?php esc_html_e( 'Tuesday', 'greenmetrics' ); ?></option>
										<option value="3" <?php selected( $day_value, 3 ); ?>><?php esc_html_e( 'Wednesday', 'greenmetrics' ); ?></option>
										<option value="4" <?php selected( $day_value, 4 ); ?>><?php esc_html_e( 'Thursday', 'greenmetrics' ); ?></option>
										<option value="5" <?php selected( $day_value, 5 ); ?>><?php esc_html_e( 'Friday', 'greenmetrics' ); ?></option>
										<option value="6" <?php selected( $day_value, 6 ); ?>><?php esc_html_e( 'Saturday', 'greenmetrics' ); ?></option>
									</select>
									<p class="description"><?php esc_html_e( 'Day of the week to send reports.', 'greenmetrics' ); ?></p>
								<?php elseif ( $frequency === 'monthly' ) : ?>
									<select id="email_reporting_day" name="greenmetrics_settings[email_reporting_day]">
										<?php for ( $i = 1; $i <= 28; $i++ ) : ?>
											<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $day_value, $i ); ?>><?php echo esc_html( $i ); ?></option>
										<?php endfor; ?>
									</select>
									<p class="description"><?php esc_html_e( 'Day of the month to send reports (1-28).', 'greenmetrics' ); ?></p>
								<?php else : ?>
									<input type="hidden" id="email_reporting_day" name="greenmetrics_settings[email_reporting_day]" value="1">
									<p class="description"><?php esc_html_e( 'Reports will be sent daily at midnight.', 'greenmetrics' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Recipients', 'greenmetrics' ); ?></th>
							<td>
								<input type="text" id="email_reporting_recipients" name="greenmetrics_settings[email_reporting_recipients]" value="<?php echo esc_attr( isset( $settings['email_reporting_recipients'] ) ? $settings['email_reporting_recipients'] : '' ); ?>" class="regular-text">
								<p class="description">
									<?php esc_html_e( 'Email addresses to receive reports (comma-separated). Leave empty to use admin email.', 'greenmetrics' ); ?>
									<br>
									<?php esc_html_e( 'Current admin email:', 'greenmetrics' ); ?> <code><?php echo esc_html( get_option( 'admin_email' ) ); ?></code>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Email Subject', 'greenmetrics' ); ?></th>
							<td>
								<input type="text" id="email_reporting_subject" name="greenmetrics_settings[email_reporting_subject]" value="<?php echo esc_attr( isset( $settings['email_reporting_subject'] ) ? $settings['email_reporting_subject'] : 'GreenMetrics Report for [site_name]' ); ?>" class="regular-text">
								<p class="description">
									<?php esc_html_e( 'Subject line for email reports.', 'greenmetrics' ); ?>
									<br>
									<?php esc_html_e( 'Available placeholders:', 'greenmetrics' ); ?> <code>[site_name]</code>, <code>[date]</code>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Report Content', 'greenmetrics' ); ?></th>
							<td>
								<label class="checkbox-label">
									<input type="checkbox" id="email_reporting_include_stats" name="greenmetrics_settings[email_reporting_include_stats]" value="1" <?php checked( isset( $settings['email_reporting_include_stats'] ) ? $settings['email_reporting_include_stats'] : 1, 1 ); ?>>
									<?php esc_html_e( 'Include environmental statistics', 'greenmetrics' ); ?>
								</label>
								<br>
								<label class="checkbox-label">
									<input type="checkbox" id="email_reporting_include_chart" name="greenmetrics_settings[email_reporting_include_chart]" value="1" <?php checked( isset( $settings['email_reporting_include_chart'] ) ? $settings['email_reporting_include_chart'] : 1, 1 ); ?>>
									<?php esc_html_e( 'Include metrics chart', 'greenmetrics' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Choose what content to include in the email report.', 'greenmetrics' ); ?></p>
							</td>
						</tr>
					</table>

					<?php submit_button( __( 'Save Settings', 'greenmetrics' ) ); ?>
				</form>
			</div>



			<div class="greenmetrics-admin-card test-email-section">
				<h2><?php esc_html_e( 'Test Email', 'greenmetrics' ); ?></h2>
				<p><?php esc_html_e( 'Send a test email to verify your settings.', 'greenmetrics' ); ?></p>

				<div class="test-email-container">
					<button type="button" id="send_test_email" class="button button-primary button-large">
						<span class="dashicons dashicons-email-alt" style="font-size: 18px; vertical-align: middle; margin-right: 8px;"></span>
						<?php esc_html_e( 'Send Test Email', 'greenmetrics' ); ?>
					</button>
					<div id="test_email_result" class="test-email-result"></div>
				</div>
			</div>
		</div>
			</div>
		</div>

<!-- Templates Tab -->
<div class="greenmetrics-tab-content" id="tab-templates">
	<div class="greenmetrics-admin-content-wrapper">
		<div class="greenmetrics-admin-settings-column">
			<div class="greenmetrics-admin-card" id="email-template-editor">
				<h2><?php esc_html_e( 'Email Template', 'greenmetrics' ); ?></h2>
				<p><?php esc_html_e( 'Customize the appearance of your email reports.', 'greenmetrics' ); ?></p>

				<form method="post" action="options.php">
					<?php settings_fields( 'greenmetrics_settings' ); ?>

					<?php
					// Add hidden fields for all the settings from the first form to preserve them
					$preserve_fields = array(
						'email_reporting_enabled',
						'email_reporting_frequency',
						'email_reporting_day',
						'email_reporting_recipients',
						'email_reporting_subject',
						'email_reporting_include_stats',
						'email_reporting_include_chart'
					);

					foreach ($preserve_fields as $field) {
						$value = isset($settings[$field]) ? $settings[$field] : '';
						if (is_array($value)) {
							foreach ($value as $key => $val) {
								echo '<input type="hidden" name="greenmetrics_settings[' . esc_attr($field) . '][' . esc_attr($key) . ']" value="' . esc_attr($val) . '">';
							}
						} else {
							echo '<input type="hidden" name="greenmetrics_settings[' . esc_attr($field) . ']" value="' . esc_attr($value) . '">';
						}
					}
					?>

					<div class="template-selector-container">
						<table class="form-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Template Style', 'greenmetrics' ); ?></th>
								<td>
									<?php
									// Get all settings directly
									$all_settings = get_option('greenmetrics_settings', array());

									// Set default template
									$current_template = 'default';

									// First check the $settings array passed to the template
									if (isset($settings['email_template_style']) && !empty($settings['email_template_style'])) {
										$current_template = $settings['email_template_style'];
									}
									// Then check the direct option value
									else if (isset($all_settings['email_template_style']) && !empty($all_settings['email_template_style'])) {
										$current_template = $all_settings['email_template_style'];
									}

									?>
									<select id="email_template_selector" name="greenmetrics_settings[email_template_style]" class="regular-text">
										<option value="default" <?php selected($current_template, 'default'); ?>>Default</option>
										<option value="minimal" <?php selected($current_template, 'minimal'); ?>>Minimal</option>
										<option value="modern" <?php selected($current_template, 'modern'); ?>>Modern</option>
										<option value="eco" <?php selected($current_template, 'eco'); ?>>Eco-Friendly</option>
									</select>
									<p class="description" id="template-description">
										<?php esc_html_e( 'Select a predefined template style.', 'greenmetrics' ); ?>
									</p>
								</td>
							</tr>
						</table>
					</div>

					<div class="template-colors-container">
						<h3><span class="dashicons dashicons-admin-appearance" style="margin-right: 5px; font-size: 18px; vertical-align: middle;"></span><?php esc_html_e( 'Color Scheme', 'greenmetrics' ); ?></h3>
						<table class="form-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Primary Color', 'greenmetrics' ); ?></th>
								<td>
									<input type="text" id="email_color_primary" name="greenmetrics_settings[email_color_primary]" value="<?php echo esc_attr( isset( $settings['email_color_primary'] ) ? $settings['email_color_primary'] : '#4CAF50' ); ?>" class="color-picker" data-default-color="#4CAF50" />
									<p class="description">
										<?php esc_html_e( 'Main brand color used for headings and accents.', 'greenmetrics' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Secondary Color', 'greenmetrics' ); ?></th>
								<td>
									<input type="text" id="email_color_secondary" name="greenmetrics_settings[email_color_secondary]" value="<?php echo esc_attr( isset( $settings['email_color_secondary'] ) ? $settings['email_color_secondary'] : '#f9f9f9' ); ?>" class="color-picker" data-default-color="#f9f9f9" />
									<p class="description">
										<?php esc_html_e( 'Used for backgrounds and secondary elements.', 'greenmetrics' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Accent Color', 'greenmetrics' ); ?></th>
								<td>
									<input type="text" id="email_color_accent" name="greenmetrics_settings[email_color_accent]" value="<?php echo esc_attr( isset( $settings['email_color_accent'] ) ? $settings['email_color_accent'] : '#333333' ); ?>" class="color-picker" data-default-color="#333333" />
									<p class="description">
										<?php esc_html_e( 'Used for buttons and highlights.', 'greenmetrics' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Text Color', 'greenmetrics' ); ?></th>
								<td>
									<input type="text" id="email_color_text" name="greenmetrics_settings[email_color_text]" value="<?php echo esc_attr( isset( $settings['email_color_text'] ) ? $settings['email_color_text'] : '#333333' ); ?>" class="color-picker" data-default-color="#333333" />
									<p class="description">
										<?php esc_html_e( 'Main text color.', 'greenmetrics' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Background Color', 'greenmetrics' ); ?></th>
								<td>
									<input type="text" id="email_color_background" name="greenmetrics_settings[email_color_background]" value="<?php echo esc_attr( isset( $settings['email_color_background'] ) ? $settings['email_color_background'] : '#ffffff' ); ?>" class="color-picker" data-default-color="#ffffff" />
									<p class="description">
										<?php esc_html_e( 'Main background color.', 'greenmetrics' ); ?>
									</p>
								</td>
							</tr>
						</table>
					</div>

					<div class="template-content-container">
						<h3><span class="dashicons dashicons-editor-code" style="margin-right: 5px; font-size: 18px; vertical-align: middle;"></span><?php esc_html_e( 'Template Content', 'greenmetrics' ); ?></h3>

						<div class="placeholder-toolbar">
							<label><?php esc_html_e( 'Available Placeholders:', 'greenmetrics' ); ?></label>
							<p class="description"><?php esc_html_e( 'Click a placeholder to copy it to your clipboard, then paste it into your template.', 'greenmetrics' ); ?></p>
							<div id="placeholder-buttons" class="placeholder-buttons-container">
								<!-- Placeholder buttons will be added by JavaScript -->
							</div>
						</div>

						<table class="form-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Email Header', 'greenmetrics' ); ?></th>
								<td data-filename="header.html">
									<textarea id="email_reporting_header" name="greenmetrics_settings[email_reporting_header]" rows="4" class="large-text code email-template-textarea"><?php echo esc_textarea( isset( $settings['email_reporting_header'] ) ? $settings['email_reporting_header'] : '' ); ?></textarea>
									<p class="description">
										<?php esc_html_e( 'Custom HTML to include at the top of the email. Leave empty to use the default header.', 'greenmetrics' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Email Footer', 'greenmetrics' ); ?></th>
								<td data-filename="footer.html">
									<textarea id="email_reporting_footer" name="greenmetrics_settings[email_reporting_footer]" rows="4" class="large-text code email-template-textarea"><?php echo esc_textarea( isset( $settings['email_reporting_footer'] ) ? $settings['email_reporting_footer'] : '' ); ?></textarea>
									<p class="description">
										<?php esc_html_e( 'Custom HTML to include at the bottom of the email. Leave empty to use the default footer.', 'greenmetrics' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Custom CSS', 'greenmetrics' ); ?></th>
								<td data-filename="styles.css">
									<textarea id="email_reporting_css" name="greenmetrics_settings[email_reporting_css]" rows="6" class="large-text code email-template-textarea"><?php echo esc_textarea( isset( $settings['email_reporting_css'] ) ? $settings['email_reporting_css'] : '' ); ?></textarea>
									<p class="description">
										<?php esc_html_e( 'Custom CSS to include in the email. This will override the default styles.', 'greenmetrics' ); ?>
									</p>
								</td>
							</tr>
						</table>
					</div>

					<?php submit_button( __( 'Save Template', 'greenmetrics' ) ); ?>
				</form>
			</div>
		</div>

		<!-- Right Column: Preview -->
		<div class="greenmetrics-admin-info-column">
			<div class="greenmetrics-admin-card">
				<h2><?php esc_html_e( 'Email Preview', 'greenmetrics' ); ?></h2>
				<p><?php esc_html_e( 'This is a preview of how your email report will look.', 'greenmetrics' ); ?></p>

				<div class="email-preview-controls">
					<span class="preview-info">
						<?php esc_html_e( 'Preview updates automatically when settings are changed.', 'greenmetrics' ); ?>
					</span>
				</div>

				<div class="email-preview-container">
					<div class="email-preview-header">
						<div class="email-preview-subject">
							<strong><?php esc_html_e( 'Subject:', 'greenmetrics' ); ?></strong>
							<span id="preview-subject">
								<?php
								$subject = isset( $settings['email_reporting_subject'] )
									? $settings['email_reporting_subject']
									: 'GreenMetrics Report for [site_name]';

								$subject = str_replace( '[site_name]', get_bloginfo( 'name' ), $subject );
								$subject = str_replace( '[date]', date_i18n( get_option( 'date_format' ) ), $subject );

								echo esc_html( $subject );
								?>
							</span>
						</div>
						<div class="email-preview-recipients">
							<strong><?php esc_html_e( 'To:', 'greenmetrics' ); ?></strong>
							<span id="preview-recipients">
								<?php
								$recipients = isset( $settings['email_reporting_recipients'] ) && ! empty( $settings['email_reporting_recipients'] )
									? $settings['email_reporting_recipients']
									: get_option( 'admin_email' );

								echo esc_html( $recipients );
								?>
							</span>
						</div>
					</div>

					<div class="email-preview-content">
						<iframe id="email-preview-frame" class="email-preview-frame"></iframe>
					</div>

					<div class="email-preview-footer">
						<button type="button" id="toggle-mobile-preview" class="button">
							<span class="dashicons dashicons-smartphone" style="margin-right: 5px; font-size: 18px; vertical-align: text-bottom;"></span>
							<?php esc_html_e( 'Mobile Preview', 'greenmetrics' ); ?>
						</button>
					</div>
				</div>
			</div>

			<div class="greenmetrics-admin-card test-email-section">
				<h2><?php esc_html_e( 'Test Email', 'greenmetrics' ); ?></h2>
				<p><?php esc_html_e( 'Send a test email to verify your template.', 'greenmetrics' ); ?></p>

				<div class="test-email-container">
					<button type="button" id="send_test_email_template" class="button button-primary button-large">
						<span class="dashicons dashicons-email-alt" style="font-size: 18px; vertical-align: middle; margin-right: 8px;"></span>
						<?php esc_html_e( 'Send Test Email', 'greenmetrics' ); ?>
					</button>
					<div id="test_email_template_result" class="test-email-result"></div>
				</div>
			</div>

			<div class="greenmetrics-admin-card email-help-section">
				<h2><?php esc_html_e( 'Email Reporting Help', 'greenmetrics' ); ?></h2>

				<h3><?php esc_html_e( 'About Email Reports', 'greenmetrics' ); ?></h3>
				<p><?php esc_html_e( 'Email reports provide a convenient way to monitor your website\'s environmental impact without having to log in to your WordPress dashboard.', 'greenmetrics' ); ?></p>

				<h3><?php esc_html_e( 'Scheduling', 'greenmetrics' ); ?></h3>
				<p><?php esc_html_e( 'Reports can be scheduled daily, weekly, or monthly. For weekly reports, you can choose the day of the week. For monthly reports, you can choose the day of the month (1-28).', 'greenmetrics' ); ?></p>

				<h3><?php esc_html_e( 'Customization', 'greenmetrics' ); ?></h3>
				<p><?php esc_html_e( 'You can customize the email subject, recipients, and content. The email template can be customized with your own HTML and CSS.', 'greenmetrics' ); ?></p>

				<h3><?php esc_html_e( 'Placeholders', 'greenmetrics' ); ?></h3>
				<p><?php esc_html_e( 'You can use the following placeholders in your email subject and content:', 'greenmetrics' ); ?></p>
				<ul>
					<li><code>[site_name]</code> - <?php esc_html_e( 'Your website name', 'greenmetrics' ); ?></li>
					<li><code>[site_url]</code> - <?php esc_html_e( 'Your website URL', 'greenmetrics' ); ?></li>
					<li><code>[date]</code> - <?php esc_html_e( 'Current date', 'greenmetrics' ); ?></li>
					<li><code>[admin_email]</code> - <?php esc_html_e( 'Admin email address', 'greenmetrics' ); ?></li>
					<li><code>[user_name]</code> - <?php esc_html_e( 'Current user\'s name', 'greenmetrics' ); ?></li>
					<li><code>[user_email]</code> - <?php esc_html_e( 'Current user\'s email', 'greenmetrics' ); ?></li>
				</ul>
			</div>
		</div>
	</div>
</div>

	<!-- History Tab -->
	<div class="greenmetrics-tab-content" id="tab-history">
		<div class="greenmetrics-admin-content-wrapper">
			<div class="greenmetrics-admin-section email-reports-section">
				<div class="greenmetrics-admin-card">
				<h2>
					<span class="dashicons dashicons-email-alt" style="font-size: 24px; margin-right: 10px; color: #2271b1;"></span>
					<?php esc_html_e( 'Email Report History', 'greenmetrics' ); ?>
				</h2>
				<p><?php esc_html_e( 'View a history of sent email reports and manage your reporting activity.', 'greenmetrics' ); ?></p>

				<?php
				// Get report history
				if ( class_exists( '\GreenMetrics\GreenMetrics_Email_Report_History' ) ) {
					$history = \GreenMetrics\GreenMetrics_Email_Report_History::get_instance();

					// Get page number from URL with sanitization
					$page = isset( $_GET['report_page'] ) ? absint( $_GET['report_page'] ) : 1;

					// Verify we're on an admin page to prevent unauthorized access
					if ( ! is_admin() ) {
						$page = 1;
					}

					// Verify nonce if pagination is being used
					if ( isset( $_GET['report_page'] ) && isset( $_GET['_wpnonce'] ) ) {
						if ( ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'greenmetrics_email_report_pagination' ) ) {
							// If nonce verification fails, reset to page 1
							$page = 1;
						}
					}

					// Add a nonce for pagination links
					$nonce = wp_create_nonce( 'greenmetrics_email_report_pagination' );

					// Get reports
					$reports = $history->get_reports( array(
						'per_page' => 10,
						'page'     => $page,
					) );

					// Get total reports
					$total_reports = $history->get_total_reports();

					// Calculate total pages
					$total_pages = ceil( $total_reports / 10 );

					if ( ! empty( $reports ) ) {
						?>
						<div class="report-history-table-container">
							<table class="wp-list-table widefat fixed striped report-history-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Date', 'greenmetrics' ); ?></th>
										<th><?php esc_html_e( 'Type', 'greenmetrics' ); ?></th>
										<th><?php esc_html_e( 'Recipients', 'greenmetrics' ); ?></th>
										<th><?php esc_html_e( 'Subject', 'greenmetrics' ); ?></th>
										<th><?php esc_html_e( 'Status', 'greenmetrics' ); ?></th>
										<th><?php esc_html_e( 'Actions', 'greenmetrics' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $reports as $report ) : ?>
										<tr>
											<td>
												<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $report['sent_at'] ) ) ); ?>
											</td>
											<td>
												<?php
												$type_label = '';
												switch ( $report['report_type'] ) {
													case 'daily':
														$type_label = __( 'Daily', 'greenmetrics' );
														break;
													case 'weekly':
														$type_label = __( 'Weekly', 'greenmetrics' );
														break;
													case 'monthly':
														$type_label = __( 'Monthly', 'greenmetrics' );
														break;
													default:
														$type_label = $report['report_type'];
												}
												echo esc_html( $type_label );

												if ( $report['is_test'] ) {
													echo ' <span class="test-badge">' . esc_html__( 'Test', 'greenmetrics' ) . '</span>';
												}
												?>
											</td>
											<td>
												<?php echo esc_html( $report['recipients'] ); ?>
											</td>
											<td>
												<?php echo esc_html( $report['subject'] ); ?>
											</td>
											<td>
												<?php
												$status_class = '';
												switch ( $report['status'] ) {
													case 'sent':
														$status_class = 'status-success';
														break;
													case 'failed':
														$status_class = 'status-error';
														break;
													default:
														$status_class = '';
												}
												?>
												<span class="status-badge <?php echo esc_attr( $status_class ); ?>">
													<?php echo esc_html( ucfirst( $report['status'] ) ); ?>
												</span>
											</td>
											<td>
												<button type="button" class="button button-small view-report" data-report-id="<?php echo esc_attr( $report['id'] ); ?>">
													<span class="dashicons dashicons-visibility" style="font-size: 16px; vertical-align: middle; margin-right: 3px;"></span>
													<?php esc_html_e( 'View', 'greenmetrics' ); ?>
												</button>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>

							<?php if ( $total_pages > 1 ) : ?>
								<div class="tablenav">
									<div class="tablenav-pages">
										<span class="displaying-num">
											<?php
											/* translators: %s: Number of items. */
											printf(
												esc_html( _n( '%s item', '%s items', $total_reports, 'greenmetrics' ) ),
												esc_html( number_format_i18n( $total_reports ) )
											);
											?>
										</span>
										<span class="pagination-links">
											<?php
											// Add nonce to pagination links
											$page_links = paginate_links( array(
												'base'      => add_query_arg( array(
													'report_page' => '%#%',
													'_wpnonce'    => $nonce,
												) ),
												'format'    => '',
												'prev_text' => __( '&laquo;', 'greenmetrics' ),
												'next_text' => __( '&raquo;', 'greenmetrics' ),
												'total'     => $total_pages,
												'current'   => $page,
											) );

											echo wp_kses_post( $page_links );
											?>
										</span>
									</div>
								</div>
							<?php endif; ?>
						</div>
						<?php
					} else {
						?>
						<div class="no-reports-message">
							<p><?php esc_html_e( 'No email reports have been sent yet.', 'greenmetrics' ); ?></p>
							<p><?php esc_html_e( 'Configure your email settings and click "Send Test Email" to see how your reports will look.', 'greenmetrics' ); ?></p>
							<p>
								<a href="#" class="button send-test-email-link" data-target-tab="templates">
									<span class="dashicons dashicons-email-alt" style="font-size: 16px; vertical-align: middle; margin-right: 3px;"></span>
									<?php esc_html_e( 'Send a Test Email', 'greenmetrics' ); ?>
								</a>
							</p>
						</div>
						<?php
					}
				} else {
					?>
					<div class="error-message">
						<p><?php esc_html_e( 'Error: Email Report History class not found.', 'greenmetrics' ); ?></p>
					</div>
					<?php
				}
				?>
			</div>
		</div>
	</div>
</div>

<!-- Report View Modal -->
<div id="report-view-modal" class="report-modal">
	<div class="report-modal-content">
		<span class="report-modal-close">&times;</span>
		<div id="report-modal-body">
			<!-- Report content will be loaded here -->
		</div>
	</div>
</div>

<!-- Tab functionality is now handled in greenmetrics-admin-modules/email-reporting.js -->

					</div>
				</div>
			</div>
		</div>
</div>
