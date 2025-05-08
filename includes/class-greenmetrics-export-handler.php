<?php
/**
 * GreenMetrics Export Handler
 *
 * Handles data export functionality for GreenMetrics.
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
 * GreenMetrics Export Handler Class
 *
 * Handles exporting metrics data in various formats.
 *
 * @since      1.0.0
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */
class GreenMetrics_Export_Handler {

	/**
	 * The singleton instance of this class.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      GreenMetrics_Export_Handler    $instance    The singleton instance.
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance of this class.
	 *
	 * @since     1.0.0
	 * @return    GreenMetrics_Export_Handler    The singleton instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 */
	private function __construct() {
		// Private constructor to enforce singleton pattern.
	}

	/**
	 * Export data in the specified format.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Export arguments.
	 * @return   array|WP_Error    Export result or error.
	 */
	public function export_data( $args = array() ) {
		// Default arguments
		$defaults = array(
			'format'           => 'csv',       // Export format: 'csv', 'json', or 'pdf'
			'data_type'        => 'raw',       // Data type: 'raw' or 'aggregated'
			'start_date'       => '',          // Start date for filtering (YYYY-MM-DD)
			'end_date'         => '',          // End date for filtering (YYYY-MM-DD)
			'page_id'          => 0,           // Page ID for filtering (0 for all pages)
			'aggregation_type' => 'daily',     // Aggregation type for aggregated data
			'include_headers'  => true,        // Include headers in CSV export
			'limit'            => 10000,       // Maximum number of records to export
		);

		$args = wp_parse_args( $args, $defaults );

		// Validate arguments
		if ( ! in_array( $args['format'], array( 'csv', 'json', 'pdf' ), true ) ) {
			return new \WP_Error( 'invalid_format', __( 'Invalid export format.', 'greenmetrics' ) );
		}

		if ( ! in_array( $args['data_type'], array( 'raw', 'aggregated' ), true ) ) {
			return new \WP_Error( 'invalid_data_type', __( 'Invalid data type.', 'greenmetrics' ) );
		}

		// Set date range if not provided
		if ( empty( $args['start_date'] ) ) {
			$args['start_date'] = date( 'Y-m-d', strtotime( '-30 days' ) );
		}

		if ( empty( $args['end_date'] ) ) {
			$args['end_date'] = date( 'Y-m-d' );
		}

		// Get data based on type
		if ( 'raw' === $args['data_type'] ) {
			$data = $this->get_raw_data( $args );
		} else {
			$data = $this->get_aggregated_data( $args );
		}

		// Check if data retrieval was successful
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Format data based on requested format
		if ( 'csv' === $args['format'] ) {
			return $this->format_as_csv( $data, $args );
		} elseif ( 'pdf' === $args['format'] ) {
			return $this->format_as_pdf( $data, $args );
		} else {
			return $this->format_as_json( $data, $args );
		}
	}

	/**
	 * Get raw metrics data.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments.
	 * @return   array|WP_Error    Raw data or error.
	 */
	private function get_raw_data( $args ) {
		global $wpdb;

		// Get table name
		$table_name = $wpdb->prefix . 'greenmetrics_stats';

		// Check if table exists
		if ( ! GreenMetrics_DB_Helper::table_exists( $table_name ) ) {
			return new \WP_Error( 'table_not_found', __( 'Metrics data table not found.', 'greenmetrics' ) );
		}

		// Sanitize table name and wrap in backticks
		$table_name = '`' . esc_sql( $table_name ) . '`';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- safe identifier interpolation

		// Build query
		$query = "SELECT
			id,
			page_id,
			data_transfer,
			load_time,
			requests,
			carbon_footprint,
			energy_consumption,
			performance_score,
			created_at
		FROM {$table_name}
		WHERE 1=1";

		$query_args = array();

		// Add date range filter
		if ( ! empty( $args['start_date'] ) ) {
			$query .= " AND created_at >= %s";
			$query_args[] = $args['start_date'] . ' 00:00:00';
		}

		if ( ! empty( $args['end_date'] ) ) {
			$query .= " AND created_at <= %s";
			$query_args[] = $args['end_date'] . ' 23:59:59';
		}

		// Add page filter
		if ( ! empty( $args['page_id'] ) ) {
			$query .= " AND page_id = %d";
			$query_args[] = $args['page_id'];
		}

		// Add order and limit
		$query .= " ORDER BY created_at DESC LIMIT %d";
		$query_args[] = $args['limit'];

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- safe identifier interpolation
		// Prepare and execute query
		$prepared_query = $wpdb->prepare( $query, $query_args );
		$results = $wpdb->get_results( $prepared_query, ARRAY_A );

		// Check for database errors
		if ( $wpdb->last_error ) {
			return new \WP_Error( 'database_error', $wpdb->last_error );
		}

		// Add page titles to results
		$results = $this->add_page_titles( $results );

		return $results;
	}

