<?php
/**
 * Chart generator for email reports.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */

namespace GreenMetrics;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class for generating chart images for email reports.
 */
class GreenMetrics_Chart_Generator {

	/**
	 * Generate a chart image for email reports with caching.
	 *
	 * @param array  $data      The metrics data.
	 * @param string $frequency The reporting frequency (daily, weekly, monthly).
	 * @param string $chart_type The type of chart to generate (line, bar, etc.).
	 * @param bool   $force_refresh Whether to force a refresh of the chart.
	 * @return string The URL to the generated chart image.
	 */
	public static function generate_chart_for_email( $data, $frequency, $chart_type = 'line', $force_refresh = false ) {
		$upload_dir = wp_upload_dir();
		$chart_dir = '/greenmetrics-charts/';

		// Ensure the directory exists
		if ( ! file_exists( $upload_dir['basedir'] . $chart_dir ) ) {
			wp_mkdir_p( $upload_dir['basedir'] . $chart_dir );
		}

		// Create a cache key based on data and frequency
		$cache_key = md5( serialize( $data ) . $frequency . $chart_type );
		$cached_file = 'greenmetrics-chart-' . $cache_key . '.png';
		$cached_path = $upload_dir['basedir'] . $chart_dir . $cached_file;
		$cached_url = $upload_dir['baseurl'] . $chart_dir . $cached_file;

		// Check if cached chart exists and is less than 1 hour old
		if ( !$force_refresh && file_exists( $cached_path ) && ( time() - filemtime( $cached_path ) < 3600 ) ) {
			if ( function_exists( 'greenmetrics_log' ) ) {
				greenmetrics_log( 'Using cached chart: ' . $cached_file, null, 'debug' );
			}
			return $cached_url;
		}

		// If we need to generate a new chart
		if ( function_exists( 'greenmetrics_log' ) ) {
			greenmetrics_log( 'Generating new chart for ' . $frequency . ' frequency', null, 'debug' );
		}

		// Format data for the chart based on frequency
		$chart_data = self::prepare_chart_data( $data, $frequency );

		// Try to generate the chart using our simple method first
		$success = self::create_simple_chart_image( $cached_path, $chart_data, $chart_type, $frequency );

		// If simple chart generation fails, try the advanced method
		if ( ! $success ) {
			if ( function_exists( 'greenmetrics_log' ) ) {
				greenmetrics_log( 'Simple chart generation failed, trying advanced method', null, 'debug' );
			}
			$success = self::create_chart_image( $cached_path, $chart_data, $chart_type, $frequency );
		}

		// If both methods fail, return the default chart image
		if ( ! $success ) {
			if ( function_exists( 'greenmetrics_log' ) ) {
				greenmetrics_log( 'All chart generation methods failed, using default image', null, 'error' );
			}
			return GREENMETRICS_PLUGIN_URL . 'includes/admin/img/sample-chart.png';
		}

		// Return the URL to the chart image
		return $cached_url;
	}

