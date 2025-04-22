<?php
namespace GreenMetrics;

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
	 * Check if a table exists, using a prepared statement and caching.
	 *
	 * @param string $table_name Full table name including prefix.
	 * @return bool True if the table exists.
	 */
	public static function table_exists( string $table_name ): bool {
		if ( ! isset( self::$table_exists_cache[ $table_name ] ) ) {
			global $wpdb;
			self::$table_exists_cache[ $table_name ] = (bool) $wpdb->get_var(
				$wpdb->prepare(
					"SHOW TABLES LIKE %s",
					$table_name
					)
				);
		}

		return self::$table_exists_cache[ $table_name ];
	}

	/**
	 * Get the column names for a table, caching the result.
	 *
	 * @param string $table_name Full table name including prefix.
	 * @return string[] List of column names.
	 */
	public static function get_table_columns( string $table_name ): array {
		if ( ! isset( self::$table_columns_cache[ $table_name ] ) ) {
			global $wpdb;
			$escaped = esc_sql( $table_name );
			$results = $wpdb->get_results( "DESCRIBE $escaped" );
			$columns = array();
			foreach ( $results as $col ) {
				$columns[] = $col->Field;
			}
			self::$table_columns_cache[ $table_name ] = $columns;
		}

		return self::$table_columns_cache[ $table_name ];
	}
} 