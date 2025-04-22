<?php
/**
 * Admin display template.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get stats using the tracker singleton
$tracker = \GreenMetrics\GreenMetrics_Tracker::get_instance();
$stats   = $tracker->get_stats();

// Get settings
$settings = get_option(
	'greenmetrics_settings',
	array(
		'tracking_enabled' => 1,
		'enable_badge'     => 1,
	)
);
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="greenmetrics-admin-container">
		<div class="greenmetrics-admin-content">
			<div class="greenmetrics-admin-stats">
				<h2><?php esc_html_e( 'Website Environmental Metrics', 'greenmetrics' ); ?></h2>
				
				<!-- Environmental Impact Context Section with visuals -->
				<div class="greenmetrics-environmental-context">
					<div class="context-item carbon">
						<div class="context-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="64" height="64">
								<path fill="currentColor" d="M17,8C8,10,5.9,16.17,3.82,21.34L5.71,22l1-2.3A4.49,4.49,0,0,0,8,20C19,20,22,3,22,3,21,5,14,5.25,9,6.25S2,11.5,2,13.5a6.23,6.23,0,0,0,1.4,3.3L3,19l1.76,1.37A10.23,10.23,0,0,1,4,17C4,16,7,8,17,8Z"/>
							</svg>
						</div>
						<div class="context-content">
							<h3><?php esc_html_e( 'Carbon Footprint Impact', 'greenmetrics' ); ?></h3>
							<p>
								<?php
									// Get the correctly calculated carbon footprint value
									$total_carbon = $stats['total_carbon_footprint'];
								if ( $total_carbon < 0.00001 ) {
									$total_carbon = \GreenMetrics\GreenMetrics_Calculator::calculate_carbon_emissions( $stats['total_data_transfer'] );
								}

									// Convert carbon to equivalent values
									$carbon_kg    = $total_carbon / 1000; // Convert g to kg
									$tree_seconds = $carbon_kg * 4500; // 1 tree absorbs ~8 kg CO2 per year (4500 seconds to absorb 1g)

									// Format the time in appropriate units
								if ( $tree_seconds < 60 ) {
									// Less than a minute, show seconds
									$tree_time = number_format( $tree_seconds, 1 ) . ' ' . esc_html__( 'seconds', 'greenmetrics' );
								} elseif ( $tree_seconds < 3600 ) {
									// Less than an hour, show minutes
									$tree_minutes = $tree_seconds / 60;
									$tree_time    = number_format( $tree_minutes, 1 ) . ' ' . esc_html__( 'minutes', 'greenmetrics' );
								} elseif ( $tree_seconds < 86400 ) {
									// Less than a day, show hours
									$tree_hours = $tree_seconds / 3600;
									$tree_time  = number_format( $tree_hours, 1 ) . ' ' . esc_html__( 'hours', 'greenmetrics' );
								} else {
									// Show days
									$tree_days = $tree_seconds / 86400;
									$tree_time = number_format( $tree_days, 1 ) . ' ' . esc_html__( 'days', 'greenmetrics' );
								}

									printf(
										esc_html__( 'Your website has produced %1$s g of CO2, which would take a tree approximately %2$s to absorb.', 'greenmetrics' ),
										'<strong>' . \GreenMetrics\GreenMetrics_Calculator::format_carbon_emissions( $total_carbon ) . '</strong>',
										'<strong>' . $tree_time . '</strong>'
									);
									?>
							</p>
						</div>
					</div>
					<div class="context-item energy">
						<div class="context-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="64" height="64">
								<path fill="currentColor" d="M12,2A7,7 0 0,1 19,9C19,11.38 17.81,13.47 16,14.74V17A1,1 0 0,1 15,18H9A1,1 0 0,1 8,17V14.74C6.19,13.47 5,11.38 5,9A7,7 0 0,1 12,2M9,21V20H15V21A1,1 0 0,1 14,22H10A1,1 0 0,1 9,21M12,4A5,5 0 0,0 7,9C7,11.05 8.23,12.81 10,13.58V16H14V13.58C15.77,12.81 17,11.05 17,9A5,5 0 0,0 12,4Z" />
							</svg>
						</div>
						<div class="context-content">
							<h3><?php esc_html_e( 'Energy Consumption Impact', 'greenmetrics' ); ?></h3>
							<p>
								<?php
									// Get the correctly calculated energy consumption value
									$total_energy = $stats['total_energy_consumption'];
								if ( $total_energy < 0.00001 ) {
									$total_energy = \GreenMetrics\GreenMetrics_Calculator::calculate_energy_consumption( $stats['total_data_transfer'] );
								}

									// Convert energy to equivalent values
									$energy_kwh      = $total_energy;
									$lightbulb_hours = $energy_kwh * 100; // 10W LED bulb runs for ~100 hours on 1 kWh

									// Format the time in appropriate units
								if ( $lightbulb_hours < 1 ) {
									// Less than an hour, show minutes
									$lightbulb_minutes = $lightbulb_hours * 60;
									$lightbulb_time    = number_format( $lightbulb_minutes, 1 ) . ' ' . esc_html__( 'minutes', 'greenmetrics' );
								} else {
									// Show hours
									$lightbulb_time = number_format( $lightbulb_hours, 1 ) . ' ' . esc_html__( 'hours', 'greenmetrics' );
								}

									printf(
										esc_html__( 'Your website has consumed %1$s of energy, equivalent to running a 10W LED light bulb for %2$s.', 'greenmetrics' ),
										'<strong>' . \GreenMetrics\GreenMetrics_Calculator::format_energy_consumption( $energy_kwh ) . '</strong>',
										'<strong>' . $lightbulb_time . '</strong>'
									);
									?>
							</p>
						</div>
					</div>
				</div>
				
				<!-- Total Metrics Section -->
				<h3><?php esc_html_e( 'Total Website Impact', 'greenmetrics' ); ?></h3>
				<div class="greenmetrics-stats-grid">
					<div class="greenmetrics-stat-card total">
						<h4><?php esc_html_e( 'Total Carbon Footprint', 'greenmetrics' ); ?></h4>
						<div class="greenmetrics-stat-value" id="total-carbon-footprint">
							<?php
							// If total_carbon_footprint is too small, recalculate it
							$total_carbon = $stats['total_carbon_footprint'];
							if ( $total_carbon < 0.00001 ) {
								// Recalculate carbon footprint using Calculator
								$total_carbon = \GreenMetrics\GreenMetrics_Calculator::calculate_carbon_emissions( $stats['total_data_transfer'] );
							}
							echo esc_html( \GreenMetrics\GreenMetrics_Calculator::format_carbon_emissions( $total_carbon ) );
							?>
						</div>
					</div>
					<div class="greenmetrics-stat-card total">
						<h4><?php esc_html_e( 'Total Energy Consumption', 'greenmetrics' ); ?></h4>
						<div class="greenmetrics-stat-value" id="total-energy-consumption">
							<?php
							// If total_energy_consumption is too small, recalculate it
							$total_energy = $stats['total_energy_consumption'];
							if ( $total_energy < 0.00001 ) {
								// Recalculate energy consumption using Calculator
								$total_energy = \GreenMetrics\GreenMetrics_Calculator::calculate_energy_consumption( $stats['total_data_transfer'] );
							}
							echo esc_html( \GreenMetrics\GreenMetrics_Calculator::format_energy_consumption( $total_energy ) );
							?>
						</div>
					</div>
					<div class="greenmetrics-stat-card total">
						<h4><?php esc_html_e( 'Total Data Transfer', 'greenmetrics' ); ?></h4>
						<div class="greenmetrics-stat-value" id="total-data-transfer">
							<?php echo esc_html( \GreenMetrics\GreenMetrics_Calculator::format_data_transfer( $stats['total_data_transfer'] ) ); ?>
						</div>
					</div>
					<div class="greenmetrics-stat-card total">
						<h4><?php esc_html_e( 'Total HTTP Requests', 'greenmetrics' ); ?></h4>
						<div class="greenmetrics-stat-value" id="total-requests">
							<?php echo esc_html( number_format( $stats['total_requests'] ) ); ?>
						</div>
					</div>
					<div class="greenmetrics-stat-card total">
						<h4><?php esc_html_e( 'Total Views', 'greenmetrics' ); ?></h4>
						<div class="greenmetrics-stat-value" id="total-views"><?php echo esc_html( number_format( $stats['total_views'] ) ); ?></div>
					</div>
				</div>

				<!-- Per-Page Average Metrics Section -->
				<h3><?php esc_html_e( 'Per-Page Average Impact', 'greenmetrics' ); ?></h3>
				<div class="greenmetrics-stats-grid">
					<div class="greenmetrics-stat-card average">
						<h4><?php esc_html_e( 'Avg. Carbon Footprint', 'greenmetrics' ); ?></h4>
						<div class="greenmetrics-stat-value" id="avg-carbon-footprint">
							<?php
								$avg_carbon = $stats['total_views'] > 0 ? $stats['total_carbon_footprint'] / $stats['total_views'] : 0;
								// If avg_carbon is too small or zero, recalculate it
							if ( $avg_carbon < 0.00001 ) {
								// Calculate average data transfer per page
								$avg_data_transfer = $stats['total_views'] > 0 ? $stats['total_data_transfer'] / $stats['total_views'] : 0;
								// Recalculate carbon footprint using Calculator
								$avg_carbon = \GreenMetrics\GreenMetrics_Calculator::calculate_carbon_emissions( $avg_data_transfer );
							}
								echo esc_html( \GreenMetrics\GreenMetrics_Calculator::format_carbon_emissions( $avg_carbon ) );
							?>
						</div>
					</div>
					<div class="greenmetrics-stat-card average">
						<h4><?php esc_html_e( 'Avg. Energy Consumption', 'greenmetrics' ); ?></h4>
						<div class="greenmetrics-stat-value" id="avg-energy-consumption">
							<?php
								$avg_energy = $stats['total_views'] > 0 ? $stats['total_energy_consumption'] / $stats['total_views'] : 0;
								// If avg_energy is too small or zero, recalculate it
							if ( $avg_energy < 0.00001 ) {
								// Calculate average data transfer per page
								$avg_data_transfer = $stats['total_views'] > 0 ? $stats['total_data_transfer'] / $stats['total_views'] : 0;
								// Recalculate energy consumption using Calculator
								$avg_energy = \GreenMetrics\GreenMetrics_Calculator::calculate_energy_consumption( $avg_data_transfer );
							}
								echo esc_html( \GreenMetrics\GreenMetrics_Calculator::format_energy_consumption( $avg_energy ) );
							?>
						</div>
					</div>
					<div class="greenmetrics-stat-card average">
						<h4><?php esc_html_e( 'Avg. Data Transfer', 'greenmetrics' ); ?></h4>
						<div class="greenmetrics-stat-value" id="avg-data-transfer">
							<?php
								$avg_data = $stats['total_views'] > 0 ? $stats['total_data_transfer'] / $stats['total_views'] : 0;
								echo esc_html( \GreenMetrics\GreenMetrics_Calculator::format_data_transfer( $avg_data ) );
							?>
						</div>
					</div>
					<div class="greenmetrics-stat-card average">
						<h4><?php esc_html_e( 'Avg. HTTP Requests', 'greenmetrics' ); ?></h4>
						<div class="greenmetrics-stat-value" id="avg-requests">
							<?php
								$avg_requests = $stats['total_views'] > 0 ? $stats['total_requests'] / $stats['total_views'] : 0;
								echo esc_html( number_format( $avg_requests, 1 ) );
							?>
						</div>
					</div>
					<div class="greenmetrics-stat-card average">
						<h4><?php esc_html_e( 'Avg. Load Time', 'greenmetrics' ); ?></h4>
						<div class="greenmetrics-stat-value" id="avg-load-time">
							<?php
								$avg_load_time = floatval( $stats['avg_load_time'] );
								// Ensure it's a positive number
								$avg_load_time = max( 0, $avg_load_time );

								echo esc_html( \GreenMetrics\GreenMetrics_Calculator::format_load_time( $avg_load_time ) );
							?>
						</div>
					</div>
					<div class="greenmetrics-stat-card average">
						<h4><?php esc_html_e( 'Median Load Time', 'greenmetrics' ); ?></h4>
						<div class="greenmetrics-stat-value" id="median-load-time">
							<?php
								$median_load_time = isset( $stats['median_load_time'] ) ? floatval( $stats['median_load_time'] ) : $avg_load_time;
								// Ensure it's a positive number
								$median_load_time = max( 0, $median_load_time );

								echo esc_html( \GreenMetrics\GreenMetrics_Calculator::format_load_time( $median_load_time ) );
							?>
						</div>
					</div>
					<div class="greenmetrics-stat-card average">
						<h4><?php esc_html_e( 'Performance Score', 'greenmetrics' ); ?></h4>
						<div class="greenmetrics-stat-value" id="performance-score">
							<?php
								// Ensure performance score is within bounds
								$performance_score = floatval( $stats['avg_performance_score'] );
							if ( $performance_score > 100 || $performance_score < 0 ) {
								if ( $stats['avg_load_time'] > 0 ) {
									// Use the same calculation as in the tracker
									$tracker = \GreenMetrics\GreenMetrics_Tracker::get_instance();
									if ( method_exists( $tracker, 'calculate_performance_score' ) ) {
										$performance_score = $tracker->calculate_performance_score( $stats['avg_load_time'] );
									} else {
										$performance_score = max( 0, min( 100, 100 - ( $stats['avg_load_time'] * 10 ) ) );
									}
								} else {
									$performance_score = 100; // If no load time data, assume perfect score
								}
							}
								// Display with 2 decimal places for precision (matching frontend)
								echo esc_html( number_format( $performance_score, 2 ) );
							?>
							%
						</div>
					</div>
				</div>
			</div>

			<div class="greenmetrics-admin-settings">
				<h2><?php esc_html_e( 'Settings', 'greenmetrics' ); ?></h2>
				
				<!-- Tracking Settings Section -->
				<form method="post" action="options.php">
					<?php
					settings_fields( 'greenmetrics_settings' );
					do_settings_sections( 'greenmetrics' );
					submit_button();
					?>
				</form>
				
				<!-- Display Settings Link -->
				<div style="margin: 20px 0;">
					<p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=greenmetrics_display' ) ); ?>" class="button">
							<span class="dashicons dashicons-visibility" style="vertical-align: middle; margin-top: -2px; margin-right: 5px;"></span>
							<?php esc_html_e( 'Configure Display Settings', 'greenmetrics' ); ?>
						</a>
					</p>
					<p class="description"><?php esc_html_e( 'Configure how the eco-friendly badge appears on your website.', 'greenmetrics' ); ?></p>
				</div>
				
				<!-- Statistics Cache Section -->
				<div class="greenmetrics-refresh-stats" style="margin-top: 25px; border-top: 1px solid #eee; padding-top: 20px;">
					<h3><?php esc_html_e( 'Statistics Cache', 'greenmetrics' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Statistics are automatically cached for better performance. Use this button to refresh the statistics from the database if needed.', 'greenmetrics' ); ?></p>
					<form method="post">
						<?php wp_nonce_field( 'greenmetrics_refresh_stats', 'greenmetrics_refresh_nonce' ); ?>
						<input type="hidden" name="action" value="refresh_stats">
						<button type="submit" class="button button-secondary">
							<span class="dashicons dashicons-update" style="vertical-align: middle; margin-top: -3px;"></span>
							<?php esc_html_e( 'Refresh Statistics', 'greenmetrics' ); ?>
						</button>
					</form>
				</div>
			</div>
			
			<!-- Optimization Suggestions -->
			<div class="greenmetrics-admin-card optimization-suggestions">
				<h2><?php esc_html_e( 'Optimization Suggestions', 'greenmetrics' ); ?></h2>
				<ul class="optimization-list">
					<?php
					// Calculate average data per page
					$avg_data_per_page = $stats['total_views'] > 0 ? $stats['total_data_transfer'] / $stats['total_views'] : 0;

					// Calculate average requests per page
					$avg_requests_per_page = $stats['total_views'] > 0 ? $stats['total_requests'] / $stats['total_views'] : 0;

					// Get performance score
					$performance_score = floatval( $stats['avg_performance_score'] );
					?>
					<li class="optimization-item <?php echo ( $avg_data_per_page > 500 * 1024 ) ? 'needs-improvement' : 'good-status'; ?>">
						<div class="optimization-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
								<path fill="currentColor" d="M17,7H7A5,5 0 0,0 2,12A5,5 0 0,0 7,17H17A5,5 0 0,0 22,12A5,5 0 0,0 17,7M7,15A3,3 0 0,1 4,12A3,3 0 0,1 7,9H17A3,3 0 0,1 20,12A3,3 0 0,1 17,15H7Z" />
							</svg>
						</div>
						<div class="optimization-content">
							<h4><?php esc_html_e( 'Page Size', 'greenmetrics' ); ?></h4>
							<?php if ( $avg_data_per_page > 500 * 1024 ) : ?>
							<p>
								<?php
								printf(
									esc_html__( 'Your average page size is %s which is quite large. Consider these optimizations:', 'greenmetrics' ),
									'<strong>' . \GreenMetrics\GreenMetrics_Calculator::format_data_transfer( $avg_data_per_page ) . '</strong>'
								);
								?>
							</p>
							<ul class="optimization-tips">
								<li><?php esc_html_e( 'Compress and optimize images', 'greenmetrics' ); ?></li>
								<li><?php esc_html_e( 'Minify CSS and JavaScript files', 'greenmetrics' ); ?></li>
								<li><?php esc_html_e( 'Enable GZIP compression on your server', 'greenmetrics' ); ?></li>
								<li><?php esc_html_e( 'Remove unnecessary plugins that add bulk', 'greenmetrics' ); ?></li>
							</ul>
							<?php else : ?>
							<p>
								<?php
								printf(
									esc_html__( 'Your average page size is %s which is excellent! Small page sizes reduce energy consumption and carbon footprint.', 'greenmetrics' ),
									'<strong>' . \GreenMetrics\GreenMetrics_Calculator::format_data_transfer( $avg_data_per_page ) . '</strong>'
								);
								?>
							</p>
							<?php endif; ?>
						</div>
					</li>
					
					<li class="optimization-item <?php echo ( $avg_requests_per_page > 30 ) ? 'needs-improvement' : 'good-status'; ?>">
						<div class="optimization-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
								<path fill="currentColor" d="M16.5,6V17.5A4,4 0 0,1 12.5,21.5A4,4 0 0,1 8.5,17.5V5A2.5,2.5 0 0,1 11,2.5A2.5,2.5 0 0,1 13.5,5V15.5A1,1 0 0,1 12.5,16.5A1,1 0 0,1 11.5,15.5V6H10V15.5A2.5,2.5 0 0,0 12.5,18A2.5,2.5 0 0,0 15,15.5V5A4,4 0 0,0 11,1A4,4 0 0,0 7,5V17.5A5.5,5.5 0 0,0 12.5,23A5.5,5.5 0 0,0 18,17.5V6H16.5Z" />
							</svg>
						</div>
						<div class="optimization-content">
							<h4><?php esc_html_e( 'HTTP Requests', 'greenmetrics' ); ?></h4>
							<?php if ( $avg_requests_per_page > 30 ) : ?>
							<p>
								<?php
								printf(
									esc_html__( 'Your pages make an average of %s HTTP requests, which is high. Try these tips:', 'greenmetrics' ),
									'<strong>' . number_format( $avg_requests_per_page, 1 ) . '</strong>'
								);
								?>
							</p>
							<ul class="optimization-tips">
								<li><?php esc_html_e( 'Combine multiple CSS/JS files', 'greenmetrics' ); ?></li>
								<li><?php esc_html_e( 'Use CSS sprites for small images', 'greenmetrics' ); ?></li>
								<li><?php esc_html_e( 'Implement lazy loading for images and videos', 'greenmetrics' ); ?></li>
								<li><?php esc_html_e( 'Disable unnecessary third-party scripts', 'greenmetrics' ); ?></li>
							</ul>
							<?php else : ?>
							<p>
								<?php
								printf(
									esc_html__( 'Your pages make an average of %s HTTP requests, which is very good! Fewer HTTP requests means faster loading times and less energy usage.', 'greenmetrics' ),
									'<strong>' . number_format( $avg_requests_per_page, 1 ) . '</strong>'
								);
								?>
							</p>
							<?php endif; ?>
						</div>
					</li>

					<li class="optimization-item <?php echo ( $performance_score < 90 ) ? 'needs-improvement' : 'good-status'; ?>">
						<div class="optimization-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
								<path fill="currentColor" d="M12,16A3,3 0 0,1 9,13C9,11.88 9.61,10.9 10.5,10.39L20.21,4.77L14.68,14.35C14.18,15.33 13.17,16 12,16M12,3C13.81,3 15.5,3.5 16.97,4.32L14.87,5.53C14,5.19 13,5 12,5A8,8 0 0,0 4,13C4,15.21 4.89,17.21 6.34,18.65H6.35C6.74,19.04 6.74,19.67 6.35,20.06C5.96,20.45 5.32,20.45 4.93,20.07V20.07C3.12,18.26 2,15.76 2,13A10,10 0 0,1 12,3M22,13C22,15.76 20.88,18.26 19.07,20.07V20.07C18.68,20.45 18.05,20.45 17.66,20.06C17.27,19.67 17.27,19.04 17.66,18.65V18.65C19.11,17.2 20,15.21 20,13C20,12 19.81,11 19.46,10.1L20.67,8C21.5,9.5 22,11.18 22,13Z" />
							</svg>
						</div>
						<div class="optimization-content">
							<h4><?php esc_html_e( 'Performance Score', 'greenmetrics' ); ?></h4>
							<?php if ( $performance_score < 90 ) : ?>
							<p>
								<?php
								printf(
									esc_html__( 'Your performance score is %s which could be improved. Try these optimizations:', 'greenmetrics' ),
									'<strong>' . number_format( $performance_score, 2 ) . '%</strong>'
								);
								?>
							</p>
							<ul class="optimization-tips">
								<li><?php esc_html_e( 'Enable browser caching', 'greenmetrics' ); ?></li>
								<li><?php esc_html_e( 'Optimize server response time', 'greenmetrics' ); ?></li>
								<li><?php esc_html_e( 'Prioritize visible content', 'greenmetrics' ); ?></li>
								<li><?php esc_html_e( 'Defer non-critical JavaScript', 'greenmetrics' ); ?></li>
							</ul>
							<?php else : ?>
							<p>
								<?php
								printf(
									esc_html__( 'Your performance score is %s which is excellent! High performance means better user experience and less energy consumption.', 'greenmetrics' ),
									'<strong>' . number_format( $performance_score, 2 ) . '%</strong>'
								);
								?>
							</p>
							<?php endif; ?>
						</div>
					</li>

					<?php
					// Get dynamic optimization suggestions from Calculator class
					$avg_page_bytes      = $stats['total_views'] > 0 ? $stats['total_data_transfer'] / $stats['total_views'] : 0;
					$dynamic_suggestions = \GreenMetrics\GreenMetrics_Calculator::get_optimization_suggestions( $avg_page_bytes );

					// Display dynamic suggestions
					foreach ( $dynamic_suggestions as $suggestion ) :
						$priority_class = ( $suggestion['priority'] === 'high' ) ? 'needs-improvement' : 'good-status';
						?>
					<li class="optimization-item <?php echo esc_attr( $priority_class ); ?>">
						<div class="optimization-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
								<?php echo isset( $suggestion['icon'] ) ? $suggestion['icon'] : '<path fill="currentColor" d="M12,2L1,21H23M12,6L19.53,19H4.47M11,10V14H13V10M11,16V18H13V16" />'; ?>
							</svg>
						</div>
						<div class="optimization-content">
							<h4><?php echo esc_html( $suggestion['title'] ); ?></h4>
							<p><?php echo esc_html( $suggestion['description'] ); ?></p>
						</div>
					</li>
					<?php endforeach; ?>
					
					<li class="optimization-item good-status">
						<div class="optimization-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
								<path fill="currentColor" d="M4,1H20A1,1 0 0,1 21,2V6A1,1 0 0,1 20,7H4A1,1 0 0,1 3,6V2A1,1 0 0,1 4,1M4,9H20A1,1 0 0,1 21,10V14A1,1 0 0,1 20,15H4A1,1 0 0,1 3,14V10A1,1 0 0,1 4,9M4,17H20A1,1 0 0,1 21,18V22A1,1 0 0,1 20,23H4A1,1 0 0,1 3,22V18A1,1 0 0,1 4,17M9,5H10V3H9V5M9,13H10V11H9V13M9,21H10V19H9V21M5,3V5H7V3H5M5,11V13H7V11H5M5,19V21H7V19H5Z" />
							</svg>
						</div>
						<div class="optimization-content">
							<h4><?php esc_html_e( 'Green Hosting', 'greenmetrics' ); ?></h4>
							<p><?php esc_html_e( 'Consider using a hosting provider powered by renewable energy to further reduce your carbon footprint.', 'greenmetrics' ); ?></p>
							<ul class="optimization-tips">
								<li><?php esc_html_e( 'Look for hosts that use 100% renewable energy', 'greenmetrics' ); ?></li>
								<li><?php esc_html_e( 'Check for carbon offset programs', 'greenmetrics' ); ?></li>
								<li><?php esc_html_e( 'Consider data center location for efficiency', 'greenmetrics' ); ?></li>
							</ul>
						</div>
					</li>
				</ul>
			</div>
		</div>
	</div>
</div> 