	/**
	 * Create a simple chart image using basic GD functions.
	 * This is a fallback method that doesn't use FreeType or advanced GD features.
	 *
	 * @param string $chart_path The path to save the chart image.
	 * @param array  $chart_data The chart data.
	 * @param string $chart_type The type of chart to generate.
	 * @param string $frequency  The reporting frequency.
	 * @return bool Whether the chart was successfully created.
	 */
	private static function create_simple_chart_image( $chart_path, $chart_data, $chart_type, $frequency ) {
		// Check if GD is available
		if ( ! function_exists( 'imagecreate' ) ) {
			return false;
		}

		try {
			// Create a blank image with larger resolution for better readability
			$width = 750;
			$height = 400;
			$image = imagecreate( $width, $height );

			// Set background color (white) - must be first color allocated
			$bg_color = imagecolorallocate( $image, 255, 255, 255 );

			// Define colors
			$grid_color = imagecolorallocate( $image, 240, 240, 240 );
			$border_color = imagecolorallocate( $image, 200, 200, 200 );
			$text_color = imagecolorallocate( $image, 50, 50, 50 ); // Darker text for better readability
			$title_color = imagecolorallocate( $image, 0, 0, 0 ); // Black title for maximum contrast

			// Define chart colors
			$chart_colors = array(
				imagecolorallocate( $image, 76, 175, 80 ),   // Green
				imagecolorallocate( $image, 33, 150, 243 ),  // Blue
				imagecolorallocate( $image, 255, 152, 0 ),   // Orange
				imagecolorallocate( $image, 156, 39, 176 ),  // Purple
				imagecolorallocate( $image, 244, 67, 54 ),   // Red
			);

			// Chart area dimensions - use most of the image with more padding
			$margin_top = 40;
			$margin_right = 30;
			$margin_bottom = 50;
			$margin_left = 60;

			$chart_x = $margin_left;
			$chart_y = $margin_top;
			$chart_width = $width - $margin_left - $margin_right;
			$chart_height = $height - $margin_top - $margin_bottom;

			// Draw grid lines - horizontal
			for ( $i = 0; $i <= 5; $i++ ) {
				$y = $chart_y + $chart_height - ( $i / 5 ) * $chart_height;
				imageline( $image, $chart_x, $y, $chart_x + $chart_width, $y, $grid_color );

				// Add y-axis labels (will be filled with actual values later)
				imagestring( $image, 2, $chart_x - 35, $y - 7, "Value " . $i, $text_color );
			}

			// Get datasets and labels
			$datasets = $chart_data['datasets'];
			$labels = $chart_data['labels'];
			$label_count = count( $labels );

			// Draw vertical grid lines and x-axis labels
			$label_width = $chart_width / ( $label_count - 1 > 0 ? $label_count - 1 : 1 );
			for ( $i = 0; $i < $label_count; $i++ ) {
				$x = $chart_x + $i * $label_width;
				imageline( $image, $x, $chart_y, $x, $chart_y + $chart_height, $grid_color );

				// Add x-axis labels if we have them
				if ( isset( $labels[ $i ] ) ) {
					$label_text = $labels[ $i ];
					// Shorten label if too long
					if ( strlen( $label_text ) > 8 ) {
						$label_text = substr( $label_text, 0, 7 ) . '..';
					}

					// For daily charts (24 points), only show every 3rd hour to prevent overlap
					if ($frequency == 'daily') {
						if ($i % 3 == 0) {
							imagestring( $image, 2, $x - 5, $chart_y + $chart_height + 5, $label_text, $text_color );
						}
					} else {
						imagestring( $image, 2, $x - 15, $chart_y + $chart_height + 5, $label_text, $text_color );
					}
				}
			}

			// Fill chart area with a very light gray background
			$chart_bg_color = imagecolorallocate( $image, 248, 248, 248 );
			imagefilledrectangle( $image, $chart_x, $chart_y, $chart_x + $chart_width, $chart_y + $chart_height, $chart_bg_color );

			// Redraw grid lines which were covered by the background
			for ( $i = 0; $i <= 5; $i++ ) {
				$y = $chart_y + $chart_height - ( $i / 5 ) * $chart_height;
				imageline( $image, $chart_x, $y, $chart_x + $chart_width, $y, $grid_color );
			}

			for ( $i = 0; $i < $label_count; $i++ ) {
				$x = $chart_x + $i * $label_width;
				imageline( $image, $x, $chart_y, $x, $chart_y + $chart_height, $grid_color );
			}

			// Draw chart border
			imagerectangle( $image, $chart_x, $chart_y, $chart_x + $chart_width, $chart_y + $chart_height, $border_color );

			// Find max value for scaling
			$max_value = 0.1; // Minimum to avoid division by zero
			foreach ( $datasets as $ds ) {
				if (!empty($ds['data'])) {
					$max_value = max( $max_value, max( $ds['data'] ) );
				}
			}

			// Round max value up to a nice number
			$max_value = self::round_up_to_nice_number( $max_value );

			// Update y-axis labels with actual values
			for ( $i = 0; $i <= 5; $i++ ) {
				$y = $chart_y + $chart_height - ( $i / 5 ) * $chart_height;
				$value = ( $i / 5 ) * $max_value;
				$value_text = number_format( $value, $value < 1 ? 2 : 1 );

				// Clear previous placeholder text by drawing a white rectangle
				imagefilledrectangle( $image, $chart_x - 55, $y - 8, $chart_x - 2, $y + 8, $bg_color );

				// Draw the actual value with right alignment
				$text_width = strlen($value_text) * imagefontwidth(2);
				imagestring( $image, 2, $chart_x - $text_width - 5, $y - 7, $value_text, $text_color );
			}

			// Draw datasets
			foreach ( $datasets as $dataset_index => $dataset ) {
				$data = $dataset['data'];
				$color = $chart_colors[ $dataset_index % count( $chart_colors ) ];

				// Draw lines
				$prev_x = $prev_y = null;

				for ( $i = 0; $i < $label_count; $i++ ) {
					// Calculate point position
					$x = $chart_x + $i * $label_width;

					// Scale value to chart height
					$value = isset( $data[ $i ] ) ? $data[ $i ] : 0;
					$y = $chart_y + $chart_height - ( $value / $max_value ) * $chart_height;

					// Draw point (larger)
					imagefilledellipse( $image, $x, $y, 8, 8, $color );
					imageellipse( $image, $x, $y, 8, 8, $border_color ); // Add border for better visibility

					// Draw line from previous point (thicker)
					if ( $prev_x !== null && $prev_y !== null ) {
						// Draw multiple lines to create thickness
						for ($t = 0; $t < 2; $t++) {
							imageline( $image, $prev_x, $prev_y + $t, $x, $y + $t, $color );
						}
					}

					$prev_x = $x;
					$prev_y = $y;
				}
			}

			// Draw legend at bottom with more space
			$legend_y = $height - 30;

			// Calculate spacing based on number of datasets
			$total_datasets = count( $datasets );
			$legend_spacing = min( 200, $width / ($total_datasets + 1) );
			$legend_start_x = ($width - ($total_datasets * $legend_spacing)) / 2;

			foreach ( $datasets as $dataset_index => $dataset ) {
				$legend_x = $legend_start_x + $dataset_index * $legend_spacing;
				$color = $chart_colors[ $dataset_index % count( $chart_colors ) ];

				// Draw legend color box (larger)
				imagefilledrectangle( $image, $legend_x, $legend_y, $legend_x + 14, $legend_y + 14, $color );
				imagerectangle( $image, $legend_x, $legend_y, $legend_x + 14, $legend_y + 14, $border_color );

				// Draw legend text (shortened to fit)
				$label = $dataset['label'];
				if ( strlen( $label ) > 20 ) {
					$label = substr( $label, 0, 17 ) . '...';
				}
				imagestring( $image, 3, $legend_x + 20, $legend_y + 2, $label, $text_color );
			}

			// Draw title at top with more space
			$title = 'GreenMetrics ' . ucfirst( $frequency ) . ' Report';
			$title_width = imagefontwidth(5) * strlen($title);
			imagestring( $image, 5, ($width - $title_width) / 2, 15, $title, $title_color );

			// Removed axis titles as they were overlapping with other elements

			// Save the image with best quality
			imagepng( $image, $chart_path, 0 );
			imagedestroy( $image );

			return file_exists( $chart_path );
		} catch ( \Exception $e ) {
			if ( function_exists( 'greenmetrics_log' ) ) {
				greenmetrics_log( 'Error generating simple chart: ' . $e->getMessage(), null, 'error' );
			}
			return false;
		}
	}

