<?php
/**
 * Class responsible for calculating carbon emissions and energy consumption.
 *
 * This class provides methods to calculate and format environmental impact metrics
 * based on data transfer. The default constants are aligned with the Settings Manager
 * and can be overridden by user settings.
 *
 * @since      1.0.0
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

namespace GreenMetrics;

class GreenMetrics_Calculator {
	/**
	 * Carbon intensity in kg CO2/kWh.
	 * This default value is consistent with the Settings Manager and can be overridden by user settings.
	 */
	const CARBON_INTENSITY = 0.475; // kg CO2/kWh

	/**
	 * Power Usage Effectiveness (PUE).
	 * Represents data center efficiency factor.
	 */
	const PUE = 1.67;

	/**
	 * Energy consumption per byte in kWh/byte.
	 * This default value is consistent with the Settings Manager and can be overridden by user settings.
	 */
	const ENERGY_PER_BYTE = 0.000000000072; // kWh/byte

	/**
	 * Calculate carbon emissions for data transfer.
	 *
	 * @param float      $bytes Data transfer in bytes.
	 * @param float|null $carbon_intensity Optional custom carbon intensity value.
	 * @return float Carbon emissions in grams.
	 */
	public static function calculate_carbon_emissions( $bytes, $carbon_intensity = null ) {
		if ( ! is_numeric( $bytes ) || $bytes < 0 ) {
			greenmetrics_log( 'Invalid data transfer value', $bytes, 'error' );
			return 0;
		}

		// Calculate energy consumption directly in kWh (ENERGY_PER_BYTE is now in kWh/byte)
		$energy_kwh = $bytes * self::ENERGY_PER_BYTE * self::PUE;

		// Use provided carbon intensity or default
		$intensity = $carbon_intensity ?? self::CARBON_INTENSITY;

		// Calculate carbon emissions (intensity is in kg CO2/kWh, we need grams)
		$carbon_grams = $energy_kwh * $intensity * 1000; // Convert kg to grams

		greenmetrics_log(
			'Carbon emissions calculated',
			array(
				'bytes'            => $bytes,
				'energy_kwh'       => $energy_kwh,
				'carbon_intensity' => $intensity,
				'carbon_grams'     => $carbon_grams,
			)
		);

		return $carbon_grams;
	}

	/**
	 * Calculate energy consumption for data transfer.
	 *
	 * @param float $bytes Data transfer in bytes.
	 * @return float Energy consumption in kWh.
	 */
	public static function calculate_energy_consumption( $bytes ) {
		if ( ! is_numeric( $bytes ) || $bytes < 0 ) {
			greenmetrics_log( 'Invalid data transfer value', $bytes, 'error' );
			return 0;
		}

		// Calculate energy consumption directly in kWh (ENERGY_PER_BYTE is now in kWh/byte)
		$energy_kwh = $bytes * self::ENERGY_PER_BYTE * self::PUE;

		greenmetrics_log(
			'Energy consumption calculated',
			array(
				'bytes'      => $bytes,
				'energy_kwh' => $energy_kwh,
			)
		);

		return $energy_kwh;
	}

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
		if ( ! is_numeric( $bytes ) || $bytes < 0 ) {
			return '0 B';
		}

		if ( $bytes < 1024 ) {
			return $bytes . ' B';
		} elseif ( $bytes < 1048576 ) {
			return round( $bytes / 1024, 2 ) . ' KB';
		} elseif ( $bytes < 1073741824 ) {
			return round( $bytes / 1048576, 2 ) . ' MB';
		} else {
			return round( $bytes / 1073741824, 2 ) . ' GB';
		}
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
	 * Format load time
	 *
	 * @param float $seconds Load time in seconds
	 * @return string Formatted time with appropriate unit
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
	 * Get optimization suggestions based on data transfer.
	 *
	 * @param float $bytes Data transfer in bytes.
	 * @return array Array of optimization suggestions.
	 */
	public static function get_optimization_suggestions( $bytes ) {
		if ( ! is_numeric( $bytes ) || $bytes < 0 ) {
			return array();
		}

		$suggestions = array();

		// Check for large images
		if ( $bytes > 2 * 1024 * 1024 ) { // More than 2MB
			$suggestions[] = array(
				'id'          => 'optimize_images',
				'title'       => __( 'Optimize Images', 'greenmetrics' ),
				'description' => __( 'Your page contains large images. Consider compressing them to reduce data transfer.', 'greenmetrics' ),
				'priority'    => 'high',
				'icon'        => '<path fill="currentColor" d="M8.5,13.5L11,16.5L14.5,12L19,18H5M21,19V5C21,3.89 20.1,3 19,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19Z" />',
			);
		}

		// Check for missing caching
		if ( ! defined( 'WP_CACHE' ) || ! WP_CACHE ) {
			$suggestions[] = array(
				'id'          => 'enable_caching',
				'title'       => __( 'Enable Caching', 'greenmetrics' ),
				'description' => __( 'Caching is not enabled. Enable caching to reduce server load and data transfer.', 'greenmetrics' ),
				'priority'    => 'medium',
				'icon'        => '<path fill="currentColor" d="M12,3C7.58,3 4,4.79 4,7C4,9.21 7.58,11 12,11C16.42,11 20,9.21 20,7C20,4.79 16.42,3 12,3M4,9V12C4,14.21 7.58,16 12,16C16.42,16 20,14.21 20,12V9C20,11.21 16.42,13 12,13C7.58,13 4,11.21 4,9M4,14V17C4,19.21 7.58,21 12,21C16.42,21 20,19.21 20,17V14C20,16.21 16.42,18 12,18C7.58,18 4,16.21 4,14Z" />',
			);
		}

		// Check for missing lazy loading
		if ( ! has_filter( 'wp_lazy_loading_enabled', '__return_true' ) ) {
			$suggestions[] = array(
				'id'          => 'enable_lazy_loading',
				'title'       => __( 'Enable Lazy Loading', 'greenmetrics' ),
				'description' => __( 'Lazy loading is not enabled. Enable it to reduce initial page load size.', 'greenmetrics' ),
				'priority'    => 'medium',
				'icon'        => '<path fill="currentColor" d="M19,19H5V5H19M19,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5A2,2 0 0,0 19,3M13.96,12.29L11.21,15.83L9.25,13.47L6.5,17H17.5L13.96,12.29Z" />',
			);
		}

		greenmetrics_log(
			'Generated optimization suggestions',
			array(
				'bytes'             => $bytes,
				'suggestions_count' => count( $suggestions ),
			)
		);

		return $suggestions;
	}
}
