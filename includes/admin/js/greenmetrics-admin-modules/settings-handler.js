/**
 * GreenMetrics Admin Settings Handler Module
 * Handles the settings form submissions
 *
 * @module GreenMetricsAdmin.SettingsHandler
 * @requires jQuery
 * @requires GreenMetricsAdmin.Config
 * @requires GreenMetricsErrorHandler
 */
// Ensure namespace exists
var GreenMetricsAdmin = GreenMetricsAdmin || {};

// Add settings handler functionality to namespace
GreenMetricsAdmin.SettingsHandler = (function ($) {
    'use strict';

    /**
     * Initialize the settings handler module
     *
     * @function init
     * @memberof GreenMetricsAdmin.SettingsHandler
     * @public
     */
    function init() {
        console.log('GreenMetrics: Settings handler initialized');

        // Check if we have a settings-updated parameter in the URL
        if (window.location.search.indexOf('settings-updated=true') > -1) {
            // Show a success message
            GreenMetricsErrorHandler.displayAdminNotice(
                'Settings saved successfully.',
                'success',
                true
            );

            // Update preview if available
            if (typeof GreenMetricsAdmin.Preview !== 'undefined' &&
                typeof GreenMetricsAdmin.Preview.updatePreview === 'function') {
                GreenMetricsAdmin.Preview.updatePreview();
            }

            // Update email preview if available
            if (typeof GreenMetricsAdmin.EmailReporting !== 'undefined' &&
                typeof GreenMetricsAdmin.EmailReporting.updatePreview === 'function') {
                GreenMetricsAdmin.EmailReporting.updatePreview();
            }
        }

        // Add submit event handlers to all settings forms
        $('form[action="options.php"]').on('submit', function() {
            // Store the form data in localStorage before submitting
            var formData = $(this).serialize();
            localStorage.setItem('greenmetrics_last_form_data', formData);

            // Ensure all form fields are properly included
            $(this).find('input[type="checkbox"]').each(function() {
                if (!$(this).is(':checked')) {
                    // Add a hidden field for unchecked checkboxes
                    var name = $(this).attr('name');
                    if (name && name.indexOf('greenmetrics_settings') > -1) {
                        // Only add if not already present
                        if ($(this).siblings('input[type="hidden"][name="' + name + '"]').length === 0) {
                            $(this).after('<input type="hidden" name="' + name + '" value="0">');
                        }
                    }
                }
            });

            // Special handling for email reporting day field
            var frequency = $('#email_reporting_frequency').val();
            var $dayField = $('#email_reporting_day');

            // If frequency is daily, ensure we have a hidden field for the day
            if (frequency === 'daily' && $dayField.is(':hidden')) {
                // Add a hidden field for the day if not already present
                if ($('#email_reporting_day_hidden').length === 0) {
                    $dayField.closest('tr').append('<input type="hidden" id="email_reporting_day_hidden" name="greenmetrics_settings[email_reporting_day]" value="1">');
                }
            }

            // Log form data for debugging
            console.log('Form data before submit:', $(this).serialize());

            // Let the form submit normally
            return true;
        });
    }

    // Public API
    return {
        init: init
    };
})(jQuery);