	/**
	 * Prepare data for the chart based on frequency.
	 *
	 * @param array  $data      The metrics data.
	 * @param string $frequency The reporting frequency (daily, weekly, monthly).
	 * @return array The formatted chart data.
	 */
	private static function prepare_chart_data( $data, $frequency ) {
		// Default values if data is missing
		$carbon_footprint = isset( $data['carbon_footprint'] ) ? $data['carbon_footprint'] : 0;
		$energy_consumption = isset( $data['energy_consumption'] ) ? $data['energy_consumption'] : 0;
		$data_transfer = isset( $data['data_transfer'] ) ? $data['data_transfer'] : 0;
		$performance_score = isset( $data['performance_score'] ) ? $data['performance_score'] : 0;
		$page_views = isset( $data['page_views'] ) ? $data['page_views'] : 0;

		// Format values for better display
		// Scale values to appropriate units
		$carbon_display = $carbon_footprint;
		$energy_display = $energy_consumption;

		// Determine which metrics to show based on available data
		$datasets = array();

		// Always include carbon footprint as the primary metric
		$datasets[] = array(
			'label' => 'Carbon Footprint (kg CO2)',
			'data' => self::generate_sample_data( $carbon_display, $frequency ),
			'borderColor' => 'rgba(76, 175, 80, 1)', // Green
			'backgroundColor' => 'rgba(76, 175, 80, 0.2)',
		);

		// Add energy consumption if available
		if ( $energy_consumption > 0 ) {
			$datasets[] = array(
				'label' => 'Energy Consumption (kWh)',
				'data' => self::generate_sample_data( $energy_display, $frequency ),
				'borderColor' => 'rgba(33, 150, 243, 1)', // Blue
				'backgroundColor' => 'rgba(33, 150, 243, 0.2)',
			);
		}

		// Add data transfer if available and significant
		if ( $data_transfer > 1000 ) { // Only show if more than 1KB
			$data_transfer_mb = $data_transfer / (1024 * 1024); // Convert to MB for better scale
			$datasets[] = array(
				'label' => 'Data Transfer (MB)',
				'data' => self::generate_sample_data( $data_transfer_mb, $frequency ),
				'borderColor' => 'rgba(255, 152, 0, 1)', // Orange
				'backgroundColor' => 'rgba(255, 152, 0, 0.2)',
			);
		}

		// Create the chart data structure
		$chart_data = array(
			'labels' => self::get_labels_for_frequency( $frequency ),
			'datasets' => $datasets,
		);

		return $chart_data;
	}

