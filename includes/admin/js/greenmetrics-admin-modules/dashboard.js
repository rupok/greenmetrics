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
						// Enhanced error handling
						let errorMessage = 'Error loading stats. Please try again.';

						// Try to get more detailed error message from response
						if (xhr.responseJSON && xhr.responseJSON.message) {
							errorMessage = xhr.responseJSON.message;
						}

						// Check for nonce/security errors
						if (errorMessage.includes('Security verification failed') ||
							errorMessage.includes('Nonce')) {
							errorMessage += ' Please refresh the page and try again.';
						}

						// Display error message
						$( '#greenmetrics-stats' ).html( '<p class="error">' + errorMessage + '</p>' );

						// Log error in debug mode
						if (greenmetricsAdmin.debug) {
							console.error('GreenMetrics: Error loading stats', {
								status: status,
								error: error,
								response: xhr.responseText
							});
						}
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