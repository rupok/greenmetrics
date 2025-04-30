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
	<div class="greenmetrics-admin-container">
		<div class="greenmetrics-admin-header">
			<div class="header-content">
				<img src="<?php echo esc_url( GREENMETRICS_PLUGIN_URL . 'includes/admin/img/greenmetrics-icon.png' ); ?>" alt="<?php esc_attr_e( 'GreenMetrics Icon', 'greenmetrics' ); ?>" />
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			</div>
			<span class="version">
			<?php
			/* translators: %s: Plugin version number */
			echo esc_html( sprintf( __( 'GreenMetrics v%s', 'greenmetrics' ), GREENMETRICS_VERSION ) );
			?>
			</span>
		</div>

		<div class="greenmetrics-admin-content">
			<div class="greenmetrics-admin-stats">
				<h2><?php esc_html_e( 'Website Environmental Metrics', 'greenmetrics' ); ?></h2>

				<!-- Environmental Impact Context Section with visuals -->
				<div class="greenmetrics-environmental-context">
					<div class="context-item carbon">
						<div class="context-icon">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120.96 122.88"><defs></defs><path fill="currentColor" d="M54,6.38c-1.44.17-2.86.39-4.26.67a51.87,51.87,0,0,0-10.21,3.16,57.53,57.53,0,0,0-9.31,5,52.46,52.46,0,0,0-8,6.58h0a52.46,52.46,0,0,0-6.58,8,57.53,57.53,0,0,0-5,9.31l-.06.14a51,51,0,0,0-2.09,5.9C7.2,44.77,6,44.35,4.78,43.91a8.82,8.82,0,0,0-2-.68,56.33,56.33,0,0,1,2.25-6.3l.06-.16A63.12,63.12,0,0,1,10.7,26.5,59.73,59.73,0,0,1,26.91,10.29,63.12,63.12,0,0,1,37.18,4.71h0a58.07,58.07,0,0,1,11.4-3.53,62.58,62.58,0,0,1,24.19,0A58.28,58.28,0,0,1,84,4.65l.16.06a63.12,63.12,0,0,1,10.27,5.58A59.73,59.73,0,0,1,110.67,26.5a63.12,63.12,0,0,1,5.58,10.27v0a58.47,58.47,0,0,1,3.54,11.39,63.15,63.15,0,0,1,0,24.2,57.53,57.53,0,0,1-3.48,11.24l-.06.16a63.12,63.12,0,0,1-5.58,10.27,59.73,59.73,0,0,1-16.21,16.21,63.12,63.12,0,0,1-10.27,5.58h0a58.61,58.61,0,0,1-10.8,3.41c-.23-.88-.52-1.8-.85-2.8-.4-1.22-.73-2.15-1-2.93l.12,0a51.66,51.66,0,0,0,10.21-3.16,57.53,57.53,0,0,0,9.31-5,52.46,52.46,0,0,0,8-6.58h0a52.46,52.46,0,0,0,6.58-8,57.53,57.53,0,0,0,5-9.31l.06-.14a51.76,51.76,0,0,0,3.1-10.07A55.18,55.18,0,0,0,115,60.28a56,56,0,0,0-.58-8.15c-1.21,2.44-1.57,9.19-1.57,10.65l-3,.92h-.45L106.88,56l-1.95-6.16-6.6,11.87-2.19,1.38-2.28-12.8-4.34-3.08L77.2,45.05c2.87,7.42,12.85,13.71-3,17,1.27,3.82,8.71.55,1.66,9.77q-3.37,4.4-5.3,7.36l1.13,8.23c.12.91-.49,1.85-1.93,2.8l-.09.06C67,65.3,50.14,57.08,31.2,51.49a10.53,10.53,0,0,1,4.32-5.85c4.56-6.57,7.92-8.34,15.3-8.34v4.44l6.27,2.75.59-2.75A10.16,10.16,0,0,1,61,43c1.77,1.14,6.71,1.55,7.19-1.21A21.33,21.33,0,0,0,68.51,38l-5,.45C61.2,33.62,55.4,34.19,49,27.49,27.35,35.27,39.4,30,39.4,23.05l9.26-4.23c-.83-7.31-.92-.56-2.28-7.19a46.69,46.69,0,0,0,6-4.05c.59-.46,1.15-.86,1.66-1.2ZM73,7.34c-.44-.11-.89-.2-1.34-.29-1.67-.33-3.38-.59-5.11-.77A10.62,10.62,0,0,0,73,7.34Z"/><path fill="currentColor" d="M13.07,90.72a48.8,48.8,0,0,1-4.41-8.06,53.36,53.36,0,0,1-3-8.84l10.06,4.81c.17,4.62-2.46,7.34-2.69,12.09Z"/><path d="M13.07,90.72a48.8,48.8,0,0,1-4.41-8.06,53.36,53.36,0,0,1-3-8.84l10.06,4.81c.17,4.62-2.46,7.34-2.69,12.09Z"/><path fill="currentColor" d="M60,111.45c2.83,4.06,3,3.86,4.29,7.68s1.72,5.43-1.21,1.51c-2.74-3.66-3-3.63-6.83-7l-.89.16C19.85,119.57-5.3,102.83,1,51.71,27.8,62,66.4,59.52,60.78,107.58a13.06,13.06,0,0,1-.79,3.87Zm-9.2-8C42.57,84,20,76.71,9.17,63.27c11.46,24.33,18.18,23.5,41.62,40.15Z"/></svg>
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
										/* translators: %1$s: Amount of CO2 produced, %2$s: Time it takes a tree to absorb */
										esc_html__( 'Your website has produced %1$s g of CO2, which would take a tree approximately %2$s to absorb.', 'greenmetrics' ),
										'<strong>' . esc_html( \GreenMetrics\GreenMetrics_Calculator::format_carbon_emissions( $total_carbon ) ) . '</strong>',
										'<strong>' . esc_html( $tree_time ) . '</strong>'
									);
									?>
							</p>
						</div>
					</div>
					<div class="context-item energy">
						<div class="context-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 113.79 122.88"><defs><style>.cls-energy-imp{fill-rule:evenodd;}</style></defs><path class="cls-energy-imp" fill-rule:evenodd fill="currentColor" d="M75.64,27a35.42,35.42,0,0,1,8.58,7.07A32.54,32.54,0,0,1,90,43.34h0a37.48,37.48,0,0,1,1.85,5.93,35,35,0,0,1,.24,14,38.35,38.35,0,0,1-2.16,7.3l-.11.25c-2,5-5.58,9.84-9,14.62-1.74,2.42-3.47,4.81-4.92,7.13a4.71,4.71,0,0,1-4.33,2.18L44.05,98.84a4.7,4.7,0,0,1-5.21-3.41,38.85,38.85,0,0,0-2.53-5.8,24.22,24.22,0,0,0-3-4.48C31.89,83.53,30.44,81.87,29,80a40.57,40.57,0,0,1-4.14-6.92h0a41.19,41.19,0,0,1-2.8-8,35.59,35.59,0,0,1-.95-8.42v0a35.78,35.78,0,0,1,1.17-8.73,41.74,41.74,0,0,1,3.41-8.82l.2-.36A35.1,35.1,0,0,1,33,30.09a33.5,33.5,0,0,1,9.43-5.81l.29-.11a35.14,35.14,0,0,1,8-2.13,37.61,37.61,0,0,1,8.75-.2,38.63,38.63,0,0,1,8.37,1.71A37.79,37.79,0,0,1,75.64,27Zm-3.88,87.35a17.36,17.36,0,0,1-6.26,6.28,16.36,16.36,0,0,1-7.19,2.19,14.86,14.86,0,0,1-7.39-1.44,15.07,15.07,0,0,1-4.38-3.26l25.22-3.77Zm2.4-14.11,0,1.65,0,.57a23.51,23.51,0,0,1,0,3.25l-.5,2.38-30.56,4.54-.53-1.22-1.19-4.88,0-1.42,32.7-4.87Zm-18-96.51A3.84,3.84,0,0,1,60.07,0h0l.26,0A3.89,3.89,0,0,1,62.8,1.19a3.86,3.86,0,0,1,1.06,2.69h0a1.27,1.27,0,0,1,0,.2l-.21,8.19h0a2.28,2.28,0,0,1,0,.26,3.81,3.81,0,0,1-3.86,3.52h0l-.27,0a3.77,3.77,0,0,1-2.46-1.17A3.84,3.84,0,0,1,56,12.18h0a1.27,1.27,0,0,1,0-.2l.2-8.22ZM14,18.1a3.9,3.9,0,0,1-1.22-2.67,3.83,3.83,0,0,1,3.69-4,3.84,3.84,0,0,1,2.75,1l6.14,5.73a3.85,3.85,0,0,1,.21,5.42,3.91,3.91,0,0,1-2.68,1.22,3.82,3.82,0,0,1-2.74-1L14,18.1Zm-10,42.22A3.86,3.86,0,0,1,0,56.6a3.78,3.78,0,0,1,1-2.75,3.81,3.81,0,0,1,2.68-1.2l8.38-.28a3.83,3.83,0,0,1,4,3.71v.06h0v.14a3.86,3.86,0,0,1-1,2.55A3.81,3.81,0,0,1,12.34,60h-.15l-8.28.28ZM109.6,48.43h.13a3.84,3.84,0,0,1,2.65.85,3.91,3.91,0,0,1,1.4,2.59v0s0,.1,0,.12a3.84,3.84,0,0,1-3.44,4L102,57a3.84,3.84,0,0,1-4.21-3.42,3.84,3.84,0,0,1,3.43-4.21c2.78-.3,5.58-.62,8.37-.89ZM93.08,15.05A3.81,3.81,0,0,1,98.39,14h0A3.78,3.78,0,0,1,100,16.44a3.88,3.88,0,0,1-.57,2.88l-4.67,7A3.84,3.84,0,0,1,88.4,22l4.68-7ZM61.26,54.91h5.89a1.54,1.54,0,0,1,1.54,1.54,1.56,1.56,0,0,1-.26.86l-14,23.93a1.53,1.53,0,0,1-2.11.52,1.55,1.55,0,0,1-.72-1.63l2.07-14.68-7,.12a1.53,1.53,0,0,1-1.56-1.51,1.49,1.49,0,0,1,.21-.81L59.11,39.33a1.55,1.55,0,0,1,2.11-.54A1.52,1.52,0,0,1,62,40.33l-.7,14.58Z"/></svg>
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
									$lightbulb_time = number_format( $lightbulb_hours, 1 ) . ' ' . esc_html__( 'hours', 'greenmetrics' );
								}

									printf(
										/* translators: %1$s: Amount of energy consumed, %2$s: Time a light bulb can run */
										esc_html__( 'Your website has consumed %1$s of energy, equivalent to running a 10W LED light bulb for %2$s.', 'greenmetrics' ),
										'<strong>' . esc_html( \GreenMetrics\GreenMetrics_Calculator::format_energy_consumption( $energy_kwh ) ) . '</strong>',
										'<strong>' . esc_html( $lightbulb_time ) . '</strong>'
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

			<!-- Historical Metrics Analysis Section -->
			<div class="greenmetrics-metrics-trends">
				<h2><?php esc_html_e( 'Environmental Metrics Trends', 'greenmetrics' ); ?></h2>

				<!-- Date Range Selector -->
				<div class="greenmetrics-date-range">
					<h3 class="greenmetrics-date-range-title"><?php esc_html_e( 'Select Date Range', 'greenmetrics' ); ?></h3>
					<div class="greenmetrics-date-range-controls">
						<button class="button greenmetrics-date-btn active" data-range="7days"><?php esc_html_e( 'Last 7 days', 'greenmetrics' ); ?></button>
						<button class="button greenmetrics-date-btn" data-range="30days"><?php esc_html_e( 'Last 30 days', 'greenmetrics' ); ?></button>
						<button class="button greenmetrics-date-btn" data-range="thisMonth"><?php esc_html_e( 'This Month', 'greenmetrics' ); ?></button>

						<div class="greenmetrics-custom-date-range">
							<span><?php esc_html_e( 'Custom Range:', 'greenmetrics' ); ?></span>
							<input type="date" id="greenmetrics-start-date" name="greenmetrics-start-date" class="greenmetrics-date-input">
							<span><?php esc_html_e( 'to', 'greenmetrics' ); ?></span>
							<input type="date" id="greenmetrics-end-date" name="greenmetrics-end-date" class="greenmetrics-date-input">
							<button class="button greenmetrics-date-btn" data-range="custom" id="greenmetrics-apply-date"><?php esc_html_e( 'Apply', 'greenmetrics' ); ?></button>
						</div>
					</div>
				</div>

				<!-- Metrics Chart -->
				<div class="greenmetrics-metrics-chart">
					<div class="greenmetrics-chart-header">
						<h3><?php esc_html_e( 'Metrics Chart', 'greenmetrics' ); ?></h3>
						<button class="button greenmetrics-date-btn force-refresh" data-range="current">
							<span class="dashicons dashicons-update" style="vertical-align: middle; margin-top: -3px;"></span>
							<?php esc_html_e( 'Refresh Data', 'greenmetrics' ); ?>
						</button>
					</div>
					<div class="greenmetrics-chart-container">
						<canvas id="greenmetrics-chart"></canvas>
					</div>
					<div class="greenmetrics-chart-legend">
						<div class="greenmetrics-chart-legend-item carbon_footprint">
							<input type="checkbox" id="carbon_footprint" class="chart-toggle" checked>
							<span class="color-indicator"></span>
							<label for="carbon_footprint"><?php esc_html_e( 'Carbon Footprint (g)', 'greenmetrics' ); ?></label>
						</div>
						<div class="greenmetrics-chart-legend-item energy_consumption">
							<input type="checkbox" id="energy_consumption" class="chart-toggle" checked>
							<span class="color-indicator"></span>
							<label for="energy_consumption"><?php esc_html_e( 'Energy Consumption (kWh)', 'greenmetrics' ); ?></label>
						</div>
						<div class="greenmetrics-chart-legend-item data_transfer">
							<input type="checkbox" id="data_transfer" class="chart-toggle" checked>
							<span class="color-indicator"></span>
							<label for="data_transfer"><?php esc_html_e( 'Data Transfer (KB)', 'greenmetrics' ); ?></label>
						</div>
						<div class="greenmetrics-chart-legend-item http_requests">
							<input type="checkbox" id="http_requests" class="chart-toggle" checked>
							<span class="color-indicator"></span>
							<label for="http_requests"><?php esc_html_e( 'HTTP Requests', 'greenmetrics' ); ?></label>
						</div>
						<div class="greenmetrics-chart-legend-item page_views">
							<input type="checkbox" id="page_views" class="chart-toggle" checked>
							<span class="color-indicator"></span>
							<label for="page_views"><?php esc_html_e( 'Page Views', 'greenmetrics' ); ?></label>
						</div>
					</div>
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
									/* translators: %s: Average page size */
									esc_html__( 'Your average page size is %s which is quite large. Consider these optimizations:', 'greenmetrics' ),
									'<strong>' . esc_html( \GreenMetrics\GreenMetrics_Calculator::format_data_transfer( $avg_data_per_page ) ) . '</strong>'
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
									/* translators: %s: Average page size */
									esc_html__( 'Your average page size is %s which is excellent! Small page sizes reduce energy consumption and carbon footprint.', 'greenmetrics' ),
									'<strong>' . esc_html( \GreenMetrics\GreenMetrics_Calculator::format_data_transfer( $avg_data_per_page ) ) . '</strong>'
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
									/* translators: %s: Average number of HTTP requests */
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
									/* translators: %s: Average number of HTTP requests */
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
									/* translators: %s: Performance score percentage */
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
									/* translators: %s: Performance score percentage */
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
								<?php echo wp_kses_post( isset( $suggestion['icon'] ) ? $suggestion['icon'] : '<path fill="currentColor" d="M12,2L1,21H23M12,6L19.53,19H4.47M11,10V14H13V10M11,16V18H13V16" />' ); ?>
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