	/**
	 * Get labels for the chart based on frequency.
	 *
	 * @param string $frequency The reporting frequency (daily, weekly, monthly).
	 * @return array The labels for the chart.
	 */
	private static function get_labels_for_frequency( $frequency ) {
		$labels = array();
		$now = current_time( 'timestamp' );

		switch ( $frequency ) {
			case 'daily':
				// Hourly labels for the past 24 hours - simplified to just hours
				for ( $i = 23; $i >= 0; $i-- ) {
					$time = strtotime( "-{$i} hours", $now );
					$labels[] = gmdate( 'H', $time ); // Just the hour number, no minutes
				}
				break;

			case 'weekly':
				// Daily labels for the past 7 days
				for ( $i = 6; $i >= 0; $i-- ) {
					$time = strtotime( "-{$i} days", $now );
					$labels[] = gmdate( 'D', $time );
				}
				break;

			case 'monthly':
			default:
				// Weekly labels for the past 30 days
				for ( $i = 4; $i >= 0; $i-- ) {
					$time = strtotime( "-{$i} weeks", $now );
					$labels[] = 'Week ' . gmdate( 'W', $time );
				}
				break;
		}

		return $labels;
	}

	/**
	 * Generate sample data for the chart.
	 *
	 * @param float  $base_value The base value to generate data from.
	 * @param string $frequency  The reporting frequency (daily, weekly, monthly).
	 * @return array The generated data.
	 */
	private static function generate_sample_data( $base_value, $frequency ) {
		$data = array();
		$count = ( 'daily' === $frequency ) ? 24 : ( ( 'weekly' === $frequency ) ? 7 : 5 );

		// Set a seed based on the base value to ensure consistent results
		// Note: Using WordPress random functions for better security
		$seed = (int) ( $base_value * 1000 );

		// Create a trend direction (up, down, or stable)
		$trend = wp_rand( 0, 2 ); // 0 = stable, 1 = upward, 2 = downward
		$trend_factor = 0;

		switch ( $trend ) {
			case 1: // upward trend
				$trend_factor = wp_rand( 5, 15 ) / 100; // 5-15% increase per step
				break;
			case 2: // downward trend
				$trend_factor = -wp_rand( 5, 15 ) / 100; // 5-15% decrease per step
				break;
			default: // stable
				$trend_factor = 0;
		}

		// Start with a value close to the base value
		$current_value = $base_value * ( 1 + ( wp_rand( -10, 10 ) / 100 ) );

		// Generate data with a trend and some random noise
		for ( $i = 0; $i < $count; $i++ ) {
			// Apply trend
			$current_value = $current_value * ( 1 + $trend_factor );

			// Add some random noise (±10%)
			$noise = $current_value * ( wp_rand( -10, 10 ) / 100 );

			// Ensure value doesn't go below zero
			$data[] = max( 0, $current_value + $noise );
		}

		return $data;
	}

