/**
 * Email Reporting Module
 * Handles the email reporting functionality
 */

// Add email reporting functionality to namespace
GreenMetricsAdmin.EmailReporting = (function ($) {
	'use strict';

	/**
	 * Initialize the email reporting module
	 *
	 * @function init
	 * @memberof GreenMetricsAdmin.EmailReporting
	 * @public
	 */
	function init() {
		// Only proceed if we're on an email reporting page
		if (!GreenMetricsAdmin.Config.isEmailReportingPage) {
			return;
		}

		// Initialize email reporting components
		initEmailReporting();
	}

	/**
	 * Initialize email reporting components
	 *
	 * @function initEmailReporting
	 * @memberof GreenMetricsAdmin.EmailReporting
	 * @private
	 */
	function initEmailReporting() {
		// Set up event listeners
		setupEventListeners();
	}

	/**
	 * Set up event listeners for email reporting
	 *
	 * @function setupEventListeners
	 * @memberof GreenMetricsAdmin.EmailReporting
	 * @private
	 */
	function setupEventListeners() {
		// Handle frequency change
		$('#email_reporting_frequency').on('change', function() {
			updateDayOptions();
		});

		// Handle preview updates
		$('#email_reporting_include_stats, #email_reporting_include_chart, #email_reporting_header, #email_reporting_footer, #email_reporting_css').on('change', function() {
			updateEmailPreview();
		});

		// Handle test email button
		$('#send_test_email').on('click', function() {
			sendTestEmail();
		});

		// Handle full preview button
		$('#try_full_preview').on('click', function() {
			var $button = $(this);
			var $result = $('#preview_result');

			// Disable button and show loading
			$button.prop('disabled', true).text('Loading...');
			$result.text('').hide();

			// Get the email content from the server
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'greenmetrics_get_email_preview',
					nonce: $('#greenmetrics_nonce').val(),
					include_stats: $('#email_reporting_include_stats').is(':checked') ? 1 : 0,
					include_chart: $('#email_reporting_include_chart').is(':checked') ? 1 : 0,
					header: $('#email_reporting_header').val(),
					footer: $('#email_reporting_footer').val(),
					custom_css: $('#email_reporting_css').val(),
					full_preview: 1 // Request full preview
				},
				success: function(response) {
					if (response && response.success) {
						// Update the iframe content
						var iframe = document.getElementById('email-preview-frame');
						if (iframe) {
							var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
							iframeDoc.open();
							iframeDoc.write(response.data.content);
							iframeDoc.close();

							// Update subject preview
							$('#preview-subject').text(response.data.subject);

							// Update recipients preview
							$('#preview-recipients').text(response.data.recipients);

							// Show success message
							$result.removeClass('error').addClass('success').text('Full preview loaded successfully!').show();
						}
					} else {
						console.log('Error in email preview response:', response);
						$result.removeClass('success').addClass('error').text('Error loading full preview.').show();
					}
				},
				error: function(xhr, status, error) {
					console.log('Error fetching email preview:', status, error);

					// Try to get more detailed error information
					var errorMessage = 'AJAX request failed. Please try again.';

					try {
						if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
							errorMessage = xhr.responseJSON.data.message;
						} else if (xhr.responseText) {
							// Check if the response is HTML (likely a PHP error)
							if (xhr.responseText.indexOf('<!DOCTYPE html>') !== -1 ||
								xhr.responseText.indexOf('<html') !== -1 ||
								xhr.responseText.indexOf('<body') !== -1 ||
								xhr.responseText.indexOf('<br') !== -1) {

								console.log('Received HTML response instead of JSON:', xhr.responseText.substring(0, 500));
								errorMessage = 'Server returned HTML instead of JSON. Check server logs for PHP errors.';
							} else {
								// Try to parse the response text
								try {
									var response = JSON.parse(xhr.responseText);
									if (response && response.data && response.data.message) {
										errorMessage = response.data.message;
									}
								} catch (parseError) {
									console.log('Error parsing JSON response:', parseError);
									// Show the first 100 characters of the response for debugging
									errorMessage = 'Invalid JSON response: ' + xhr.responseText.substring(0, 100) + '...';
								}
							}
						}
					} catch (e) {
						console.log('Error handling error response:', e);
					}

					$result.removeClass('success').addClass('error').text(errorMessage).show();
				},
				complete: function() {
					// Re-enable button
					$button.prop('disabled', false).text('Try Full Preview');

					// Hide result after 5 seconds
					setTimeout(function() {
						$result.fadeOut();
					}, 5000);
				}
			});
		});

		// Initialize day options
		updateDayOptions();

		// Initialize email preview
		updateEmailPreview();
	}

	/**
	 * Update day options based on frequency
	 *
	 * @function updateDayOptions
	 * @memberof GreenMetricsAdmin.EmailReporting
	 * @private
	 */
	function updateDayOptions() {
		var frequency = $('#email_reporting_frequency').val();
		var $dayRow = $('#email_reporting_day_row');

		if (frequency === 'daily') {
			$dayRow.hide();
		} else {
			$dayRow.show();

			// Update day options based on frequency
			var $dayField = $('#email_reporting_day');
			$dayField.empty();

			if (frequency === 'weekly') {
				// Days of week
				var days = [
					{ value: 0, label: GreenMetricsAdmin.Utils.i18n('sunday') },
					{ value: 1, label: GreenMetricsAdmin.Utils.i18n('monday') },
					{ value: 2, label: GreenMetricsAdmin.Utils.i18n('tuesday') },
					{ value: 3, label: GreenMetricsAdmin.Utils.i18n('wednesday') },
					{ value: 4, label: GreenMetricsAdmin.Utils.i18n('thursday') },
					{ value: 5, label: GreenMetricsAdmin.Utils.i18n('friday') },
					{ value: 6, label: GreenMetricsAdmin.Utils.i18n('saturday') }
				];

				$.each(days, function(i, day) {
					$dayField.append($('<option></option>').val(day.value).text(day.label));
				});

				// Get the saved value from the PHP template
				var savedValue = parseInt($('#email_reporting_day_saved_value').val() || 1, 10);
				// Use the saved value if it's valid for weekly (0-6), otherwise default to Monday (1)
				$dayField.val(savedValue >= 0 && savedValue <= 6 ? savedValue : 1);
			} else if (frequency === 'monthly') {
				// Days of month (1-28)
				for (var i = 1; i <= 28; i++) {
					$dayField.append($('<option></option>').val(i).text(i));
				}

				// Get the saved value from the PHP template
				var savedValue = parseInt($('#email_reporting_day_saved_value').val() || 1, 10);
				// Use the saved value if it's valid for monthly (1-28), otherwise default to 1st
				$dayField.val(savedValue >= 1 && savedValue <= 28 ? savedValue : 1);
			}
		}
	}

	/**
	 * Update email preview
	 *
	 * @function updateEmailPreview
	 * @memberof GreenMetricsAdmin.EmailReporting
	 * @private
	 */
	function updateEmailPreview() {
		// Get the email content from the server
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'greenmetrics_get_email_preview',
				nonce: $('#greenmetrics_nonce').val(),
				include_stats: $('#email_reporting_include_stats').is(':checked') ? 1 : 0,
				include_chart: $('#email_reporting_include_chart').is(':checked') ? 1 : 0,
				header: $('#email_reporting_header').val(),
				footer: $('#email_reporting_footer').val(),
				custom_css: $('#email_reporting_css').val(),
				full_preview: 0 // Start with simple preview first
			},
			success: function(response) {
				if (response && response.success) {
					// Update the iframe content
					var iframe = document.getElementById('email-preview-frame');
					if (iframe) {
						var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
						iframeDoc.open();
						iframeDoc.write(response.data.content);
						iframeDoc.close();

						// Update subject preview
						$('#preview-subject').text(response.data.subject);

						// Update recipients preview
						$('#preview-recipients').text(response.data.recipients);
					}
				} else {
					console.log('Error in email preview response:', response);
				}
			},
			error: function(xhr, status, error) {
				console.log('Error fetching email preview:', status, error);

				// Try to get more detailed error information
				var errorMessage = 'There was an error generating the email preview. Please check your settings and try again.';
				var technicalDetails = status + ' - ' + error;

				try {
					if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
						errorMessage = xhr.responseJSON.data.message;
					} else if (xhr.responseText) {
						// Check if the response is HTML (likely a PHP error)
						if (xhr.responseText.indexOf('<!DOCTYPE html>') !== -1 ||
							xhr.responseText.indexOf('<html') !== -1 ||
							xhr.responseText.indexOf('<body') !== -1 ||
							xhr.responseText.indexOf('<br') !== -1) {

							console.log('Received HTML response instead of JSON:', xhr.responseText.substring(0, 500));
							errorMessage = 'Server returned HTML instead of JSON. Check server logs for PHP errors.';
							technicalDetails = 'HTML response received';
						} else {
							// Try to parse the response text
							try {
								var response = JSON.parse(xhr.responseText);
								if (response && response.data && response.data.message) {
									errorMessage = response.data.message;
								}
							} catch (parseError) {
								console.log('Error parsing JSON response:', parseError);
								// Show the first 100 characters of the response for debugging
								errorMessage = 'Invalid JSON response';
								technicalDetails = xhr.responseText.substring(0, 100) + '...';
							}
						}
					}
				} catch (e) {
					console.log('Error handling error response:', e);
				}

				// Show a simple preview with error message
				var iframe = document.getElementById('email-preview-frame');
				if (iframe) {
					var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
					iframeDoc.open();
					iframeDoc.write('<html><head><style>body{font-family:sans-serif;padding:20px;color:#444;} .error{color:#cc0000;} pre{background:#f5f5f5;padding:10px;overflow:auto;max-height:200px;}</style></head><body><h2>Preview Error</h2><p>' + errorMessage + '</p><p class="error">Technical details: ' + technicalDetails + '</p></body></html>');
					iframeDoc.close();
				}
			}
		});
	}

	/**
	 * Send test email
	 *
	 * @function sendTestEmail
	 * @memberof GreenMetricsAdmin.EmailReporting
	 * @private
	 */
	function sendTestEmail() {
		var $button = $('#send_test_email');
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
				nonce: $('#greenmetrics_nonce').val()
			},
			success: function(response) {
				if (response && response.success) {
					$result.removeClass('error').addClass('success').text(response.data.message).show();
				} else {
					var errorMsg = (response && response.data && response.data.message) ?
						response.data.message : 'Failed to send test email. Please check your settings.';
					$result.removeClass('success').addClass('error').text(errorMsg).show();
				}
			},
			error: function(xhr, status, error) {
				console.log('Error sending test email:', status, error);

				// Try to get more detailed error information
				var errorMessage = 'AJAX request failed. Please try again.';

				try {
					if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
						errorMessage = xhr.responseJSON.data.message;
					} else if (xhr.responseText) {
						// Check if the response is HTML (likely a PHP error)
						if (xhr.responseText.indexOf('<!DOCTYPE html>') !== -1 ||
							xhr.responseText.indexOf('<html') !== -1 ||
							xhr.responseText.indexOf('<body') !== -1 ||
							xhr.responseText.indexOf('<br') !== -1) {

							console.log('Received HTML response instead of JSON:', xhr.responseText.substring(0, 500));
							errorMessage = 'Server returned HTML instead of JSON. Check server logs for PHP errors.';
						} else {
							// Try to parse the response text
							try {
								var response = JSON.parse(xhr.responseText);
								if (response && response.data && response.data.message) {
									errorMessage = response.data.message;
								}
							} catch (parseError) {
								console.log('Error parsing JSON response:', parseError);
								// Show the first 100 characters of the response for debugging
								errorMessage = 'Invalid JSON response: ' + xhr.responseText.substring(0, 100) + '...';
							}
						}
					}
				} catch (e) {
					console.log('Error handling error response:', e);
				}

				$result.removeClass('success').addClass('error').text(errorMessage).show();
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
	}

	// Public API
	return {
		init: init
	};
})(jQuery);
