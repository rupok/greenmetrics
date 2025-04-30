/**
 * GreenMetrics Admin Configuration Module
 * 
 * This module provides configuration values and constants used across
 * the admin JavaScript modules.
 */
var GreenMetricsAdmin = GreenMetricsAdmin || {};

GreenMetricsAdmin.Config = (function() {
    'use strict';

    // Default colors for reference
    const defaultColors = {
        'badge_background_color': '#4CAF50',
        'badge_text_color': '#ffffff',
        'badge_icon_color': '#ffffff',
        'popover_bg_color': '#ffffff',
        'popover_text_color': '#333333',
        'popover_metrics_color': '#4CAF50',
        'popover_metrics_bg_color': 'rgba(0, 0, 0, 0.05)',
        'popover_metrics_list_bg_color': '#f8f9fa',
        'popover_metrics_list_hover_bg_color': '#f3f4f6'
    };

    // API endpoints
    const apiEndpoints = {
        stats: greenmetricsAdmin.rest_url + 'stats',
        metrics: greenmetricsAdmin.rest_url + 'metrics',
        settings: greenmetricsAdmin.rest_url + 'settings'
    };

    // Date ranges for reports
    const dateRanges = {
        day: 1,
        week: 7,
        month: 30,
        quarter: 90,
        year: 365
    };

    // Debug mode
    const debug = greenmetricsAdmin.debug || false;

    // Public API
    return {
        defaultColors: defaultColors,
        apiEndpoints: apiEndpoints,
        dateRanges: dateRanges,
        debug: debug,
        isDashboardPage: greenmetricsAdmin.is_dashboard_page || false,
        isPluginPage: greenmetricsAdmin.is_plugin_page || false
    };
})();
