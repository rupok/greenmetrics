<?php
/**
 * Email Reporter Class
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */

namespace GreenMetrics;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Make sure the Formatter class is loaded
if ( ! class_exists( '\GreenMetrics\GreenMetrics_Formatter' ) ) {
	require_once GREENMETRICS_PLUGIN_DIR . 'includes/class-greenmetrics-formatter.php';
}

/**
 * Email Reporter Class
 *
 * Handles the generation and sending of email reports
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */
class GreenMetrics_Email_Reporter {

	/**
	 * Singleton instance
	 *
	 * @var GreenMetrics_Email_Reporter
	 */
	private static $instance = null;

	/**
	 * Initialize the class.
	 */
	private function __construct() {
		// Private constructor to enforce singleton
		greenmetrics_log( 'Email reporter initialized' );
	}

	/**
	 * Get the singleton instance.
	 *
	 * @return GreenMetrics_Email_Reporter
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Schedule the email reporting cron job.
	 */
	public static function schedule_email_reporting() {
		// Get the settings
		$settings = GreenMetrics_Settings_Manager::get_instance()->get();

		// Check if email reporting is enabled
		if ( empty( $settings['email_reporting_enabled'] ) ) {
			// If disabled, clear any existing scheduled events
			if ( wp_next_scheduled( 'greenmetrics_send_email_report' ) ) {
				wp_clear_scheduled_hook( 'greenmetrics_send_email_report' );
				greenmetrics_log( 'Email reporting cron job unscheduled' );
			}
			return;
		}

		// Get frequency and day settings
		$frequency = isset( $settings['email_reporting_frequency'] ) ? $settings['email_reporting_frequency'] : 'weekly';
		$day = isset( $settings['email_reporting_day'] ) ? intval( $settings['email_reporting_day'] ) : 1;

		// Clear any existing scheduled events
		if ( wp_next_scheduled( 'greenmetrics_send_email_report' ) ) {
			wp_clear_scheduled_hook( 'greenmetrics_send_email_report' );
		}

		// Calculate the next run time based on frequency and day
		$next_run = self::calculate_next_run_time( $frequency, $day );

		// Schedule the cron job
		wp_schedule_event( $next_run, $frequency, 'greenmetrics_send_email_report' );
		greenmetrics_log( 'Email reporting cron job scheduled', array( 'next_run' => date( 'Y-m-d H:i:s', $next_run ) ) );
	}

	/**
	 * Calculate the next run time based on frequency and day.
	 *
	 * @param string $frequency The frequency (daily, weekly, monthly).
	 * @param int    $day The day (0-6 for weekly, 1-31 for monthly).
	 * @return int Timestamp for the next run.
	 */
	private static function calculate_next_run_time( $frequency, $day ) {
		$current_time = current_time( 'timestamp' );

		switch ( $frequency ) {
			case 'daily':
				// Run at midnight each day
				return strtotime( 'tomorrow midnight', $current_time );

			case 'weekly':
				// $day is 0-6 (Sunday-Saturday)
				$day = max( 0, min( 6, $day ) ); // Ensure day is between 0-6
				$days_of_week = array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
				$target_day = $days_of_week[ $day ];

				// Calculate next occurrence of the specified day at midnight
				return strtotime( "next $target_day midnight", $current_time );

			case 'monthly':
				// $day is 1-31
				$day = max( 1, min( 28, $day ) ); // Ensure day is between 1-28 for compatibility

				// Get the current month and year
				$current_month = date( 'n', $current_time );
				$current_year = date( 'Y', $current_time );
				$current_day = date( 'j', $current_time );

				// If the target day has already passed this month, move to next month
				if ( $current_day >= $day ) {
					$current_month++;
					// Handle year rollover
					if ( $current_month > 12 ) {
						$current_month = 1;
						$current_year++;
					}
				}

				// Create timestamp for the target day in the target month
				return mktime( 0, 0, 0, $current_month, $day, $current_year );

			default:
				// Default to weekly on Monday
				return strtotime( 'next Monday midnight', $current_time );
		}
	}

	/**
	 * Register the cron job callback.
	 */
	public static function register_cron_job() {
		add_action( 'greenmetrics_send_email_report', array( __CLASS__, 'send_scheduled_email_report' ) );
	}

