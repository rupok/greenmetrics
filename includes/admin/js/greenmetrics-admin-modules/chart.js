/**
 * GreenMetrics Admin Chart Module
 * Handles the chart visualization functionality
 */
// Ensure namespace exists
var GreenMetricsAdmin = GreenMetricsAdmin || {};

// Add chart functionality to namespace
GreenMetricsAdmin.Chart = (function ($) {
	'use strict';

	// Module variables
	var metricsChart = null;
	var chartCanvas  = null;

	// Chart color settings
	var chartColors = {
		'carbon_footprint': {
			borderColor: 'rgb(255, 99, 132)',
			backgroundColor: 'rgba(255, 99, 132, 0.5)'
		},
		'energy_consumption': {
			borderColor: 'rgb(54, 162, 235)',
			backgroundColor: 'rgba(54, 162, 235, 0.5)'
		},
		'data_transfer': {
			borderColor: 'rgb(75, 192, 192)',
			backgroundColor: 'rgba(75, 192, 192, 0.5)'
		},
		'http_requests': {
			borderColor: 'rgb(153, 102, 255)',
			backgroundColor: 'rgba(153, 102, 255, 0.5)'
		},
		'page_views': {
			borderColor: 'rgb(255, 159, 64)',
			backgroundColor: 'rgba(255, 159, 64, 0.5)'
		}
	};

	// Initialize the chart module
	function init() {
		// Only proceed if we're on a dashboard page
		if ( ! greenmetricsAdmin.is_dashboard_page) {
			return;
		}

		// Get chart canvas
		chartCanvas = document.getElementById( 'greenmetrics-chart' );

		// Only initialize if the canvas exists
		if (chartCanvas) {
			initChart();
		}
	}

	// Initialize Chart.js with our configuration
	function initChart() {
		Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif';
		Chart.defaults.font.size   = 12;

		metricsChart = new Chart(
			chartCanvas,
			{
				type: 'line',
				data: {
					labels: [],
					datasets: []
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					scales: {
						y: {
							beginAtZero: true,
							grid: {
								color: 'rgba(0, 0, 0, 0.05)',
								lineWidth: 1
							},
							ticks: {
								padding: 10,
								color: '#666'
							},
							border: {
								dash: [4, 4]
							}
						},
						x: {
							grid: {
								color: 'rgba(0, 0, 0, 0.03)',
								lineWidth: 1,
								drawOnChartArea: true
							},
							ticks: {
								padding: 10,
								maxRotation: 45,
								minRotation: 0,
								color: '#666'
							}
						}
					},
					interaction: {
						mode: 'index',
						intersect: false
					},
					plugins: {
						tooltip: {
							enabled: true,
							backgroundColor: 'rgba(255, 255, 255, 0.95)',
							titleColor: '#333',
							bodyColor: '#666',
							titleFont: {
								weight: 'bold',
								size: 14
							},
							bodyFont: {
								size: 13
							},
							padding: 12,
							cornerRadius: 6,
							boxPadding: 6,
							borderColor: 'rgba(0, 0, 0, 0.1)',
							borderWidth: 1,
							usePointStyle: true,
							callbacks: {
								labelPointStyle: function (context) {
									return {
										pointStyle: 'circle',
										rotation: 0
									};
								}
							}
						},
						legend: {
							display: false // We're using custom checkboxes for legend
						}
					},
					animation: {
						duration: 750,
						easing: 'easeOutQuart'
					},
					elements: {
						line: {
							tension: 0.3 // Smoother curves
						},
						point: {
							radius: 3,
							hoverRadius: 5,
							hitRadius: 30
						}
					}
				}
			}
		);

		// Load initial data (last 7 days by default) with force refresh
		loadMetricsByDate(null, null, true);

		// Set up event handlers for date range buttons and chart toggles
		setupDateRangeHandlers();
		setupChartToggleHandlers();
	}

	// Load metrics data by date range
	function loadMetricsByDate(startDate, endDate, forceRefresh) {
		// Show loading state
		if (metricsChart) {
			metricsChart.data.labels   = [];
			metricsChart.data.datasets = [];
			metricsChart.update();
		}

		// Add loading class to chart container
		$( '.greenmetrics-chart-container' ).addClass( 'loading' );

		// Set default date range if not provided (last 7 days)
		if ( ! startDate && ! endDate) {
			startDate = GreenMetricsAdmin.Utils.getDateString( 7 ); // 7 days ago
			endDate   = GreenMetricsAdmin.Utils.getDateString( 0 );   // Today
		}

		// Make API request
		$.ajax(
			{
				url: greenmetricsAdmin.rest_url + '/metrics-by-date',
				method: 'GET',
				data: {
					start_date: startDate,
					end_date: endDate,
					force_refresh: forceRefresh ? 'true' : 'false'
				},
				beforeSend: function (xhr) {
					xhr.setRequestHeader( 'X-WP-Nonce', greenmetricsAdmin.rest_nonce );
				},
				success: function (response) {
					updateChart( response );
				},
				error: function (xhr, status, error) {
					// Reset chart data
					if (metricsChart) {
						metricsChart.data.labels   = [];
						metricsChart.data.datasets = [];
						metricsChart.update();
					}

					// Use the enhanced error handler with admin notice for critical errors
					const showAdminNotice = xhr.status >= 500; // Show admin notice for server errors
					GreenMetricsErrorHandler.handleRestError(xhr, status, error, '.greenmetrics-chart-container', showAdminNotice);
				},
				complete: function () {
					// Remove loading states
					$( '.greenmetrics-date-btn' ).removeClass( 'loading' );
					$( '.greenmetrics-chart-container' ).removeClass( 'loading' );
				}
			}
		);
	}

	// Update chart with new data
	function updateChart(data) {
		if ( ! metricsChart) {
			return;
		}

		// Clear previous data
		metricsChart.data.labels   = [];
		metricsChart.data.datasets = [];

		// Check if we have valid data
		if ( ! data || ! data.dates || data.dates.length === 0) {
			metricsChart.update();
			return;
		}

		// Format dates for display (e.g., "Jan 15" instead of "2023-01-15")
		const formattedDates = data.dates.map(
			dateStr => {
				const date       = new Date( dateStr );
				return date.toLocaleDateString( undefined, { month: 'short', day: 'numeric' } );
			}
		);

		// Update chart labels (dates)
		metricsChart.data.labels = formattedDates;

		// Create datasets for each metric
		const metrics = [
		{ id: 'carbon_footprint', label: 'Carbon Footprint (g)' },
		{ id: 'energy_consumption', label: 'Energy Consumption (kWh)' },
		{ id: 'data_transfer', label: 'Data Transfer (KB)' },
		{ id: 'http_requests', label: 'HTTP Requests' },
		{ id: 'page_views', label: 'Page Views' }
		];

		// Add each metric dataset if it has data
		metrics.forEach(
			function (metric) {
				if (data[metric.id] && data[metric.id].length > 0) {
					const colors  = chartColors[metric.id];
					const visible = $( '#' + metric.id ).prop( 'checked' );

					metricsChart.data.datasets.push(
						{
							label: metric.label,
							data: data[metric.id],
							borderColor: colors.borderColor,
							backgroundColor: colors.backgroundColor,
							borderWidth: 2,
							pointRadius: 4,
							pointStyle: 'circle',
							pointBackgroundColor: colors.borderColor,
							pointBorderColor: 'rgba(255, 255, 255, 0.8)',
							pointBorderWidth: 2,
							pointHoverRadius: 6,
							pointHoverBackgroundColor: colors.borderColor,
							pointHoverBorderColor: 'white',
							pointHoverBorderWidth: 2,
							hidden: ! visible,
							cubicInterpolationMode: 'monotone',
							tension: 0.4,
							fill: false
						}
					);
				}
			}
		);

		// Update the chart
		metricsChart.update();
	}

	// Set up event handlers for date range buttons
	function setupDateRangeHandlers() {
		// Date range button clicks
		$( '.greenmetrics-date-btn' ).on(
			'click',
			function (e) {
				e.preventDefault();

				// Special handling for force refresh button
				if ($(this).hasClass('force-refresh')) {
					// Get current active date range
					const activeRange = $('.greenmetrics-date-btn.active').data('range');
					let startDate, endDate;

					// Get the current date range
					switch (activeRange) {
						case '7days':
							startDate = GreenMetricsAdmin.Utils.getDateString(7);
							endDate = GreenMetricsAdmin.Utils.getDateString(0);
							break;
						case '30days':
							startDate = GreenMetricsAdmin.Utils.getDateString(30);
							endDate = GreenMetricsAdmin.Utils.getDateString(0);
							break;
						case 'thisMonth':
							const now = new Date();
							startDate = now.getFullYear() + '-' + GreenMetricsAdmin.Utils.pad(now.getMonth() + 1) + '-01';
							endDate = GreenMetricsAdmin.Utils.getDateString(0);
							break;
						case 'custom':
							startDate = $('#greenmetrics-start-date').val();
							endDate = $('#greenmetrics-end-date').val();
							break;
						default:
							startDate = GreenMetricsAdmin.Utils.getDateString(7);
							endDate = GreenMetricsAdmin.Utils.getDateString(0);
					}

					// Show loading state
					$(this).addClass('loading');

					// Force refresh the data
					$.ajax({
						url: greenmetricsAdmin.rest_url + '/metrics-by-date',
						method: 'GET',
						data: {
							start_date: startDate,
							end_date: endDate,
							force_refresh: 'true'
						},
						beforeSend: function (xhr) {
							xhr.setRequestHeader('X-WP-Nonce', greenmetricsAdmin.rest_nonce);
						},
						success: function (response) {
							updateChart(response);
						},
						error: function (xhr, status, error) {
							// Reset chart data
							if (metricsChart) {
								metricsChart.data.labels = [];
								metricsChart.data.datasets = [];
								metricsChart.update();
							}

							// Use the enhanced error handler with admin notice for critical errors
							const showAdminNotice = xhr.status >= 500; // Show admin notice for server errors
							GreenMetricsErrorHandler.handleRestError(xhr, status, error, '.greenmetrics-chart-container', showAdminNotice);
						},
						complete: function () {
							// Remove loading states
							$('.greenmetrics-date-btn').removeClass('loading');
							$('.greenmetrics-chart-container').removeClass('loading');
						}
					});

					return;
				}

				// Regular date range button handling
				// Remove active class from all buttons
				$( '.greenmetrics-date-btn' ).removeClass( 'active' );

				// Add active class to clicked button
				$( this ).addClass( 'active' );

				// Get the date range type
				const range = $( this ).data( 'range' );
				let startDate, endDate;

				// Set date range based on button clicked
				switch (range) {
					case '7days':
						startDate = GreenMetricsAdmin.Utils.getDateString( 7 ); // 7 days ago
						endDate   = GreenMetricsAdmin.Utils.getDateString( 0 );   // Today

						// Update date inputs to match selected range
						$( '#greenmetrics-start-date' ).val( startDate );
						$( '#greenmetrics-end-date' ).val( endDate );
					break;

					case '30days':
						startDate = GreenMetricsAdmin.Utils.getDateString( 30 ); // 30 days ago
						endDate   = GreenMetricsAdmin.Utils.getDateString( 0 );    // Today

						// Update date inputs to match selected range
						$( '#greenmetrics-start-date' ).val( startDate );
						$( '#greenmetrics-end-date' ).val( endDate );
					break;

					case 'thisMonth':
						// First day of current month
						const now = new Date();
						startDate = now.getFullYear() + '-' + GreenMetricsAdmin.Utils.pad( now.getMonth() + 1 ) + '-01';
						endDate   = GreenMetricsAdmin.Utils.getDateString( 0 ); // Today

						// Update date inputs to match selected range
						$( '#greenmetrics-start-date' ).val( startDate );
						$( '#greenmetrics-end-date' ).val( endDate );
				break;

					case 'custom':
						// Use the date inputs for custom range
						startDate = $( '#greenmetrics-start-date' ).val();
						endDate   = $( '#greenmetrics-end-date' ).val();

						// Validate dates
						if ( ! startDate || ! endDate) {
							// Show feedback to user
							alert( 'Please select both start and end dates' );
							return; // Don't proceed if dates are not set
						}

						// Check if end date is before start date
						if (new Date( endDate ) < new Date( startDate )) {
							// Swap dates
							const temp = startDate;
							startDate  = endDate;
							endDate    = temp;

							// Update inputs
							$( '#greenmetrics-start-date' ).val( startDate );
							$( '#greenmetrics-end-date' ).val( endDate );
						}
				break;

					default:
				return;
				}

				// Show loading state
				$( this ).addClass( 'loading' );

				// Load metrics for the selected date range
				// Pass true for forceRefresh if this is the refresh button
				loadMetricsByDate( startDate, endDate, $(this).hasClass('force-refresh') );
			}
		);

		// Date input changes
		$( '.greenmetrics-date-input' ).on(
			'change',
			function () {
				// When date inputs change, highlight the custom button
				$( '.greenmetrics-date-btn' ).removeClass( 'active' );
				$( '.greenmetrics-date-btn[data-range="custom"]' ).addClass( 'active' );
			}
		);

		// Set default dates for custom date picker
		const today        = new Date();
		const sevenDaysAgo = new Date();
		sevenDaysAgo.setDate( today.getDate() - 7 );

		$( '#greenmetrics-start-date' ).val( GreenMetricsAdmin.Utils.getDateString( 7 ) ); // 7 days ago by default
		$( '#greenmetrics-end-date' ).val( GreenMetricsAdmin.Utils.getDateString( 0 ) );   // Today by default

		// Set max date for both date pickers to today
		const maxDate = GreenMetricsAdmin.Utils.getDateString( 0 );
		$( '#greenmetrics-start-date, #greenmetrics-end-date' ).attr( 'max', maxDate );
	}

	// Set up event handlers for chart metric toggles
	function setupChartToggleHandlers() {
		$( '.chart-toggle' ).on(
			'change',
			function () {
				if ( ! metricsChart) {
					return;
				}

				const metricId = $( this ).attr( 'id' );
				const checked  = $( this ).prop( 'checked' );

				// Find the dataset index for this metric
				const datasetIndex = metricsChart.data.datasets.findIndex(
					dataset =>
					dataset.label.toLowerCase().includes( metricId.toLowerCase().replace( '_', ' ' ) )
				);

				// Toggle visibility if dataset found
				if (datasetIndex !== -1) {
					metricsChart.setDatasetVisibility( datasetIndex, checked );
					metricsChart.update();
				}
			}
		);
	}

	// Public API
	return {
		init: init,
		loadMetricsByDate: loadMetricsByDate
	};
})( jQuery );