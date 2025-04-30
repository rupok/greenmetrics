/**
 * GreenMetrics Admin Utils Module
 * Contains reusable utility functions for the admin interface
 *
 * @module GreenMetricsAdmin.Utils
 * @requires jQuery
 */
var GreenMetricsAdmin = GreenMetricsAdmin || {};

GreenMetricsAdmin.Utils = (function ($) {
	'use strict';

	/**
	 * Debounce function to limit how often a function can be called
	 *
	 * @function debounce
	 * @memberof GreenMetricsAdmin.Utils
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
	 * @function pad
	 * @memberof GreenMetricsAdmin.Utils
	 * @param {Number} num - The number to pad
	 * @return {String} The padded number
	 */
	function pad(num) {
		return num < 10 ? '0' + num : num;
	}

	/**
	 * Get formatted date string for a date N days ago
	 *
	 * @function getDateString
	 * @memberof GreenMetricsAdmin.Utils
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
	 *
	 * @function markDirty
	 * @memberof GreenMetricsAdmin.Utils
	 */
	function markDirty() {
		$( '#submit' ).prop( 'disabled', false ).removeClass( 'button-disabled' );
	}

	/**
	 * Format a number with commas as thousands separators
	 *
	 * @function formatNumber
	 * @memberof GreenMetricsAdmin.Utils
	 * @param {Number} number - The number to format
	 * @return {String} The formatted number
	 */
	function formatNumber(number) {
		return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
	}

	/**
	 * Format a date for display
	 *
	 * @function formatDate
	 * @memberof GreenMetricsAdmin.Utils
	 * @param {String} dateString - Date string in YYYY-MM-DD format
	 * @param {Boolean} includeTime - Whether to include time in the formatted date
	 * @return {String} The formatted date
	 */
	function formatDate(dateString, includeTime = false) {
		if (!dateString) {
			return '';
		}

		const date = new Date(dateString);
		if (isNaN(date.getTime())) {
			return dateString;
		}

		const options = {
			year: 'numeric',
			month: 'short',
			day: 'numeric'
		};

		if (includeTime) {
			options.hour = '2-digit';
			options.minute = '2-digit';
		}

		return date.toLocaleDateString(undefined, options);
	}

	// Public API
	return {
		debounce: debounce,
		pad: pad,
		getDateString: getDateString,
		markDirty: markDirty,
		formatNumber: formatNumber,
		formatDate: formatDate
	};
})( jQuery );