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

	// Private variables

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

		// Email reporting functionality is now handled in email-reporting.js

		// Test email functionality is now handled in email-reporting.js

		// Allow multiple notices to be displayed

		// Auto-dismiss notices after 5 seconds
		setTimeout(
			function () {
				// Auto-dismiss all success notices
				$( '.notice-success.is-dismissible' ).fadeOut(
					500,
					function () {
						$( this ).hide();
					}
				);
			},
			5000
		);

		// Simple approach to remove notice parameters from URL
		if (window.location.search.indexOf('stats-refreshed=true') > -1 ||
			window.location.search.indexOf('data-management-updated=true') > -1 ||
			window.location.search.indexOf('aggregation=true') > -1 ||
			window.location.search.indexOf('pruning=true') > -1) {

			// Get current URL without parameters
			var baseUrl = window.location.pathname;

			// Get current search params
			var params = new URLSearchParams(window.location.search);

			// Remove notice-related parameters
			params.delete('stats-refreshed');
			params.delete('data-management-updated');
			params.delete('aggregation');
			params.delete('pruning');

			// Build new URL
			var newUrl = baseUrl;

			// Add remaining parameters if any
			var remainingParams = params.toString();
			if (remainingParams) {
				newUrl += '?' + remainingParams;
			}

			// Update URL without reloading
			window.history.replaceState(null, '', newUrl);
		}

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