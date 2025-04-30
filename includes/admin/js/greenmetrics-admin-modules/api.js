/**
 * GreenMetrics Admin API Module
 *
 * This module provides standardized methods for interacting with the
 * GreenMetrics REST API endpoints.
 */
var GreenMetricsAdmin = GreenMetricsAdmin || {};

GreenMetricsAdmin.API = (function($) {
    'use strict';

    /**
     * Get statistics data from the API
     *
     * @param {Object} params - Query parameters
     * @param {Function} successCallback - Success callback function
     * @param {Function} errorCallback - Error callback function
     */
    function getStats(params, successCallback, errorCallback) {
        $.ajax({
            url: GreenMetricsAdmin.Config.apiEndpoints.stats,
            method: 'GET',
            data: params,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', greenmetricsAdmin.rest_nonce);
            },
            success: function(response) {
                if (typeof successCallback === 'function') {
                    successCallback(response);
                }
            },
            error: function(xhr, status, error) {
                if (typeof errorCallback === 'function') {
                    errorCallback(xhr, status, error);
                } else {
                    // Use the enhanced error handler with admin notice for critical errors
                    const showAdminNotice = xhr.status >= 500; // Show admin notice for server errors
                    GreenMetricsErrorHandler.handleRestError(xhr, status, error, '#greenmetrics-stats', showAdminNotice);
                }
            }
        });
    }

    /**
     * Get metrics data from the API
     *
     * @param {Object} params - Query parameters
     * @param {Function} successCallback - Success callback function
     * @param {Function} errorCallback - Error callback function
     */
    function getMetrics(params, successCallback, errorCallback) {
        $.ajax({
            url: GreenMetricsAdmin.Config.apiEndpoints.metrics,
            method: 'GET',
            data: params,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', greenmetricsAdmin.rest_nonce);
            },
            success: function(response) {
                if (typeof successCallback === 'function') {
                    successCallback(response);
                }
            },
            error: function(xhr, status, error) {
                if (typeof errorCallback === 'function') {
                    errorCallback(xhr, status, error);
                } else {
                    // Use the enhanced error handler with admin notice for critical errors
                    const showAdminNotice = xhr.status >= 500; // Show admin notice for server errors
                    GreenMetricsErrorHandler.handleRestError(xhr, status, error, '#greenmetrics-metrics', showAdminNotice);
                }
            }
        });
    }

    /**
     * Update settings via the API
     *
     * @param {Object} settings - Settings to update
     * @param {Function} successCallback - Success callback function
     * @param {Function} errorCallback - Error callback function
     */
    function updateSettings(settings, successCallback, errorCallback) {
        $.ajax({
            url: GreenMetricsAdmin.Config.apiEndpoints.settings,
            method: 'POST',
            data: JSON.stringify(settings),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', greenmetricsAdmin.rest_nonce);
            },
            success: function(response) {
                if (typeof successCallback === 'function') {
                    successCallback(response);
                }
            },
            error: function(xhr, status, error) {
                if (typeof errorCallback === 'function') {
                    errorCallback(xhr, status, error);
                } else {
                    // Use the enhanced error handler with admin notice for critical errors
                    const showAdminNotice = true; // Always show admin notice for settings errors
                    GreenMetricsErrorHandler.handleRestError(xhr, status, error, '.greenmetrics-admin-notices', showAdminNotice);
                }
            }
        });
    }

    // Public API
    return {
        getStats: getStats,
        getMetrics: getMetrics,
        updateSettings: updateSettings
    };
})(jQuery);