	/**
	 * Send the scheduled email report.
	 */
	public static function send_scheduled_email_report() {
		$instance = self::get_instance();

		// Get settings
		$settings = GreenMetrics_Settings_Manager::get_instance()->get();

		// Check if email reporting is enabled
		if ( empty( $settings['email_reporting_enabled'] ) ) {
			greenmetrics_log( 'Scheduled email report skipped - feature disabled' );
			return;
		}

		greenmetrics_log( 'Sending scheduled email report' );

		// Generate and send the report
		$instance->generate_and_send_report();

		// Reschedule the next report
		self::schedule_email_reporting();
	}

	/**
	 * Generate and send the email report.
	 *
	 * @param bool $is_test Whether this is a test email.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function generate_and_send_report( $is_test = false ) {
		// Get settings
		$settings = GreenMetrics_Settings_Manager::get_instance()->get();

		// Get recipients
		$recipients = $this->get_recipients( $settings );

		// Get subject
		$subject = $this->get_email_subject( $settings, $is_test );

		// Generate email content
		$content = $this->generate_email_content( $settings, $is_test );

		// Set headers for HTML email
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
		);

		// Log the email details before sending
		if (function_exists('greenmetrics_log')) {
			greenmetrics_log( 'Attempting to send email', array(
				'recipients' => $recipients,
				'subject' => $subject,
				'headers' => $headers,
				'content_length' => strlen($content)
			) );
		} else {
			error_log('GreenMetrics - Attempting to send email to: ' . $recipients);
		}

		// Send the email
		$result = wp_mail( $recipients, $subject, $content, $headers );

		// Log the result
		if ( $result ) {
			if (function_exists('greenmetrics_log')) {
				greenmetrics_log( 'Email report sent successfully', array( 'recipients' => $recipients ) );
			} else {
				error_log('GreenMetrics - Email report sent successfully to: ' . $recipients);
			}
		} else {
			$mail_error = '';

			// Try to get the mail error
			global $phpmailer;
			if (isset($phpmailer) && is_object($phpmailer) && isset($phpmailer->ErrorInfo)) {
				$mail_error = $phpmailer->ErrorInfo;
			}

			if (function_exists('greenmetrics_log')) {
				greenmetrics_log( 'Failed to send email report', array(
					'recipients' => $recipients,
					'error' => $mail_error
				), 'error' );
			} else {
				error_log('GreenMetrics - Failed to send email report to: ' . $recipients . ' - Error: ' . $mail_error);
			}
		}

		return $result;
	}

	/**
	 * Get the recipients for the email report.
	 *
	 * @param array $settings The plugin settings.
	 * @return array|string The recipients.
	 */
	private function get_recipients( $settings ) {
		// If recipients are specified, use them
		if ( ! empty( $settings['email_reporting_recipients'] ) ) {
			return $settings['email_reporting_recipients'];
		}

		// Otherwise, use the admin email
		return get_option( 'admin_email' );
	}

	/**
	 * Get the email subject.
	 *
	 * @param array $settings The plugin settings.
	 * @param bool  $is_test Whether this is a test email.
	 * @return string The email subject.
	 */
	private function get_email_subject( $settings, $is_test ) {
		$subject = ! empty( $settings['email_reporting_subject'] )
			? $settings['email_reporting_subject']
			: 'GreenMetrics Report for [site_name]';

		// Replace placeholders
		$subject = str_replace( '[site_name]', get_bloginfo( 'name' ), $subject );
		$subject = str_replace( '[date]', date_i18n( get_option( 'date_format' ) ), $subject );

		// Add test indicator if this is a test
		if ( $is_test ) {
			$subject = '[TEST] ' . $subject;
		}

		return $subject;
	}

