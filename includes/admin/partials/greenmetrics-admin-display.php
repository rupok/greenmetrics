<?php
/**
 * Admin area display template
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="greenmetrics-admin-container">
        <div class="greenmetrics-admin-main">
            <div class="greenmetrics-admin-box">
                <h2><?php esc_html_e('Settings', 'greenmetrics'); ?></h2>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('greenmetrics_settings');
                    do_settings_sections('greenmetrics');
                    submit_button();
                    ?>
                </form>
            </div>

            <div class="greenmetrics-admin-box">
                <h2><?php esc_html_e('Statistics', 'greenmetrics'); ?></h2>
                <div class="greenmetrics-stats-container">
                    <?php
                    $tracker = \GreenMetrics\GreenMetrics_Tracker::get_instance();
                    $stats = $tracker->get_stats();

                    // Format stats
                    $data_transfer = isset($stats['avg_data_transfer']) ? number_format($stats['avg_data_transfer'] / 1024, 2) . ' KB' : '0 KB';
                    $load_time = isset($stats['avg_load_time']) ? number_format($stats['avg_load_time'], 2) . ' s' : '0 s';
                    $total_views = isset($stats['total_views']) ? number_format($stats['total_views']) : '0';
                    ?>
                    <div class="greenmetrics-stat-box">
                        <h3><?php esc_html_e('Average Data Transfer', 'greenmetrics'); ?></h3>
                        <div class="stat-value"><?php echo esc_html($data_transfer); ?></div>
                    </div>
                    <div class="greenmetrics-stat-box">
                        <h3><?php esc_html_e('Average Load Time', 'greenmetrics'); ?></h3>
                        <div class="stat-value"><?php echo esc_html($load_time); ?></div>
                    </div>
                    <div class="greenmetrics-stat-box">
                        <h3><?php esc_html_e('Total Page Views', 'greenmetrics'); ?></h3>
                        <div class="stat-value"><?php echo esc_html($total_views); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="greenmetrics-admin-sidebar">
            <div class="greenmetrics-admin-box">
                <h2><?php esc_html_e('Usage', 'greenmetrics'); ?></h2>
                <div class="usage-info">
                    <h3><?php esc_html_e('Shortcode', 'greenmetrics'); ?></h3>
                    <code>[greenmetrics_badge]</code>
                    <p><?php esc_html_e('Use this shortcode to display the eco-friendly badge anywhere on your site.', 'greenmetrics'); ?></p>
                    
                    <h3><?php esc_html_e('Attributes', 'greenmetrics'); ?></h3>
                    <ul>
                        <li><code>position</code> - <?php esc_html_e('Badge position (e.g., bottom-right, bottom-left)', 'greenmetrics'); ?></li>
                        <li><code>theme</code> - <?php esc_html_e('Color theme (light or dark)', 'greenmetrics'); ?></li>
                        <li><code>size</code> - <?php esc_html_e('Badge size (small, medium, or large)', 'greenmetrics'); ?></li>
                    </ul>

                    <h3><?php esc_html_e('Block Editor', 'greenmetrics'); ?></h3>
                    <p><?php esc_html_e('You can also add the GreenMetrics badge using the block editor. Look for the "GreenMetrics Badge" block in the block inserter.', 'greenmetrics'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div> 