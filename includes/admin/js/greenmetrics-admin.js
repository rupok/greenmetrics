/**
 * GreenMetrics Admin JavaScript
 * This is the main entry point for the admin JavaScript functionality
 *
 * The actual implementation is split into modules for better maintainability:
 * - config.js: Configuration values and constants
 * - utils.js: Utility functions used across modules
 * - api.js: API interaction functions
 * - error-handler.js: Standardized error handling
 * - core.js: Core functionality and namespace initialization
 * - preview.js: Badge and popover preview functionality (loaded only on plugin settings pages)
 * - chart.js: Chart visualization functionality (loaded only on dashboard pages)
 * - dashboard.js: Dashboard statistics functionality (loaded only on dashboard pages)
 *
 * The GreenMetricsAdmin namespace is used to organize all functionality
 *
 * @requires jQuery
 * @requires GreenMetricsAdmin.Config
 * @requires GreenMetricsAdmin.Utils
 * @requires GreenMetricsAdmin.API
 * @requires GreenMetricsErrorHandler
 * @requires GreenMetricsAdmin.core
 * @requires GreenMetricsAdmin.Preview
 * @requires GreenMetricsAdmin.Chart
 * @requires GreenMetricsAdmin.Dashboard
 */

/**
 * Main initialization function
 *
 * @function initGreenMetricsAdmin
 */
function initGreenMetricsAdmin() {
    // Always initialize core module
    if (typeof GreenMetricsAdmin.core !== 'undefined') {
        GreenMetricsAdmin.core.init();
    }

    // Initialize Preview module only on plugin pages
    if (typeof GreenMetricsAdmin.Preview !== 'undefined' &&
        GreenMetricsAdmin.Config && GreenMetricsAdmin.Config.isPluginPage) {
        GreenMetricsAdmin.Preview.init();
    }

    // Initialize Chart and Dashboard modules only on dashboard pages
    if (typeof GreenMetricsAdmin.Chart !== 'undefined' &&
        GreenMetricsAdmin.Config && GreenMetricsAdmin.Config.isDashboardPage) {
        GreenMetricsAdmin.Chart.init();
    }

    if (typeof GreenMetricsAdmin.Dashboard !== 'undefined' &&
        GreenMetricsAdmin.Config && GreenMetricsAdmin.Config.isDashboardPage) {
        GreenMetricsAdmin.Dashboard.init();
    }
}

// Initialize modules when document is ready
jQuery(document).ready(initGreenMetricsAdmin);