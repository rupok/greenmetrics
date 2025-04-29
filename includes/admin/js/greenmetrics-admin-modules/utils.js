/**
 * GreenMetrics Admin Utils Module
 * Contains reusable utility functions
 */
var GreenMetricsAdmin = GreenMetricsAdmin || {};

GreenMetricsAdmin.Utils = (function ($) {
	'use strict';

	/**
	 * Debounce function to limit how often a function can be called
	 *
	 * @param {Function} fn - The function to debounce
	 * @param {Number} delay - The delay in milliseconds
	 * @return {Function} The debounced function
	 */
	function debounce(fn, delay) {
		var timer;
		return function () {
			var context = this, args = arguments;
			clearTimeout( timer );
			timer = setTimeout(
				function () {
					fn.apply( context, args );
				},
				delay
			);
		};
	}

	/**
	 * Format a date with leading zeros
	 *
	 * @param {Number} num - The number to pad
	 * @return {String} The padded number
	 */
	function pad(num) {
		return num < 10 ? '0' + num : num;
	}

	/**
	 * Get formatted date string for a date N days ago
	 *
	 * @param {Number} daysAgo - Number of days ago
	 * @return {String} The formatted date string (YYYY-MM-DD)
	 */
	function getDateString(daysAgo) {
		const date = new Date();
		date.setDate( date.getDate() - daysAgo );
		return date.getFullYear() + '-' + pad( date.getMonth() + 1 ) + '-' + pad( date.getDate() );
	}

	/**
	 * Mark the form as dirty/changed
	 * This enables the submit button
	 */
	function markDirty() {
		$( '#submit' ).prop( 'disabled', false ).removeClass( 'button-disabled' );
	}

	// Public API
	return {
		debounce: debounce,
		pad: pad,
		getDateString: getDateString,
		markDirty: markDirty
	};
})( jQuery ); 