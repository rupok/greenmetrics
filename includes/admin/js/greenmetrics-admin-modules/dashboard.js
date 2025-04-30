/**
 * GreenMetrics Admin Dashboard Module
 * Handles the dashboard functionality
 */
// Ensure namespace exists
var GreenMetricsAdmin = GreenMetricsAdmin || {};

// Add dashboard functionality to namespace
GreenMetricsAdmin.Dashboard = (function ($) {
	'use strict';

	// Initialize the dashboard module
	function init() {
		// Only proceed if we're on a dashboard page
		if ( ! greenmetricsAdmin.is_dashboard_page) {
			return;
		}

		// Only initialize if we're on the dashboard page
		if ($( '#greenmetrics-stats' ).length) {
			initDashboard();
		}
	}

	// Initialize dashboard
	function initDashboard() {
		// Load initial stats
		getStats();

		// Set up event listeners
		setupEventListeners();
	}

	// Get stats via AJAX
	function getStats(forceRefresh) {
		if (typeof greenmetricsAdmin !== 'undefined' && greenmetricsAdmin.rest_url) {
			// For page loads, always force refresh to ensure latest data
			if (forceRefresh === undefined) {
				forceRefresh = true;
			}

			$.ajax(
				{
					url: greenmetricsAdmin.rest_url + 'greenmetrics/v1/metrics',
					type: 'GET',
					data: {
						force_refresh: forceRefresh ? 'true' : 'false'
					},
					beforeSend: function (xhr) {
						xhr.setRequestHeader( 'X-WP-Nonce', greenmetricsAdmin.rest_nonce );
					},
					success: function (response) {
						updateStatsDisplay( response );
					},
					error: function (xhr, status, error) {
						// Use the standardized error handler
						GreenMetricsErrorHandler.handleRestError(xhr, status, error, '#greenmetrics-stats');
					}
				}
			);
		}
	}

	// Update stats display with data
	function updateStatsDisplay(stats) {
		if ($( '#greenmetrics-stats' ).length && stats) {
			const html  = `
			< div class = "stats-grid" >
			< div class = "stat-card" >
			< h3 > Total Views < / h3 >
			< p class   = "stat-value" > ${stats.total_views} < / p >
			< / div >
			< div class = "stat-card" >
			< h3 > Carbon Footprint < / h3 >
			< p class   = "stat-value" > ${stats.carbon_footprint.toFixed( 2 )} g CO2 < / p >
			< / div >
			< div class = "stat-card" >
			< h3 > Energy Consumption < / h3 >
			< p class   = "stat-value" > ${stats.energy_consumption.toFixed( 2 )} kWh < / p >
			< / div >
			< div class = "stat-card" >
			< h3 > Data Transfer < / h3 >
			< p class   = "stat-value" > ${stats.avg_data_transfer.toFixed( 2 )} KB < / p >
			< / div >
			< div class = "stat-card" >
			< h3 > Requests < / h3 >
			< p class   = "stat-value" > ${stats.requests} < / p >
			< / div >
			< div class = "stat-card" >
			< h3 > Performance Score < / h3 >
			< p class = "stat-value" > ${stats.performance_score.toFixed( 2 )} % < / p >
			< / div >
			< / div >
			`;
			$( '#greenmetrics-stats' ).html( html );
		}
	}

	// Set up event listeners
	function setupEventListeners() {
		// Add dashboard-specific event listeners if needed
	}

	// Public API
	return {
		init: init,
		getStats: getStats
	};
})( jQuery );