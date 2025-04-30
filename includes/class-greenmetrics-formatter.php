<?php
/**
 * Formatting utility class for GreenMetrics.
 *
 * This class provides standardized formatting methods for all metrics
 * to ensure consistent display throughout the plugin.
 *
 * @since      1.0.0
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */

namespace GreenMetrics;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Formatting utility class.
 *
 * Provides methods for formatting various metrics consistently
 * throughout the plugin.
 */
class GreenMetrics_Formatter {

	/**
	 * Format carbon emissions for display.
	 *
	 * @param float $grams Carbon emissions in grams.
	 * @return string Formatted string with appropriate unit.
	 */
	public static function format_carbon_emissions( $grams ) {
		if ( ! is_numeric( $grams ) || $grams < 0 ) {
			return '0 g';
		}

		if ( $grams >= 1000 ) {
			return number_format( $grams / 1000, 2 ) . ' kg';
		} elseif ( $grams >= 1 ) {
			return number_format( $grams, 2 ) . ' g';
		} elseif ( $grams >= 0.001 ) {
			return number_format( $grams * 1000, 2 ) . ' mg';
		} else {
			// For extremely small values, increase precision to avoid showing 0
			return number_format( $grams, 6 ) . ' g';
		}
	}

	/**
	 * Format data transfer for display.
	 *
	 * @param float $bytes Data transfer in bytes.
	 * @return string Formatted string with appropriate unit.
	 */
	public static function format_data_transfer( $bytes ) {
		return self::format_bytes( $bytes );
	}

	/**
	 * Format energy consumption for display.
	 *
	 * @param float $kwh Energy consumption in kWh.
	 * @return string Formatted string with appropriate unit.
	 */
	public static function format_energy_consumption( $kwh ) {
		if ( ! is_numeric( $kwh ) || $kwh < 0 ) {
			return '0 kWh';
		}

		return number_format( $kwh, 4 ) . ' kWh';
	}

	/**
	 * Format load time for display.
	 *
	 * @param float $seconds Load time in seconds.
	 * @return string Formatted string with appropriate unit.
	 */
	public static function format_load_time( $seconds ) {
		if ( ! is_numeric( $seconds ) || $seconds < 0 ) {
			return '0 ms';
		}

		if ( $seconds < 1 ) {
			return round( $seconds * 1000, 2 ) . ' ms';
		} else {
			return round( $seconds, 2 ) . ' s';
		}
	}

	/**
	 * Format performance score for display.
	 *
	 * @param float $score Performance score (0-100).
	 * @return string Formatted string with percentage.
	 */
	public static function format_performance_score( $score ) {
		if ( ! is_numeric( $score ) || $score < 0 ) {
			return '0%';
		}

		return number_format( $score, 2 ) . '%';
	}

	/**
	 * Format number of views for display.
	 *
	 * @param int $views Number of views.
	 * @return string Formatted string.
	 */
	public static function format_views( $views ) {
		if ( ! is_numeric( $views ) || $views < 0 ) {
			return '0';
		}

		return number_format( $views );
	}

	/**
	 * Format number of requests for display.
	 *
	 * @param int $requests Number of HTTP requests.
	 * @return string Formatted string.
	 */
	public static function format_requests( $requests ) {
		if ( ! is_numeric( $requests ) || $requests < 0 ) {
			return '0';
		}

		return number_format( $requests );
	}

	/**
	 * Format bytes to human-readable format.
	 *
	 * @param int $bytes The size in bytes.
	 * @param int $precision The number of decimal places.
	 * @return string The formatted size.
	 */
	public static function format_bytes( $bytes, $precision = 2 ) {
		if ( ! is_numeric( $bytes ) || $bytes < 0 ) {
			return '0 B';
		}

		$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );
		
		$bytes = max( $bytes, 0 );
		$pow = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow = min( $pow, count( $units ) - 1 );
		
		$bytes /= pow( 1024, $pow );
		
		return round( $bytes, $precision ) . ' ' . $units[ $pow ];
	}

	/**
	 * Format a date for display.
	 *
	 * @param string $date_string Date string in MySQL format.
	 * @param string $format Optional. PHP date format. Default 'M j, Y'.
	 * @return string Formatted date.
	 */
	public static function format_date( $date_string, $format = 'M j, Y' ) {
		if ( empty( $date_string ) ) {
			return '';
		}

		$timestamp = strtotime( $date_string );
		if ( false === $timestamp ) {
			return '';
		}

		return date_i18n( $format, $timestamp );
	}

	/**
	 * Format all metrics in a data array.
	 *
	 * @param array $metrics Array of metrics data.
	 * @return array Formatted metrics.
	 */
	public static function format_all_metrics( $metrics ) {
		if ( ! is_array( $metrics ) ) {
			return array();
		}

		$formatted = array();

		// Format carbon footprint
		if ( isset( $metrics['carbon_footprint'] ) ) {
			$formatted['carbon_footprint'] = self::format_carbon_emissions( $metrics['carbon_footprint'] );
		}

		// Format energy consumption
		if ( isset( $metrics['energy_consumption'] ) ) {
			$formatted['energy_consumption'] = self::format_energy_consumption( $metrics['energy_consumption'] );
		}

		// Format data transfer
		if ( isset( $metrics['data_transfer'] ) ) {
			$formatted['data_transfer'] = self::format_data_transfer( $metrics['data_transfer'] );
		}

		// Format views
		if ( isset( $metrics['total_views'] ) ) {
			$formatted['total_views'] = self::format_views( $metrics['total_views'] );
		}

		// Format requests
		if ( isset( $metrics['requests'] ) ) {
			$formatted['requests'] = self::format_requests( $metrics['requests'] );
		}

		// Format performance score
		if ( isset( $metrics['performance_score'] ) ) {
			$formatted['performance_score'] = self::format_performance_score( $metrics['performance_score'] );
		}

		// Format load time
		if ( isset( $metrics['load_time'] ) ) {
			$formatted['load_time'] = self::format_load_time( $metrics['load_time'] );
		}

		return $formatted;
	}
}
