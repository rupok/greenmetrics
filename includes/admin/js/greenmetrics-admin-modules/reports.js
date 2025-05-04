/**
 * GreenMetrics Admin Reports Module
 * Handles the advanced reporting functionality
 *
 * @module GreenMetricsAdmin.Reports
 * @requires jQuery
 * @requires GreenMetricsAdmin.Utils
 * @requires GreenMetricsAdmin.Config
 * @requires GreenMetricsAdmin.API
 * @requires GreenMetricsErrorHandler
 */
// Ensure namespace exists
var GreenMetricsAdmin = GreenMetricsAdmin || {};

// Add reports functionality to namespace
GreenMetricsAdmin.Reports = (function ($) {
    'use strict';

    // Module variables
    var reportChart = null;
    var currentFilters = {
        dateRange: '30',
        startDate: '',
        endDate: '',
        pageId: '0',
        comparison: 'none',
        chartType: 'line',
        metricFocus: 'all'
    };
    var savedReports = [];
    var chartData = null;
    var pageData = null;

    // Load saved reports from localStorage
    try {
        var savedReportsJson = localStorage.getItem('greenmetrics_saved_reports');
        if (savedReportsJson) {
            savedReports = JSON.parse(savedReportsJson);
        }
    } catch (e) {
        console.error('Error loading saved reports:', e);
    }

    /**
     * Initialize the reports module
     *
     * @function init
     * @memberof GreenMetricsAdmin.Reports
     * @public
     */
    function init() {
        // Only proceed if we're on the reports page
        if (!isReportsPage()) {
            return;
        }

        // Initialize date values
        initializeDateValues();

        // Set up event listeners
        setupEventListeners();

        // Load initial data
        loadReportData();
    }

    /**
     * Check if we're on the reports page
     *
     * @function isReportsPage
     * @memberof GreenMetricsAdmin.Reports
     * @private
     * @returns {boolean} True if on reports page
     */
    function isReportsPage() {
        return $('#greenmetrics-report-filters-form').length > 0;
    }

    /**
     * Initialize date values for filters
     *
     * @function initializeDateValues
     * @memberof GreenMetricsAdmin.Reports
     * @private
     */
    function initializeDateValues() {
        var today = new Date();
        var thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 30);

        currentFilters.endDate = formatDate(today);
        currentFilters.startDate = formatDate(thirtyDaysAgo);

        // Set the date inputs
        $('#start-date').val(currentFilters.startDate);
        $('#end-date').val(currentFilters.endDate);
    }

    /**
     * Format a date as YYYY-MM-DD
     *
     * @function formatDate
     * @memberof GreenMetricsAdmin.Reports
     * @private
     * @param {Date} date - The date to format
     * @returns {string} Formatted date string
     */
    function formatDate(date) {
        var year = date.getFullYear();
        var month = (date.getMonth() + 1).toString().padStart(2, '0');
        var day = date.getDate().toString().padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    /**
     * Set up event listeners for the reports page
     *
     * @function setupEventListeners
     * @memberof GreenMetricsAdmin.Reports
     * @private
     */
    function setupEventListeners() {
        // Date range selector
        $('#date-range').on('change', function() {
            var range = $(this).val();
            currentFilters.dateRange = range;

            if (range === 'custom') {
                $('.custom-date-range').show();
            } else {
                $('.custom-date-range').hide();

                // Calculate dates based on range
                var endDate = new Date();
                var startDate = new Date();
                startDate.setDate(endDate.getDate() - parseInt(range));

                currentFilters.startDate = formatDate(startDate);
                currentFilters.endDate = formatDate(endDate);

                // Update date inputs
                $('#start-date').val(currentFilters.startDate);
                $('#end-date').val(currentFilters.endDate);
            }
        });

        // Custom date inputs
        $('#start-date, #end-date').on('change', function() {
            currentFilters.startDate = $('#start-date').val();
            currentFilters.endDate = $('#end-date').val();
        });

        // Page filter
        $('#page-filter').on('change', function() {
            currentFilters.pageId = $(this).val();
        });

        // Comparison selector
        $('#comparison').on('change', function() {
            currentFilters.comparison = $(this).val();
        });

        // Chart type selector
        $('#chart-type').on('change', function() {
            var chartType = $(this).val();
            // Only allow 'line' or 'bar' chart types
            currentFilters.chartType = (chartType === 'line' || chartType === 'bar') ? chartType : 'line';

            // If chart exists, update its type
            if (reportChart) {
                updateChartType();
            }
        });

        // Metric focus selector
        $('#metric-focus').on('change', function() {
            currentFilters.metricFocus = $(this).val();

            // If chart exists, update displayed metrics
            if (reportChart) {
                updateMetricFocus();
            }
        });

        // Apply filters button
        $('#greenmetrics-report-filters-form').on('submit', function(e) {
            e.preventDefault();
            loadReportData();
        });

        // Save report button
        $('#save-report').on('click', function() {
            $('#save-report-modal').show();
        });

        // Modal close button
        $('.greenmetrics-modal-close, #cancel-save-report').on('click', function() {
            $('#save-report-modal').hide();
        });

        // Save report form submission
        $('#save-report-form').on('submit', function(e) {
            e.preventDefault();
            saveReport();
        });

        // Tab navigation
        $('.tab-button').on('click', function() {
            var tabId = $(this).data('tab');

            // Update active tab
            $('.tab-button').removeClass('active');
            $(this).addClass('active');

            // Show selected tab content
            $('.tab-pane').removeClass('active');
            $('#' + tabId).addClass('active');
        });

        // Export chart as image
        $('#export-chart-image').on('click', function() {
            exportChartAsImage();
        });

        // Export chart data
        $('#export-chart-data').on('click', function() {
            exportChartData();
        });
    }

    /**
     * Load report data based on current filters
     *
     * @function loadReportData
     * @memberof GreenMetricsAdmin.Reports
     * @private
     */
    function loadReportData() {
        // Show loading indicators
        $('.chart-loading, .summary-loading, .trend-loading, .loading-row').show();

        // Update report title
        updateReportTitle();

        // Prepare API parameters
        var params = {
            start_date: currentFilters.startDate,
            end_date: currentFilters.endDate,
            page_id: currentFilters.pageId !== '0' ? currentFilters.pageId : null,
            force_refresh: 'false'
        };

        // Make API request for chart data
        $.ajax({
            url: greenmetricsAdmin.rest_url.replace(/\/$/, '') + '/metrics-by-date',
            method: 'GET',
            data: params,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', greenmetricsAdmin.rest_nonce);
            },
            success: function(response) {
                chartData = response;
                updateChart();
                updateSummary();
            },
            error: function(xhr, status, error) {
                // Use the enhanced error handler
                GreenMetricsErrorHandler.handleRestError(xhr, status, error, '.greenmetrics-report-chart-container', true);
                $('.chart-loading').hide();
            }
        });

        // Make API request for page data
        $.ajax({
            url: greenmetricsAdmin.rest_url.replace(/\/$/, '') + '/metrics',
            method: 'GET',
            data: {
                force_refresh: 'false'
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', greenmetricsAdmin.rest_nonce);
            },
            success: function(response) {
                pageData = response;

                if (!pageData.pages || pageData.pages.length === 0) {
                    // Show no data message if no pages are available
                    $('#top-pages-table tbody, #worst-pages-table tbody, #most-viewed-table tbody').html(
                        '<tr><td colspan="6" class="no-data">' + greenmetricsAdmin.noDataText + '</td></tr>'
                    );
                } else {
                    // Process and display page data
                    updatePageTables();
                }

                // Hide loading indicators
                $('.loading-row').hide();

                // Update trend analysis with overall data
                updateTrendAnalysis();
            },
            error: function(xhr, status, error) {
                // Use the enhanced error handler
                GreenMetricsErrorHandler.handleRestError(xhr, status, error, '.page-performance-table-container', true);
                $('.loading-row').hide();
            }
        });
    }



    /**
     * Update the report title based on current filters
     *
     * @function updateReportTitle
     * @memberof GreenMetricsAdmin.Reports
     * @private
     */
    function updateReportTitle() {
        var title = 'Performance Metrics';

        // Add date range to title
        if (currentFilters.dateRange === 'custom') {
            title += ' (' + currentFilters.startDate + ' to ' + currentFilters.endDate + ')';
        } else {
            title += ' (Last ' + currentFilters.dateRange + ' Days)';
        }

        // Add page filter to title if applicable
        if (currentFilters.pageId !== '0') {
            var pageName = $('#page-filter option:selected').text();
            title += ' for ' + pageName;
        }

        $('#report-title').text(title);
    }

    /**
     * Update the chart with current data
     *
     * @function updateChart
     * @memberof GreenMetricsAdmin.Reports
     * @private
     */
    function updateChart() {
        if (!chartData || !chartData.dates || chartData.dates.length === 0) {
            // Show no data message
            $('.greenmetrics-report-chart-container').html(
                '<canvas id="greenmetrics-report-chart" style="display:none;"></canvas>' +
                '<div class="no-data">' + greenmetricsAdmin.noDataText + '</div>'
            );
            $('.chart-loading').hide();
            return;
        }

        // Initialize or update the chart
        if (typeof GreenMetricsAdmin.ReportsChart !== 'undefined') {
            GreenMetricsAdmin.ReportsChart.init(
                'greenmetrics-report-chart',
                chartData,
                currentFilters.chartType,
                currentFilters.metricFocus,
                currentFilters.comparison
            );
        }

        $('.chart-loading').hide();
    }

    /**
     * Update the chart type based on user selection
     *
     * @function updateChartType
     * @memberof GreenMetricsAdmin.Reports
     * @private
     */
    function updateChartType() {
        if (typeof GreenMetricsAdmin.ReportsChart !== 'undefined') {
            GreenMetricsAdmin.ReportsChart.updateChartType(currentFilters.chartType);
        }
    }

    /**
     * Update which metrics are displayed based on user selection
     *
     * @function updateMetricFocus
     * @memberof GreenMetricsAdmin.Reports
     * @private
     */
    function updateMetricFocus() {
        if (typeof GreenMetricsAdmin.ReportsChart !== 'undefined') {
            GreenMetricsAdmin.ReportsChart.updateMetricFocus(currentFilters.metricFocus);
        }
    }

    /**
     * Update the performance summary section
     *
     * @function updateSummary
     * @memberof GreenMetricsAdmin.Reports
     * @private
     */
    function updateSummary() {
        if (!chartData || !chartData.dates || chartData.dates.length === 0) {
            // Show no data message
            $('#performance-summary').html(
                '<div class="no-data">' + greenmetricsAdmin.noDataText + '</div>'
            );
            $('.summary-loading').hide();
            return;
        }

        var summaryContainer = $('#performance-summary');
        summaryContainer.empty();

        // Calculate summary metrics
        var summary = calculateSummaryMetrics(chartData);

        // Create summary cards
        createSummaryCards(summaryContainer, summary);

        // Hide loading indicator
        $('.summary-loading').hide();
    }

    /**
     * Calculate summary metrics from chart data
     *
     * @function calculateSummaryMetrics
     * @memberof GreenMetricsAdmin.Reports
     * @private
     * @param {Object} data - Chart data
     * @returns {Object} Summary metrics
     */
    function calculateSummaryMetrics(data) {
        var summary = {
            totalCarbonFootprint: 0,
            totalEnergyConsumption: 0,
            totalDataTransfer: 0,
            totalRequests: 0,
            totalPageViews: 0,
            averageCarbonFootprint: 0,
            averageEnergyConsumption: 0,
            averageDataTransfer: 0,
            averageRequests: 0,
            carbonChange: 0,
            energyChange: 0,
            dataChange: 0,
            requestsChange: 0,
            pageViewsChange: 0
        };

        // Calculate totals
        if (data.carbon_footprint && data.carbon_footprint.length > 0) {
            summary.totalCarbonFootprint = data.carbon_footprint.reduce(function(sum, value) {
                return sum + (value || 0);
            }, 0);
        }

        if (data.energy_consumption && data.energy_consumption.length > 0) {
            summary.totalEnergyConsumption = data.energy_consumption.reduce(function(sum, value) {
                return sum + (value || 0);
            }, 0);
        }

        if (data.data_transfer && data.data_transfer.length > 0) {
            summary.totalDataTransfer = data.data_transfer.reduce(function(sum, value) {
                return sum + (value || 0);
            }, 0);
        }

        if (data.requests && data.requests.length > 0) {
            summary.totalRequests = data.requests.reduce(function(sum, value) {
                return sum + (value || 0);
            }, 0);
        }

        if (data.page_views && data.page_views.length > 0) {
            summary.totalPageViews = data.page_views.reduce(function(sum, value) {
                return sum + (value || 0);
            }, 0);
        }

        // Calculate averages
        var dataPoints = data.dates ? data.dates.length : 0;
        if (dataPoints > 0) {
            summary.averageCarbonFootprint = summary.totalCarbonFootprint / dataPoints;
            summary.averageEnergyConsumption = summary.totalEnergyConsumption / dataPoints;
            summary.averageDataTransfer = summary.totalDataTransfer / dataPoints;
            summary.averageRequests = summary.totalRequests / dataPoints;
        }

        // Calculate changes if comparison data is available
        if (data.comparison) {
            var comparisonTotals = {
                carbon: 0,
                energy: 0,
                data: 0,
                requests: 0,
                views: 0
            };

            if (data.comparison.carbon_footprint) {
                comparisonTotals.carbon = data.comparison.carbon_footprint.reduce(function(sum, value) {
                    return sum + (value || 0);
                }, 0);

                if (comparisonTotals.carbon > 0) {
                    summary.carbonChange = ((summary.totalCarbonFootprint - comparisonTotals.carbon) / comparisonTotals.carbon) * 100;
                }
            }

            if (data.comparison.energy_consumption) {
                comparisonTotals.energy = data.comparison.energy_consumption.reduce(function(sum, value) {
                    return sum + (value || 0);
                }, 0);

                if (comparisonTotals.energy > 0) {
                    summary.energyChange = ((summary.totalEnergyConsumption - comparisonTotals.energy) / comparisonTotals.energy) * 100;
                }
            }

            if (data.comparison.data_transfer) {
                comparisonTotals.data = data.comparison.data_transfer.reduce(function(sum, value) {
                    return sum + (value || 0);
                }, 0);

                if (comparisonTotals.data > 0) {
                    summary.dataChange = ((summary.totalDataTransfer - comparisonTotals.data) / comparisonTotals.data) * 100;
                }
            }

            if (data.comparison.requests) {
                comparisonTotals.requests = data.comparison.requests.reduce(function(sum, value) {
                    return sum + (value || 0);
                }, 0);

                if (comparisonTotals.requests > 0) {
                    summary.requestsChange = ((summary.totalRequests - comparisonTotals.requests) / comparisonTotals.requests) * 100;
                }
            }

            if (data.comparison.page_views) {
                comparisonTotals.views = data.comparison.page_views.reduce(function(sum, value) {
                    return sum + (value || 0);
                }, 0);

                if (comparisonTotals.views > 0) {
                    summary.pageViewsChange = ((summary.totalPageViews - comparisonTotals.views) / comparisonTotals.views) * 100;
                }
            }
        }

        return summary;
    }

    /**
     * Create summary cards
     *
     * @function createSummaryCards
     * @memberof GreenMetricsAdmin.Reports
     * @private
     * @param {jQuery} container - Container element
     * @param {Object} summary - Summary metrics
     */
    function createSummaryCards(container, summary) {
        // Total Carbon Footprint
        container.append(createSummaryCard(
            'Total Carbon Footprint',
            formatNumber(summary.totalCarbonFootprint, 2) + ' g CO2e',
            summary.carbonChange,
            'carbon',
            'Lower is better'
        ));

        // Average Carbon Footprint
        container.append(createSummaryCard(
            'Avg. Carbon Footprint',
            formatNumber(summary.averageCarbonFootprint, 2) + ' g CO2e',
            summary.carbonChange,
            'carbon',
            'Per day'
        ));

        // Total Energy Consumption
        container.append(createSummaryCard(
            'Total Energy Consumption',
            formatNumber(summary.totalEnergyConsumption, 2) + ' Wh',
            summary.energyChange,
            'energy',
            'Lower is better'
        ));

        // Total Data Transfer
        container.append(createSummaryCard(
            'Total Data Transfer',
            formatDataSize(summary.totalDataTransfer),
            summary.dataChange,
            'data',
            'Lower is better'
        ));

        // Total Page Views
        container.append(createSummaryCard(
            'Total Page Views',
            formatNumber(summary.totalPageViews),
            summary.pageViewsChange,
            'views',
            'Higher is better'
        ));

        // Average Requests
        container.append(createSummaryCard(
            'Avg. Requests Per Page',
            formatNumber(summary.averageRequests, 1),
            summary.requestsChange,
            'requests',
            'Lower is better'
        ));
    }

    /**
     * Create a summary card
     *
     * @function createSummaryCard
     * @memberof GreenMetricsAdmin.Reports
     * @private
     * @param {string} title - Card title
     * @param {string} value - Card value
     * @param {number} change - Percentage change
     * @param {string} type - Metric type
     * @param {string} note - Additional note
     * @returns {jQuery} Card element
     */
    function createSummaryCard(title, value, change, type, note) {
        var card = $('<div class="summary-card"></div>');

        // Add title
        card.append('<h3>' + title + '</h3>');

        // Add value
        card.append('<div class="summary-value">' + value + '</div>');

        // Add note
        card.append('<div class="summary-note">' + note + '</div>');

        // Add change if available
        if (change !== 0 && !isNaN(change)) {
            var changeClass = '';
            var changeIcon = '';

            // For carbon, energy, data, and requests, negative change is good
            if ((type === 'carbon' || type === 'energy' || type === 'data' || type === 'requests') && change < 0) {
                changeClass = 'positive';
                changeIcon = '<span class="dashicons dashicons-arrow-down-alt"></span>';
            } else if ((type === 'carbon' || type === 'energy' || type === 'data' || type === 'requests') && change > 0) {
                changeClass = 'negative';
                changeIcon = '<span class="dashicons dashicons-arrow-up-alt"></span>';
            } else if (type === 'views' && change > 0) {
                changeClass = 'positive';
                changeIcon = '<span class="dashicons dashicons-arrow-up-alt"></span>';
            } else if (type === 'views' && change < 0) {
                changeClass = 'negative';
                changeIcon = '<span class="dashicons dashicons-arrow-down-alt"></span>';
            } else {
                changeClass = 'neutral';
                changeIcon = '<span class="dashicons dashicons-minus"></span>';
            }

            card.append('<div class="summary-change ' + changeClass + '">' +
                changeIcon + ' ' + Math.abs(change).toFixed(1) + '%' +
                '</div>');
        }

        return card;
    }

    /**
     * Update the page performance tables
     *
     * @function updatePageTables
     * @memberof GreenMetricsAdmin.Reports
     * @private
     */
    function updatePageTables() {
        if (!pageData || !pageData.pages || pageData.pages.length === 0) {
            // Show no data message
            $('#top-pages-table tbody, #worst-pages-table tbody, #most-viewed-table tbody').html(
                '<tr><td colspan="6" class="no-data">' + greenmetricsAdmin.noDataText + '</td></tr>'
            );
            $('.loading-row').hide();
            return;
        }

        // Process page data
        var processedPages = processPageData(pageData.pages);

        // Update top performing pages table
        updateTopPagesTable(processedPages.topPages);

        // Update worst performing pages table
        updateWorstPagesTable(processedPages.worstPages);

        // Update most viewed pages table
        updateMostViewedTable(processedPages.mostViewedPages);

        // Hide loading indicators
        $('.loading-row').hide();
    }

    /**
     * Process page data for tables
     *
     * @function processPageData
     * @memberof GreenMetricsAdmin.Reports
     * @private
     * @param {Array} pages - Array of page data
     * @returns {Object} Processed page data
     */
    function processPageData(pages) {
        // Calculate performance score for each page
        var pagesWithScores = pages.map(function(page) {
            // Calculate a simple performance score based on metrics
            // Lower values are better for carbon, energy, data, and requests
            var carbonScore = 100 - Math.min(100, (page.carbon_footprint / 2)); // Assuming 200g is max (100%)
            var energyScore = 100 - Math.min(100, (page.energy_consumption / 5)); // Assuming 500Wh is max (100%)
            var dataScore = 100 - Math.min(100, (page.data_transfer / 10000)); // Assuming 10MB is max (100%)
            var requestsScore = 100 - Math.min(100, (page.requests / 100)); // Assuming 100 requests is max (100%)

            // Calculate overall score (weighted average)
            var performanceScore = (
                (carbonScore * 0.4) + // Carbon footprint has highest weight
                (energyScore * 0.3) +
                (dataScore * 0.2) +
                (requestsScore * 0.1)
            );

            return {
                ...page,
                performanceScore: Math.round(performanceScore)
            };
        });

        // Sort by performance score (descending)
        var topPages = [...pagesWithScores].sort(function(a, b) {
            return b.performanceScore - a.performanceScore;
        }).slice(0, 10); // Top 10

        // Sort by performance score (ascending)
        var worstPages = [...pagesWithScores].sort(function(a, b) {
            return a.performanceScore - b.performanceScore;
        }).slice(0, 10); // Bottom 10

        // Sort by page views (descending)
        var mostViewedPages = [...pagesWithScores].sort(function(a, b) {
            return b.page_views - a.page_views;
        }).slice(0, 10); // Top 10 by views

        return {
            topPages: topPages,
            worstPages: worstPages,
            mostViewedPages: mostViewedPages
        };
    }

    /**
     * Update the top performing pages table
     *
     * @function updateTopPagesTable
     * @memberof GreenMetricsAdmin.Reports
     * @private
     * @param {Array} pages - Array of top performing pages
     */
    function updateTopPagesTable(pages) {
        var tableBody = $('#top-pages-table tbody');
        tableBody.empty();

        if (pages.length === 0) {
            tableBody.append('<tr><td colspan="6" class="no-data">No data available</td></tr>');
            return;
        }

        pages.forEach(function(page) {
            var row = $('<tr></tr>');

            // Page title/URL
            var pageTitle = page.page_title || 'Unknown Page';
            var pageUrl = page.page_url || '#';
            row.append('<td><a href="' + pageUrl + '" target="_blank">' + pageTitle + '</a></td>');

            // Page views
            row.append('<td>' + formatNumber(page.page_views) + '</td>');

            // Carbon footprint
            row.append('<td>' + formatNumber(page.carbon_footprint, 2) + ' g</td>');

            // Energy consumption
            row.append('<td>' + formatNumber(page.energy_consumption, 2) + ' Wh</td>');

            // Data transfer
            row.append('<td>' + formatDataSize(page.data_transfer) + '</td>');

            // Performance score
            var scoreClass = getScoreClass(page.performanceScore);
            row.append('<td><span class="performance-score ' + scoreClass + '">' + page.performanceScore + '</span></td>');

            tableBody.append(row);
        });
    }

    /**
     * Update the worst performing pages table
     *
     * @function updateWorstPagesTable
     * @memberof GreenMetricsAdmin.Reports
     * @private
     * @param {Array} pages - Array of worst performing pages
     */
    function updateWorstPagesTable(pages) {
        var tableBody = $('#worst-pages-table tbody');
        tableBody.empty();

        if (pages.length === 0) {
            tableBody.append('<tr><td colspan="6" class="no-data">No data available</td></tr>');
            return;
        }

        pages.forEach(function(page) {
            var row = $('<tr></tr>');

            // Page title/URL
            var pageTitle = page.page_title || 'Unknown Page';
            var pageUrl = page.page_url || '#';
            row.append('<td><a href="' + pageUrl + '" target="_blank">' + pageTitle + '</a></td>');

            // Page views
            row.append('<td>' + formatNumber(page.page_views) + '</td>');

            // Carbon footprint
            row.append('<td>' + formatNumber(page.carbon_footprint, 2) + ' g</td>');

            // Energy consumption
            row.append('<td>' + formatNumber(page.energy_consumption, 2) + ' Wh</td>');

            // Data transfer
            row.append('<td>' + formatDataSize(page.data_transfer) + '</td>');

            // Performance score
            var scoreClass = getScoreClass(page.performanceScore);
            row.append('<td><span class="performance-score ' + scoreClass + '">' + page.performanceScore + '</span></td>');

            tableBody.append(row);
        });
    }

    /**
     * Update the most viewed pages table
     *
     * @function updateMostViewedTable
     * @memberof GreenMetricsAdmin.Reports
     * @private
     * @param {Array} pages - Array of most viewed pages
     */
    function updateMostViewedTable(pages) {
        var tableBody = $('#most-viewed-table tbody');
        tableBody.empty();

        if (pages.length === 0) {
            tableBody.append('<tr><td colspan="6" class="no-data">No data available</td></tr>');
            return;
        }

        pages.forEach(function(page) {
            var row = $('<tr></tr>');

            // Page title/URL
            var pageTitle = page.page_title || 'Unknown Page';
            var pageUrl = page.page_url || '#';
            row.append('<td><a href="' + pageUrl + '" target="_blank">' + pageTitle + '</a></td>');

            // Page views
            row.append('<td>' + formatNumber(page.page_views) + '</td>');

            // Carbon footprint
            row.append('<td>' + formatNumber(page.carbon_footprint, 2) + ' g</td>');

            // Energy consumption
            row.append('<td>' + formatNumber(page.energy_consumption, 2) + ' Wh</td>');

            // Data transfer
            row.append('<td>' + formatDataSize(page.data_transfer) + '</td>');

            // Performance score
            var scoreClass = getScoreClass(page.performanceScore);
            row.append('<td><span class="performance-score ' + scoreClass + '">' + page.performanceScore + '</span></td>');

            tableBody.append(row);
        });
    }

    /**
     * Update the page performance tables
     *
     * @function updatePageTables
     * @memberof GreenMetricsAdmin.Reports
     * @private
     */
    function updatePageTables() {
        if (!pageData || !pageData.pages || pageData.pages.length === 0) {
            // Show no data message
            $('#top-pages-table tbody, #worst-pages-table tbody, #most-viewed-table tbody').html(
                '<tr><td colspan="6" class="no-data">' + greenmetricsAdmin.noDataText + '</td></tr>'
            );
            $('.loading-row').hide();
            return;
        }

        // Process page data
        var processedPages = processPageData(pageData.pages);

        // Update top performing pages table
        updateTopPagesTable(processedPages.topPages);

        // Update worst performing pages table
        updateWorstPagesTable(processedPages.worstPages);

        // Update most viewed pages table
        updateMostViewedTable(processedPages.mostViewedPages);

        // Hide loading indicators
        $('.loading-row').hide();
    }

    /**
     * Process page data for tables
     *
     * @function processPageData
     * @memberof GreenMetricsAdmin.Reports
     * @private
     * @param {Array} pages - Array of page data
     * @returns {Object} Processed page data
     */
    function processPageData(pages) {
        // Calculate performance score for each page
        var pagesWithScores = pages.map(function(page) {
            // Calculate a simple performance score based on metrics
            // Lower values are better for carbon, energy, data, and requests
            var carbonScore = 100 - Math.min(100, (page.carbon_footprint / 2)); // Assuming 200g is max (100%)
            var energyScore = 100 - Math.min(100, (page.energy_consumption / 5)); // Assuming 500Wh is max (100%)
            var dataScore = 100 - Math.min(100, (page.data_transfer / 10000)); // Assuming 10MB is max (100%)
            var requestsScore = 100 - Math.min(100, (page.requests / 100)); // Assuming 100 requests is max (100%)

            // Calculate overall score (weighted average)
            var performanceScore = (
                (carbonScore * 0.4) + // Carbon footprint has highest weight
                (energyScore * 0.3) +
                (dataScore * 0.2) +
                (requestsScore * 0.1)
            );

            return {
                ...page,
                performanceScore: Math.round(performanceScore)
            };
        });

        // Sort by performance score (descending)
        var topPages = [...pagesWithScores].sort(function(a, b) {
            return b.performanceScore - a.performanceScore;
        }).slice(0, 10); // Top 10

        // Sort by performance score (ascending)
        var worstPages = [...pagesWithScores].sort(function(a, b) {
            return a.performanceScore - b.performanceScore;
        }).slice(0, 10); // Bottom 10

        // Sort by page views (descending)
        var mostViewedPages = [...pagesWithScores].sort(function(a, b) {
            return b.page_views - a.page_views;
        }).slice(0, 10); // Top 10 by views

        return {
            topPages: topPages,
            worstPages: worstPages,
            mostViewedPages: mostViewedPages
        };
    }

    /**
     * Update the top performing pages table
     *
     * @function updateTopPagesTable
     * @memberof GreenMetricsAdmin.Reports
     * @private
     * @param {Array} pages - Array of top performing pages
     */
    function updateTopPagesTable(pages) {
        var tableBody = $('#top-pages-table tbody');
        tableBody.empty();

        if (pages.length === 0) {
            tableBody.append('<tr><td colspan="6" class="no-data">No data available</td></tr>');
            return;
        }

        pages.forEach(function(page) {
            var row = $('<tr></tr>');

            // Page title/URL
            var pageTitle = page.page_title || 'Unknown Page';
            var pageUrl = page.page_url || '#';
            row.append('<td><a href="' + pageUrl + '" target="_blank">' + pageTitle + '</a></td>');

            // Page views
            row.append('<td>' + formatNumber(page.page_views) + '</td>');

            // Carbon footprint
            row.append('<td>' + formatNumber(page.carbon_footprint, 2) + ' g</td>');

            // Energy consumption
            row.append('<td>' + formatNumber(page.energy_consumption, 2) + ' Wh</td>');

            // Data transfer
            row.append('<td>' + formatDataSize(page.data_transfer) + '</td>');

            // Performance score
            var scoreClass = getScoreClass(page.performanceScore);
            row.append('<td><span class="performance-score ' + scoreClass + '">' + page.performanceScore + '</span></td>');

            tableBody.append(row);
        });
    }

    /**
     * Update the worst performing pages table
     *
     * @function updateWorstPagesTable
     * @memberof GreenMetricsAdmin.Reports
     * @private
     * @param {Array} pages - Array of worst performing pages
     */
    function updateWorstPagesTable(pages) {
        var tableBody = $('#worst-pages-table tbody');
        tableBody.empty();

        if (pages.length === 0) {
            tableBody.append('<tr><td colspan="6" class="no-data">No data available</td></tr>');
            return;
        }

        pages.forEach(function(page) {
            var row = $('<tr></tr>');

            // Page title/URL
            var pageTitle = page.page_title || 'Unknown Page';
            var pageUrl = page.page_url || '#';
            row.append('<td><a href="' + pageUrl + '" target="_blank">' + pageTitle + '</a></td>');

            // Page views
            row.append('<td>' + formatNumber(page.page_views) + '</td>');

            // Carbon footprint
            row.append('<td>' + formatNumber(page.carbon_footprint, 2) + ' g</td>');

            // Energy consumption
            row.append('<td>' + formatNumber(page.energy_consumption, 2) + ' Wh</td>');

            // Data transfer
            row.append('<td>' + formatDataSize(page.data_transfer) + '</td>');

            // Performance score
            var scoreClass = getScoreClass(page.performanceScore);
            row.append('<td><span class="performance-score ' + scoreClass + '">' + page.performanceScore + '</span></td>');

            tableBody.append(row);
        });
    }

    /**
     * Update the most viewed pages table
     *
     * @function updateMostViewedTable
     * @memberof GreenMetricsAdmin.Reports
     * @private
     * @param {Array} pages - Array of most viewed pages
     */
    function updateMostViewedTable(pages) {
        var tableBody = $('#most-viewed-table tbody');
        tableBody.empty();

        if (pages.length === 0) {
            tableBody.append('<tr><td colspan="6" class="no-data">No data available</td></tr>');
            return;
        }

        pages.forEach(function(page) {
            var row = $('<tr></tr>');

            // Page title/URL
            var pageTitle = page.page_title || 'Unknown Page';
            var pageUrl = page.page_url || '#';
            row.append('<td><a href="' + pageUrl + '" target="_blank">' + pageTitle + '</a></td>');

            // Page views
            row.append('<td>' + formatNumber(page.page_views) + '</td>');

            // Carbon footprint
            row.append('<td>' + formatNumber(page.carbon_footprint, 2) + ' g</td>');

            // Energy consumption
            row.append('<td>' + formatNumber(page.energy_consumption, 2) + ' Wh</td>');

            // Data transfer
            row.append('<td>' + formatDataSize(page.data_transfer) + '</td>');

            // Performance score
            var scoreClass = getScoreClass(page.performanceScore);
            row.append('<td><span class="performance-score ' + scoreClass + '">' + page.performanceScore + '</span></td>');

            tableBody.append(row);
        });
    }

    /**
     * Get CSS class for performance score
     *
     * @function getScoreClass
     * @memberof GreenMetricsAdmin.Reports
     * @private
     * @param {number} score - Performance score
     * @returns {string} CSS class
     */
    function getScoreClass(score) {
        if (score >= 80) {
            return 'excellent';
        } else if (score >= 60) {
            return 'good';
        } else if (score >= 40) {
            return 'average';
        } else if (score >= 20) {
            return 'poor';
        } else {
            return 'critical';
        }
    }

    /**
     * Format a number with commas and decimal places
     *
     * @function formatNumber
     * @memberof GreenMetricsAdmin.Reports
     * @private
     * @param {number} number - Number to format
     * @param {number} decimals - Number of decimal places
     * @returns {string} Formatted number
     */
    function formatNumber(number, decimals) {
        if (number === undefined || number === null) {
            return '0';
        }

        decimals = decimals || 0;
        return number.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    /**
     * Format data size in appropriate units
     *
     * @function formatDataSize
     * @memberof GreenMetricsAdmin.Reports
     * @private
     * @param {number} bytes - Size in bytes
     * @returns {string} Formatted size
     */
    function formatDataSize(bytes) {
        if (bytes === undefined || bytes === null) {
            return '0 KB';
        }

        // Convert to KB
        var kb = bytes / 1024;

        if (kb < 1024) {
            return kb.toFixed(2) + ' KB';
        } else {
            var mb = kb / 1024;
            return mb.toFixed(2) + ' MB';
        }
    }

    /**
     * Update the trend analysis section
     *
     * @function updateTrendAnalysis
     * @memberof GreenMetricsAdmin.Reports
     * @private
     */
    function updateTrendAnalysis() {
        if (!chartData || !pageData || !chartData.dates || chartData.dates.length === 0) {
            // Show no data message
            $('#trend-analysis').html(
                '<div class="no-data">' + greenmetricsAdmin.noDataText + '</div>'
            );
            $('.trend-loading').hide();
            return;
        }

        var trendContainer = $('#trend-analysis');
        trendContainer.empty();

        // Analyze trends
        var trends = analyzeTrends(chartData, pageData);

        // Create trend cards
        createTrendCards(trendContainer, trends);

        // Hide loading indicator
        $('.trend-loading').hide();
    }

    /**
     * Analyze trends in the data
     *
     * @function analyzeTrends
     * @memberof GreenMetricsAdmin.Reports
     * @private
     * @param {Object} chartData - Chart data
     * @param {Object} pageData - Page data
     * @returns {Array} Trend analysis results
     */
    function analyzeTrends(chartData, pageData) {
        var trends = [];

        // Check if we have enough data for trend analysis
        if (!chartData.dates || chartData.dates.length < 3) {
            trends.push({
                title: 'Insufficient Data for Trend Analysis',
                description: 'At least 3 days of data are required for trend analysis. Please select a longer date range.',
                type: 'info'
            });
            return trends;
        }

        // Analyze carbon footprint trend
        if (chartData.carbon_footprint && chartData.carbon_footprint.length > 0) {
            var carbonTrend = calculateTrend(chartData.carbon_footprint);
            var carbonTrendDescription = '';
            var carbonTrendType = '';

            if (carbonTrend < -0.05) {
                carbonTrendDescription = 'Your carbon footprint is decreasing, which is great for the environment! Continue optimizing your site to maintain this positive trend.';
                carbonTrendType = 'positive';
            } else if (carbonTrend > 0.05) {
                carbonTrendDescription = 'Your carbon footprint is increasing. Consider optimizing your site to reduce its environmental impact.';
                carbonTrendType = 'negative';
            } else {
                carbonTrendDescription = 'Your carbon footprint is stable. While stability is good, continue looking for opportunities to reduce your environmental impact.';
                carbonTrendType = 'neutral';
            }

            trends.push({
                title: 'Carbon Footprint Trend',
                description: carbonTrendDescription,
                type: carbonTrendType,
                metrics: [
                    {
                        label: 'Trend Direction',
                        value: getTrendDirectionText(carbonTrend, 'carbon')
                    },
                    {
                        label: 'Daily Change',
                        value: formatNumber(carbonTrend * 100, 2) + '%'
                    }
                ]
            });
        }

        // Analyze page performance
        if (pageData.pages && pageData.pages.length > 0) {
            // Find pages with high carbon footprint
            var highCarbonPages = pageData.pages
                .filter(function(page) {
                    return page.carbon_footprint > 5; // More than 5g CO2e
                })
                .sort(function(a, b) {
                    return b.carbon_footprint - a.carbon_footprint;
                })
                .slice(0, 3);

            if (highCarbonPages.length > 0) {
                var pagesList = highCarbonPages.map(function(page) {
                    return '<li><strong>' + (page.page_title || 'Unknown Page') + '</strong>: ' +
                        formatNumber(page.carbon_footprint, 2) + ' g CO2e</li>';
                }).join('');

                trends.push({
                    title: 'Pages with High Carbon Footprint',
                    description: 'These pages have a higher than average carbon footprint. Consider optimizing them to reduce their environmental impact:',
                    type: 'negative',
                    customContent: '<ul class="trend-pages-list">' + pagesList + '</ul>' +
                        '<p>Optimization tips:</p>' +
                        '<ul>' +
                        '<li>Reduce image sizes and use modern formats (WebP)</li>' +
                        '<li>Minimize JavaScript and CSS</li>' +
                        '<li>Reduce third-party requests</li>' +
                        '<li>Implement lazy loading for images and videos</li>' +
                        '</ul>'
                });
            }
        }

        // Analyze data transfer trend
        if (chartData.data_transfer && chartData.data_transfer.length > 0) {
            var dataTrend = calculateTrend(chartData.data_transfer);
            var dataTrendDescription = '';
            var dataTrendType = '';

            if (dataTrend < -0.05) {
                dataTrendDescription = 'Your data transfer is decreasing, which improves page load times and reduces environmental impact. Great job!';
                dataTrendType = 'positive';
            } else if (dataTrend > 0.05) {
                dataTrendDescription = 'Your data transfer is increasing. Consider optimizing your site to reduce data transfer and improve performance.';
                dataTrendType = 'negative';
            } else {
                dataTrendDescription = 'Your data transfer is stable. Continue looking for opportunities to reduce data transfer for better performance.';
                dataTrendType = 'neutral';
            }

            trends.push({
                title: 'Data Transfer Trend',
                description: dataTrendDescription,
                type: dataTrendType,
                metrics: [
                    {
                        label: 'Trend Direction',
                        value: getTrendDirectionText(dataTrend, 'data')
                    },
                    {
                        label: 'Daily Change',
                        value: formatNumber(dataTrend * 100, 2) + '%'
                    }
                ]
            });
        }

        // Add overall performance assessment
        var overallAssessment = '';
        var overallType = '';

        if (trends.filter(function(trend) { return trend.type === 'positive'; }).length > trends.filter(function(trend) { return trend.type === 'negative'; }).length) {
            overallAssessment = 'Your site is showing positive trends in environmental performance. Continue your optimization efforts to maintain this positive direction.';
            overallType = 'positive';
        } else if (trends.filter(function(trend) { return trend.type === 'negative'; }).length > trends.filter(function(trend) { return trend.type === 'positive'; }).length) {
            overallAssessment = 'Your site is showing some concerning trends in environmental performance. Consider implementing the suggested optimizations to improve.';
            overallType = 'negative';
        } else {
            overallAssessment = 'Your site\'s environmental performance is relatively stable. While stability is good, continue looking for opportunities to improve.';
            overallType = 'neutral';
        }

        trends.push({
            title: 'Overall Performance Assessment',
            description: overallAssessment,
            type: overallType
        });

        return trends;
    }

    /**
     * Calculate trend in a data series
     *
     * @function calculateTrend
     * @memberof GreenMetricsAdmin.Reports
     * @private
     * @param {Array} data - Data series
     * @returns {number} Trend coefficient
     */
    function calculateTrend(data) {
        if (!data || data.length < 3) {
            return 0;
        }

        // Simple linear regression
        var n = data.length;
        var sum_x = 0;
        var sum_y = 0;
        var sum_xy = 0;
        var sum_xx = 0;

        for (var i = 0; i < n; i++) {
            var x = i;
            var y = data[i] || 0;

            sum_x += x;
            sum_y += y;
            sum_xy += x * y;
            sum_xx += x * x;
        }

        // Calculate slope
        var slope = (n * sum_xy - sum_x * sum_y) / (n * sum_xx - sum_x * sum_x);

        // Normalize by average value
        var avg = sum_y / n;
        if (avg !== 0) {
            return slope / avg;
        }

        return 0;
    }

    /**
     * Get trend direction text
     *
     * @function getTrendDirectionText
     * @memberof GreenMetricsAdmin.Reports
     * @private
     * @param {number} trend - Trend coefficient
     * @param {string} type - Metric type
     * @returns {string} Trend direction text
     */
    function getTrendDirectionText(trend, type) {
        if (Math.abs(trend) < 0.01) {
            return 'Stable';
        }

        if ((type === 'carbon' || type === 'energy' || type === 'data' || type === 'requests') && trend < 0) {
            return 'Decreasing ✓';
        } else if ((type === 'carbon' || type === 'energy' || type === 'data' || type === 'requests') && trend > 0) {
            return 'Increasing ✗';
        } else if (type === 'views' && trend > 0) {
            return 'Increasing ✓';
        } else if (type === 'views' && trend < 0) {
            return 'Decreasing ✗';
        }

        return 'Stable';
    }

    /**
     * Create trend cards
     *
     * @function createTrendCards
     * @memberof GreenMetricsAdmin.Reports
     * @private
     * @param {jQuery} container - Container element
     * @param {Array} trends - Trend analysis results
     */
    function createTrendCards(container, trends) {
        trends.forEach(function(trend) {
            var card = $('<div class="trend-card trend-' + trend.type + '"></div>');

            // Add title
            card.append('<h3>' + trend.title + '</h3>');

            // Add description
            card.append('<div class="trend-description">' + trend.description + '</div>');

            // Add custom content if available
            if (trend.customContent) {
                card.append('<div class="trend-custom-content">' + trend.customContent + '</div>');
            }

            // Add metrics if available
            if (trend.metrics && trend.metrics.length > 0) {
                var metricsHtml = '<div class="trend-metrics">';

                trend.metrics.forEach(function(metric) {
                    metricsHtml += '<div class="trend-metric">' +
                        '<div class="trend-metric-label">' + metric.label + '</div>' +
                        '<div class="trend-metric-value">' + metric.value + '</div>' +
                        '</div>';
                });

                metricsHtml += '</div>';
                card.append(metricsHtml);
            }

            container.append(card);
        });
    }

    /**
     * Save the current report configuration
     *
     * @function saveReport
     * @memberof GreenMetricsAdmin.Reports
     * @private
     */
    function saveReport() {
        // Get report name and description
        var reportName = $('#report-name').val();
        var reportDescription = $('#report-description').val();
        var setAsDefault = $('#set-as-default').is(':checked');

        if (!reportName) {
            alert('Please enter a report name.');
            return;
        }

        // Create report configuration
        var reportConfig = {
            id: 'report_' + Date.now(),
            name: reportName,
            description: reportDescription,
            isDefault: setAsDefault,
            filters: { ...currentFilters },
            created: new Date().toISOString()
        };

        // Add to saved reports
        if (setAsDefault) {
            // Remove default flag from other reports
            savedReports.forEach(function(report) {
                report.isDefault = false;
            });
        }

        savedReports.push(reportConfig);

        // Save to localStorage
        try {
            localStorage.setItem('greenmetrics_saved_reports', JSON.stringify(savedReports));

            // Show success message
            alert('Report saved successfully!');

            // Update saved reports dropdown (if implemented)
            updateSavedReportsDropdown();

            // Hide modal
            $('#save-report-modal').hide();

            // Reset form
            $('#report-name').val('');
            $('#report-description').val('');
            $('#set-as-default').prop('checked', false);
        } catch (e) {
            console.error('Error saving report:', e);
            alert('Error saving report: ' + e.message);
        }
    }

    /**
     * Update the saved reports dropdown
     *
     * @function updateSavedReportsDropdown
     * @memberof GreenMetricsAdmin.Reports
     * @private
     */
    function updateSavedReportsDropdown() {
        // This is a placeholder for future implementation
        // Will be implemented when we add a saved reports dropdown to the UI
    }

    /**
     * Export the current chart as an image
     *
     * @function exportChartAsImage
     * @memberof GreenMetricsAdmin.Reports
     * @private
     */
    function exportChartAsImage() {
        if (typeof GreenMetricsAdmin.ReportsChart !== 'undefined') {
            // Generate filename based on current filters
            var filename = 'greenmetrics-report';

            if (currentFilters.pageId !== '0') {
                filename += '-page-' + currentFilters.pageId;
            }

            if (currentFilters.metricFocus !== 'all') {
                filename += '-' + currentFilters.metricFocus;
            }

            filename += '-' + currentFilters.startDate + '-to-' + currentFilters.endDate;
            filename += '.png';

            // Export chart as image
            GreenMetricsAdmin.ReportsChart.exportAsImage(filename);
        }
    }

    /**
     * Export the current chart data
     *
     * @function exportChartData
     * @memberof GreenMetricsAdmin.Reports
     * @private
     */
    function exportChartData() {
        if (!chartData) {
            return;
        }

        // Prepare export data
        var exportData = prepareExportData(chartData);

        // Generate filename based on current filters
        var filename = 'greenmetrics-report';

        if (currentFilters.pageId !== '0') {
            filename += '-page-' + currentFilters.pageId;
        }

        if (currentFilters.metricFocus !== 'all') {
            filename += '-' + currentFilters.metricFocus;
        }

        filename += '-' + currentFilters.startDate + '-to-' + currentFilters.endDate;
        filename += '.csv';

        // Download CSV file
        downloadCSV(exportData, filename);
    }

    /**
     * Prepare data for export
     *
     * @function prepareExportData
     * @memberof GreenMetricsAdmin.Reports
     * @private
     * @param {Object} data - Chart data
     * @returns {Array} Export data
     */
    function prepareExportData(data) {
        var exportData = [];

        // Add header row
        var headers = ['Date', 'Carbon Footprint (g CO2e)', 'Energy Consumption (Wh)', 'Data Transfer (KB)', 'HTTP Requests', 'Page Views'];
        exportData.push(headers);

        // Add data rows
        if (data.dates) {
            for (var i = 0; i < data.dates.length; i++) {
                var row = [
                    data.dates[i],
                    data.carbon_footprint ? data.carbon_footprint[i] || 0 : 0,
                    data.energy_consumption ? data.energy_consumption[i] || 0 : 0,
                    data.data_transfer ? data.data_transfer[i] || 0 : 0,
                    data.requests ? data.requests[i] || 0 : 0,
                    data.page_views ? data.page_views[i] || 0 : 0
                ];

                exportData.push(row);
            }
        }

        return exportData;
    }

    /**
     * Download data as CSV file
     *
     * @function downloadCSV
     * @memberof GreenMetricsAdmin.Reports
     * @private
     * @param {Array} data - Export data
     * @param {string} filename - Filename
     */
    function downloadCSV(data, filename) {
        try {
            // Convert data to CSV string
            var csvContent = '';

            data.forEach(function(row) {
                csvContent += row.join(',') + '\n';
            });

            // Use Blob API when possible to avoid security warnings
            if (window.Blob && window.URL && window.URL.createObjectURL) {
                var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                var url = URL.createObjectURL(blob);
                var link = document.createElement('a');
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.setAttribute('rel', 'noopener noreferrer');

                // Trigger download
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                // Clean up the object URL
                setTimeout(function() {
                    URL.revokeObjectURL(url);
                }, 100);
            } else {
                // Fallback for older browsers
                var encodedUri = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvContent);
                var link = document.createElement('a');
                link.setAttribute('href', encodedUri);
                link.setAttribute('download', filename);
                link.setAttribute('rel', 'noopener noreferrer');

                // Trigger download
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        } catch (e) {
            console.error('Error downloading CSV:', e);
            alert('Error downloading CSV. Please try again.');
        }
    }

    // Public API
    return {
        init: init
    };
})(jQuery);
