/**
 * GreenMetrics Admin Core Module
 * Handles the core functionality and module initialization
 *
 * @module GreenMetricsAdmin.Core
 * @requires jQuery
 * @requires GreenMetricsAdmin.Utils
 * @requires GreenMetricsAdmin.Config
 */
// Ensure namespace exists
var GreenMetricsAdmin = GreenMetricsAdmin || {};

// Add core functionality to the namespace
(function ($) {
	'use strict';

	// Private shared variables
	var $checkboxes, $selects;

	// Get the admin data from WordPress
	var ajaxurl = window.ajaxurl || '';

	// Utility functions
	var utils = {
		/**
		 * Move WordPress admin notices to the correct position
		 *
		 * @function moveAdminNotices
		 * @memberof GreenMetricsAdmin.Core
		 */
		moveAdminNotices: function () {
			// Find all WordPress admin notices
			const $notices = $( '.notice, .error:not(.notice), .updated:not(.notice), .update-nag' );
			if ($notices.length) {
				// Find our custom admin header
				const $adminHeader = $( '.greenmetrics-admin-header' );
				if ($adminHeader.length) {
					// Move all notices before our admin header
					$notices.detach().insertBefore( $adminHeader );
				}
			}
		}
	};

	/**
	 * Initialize the entire admin interface
	 *
	 * @function init
	 * @memberof GreenMetricsAdmin.Core
	 */
	function init() {
		// Cache DOM elements
		$checkboxes  = $( 'input[type="checkbox"]' );
		$selects     = $( 'select' );

		// Move admin notices
		utils.moveAdminNotices();
		// Run twice more with delays to catch dynamically added notices
		setTimeout( utils.moveAdminNotices, 100 );
		setTimeout( utils.moveAdminNotices, 1000 );

		// Setup global events
		setupGlobalEvents();
	}

	/**
	 * Setup events that apply across modules
	 *
	 * @function setupGlobalEvents
	 * @memberof GreenMetricsAdmin.Core
	 * @private
	 */
	function setupGlobalEvents() {
		// Change events for form elements to mark form as dirty
		$checkboxes.on( 'change', GreenMetricsAdmin.Utils.markDirty );
		$selects.on( 'change', GreenMetricsAdmin.Utils.markDirty );

		// Handle email reporting frequency change to update day options
		$('#email_reporting_frequency').on('change', function() {
			updateDayField($(this).val());
		});

		// Initialize day field based on current frequency
		function updateDayField(frequency) {
			var $dayField = $('#email_reporting_day').closest('tr');
			var $select = $('#email_reporting_day');
			var currentValue = $select.val();

			console.log('Updating day field - Frequency:', frequency, 'Current value:', currentValue);

			// Show/hide day field based on frequency
			if (frequency === 'daily') {
				$dayField.hide();
				// Add a hidden input to ensure the value is submitted
				if ($('#email_reporting_day_hidden').length === 0) {
					$dayField.append('<input type="hidden" id="email_reporting_day_hidden" name="greenmetrics_settings[email_reporting_day]" value="1">');
				}
			} else {
				$dayField.show();
				// Remove hidden input if it exists
				$('#email_reporting_day_hidden').remove();

				// Update day options based on frequency
				if (frequency === 'weekly') {
					// Store current value before emptying
					var oldValue = parseInt(currentValue, 10);
					$select.empty();

					// Days of week
					var days = [
						{ value: 0, label: 'Sunday' },
						{ value: 1, label: 'Monday' },
						{ value: 2, label: 'Tuesday' },
						{ value: 3, label: 'Wednesday' },
						{ value: 4, label: 'Thursday' },
						{ value: 5, label: 'Friday' },
						{ value: 6, label: 'Saturday' }
					];

					// Add options
					$.each(days, function(_, day) {
						$select.append($('<option></option>').val(day.value).text(day.label));
					});

					// Set value
					if (oldValue >= 0 && oldValue <= 6) {
						$select.val(oldValue);
					} else {
						$select.val(1); // Default to Monday
					}
				} else if (frequency === 'monthly') {
					// Store current value before emptying
					var oldValue = parseInt(currentValue, 10);
					$select.empty();

					// Days of month (1-28)
					for (var i = 1; i <= 28; i++) {
						$select.append($('<option></option>').val(i).text(i));
					}

					// Set value
					if (oldValue >= 1 && oldValue <= 28) {
						$select.val(oldValue);
					} else {
						$select.val(1); // Default to 1st
					}
				}
			}
		}

		// Initialize day field on page load
		updateDayField($('#email_reporting_frequency').val());

		// Handle test email button
		$('#send_test_email').on('click', function() {
			var $button = $(this);
			var $result = $('#test_email_result');

			// Disable button and show loading
			$button.prop('disabled', true).text('Sending...');
			$result.text('').hide();

			// Send AJAX request
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'greenmetrics_send_test_email',
					nonce: jQuery('input[name="greenmetrics_nonce"]').val() || ''
				},
				success: function(response) {
					if (response.success) {
						$result.removeClass('error').addClass('success').text(response.data.message).show();
					} else {
						$result.removeClass('success').addClass('error').text(response.data.message).show();
					}
				},
				error: function() {
					$result.removeClass('success').addClass('error').text('AJAX request failed. Please try again.').show();
				},
				complete: function() {
					// Re-enable button
					$button.prop('disabled', false).text('Send Test Email');

					// Hide result after 5 seconds
					setTimeout(function() {
						$result.fadeOut();
					}, 5000);
				}
			});
		});

		// Allow multiple notices to be displayed

		// Auto-dismiss notices after 5 seconds
		setTimeout(
			function () {
				// Auto-dismiss all success notices
				$( '.notice-success.is-dismissible' ).fadeOut(
					500,
					function () {
						$( this ).remove();
					}
				);

				// For URL parameter specific notices
				if (window.location.search.indexOf( 'settings-updated=true' ) > -1 ||
				window.location.search.indexOf( 'settings-updated=1' ) > -1) {
					$( '.notice' ).fadeOut(
						500,
						function () {
							$( this ).remove();
						}
					);
				}
			},
			5000
		);

		// Settings triggers if they exist
		if ($( '.greenmetrics-settings-trigger' ).length) {
			$( '.greenmetrics-settings-trigger' ).on(
				'click',
				function (e) {
					e.preventDefault();
					$( $( this ).data( 'target' ) ).slideToggle();
				}
			);
		}
	}

	// Add to public API
	GreenMetricsAdmin.core = {
		init: init,
		utils: utils
	};
})( jQuery );