	/**
	 * Generate the email content.
	 *
	 * @param array $settings The plugin settings.
	 * @param bool  $is_test Whether this is a test email.
	 * @return string The email content.
	 * @throws \Exception If there is an error generating the email content.
	 */
	protected function generate_email_content( $settings, $is_test ) {
		// Validate required classes and functions
		if (!class_exists('\GreenMetrics\GreenMetrics_Tracker')) {
			throw new \Exception('GreenMetrics_Tracker class not found');
		}
		if (!class_exists('\GreenMetrics\GreenMetrics_Formatter')) {
			throw new \Exception('GreenMetrics_Formatter class not found');
		}
		// Get tracker instance
		try {
			$tracker = GreenMetrics_Tracker::get_instance();
			$stats = $tracker->get_stats();

			// Ensure stats is an array
			if (!is_array($stats)) {
				$stats = array(
					'carbon_footprint' => 0,
					'energy_consumption' => 0,
					'data_transfer' => 0,
					'performance_score' => 0,
				);
			}
		} catch (\Exception $e) {
			// Log the error
			if (function_exists('greenmetrics_log')) {
				greenmetrics_log('Error getting stats: ' . $e->getMessage(), null, 'error');
			} else {
				error_log('GreenMetrics - Error getting stats: ' . $e->getMessage());
			}

			// Use default stats
			$stats = array(
				'carbon_footprint' => 0,
				'energy_consumption' => 0,
				'data_transfer' => 0,
				'performance_score' => 0,
			);
		}

		// Default styles
		$default_styles = '
			body {
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
				line-height: 1.6;
				color: #333;
				background-color: #f9f9f9;
				margin: 0;
				padding: 0;
			}
			.container {
				max-width: 600px;
				margin: 0 auto;
				background-color: #ffffff;
				padding: 20px;
				border-radius: 5px;
				box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
			}
			.header {
				text-align: center;
				padding-bottom: 20px;
				border-bottom: 1px solid #eee;
				margin-bottom: 20px;
			}
			.header h1 {
				color: #4CAF50;
				margin: 0;
				padding: 0;
				font-size: 24px;
			}
			.header p {
				color: #777;
				margin: 5px 0 0;
			}
			.metrics-container {
				margin-bottom: 30px;
			}
			.metrics-grid {
				display: grid;
				grid-template-columns: repeat(2, 1fr);
				gap: 15px;
				margin-top: 20px;
			}
			.metric-card {
				background-color: #f5f5f5;
				border-radius: 4px;
				padding: 15px;
				text-align: center;
			}
			.metric-value {
				font-size: 24px;
				font-weight: bold;
				color: #4CAF50;
				margin: 10px 0;
			}
			.metric-label {
				font-size: 14px;
				color: #666;
			}
			.footer {
				text-align: center;
				margin-top: 30px;
				padding-top: 20px;
				border-top: 1px solid #eee;
				color: #777;
				font-size: 12px;
			}
			.test-notice {
				background-color: #fff3cd;
				color: #856404;
				padding: 10px;
				margin-bottom: 20px;
				border-radius: 4px;
				text-align: center;
			}
			.button {
				display: inline-block;
				background-color: #4CAF50;
				color: white;
				padding: 10px 20px;
				text-decoration: none;
				border-radius: 4px;
				margin-top: 15px;
			}
			.chart-container {
				margin: 20px 0;
				text-align: center;
			}
			.chart-container img {
				max-width: 100%;
				height: auto;
				border-radius: 4px;
				box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
			}
		';

		// Custom CSS if provided
		$custom_css = ! empty( $settings['email_reporting_css'] ) ? $settings['email_reporting_css'] : '';

		// Start building the email content
		$content = '<!DOCTYPE html>
		<html>
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
			<title>' . esc_html( get_bloginfo( 'name' ) ) . ' GreenMetrics Report</title>
			<style type="text/css">
				' . $default_styles . '
				' . $custom_css . '
			</style>
		</head>
		<body>
			<div class="container">';

		// Add test notice if this is a test
		if ( $is_test ) {
			$content .= '<div class="test-notice">
				<strong>This is a test email.</strong> This is how your scheduled reports will look.
			</div>';
		}

		// Add custom header if provided, otherwise use default
		if ( ! empty( $settings['email_reporting_header'] ) ) {
			$content .= wp_kses_post( $settings['email_reporting_header'] );
		} else {
			$content .= '<div class="header">
				<h1>GreenMetrics Report</h1>
				<p>' . esc_html( get_bloginfo( 'name' ) ) . ' | ' . esc_html( date_i18n( get_option( 'date_format' ) ) ) . '</p>
			</div>';
		}

		// Add metrics if enabled
		if ( ! empty( $settings['email_reporting_include_stats'] ) ) {
			$content .= '<div class="metrics-container">
				<h2>Website Environmental Impact</h2>
				<p>Here\'s a summary of your website\'s environmental metrics:</p>

				<div class="metrics-grid">
					<div class="metric-card">
						<div class="metric-label">Carbon Footprint</div>
						<div class="metric-value">' . esc_html( $this->safe_format('carbon', $stats['carbon_footprint']) ) . '</div>
					</div>

					<div class="metric-card">
						<div class="metric-label">Energy Consumption</div>
						<div class="metric-value">' . esc_html( $this->safe_format('energy', $stats['energy_consumption']) ) . '</div>
					</div>

					<div class="metric-card">
						<div class="metric-label">Data Transfer</div>
						<div class="metric-value">' . esc_html( $this->safe_format('bytes', $stats['data_transfer']) ) . '</div>
					</div>

					<div class="metric-card">
						<div class="metric-label">Performance Score</div>
						<div class="metric-value">' . esc_html( $this->safe_format('score', $stats['performance_score']) ) . '</div>
					</div>
				</div>
			</div>';
		}

		// Add chart if enabled
		if ( ! empty( $settings['email_reporting_include_chart'] ) ) {
			// In a real implementation, we would generate a chart image here
			// For now, we'll just add a placeholder
			$chart_image_url = GREENMETRICS_PLUGIN_URL . 'includes/admin/img/sample-chart.png';

			// Check if the chart image exists, if not use a placeholder
			$chart_image_path = GREENMETRICS_PLUGIN_DIR . 'includes/admin/img/sample-chart.png';
			if ( ! file_exists( $chart_image_path ) ) {
				// Create a data URI for a simple placeholder
				$chart_image_url = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAfQAAAEsCAYAAAA1u0HIAAAACXBIWXMAAAsTAAALEwEAmpwYAAAF0WlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4gPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS42LWMxNDUgNzkuMTYzNDk5LCAyMDE4LzA4LzEzLTE2OjQwOjIyICAgICAgICAiPiA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPiA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIgeG1sbnM6cGhvdG9zaG9wPSJodHRwOi8vbnMuYWRvYmUuY29tL3Bob3Rvc2hvcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ0MgMjAxOSAoTWFjaW50b3NoKSIgeG1wOkNyZWF0ZURhdGU9IjIwMjMtMDUtMDRUMTE6MzA6NDctMDQ6MDAiIHhtcDpNb2RpZnlEYXRlPSIyMDIzLTA1LTA0VDExOjMxOjI3LTA0OjAwIiB4bXA6TWV0YWRhdGFEYXRlPSIyMDIzLTA1LTA0VDExOjMxOjI3LTA0OjAwIiBkYzpmb3JtYXQ9ImltYWdlL3BuZyIgcGhvdG9zaG9wOkNvbG9yTW9kZT0iMyIgcGhvdG9zaG9wOklDQ1Byb2ZpbGU9InNSR0IgSUVDNjE5NjYtMi4xIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjRmYzFhNDY3LTBkMDctNDM5Ni1hYTM1LTkxZWYxODUzYjRkZSIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDo0ZmMxYTQ2Ny0wZDA3LTQzOTYtYWEzNS05MWVmMTg1M2I0ZGUiIHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZDo0ZmMxYTQ2Ny0wZDA3LTQzOTYtYWEzNS05MWVmMTg1M2I0ZGUiPiA8eG1wTU06SGlzdG9yeT4gPHJkZjpTZXE+IDxyZGY6bGkgc3RFdnQ6YWN0aW9uPSJjcmVhdGVkIiBzdEV2dDppbnN0YW5jZUlEPSJ4bXAuaWlkOjRmYzFhNDY3LTBkMDctNDM5Ni1hYTM1LTkxZWYxODUzYjRkZSIgc3RFdnQ6d2hlbj0iMjAyMy0wNS0wNFQxMTozMDo0Ny0wNDowMCIgc3RFdnQ6c29mdHdhcmVBZ2VudD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTkgKE1hY2ludG9zaCkiLz4gPC9yZGY6U2VxPiA8L3htcE1NOkhpc3Rvcnk+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+Gy6NrQAABFdJREFUeJzt1DEBACAMwDDAv+dxIoEeiYKe3TMzAMCr/g4AAK4ZOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAEGDoABBg6AAQYOgAELAeYQQNYVnuFAAAAAElFTkSuQmCC';
			}

			$content .= '<div class="chart-container">
				<h2>Metrics Trend</h2>
				<p>Your website\'s environmental metrics over the past 30 days:</p>
				<img src="' . esc_url( $chart_image_url ) . '" alt="Metrics Chart" width="500" height="300">
			</div>';
		}

		// Add link to dashboard
		$content .= '<div style="text-align: center;">
			<a href="' . esc_url( admin_url( 'admin.php?page=greenmetrics' ) ) . '" class="button">View Full Dashboard</a>
		</div>';

		// Add custom footer if provided, otherwise use default
		if ( ! empty( $settings['email_reporting_footer'] ) ) {
			$content .= wp_kses_post( $settings['email_reporting_footer'] );
		} else {
			$content .= '<div class="footer">
				<p>This report was generated by the GreenMetrics WordPress plugin.</p>
				<p>You can change your email preferences in the <a href="' . esc_url( admin_url( 'admin.php?page=greenmetrics_email_reporting' ) ) . '">GreenMetrics Email Reporting</a> settings.</p>
			</div>';
		}

		$content .= '</div>
		</body>
		</html>';

		return $content;
	}