	/**
	 * Get aggregated metrics data.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments.
	 * @return   array|WP_Error    Aggregated data or error.
	 */
	private function get_aggregated_data( $args ) {
		global $wpdb;

		// Get table name
		$table_name = $wpdb->prefix . 'greenmetrics_aggregated_stats';

		// Check if table exists
		if ( ! GreenMetrics_DB_Helper::table_exists( $table_name ) ) {
			return new \WP_Error( 'table_not_found', __( 'Aggregated metrics data table not found.', 'greenmetrics' ) );
		}

		// Sanitize table name and wrap in backticks
		$table_name = '`' . esc_sql( $table_name ) . '`';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- safe identifier interpolation

		// Build query
		$query = "SELECT
			id,
			page_id,
			date_start,
			date_end,
			aggregation_type,
			views,
			total_data_transfer,
			avg_data_transfer,
			total_load_time,
			avg_load_time,
			total_requests,
			avg_requests,
			total_carbon_footprint,
			avg_carbon_footprint,
			total_energy_consumption,
			avg_energy_consumption,
			avg_performance_score,
			created_at
		FROM {$table_name}
		WHERE 1=1";

		$query_args = array();

		// Add aggregation type filter
		if ( ! empty( $args['aggregation_type'] ) ) {
			$query .= " AND aggregation_type = %s";
			$query_args[] = $args['aggregation_type'];
		}

		// Add date range filter
		if ( ! empty( $args['start_date'] ) ) {
			$query .= " AND date_start >= %s";
			$query_args[] = $args['start_date'] . ' 00:00:00';
		}

		if ( ! empty( $args['end_date'] ) ) {
			$query .= " AND date_end <= %s";
			$query_args[] = $args['end_date'] . ' 23:59:59';
		}

		// Add page filter
		if ( ! empty( $args['page_id'] ) ) {
			$query .= " AND page_id = %d";
			$query_args[] = $args['page_id'];
		}

		// Add order and limit
		$query .= " ORDER BY date_start DESC LIMIT %d";
		$query_args[] = $args['limit'];

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- safe identifier interpolation
		// Prepare and execute query
		$prepared_query = $wpdb->prepare( $query, $query_args );
		$results = $wpdb->get_results( $prepared_query, ARRAY_A );

		// Check for database errors
		if ( $wpdb->last_error ) {
			return new \WP_Error( 'database_error', $wpdb->last_error );
		}

		// Add page titles to results
		$results = $this->add_page_titles( $results );

		return $results;
	}

	/**
	 * Add page titles to results.
	 *
	 * @since    1.0.0
	 * @param    array    $results    Query results.
	 * @return   array    Results with page titles.
	 */
	private function add_page_titles( $results ) {
		if ( empty( $results ) ) {
			return array();
		}

		// Get unique page IDs
		$page_ids = array_unique( wp_list_pluck( $results, 'page_id' ) );

		// Get page titles
		$page_titles = array();
		foreach ( $page_ids as $page_id ) {
			$page_titles[ $page_id ] = get_the_title( $page_id );

			// If no title, use the permalink or "Unknown"
			if ( empty( $page_titles[ $page_id ] ) ) {
				$permalink = get_permalink( $page_id );
				$page_titles[ $page_id ] = $permalink ? $permalink : __( 'Unknown', 'greenmetrics' );
			}
		}

		// Add page titles to results
		foreach ( $results as &$result ) {
			$result['page_title'] = isset( $page_titles[ $result['page_id'] ] ) ? $page_titles[ $result['page_id'] ] : __( 'Unknown', 'greenmetrics' );
		}

		return $results;
	}

