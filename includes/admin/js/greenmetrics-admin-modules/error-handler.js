/**
 * GreenMetrics Error Handler Module
 *
 * Provides standardized error handling functions for the admin interface.
 */
var GreenMetricsErrorHandler = (function($) {
    'use strict';

    /**
     * Process an AJAX error response and return a standardized error object
     *
     * @param {Object} xhr The XMLHttpRequest object
     * @param {string} status The status text
     * @param {string} error The error text
     * @return {Object} Standardized error object
     */
    function processAjaxError(xhr, status, error) {
        let errorMessage = 'An error occurred. Please try again.';
        let errorData = {
            status: status,
            error: error
        };

        // Try to get more detailed error message from response
        if (xhr.responseJSON) {
            if (xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            errorData.response = xhr.responseJSON;

            // Add code to error data if available
            if (xhr.responseJSON.code) {
                errorData.code = xhr.responseJSON.code;
            }
        } else if (xhr.responseText) {
            errorData.responseText = xhr.responseText;

            // Try to parse the response text as JSON
            try {
                const parsedResponse = JSON.parse(xhr.responseText);
                if (parsedResponse.message) {
                    errorMessage = parsedResponse.message;
                }
                // Add code to error data if available
                if (parsedResponse.code) {
                    errorData.code = parsedResponse.code;
                }
            } catch (e) {
                // Not JSON, use as is
            }
        }

        // Add HTTP status context to error message for certain status codes
        if (xhr.status) {
            errorData.httpStatus = xhr.status;

            // Add context based on HTTP status code
            switch (xhr.status) {
                case 401:
                    errorMessage = 'Authentication required: ' + errorMessage;
                    errorData.authError = true;
                    break;
                case 403:
                    errorMessage = 'Permission denied: ' + errorMessage;
                    errorData.permissionError = true;
                    break;
                case 404:
                    errorMessage = 'Resource not found: ' + errorMessage;
                    errorData.notFoundError = true;
                    break;
                case 500:
                    errorMessage = 'Server error: ' + errorMessage;
                    errorData.serverError = true;
                    break;
            }
        }

        // Check for nonce/security errors
        if (errorMessage.includes('Security verification failed') ||
            errorMessage.includes('Nonce') ||
            errorMessage.includes('rest_cookie_invalid_nonce')) {
            errorMessage += ' Please refresh the page and try again.';
            errorData.isSecurityError = true;
        }

        // Log error in debug mode
        if (typeof greenmetricsAdmin !== 'undefined' && greenmetricsAdmin.debug) {
            console.error('GreenMetrics Error:', {
                message: errorMessage,
                data: errorData
            });
        }

        return {
            message: errorMessage,
            data: errorData
        };
    }

    /**
     * Display an error message in a container
     *
     * @param {string} message The error message
     * @param {string} container The container selector to display the error in
     */
    function displayError(message, container) {
        const $container = $(container);
        if ($container.length) {
            // Remove any existing error messages
            $container.find('.greenmetrics-error-message').remove();

            // Add the new error message
            $container.append(
                '<div class="greenmetrics-error-message">' +
                '<p class="error">' + message + '</p>' +
                '</div>'
            );
        }
    }

    /**
     * Display an error message as a WordPress admin notice
     *
     * @param {string} message The error message
     * @param {string} type The notice type (error, warning, success, info)
     * @param {boolean} dismissible Whether the notice should be dismissible
     */
    function displayAdminNotice(message, type = 'error', dismissible = true) {
        const $notices = $('.wrap .notice, .wrap .error, .wrap .updated, .wrap .update-nag');

        // Create the notice HTML
        let noticeClass = 'notice notice-' + type;
        if (dismissible) {
            noticeClass += ' is-dismissible';
        }

        const $notice = $(
            '<div class="' + noticeClass + '">' +
            '<p>' + message + '</p>' +
            '</div>'
        );

        // If there are existing notices, insert after the last one
        if ($notices.length) {
            $notices.last().after($notice);
        } else {
            // Otherwise, insert at the beginning of the wrap
            $('.wrap').prepend($notice);
        }

        // Initialize the dismissible functionality if needed
        if (dismissible && typeof wp !== 'undefined' && wp.updates && wp.updates.dismissNotice) {
            wp.updates.dismissNotice($notice);
        }
    }

    /**
     * Handle REST API errors consistently
     *
     * @param {Object} xhr The XMLHttpRequest object
     * @param {string} status The status text
     * @param {string} error The error text
     * @param {string} container The container selector to display the error in (optional)
     * @param {boolean} showAdminNotice Whether to also show an admin notice (default: false)
     * @return {Object} Standardized error object
     */
    function handleRestError(xhr, status, error, container, showAdminNotice = false) {
        const errorObj = processAjaxError(xhr, status, error);

        // Create a more user-friendly error message with troubleshooting tips
        let displayMessage = errorObj.message;

        // Add troubleshooting tips based on error type
        if (errorObj.data) {
            if (errorObj.data.isSecurityError) {
                displayMessage += ' This may be due to an expired session. Try refreshing the page.';
            } else if (errorObj.data.serverError) {
                displayMessage += ' Please try again later or contact the site administrator.';
            } else if (errorObj.data.permissionError) {
                displayMessage += ' You may not have sufficient permissions for this action.';
            } else if (errorObj.data.notFoundError) {
                displayMessage += ' The requested resource could not be found.';
            }

            // Add specific guidance based on error code if available
            if (errorObj.data.code) {
                switch (errorObj.data.code) {
                    case 'tracking_disabled':
                        displayMessage += ' Tracking is currently disabled in the plugin settings.';
                        break;
                    case 'invalid_stats':
                    case 'invalid_metrics':
                        displayMessage += ' The data may be corrupted or unavailable.';
                        break;
                    case 'database_error':
                        displayMessage += ' There may be an issue with the database connection.';
                        break;
                }
            }
        }

        // Display the error if a container is provided
        if (container) {
            displayError(displayMessage, container);
        }

        // Optionally show as admin notice as well for more visibility
        if (showAdminNotice) {
            displayAdminNotice(displayMessage, 'error', true);
        }

        return errorObj;
    }

    // Public API
    return {
        processAjaxError: processAjaxError,
        displayError: displayError,
        displayAdminNotice: displayAdminNotice,
        handleRestError: handleRestError
    };
})(jQuery);