	/**
	 * Send a test email report.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function send_test_email() {
		return $this->generate_and_send_report( true );
	}

	/**
	 * Safely format a value using the GreenMetrics_Formatter class.
	 *
	 * @param string $type The type of formatting to apply (carbon, energy, bytes, score).
	 * @param mixed $value The value to format.
	 * @return string The formatted value or a default value if formatting fails.
	 */
	private function safe_format($type, $value) {
		try {
			switch ($type) {
				case 'carbon':
					return GreenMetrics_Formatter::format_carbon_emissions($value);
				case 'energy':
					return GreenMetrics_Formatter::format_energy_consumption($value);
				case 'bytes':
					return GreenMetrics_Formatter::format_bytes($value);
				case 'score':
					return GreenMetrics_Formatter::format_performance_score($value);
				default:
					return (string) $value;
			}
		} catch (\Exception $e) {
			// Log the error
			if (function_exists('greenmetrics_log')) {
				greenmetrics_log('Error formatting ' . $type . ': ' . $e->getMessage(), null, 'error');
			} else {
				error_log('GreenMetrics - Error formatting ' . $type . ': ' . $e->getMessage());
			}

			// Return a default value
			switch ($type) {
				case 'carbon':
					return '0 kg CO2';
				case 'energy':
					return '0 kWh';
				case 'bytes':
					return '0 B';
				case 'score':
					return '0%';
				default:
					return '0';
			}
		}
	}