	/**
	 * Sanitize a CSV cell to prevent CSV injection attacks.
	 *
	 * @since    1.0.0
	 * @param    mixed    $cell    Cell value.
	 * @return   string   Sanitized cell value.
	 */
	private function safe_csv_cell( $cell ) {
		// Convert to string
		$cell = (string) $cell;

		// If the cell starts with =, +, -, @, or tab/newline, prefix with a single quote
		// This prevents formula injection in spreadsheet applications
		if ( preg_match('/^[=+\-@\t\r\n]/', $cell) ) {
			return "'" . $cell;
		}

		return $cell;
	}

	/**
	 * Format data as CSV.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Data to format.
	 * @param    array    $args    Export arguments.
	 * @return   array    Formatted data.
	 */
	private function format_as_csv( $data, $args ) {
		if ( empty( $data ) ) {
			return array(
				'content' => '',
				'filename' => 'greenmetrics-export-empty.csv',
				'type' => 'text/csv',
			);
		}

		// Start output buffer
		ob_start();

		// Create a file pointer
		$output = fopen( 'php://output', 'w' );

		// Add headers if requested
		if ( $args['include_headers'] ) {
			// Sanitize header names to prevent CSV injection
			$headers = array_map( array( $this, 'safe_csv_cell' ), array_keys( $data[0] ) );
			fputcsv( $output, $headers );
		}

		// Add data rows
		foreach ( $data as $row ) {
			// Sanitize each cell to prevent CSV injection
			$safe_row = array_map( array( $this, 'safe_csv_cell' ), $row );
			fputcsv( $output, $safe_row );
		}

		// Get buffer contents and clean buffer
		$content = ob_get_clean();

		// Generate filename
		$date_suffix = date( 'Y-m-d' );
		$type_suffix = $args['data_type'];
		$filename = "greenmetrics-{$type_suffix}-{$date_suffix}.csv";

		return array(
			'content' => $content,
			'filename' => $filename,
			'type' => 'text/csv',
		);
	}

	/**
	 * Format data as JSON.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Data to format.
	 * @param    array    $args    Export arguments.
	 * @return   array    Formatted data.
	 */
	private function format_as_json( $data, $args ) {
		// Generate filename
		$date_suffix = date( 'Y-m-d' );
		$type_suffix = $args['data_type'];
		$filename = "greenmetrics-{$type_suffix}-{$date_suffix}.json";

		// Format JSON with pretty print
		$content = wp_json_encode( $data, JSON_PRETTY_PRINT );

		return array(
			'content' => $content,
			'filename' => $filename,
			'type' => 'application/json',
		);
	}

