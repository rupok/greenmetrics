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
 * - settings-handler.js: Settings form submission handling
 * - preview.js: Badge and popover preview functionality (loaded only on plugin settings pages)
 * - chart.js: Chart visualization functionality (loaded only on dashboard and reports pages)
 * - dashboard.js: Dashboard statistics functionality (loaded only on dashboard pages)
 * - reports.js: Advanced reporting functionality (loaded only on reports pages)
 * - display-settings.js: Display Settings page functionality (loaded only on display settings page)
 *
 * The GreenMetricsAdmin namespace is used to organize all functionality
 *
 * @requires jQuery
 * @requires GreenMetricsAdmin.Config
 * @requires GreenMetricsAdmin.Utils
 * @requires GreenMetricsAdmin.API
 * @requires GreenMetricsErrorHandler
 * @requires GreenMetricsAdmin.core
 * @requires GreenMetricsAdmin.SettingsHandler
 * @requires GreenMetricsAdmin.Preview
 * @requires GreenMetricsAdmin.Chart
 * @requires GreenMetricsAdmin.Dashboard
 * @requires GreenMetricsAdmin.ReportsChart
 * @requires GreenMetricsAdmin.Reports
 * @requires GreenMetricsAdmin.DisplaySettings
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

    // Initialize Settings Handler module on all admin pages
    if (typeof GreenMetricsAdmin.SettingsHandler !== 'undefined') {
        GreenMetricsAdmin.SettingsHandler.init();
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

    // Initialize Reports module only on reports pages
    if (typeof GreenMetricsAdmin.Reports !== 'undefined' &&
        GreenMetricsAdmin.Config && GreenMetricsAdmin.Config.isReportsPage) {
        GreenMetricsAdmin.Reports.init();
    }

    // Initialize Email Reporting module only on email reporting pages
    if (typeof GreenMetricsAdmin.EmailReporting !== 'undefined' &&
        GreenMetricsAdmin.Config && GreenMetricsAdmin.Config.isEmailReportingPage) {
        GreenMetricsAdmin.EmailReporting.init();
    }

    // Initialize Display Settings module
    if (typeof GreenMetricsAdmin.DisplaySettings !== 'undefined') {
        GreenMetricsAdmin.DisplaySettings.init();
    }

    // Initialize Data Management module
    if (typeof GreenMetricsAdmin.DataManagement !== 'undefined') {
        GreenMetricsAdmin.DataManagement.init();
    }
}

// Initialize modules when document is ready
jQuery(document).ready(initGreenMetricsAdmin);