<?php
/**
 * Admin display template.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes/admin/partials
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
        <div class="greenmetrics-admin-content">
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

        <div class="greenmetrics-admin-sidebar">
            <div class="greenmetrics-admin-card">
                <h3><?php esc_html_e('Usage', 'greenmetrics'); ?></h3>
                
                <div class="usage-section">
                    <h4><?php esc_html_e('Shortcode', 'greenmetrics'); ?></h4>
                    <p><?php esc_html_e('Use the following shortcode to display the GreenMetrics badge:', 'greenmetrics'); ?></p>
                    <div class="code-block">
                        <code>[greenmetrics_badge]</code>
                    </div>
                    
                    <h5><?php esc_html_e('Available Attributes:', 'greenmetrics'); ?></h5>
                    <ul class="attributes-list">
                        <li><code>show_icon="true|false"</code> - <?php esc_html_e('Show/hide the icon', 'greenmetrics'); ?></li>
                        <li><code>icon_size="small|medium|large"</code> - <?php esc_html_e('Set the icon size', 'greenmetrics'); ?></li>
                        <li><code>show_text="true|false"</code> - <?php esc_html_e('Show/hide the text', 'greenmetrics'); ?></li>
                        <li><code>text="Custom Text"</code> - <?php esc_html_e('Custom text to display', 'greenmetrics'); ?></li>
                        <li><code>text_font_size="12"</code> - <?php esc_html_e('Text font size in pixels', 'greenmetrics'); ?></li>
                        <li><code>border_radius="4"</code> - <?php esc_html_e('Border radius in pixels', 'greenmetrics'); ?></li>
                        <li><code>padding="10"</code> - <?php esc_html_e('Padding in pixels', 'greenmetrics'); ?></li>
                        <li><code>font_size="14"</code> - <?php esc_html_e('Font size in pixels', 'greenmetrics'); ?></li>
                        <li><code>position="top-left|top-right|bottom-left|bottom-right"</code> - <?php esc_html_e('Badge position', 'greenmetrics'); ?></li>
                        <li><code>theme="light|dark"</code> - <?php esc_html_e('Color theme', 'greenmetrics'); ?></li>
                        <li><code>size="small|medium|large"</code> - <?php esc_html_e('Overall badge size', 'greenmetrics'); ?></li>
                        <li><code>show_content="true|false"</code> - <?php esc_html_e('Show/hide detailed content', 'greenmetrics'); ?></li>
                        <li><code>content_title="Custom Title"</code> - <?php esc_html_e('Custom title for detailed content', 'greenmetrics'); ?></li>
                        <li><code>selected_metrics="carbon_footprint,energy_consumption,data_transfer"</code> - <?php esc_html_e('Comma-separated list of metrics to display', 'greenmetrics'); ?></li>
                        <li><code>custom_content="Custom HTML"</code> - <?php esc_html_e('Custom HTML content to display', 'greenmetrics'); ?></li>
                        <li><code>animation_duration="300"</code> - <?php esc_html_e('Animation duration in milliseconds', 'greenmetrics'); ?></li>
                    </ul>
                    
                    <h5><?php esc_html_e('Example:', 'greenmetrics'); ?></h5>
                    <div class="code-block">
                        <code>[greenmetrics_badge show_icon="true" icon_size="medium" show_text="true" position="bottom-right" theme="light" size="medium"]</code>
                    </div>
                </div>

                <div class="usage-section">
                    <h4><?php esc_html_e('Block', 'greenmetrics'); ?></h4>
                    <p><?php esc_html_e('Add the GreenMetrics badge block to your page or post:', 'greenmetrics'); ?></p>
                    <ol>
                        <li><?php esc_html_e('Click the "+" button to add a new block', 'greenmetrics'); ?></li>
                        <li><?php esc_html_e('Search for "GreenMetrics"', 'greenmetrics'); ?></li>
                        <li><?php esc_html_e('Select the "GreenMetrics Badge" block', 'greenmetrics'); ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div> 