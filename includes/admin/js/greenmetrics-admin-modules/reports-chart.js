/**
 * GreenMetrics Admin Reports Chart Module
 * Handles the chart functionality for the advanced reports
 *
 * @module GreenMetricsAdmin.ReportsChart
 * @requires jQuery
 * @requires Chart.js
 */
// Ensure namespace exists
var GreenMetricsAdmin = GreenMetricsAdmin || {};

// Add reports chart functionality to namespace
GreenMetricsAdmin.ReportsChart = (function ($) {
    'use strict';

    // Module variables
    var reportChart = null;
    var chartCanvas = null;
    var chartContext = null;
    var chartData = null;
    var chartOptions = null;
    var currentChartType = 'line';
    var currentMetricFocus = 'all';
    var comparisonMode = 'none';
    var chartColors = {
        carbon: 'rgba(76, 175, 80, 1)',
        energy: 'rgba(33, 150, 243, 1)',
        data: 'rgba(255, 152, 0, 1)',
        requests: 'rgba(156, 39, 176, 1)',
        views: 'rgba(96, 125, 139, 1)'
    };
    var chartColorsTransparent = {
        carbon: 'rgba(76, 175, 80, 0.2)',
        energy: 'rgba(33, 150, 243, 0.2)',
        data: 'rgba(255, 152, 0, 0.2)',
        requests: 'rgba(156, 39, 176, 0.2)',
        views: 'rgba(96, 125, 139, 0.2)'
    };
    var comparisonColors = {
        carbon: 'rgba(76, 175, 80, 0.5)',
        energy: 'rgba(33, 150, 243, 0.5)',
        data: 'rgba(255, 152, 0, 0.5)',
        requests: 'rgba(156, 39, 176, 0.5)',
        views: 'rgba(96, 125, 139, 0.5)'
    };

    /**
     * Initialize the chart
     *
     * @function init
     * @memberof GreenMetricsAdmin.ReportsChart
     * @public
     * @param {string} canvasId - The ID of the canvas element
     * @param {Object} data - The chart data
     * @param {string} chartType - The chart type (line, bar, pie)
     * @param {string} metricFocus - The metric to focus on
     * @param {string} comparison - The comparison mode
     */
    function init(canvasId, data, chartType, metricFocus, comparison) {
        // Store parameters
        chartData = data;
        currentChartType = chartType || 'line';
        currentMetricFocus = metricFocus || 'all';
        comparisonMode = comparison || 'none';

        // Get canvas and context
        chartCanvas = document.getElementById(canvasId);
        if (!chartCanvas) {
            console.error('Chart canvas not found: ' + canvasId);
            return;
        }
        chartContext = chartCanvas.getContext('2d');

        // Create chart
        createChart();
    }

    /**
     * Create the chart
     *
     * @function createChart
     * @memberof GreenMetricsAdmin.ReportsChart
     * @private
     */
    function createChart() {
        // Prepare chart data
        var preparedData = prepareChartData();

        // Prepare chart options
        chartOptions = getChartOptions();

        // Destroy existing chart if it exists
        if (reportChart) {
            reportChart.destroy();
        }

        // Create new chart
        reportChart = new Chart(chartContext, {
            type: currentChartType,
            data: preparedData,
            options: chartOptions
        });

        // Update legend
        updateLegend();
    }

    /**
     * Prepare chart data based on current settings
     *
     * @function prepareChartData
     * @memberof GreenMetricsAdmin.ReportsChart
     * @private
     * @returns {Object} Prepared chart data
     */
    function prepareChartData() {
        var datasets = [];
        var labels = [];

        // Extract labels (dates)
        if (chartData && chartData.dates) {
            labels = chartData.dates.map(function(date) {
                return formatDate(date);
            });
        }

        // Create datasets based on metric focus
        if (currentMetricFocus === 'all' || currentMetricFocus === 'carbon_footprint') {
            datasets.push({
                label: 'Carbon Footprint (g CO2e)',
                data: chartData.carbon_footprint || [],
                backgroundColor: chartColorsTransparent.carbon,
                borderColor: chartColors.carbon,
                borderWidth: 2,
                tension: 0.1,
                fill: true
            });

            // Add comparison dataset if enabled
            if (comparisonMode === 'previous' && chartData.comparison && chartData.comparison.carbon_footprint) {
                datasets.push({
                    label: 'Previous Carbon Footprint (g CO2e)',
                    data: chartData.comparison.carbon_footprint || [],
                    backgroundColor: 'transparent',
                    borderColor: comparisonColors.carbon,
                    borderWidth: 2,
                    borderDash: [5, 5],
                    tension: 0.1,
                    fill: false
                });
            }
        }

        if (currentMetricFocus === 'all' || currentMetricFocus === 'energy_consumption') {
            datasets.push({
                label: 'Energy Consumption (Wh)',
                data: chartData.energy_consumption || [],
                backgroundColor: chartColorsTransparent.energy,
                borderColor: chartColors.energy,
                borderWidth: 2,
                tension: 0.1,
                fill: true
            });

            // Add comparison dataset if enabled
            if (comparisonMode === 'previous' && chartData.comparison && chartData.comparison.energy_consumption) {
                datasets.push({
                    label: 'Previous Energy Consumption (Wh)',
                    data: chartData.comparison.energy_consumption || [],
                    backgroundColor: 'transparent',
                    borderColor: comparisonColors.energy,
                    borderWidth: 2,
                    borderDash: [5, 5],
                    tension: 0.1,
                    fill: false
                });
            }
        }

        if (currentMetricFocus === 'all' || currentMetricFocus === 'data_transfer') {
            datasets.push({
                label: 'Data Transfer (KB)',
                data: chartData.data_transfer || [],
                backgroundColor: chartColorsTransparent.data,
                borderColor: chartColors.data,
                borderWidth: 2,
                tension: 0.1,
                fill: true
            });

            // Add comparison dataset if enabled
            if (comparisonMode === 'previous' && chartData.comparison && chartData.comparison.data_transfer) {
                datasets.push({
                    label: 'Previous Data Transfer (KB)',
                    data: chartData.comparison.data_transfer || [],
                    backgroundColor: 'transparent',
                    borderColor: comparisonColors.data,
                    borderWidth: 2,
                    borderDash: [5, 5],
                    tension: 0.1,
                    fill: false
                });
            }
        }

        if (currentMetricFocus === 'all' || currentMetricFocus === 'requests') {
            datasets.push({
                label: 'HTTP Requests',
                data: chartData.requests || [],
                backgroundColor: chartColorsTransparent.requests,
                borderColor: chartColors.requests,
                borderWidth: 2,
                tension: 0.1,
                fill: true
            });

            // Add comparison dataset if enabled
            if (comparisonMode === 'previous' && chartData.comparison && chartData.comparison.requests) {
                datasets.push({
                    label: 'Previous HTTP Requests',
                    data: chartData.comparison.requests || [],
                    backgroundColor: 'transparent',
                    borderColor: comparisonColors.requests,
                    borderWidth: 2,
                    borderDash: [5, 5],
                    tension: 0.1,
                    fill: false
                });
            }
        }

        if (currentMetricFocus === 'all' || currentMetricFocus === 'page_views') {
            datasets.push({
                label: 'Page Views',
                data: chartData.page_views || [],
                backgroundColor: chartColorsTransparent.views,
                borderColor: chartColors.views,
                borderWidth: 2,
                tension: 0.1,
                fill: true
            });

            // Add comparison dataset if enabled
            if (comparisonMode === 'previous' && chartData.comparison && chartData.comparison.page_views) {
                datasets.push({
                    label: 'Previous Page Views',
                    data: chartData.comparison.page_views || [],
                    backgroundColor: 'transparent',
                    borderColor: comparisonColors.views,
                    borderWidth: 2,
                    borderDash: [5, 5],
                    tension: 0.1,
                    fill: false
                });
            }
        }

        return {
            labels: labels,
            datasets: datasets
        };
    }

    /**
     * Get chart options based on chart type
     *
     * @function getChartOptions
     * @memberof GreenMetricsAdmin.ReportsChart
     * @private
     * @returns {Object} Chart options
     */
    function getChartOptions() {
        var options = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false, // We'll create our own legend
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            var label = context.dataset.label || '';
                            var value = context.parsed.y;
                            return label + ': ' + formatValue(value, label);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        callback: function(value, index, values) {
                            // Format based on metric focus
                            if (currentMetricFocus === 'carbon_footprint') {
                                return value + ' g';
                            } else if (currentMetricFocus === 'energy_consumption') {
                                return value + ' Wh';
                            } else if (currentMetricFocus === 'data_transfer') {
                                return value + ' KB';
                            } else {
                                return value;
                            }
                        }
                    }
                }
            }
        };

        // Adjust options based on chart type
        if (currentChartType === 'pie' || currentChartType === 'doughnut') {
            // Remove scales for pie/doughnut charts
            delete options.scales;
        }

        return options;
    }

    /**
     * Update the chart type
     *
     * @function updateChartType
     * @memberof GreenMetricsAdmin.ReportsChart
     * @public
     * @param {string} chartType - The new chart type
     */
    function updateChartType(chartType) {
        if (!reportChart) {
            return;
        }

        currentChartType = chartType;

        // Update chart type
        reportChart.config.type = chartType;

        // Update options
        reportChart.options = getChartOptions();

        // Update chart
        reportChart.update();
    }

    /**
     * Update the metric focus
     *
     * @function updateMetricFocus
     * @memberof GreenMetricsAdmin.ReportsChart
     * @public
     * @param {string} metricFocus - The new metric focus
     */
    function updateMetricFocus(metricFocus) {
        if (!reportChart) {
            return;
        }

        currentMetricFocus = metricFocus;

        // Update chart data
        reportChart.data = prepareChartData();

        // Update options
        reportChart.options = getChartOptions();

        // Update chart
        reportChart.update();

        // Update legend
        updateLegend();
    }

    /**
     * Update the comparison mode
     *
     * @function updateComparisonMode
     * @memberof GreenMetricsAdmin.ReportsChart
     * @public
     * @param {string} comparison - The new comparison mode
     */
    function updateComparisonMode(comparison) {
        if (!reportChart) {
            return;
        }

        comparisonMode = comparison;

        // Update chart data
        reportChart.data = prepareChartData();

        // Update chart
        reportChart.update();

        // Update legend
        updateLegend();
    }

    /**
     * Update the chart data
     *
     * @function updateChartData
     * @memberof GreenMetricsAdmin.ReportsChart
     * @public
     * @param {Object} data - The new chart data
     */
    function updateChartData(data) {
        chartData = data;

        if (!reportChart) {
            createChart();
            return;
        }

        // Update chart data
        reportChart.data = prepareChartData();

        // Update chart
        reportChart.update();

        // Update legend
        updateLegend();
    }

    /**
     * Update the chart legend
     *
     * @function updateLegend
     * @memberof GreenMetricsAdmin.ReportsChart
     * @private
     */
    function updateLegend() {
        var legendContainer = $('#report-chart-legend');
        if (!legendContainer.length || !reportChart) {
            return;
        }

        // Clear existing legend
        legendContainer.empty();

        // Create legend items
        var legendHtml = '<ul class="chart-legend">';

        reportChart.data.datasets.forEach(function(dataset, index) {
            var color = dataset.borderColor;
            var label = dataset.label;
            var isDashed = dataset.borderDash ? true : false;

            legendHtml += '<li>';
            legendHtml += '<span class="legend-color" style="background-color: ' + color + ';';
            if (isDashed) {
                legendHtml += ' border: 1px dashed ' + color + '; background-color: transparent;';
            }
            legendHtml += '"></span>';
            legendHtml += '<span class="legend-label">' + label + '</span>';
            legendHtml += '</li>';
        });

        legendHtml += '</ul>';

        // Add legend to container
        legendContainer.html(legendHtml);
    }

    /**
     * Format a date string
     *
     * @function formatDate
     * @memberof GreenMetricsAdmin.ReportsChart
     * @private
     * @param {string} dateString - The date string to format
     * @returns {string} Formatted date string
     */
    function formatDate(dateString) {
        var date = new Date(dateString);
        return date.toLocaleDateString();
    }

    /**
     * Format a value based on its label
     *
     * @function formatValue
     * @memberof GreenMetricsAdmin.ReportsChart
     * @private
     * @param {number} value - The value to format
     * @param {string} label - The label of the value
     * @returns {string} Formatted value
     */
    function formatValue(value, label) {
        if (label.includes('Carbon')) {
            return value.toFixed(2) + ' g CO2e';
        } else if (label.includes('Energy')) {
            return value.toFixed(2) + ' Wh';
        } else if (label.includes('Data')) {
            return value.toFixed(2) + ' KB';
        } else {
            return value;
        }
    }

    /**
     * Export the chart as an image
     *
     * @function exportAsImage
     * @memberof GreenMetricsAdmin.ReportsChart
     * @public
     * @param {string} filename - The filename for the exported image
     */
    function exportAsImage(filename) {
        if (!reportChart) {
            return;
        }

        try {
            // Create a temporary canvas to add security attributes
            var tempCanvas = document.createElement('canvas');
            var tempContext = tempCanvas.getContext('2d');

            // Set canvas dimensions to match the chart
            tempCanvas.width = chartCanvas.width;
            tempCanvas.height = chartCanvas.height;

            // Draw the chart on the temporary canvas
            tempContext.drawImage(chartCanvas, 0, 0);

            // Create a temporary link with secure attributes
            var link = document.createElement('a');

            // Use toBlob instead of toDataURL when possible to avoid security warnings
            if (tempCanvas.toBlob) {
                tempCanvas.toBlob(function(blob) {
                    var url = URL.createObjectURL(blob);
                    link.href = url;
                    link.download = filename || 'greenmetrics-report.png';

                    // Trigger download
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    // Clean up the object URL
                    setTimeout(function() {
                        URL.revokeObjectURL(url);
                    }, 100);
                });
            } else {
                // Fallback to base64 image if toBlob is not supported
                link.href = reportChart.toBase64Image();
                link.download = filename || 'greenmetrics-report.png';
                link.setAttribute('rel', 'noopener noreferrer');

                // Trigger download
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        } catch (e) {
            console.error('Error exporting chart as image:', e);
            alert('Error exporting chart as image. Please try again.');
        }
    }

    /**
     * Get the current chart data
     *
     * @function getChartData
     * @memberof GreenMetricsAdmin.ReportsChart
     * @public
     * @returns {Object} The current chart data
     */
    function getChartData() {
        return chartData;
    }

    // Public API
    return {
        init: init,
        updateChartType: updateChartType,
        updateMetricFocus: updateMetricFocus,
        updateComparisonMode: updateComparisonMode,
        updateChartData: updateChartData,
        exportAsImage: exportAsImage,
        getChartData: getChartData
    };
})(jQuery);
