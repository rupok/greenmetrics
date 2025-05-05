<?php
/**
 * Data management admin template.
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
		'data_management_enabled' => 1,
		'aggregation_age' => 30,
		'aggregation_type' => 'daily',
		'retention_period' => 90,
		'require_aggregation_before_pruning' => 1,
	)
);

// Get data manager instance
$data_manager = \GreenMetrics\GreenMetrics_Data_Manager::get_instance();

// Get table sizes
$table_sizes = $data_manager->get_table_sizes();

// Format sizes
$main_table_size = \GreenMetrics\GreenMetrics_Data_Manager::format_bytes($table_sizes['main_table']['size']);
$aggregated_table_size = \GreenMetrics\GreenMetrics_Data_Manager::format_bytes($table_sizes['aggregated_table']['size']);
$total_size = \GreenMetrics\GreenMetrics_Data_Manager::format_bytes($table_sizes['total_size']);

?>
<div class="wrap">
	<div class="greenmetrics-admin-container">
		<div class="greenmetrics-admin-header">
			<div class="header-content">
				<img src="<?php echo esc_url( GREENMETRICS_PLUGIN_URL . 'includes/admin/img/greenmetrics-icon.png' ); ?>" alt="<?php esc_attr_e( 'GreenMetrics Icon', 'greenmetrics' ); ?>" />
				<h1><?php esc_html_e( 'GreenMetrics - Data Management', 'greenmetrics' ); ?></h1>
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

	<div class="greenmetrics-admin-content-wrapper">
		<!-- Left Column: Settings Form -->
		<div class="greenmetrics-admin-settings-column">
			<div class="greenmetrics-admin-card">
				<form method="post" action="options.php">
					<?php settings_fields( 'greenmetrics_settings' ); ?>
					<?php do_settings_sections( 'greenmetrics_data_management' ); ?>
					<?php submit_button( __( 'Save Settings', 'greenmetrics' ) ); ?>
				</form>
			</div>

			<div class="greenmetrics-admin-card">
				<h2><?php esc_html_e( 'Data Management Settings', 'greenmetrics' ); ?></h2>
				<p><?php esc_html_e( 'Configure how GreenMetrics manages your metrics data to prevent excessive database growth.', 'greenmetrics' ); ?></p>

				<form method="post" action="options.php">
					<?php settings_fields( 'greenmetrics_settings' ); ?>

					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Data Management', 'greenmetrics' ); ?></th>
							<td>
								<label class="toggle-switch">
									<input type="checkbox" id="data_management_enabled" name="greenmetrics_settings[data_management_enabled]" value="1" <?php checked( isset( $settings['data_management_enabled'] ) ? $settings['data_management_enabled'] : 1, 1 ); ?>>
									<span class="slider"></span>
								</label>
								<p class="description"><?php esc_html_e( 'Automatically aggregate and prune old metrics data to prevent excessive database growth.', 'greenmetrics' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Data Aggregation', 'greenmetrics' ); ?></th>
							<td>
								<label for="aggregation_age"><?php esc_html_e( 'Aggregate data older than', 'greenmetrics' ); ?></label>
								<input type="number" id="aggregation_age" name="greenmetrics_settings[aggregation_age]" value="<?php echo esc_attr( isset( $settings['aggregation_age'] ) ? $settings['aggregation_age'] : 30 ); ?>" min="1" max="365" class="small-text"> <?php esc_html_e( 'days', 'greenmetrics' ); ?>
								<p class="description"><?php esc_html_e( 'Individual page views older than this will be aggregated.', 'greenmetrics' ); ?></p>

								<div style="margin-top: 10px;">
									<label for="aggregation_type"><?php esc_html_e( 'Aggregation type', 'greenmetrics' ); ?></label>
									<select id="aggregation_type" name="greenmetrics_settings[aggregation_type]">
										<option value="daily" <?php selected( isset( $settings['aggregation_type'] ) ? $settings['aggregation_type'] : 'daily', 'daily' ); ?>><?php esc_html_e( 'Daily', 'greenmetrics' ); ?></option>
										<option value="weekly" <?php selected( isset( $settings['aggregation_type'] ) ? $settings['aggregation_type'] : 'daily', 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'greenmetrics' ); ?></option>
										<option value="monthly" <?php selected( isset( $settings['aggregation_type'] ) ? $settings['aggregation_type'] : 'daily', 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'greenmetrics' ); ?></option>
									</select>
									<p class="description"><?php esc_html_e( 'How to group the aggregated data.', 'greenmetrics' ); ?></p>
								</div>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Data Retention', 'greenmetrics' ); ?></th>
							<td>
								<label for="retention_period"><?php esc_html_e( 'Delete individual records older than', 'greenmetrics' ); ?></label>
								<input type="number" id="retention_period" name="greenmetrics_settings[retention_period]" value="<?php echo esc_attr( isset( $settings['retention_period'] ) ? $settings['retention_period'] : 90 ); ?>" min="1" max="3650" class="small-text"> <?php esc_html_e( 'days', 'greenmetrics' ); ?>
								<p class="description"><?php esc_html_e( 'Individual page view records older than this will be permanently deleted.', 'greenmetrics' ); ?></p>

								<div style="margin-top: 10px;">
									<label class="toggle-switch">
										<input type="checkbox" id="require_aggregation_before_pruning" name="greenmetrics_settings[require_aggregation_before_pruning]" value="1" <?php checked( isset( $settings['require_aggregation_before_pruning'] ) ? $settings['require_aggregation_before_pruning'] : 1, 1 ); ?>>
										<span class="slider"></span>
									</label>
									<label for="require_aggregation_before_pruning"><?php esc_html_e( 'Only delete data that has been aggregated', 'greenmetrics' ); ?></label>
									<p class="description"><?php esc_html_e( 'When enabled, individual records will only be deleted if they have been aggregated first.', 'greenmetrics' ); ?></p>
								</div>
							</td>
						</tr>
					</table>

					<?php submit_button( __( 'Save Settings', 'greenmetrics' ) ); ?>
				</form>
			</div>

			<div class="greenmetrics-admin-card">
				<h2><?php esc_html_e( 'Manual Data Management', 'greenmetrics' ); ?></h2>
				<p><?php esc_html_e( 'Run data management tasks manually.', 'greenmetrics' ); ?></p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="greenmetrics_run_data_management">
					<?php wp_nonce_field( 'greenmetrics_run_data_management', 'greenmetrics_data_management_nonce' ); ?>

					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Aggregate Data', 'greenmetrics' ); ?></th>
							<td>
								<button type="submit" name="run_aggregation" value="1" class="button button-secondary">
									<?php esc_html_e( 'Run Data Aggregation Now', 'greenmetrics' ); ?>
								</button>
								<p class="description"><?php esc_html_e( 'Manually run the data aggregation process using the settings above.', 'greenmetrics' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Prune Data', 'greenmetrics' ); ?></th>
							<td>
								<button type="submit" name="run_pruning" value="1" class="button button-secondary">
									<?php esc_html_e( 'Run Data Pruning Now', 'greenmetrics' ); ?>
								</button>
								<p class="description"><?php esc_html_e( 'Manually run the data pruning process using the settings above.', 'greenmetrics' ); ?></p>
							</td>
						</tr>
					</table>
				</form>
			</div>

			<div class="greenmetrics-admin-card">
				<h2><?php esc_html_e( 'Data Export', 'greenmetrics' ); ?></h2>
				<p><?php esc_html_e( 'Export your metrics data for external analysis or reporting.', 'greenmetrics' ); ?></p>

				<form id="greenmetrics-export-form" method="get" action="<?php echo esc_url( get_rest_url( null, 'greenmetrics/v1/export' ) ); ?>" target="_blank">
					<?php wp_nonce_field( 'wp_rest', '_wpnonce' ); ?>

					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Export Format', 'greenmetrics' ); ?></th>
							<td>
								<select name="format" id="export-format">
									<option value="csv"><?php esc_html_e( 'CSV (Spreadsheet)', 'greenmetrics' ); ?></option>
									<option value="json"><?php esc_html_e( 'JSON (Data)', 'greenmetrics' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Choose the format for your exported data.', 'greenmetrics' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Data Type', 'greenmetrics' ); ?></th>
							<td>
								<select name="data_type" id="export-data-type">
									<option value="raw"><?php esc_html_e( 'Raw Data (Individual Page Views)', 'greenmetrics' ); ?></option>
									<option value="aggregated"><?php esc_html_e( 'Aggregated Data (Summary Statistics)', 'greenmetrics' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Raw data includes individual page views. Aggregated data includes summary statistics by time period.', 'greenmetrics' ); ?></p>
							</td>
						</tr>
						<tr class="aggregation-type-row" style="display: none;">
							<th scope="row"><?php esc_html_e( 'Aggregation Type', 'greenmetrics' ); ?></th>
							<td>
								<select name="aggregation_type" id="export-aggregation-type">
									<option value="daily"><?php esc_html_e( 'Daily', 'greenmetrics' ); ?></option>
									<option value="weekly"><?php esc_html_e( 'Weekly', 'greenmetrics' ); ?></option>
									<option value="monthly"><?php esc_html_e( 'Monthly', 'greenmetrics' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Choose the time period for aggregated data.', 'greenmetrics' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Date Range', 'greenmetrics' ); ?></th>
							<td>
								<div class="date-range-inputs">
									<label>
										<?php esc_html_e( 'From:', 'greenmetrics' ); ?>
										<input type="date" name="start_date" id="export-start-date" value="<?php echo esc_attr( date( 'Y-m-d', strtotime( '-30 days' ) ) ); ?>">
									</label>
									<label>
										<?php esc_html_e( 'To:', 'greenmetrics' ); ?>
										<input type="date" name="end_date" id="export-end-date" value="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>">
									</label>
								</div>
								<p class="description"><?php esc_html_e( 'Choose the date range for the exported data.', 'greenmetrics' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Page Filter', 'greenmetrics' ); ?></th>
							<td>
								<select name="page_id" id="export-page-id">
									<option value="0"><?php esc_html_e( 'All Pages', 'greenmetrics' ); ?></option>
									<?php
									// Get pages with metrics
									global $wpdb;
									$table_name = $wpdb->prefix . 'greenmetrics_stats';
									$page_ids = $wpdb->get_col( "SELECT DISTINCT page_id FROM $table_name ORDER BY page_id" );

									foreach ( $page_ids as $page_id ) {
										$title = get_the_title( $page_id );
										if ( empty( $title ) ) {
											$title = __( 'Unknown Page', 'greenmetrics' ) . ' (ID: ' . $page_id . ')';
										}
										echo '<option value="' . esc_attr( $page_id ) . '">' . esc_html( $title ) . '</option>';
									}
									?>
								</select>
								<p class="description"><?php esc_html_e( 'Choose a specific page or export data for all pages.', 'greenmetrics' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Export Data', 'greenmetrics' ); ?></th>
							<td>
								<button type="submit" class="button button-primary">
									<span class="dashicons dashicons-download" style="vertical-align: middle; margin-top: -3px;"></span>
									<?php esc_html_e( 'Download Export File', 'greenmetrics' ); ?>
								</button>
								<p class="description"><?php esc_html_e( 'Download your metrics data in the selected format.', 'greenmetrics' ); ?></p>
							</td>
						</tr>
					</table>
				</form>

				<script>
					jQuery(document).ready(function($) {
						// Show/hide aggregation type based on data type
						$('#export-data-type').on('change', function() {
							if ($(this).val() === 'aggregated') {
								$('.aggregation-type-row').show();
							} else {
								$('.aggregation-type-row').hide();
							}
						});

						// Validate date range before submission
						$('#greenmetrics-export-form').on('submit', function(e) {
							const startDate = new Date($('#export-start-date').val());
							const endDate = new Date($('#export-end-date').val());

							if (startDate > endDate) {
								e.preventDefault();
								alert('<?php echo esc_js( __( 'Start date must be before end date.', 'greenmetrics' ) ); ?>');
								return false;
							}

							return true;
						});
					});
				</script>
			</div>

			<div class="greenmetrics-admin-card">
				<h2><?php esc_html_e( 'Statistics Cache', 'greenmetrics' ); ?></h2>
				<p><?php esc_html_e( 'Statistics are automatically cached for better performance. Use this button to refresh the statistics from the database if needed.', 'greenmetrics' ); ?></p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="greenmetrics_refresh_stats">
					<?php wp_nonce_field( 'greenmetrics_refresh_stats', 'greenmetrics_refresh_nonce' ); ?>

					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Refresh Statistics', 'greenmetrics' ); ?></th>
							<td>
								<button type="submit" class="button button-secondary">
									<span class="dashicons dashicons-update" style="vertical-align: middle; margin-top: -3px;"></span>
									<?php esc_html_e( 'Refresh Statistics Cache', 'greenmetrics' ); ?>
								</button>
								<p class="description"><?php esc_html_e( 'Clear the statistics cache and recalculate all metrics from the database.', 'greenmetrics' ); ?></p>
							</td>
						</tr>
					</table>
				</form>
			</div>
		</div>

		<!-- Right Column: Stats and Info -->
		<div class="greenmetrics-admin-info-column">
			<div class="greenmetrics-admin-card">
				<h2><?php esc_html_e( 'Database Usage', 'greenmetrics' ); ?></h2>

				<table class="widefat">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Table', 'greenmetrics' ); ?></th>
							<th><?php esc_html_e( 'Records', 'greenmetrics' ); ?></th>
							<th><?php esc_html_e( 'Size', 'greenmetrics' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><?php esc_html_e( 'Metrics Data', 'greenmetrics' ); ?></td>
							<td><?php echo esc_html( number_format( $table_sizes['main_table']['rows'] ) ); ?></td>
							<td><?php echo esc_html( $main_table_size ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Aggregated Data', 'greenmetrics' ); ?></td>
							<td><?php echo esc_html( number_format( $table_sizes['aggregated_table']['rows'] ) ); ?></td>
							<td><?php echo esc_html( $aggregated_table_size ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Total', 'greenmetrics' ); ?></th>
							<th><?php echo esc_html( number_format( $table_sizes['main_table']['rows'] + $table_sizes['aggregated_table']['rows'] ) ); ?></th>
							<th><?php echo esc_html( $total_size ); ?></th>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="greenmetrics-admin-card">
				<h2><?php esc_html_e( 'How Data Management Works', 'greenmetrics' ); ?></h2>

				<h3><?php esc_html_e( 'Data Aggregation', 'greenmetrics' ); ?></h3>
				<p><?php esc_html_e( 'Data aggregation combines individual page view records into summary statistics, grouped by time period (daily, weekly, or monthly) and page.', 'greenmetrics' ); ?></p>
				<p><?php esc_html_e( 'This preserves your historical metrics while significantly reducing database size.', 'greenmetrics' ); ?></p>

				<h3><?php esc_html_e( 'Data Pruning', 'greenmetrics' ); ?></h3>
				<p><?php esc_html_e( 'Data pruning permanently removes old individual page view records from the database after they\'ve been aggregated.', 'greenmetrics' ); ?></p>
				<p><?php esc_html_e( 'This prevents your database from growing too large over time.', 'greenmetrics' ); ?></p>

				<h3><?php esc_html_e( 'Automatic Scheduling', 'greenmetrics' ); ?></h3>
				<p><?php esc_html_e( 'When data management is enabled, these processes run automatically once per day via WordPress cron.', 'greenmetrics' ); ?></p>
				<p><?php esc_html_e( 'You can also run them manually using the buttons on this page.', 'greenmetrics' ); ?></p>
			</div>
		</div>
	</div>
</div>
</div>
