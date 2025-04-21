<?php
/**
 * Handles version upgrades for the plugin.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */

namespace GreenMetrics;

/**
 * Handles version upgrades for the plugin.
 * 
 * This class compares the stored plugin version with the current version
 * and runs appropriate upgrade routines if needed.
 */
class GreenMetrics_Upgrader {
    /**
     * The option name where we store the plugin version.
     */
    const VERSION_OPTION = 'greenmetrics_version';
    
    /**
     * Check if an upgrade is needed and perform it if necessary.
     */
    public static function check_for_upgrades() {
        // Get the stored version
        $stored_version = get_option(self::VERSION_OPTION, '0.0.0');
        
        // If versions match, no upgrade needed
        if (version_compare($stored_version, GREENMETRICS_VERSION, '=')) {
            greenmetrics_log('No upgrade needed, version matches', GREENMETRICS_VERSION);
            return;
        }
        
        greenmetrics_log('Upgrade needed', array(
            'stored_version' => $stored_version,
            'current_version' => GREENMETRICS_VERSION
        ));
        
        // Perform upgrades based on stored version
        self::perform_upgrades($stored_version);
        
        // Update the stored version to current
        update_option(self::VERSION_OPTION, GREENMETRICS_VERSION);
        greenmetrics_log('Version updated in database', GREENMETRICS_VERSION);
    }
    
    /**
     * Perform necessary upgrades based on the stored version.
     *
     * @param string $from_version The version we're upgrading from.
     */
    private static function perform_upgrades($from_version) {
        // Run upgrade routines in sequence
        if (version_compare($from_version, '1.0.0', '<')) {
            self::upgrade_to_1_0_0();
        }
        
        // Example of future upgrade paths
        if (version_compare($from_version, '1.1.0', '<') && version_compare(GREENMETRICS_VERSION, '1.1.0', '>=')) {
            self::upgrade_to_1_1_0();
        }
        
        // Run database schema check regardless of version
        self::check_database_schema();
    }
    
    /**
     * Upgrade to version 1.0.0
     */
    private static function upgrade_to_1_0_0() {
        greenmetrics_log('Upgrading to 1.0.0');
        
        // This is the initial version, so we'll just ensure settings are set
        $settings = get_option('greenmetrics_settings', array());
        
        // Add any missing settings with defaults
        $default_settings = array(
            'carbon_intensity' => 0.475,         // Default carbon intensity factor (kg CO2/kWh)
            'energy_per_byte' => 0.000000000072, // Default energy per byte (kWh/byte)
            'tracking_enabled' => 1,             // Tracking enabled by default
            'enable_badge' => 0,                 // Badge disabled by default
            'badge_position' => 'bottom-right',  // Default badge position
            'badge_theme' => 'light',            // Default badge theme
            'badge_size' => 'medium'             // Default badge size
        );
        
        // Only add missing settings, don't overwrite existing ones
        $updated_settings = array_merge($default_settings, $settings);
        
        // Update settings if they changed
        if ($updated_settings !== $settings) {
            update_option('greenmetrics_settings', $updated_settings);
            greenmetrics_log('Settings updated during upgrade', $updated_settings);
        }
    }
    
    /**
     * Upgrade to version 1.1.0
     * Example function for future upgrades
     */
    private static function upgrade_to_1_1_0() {
        greenmetrics_log('Upgrading to 1.1.0');
        
        // Example: Add a new setting for 1.1.0
        $settings = get_option('greenmetrics_settings', array());
        
        // Only set if not already set
        if (!isset($settings['new_setting_for_1_1_0'])) {
            $settings['new_setting_for_1_1_0'] = 'default_value';
            update_option('greenmetrics_settings', $settings);
            greenmetrics_log('Added new setting for 1.1.0', $settings);
        }
    }
    
    /**
     * Check and update database schema if needed.
     */
    private static function check_database_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'greenmetrics_stats';
        
        greenmetrics_log('Checking database schema');
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
        
        if (!$table_exists) {
            greenmetrics_log('Database table missing, creating it', $table_name);
            self::create_database_tables();
            return;
        }
        
        // Check for missing columns - can't use placeholder for table name in DESCRIBE
        $table_name_escaped = esc_sql($table_name);
        $columns = $wpdb->get_results("DESCRIBE $table_name_escaped");
        $column_names = array_map(function($col) { return $col->Field; }, $columns);
        
        // Define the required columns
        $required_columns = array(
            'id', 'page_id', 'data_transfer', 'load_time', 'requests', 
            'carbon_footprint', 'energy_consumption', 'performance_score', 'created_at'
        );
        
        // Check for missing columns
        $missing_columns = array_diff($required_columns, $column_names);
        
        if (!empty($missing_columns)) {
            greenmetrics_log('Missing database columns', $missing_columns);
            self::add_missing_columns($table_name, $missing_columns);
        }
    }
    
    /**
     * Create the database tables.
     */
    private static function create_database_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'greenmetrics_stats';
        
        $charset_collate = $wpdb->get_charset_collate();

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

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);
        
        greenmetrics_log('Database tables created', $result);
    }
    
    /**
     * Add missing columns to the database table.
     *
     * @param string $table_name The table name.
     * @param array $missing_columns The missing columns.
     */
    private static function add_missing_columns($table_name, $missing_columns) {
        global $wpdb;
        
        foreach ($missing_columns as $column) {
            $sql = '';
            
            switch ($column) {
                case 'id':
                    $sql = "ALTER TABLE $table_name ADD COLUMN id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY";
                    break;
                case 'page_id':
                    $sql = "ALTER TABLE $table_name ADD COLUMN page_id bigint(20) NOT NULL";
                    break;
                case 'data_transfer':
                    $sql = "ALTER TABLE $table_name ADD COLUMN data_transfer bigint(20) NOT NULL";
                    break;
                case 'load_time':
                    $sql = "ALTER TABLE $table_name ADD COLUMN load_time float NOT NULL";
                    break;
                case 'requests':
                    $sql = "ALTER TABLE $table_name ADD COLUMN requests int(11) NOT NULL";
                    break;
                case 'carbon_footprint':
                    $sql = "ALTER TABLE $table_name ADD COLUMN carbon_footprint float NOT NULL";
                    break;
                case 'energy_consumption':
                    $sql = "ALTER TABLE $table_name ADD COLUMN energy_consumption float NOT NULL";
                    break;
                case 'performance_score':
                    $sql = "ALTER TABLE $table_name ADD COLUMN performance_score float NOT NULL";
                    break;
                case 'created_at':
                    $sql = "ALTER TABLE $table_name ADD COLUMN created_at datetime DEFAULT CURRENT_TIMESTAMP";
                    break;
            }
            
            if (!empty($sql)) {
                // Using direct query because column names can't be parameterized
                $wpdb->query($sql);
                greenmetrics_log('Added missing column', $column);
            }
        }
        
        // Add index if page_id column was just added
        if (in_array('page_id', $missing_columns)) {
            $wpdb->query("ALTER TABLE $table_name ADD INDEX (page_id)");
            greenmetrics_log('Added index for page_id column');
        }
    }
} 