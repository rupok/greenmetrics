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
	 * @return bool True if the table exists.
	 */
	public static function table_exists( string $table_name ): bool {
		if ( ! isset( self::$table_exists_cache[ $table_name ] ) ) {
			global $wpdb;
			// For SHOW TABLES LIKE, we need to properly escape the table name
			// but avoid incorrect quoting that prepare() might add
			$escaped_table_name = $wpdb->esc_like( $table_name );
			self::$table_exists_cache[ $table_name ] = (bool) $wpdb->get_var(
				$wpdb->prepare(
					'SHOW TABLES LIKE %s',
					$escaped_table_name
				)
			);
		}

		return self::$table_exists_cache[ $table_name ];
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
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Necessary direct query for schema check
		// Don't use prepare() for DESCRIBE as it quotes the table name incorrectly
		$results = $wpdb->get_results( 'DESCRIBE ' . $wpdb->_real_escape( $table_name ) );

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
	 * @return array Result of the dbDelta operation.
	 */
	public static function create_stats_table(): array {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'greenmetrics_stats';
		$charset_collate = $wpdb->get_charset_collate();

		greenmetrics_log( 'Creating greenmetrics_stats table' );

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

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$result = dbDelta( $sql );

		greenmetrics_log( 'Table creation result', $result );

		// Clear table existence cache
		if ( isset( self::$table_exists_cache[ $table_name ] ) ) {
			unset( self::$table_exists_cache[ $table_name ] );
		}

		// Force refresh column cache after table creation
		if ( self::table_exists( $table_name ) ) {
			self::get_table_columns( $table_name, true );
		}

		return $result;
	}
}