	/**
	 * Format data as PDF.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Data to format.
	 * @param    array    $args    Export arguments.
	 * @return   array    Formatted data.
	 */
	private function format_as_pdf( $data, $args ) {
		if ( empty( $data ) ) {
			return array(
				'content' => '',
				'filename' => 'greenmetrics-export-empty.pdf',
				'type' => 'application/pdf',
			);
		}

		// Generate filename
		$date_suffix = date( 'Y-m-d' );
		$type_suffix = $args['data_type'];
		$filename = "greenmetrics-{$type_suffix}-{$date_suffix}.pdf";

		// Get site info
		$site_name = get_bloginfo( 'name' );
		$site_url = get_bloginfo( 'url' );
		$export_date = date( 'F j, Y' );
		$date_range = date( 'M j, Y', strtotime( $args['start_date'] ) ) . ' - ' . date( 'M j, Y', strtotime( $args['end_date'] ) );

		// Start building base HTML content (for both PDF and HTML fallback)
		$base_html = '<!DOCTYPE html>
		<html>
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
			<title>GreenMetrics Report</title>
			<style>
				body { font-family: Arial, sans-serif; color: #333; line-height: 1.5; }
				h1 { color: #4CAF50; margin-bottom: 5px; }
				h2 { color: #4CAF50; margin-top: 20px; margin-bottom: 10px; }
				.header { margin-bottom: 20px; }
				.site-info { color: #666; margin-bottom: 5px; }
				.date-info { color: #666; margin-bottom: 20px; }
				table { width: 100%; border-collapse: collapse; margin: 20px 0; }
				th { background-color: #f2f2f2; text-align: left; padding: 8px; border: 1px solid #ddd; }
				td { padding: 8px; border: 1px solid #ddd; }
				tr:nth-child(even) { background-color: #f9f9f9; }
				.footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
			</style>
		</head>
		<body>
			<div class="header">
				<h1>GreenMetrics Report</h1>
				<div class="site-info">Site: ' . esc_html( $site_name ) . ' (' . esc_html( $site_url ) . ')</div>
				<div class="date-info">Report generated on ' . esc_html( $export_date ) . '</div>
				<div class="date-info">Data range: ' . esc_html( $date_range ) . '</div>
			</div>';

		// Use the base HTML for PDF generation
		$html = $base_html;

		// Add page title if filtering by page
		if ( ! empty( $args['page_id'] ) && $args['page_id'] > 0 ) {
			$page_title = get_the_title( $args['page_id'] );
			if ( ! empty( $page_title ) ) {
				$html .= '<div class="page-info">Page: ' . esc_html( $page_title ) . '</div>';
			}
		}

		// Add data type info
		$html .= '<h2>' . ( $args['data_type'] === 'raw' ? 'Raw Metrics Data' : 'Aggregated Metrics Data' ) . '</h2>';

		// Create table
		$html .= '<table>';

		// Add headers
		if ( ! empty( $data ) ) {
			$html .= '<tr>';
			foreach ( array_keys( $data[0] ) as $header ) {
				// Make headers more readable
				$display_header = ucwords( str_replace( '_', ' ', $header ) );
				$html .= '<th>' . esc_html( $display_header ) . '</th>';
			}
			$html .= '</tr>';

			// Add data rows (limit to 100 rows for PDF readability)
			$row_count = 0;
			$max_rows = 100;

			foreach ( $data as $row ) {
				if ( $row_count >= $max_rows ) {
					break;
				}

				$html .= '<tr>';
				foreach ( $row as $cell ) {
					$html .= '<td>' . esc_html( $cell ) . '</td>';
				}
				$html .= '</tr>';

				$row_count++;
			}

			// Add note if data was truncated
			if ( count( $data ) > $max_rows ) {
				$html .= '<tr><td colspan="' . count( array_keys( $data[0] ) ) . '" style="text-align: center; font-style: italic;">Note: This report shows the first ' . $max_rows . ' rows of data. Export as CSV or JSON to get the complete dataset.</td></tr>';
			}
		}

		$html .= '</table>';

		// Add footer
		$html .= '<div class="footer">Generated by GreenMetrics WordPress Plugin</div>';
		$html .= '</body></html>';

		// Try to find any available PDF generation library
		$pdf_generated = false;
		$content = '';

		// Check for TCPDF (used by many WordPress plugins)
		if ( !$pdf_generated && class_exists( 'TCPDF' ) ) {
			try {
				// Create new PDF document
				$pdf = new \TCPDF( 'P', 'mm', 'A4', true, 'UTF-8', false );

				// Set document information
				$pdf->SetCreator( 'GreenMetrics' );
				$pdf->SetAuthor( 'GreenMetrics Plugin' );
				$pdf->SetTitle( 'GreenMetrics Report' );
				$pdf->SetSubject( 'GreenMetrics Metrics Report' );

				// Remove header/footer
				$pdf->setPrintHeader( false );
				$pdf->setPrintFooter( false );

				// Set margins
				$pdf->SetMargins( 15, 15, 15 );

				// Add a page
				$pdf->AddPage();

				// Write HTML content
				$pdf->writeHTML( $html, true, false, true, false, '' );

				// Get PDF content
				$content = $pdf->Output( '', 'S' );
				$pdf_generated = true;
			} catch ( \Exception $e ) {
				// If TCPDF fails, continue to next method
				greenmetrics_log( 'PDF Export: TCPDF error', $e->getMessage(), 'error' );
			}
		}

		// Check for DOMPDF
		if ( !$pdf_generated && ( class_exists( 'DOMPDF' ) || class_exists( '\Dompdf\Dompdf' ) ) ) {
			try {
				// Use dompdf to convert HTML to PDF
				if ( class_exists( '\Dompdf\Dompdf' ) ) {
					$dompdf = new \Dompdf\Dompdf();
					$dompdf->loadHtml( $html );
					$dompdf->setPaper( 'A4', 'portrait' );
					$dompdf->render();
					$content = $dompdf->output();
				} else {
					$dompdf = new \DOMPDF();
					$dompdf->load_html( $html );
					$dompdf->set_paper( 'A4', 'portrait' );
					$dompdf->render();
					$content = $dompdf->output();
				}
				$pdf_generated = true;
			} catch ( \Exception $e ) {
				// If DOMPDF fails, continue to next method
				greenmetrics_log( 'PDF Export: DOMPDF error', $e->getMessage(), 'error' );
			}
		}

		// Check for mPDF
		if ( !$pdf_generated && class_exists( '\Mpdf\Mpdf' ) ) {
			try {
				$mpdf = new \Mpdf\Mpdf();
				$mpdf->WriteHTML( $html );
				$content = $mpdf->Output( '', 'S' );
				$pdf_generated = true;
			} catch ( \Exception $e ) {
				// If mPDF fails, continue to next method
				greenmetrics_log( 'PDF Export: mPDF error', $e->getMessage(), 'error' );
			}
		}

		// Check for WooCommerce PDF Invoices & Packing Slips plugin's library
		if ( !$pdf_generated && function_exists( 'WPO_WCPDF' ) && class_exists( '\WPO\WC\PDF_Invoices\Vendor\Dompdf\Dompdf' ) ) {
			try {
				$dompdf = new \WPO\WC\PDF_Invoices\Vendor\Dompdf\Dompdf();
				$dompdf->loadHtml( $html );
				$dompdf->setPaper( 'A4', 'portrait' );
				$dompdf->render();
				$content = $dompdf->output();
				$pdf_generated = true;
			} catch ( \Exception $e ) {
				// If WooCommerce PDF library fails, continue to next method
				greenmetrics_log( 'PDF Export: WooCommerce PDF library error', $e->getMessage(), 'error' );
			}
		}

		// If no PDF library is available, use HTML with print-friendly styling
		if ( !$pdf_generated ) {
			// Create a new HTML document specifically for the HTML fallback
			// This ensures we don't modify the PDF version
			$html_fallback = $base_html;

			// Add page title if filtering by page (same as for PDF)
			if ( ! empty( $args['page_id'] ) && $args['page_id'] > 0 ) {
				$page_title = get_the_title( $args['page_id'] );
				if ( ! empty( $page_title ) ) {
					$html_fallback .= '<div class="page-info">Page: ' . esc_html( $page_title ) . '</div>';
				}
			}

			// Add data type info (same as for PDF)
			$html_fallback .= '<h2>' . ( $args['data_type'] === 'raw' ? 'Raw Metrics Data' : 'Aggregated Metrics Data' ) . '</h2>';

			// Create table (same as for PDF)
			$html_fallback .= '<table>';

			// Add headers
			if ( ! empty( $data ) ) {
				$html_fallback .= '<tr>';
				foreach ( array_keys( $data[0] ) as $header ) {
					// Make headers more readable
					$display_header = ucwords( str_replace( '_', ' ', $header ) );
					$html_fallback .= '<th>' . esc_html( $display_header ) . '</th>';
				}
				$html_fallback .= '</tr>';

				// Add data rows (limit to 100 rows for readability)
				$row_count = 0;
				$max_rows = 100;

				foreach ( $data as $row ) {
					if ( $row_count >= $max_rows ) {
						break;
					}

					$html_fallback .= '<tr>';
					foreach ( $row as $cell ) {
						$html_fallback .= '<td>' . esc_html( $cell ) . '</td>';
					}
					$html_fallback .= '</tr>';

					$row_count++;
				}

				// Add note if data was truncated
				if ( count( $data ) > $max_rows ) {
					$html_fallback .= '<tr><td colspan="' . count( array_keys( $data[0] ) ) . '" style="text-align: center; font-style: italic;">Note: This report shows the first ' . $max_rows . ' rows of data. Export as CSV or JSON to get the complete dataset.</td></tr>';
				}
			}

			$html_fallback .= '</table>';

			// Add footer
			$html_fallback .= '<div class="footer">Generated by GreenMetrics WordPress Plugin</div>';
			$html_fallback .= '</body></html>';

			// Add simple print instructions that will be hidden when printing
			$print_instructions = '
				<div class="print-instructions" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #4CAF50;">
					<strong>Instructions:</strong> Use your browser\'s print function (Ctrl+P / Cmd+P) to save this report as a PDF.
				</div>
			';

			// Add the print instructions HTML directly after the body tag
			$html_fallback = str_replace('<body>', '<body>' . $print_instructions, $html_fallback);

			// Add print-friendly CSS to the HTML fallback only
			$print_css = '
				<style>
					@media print {
						body { font-family: Arial, sans-serif; color: #000; }
						a { text-decoration: none; color: #000; }
						.header { margin-bottom: 20px; }
						.footer { margin-top: 20px; text-align: center; font-size: 12px; }
						table { width: 100%; border-collapse: collapse; margin: 20px 0; }
						th { background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; color-adjust: exact; text-align: left; padding: 8px; border: 1px solid #ddd; }
						td { padding: 8px; border: 1px solid #ddd; }
						tr:nth-child(even) { background-color: #f9f9f9 !important; -webkit-print-color-adjust: exact; color-adjust: exact; }
						.print-instructions { display: none; }
					}
				</style>
			';

			// Insert the print CSS into the HTML head
			$html_fallback = str_replace('</head>', $print_css . '</head>', $html_fallback);

			// Return as HTML
			$content = $html_fallback;
			$filename = str_replace( '.pdf', '.html', $filename );
			return array(
				'content' => $content,
				'filename' => $filename,
				'type' => 'text/html',
			);
		}

		return array(
			'content' => $content,
			'filename' => $filename,
			'type' => 'application/pdf',
		);
	}

	/**
	 * Stream file download to browser.
	 *
	 * @since    1.0.0
	 * @param    array    $file    File data.
	 */
	public function stream_download( $file ) {
		// Set headers for download
		header( 'Content-Type: ' . sanitize_text_field( $file['type'] ) );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $file['filename'] ) . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Output file content with proper escaping based on content type
		if ( strpos( $file['type'], 'text/html' ) !== false ) {
			// For HTML content, use wp_kses_post to prevent XSS
			echo wp_kses_post( $file['content'] );
		} elseif ( strpos( $file['type'], 'application/json' ) !== false ) {
			// For JSON, ensure it's properly encoded
			echo wp_json_encode( json_decode( $file['content'] ) );
		} else {
			// For CSV, PDF, and other non-HTML formats
			// These are binary or plain text formats that are downloaded as files, not rendered as HTML
			// We need to ensure we're sending the correct content type and handling the output properly

			// Validate that we have the expected content type for security
			$allowed_types = array(
				'text/csv',
				'application/pdf',
				'text/plain',
				'application/octet-stream',
				'application/vnd.ms-excel',
				'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
			);

			if ( ! in_array( $file['type'], $allowed_types, true ) ) {
				wp_die( esc_html__( 'Invalid file type for export.', 'greenmetrics' ) );
			}

			// For binary files like PDF, we need to ensure we're not corrupting the data
			// For text files like CSV, we need to ensure we're not introducing security issues
			if ( strpos( $file['type'], 'text/' ) === 0 ) {
				// For text-based formats like CSV, preserve structure while ensuring valid UTF-8
				echo wp_check_invalid_utf8( $file['content'] );
			} else {
				// For binary formats, output directly
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary data cannot be escaped
				echo $file['content'];
			}
		}
		exit;
	}
}
