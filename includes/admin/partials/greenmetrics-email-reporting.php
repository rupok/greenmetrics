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
<div class="wrap greenmetrics-admin-wrap">
	<h1><?php esc_html_e( 'GreenMetrics - Email Reporting', 'greenmetrics' ); ?></h1>

	<?php settings_errors(); ?>
	<?php wp_nonce_field( 'greenmetrics_admin_nonce', 'greenmetrics_nonce' ); ?>

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

			<div class="greenmetrics-admin-card">
				<h2><?php esc_html_e( 'Email Template', 'greenmetrics' ); ?></h2>
				<p><?php esc_html_e( 'Customize the appearance of your email reports.', 'greenmetrics' ); ?></p>

				<form method="post" action="options.php">
					<?php settings_fields( 'greenmetrics_settings' ); ?>

					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Email Header', 'greenmetrics' ); ?></th>
							<td>
								<textarea id="email_reporting_header" name="greenmetrics_settings[email_reporting_header]" rows="4" class="large-text code email-template-textarea"><?php echo esc_textarea( isset( $settings['email_reporting_header'] ) ? $settings['email_reporting_header'] : '' ); ?></textarea>
								<p class="description">
									<?php esc_html_e( 'Custom HTML to include at the top of the email. Leave empty to use the default header.', 'greenmetrics' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Email Footer', 'greenmetrics' ); ?></th>
							<td>
								<textarea id="email_reporting_footer" name="greenmetrics_settings[email_reporting_footer]" rows="4" class="large-text code email-template-textarea"><?php echo esc_textarea( isset( $settings['email_reporting_footer'] ) ? $settings['email_reporting_footer'] : '' ); ?></textarea>
								<p class="description">
									<?php esc_html_e( 'Custom HTML to include at the bottom of the email. Leave empty to use the default footer.', 'greenmetrics' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Custom CSS', 'greenmetrics' ); ?></th>
							<td>
								<textarea id="email_reporting_css" name="greenmetrics_settings[email_reporting_css]" rows="6" class="large-text code email-template-textarea"><?php echo esc_textarea( isset( $settings['email_reporting_css'] ) ? $settings['email_reporting_css'] : '' ); ?></textarea>
								<p class="description">
									<?php esc_html_e( 'Custom CSS to include in the email. This will override the default styles.', 'greenmetrics' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<?php submit_button( __( 'Save Template', 'greenmetrics' ) ); ?>
				</form>
			</div>

			<div class="greenmetrics-admin-card test-email-section">
				<h2><?php esc_html_e( 'Test Email', 'greenmetrics' ); ?></h2>
				<p><?php esc_html_e( 'Send a test email to verify your settings.', 'greenmetrics' ); ?></p>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Send Test Email', 'greenmetrics' ); ?></th>
						<td>
							<button type="button" id="send_test_email" class="button button-primary">
								<?php esc_html_e( 'Send Test Email', 'greenmetrics' ); ?>
							</button>
							<span id="test_email_result" class="test-email-result"></span>
							<p class="description"><?php esc_html_e( 'Send a test email to the recipients specified above.', 'greenmetrics' ); ?></p>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<!-- Right Column: Preview -->
		<div class="greenmetrics-admin-info-column">
			<div class="greenmetrics-admin-card">
				<h2><?php esc_html_e( 'Email Preview', 'greenmetrics' ); ?></h2>
				<p><?php esc_html_e( 'This is a preview of how your email report will look.', 'greenmetrics' ); ?></p>

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

					<div class="email-preview-note">
						<?php esc_html_e( 'Preview updates automatically when settings are changed.', 'greenmetrics' ); ?>
					</div>
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
					<li><code>[date]</code> - <?php esc_html_e( 'Current date', 'greenmetrics' ); ?></li>
				</ul>
			</div>
		</div>
	</div>
</div>

<!-- JavaScript functionality is now handled by the email-reporting.js module -->


