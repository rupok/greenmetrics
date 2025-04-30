/**
 * GreenMetrics Formatter Utility
 * 
 * Provides standardized formatting functions for metrics display
 * to ensure consistency throughout the plugin.
 */
const GreenMetricsFormatter = (function() {
    'use strict';

    /**
     * Format carbon emissions for display
     * 
     * @param {number} grams - Carbon emissions in grams
     * @return {string} Formatted string with appropriate unit
     */
    function formatCarbonEmissions(grams) {
        if (!isValidNumber(grams)) {
            return '0 g';
        }

        if (grams >= 1000) {
            return (grams / 1000).toFixed(2) + ' kg';
        } else if (grams >= 1) {
            return grams.toFixed(2) + ' g';
        } else if (grams >= 0.001) {
            return (grams * 1000).toFixed(2) + ' mg';
        } else {
            // For extremely small values, increase precision to avoid showing 0
            return grams.toFixed(6) + ' g';
        }
    }

    /**
     * Format data transfer for display
     * 
     * @param {number} bytes - Data transfer in bytes
     * @return {string} Formatted string with appropriate unit
     */
    function formatDataTransfer(bytes) {
        return formatBytes(bytes);
    }

    /**
     * Format energy consumption for display
     * 
     * @param {number} kwh - Energy consumption in kWh
     * @return {string} Formatted string with appropriate unit
     */
    function formatEnergyConsumption(kwh) {
        if (!isValidNumber(kwh)) {
            return '0 kWh';
        }

        return kwh.toFixed(4) + ' kWh';
    }

    /**
     * Format load time for display
     * 
     * @param {number} seconds - Load time in seconds
     * @return {string} Formatted string with appropriate unit
     */
    function formatLoadTime(seconds) {
        if (!isValidNumber(seconds)) {
            return '0 ms';
        }

        if (seconds < 1) {
            return (seconds * 1000).toFixed(2) + ' ms';
        } else {
            return seconds.toFixed(2) + ' s';
        }
    }

    /**
     * Format performance score for display
     * 
     * @param {number} score - Performance score (0-100)
     * @return {string} Formatted string with percentage
     */
    function formatPerformanceScore(score) {
        if (!isValidNumber(score)) {
            return '0%';
        }

        return score.toFixed(2) + '%';
    }

    /**
     * Format number of views for display
     * 
     * @param {number} views - Number of views
     * @return {string} Formatted string
     */
    function formatViews(views) {
        if (!isValidNumber(views)) {
            return '0';
        }

        return views.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    /**
     * Format number of requests for display
     * 
     * @param {number} requests - Number of HTTP requests
     * @return {string} Formatted string
     */
    function formatRequests(requests) {
        if (!isValidNumber(requests)) {
            return '0';
        }

        return requests.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    /**
     * Format bytes to human-readable format
     * 
     * @param {number} bytes - The size in bytes
     * @param {number} precision - The number of decimal places
     * @return {string} The formatted size
     */
    function formatBytes(bytes, precision = 2) {
        if (!isValidNumber(bytes)) {
            return '0 B';
        }

        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        bytes = Math.max(bytes, 0);
        let pow = Math.floor((bytes ? Math.log(bytes) : 0) / Math.log(1024));
        pow = Math.min(pow, units.length - 1);
        
        bytes /= Math.pow(1024, pow);
        
        return bytes.toFixed(precision) + ' ' + units[pow];
    }

    /**
     * Format a date for display
     * 
     * @param {string} dateString - Date string
     * @param {Object} options - Formatting options for toLocaleDateString
     * @return {string} Formatted date
     */
    function formatDate(dateString, options = { month: 'short', day: 'numeric', year: 'numeric' }) {
        if (!dateString) {
            return '';
        }

        try {
            const date = new Date(dateString);
            return date.toLocaleDateString(undefined, options);
        } catch (e) {
            return '';
        }
    }

    /**
     * Format all metrics in a data object
     * 
     * @param {Object} metrics - Object containing metrics data
     * @return {Object} Object with formatted metrics
     */
    function formatAllMetrics(metrics) {
        if (!metrics || typeof metrics !== 'object') {
            return {};
        }

        const formatted = {};

        // Format carbon footprint
        if ('carbon_footprint' in metrics) {
            formatted.carbon_footprint = formatCarbonEmissions(metrics.carbon_footprint);
        }

        // Format energy consumption
        if ('energy_consumption' in metrics) {
            formatted.energy_consumption = formatEnergyConsumption(metrics.energy_consumption);
        }

        // Format data transfer
        if ('data_transfer' in metrics) {
            formatted.data_transfer = formatDataTransfer(metrics.data_transfer);
        }

        // Format views
        if ('total_views' in metrics) {
            formatted.total_views = formatViews(metrics.total_views);
        }

        // Format requests
        if ('requests' in metrics) {
            formatted.requests = formatRequests(metrics.requests);
        }

        // Format performance score
        if ('performance_score' in metrics) {
            formatted.performance_score = formatPerformanceScore(metrics.performance_score);
        }

        // Format load time
        if ('load_time' in metrics) {
            formatted.load_time = formatLoadTime(metrics.load_time);
        }

        return formatted;
    }

    /**
     * Check if a value is a valid number
     * 
     * @param {*} value - The value to check
     * @return {boolean} True if the value is a valid number
     */
    function isValidNumber(value) {
        return typeof value === 'number' && !isNaN(value) && isFinite(value) && value >= 0;
    }

    // Public API
    return {
        formatCarbonEmissions,
        formatDataTransfer,
        formatEnergyConsumption,
        formatLoadTime,
        formatPerformanceScore,
        formatViews,
        formatRequests,
        formatBytes,
        formatDate,
        formatAllMetrics
    };
})();
