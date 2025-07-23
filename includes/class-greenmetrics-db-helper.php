<?php
namespace GreenMetrics;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Helper class for database schema checks and caching.
 *
 * @package GreenMetrics
 */
class GreenMetrics_DB_Helper {
	/**
	 * Cached table existence results.
	 *
	 * @var array<string,bool>
	 */
	private static $table_exists_cache = array();

	/**
	 * Cached column lists for tables.
	 *
	 * @var array<string,string[]>
	 */
	private static $table_columns_cache = array();

	/**
	 * Option name for storing column information.
	 *
	 * @var string
	 */
	private static $columns_option_name = 'greenmetrics_table_columns';

	/**
	 * Check if a table exists, using a prepared statement and caching.
	 *
	 * @param string $table_name Full table name including prefix.
	 * @param bool   $force_db_check Whether to force a fresh check from the database.
	 * @return bool True if the table exists.
	 */
	public static function table_exists( string $table_name, bool $force_db_check = false ): bool {
		// First check memory cache
		if ( ! $force_db_check && isset( self::$table_exists_cache[ $table_name ] ) ) {
			return self::$table_exists_cache[ $table_name ];
		}

		// Then check transient cache
		$cache_key = 'greenmetrics_table_exists_' . $table_name;
		$cached_result = false;

		if ( ! $force_db_check ) {
			$cached_result = get_transient( $cache_key );
			if ( $cached_result !== false ) {
				// Update memory cache
				self::$table_exists_cache[ $table_name ] = (bool) $cached_result;
				return (bool) $cached_result;
			}
		}

		// If no cache or forced refresh, query the database
		global $wpdb;
		// For SHOW TABLES LIKE, we need to properly escape the table name
		// but avoid incorrect quoting that prepare() might add
		$escaped_table_name = $wpdb->esc_like( $table_name );
		$exists = (bool) $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$escaped_table_name
			)
		);

		// Update both caches
		self::$table_exists_cache[ $table_name ] = $exists;
		set_transient( $cache_key, $exists, WEEK_IN_SECONDS ); // Cache for a week

		return $exists;
	}

	/**
	 * Get the column names for a table, using cached values when possible.
	 *
	 * @param string $table_name Full table name including prefix.
	 * @param bool   $force_db_check Whether to force a fresh check from the database.
	 * @return string[] List of column names.
	 */
	public static function get_table_columns( string $table_name, bool $force_db_check = false ): array {
		// First try memory cache
		$cache_key = 'greenmetrics_table_columns_' . $table_name;
		$cached_columns = wp_cache_get( $cache_key, 'greenmetrics' );
		if ( ! $force_db_check && $cached_columns !== false ) {
			return $cached_columns;
		}

		// Then try persistent cache in wp_options
		if ( ! $force_db_check ) {
			$cached_columns = self::get_cached_table_columns( $table_name );
			if ( $cached_columns !== false ) {
				self::$table_columns_cache[ $table_name ] = $cached_columns;
				wp_cache_set( $cache_key, $cached_columns, 'greenmetrics' );
				return $cached_columns;
			}
		}

		// If no cache or forced refresh, query the database
		global $wpdb;

		// First check if the table exists to avoid "DESCRIBE IF" error
		$table_exists = (bool) $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like( $table_name )
			)
		);

		if (!$table_exists) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Necessary direct query for schema check
		// Use esc_sql to properly escape table name for SQL query
		$table_name_escaped = esc_sql($table_name);
		// For table names, we need to use direct interpolation with esc_sql
		// since $wpdb->prepare() doesn't have a placeholder for identifiers
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name identifiers cannot use placeholders, safely escaped with esc_sql()
		$results = $wpdb->get_results( "DESCRIBE `{$table_name_escaped}`" );

		if ( ! $results ) {
			// Return empty array if no results (e.g., table doesn't exist)
			return array();
		}

		$columns = array();
		foreach ( $results as $col ) {
			$columns[] = $col->Field;
		}

		// Update both caches
		self::$table_columns_cache[ $table_name ] = $columns;
		self::update_cached_table_columns( $table_name, $columns );
		wp_cache_set( $cache_key, $columns, 'greenmetrics' );

		return $columns;
	}

	/**
	 * Get cached table columns from wp_options.
	 *
	 * @param string $table_name Full table name including prefix.
	 * @return array|false Cached column names or false if not in cache.
	 */
	public static function get_cached_table_columns( string $table_name ) {
		$columns_cache = get_option( self::$columns_option_name, array() );

		if ( isset( $columns_cache[ $table_name ] ) ) {
			return $columns_cache[ $table_name ];
		}

		return false;
	}

	/**
	 * Update cached table columns in wp_options.
	 *
	 * @param string $table_name Full table name including prefix.
	 * @param array  $columns List of column names.
	 * @return bool True if the option was updated.
	 */
	public static function update_cached_table_columns( string $table_name, array $columns ): bool {
		$columns_cache                = get_option( self::$columns_option_name, array() );
		$columns_cache[ $table_name ] = $columns;

		return update_option( self::$columns_option_name, $columns_cache );
	}

	/**
	 * Create the greenmetrics_stats table if it doesn't exist.
	 *
	 * @param bool $show_admin_notice Whether to show an admin notice on failure.
	 * @return array|WP_Error Result of the dbDelta operation or WP_Error on failure.
	 */
	public static function create_stats_table( $show_admin_notice = false ) {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'greenmetrics_stats';
		$charset_collate = $wpdb->get_charset_collate();

		greenmetrics_log( 'Creating greenmetrics_stats table' );

		// Check for necessary MySQL privileges
		$has_create_privilege = false;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Necessary direct query for privilege check
		$privileges = $wpdb->get_results( "SHOW GRANTS FOR CURRENT_USER()" );
		if ( $privileges ) {
			foreach ( $privileges as $privilege ) {
				$grant = reset( get_object_vars( $privilege ) );
				if ( strpos( $grant, 'ALL PRIVILEGES' ) !== false ||
					 strpos( $grant, 'CREATE' ) !== false ) {
					$has_create_privilege = true;
					break;
				}
			}
		}

		if ( ! $has_create_privilege ) {
			$error_message = 'Database user lacks CREATE privilege required to create tables';
			greenmetrics_log( $error_message, null, 'error' );

			if ( $show_admin_notice ) {
				GreenMetrics_Error_Handler::admin_notice(
					__( 'GreenMetrics: Database user lacks necessary privileges to create tables. Please contact your hosting provider.', 'greenmetrics' ),
					'error',
					false
				);
			}

			return GreenMetrics_Error_Handler::database_error( $error_message );
		}

		// Prepare the SQL statement
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            page_id bigint(20) NOT NULL,
            data_transfer bigint(20) NOT NULL,
            load_time float NOT NULL,
            requests int(11) NOT NULL,
            carbon_footprint float NOT NULL,
            energy_consumption float NOT NULL,
            performance_score float NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY page_id (page_id)
        ) $charset_collate;";

		// Capture any PHP errors that might occur during table creation (only in debug mode)
		$previous_error_reporting = null;
		$previous_error_handler = null;

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting -- Debug mode only, used for enhanced error logging during table creation
			$previous_error_reporting = error_reporting();
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting -- Debug mode only, used for enhanced error logging during table creation
			error_reporting( E_ALL );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Debug mode only, used for enhanced error logging during table creation
			$previous_error_handler = set_error_handler(
				function ( $errno, $errstr, $errfile, $errline ) {
					greenmetrics_log( "PHP Error during table creation: $errstr", array( 'file' => $errfile, 'line' => $errline ), 'error' );
					return false; // Let the standard error handler continue
				}
			);
		}

		try {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$result = dbDelta( $sql );

			// Check for MySQL errors
			if ( ! empty( $wpdb->last_error ) ) {
				$error_message = 'MySQL error during table creation: ' . $wpdb->last_error;
				greenmetrics_log( $error_message, $wpdb->last_query, 'error' );

				if ( $show_admin_notice ) {
					GreenMetrics_Error_Handler::admin_notice(
						sprintf(
							/* translators: %s: Database error message */
							__( 'GreenMetrics: Failed to create database tables. Error: %s', 'greenmetrics' ),
							esc_html( $wpdb->last_error )
						),
						'error',
						false
					);
				}

				return GreenMetrics_Error_Handler::database_error( $error_message, $wpdb->last_error, $wpdb->last_query );
			}

			greenmetrics_log( 'Table creation result', $result );

			// Clear table existence caches
			if ( isset( self::$table_exists_cache[ $table_name ] ) ) {
				unset( self::$table_exists_cache[ $table_name ] );
			}
			delete_transient( 'greenmetrics_table_exists_' . $table_name );

			// Verify table was actually created
			if ( ! self::table_exists( $table_name, true ) ) {
				$error_message = 'Table creation failed: Table does not exist after dbDelta';
				greenmetrics_log( $error_message, null, 'error' );

				if ( $show_admin_notice ) {
					GreenMetrics_Error_Handler::admin_notice(
						__( 'GreenMetrics: Failed to create database tables. Please check server error logs for details.', 'greenmetrics' ),
						'error',
						false
					);
				}

				return GreenMetrics_Error_Handler::database_error( $error_message );
			}

			// Force refresh column cache after table creation
			self::get_table_columns( $table_name, true );

			// Show success notice if requested
			if ( $show_admin_notice ) {
				GreenMetrics_Error_Handler::admin_notice(
					__( 'GreenMetrics: Database tables created successfully.', 'greenmetrics' ),
					'success',
					true
				);
			}

			return $result;
		} catch ( \Exception $e ) {
			$error_message = 'Exception during table creation: ' . $e->getMessage();
			greenmetrics_log( $error_message, $e->getTraceAsString(), 'error' );

			if ( $show_admin_notice ) {
				GreenMetrics_Error_Handler::admin_notice(
					sprintf(
						/* translators: %s: Exception message */
						__( 'GreenMetrics: Exception during database table creation: %s', 'greenmetrics' ),
						esc_html( $e->getMessage() )
					),
					'error',
					false
				);
			}

			return GreenMetrics_Error_Handler::handle_exception( $e, 'database_creation_exception' );
		} finally {
			// Restore previous error handler and reporting level (only if they were set)
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				if ( $previous_error_handler ) {
					restore_error_handler();
				}
				if ( $previous_error_reporting !== null ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting -- Debug mode only, restoring previous error reporting level
					error_reporting( $previous_error_reporting );
				}
			}
		}
	}
}
