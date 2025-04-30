<?php
/**
 * Standardized error handling for the GreenMetrics plugin.
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
 * The error handler for the plugin.
 * This class provides standardized error handling functions to be used throughout the plugin.
 *
 * Error handling guidelines:
 * 1. Always use this class for error handling instead of direct error reporting
 * 2. Use appropriate error codes that follow the naming convention: {context}_{error_type}
 * 3. Provide descriptive error messages that are user-friendly
 * 4. Include relevant data for debugging when appropriate
 * 5. Use the appropriate method based on the context (REST, AJAX, admin, etc.)
 */
class GreenMetrics_Error_Handler {
	/**
	 * Create a standardized error object.
	 *
	 * @param string $code Error code.
	 * @param string $message Error message.
	 * @param array  $data Additional error data.
	 * @param int    $status HTTP status code (for REST API responses).
	 * @return \WP_Error|array Error object.
	 */
	public static function create_error( $code, $message, $data = array(), $status = 400 ) {
		// Log the error
		greenmetrics_log( $message, $data, 'error' );

		// Create WP_Error object for REST API contexts
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return new \WP_Error( $code, $message, array_merge( $data, array( 'status' => $status ) ) );
		}

		// For non-REST contexts, return a standard error array
		return array(
			'success' => false,
			'code'    => $code,
			'message' => $message,
			'data'    => $data,
		);
	}

	/**
	 * Check if a value is an error.
	 *
	 * @param mixed $value The value to check.
	 * @return bool Whether the value is an error.
	 */
	public static function is_error( $value ) {
		if ( $value instanceof \WP_Error ) {
			return true;
		}

		if ( is_array( $value ) && isset( $value['success'] ) && false === $value['success'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Get error message from an error.
	 *
	 * @param \WP_Error|array $error The error.
	 * @return string The error message.
	 */
	public static function get_error_message( $error ) {
		if ( $error instanceof \WP_Error ) {
			return $error->get_error_message();
		}

		if ( is_array( $error ) && isset( $error['message'] ) ) {
			return $error['message'];
		}

		return 'Unknown error';
	}

	/**
	 * Handle an exception by logging it and returning a standardized error.
	 *
	 * @param \Exception $e The exception.
	 * @param string     $code Error code.
	 * @param string     $message Error message.
	 * @param int        $status HTTP status code.
	 * @return \WP_Error|array Error object.
	 */
	public static function handle_exception( $e, $code = 'exception', $message = null, $status = 500 ) {
		$error_message = $message ?? 'Exception: ' . $e->getMessage();

		// Log the exception details
		greenmetrics_log(
			$error_message,
			array(
				'exception_message' => $e->getMessage(),
				'exception_trace'   => $e->getTraceAsString(),
			),
			'error'
		);

		// Create and return the error
		return self::create_error( $code, $error_message, array(), $status );
	}

	/**
	 * Return success response in a standardized format.
	 *
	 * @param mixed $data Response data.
	 * @return array Success response.
	 */
	public static function success( $data = null ) {
		$response = array( 'success' => true );

		if ( $data !== null ) {
			$response['data'] = $data;
		}

		return $response;
	}

	/**
	 * Process the error for the appropriate context.
	 * For REST, returns a WP_Error object.
	 * For AJAX, sends JSON error response.
	 * For normal requests, returns the error array.
	 *
	 * @param \WP_Error|array $error The error.
	 * @return mixed Processed error.
	 */
	public static function process_error( $error ) {
		// For REST API requests, return WP_Error
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			if ( $error instanceof \WP_Error ) {
				return $error;
			}

			return new \WP_Error(
				isset( $error['code'] ) ? $error['code'] : 'error',
				isset( $error['message'] ) ? $error['message'] : 'Unknown error',
				isset( $error['data'] ) ? $error['data'] : array()
			);
		}

		// For AJAX requests, send JSON error response
		if ( wp_doing_ajax() ) {
			$message = self::get_error_message( $error );
			wp_send_json_error( $message );
			exit;
		}

		// For normal requests, return the error
		return $error;
	}

	/**
	 * Display an admin notice for an error.
	 *
	 * @param string $message The error message to display.
	 * @param string $type The notice type (error, warning, success, info).
	 * @param bool   $dismissible Whether the notice should be dismissible.
	 * @return void
	 */
	public static function admin_notice( $message, $type = 'error', $dismissible = true ) {
		add_action(
			'admin_notices',
			function () use ( $message, $type, $dismissible ) {
				$class = 'notice notice-' . $type;
				if ( $dismissible ) {
					$class .= ' is-dismissible';
				}
				echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
			}
		);
	}

	/**
	 * Create a database error with standardized format.
	 *
	 * @param string $message The error message.
	 * @param mixed  $db_error The database error (usually $wpdb->last_error).
	 * @param string $query The query that caused the error (usually $wpdb->last_query).
	 * @param int    $status HTTP status code.
	 * @return \WP_Error|array Error object.
	 */
	public static function database_error( $message, $db_error = null, $query = null, $status = 500 ) {
		global $wpdb;

		$data = array();

		if ( null === $db_error && isset( $wpdb ) ) {
			$db_error = $wpdb->last_error;
		}

		if ( null === $query && isset( $wpdb ) ) {
			$query = $wpdb->last_query;
		}

		if ( $db_error ) {
			$data['db_error'] = $db_error;
		}

		if ( $query ) {
			$data['query'] = $query;
		}

		return self::create_error( 'database_error', $message, $data, $status );
	}

	/**
	 * Create a validation error with standardized format.
	 *
	 * @param string $message The error message.
	 * @param array  $validation_errors Array of validation errors.
	 * @param int    $status HTTP status code.
	 * @return \WP_Error|array Error object.
	 */
	public static function validation_error( $message, $validation_errors = array(), $status = 400 ) {
		return self::create_error( 'validation_error', $message, array( 'validation_errors' => $validation_errors ), $status );
	}

	/**
	 * Create a permission error with standardized format.
	 *
	 * @param string $message The error message.
	 * @param int    $status HTTP status code.
	 * @return \WP_Error|array Error object.
	 */
	public static function permission_error( $message = 'You do not have permission to perform this action.', $status = 403 ) {
		return self::create_error( 'permission_error', $message, array(), $status );
	}

	/**
	 * Create a not found error with standardized format.
	 *
	 * @param string $message The error message.
	 * @param string $resource The resource that was not found.
	 * @param int    $status HTTP status code.
	 * @return \WP_Error|array Error object.
	 */
	public static function not_found_error( $message = 'Resource not found.', $resource = null, $status = 404 ) {
		$data = array();
		if ( $resource ) {
			$data['resource'] = $resource;
		}

		return self::create_error( 'not_found', $message, $data, $status );
	}
}
