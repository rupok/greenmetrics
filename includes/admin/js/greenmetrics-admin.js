/**
 * GreenMetrics Admin JavaScript
 * This is the main entry point for the admin JavaScript functionality
 *
 * The actual implementation is split into modules for better maintainability:
 * - core.js: Core functionality and namespace initialization
 * - preview.js: Badge and popover preview functionality (loaded only on plugin settings pages)
 * - chart.js: Chart visualization functionality (loaded only on dashboard pages)
 * - dashboard.js: Dashboard statistics functionality (loaded only on dashboard pages)
 *
 * The GreenMetricsAdmin namespace is used to organize all functionality
 */

// Initialize modules when document is ready
jQuery( document ).ready(
	function ($) {
		// Always initialize core module
		if (typeof GreenMetricsAdmin.core !== 'undefined') {
			GreenMetricsAdmin.core.init();
		}

		// Initialize Preview module only on plugin pages
		if (typeof GreenMetricsAdmin.Preview !== 'undefined' && greenmetricsAdmin.is_plugin_page) {
			GreenMetricsAdmin.Preview.init();
		}

		// Initialize Chart and Dashboard modules only on dashboard pages
		if (typeof GreenMetricsAdmin.Chart !== 'undefined' && greenmetricsAdmin.is_dashboard_page) {
			GreenMetricsAdmin.Chart.init();
		}

		if (typeof GreenMetricsAdmin.Dashboard !== 'undefined' && greenmetricsAdmin.is_dashboard_page) {
			GreenMetricsAdmin.Dashboard.init();
		}
	}
);