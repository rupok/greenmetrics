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
		// Check if we're on an email reporting page
		if (typeof GreenMetricsAdmin.Config === 'undefined' ||
			!GreenMetricsAdmin.Config ||
			GreenMetricsAdmin.Config.isEmailReportingPage ||
			window.location.href.indexOf('page=greenmetrics_email_reporting') > -1) {

			// Initialize email reporting components
			initEmailReporting();
		}
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

		// Handle view report button
		$(document).on('click', '.view-report', function(e) {
			e.preventDefault();
			var reportId = $(this).data('report-id');
			viewReport(reportId);
		});

		// Handle modal close button
		$(document).on('click', '.report-modal-close, .report-modal-close-btn', function() {
			closeReportModal();
		});

		// Handle clicking outside the modal
		$(document).on('click', '#report-view-modal', function(e) {
			if (e.target === this) {
				closeReportModal();
			}
		});

		// Handle escape key to close modal
		$(document).keyup(function(e) {
			if (e.key === "Escape" && $('#report-view-modal').is(':visible')) {
				closeReportModal();
			}
		});

		// Initialize day options immediately
		updateDayOptions();

		// Initialize email preview with loading indicator
		showPreviewLoading();
		updateEmailPreview();
	}

	/**
	 * Show loading indicator in the preview iframe
	 *
	 * @function showPreviewLoading
	 * @memberof GreenMetricsAdmin.EmailReporting
	 * @private
	 */
	function showPreviewLoading() {
		var iframe = document.getElementById('email-preview-frame');
		if (iframe) {
			var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
			iframeDoc.open();
			iframeDoc.write('<html><head><style>body{font-family:sans-serif;padding:20px;color:#444;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;flex-direction:column;} .loading{text-align:center;} .spinner{border:4px solid rgba(0,0,0,.1);width:36px;height:36px;border-radius:50%;border-left-color:#4CAF50;animation:spin 1s linear infinite;margin:0 auto 15px;} @keyframes spin{0%{transform:rotate(0deg)} 100%{transform:rotate(360deg)}}</style></head><body><div class="loading"><div class="spinner"></div><p>Loading email preview...</p></div></body></html>');
			iframeDoc.close();
		}
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

		// Find the day field row
		var $dayRow = $('#email_reporting_day').closest('tr');

		// If we can't find the row, try to find it by ID
		if (!$dayRow.length) {
			$dayRow = $('#email_reporting_day_row');
		}

		// If we still can't find the row, exit
		if (!$dayRow.length) {
			return;
		}

		var $dayCell = $dayRow.find('td');

		// Update day options based on frequency
		if (frequency === 'daily') {
			// For daily, show the row but with a hidden input and simple description
			$dayRow.show();
			$dayCell.html('<input type="hidden" id="email_reporting_day" name="greenmetrics_settings[email_reporting_day]" value="1">' +
				'<p class="description">Reports will be sent daily at midnight.</p>');
		} else {
			// For weekly or monthly, show the row and update content
			$dayRow.show();

			var selectHtml = '<select id="email_reporting_day" name="greenmetrics_settings[email_reporting_day]">';
			var description = '';

			// Get the saved value from the hidden input or the current select value
			var currentValue = $('#email_reporting_day_saved_value').val() || $('#email_reporting_day').val() || 1;

			if (frequency === 'weekly') {
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

				// Add options to select
				for (var i = 0; i < days.length; i++) {
					var selected = (parseInt(currentValue) === days[i].value) ? ' selected' : '';
					selectHtml += '<option value="' + days[i].value + '"' + selected + '>' + days[i].label + '</option>';
				}

				description = '<p class="description">Day of the week to send reports.</p>';
			} else if (frequency === 'monthly') {
				// Days of month (1-28)
				for (var i = 1; i <= 28; i++) {
					var selected = (parseInt(currentValue) === i) ? ' selected' : '';
					selectHtml += '<option value="' + i + '"' + selected + '>' + i + '</option>';
				}

				description = '<p class="description">Day of the month to send reports (1-28).</p>';
			}

			selectHtml += '</select>';

			// Update the cell content
			$dayCell.html(selectHtml + description);
		}
	}

	/**
	 * Update email preview
	 *
	 * @function updateEmailPreview
	 * @memberof GreenMetricsAdmin.EmailReporting
	 * @public
	 */
	function updateEmailPreview() {
		// Show loading indicator
		showPreviewLoading();

		// Get color values
		var colors = {
			primary: $('#email_color_primary').val() || '#4CAF50',
			secondary: $('#email_color_secondary').val() || '#f9f9f9',
			accent: $('#email_color_accent').val() || '#333333',
			text: $('#email_color_text').val() || '#333333',
			background: $('#email_color_background').val() || '#ffffff'
		};

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
				colors: colors,
				template_style: $('#email_template_selector').val() || 'default'
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

						// Add event listener for iframe load to adjust height (with max height)
						iframe.onload = function() {
							// Calculate the content height
							var contentHeight = iframe.contentWindow.document.body.scrollHeight + 20;
							// Set a maximum height of 600px
							var maxHeight = 600;
							// Set iframe height to match content, but not more than max height
							iframe.style.height = Math.min(contentHeight, maxHeight) + 'px';
							// Add scrolling if needed
							iframe.style.overflowY = contentHeight > maxHeight ? 'scroll' : 'hidden';
						};

						// Also try to adjust height after a short delay
						setTimeout(function() {
							if (iframe.contentWindow && iframe.contentWindow.document.body) {
								var contentHeight = iframe.contentWindow.document.body.scrollHeight + 20;
								var maxHeight = 600;
								iframe.style.height = Math.min(contentHeight, maxHeight) + 'px';
								iframe.style.overflowY = contentHeight > maxHeight ? 'scroll' : 'hidden';
							}
						}, 500);

						// Update subject preview
						$('#preview-subject').text(response.data.subject);

						// Update recipients preview
						$('#preview-recipients').text(response.data.recipients);
					}
				} else {
					showPreviewError('Error loading preview', 'The server returned an error response.');
				}
			},
			error: function(xhr) {
				// Try to get more detailed error information
				var errorMessage = 'There was an error generating the email preview. Please check your settings and try again.';
				var technicalDetails = 'AJAX Error';

				try {
					if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
						errorMessage = xhr.responseJSON.data.message;
					} else if (xhr.responseText) {
						// Check if the response is HTML (likely a PHP error)
						if (xhr.responseText.indexOf('<!DOCTYPE html>') !== -1 ||
							xhr.responseText.indexOf('<html') !== -1 ||
							xhr.responseText.indexOf('<body') !== -1 ||
							xhr.responseText.indexOf('<br') !== -1) {

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
								errorMessage = 'Invalid JSON response';
								technicalDetails = xhr.responseText.substring(0, 100) + '...';
							}
						}
					}
				} catch (e) {
					// Silent catch - just use default error message
				}

				// Show error in preview
				showPreviewError(errorMessage, technicalDetails);
			}
		});
	}

	/**
	 * Show error message in the preview iframe
	 *
	 * @function showPreviewError
	 * @memberof GreenMetricsAdmin.EmailReporting
	 * @private
	 * @param {string} message - The error message
	 * @param {string} details - Technical details about the error
	 */
	function showPreviewError(message, details) {
		var iframe = document.getElementById('email-preview-frame');
		if (iframe) {
			var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
			iframeDoc.open();
			iframeDoc.write('<html><head><style>body{font-family:sans-serif;padding:20px;color:#444;} .error-container{max-width:600px;margin:40px auto;background:#fff;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);padding:25px;} .error-icon{color:#cc0000;font-size:48px;text-align:center;margin-bottom:20px;} .error-title{color:#cc0000;margin-top:0;} .error-message{margin-bottom:20px;line-height:1.5;} .technical-details{background:#f5f5f5;padding:15px;border-radius:4px;font-family:monospace;font-size:12px;overflow:auto;max-height:150px;}</style></head><body><div class="error-container"><div class="error-icon">⚠️</div><h2 class="error-title">Preview Error</h2><div class="error-message">' + message + '</div><div class="technical-details">' + details + '</div></div></body></html>');
			iframeDoc.close();
		}
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
			error: function(xhr) {
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

							errorMessage = 'Server returned HTML instead of JSON. Check server logs for PHP errors.';
						} else {
							// Try to parse the response text
							try {
								var response = JSON.parse(xhr.responseText);
								if (response && response.data && response.data.message) {
									errorMessage = response.data.message;
								}
							} catch (parseError) {
								errorMessage = 'Invalid JSON response';
							}
						}
					}
				} catch (e) {
					// Silent catch - just use default error message
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

	/**
	 * View a report
	 *
	 * @function viewReport
	 * @memberof GreenMetricsAdmin.EmailReporting
	 * @private
	 * @param {number} reportId - The ID of the report to view
	 */
	function viewReport(reportId) {
		// Show the modal
		$('#report-view-modal').show();

		// Show loading indicator
		$('#report-modal-body').html(
			'<div class="report-loading">' +
			'<span class="spinner is-active"></span>' +
			'<p>Loading report...</p>' +
			'</div>'
		);

		// Get the report from the server
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'greenmetrics_get_report',
				nonce: $('#greenmetrics_nonce').val(),
				report_id: reportId
			},
			success: function(response) {
				if (response && response.success) {
					// Format the report data
					var report = response.data;
					var reportHtml = formatReportForModal(report);

					// Update the modal content
					$('#report-modal-body').html(reportHtml);

					// Load the email content into the iframe
					setTimeout(function() {
						var iframe = document.getElementById('report-content-frame');
						if (iframe) {
							var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
							iframeDoc.open();
							iframeDoc.write(report.content);
							iframeDoc.close();

							// Add event listener for iframe load to adjust height (with max height)
							iframe.onload = function() {
								// Calculate the content height
								var contentHeight = iframe.contentWindow.document.body.scrollHeight + 20;
								// Set a maximum height of 600px
								var maxHeight = 600;
								// Set iframe height to match content, but not more than max height
								iframe.style.height = Math.min(contentHeight, maxHeight) + 'px';
							};

							// Also try to adjust height after a short delay
							setTimeout(function() {
								if (iframe.contentWindow && iframe.contentWindow.document.body) {
									var contentHeight = iframe.contentWindow.document.body.scrollHeight + 20;
									var maxHeight = 600;
									iframe.style.height = Math.min(contentHeight, maxHeight) + 'px';
								}
							}, 500);
						}
					}, 100);
				} else {
					var errorMsg = (response && response.data && response.data.message) ?
						response.data.message : 'Failed to load report. Please try again.';

					$('#report-modal-body').html(
						'<div class="error-message">' +
						'<p>' + errorMsg + '</p>' +
						'</div>'
					);
				}
			},
			error: function() {
				$('#report-modal-body').html(
					'<div class="error-message">' +
					'<p>AJAX request failed. Please try again.</p>' +
					'</div>'
				);
			}
		});
	}

	/**
	 * Format a report for display in the modal
	 *
	 * @function formatReportForModal
	 * @memberof GreenMetricsAdmin.EmailReporting
	 * @private
	 * @param {Object} report - The report data
	 * @return {string} The formatted HTML
	 */
	function formatReportForModal(report) {
		// Format the report type
		var reportType = '';
		switch (report.report_type) {
			case 'daily':
				reportType = 'Daily';
				break;
			case 'weekly':
				reportType = 'Weekly';
				break;
			case 'monthly':
				reportType = 'Monthly';
				break;
			default:
				reportType = report.report_type;
		}

		// Add test badge if needed
		if (report.is_test == 1) {
			reportType += ' <span class="test-badge">Test</span>';
		}

		// Format the status
		var statusClass = '';
		switch (report.status) {
			case 'sent':
				statusClass = 'status-success';
				break;
			case 'failed':
				statusClass = 'status-error';
				break;
			default:
				statusClass = '';
		}

		// Build the HTML
		var html = '<div class="report-info">';
		html += '<h3>' + report.subject + '</h3>';
		html += '<p><strong>Date:</strong> ' + (report.sent_at_formatted || report.sent_at) + '</p>';
		html += '<p><strong>Type:</strong> ' + reportType + '</p>';
		html += '<p><strong>Recipients:</strong> ' + report.recipients + '</p>';
		html += '<p><strong>Status:</strong> <span class="status-badge ' + statusClass + '">' + report.status.charAt(0).toUpperCase() + report.status.slice(1) + '</span></p>';
		html += '</div>';

		// Add the email content
		html += '<div class="report-content">';
		html += '<h3>Email Content</h3>';
		html += '<iframe id="report-content-frame" style="width:100%;height:500px;border:1px solid #ddd;border-radius:4px;"></iframe>';
		html += '</div>';

		// Add the actions
		html += '<div class="report-actions">';
		html += '<button type="button" class="button report-modal-close-btn">Close</button>';
		html += '</div>';

		// Return the HTML
		return html;
	}

	/**
	 * Close the report modal
	 *
	 * @function closeReportModal
	 * @memberof GreenMetricsAdmin.EmailReporting
	 * @private
	 */
	function closeReportModal() {
		$('#report-view-modal').hide();
	}

	// Public API
	return {
		init: init,
		updateEmailPreview: updateEmailPreview,
		viewReport: viewReport
	};
})(jQuery);
