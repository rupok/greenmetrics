<?php
/**
 * Advanced Reports template.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get tracker instance
$tracker = \GreenMetrics\GreenMetrics_Tracker::get_instance();
$stats   = $tracker->get_stats();

// Get settings
$settings = get_option(
	'greenmetrics_settings',
	array(
		'tracking_enabled' => 1,
	)
);

// Get current date for default date range
$end_date = gmdate('Y-m-d');
$start_date = gmdate('Y-m-d', strtotime('-30 days'));

// Get pages with metrics data (with caching)
$cache_key = 'greenmetrics_pages_with_metrics';
$pages = get_transient($cache_key);

if (false === $pages) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'greenmetrics_stats';

    // Check if table exists before querying
    if (\GreenMetrics\GreenMetrics_DB_Helper::table_exists($table_name)) {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is properly escaped with esc_sql()
        $page_ids = $wpdb->get_col("SELECT DISTINCT page_id FROM `" . esc_sql($table_name) . "` WHERE page_id > 0 ORDER BY page_id");
        $pages = array();

        foreach ($page_ids as $page_id) {
            $title = get_the_title($page_id);
            if (empty($title)) {
                $title = __('Unknown Page', 'greenmetrics') . ' (ID: ' . $page_id . ')';
            }
            $pages[$page_id] = $title;
        }
    } else {
        $pages = array();
    }

    // Cache for 5 minutes
    set_transient($cache_key, $pages, 5 * MINUTE_IN_SECONDS);
}
?>

<div class="wrap">
    <div class="greenmetrics-admin-container">
        <div class="greenmetrics-admin-header">
            <div class="header-content">
                <img src="<?php echo esc_url(GREENMETRICS_PLUGIN_URL . 'includes/admin/img/greenmetrics-icon.png'); ?>" alt="<?php esc_attr_e('GreenMetrics Icon', 'greenmetrics'); ?>" />
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            </div>
            <span class="version">
                <?php
                /* translators: %s: Plugin version number */
                echo esc_html(sprintf(__('GreenMetrics v%s', 'greenmetrics'), GREENMETRICS_VERSION));
                ?>
            </span>
        </div>

        <div class="greenmetrics-admin-content">
            <!-- Report Filters -->
            <div class="greenmetrics-admin-card">
                <h2><?php esc_html_e('Report Filters', 'greenmetrics'); ?></h2>
                <div class="greenmetrics-report-filters">
                    <form id="greenmetrics-report-filters-form">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="date-range"><?php esc_html_e('Date Range', 'greenmetrics'); ?></label>
                                <select id="date-range" name="date-range">
                                    <option value="7"><?php esc_html_e('Last 7 Days', 'greenmetrics'); ?></option>
                                    <option value="30" selected><?php esc_html_e('Last 30 Days', 'greenmetrics'); ?></option>
                                    <option value="90"><?php esc_html_e('Last 90 Days', 'greenmetrics'); ?></option>
                                    <option value="365"><?php esc_html_e('Last Year', 'greenmetrics'); ?></option>
                                    <option value="custom"><?php esc_html_e('Custom Range', 'greenmetrics'); ?></option>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label for="page-filter"><?php esc_html_e('Page', 'greenmetrics'); ?></label>
                                <select id="page-filter" name="page-filter">
                                    <option value="0"><?php esc_html_e('All Pages', 'greenmetrics'); ?></option>
                                    <?php foreach ($pages as $page_id => $title) : ?>
                                        <option value="<?php echo esc_attr($page_id); ?>"><?php echo esc_html($title); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label for="comparison"><?php esc_html_e('Comparison', 'greenmetrics'); ?></label>
                                <select id="comparison" name="comparison">
                                    <option value="none"><?php esc_html_e('None', 'greenmetrics'); ?></option>
                                    <option value="previous"><?php esc_html_e('Previous Period', 'greenmetrics'); ?></option>
                                </select>
                            </div>
                        </div>

                        <!-- Custom Date Range Row (hidden by default) -->
                        <div class="filter-row custom-date-range" style="display: none;">
                            <div class="filter-group date-range-field">
                                <label for="start-date"><?php esc_html_e('Start Date', 'greenmetrics'); ?></label>
                                <input type="date" id="start-date" name="start-date" value="<?php echo esc_attr($start_date); ?>">
                            </div>

                            <div class="filter-group date-range-field">
                                <label for="end-date"><?php esc_html_e('End Date', 'greenmetrics'); ?></label>
                                <input type="date" id="end-date" name="end-date" value="<?php echo esc_attr($end_date); ?>">
                            </div>
                        </div>

                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="chart-type"><?php esc_html_e('Chart Type', 'greenmetrics'); ?></label>
                                <select id="chart-type" name="chart-type">
                                    <option value="line"><?php esc_html_e('Line Chart', 'greenmetrics'); ?></option>
                                    <option value="bar"><?php esc_html_e('Bar Chart', 'greenmetrics'); ?></option>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label for="metric-focus"><?php esc_html_e('Metric Focus', 'greenmetrics'); ?></label>
                                <select id="metric-focus" name="metric-focus">
                                    <option value="all"><?php esc_html_e('All Metrics', 'greenmetrics'); ?></option>
                                    <option value="carbon_footprint"><?php esc_html_e('Carbon Footprint', 'greenmetrics'); ?></option>
                                    <option value="energy_consumption"><?php esc_html_e('Energy Consumption', 'greenmetrics'); ?></option>
                                    <option value="data_transfer"><?php esc_html_e('Data Transfer', 'greenmetrics'); ?></option>
                                    <option value="requests"><?php esc_html_e('HTTP Requests', 'greenmetrics'); ?></option>
                                    <option value="page_views"><?php esc_html_e('Page Views', 'greenmetrics'); ?></option>
                                </select>
                            </div>

                            <div class="filter-group">
                                <button type="submit" class="button button-primary" id="apply-filters">
                                    <span class="dashicons dashicons-filter" style="vertical-align: middle; margin-top: -3px;"></span>
                                    <?php esc_html_e('Apply Filters', 'greenmetrics'); ?>
                                </button>

                                <button type="button" class="button" id="save-report">
                                    <span class="dashicons dashicons-saved" style="vertical-align: middle; margin-top: -3px;"></span>
                                    <?php esc_html_e('Save Report', 'greenmetrics'); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Main Report Chart -->
            <div class="greenmetrics-admin-card">
                <div class="greenmetrics-report-header">
                    <h2 id="report-title"><?php esc_html_e('Performance Metrics', 'greenmetrics'); ?></h2>
                    <div class="report-actions">
                        <button class="button" id="export-chart-image">
                            <span class="dashicons dashicons-format-image" style="vertical-align: middle; margin-top: -3px;"></span>
                            <?php esc_html_e('Export Image', 'greenmetrics'); ?>
                        </button>
                        <button class="button" id="export-chart-data">
                            <span class="dashicons dashicons-media-spreadsheet" style="vertical-align: middle; margin-top: -3px;"></span>
                            <?php esc_html_e('Export Data', 'greenmetrics'); ?>
                        </button>
                    </div>
                </div>

                <div class="greenmetrics-report-chart-container">
                    <canvas id="greenmetrics-report-chart"></canvas>
                    <div class="chart-loading" style="display: none;">
                        <span class="spinner is-active"></span>
                        <p><?php esc_html_e('Loading chart data...', 'greenmetrics'); ?></p>
                    </div>
                </div>

                <div class="greenmetrics-chart-legend" id="report-chart-legend">
                    <!-- Legend will be populated by JavaScript -->
                </div>
            </div>

            <!-- Performance Summary -->
            <div class="greenmetrics-admin-card">
                <h2><?php esc_html_e('Performance Summary', 'greenmetrics'); ?></h2>
                <div class="greenmetrics-summary-grid" id="performance-summary">
                    <!-- Summary will be populated by JavaScript -->
                    <div class="summary-loading">
                        <span class="spinner is-active"></span>
                        <p><?php esc_html_e('Loading summary data...', 'greenmetrics'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Page Performance Report -->
            <div class="greenmetrics-admin-card">
                <h2><?php esc_html_e('Page Performance Report', 'greenmetrics'); ?></h2>
                <div class="greenmetrics-tabs">
                    <div class="tab-navigation">
                        <button class="tab-button active" data-tab="top-pages"><?php esc_html_e('Top Performing Pages', 'greenmetrics'); ?></button>
                        <button class="tab-button" data-tab="worst-pages"><?php esc_html_e('Pages Needing Improvement', 'greenmetrics'); ?></button>
                        <button class="tab-button" data-tab="most-viewed"><?php esc_html_e('Most Viewed Pages', 'greenmetrics'); ?></button>
                    </div>

                    <div class="tab-content">
                        <div class="tab-pane active" id="top-pages">
                            <div class="page-performance-table-container">
                                <table class="greenmetrics-table" id="top-pages-table">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('Page', 'greenmetrics'); ?></th>
                                            <th><?php esc_html_e('Views', 'greenmetrics'); ?></th>
                                            <th><?php esc_html_e('Carbon Footprint', 'greenmetrics'); ?></th>
                                            <th><?php esc_html_e('Energy Consumption', 'greenmetrics'); ?></th>
                                            <th><?php esc_html_e('Data Transfer', 'greenmetrics'); ?></th>
                                            <th><?php esc_html_e('Performance Score', 'greenmetrics'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Will be populated by JavaScript -->
                                        <tr class="loading-row">
                                            <td colspan="6" class="loading-cell">
                                                <span class="spinner is-active"></span>
                                                <p><?php esc_html_e('Loading page data...', 'greenmetrics'); ?></p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane" id="worst-pages">
                            <div class="page-performance-table-container">
                                <table class="greenmetrics-table" id="worst-pages-table">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('Page', 'greenmetrics'); ?></th>
                                            <th><?php esc_html_e('Views', 'greenmetrics'); ?></th>
                                            <th><?php esc_html_e('Carbon Footprint', 'greenmetrics'); ?></th>
                                            <th><?php esc_html_e('Energy Consumption', 'greenmetrics'); ?></th>
                                            <th><?php esc_html_e('Data Transfer', 'greenmetrics'); ?></th>
                                            <th><?php esc_html_e('Performance Score', 'greenmetrics'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Will be populated by JavaScript -->
                                        <tr class="loading-row">
                                            <td colspan="6" class="loading-cell">
                                                <span class="spinner is-active"></span>
                                                <p><?php esc_html_e('Loading page data...', 'greenmetrics'); ?></p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane" id="most-viewed">
                            <div class="page-performance-table-container">
                                <table class="greenmetrics-table" id="most-viewed-table">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('Page', 'greenmetrics'); ?></th>
                                            <th><?php esc_html_e('Views', 'greenmetrics'); ?></th>
                                            <th><?php esc_html_e('Carbon Footprint', 'greenmetrics'); ?></th>
                                            <th><?php esc_html_e('Energy Consumption', 'greenmetrics'); ?></th>
                                            <th><?php esc_html_e('Data Transfer', 'greenmetrics'); ?></th>
                                            <th><?php esc_html_e('Performance Score', 'greenmetrics'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Will be populated by JavaScript -->
                                        <tr class="loading-row">
                                            <td colspan="6" class="loading-cell">
                                                <span class="spinner is-active"></span>
                                                <p><?php esc_html_e('Loading page data...', 'greenmetrics'); ?></p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trend Analysis -->
            <div class="greenmetrics-admin-card">
                <h2><?php esc_html_e('Trend Analysis', 'greenmetrics'); ?></h2>
                <div class="greenmetrics-trend-analysis" id="trend-analysis">
                    <!-- Will be populated by JavaScript -->
                    <div class="trend-loading">
                        <span class="spinner is-active"></span>
                        <p><?php esc_html_e('Analyzing trends...', 'greenmetrics'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Save Report Modal -->
            <div id="save-report-modal" class="greenmetrics-modal" style="display: none;">
                <div class="greenmetrics-modal-content">
                    <span class="greenmetrics-modal-close">&times;</span>
                    <h2><?php esc_html_e('Save Report Configuration', 'greenmetrics'); ?></h2>
                    <form id="save-report-form">
                        <div class="form-group">
                            <label for="report-name"><?php esc_html_e('Report Name', 'greenmetrics'); ?></label>
                            <input type="text" id="report-name" name="report-name" placeholder="<?php esc_attr_e('My Custom Report', 'greenmetrics'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="report-description"><?php esc_html_e('Description', 'greenmetrics'); ?></label>
                            <textarea id="report-description" name="report-description" placeholder="<?php esc_attr_e('Report description...', 'greenmetrics'); ?>"></textarea>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="set-as-default" name="set-as-default">
                                <?php esc_html_e('Set as default report', 'greenmetrics'); ?>
                            </label>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="button" id="cancel-save-report"><?php esc_html_e('Cancel', 'greenmetrics'); ?></button>
                            <button type="submit" class="button button-primary" id="confirm-save-report"><?php esc_html_e('Save Report', 'greenmetrics'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
