/**
 * GreenMetrics Admin Core Module
 * Handles the core functionality and module initialization
 */
// Ensure namespace exists
var GreenMetricsAdmin = GreenMetricsAdmin || {};

// Add core functionality to the namespace
(function ($) {
	'use strict';

	// Private shared variables
	var $submitBtn, $checkboxes, $selects, $texts, $iconOptions, mediaFrame;

	// Default colors for reference
	var defaultColors = {
		'badge_background_color': '#4CAF50',
		'badge_text_color': '#ffffff',
		'badge_icon_color': '#ffffff',
		'popover_bg_color': '#ffffff',
		'popover_text_color': '#333333',
		'popover_metrics_color': '#4CAF50',
		'popover_metrics_bg_color': 'rgba(0, 0, 0, 0.05)',
		'popover_metrics_list_bg_color': '#f8f9fa',
		'popover_metrics_list_hover_bg_color': '#f3f4f6'
	};

	// Utility functions
	var utils = {
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

	// Initialize the entire admin interface
	function init() {
		// Cache DOM elements
		$submitBtn   = $( '#submit' );
		$checkboxes  = $( 'input[type="checkbox"]' );
		$selects     = $( 'select' );
		$texts       = $( 'input[type="text"]' );
		$iconOptions = $( '.icon-option' );

		// Move admin notices
		utils.moveAdminNotices();
		// Run twice more with delays to catch dynamically added notices
		setTimeout( utils.moveAdminNotices, 100 );
		setTimeout( utils.moveAdminNotices, 1000 );

		// Setup global events
		setupGlobalEvents();
	}

	// Setup events that apply across modules
	function setupGlobalEvents() {
		// Change events for form elements to mark form as dirty
		$checkboxes.on( 'change', GreenMetricsAdmin.Utils.markDirty );
		$selects.on( 'change', GreenMetricsAdmin.Utils.markDirty );

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
		utils: utils,
		defaultColors: defaultColors
	};
})( jQuery ); 