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
                <h2><?php esc_html_e('Website Environmental Metrics', 'greenmetrics'); ?></h2>
                
                <!-- Total Metrics Section -->
                <h3><?php esc_html_e('Total Website Impact', 'greenmetrics'); ?></h3>
                <div class="greenmetrics-stats-grid">
                    <div class="greenmetrics-stat-card total">
                        <h4><?php esc_html_e('Total Views', 'greenmetrics'); ?></h4>
                        <div class="greenmetrics-stat-value" id="total-views"><?php echo esc_html(number_format($stats['total_views'])); ?></div>
                    </div>
                    <div class="greenmetrics-stat-card total">
                        <h4><?php esc_html_e('Total Carbon Footprint', 'greenmetrics'); ?></h4>
                        <div class="greenmetrics-stat-value" id="total-carbon-footprint">
                            <?php echo esc_html(number_format($stats['total_carbon_footprint'], 2)); ?> g CO2
                        </div>
                    </div>
                    <div class="greenmetrics-stat-card total">
                        <h4><?php esc_html_e('Total Energy Consumption', 'greenmetrics'); ?></h4>
                        <div class="greenmetrics-stat-value" id="total-energy-consumption">
                            <?php echo esc_html(number_format($stats['total_energy_consumption'], 6)); ?> kWh
                        </div>
                    </div>
                    <div class="greenmetrics-stat-card total">
                        <h4><?php esc_html_e('Total Data Transfer', 'greenmetrics'); ?></h4>
                        <div class="greenmetrics-stat-value" id="total-data-transfer">
                            <?php echo esc_html(size_format($stats['total_data_transfer'], 2)); ?>
                        </div>
                    </div>
                    <div class="greenmetrics-stat-card total">
                        <h4><?php esc_html_e('Total HTTP Requests', 'greenmetrics'); ?></h4>
                        <div class="greenmetrics-stat-value" id="total-requests">
                            <?php echo esc_html(number_format($stats['total_requests'])); ?>
                        </div>
                    </div>
                </div>

                <!-- Per-Page Average Metrics Section -->
                <h3><?php esc_html_e('Per-Page Average Impact', 'greenmetrics'); ?></h3>
                <div class="greenmetrics-stats-grid">
                    <div class="greenmetrics-stat-card average">
                        <h4><?php esc_html_e('Avg. Carbon Footprint', 'greenmetrics'); ?></h4>
                        <div class="greenmetrics-stat-value" id="avg-carbon-footprint">
                            <?php 
                                $avg_carbon = $stats['total_views'] > 0 ? $stats['total_carbon_footprint'] / $stats['total_views'] : 0;
                                echo esc_html(number_format($avg_carbon, 4)); 
                            ?> g CO2
                        </div>
                    </div>
                    <div class="greenmetrics-stat-card average">
                        <h4><?php esc_html_e('Avg. Energy Consumption', 'greenmetrics'); ?></h4>
                        <div class="greenmetrics-stat-value" id="avg-energy-consumption">
                            <?php 
                                $avg_energy = $stats['total_views'] > 0 ? $stats['total_energy_consumption'] / $stats['total_views'] : 0;
                                echo esc_html(number_format($avg_energy, 8)); 
                            ?> kWh
                        </div>
                    </div>
                    <div class="greenmetrics-stat-card average">
                        <h4><?php esc_html_e('Avg. Data Transfer', 'greenmetrics'); ?></h4>
                        <div class="greenmetrics-stat-value" id="avg-data-transfer">
                            <?php 
                                $avg_data = $stats['total_views'] > 0 ? $stats['total_data_transfer'] / $stats['total_views'] : 0;
                                echo esc_html(size_format($avg_data, 2)); 
                            ?>
                        </div>
                    </div>
                    <div class="greenmetrics-stat-card average">
                        <h4><?php esc_html_e('Avg. HTTP Requests', 'greenmetrics'); ?></h4>
                        <div class="greenmetrics-stat-value" id="avg-requests">
                            <?php 
                                $avg_requests = $stats['total_views'] > 0 ? $stats['total_requests'] / $stats['total_views'] : 0;
                                echo esc_html(number_format($avg_requests, 1)); 
                            ?>
                        </div>
                    </div>
                    <div class="greenmetrics-stat-card average">
                        <h4><?php esc_html_e('Avg. Load Time', 'greenmetrics'); ?></h4>
                        <div class="greenmetrics-stat-value" id="avg-load-time">
                            <?php 
                                $avg_load_time = floatval($stats['avg_load_time']);
                                // Ensure it's a positive number
                                $avg_load_time = max(0, $avg_load_time);
                                
                                // Format with appropriate units (ms vs seconds) based on value size
                                if ($avg_load_time < 0.1) {
                                    // For very small values, show milliseconds (more meaningful)
                                    $ms_value = $avg_load_time * 1000;
                                    echo esc_html(number_format($ms_value, 1)) . ' ' . esc_html__('ms', 'greenmetrics');
                                } else {
                                    // For larger values, continue showing seconds with more precision
                                    echo esc_html(number_format($avg_load_time, 4)) . ' ' . esc_html__('seconds', 'greenmetrics');
                                }
                            ?>
                        </div>
                    </div>
                    <div class="greenmetrics-stat-card average">
                        <h4><?php esc_html_e('Performance Score', 'greenmetrics'); ?></h4>
                        <div class="greenmetrics-stat-value" id="performance-score">
                            <?php 
                                // Ensure performance score is within bounds
                                $performance_score = floatval($stats['avg_performance_score']);
                                if ($performance_score > 100 || $performance_score < 0) {
                                    if ($stats['avg_load_time'] > 0) {
                                        // Use the same calculation as in the tracker
                                        $tracker = \GreenMetrics\GreenMetrics_Tracker::get_instance();
                                        if (method_exists($tracker, 'calculate_performance_score')) {
                                            $performance_score = $tracker->calculate_performance_score($stats['avg_load_time']);
                                        } else {
                                            $performance_score = max(0, min(100, 100 - ($stats['avg_load_time'] * 10)));
                                        }
                                    } else {
                                        $performance_score = 100; // If no load time data, assume perfect score
                                    }
                                }
                                // Display with 2 decimal places for precision
                                echo esc_html(number_format($performance_score, 2)); 
                            ?>%
                        </div>
                    </div>
                </div>
                
                <!-- Environmental Impact Context Section -->
                <div class="greenmetrics-environmental-context">
                    <h3><?php esc_html_e('Environmental Impact Context', 'greenmetrics'); ?></h3>
                    <div class="context-item">
                        <p>
                            <?php 
                                // Convert carbon to equivalent values
                                $carbon_kg = $stats['total_carbon_footprint'] / 1000; // Convert g to kg
                                $tree_seconds = $carbon_kg * 4500; // 1 tree absorbs ~8 kg CO2 per year (4500 seconds to absorb 1g)
                                
                                // Format the time in appropriate units
                                if ($tree_seconds < 60) {
                                    // Less than a minute, show seconds
                                    $tree_time = number_format($tree_seconds, 1) . ' ' . esc_html__('seconds', 'greenmetrics');
                                } elseif ($tree_seconds < 3600) {
                                    // Less than an hour, show minutes
                                    $tree_minutes = $tree_seconds / 60;
                                    $tree_time = number_format($tree_minutes, 1) . ' ' . esc_html__('minutes', 'greenmetrics');
                                } elseif ($tree_seconds < 86400) {
                                    // Less than a day, show hours
                                    $tree_hours = $tree_seconds / 3600;
                                    $tree_time = number_format($tree_hours, 1) . ' ' . esc_html__('hours', 'greenmetrics');
                                } else {
                                    // Show days
                                    $tree_days = $tree_seconds / 86400;
                                    $tree_time = number_format($tree_days, 1) . ' ' . esc_html__('days', 'greenmetrics');
                                }
                                
                                printf(
                                    esc_html__('Your website has produced %1$s g of CO2, which would take a tree approximately %2$s to absorb.', 'greenmetrics'),
                                    '<strong>' . number_format($stats['total_carbon_footprint'], 2) . '</strong>',
                                    '<strong>' . $tree_time . '</strong>'
                                );
                            ?>
                        </p>
                    </div>
                    <div class="context-item">
                        <p>
                            <?php 
                                // Convert energy to equivalent values
                                $energy_kwh = $stats['total_energy_consumption'];
                                $lightbulb_hours = $energy_kwh * 10; // 10W LED bulb runs for ~100 hours on 1 kWh
                                
                                // Format the time in appropriate units
                                if ($lightbulb_hours < 1) {
                                    // Less than an hour, show minutes
                                    $lightbulb_minutes = $lightbulb_hours * 60;
                                    $lightbulb_time = number_format($lightbulb_minutes, 1) . ' ' . esc_html__('minutes', 'greenmetrics');
                                } else {
                                    // Show hours
                                    $lightbulb_time = number_format($lightbulb_hours, 1) . ' ' . esc_html__('hours', 'greenmetrics');
                                }
                                
                                printf(
                                    esc_html__('Your website has consumed %1$s kWh of energy, equivalent to running a 10W LED light bulb for %2$s.', 'greenmetrics'),
                                    '<strong>' . number_format($energy_kwh, 6) . '</strong>',
                                    '<strong>' . $lightbulb_time . '</strong>'
                                );
                            ?>
                        </p>
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
                        <li><code>position="top-left|top-right|bottom-left|bottom-right"</code> - <?php esc_html_e('Badge position', 'greenmetrics'); ?></li>
                        <li><code>theme="light|dark"</code> - <?php esc_html_e('Color theme', 'greenmetrics'); ?></li>
                        <li><code>size="small|medium|large"</code> - <?php esc_html_e('Overall badge size', 'greenmetrics'); ?></li>
                    </ul>
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
            
            <!-- Add Optimization Suggestions -->
            <div class="greenmetrics-admin-card">
                <h3><?php esc_html_e('Optimization Suggestions', 'greenmetrics'); ?></h3>
                <ul class="optimization-list">
                    <?php if ($avg_data > 500 * 1024): // If average page size > 500KB ?>
                    <li class="optimization-item">
                        <h4><?php esc_html_e('Optimize Page Size', 'greenmetrics'); ?></h4>
                        <p><?php esc_html_e('Your average page size is quite large. Consider optimizing images, minifying CSS/JS, and removing unnecessary resources.', 'greenmetrics'); ?></p>
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($avg_requests > 30): // If average requests > 30 ?>
                    <li class="optimization-item">
                        <h4><?php esc_html_e('Reduce HTTP Requests', 'greenmetrics'); ?></h4>
                        <p><?php esc_html_e('Your pages make a high number of HTTP requests. Consider bundling resources, using CSS sprites, and lazy loading non-critical resources.', 'greenmetrics'); ?></p>
                    </li>
                    <?php endif; ?>
                    
                    <li class="optimization-item">
                        <h4><?php esc_html_e('Use Green Hosting', 'greenmetrics'); ?></h4>
                        <p><?php esc_html_e('Consider using a hosting provider powered by renewable energy to further reduce your carbon footprint.', 'greenmetrics'); ?></p>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div> 