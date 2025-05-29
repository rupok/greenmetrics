<?php
/**
 * Email Report History class.
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
 * Class for managing email report history.
 */
class GreenMetrics_Email_Report_History {
	/**
	 * The table name for email report history.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Singleton instance.
	 *
	 * @var GreenMetrics_Email_Report_History
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return GreenMetrics_Email_Report_History
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'greenmetrics_email_reports';

		// Create the table if it doesn't exist
		$this->create_table();
	}

	/**
	 * Create the email report history table if it doesn't exist.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Check if table already exists using direct query to avoid DESCRIBE IF error
		$table_exists = (bool) $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like( $this->table_name )
			)
		);

		if ( $table_exists ) {
			return true;
		}

		greenmetrics_log( 'Creating email report history table' );

		// Prepare the SQL statement
		$sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			report_type varchar(20) NOT NULL,
			subject varchar(255) NOT NULL,
			recipients text NOT NULL,
			content longtext NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'sent',
			sent_at datetime DEFAULT CURRENT_TIMESTAMP,
			is_test tinyint(1) NOT NULL DEFAULT 0,
			open_count int(11) NOT NULL DEFAULT 0,
			click_count int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY report_type (report_type),
			KEY status (status),
			KEY sent_at (sent_at)
		) $charset_collate;";

		try {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$result = dbDelta( $sql );

			// Check for MySQL errors
			if ( ! empty( $wpdb->last_error ) ) {
				$error_message = 'MySQL error during email report history table creation: ' . $wpdb->last_error;
				greenmetrics_log( $error_message, $wpdb->last_query, 'error' );
				return GreenMetrics_Error_Handler::database_error( $error_message, $wpdb->last_error, $wpdb->last_query );
			}

			greenmetrics_log( 'Email report history table creation result', $result );
			return true;
		} catch ( \Exception $e ) {
			$error_message = 'Exception during email report history table creation: ' . $e->getMessage();
			greenmetrics_log( $error_message, $e->getTraceAsString(), 'error' );
			return GreenMetrics_Error_Handler::database_error( $error_message );
		}
	}

	/**
	 * Record a sent email report.
	 *
	 * @param string $report_type The report type (daily, weekly, monthly).
	 * @param string $subject The email subject.
	 * @param string $recipients The email recipients.
	 * @param string $content The email content.
	 * @param string $status The email status (sent, failed).
	 * @param bool   $is_test Whether this is a test email.
	 * @return int|false The ID of the inserted record, or false on failure.
	 */
	public function record_email( $report_type, $subject, $recipients, $content, $status = 'sent', $is_test = false ) {
		global $wpdb;

		// Ensure the table exists using direct query to avoid DESCRIBE IF error
		$table_exists = (bool) $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like( $this->table_name )
			)
		);

		if ( ! $table_exists ) {
			$this->create_table();
		}

		// Insert the record
		$result = $wpdb->insert(
			$this->table_name,
			array(
				'report_type' => $report_type,
				'subject'     => $subject,
				'recipients'  => $recipients,
				'content'     => $content,
				'status'      => $status,
				'is_test'     => $is_test ? 1 : 0,
				'sent_at'     => current_time( 'mysql' ),
			),
			array(
				'%s', // report_type
				'%s', // subject
				'%s', // recipients
				'%s', // content
				'%s', // status
				'%d', // is_test
				'%s', // sent_at
			)
		);

		if ( false === $result ) {
			greenmetrics_log( 'Failed to record email report', array(
				'error'       => $wpdb->last_error,
				'report_type' => $report_type,
				'subject'     => $subject,
				'recipients'  => $recipients,
			), 'error' );
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get email report history.
	 *
	 * @param array $args Query arguments.
	 * @return array The email report history.
	 */
	public function get_reports( $args = array() ) {
		global $wpdb;

		// Default arguments
		$defaults = array(
			'per_page'    => 10,
			'page'        => 1,
			'orderby'     => 'sent_at',
			'order'       => 'DESC',
			'report_type' => '',
			'status'      => '',
			'is_test'     => null,
			'search'      => '',
		);

		$args = wp_parse_args( $args, $defaults );

		// Sanitize table name
		$table_name = esc_sql($this->table_name);
		// Wrap table name in backticks and suppress PHPCS warning
		$table_name = "`{$table_name}`";

		// Build the query
		$sql = "SELECT * FROM {$table_name} WHERE 1=1";

		// Add filters
		if ( ! empty( $args['report_type'] ) ) {
			$sql .= $wpdb->prepare( " AND report_type = %s", $args['report_type'] );
		}

		if ( ! empty( $args['status'] ) ) {
			$sql .= $wpdb->prepare( " AND status = %s", $args['status'] );
		}

		if ( null !== $args['is_test'] ) {
			$sql .= $wpdb->prepare( " AND is_test = %d", $args['is_test'] ? 1 : 0 );
		}

		if ( ! empty( $args['search'] ) ) {
			$search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$sql .= $wpdb->prepare( " AND (subject LIKE %s OR recipients LIKE %s)", $search, $search );
		}

		// Whitelist the sortable columns to prevent SQL injection
		$allowed_cols = array( 'sent_at', 'status', 'report_type' );
		$order_col = in_array( $args['orderby'], $allowed_cols, true )
		              ? $args['orderby']
		              : 'sent_at';

		// Normalize the order direction
		$order_dir = strtoupper( $args['order'] ) === 'ASC'
		               ? 'ASC'
		               : 'DESC';

		// Append the safe ORDER BY
		$sql .= " ORDER BY {$order_col} {$order_dir}";

		// Add pagination
		$offset = ( $args['page'] - 1 ) * $args['per_page'];
		$sql .= $wpdb->prepare( " LIMIT %d, %d", $offset, $args['per_page'] );

		// Get the results
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Identifier safe via esc_sql()
		$results = $wpdb->get_results( $sql, ARRAY_A );

		return $results;
	}

	/**
	 * Get the total number of email reports.
	 *
	 * @param array $args Query arguments.
	 * @return int The total number of email reports.
	 */
	public function get_total_reports( $args = array() ) {
		global $wpdb;

		// Default arguments
		$defaults = array(
			'report_type' => '',
			'status'      => '',
			'is_test'     => null,
			'search'      => '',
		);

		$args = wp_parse_args( $args, $defaults );

		// Sanitize table name
		$table_name = esc_sql($this->table_name);
		// Wrap table name in backticks and suppress PHPCS warning
		$table_name = "`{$table_name}`";

		// Build the query
		$sql = "SELECT COUNT(*) FROM {$table_name} WHERE 1=1";

		// Add filters
		if ( ! empty( $args['report_type'] ) ) {
			$sql .= $wpdb->prepare( " AND report_type = %s", $args['report_type'] );
		}

		if ( ! empty( $args['status'] ) ) {
			$sql .= $wpdb->prepare( " AND status = %s", $args['status'] );
		}

		if ( null !== $args['is_test'] ) {
			$sql .= $wpdb->prepare( " AND is_test = %d", $args['is_test'] ? 1 : 0 );
		}

		if ( ! empty( $args['search'] ) ) {
			$search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$sql .= $wpdb->prepare( " AND (subject LIKE %s OR recipients LIKE %s)", $search, $search );
		}

		// Get the count
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Identifier safe via esc_sql()
		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Get a single email report by ID.
	 *
	 * @param int $id The report ID.
	 * @return array|null The email report, or null if not found.
	 */
	public function get_report( $id ) {
		global $wpdb;

		// Sanitize table name
		$table_name = esc_sql($this->table_name);
		// Wrap table name in backticks
		$table_name = "`{$table_name}`";
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name safe via esc_sql()
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$id
			),
			ARRAY_A
		);
	}

	/**
	 * Delete old email reports.
	 *
	 * @param int $days_to_keep The number of days to keep reports.
	 * @return int The number of deleted reports.
	 */
	public function prune_old_reports( $days_to_keep = 30 ) {
		global $wpdb;

		$date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_to_keep} days" ) );

		// Sanitize table name
		$table_name = esc_sql($this->table_name);
		// Wrap table name in backticks
		$table_name = "`{$table_name}`";
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name safe via esc_sql()
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_name} WHERE sent_at < %s",
				$date
			)
		);
		greenmetrics_log( "Pruned {$deleted} old email reports" );

		return $deleted;
	}
}
