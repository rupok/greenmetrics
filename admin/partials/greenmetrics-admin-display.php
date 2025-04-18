<?php
/**
 * Admin display template.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get stats using the tracker singleton
$tracker = \GreenMetrics\GreenMetrics_Tracker::get_instance();
$stats = $tracker->get_stats();

// Get settings
$settings = get_option('greenmetrics_settings', array(
    'tracking_enabled' => 1,
    'enable_badge' => 1
));
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="greenmetrics-admin-container">
        <div class="greenmetrics-admin-stats">
            <h2><?php esc_html_e('Website Performance', 'greenmetrics'); ?></h2>
            <div class="greenmetrics-stats-grid">
                <div class="greenmetrics-stat-card">
                    <h3><?php esc_html_e('Total Views', 'greenmetrics'); ?></h3>
                    <div class="greenmetrics-stat-value" id="total-views"><?php echo esc_html($stats->total_views ?? 0); ?></div>
                </div>
                <div class="greenmetrics-stat-card">
                    <h3><?php esc_html_e('Average Data Transfer', 'greenmetrics'); ?></h3>
                    <div class="greenmetrics-stat-value" id="avg-data-transfer"><?php echo esc_html(round($stats->avg_data_transfer ?? 0, 2)); ?> KB</div>
                </div>
                <div class="greenmetrics-stat-card">
                    <h3><?php esc_html_e('Average Load Time', 'greenmetrics'); ?></h3>
                    <div class="greenmetrics-stat-value" id="avg-load-time"><?php echo esc_html(round($stats->avg_load_time ?? 0, 2)); ?> ms</div>
                </div>
            </div>
        </div>

        <div class="greenmetrics-admin-settings">
            <h2><?php esc_html_e('Settings', 'greenmetrics'); ?></h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('greenmetrics_settings');
                do_settings_sections('greenmetrics');
                submit_button();
                ?>
            </form>
        </div>
    </div>
</div> 