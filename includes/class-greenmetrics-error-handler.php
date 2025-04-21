<?php
/**
 * Standardized error handling for the GreenMetrics plugin.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */

namespace GreenMetrics;

/**
 * The error handler for the plugin.
 * This class provides standardized error handling functions to be used throughout the plugin.
 */
class GreenMetrics_Error_Handler {
    /**
     * Create a standardized error object.
     *
     * @param string $code Error code.
     * @param string $message Error message.
     * @param array $data Additional error data.
     * @param int $status HTTP status code (for REST API responses).
     * @return \WP_Error|array Error object.
     */
    public static function create_error($code, $message, $data = array(), $status = 400) {
        // Log the error
        greenmetrics_log($message, $data, 'error');
        
        // Create WP_Error object for REST API contexts
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return new \WP_Error($code, $message, array_merge($data, array('status' => $status)));
        } 
        
        // For non-REST contexts, return a standard error array
        return array(
            'success' => false,
            'code' => $code,
            'message' => $message,
            'data' => $data
        );
    }
    
    /**
     * Check if a value is an error.
     *
     * @param mixed $value The value to check.
     * @return bool Whether the value is an error.
     */
    public static function is_error($value) {
        if ($value instanceof \WP_Error) {
            return true;
        }
        
        if (is_array($value) && isset($value['success']) && $value['success'] === false) {
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
    public static function get_error_message($error) {
        if ($error instanceof \WP_Error) {
            return $error->get_error_message();
        }
        
        if (is_array($error) && isset($error['message'])) {
            return $error['message'];
        }
        
        return 'Unknown error';
    }
    
    /**
     * Handle an exception by logging it and returning a standardized error.
     *
     * @param \Exception $e The exception.
     * @param string $code Error code.
     * @param string $message Error message.
     * @param int $status HTTP status code.
     * @return \WP_Error|array Error object.
     */
    public static function handle_exception($e, $code = 'exception', $message = null, $status = 500) {
        $error_message = $message ?? 'Exception: ' . $e->getMessage();
        
        // Log the exception details
        greenmetrics_log($error_message, array(
            'exception_message' => $e->getMessage(),
            'exception_trace' => $e->getTraceAsString()
        ), 'error');
        
        // Create and return the error
        return self::create_error($code, $error_message, array(), $status);
    }
    
    /**
     * Return success response in a standardized format.
     *
     * @param mixed $data Response data.
     * @return array Success response.
     */
    public static function success($data = null) {
        $response = array('success' => true);
        
        if ($data !== null) {
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
    public static function process_error($error) {
        // For REST API requests, return WP_Error
        if (defined('REST_REQUEST') && REST_REQUEST) {
            if ($error instanceof \WP_Error) {
                return $error;
            }
            
            return new \WP_Error(
                isset($error['code']) ? $error['code'] : 'error',
                isset($error['message']) ? $error['message'] : 'Unknown error',
                isset($error['data']) ? $error['data'] : array()
            );
        }
        
        // For AJAX requests, send JSON error response
        if (wp_doing_ajax()) {
            $message = self::get_error_message($error);
            wp_send_json_error($message);
            exit;
        }
        
        // For normal requests, return the error
        return $error;
    }
} 