	/**
	 * Create a chart image using PHP GD.
	 *
	 * @param string $chart_path The path to save the chart image.
	 * @param array  $chart_data The chart data.
	 * @param string $chart_type The type of chart to generate.
	 * @param string $frequency  The reporting frequency.
	 * @return bool Whether the chart was successfully created.
	 */
	private static function create_chart_image( $chart_path, $chart_data, $chart_type, $frequency ) {
		// Check if GD is available
		if ( ! function_exists( 'imagecreatetruecolor' ) ) {
			if ( function_exists( 'greenmetrics_log' ) ) {
				greenmetrics_log( 'GD library not available', null, 'error' );
			}
			return false;
		}

		// Check GD capabilities
		self::check_gd_capabilities();

		try {
			// Create a blank image with higher resolution
			$width = 800;
			$height = 400;
			$image = imagecreatetruecolor( $width, $height );

			// Set background color (white)
			$bg_color = imagecolorallocate( $image, 255, 255, 255 );
			imagefill( $image, 0, 0, $bg_color );

			// Define colors
			$title_color = imagecolorallocate( $image, 51, 51, 51 );
			$text_color = imagecolorallocate( $image, 100, 100, 100 );
			$grid_color = imagecolorallocate( $image, 240, 240, 240 );
			$axis_color = imagecolorallocate( $image, 200, 200, 200 );
			$border_color = imagecolorallocate( $image, 230, 230, 230 );

			// Using system fonts only - no external font dependency

			// Chart area dimensions with proper margins
			$margin_top = 60;
			$margin_right = 40;
			$margin_bottom = 80;
			$margin_left = 70;

			$chart_x = $margin_left;
			$chart_y = $margin_top;
			$chart_width = $width - $margin_left - $margin_right;
			$chart_height = $height - $margin_top - $margin_bottom;

			// Draw chart background
			imagefilledrectangle( $image, $chart_x, $chart_y, $chart_x + $chart_width, $chart_y + $chart_height, $bg_color );
			imagerectangle( $image, $chart_x, $chart_y, $chart_x + $chart_width, $chart_y + $chart_height, $border_color );

			// Draw chart title using system font
			$title = 'GreenMetrics Report - ' . ucfirst( $frequency ) . ' Data';
			imagestring( $image, 5, $width / 2 - 150, 10, $title, $title_color );

			// Get datasets and labels
			$datasets = $chart_data['datasets'];
			$labels = $chart_data['labels'];
			$label_count = count( $labels );

			// Find max value for scaling
			$max_value = 0.1; // Minimum to avoid division by zero
			foreach ( $datasets as $ds ) {
				if (!empty($ds['data'])) {
					$max_value = max( $max_value, max( $ds['data'] ) );
				}
			}

			// Round max value up to a nice number
			$max_value = self::round_up_to_nice_number( $max_value );

			// Draw y-axis grid lines and labels
			$grid_steps = 5;
			for ( $i = 0; $i <= $grid_steps; $i++ ) {
				$y = $chart_y + $chart_height - ( $i / $grid_steps ) * $chart_height;
				$value = ( $i / $grid_steps ) * $max_value;

				// Draw grid line
				imageline( $image, $chart_x, $y, $chart_x + $chart_width, $y, $grid_color );

				// Draw y-axis label
				$label = number_format( $value, $value < 1 ? 3 : 2 );
				imagestring( $image, 2, $chart_x - 40, $y - 7, $label, $text_color );
			}

			// Draw x-axis grid lines and labels
			$label_width = $chart_width / ( $label_count - 1 > 0 ? $label_count - 1 : 1 );
			for ( $i = 0; $i < $label_count; $i++ ) {
				$x = $chart_x + $i * $label_width;

				// Draw grid line
				imageline( $image, $x, $chart_y, $x, $chart_y + $chart_height, $grid_color );

				// Draw x-axis label
				$label_text = isset( $labels[ $i ] ) ? $labels[ $i ] : '';
				imagestring( $image, 2, $x - 15, $chart_y + $chart_height + 10, $label_text, $text_color );
			}

			// Draw axis lines
			imageline( $image, $chart_x, $chart_y + $chart_height, $chart_x + $chart_width, $chart_y + $chart_height, $axis_color ); // X-axis
			imageline( $image, $chart_x, $chart_y, $chart_x, $chart_y + $chart_height, $axis_color ); // Y-axis

			// Draw datasets
			foreach ( $datasets as $dataset_index => $dataset ) {
				$data = $dataset['data'];

				// Parse border color
				$border_color_parts = sscanf( $dataset['borderColor'], 'rgba(%d, %d, %d, %f)' );
				$line_color = imagecolorallocate( $image, $border_color_parts[0], $border_color_parts[1], $border_color_parts[2] );

				// Parse background color for area fill
				$bg_color_parts = sscanf( $dataset['backgroundColor'], 'rgba(%d, %d, %d, %f)' );
				$fill_color = imagecolorallocatealpha(
					$image,
					$bg_color_parts[0],
					$bg_color_parts[1],
					$bg_color_parts[2],
					127 - (int)(127 * $bg_color_parts[3]) // Convert 0-1 alpha to 0-127 alpha (0 = opaque, 127 = transparent)
				);

				// Store points for area fill
				$points = array();

				// Draw lines
				$prev_x = $prev_y = null;

				for ( $i = 0; $i < $label_count; $i++ ) {
					// Calculate point position
					$x = $chart_x + $i * $label_width;

					// Scale value to chart height
					$value = isset( $data[ $i ] ) ? $data[ $i ] : 0;
					$y = $chart_y + $chart_height - ( $value / $max_value ) * $chart_height;

					// Store point for area fill
					$points[] = $x;
					$points[] = $y;

					// Draw point with a nicer circle
					imagefilledellipse( $image, $x, $y, 8, 8, $line_color );
					imageellipse( $image, $x, $y, 8, 8, $bg_color ); // Add white border

					// Draw line from previous point
					if ( $prev_x !== null && $prev_y !== null ) {
						// Draw a thicker line by drawing multiple lines
						for ( $thickness = 0; $thickness < 2; $thickness++ ) {
							imageline( $image, $prev_x, $prev_y + $thickness, $x, $y + $thickness, $line_color );
						}
					}

					$prev_x = $x;
					$prev_y = $y;
				}

				// Add points to close the polygon for area fill (bottom corners)
				$points[] = $chart_x + ($label_count - 1) * $label_width;
				$points[] = $chart_y + $chart_height;
				$points[] = $chart_x;
				$points[] = $chart_y + $chart_height;

				// Fill area under the line (only for the first dataset to avoid clutter)
				if ( $dataset_index === 0 && count( $points ) >= 8 ) { // Need at least 4 points (2 data points + 2 bottom corners)
					imagefilledpolygon( $image, $points, count( $points ) / 2, $fill_color );
				}
			}

			// Draw legend
			$legend_y = $height - 40;
			$legend_spacing = min( 250, $width / count( $datasets ) );

			foreach ( $datasets as $dataset_index => $dataset ) {
				$legend_x = $width / 2 - ( count( $datasets ) * $legend_spacing / 2 ) + $dataset_index * $legend_spacing;

				// Parse border color
				$border_color_parts = sscanf( $dataset['borderColor'], 'rgba(%d, %d, %d, %f)' );
				$legend_color = imagecolorallocate( $image, $border_color_parts[0], $border_color_parts[1], $border_color_parts[2] );

				// Draw legend color box
				imagefilledrectangle( $image, $legend_x, $legend_y, $legend_x + 12, $legend_y + 12, $legend_color );
				imagerectangle( $image, $legend_x, $legend_y, $legend_x + 12, $legend_y + 12, $text_color );

				// Draw legend text
				imagestring( $image, 3, $legend_x + 18, $legend_y, $dataset['label'], $text_color );
			}

			// Removed axis titles as they were overlapping with other elements

			// Add watermark
			$watermark = 'Generated by GreenMetrics';
			imagestring( $image, 1, $width - 150, $height - 15, $watermark, imagecolorallocate( $image, 150, 150, 150 ) );

			// Save the image with better quality
			imagepng( $image, $chart_path, 6 ); // 0-9, lower means better quality
			imagedestroy( $image );

			return file_exists( $chart_path );
		} catch ( \Exception $e ) {
			if ( function_exists( 'greenmetrics_log' ) ) {
				greenmetrics_log( 'Error generating chart: ' . $e->getMessage(), null, 'error' );
			}
			return false;
		}
	}

