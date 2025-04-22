<?php
/**
 * Tests for the GreenMetrics_DB_Helper class.
 *
 * @package GreenMetrics
 */

// Load the plugin bootstrap (adjust path as needed).
require_once dirname( __FILE__ ) . '/../greenmetrics.php';

class Tests_DB_Helper extends WP_UnitTestCase {
	/**
	 * Test that table_exists() returns false for a non-existent table.
	 */
	public function test_table_exists_false() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'nonexistent_table_' . uniqid();
		$this->assertFalse( \GreenMetrics\GreenMetrics_DB_Helper::table_exists( $table_name ) );
	}

	/**
	 * Test that table_exists() returns true after creating a table.
	 */
	public function test_table_exists_true() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'test_table_exists_' . uniqid();
		$wpdb->query( "CREATE TABLE {$table_name} (id bigint(20) NOT NULL PRIMARY KEY)" );

		$this->assertTrue( \GreenMetrics\GreenMetrics_DB_Helper::table_exists( $table_name ) );

		// Clean up
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
	}

	/**
	 * Test that get_table_columns() returns the correct column list.
	 */
	public function test_get_table_columns() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'test_table_columns_' . uniqid();
		$wpdb->query( "CREATE TABLE {$table_name} (col1 int(11), col2 varchar(255))" );

		$columns = \GreenMetrics\GreenMetrics_DB_Helper::get_table_columns( $table_name );
		$this->assertEquals( array( 'col1', 'col2' ), $columns );

		// Clean up
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
	}
} 