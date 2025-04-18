<?php
/**
 * The public-facing view for the green badge.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$settings = get_option('greenmetrics_settings', array());
if (!isset($settings['enable_badge']) || $settings['enable_badge'] != 1) {
    return;
}

$style = isset($attributes['style']) ? $attributes['style'] : ($settings['badge_style'] ?? 'light');
$size = isset($attributes['size']) ? $attributes['size'] : 'medium';
$placement = isset($attributes['placement']) ? $attributes['placement'] : ($settings['badge_placement'] ?? 'bottom-right');
?>

<div class="greenmetrics-badge <?php echo esc_attr($style); ?> <?php echo esc_attr($size); ?> <?php echo esc_attr($placement); ?>">
    <svg class="leaf-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path d="M17 8C8 10 5.9 16.17 3.82 21.34l1.89.66.95-2.3c.48.17.98.3 1.34.3C19 20 22 3 22 3c-1 2-8 2.25-13 3.25S2 11.5 2 13.5s1.75 3.75 1.75 3.75C7 8 17 8 17 8z"/>
    </svg>
    <span><?php _e('Eco-Friendly Site', 'greenmetrics'); ?></span>
</div> 