	/**
	 * Generate a preview email for the admin interface.
	 *
	 * @param array $settings The plugin settings.
	 * @return string The email content.
	 */
	public function generate_preview_email( $settings ) {
		// Test the formatter class
		try {
			$test_carbon = GreenMetrics_Formatter::format_carbon_emissions(100);
			$test_energy = GreenMetrics_Formatter::format_energy_consumption(1.5);
			$test_bytes = GreenMetrics_Formatter::format_bytes(1024);
			$test_score = GreenMetrics_Formatter::format_performance_score(85);

			if (function_exists('greenmetrics_log')) {
				greenmetrics_log("Formatter test: Carbon: $test_carbon, Energy: $test_energy, Bytes: $test_bytes, Score: $test_score");
			} else {
				error_log("GreenMetrics - Formatter test: Carbon: $test_carbon, Energy: $test_energy, Bytes: $test_bytes, Score: $test_score");
			}
		} catch (\Exception $e) {
			if (function_exists('greenmetrics_log')) {
				greenmetrics_log("Formatter test failed: " . $e->getMessage(), null, 'error');
			} else {
				error_log("GreenMetrics - Formatter test failed: " . $e->getMessage());
			}
		}
		try {
			// Create a simple fallback content in case of error
			$fallback_content = '<!DOCTYPE html><html><head><title>Email Preview</title></head><body><h1>Simple Email Preview</h1><p>This is a simple fallback preview.</p></body></html>';

			// Try to generate the email content
			$content = $this->generate_email_content( $settings, true );

			// If content is empty, use fallback
			if (empty($content)) {
				return $fallback_content;
			}

			return $content;
		} catch (\Exception $e) {
			// Log the error
			if (function_exists('greenmetrics_log')) {
				greenmetrics_log('Error generating email preview: ' . $e->getMessage(), null, 'error');
			} else {
				error_log('GreenMetrics - Error generating email preview: ' . $e->getMessage());
			}

			// Return a simple fallback content
			return '<!DOCTYPE html><html><head><title>Email Preview Error</title></head><body><h1>Email Preview Error</h1><p>Error: ' . esc_html($e->getMessage()) . '</p></body></html>';
		}
	}
}