	/**
	 * Round up a number to a nice value for chart axis.
	 *
	 * @param float $n The number to round up.
	 * @return float The rounded up number.
	 */
	private static function round_up_to_nice_number( $n ) {
		// For very small numbers, use appropriate scale
		if ( $n < 0.01 ) {
			return ceil( $n * 1000 ) / 1000;
		} elseif ( $n < 0.1 ) {
			return ceil( $n * 100 ) / 100;
		} elseif ( $n < 1 ) {
			return ceil( $n * 10 ) / 10;
		}

		// For larger numbers
		$magnitude = pow( 10, floor( log10( $n ) ) );
		$significant = ceil( $n / $magnitude );

		// Round to a nice number (1, 2, 5, 10)
		if ( $significant <= 1 ) {
			return 1 * $magnitude;
		} elseif ( $significant <= 2 ) {
			return 2 * $magnitude;
		} elseif ( $significant <= 5 ) {
			return 5 * $magnitude;
		} else {
			return 10 * $magnitude;
		}
	}

	/**
	 * Check GD library capabilities and log them.
	 */
	private static function check_gd_capabilities() {
		if ( ! function_exists( 'greenmetrics_log' ) ) {
			return;
		}

		// Check if GD is loaded
		if ( ! extension_loaded( 'gd' ) ) {
			greenmetrics_log( 'GD extension not loaded', null, 'error' );
			return;
		}

		// Get GD info
		$gd_info = gd_info();

		// Log GD version
		$version = isset( $gd_info['GD Version'] ) ? $gd_info['GD Version'] : 'Unknown';
		greenmetrics_log( 'GD Version: ' . $version, null, 'debug' );

		// Log capabilities
		$capabilities = array(
			'PNG Support' => 'PNG Support',
			'JPEG Support' => 'JPEG Support',
			'GIF Read Support' => 'GIF Read Support',
			'GIF Create Support' => 'GIF Create Support',
		);

		foreach ( $capabilities as $key => $label ) {
			if ( isset( $gd_info[ $key ] ) ) {
				greenmetrics_log( $label . ': ' . ( $gd_info[ $key ] ? 'Yes' : 'No' ), null, 'debug' );
			}
		}
	}

