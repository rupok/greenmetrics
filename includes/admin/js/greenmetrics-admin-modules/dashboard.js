/**
 * GreenMetrics Admin Dashboard Module
 * Handles the dashboard functionality and statistics display
 *
 * @module GreenMetricsAdmin.Dashboard
 * @requires jQuery
 * @requires GreenMetricsAdmin.Config
 * @requires GreenMetricsAdmin.API
 * @requires GreenMetricsErrorHandler
 */
// Ensure namespace exists
var GreenMetricsAdmin = GreenMetricsAdmin || {};

// Add dashboard functionality to namespace
GreenMetricsAdmin.Dashboard = (function ($) {
	'use strict';

	/**
	 * Initialize the dashboard module
	 *
	 * @function init
	 * @memberof GreenMetricsAdmin.Dashboard
	 * @public
	 */
	function init() {
		// Only proceed if we're on a dashboard page
		if (!GreenMetricsAdmin.Config.isDashboardPage) {
			return;
		}

		// Only initialize if we're on the dashboard page
		if ($('#greenmetrics-stats').length) {
			initDashboard();
		}
	}

	/**
	 * Initialize dashboard components
	 *
	 * @function initDashboard
	 * @memberof GreenMetricsAdmin.Dashboard
	 * @private
	 */
	function initDashboard() {
		// Load initial stats
		getStats();

		// Set up event listeners
		setupEventListeners();
	}

	/**
	 * Get statistics data from the API
	 *
	 * @function getStats
	 * @memberof GreenMetricsAdmin.Dashboard
	 * @param {boolean} forceRefresh - Whether to force refresh the data
	 * @public
	 */
	function getStats(forceRefresh) {
		// For page loads, always force refresh to ensure latest data
		if (forceRefresh === undefined) {
			forceRefresh = true;
		}

		// Use the API module to get stats
		if (GreenMetricsAdmin.API) {
			GreenMetricsAdmin.API.getMetrics(
				{ force_refresh: forceRefresh ? 'true' : 'false' },
				updateStatsDisplay,
				function(xhr, status, error) {
					// Use enhanced error handling with admin notice for critical errors
					const showAdminNotice = xhr.status >= 500; // Show admin notice for server errors
					GreenMetricsErrorHandler.handleRestError(xhr, status, error, '#greenmetrics-stats', showAdminNotice);
				}
			);
		} else {
			// Fallback to direct AJAX if API module is not available
			$.ajax({
				url: greenmetricsAdmin.rest_url.replace(/\/$/, '') + '/metrics',
				type: 'GET',
				data: {
					force_refresh: forceRefresh ? 'true' : 'false'
				},
				beforeSend: function (xhr) {
					xhr.setRequestHeader('X-WP-Nonce', greenmetricsAdmin.rest_nonce);
				},
				success: function (response) {
					updateStatsDisplay(response);
				},
				error: function (xhr, status, error) {
					// Use enhanced error handling with admin notice for critical errors
					const showAdminNotice = xhr.status >= 500; // Show admin notice for server errors
					GreenMetricsErrorHandler.handleRestError(xhr, status, error, '#greenmetrics-stats', showAdminNotice);
				}
			});
		}
	}

	/**
	 * Update statistics display with data
	 *
	 * @function updateStatsDisplay
	 * @memberof GreenMetricsAdmin.Dashboard
	 * @param {Object} stats - Statistics data
	 * @private
	 */
	function updateStatsDisplay(stats) {
		if ($('#greenmetrics-stats').length && stats) {
			// Use the formatter for consistent display
			const formattedStats = {
				total_views: GreenMetricsAdmin.Utils.formatNumber(stats.total_views),
				carbon_footprint: GreenMetricsFormatter ?
					GreenMetricsFormatter.formatCarbonEmissions(stats.carbon_footprint) :
					stats.carbon_footprint.toFixed(2) + ' g CO2',
				energy_consumption: GreenMetricsFormatter ?
					GreenMetricsFormatter.formatEnergyConsumption(stats.energy_consumption) :
					stats.energy_consumption.toFixed(2) + ' kWh',
				data_transfer: GreenMetricsFormatter ?
					GreenMetricsFormatter.formatDataTransfer(stats.avg_data_transfer * 1024) :
					stats.avg_data_transfer.toFixed(2) + ' KB',
				requests: GreenMetricsAdmin.Utils.formatNumber(stats.requests),
				performance_score: GreenMetricsFormatter ?
					GreenMetricsFormatter.formatPerformanceScore(stats.performance_score) :
					stats.performance_score.toFixed(2) + '%'
			};

			const html = `
			<div class="stats-grid">
				<div class="stat-card">
					<h3>Total Views</h3>
					<p class="stat-value">${formattedStats.total_views}</p>
				</div>
				<div class="stat-card">
					<h3>Carbon Footprint</h3>
					<p class="stat-value">${formattedStats.carbon_footprint}</p>
				</div>
				<div class="stat-card">
					<h3>Energy Consumption</h3>
					<p class="stat-value">${formattedStats.energy_consumption}</p>
				</div>
				<div class="stat-card">
					<h3>Data Transfer</h3>
					<p class="stat-value">${formattedStats.data_transfer}</p>
				</div>
				<div class="stat-card">
					<h3>Requests</h3>
					<p class="stat-value">${formattedStats.requests}</p>
				</div>
				<div class="stat-card">
					<h3>Performance Score</h3>
					<p class="stat-value">${formattedStats.performance_score}</p>
				</div>
			</div>
			`;
			$('#greenmetrics-stats').html(html);
		}
	}

	/**
	 * Set up event listeners for dashboard
	 *
	 * @function setupEventListeners
	 * @memberof GreenMetricsAdmin.Dashboard
	 * @private
	 */
	function setupEventListeners() {
		// Refresh button if it exists
		$('#greenmetrics-refresh-stats').on('click', function(e) {
			e.preventDefault();
			getStats(true);
		});
	}

	// Public API
	return {
		init: init,
		getStats: getStats
	};
})( jQuery );