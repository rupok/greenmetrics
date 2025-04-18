<?php
/**
 * The calculator functionality of the plugin.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */

namespace GreenMetrics;

class GreenMetrics_Calculator {
    /**
     * Average carbon intensity of electricity (gCO2/kWh)
     * Source: https://www.iea.org/reports/global-energy-co2-status-report-2019
     */
    const CARBON_INTENSITY = 475;

    /**
     * Average data center PUE (Power Usage Effectiveness)
     * Source: https://www.uptimeinstitute.com/resources/asset/2019-uptime-institute-data-center-industry-survey
     */
    const PUE = 1.67;

    /**
     * Energy per byte (kWh/GB)
     * Source: https://www.researchgate.net/publication/320225452_Energy_Proportionality_in_Near-Zero_Power_Systems
     */
    const ENERGY_PER_BYTE = 0.000001805;

    /**
     * Calculate carbon emissions for data transfer.
     *
     * @param float $bytes Data transfer in bytes.
     * @return float Carbon emissions in grams.
     */
    public static function calculate_carbon_emissions($bytes) {
        // Convert bytes to GB
        $gigabytes = $bytes / (1024 * 1024 * 1024);
        
        // Calculate energy consumption
        $energy_kwh = $gigabytes * self::ENERGY_PER_BYTE * self::PUE;
        
        // Calculate carbon emissions
        $carbon_grams = $energy_kwh * self::CARBON_INTENSITY;
        
        return $carbon_grams;
    }

    /**
     * Format carbon emissions for display.
     *
     * @param float $grams Carbon emissions in grams.
     * @return string Formatted string with appropriate unit.
     */
    public static function format_carbon_emissions($grams) {
        if ($grams >= 1000) {
            return number_format($grams / 1000, 2) . ' kg';
        }
        return number_format($grams, 2) . ' g';
    }

    /**
     * Format data transfer for display.
     *
     * @param float $bytes Data transfer in bytes.
     * @return string Formatted string with appropriate unit.
     */
    public static function format_data_transfer($bytes) {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return round($bytes / 1048576, 2) . ' MB';
        }
    }

    /**
     * Get optimization suggestions based on data transfer.
     *
     * @param float $bytes Data transfer in bytes.
     * @return array Array of optimization suggestions.
     */
    public static function get_optimization_suggestions($bytes) {
        $suggestions = array();
        
        // Check for large images
        if ($bytes > 2 * 1024 * 1024) { // More than 2MB
            $suggestions[] = array(
                'id' => 'optimize_images',
                'title' => __('Optimize Images', 'greenmetrics'),
                'description' => __('Your page contains large images. Consider compressing them to reduce data transfer.', 'greenmetrics'),
                'priority' => 'high'
            );
        }
        
        // Check for missing caching
        if (!defined('WP_CACHE') || !WP_CACHE) {
            $suggestions[] = array(
                'id' => 'enable_caching',
                'title' => __('Enable Caching', 'greenmetrics'),
                'description' => __('Caching is not enabled. Enable caching to reduce server load and data transfer.', 'greenmetrics'),
                'priority' => 'medium'
            );
        }
        
        // Check for missing lazy loading
        if (!has_filter('wp_lazy_loading_enabled', '__return_true')) {
            $suggestions[] = array(
                'id' => 'enable_lazy_loading',
                'title' => __('Enable Lazy Loading', 'greenmetrics'),
                'description' => __('Lazy loading is not enabled. Enable it to reduce initial page load size.', 'greenmetrics'),
                'priority' => 'medium'
            );
        }
        
        return $suggestions;
    }

    /**
     * Format load time
     *
     * @param float $seconds Load time in seconds
     * @return string Formatted time with appropriate unit
     */
    public static function format_load_time($seconds) {
        if ($seconds < 1) {
            return round($seconds * 1000, 2) . ' ms';
        } else {
            return round($seconds, 2) . ' s';
        }
    }
} 