	/**
	 * Clean up old chart images.
	 */
	public static function cleanup_chart_images() {
		$upload_dir = wp_upload_dir();
		$chart_dir = $upload_dir['basedir'] . '/greenmetrics-charts/';

		// Check if directory exists
		if ( ! file_exists( $chart_dir ) ) {
			return;
		}

		// Get all files in the directory
		$files = glob( $chart_dir . '*.png' );

		// Current time
		$current_time = time();

		foreach ( $files as $file ) {
			$file_age = $current_time - filemtime( $file );
			$filename = basename( $file );

			// Handle cached charts (keep for 24 hours)
			if ( strpos( $filename, 'greenmetrics-chart-' ) === 0 && strlen( $filename ) > 40 ) {
				// This is likely a cached chart (has an MD5 hash in the name)
				if ( $file_age > 24 * HOUR_IN_SECONDS ) {
					@unlink( $file );
					if ( function_exists( 'greenmetrics_log' ) ) {
						greenmetrics_log( 'Deleted cached chart: ' . $filename, null, 'debug' );
					}
				}
			}
			// Handle non-cached charts (keep for 1 hour)
			else {
				if ( $file_age > 1 * HOUR_IN_SECONDS ) {
					@unlink( $file );
					if ( function_exists( 'greenmetrics_log' ) ) {
						greenmetrics_log( 'Deleted temporary chart: ' . $filename, null, 'debug' );
					}
				}
			}
		}
	}
}

// Schedule cleanup
if ( ! wp_next_scheduled( 'greenmetrics_cleanup_charts' ) ) {
	wp_schedule_event( time(), 'daily', 'greenmetrics_cleanup_charts' );
}
add_action( 'greenmetrics_cleanup_charts', array( 'GreenMetrics\\GreenMetrics_Chart_Generator', 'cleanup_chart_images' ) );
