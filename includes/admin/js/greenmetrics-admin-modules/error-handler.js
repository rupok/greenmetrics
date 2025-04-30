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
        } else if (xhr.responseText) {
            errorData.responseText = xhr.responseText;
            
            // Try to parse the response text as JSON
            try {
                const parsedResponse = JSON.parse(xhr.responseText);
                if (parsedResponse.message) {
                    errorMessage = parsedResponse.message;
                }
            } catch (e) {
                // Not JSON, use as is
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
     * @return {Object} Standardized error object
     */
    function handleRestError(xhr, status, error, container) {
        const errorObj = processAjaxError(xhr, status, error);
        
        // Display the error if a container is provided
        if (container) {
            displayError(errorObj.message, container);
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
