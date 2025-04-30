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
                    // Use the standardized error handler
                    GreenMetricsErrorHandler.handleRestError(xhr, status, error, '#greenmetrics-stats');
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
                    // Use the standardized error handler
                    GreenMetricsErrorHandler.handleRestError(xhr, status, error, '#greenmetrics-metrics');
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
                    // Use the standardized error handler
                    GreenMetricsErrorHandler.handleRestError(xhr, status, error, '.greenmetrics-admin-notices');
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
