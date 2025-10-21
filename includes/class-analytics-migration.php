<?php
/**
 * Analytics Database Migration and Enhancement System
 * 
 * Handles database schema upgrades for analytics functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class PRI_Analytics_Migration {
    
    private $db_version = '2.0.0';
    private $db_version_option = 'pri_analytics_db_version';
    
    public function __construct() {
        add_action('admin_init', array($this, 'maybe_upgrade'));
    }
    
    /**
     * Check if database upgrade is needed and run it
     */
    public function maybe_upgrade() {
        $current_version = get_option($this->db_version_option, '1.0.0');
        
        if (version_compare($current_version, $this->db_version, '<')) {
            $this->run_upgrades($current_version);
            update_option($this->db_version_option, $this->db_version);
        }
    }
    
    /**
     * Run database upgrades based on current version
     */
    private function run_upgrades($from_version) {
        global $wpdb;
        
        try {
            // Upgrade to 2.0.0 - Analytics enhancements
            if (version_compare($from_version, '2.0.0', '<')) {
                $this->upgrade_to_2_0_0();
            }
            
        } catch (Exception $e) {
            error_log('PRI Analytics Migration Error: ' . $e->getMessage());
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p><strong>Phone Repair Plugin:</strong> Database upgrade failed - ' . esc_html($e->getMessage()) . '</p></div>';
            });
        }
    }
    
    /**
     * Upgrade database to version 2.0.0 for analytics
     */
    private function upgrade_to_2_0_0() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // 1. Enhance appointments table for analytics
        $this->enhance_appointments_table();
        
        // 2. Create appointment status history table
        $this->create_status_history_table();
        
        // 3. Create analytics daily aggregation table
        $this->create_analytics_daily_table();
        
        // 4. Create indexes for performance
        $this->create_analytics_indexes();
        
        // 5. Backfill existing data
        $this->backfill_existing_data();
        
        // Log successful upgrade
        error_log('PRI Analytics: Successfully upgraded to version 2.0.0');
    }
    
    /**
     * Enhance appointments table with analytics fields
     */
    private function enhance_appointments_table() {
        global $wpdb;
        
        $appointments_table = $wpdb->prefix . 'pri_appointments';
        
        // Get current columns
        $columns = $wpdb->get_results("DESCRIBE $appointments_table");
        $column_names = array_column($columns, 'Field');
        
        $alterations = array();
        
        // Add repair_category_id if missing
        if (!in_array('repair_category_id', $column_names)) {
            $alterations[] = "ADD COLUMN repair_category_id mediumint(9) NULL AFTER iphone_model_id";
        }
        
        // Add timestamp columns for lifecycle tracking
        if (!in_array('confirmed_at', $column_names)) {
            $alterations[] = "ADD COLUMN confirmed_at datetime NULL AFTER status";
        }
        if (!in_array('completed_at', $column_names)) {
            $alterations[] = "ADD COLUMN completed_at datetime NULL AFTER confirmed_at";
        }
        if (!in_array('cancelled_at', $column_names)) {
            $alterations[] = "ADD COLUMN cancelled_at datetime NULL AFTER completed_at";
        }
        
        // Add pricing snapshot for historical accuracy
        if (!in_array('price_snapshot', $column_names)) {
            $alterations[] = "ADD COLUMN price_snapshot decimal(10,2) NULL AFTER cancelled_at";
        }
        if (!in_array('currency', $column_names)) {
            $alterations[] = "ADD COLUMN currency char(3) DEFAULT 'USD' AFTER price_snapshot";
        }
        
        // Add source tracking (online, walk-in, phone, referral, etc.)
        if (!in_array('source', $column_names)) {
            $alterations[] = "ADD COLUMN source varchar(50) DEFAULT 'online' AFTER currency";
        }
        
        // Add cancellation reason
        if (!in_array('cancellation_reason', $column_names)) {
            $alterations[] = "ADD COLUMN cancellation_reason varchar(255) NULL AFTER source";
        }
        
        // Execute alterations
        if (!empty($alterations)) {
            $sql = "ALTER TABLE $appointments_table " . implode(', ', $alterations);
            $result = $wpdb->query($sql);
            
            if ($result === false) {
                throw new Exception("Failed to alter appointments table: " . $wpdb->last_error);
            }
        }
    }
    
    /**
     * Create appointment status history table
     */
    private function create_status_history_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pri_appointment_status_history';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            appointment_id mediumint(9) NOT NULL,
            old_status varchar(50) NULL,
            new_status varchar(50) NOT NULL,
            changed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            changed_by bigint(20) unsigned NULL,
            notes text NULL,
            PRIMARY KEY (id),
            KEY idx_appointment (appointment_id),
            KEY idx_status_time (new_status, changed_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create analytics daily aggregation table
     */
    private function create_analytics_daily_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pri_analytics_daily';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            day date NOT NULL,
            repair_category_id mediumint(9) NULL,
            model_id mediumint(9) NULL,
            source varchar(50) NULL,
            requests_count int unsigned NOT NULL DEFAULT 0,
            confirmed_count int unsigned NOT NULL DEFAULT 0,
            completed_count int unsigned NOT NULL DEFAULT 0,
            cancelled_count int unsigned NOT NULL DEFAULT 0,
            revenue_sum decimal(12,2) NOT NULL DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_aggregation (day, repair_category_id, model_id, source),
            KEY idx_day (day),
            KEY idx_category (repair_category_id),
            KEY idx_model (model_id),
            KEY idx_source (source)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create indexes for analytics performance
     */
    private function create_analytics_indexes() {
        global $wpdb;
        
        $appointments_table = $wpdb->prefix . 'pri_appointments';
        
        // Add indexes for analytics queries
        $indexes = array(
            "CREATE INDEX idx_pri_created_at ON $appointments_table (created_at)",
            "CREATE INDEX idx_pri_status ON $appointments_table (status)",
            "CREATE INDEX idx_pri_source ON $appointments_table (source)",
            "CREATE INDEX idx_pri_category_model ON $appointments_table (repair_category_id, iphone_model_id)",
            "CREATE INDEX idx_pri_completed_at ON $appointments_table (completed_at)",
            "CREATE INDEX idx_pri_confirmed_at ON $appointments_table (confirmed_at)"
        );
        
        foreach ($indexes as $index_sql) {
            // Ignore errors if index already exists
            $wpdb->query($index_sql);
        }
    }
    
    /**
     * Backfill existing data with analytics enhancements
     */
    private function backfill_existing_data() {
        global $wpdb;
        
        $appointments_table = $wpdb->prefix . 'pri_appointments';
        
        // Map existing repair_type to repair_category_id
        $this->map_repair_types_to_categories();
        
        // Set price_snapshot from current pricing
        $this->backfill_price_snapshots();
        
        // Set default source for existing appointments
        $wpdb->query($wpdb->prepare("
            UPDATE $appointments_table 
            SET source = 'online' 
            WHERE source IS NULL OR source = ''
        "));
        
        // Create initial status history entries for existing appointments
        $this->create_initial_status_history();
    }
    
    /**
     * Map existing repair_type text to repair_category_id
     */
    private function map_repair_types_to_categories() {
        global $wpdb;
        
        $appointments_table = $wpdb->prefix . 'pri_appointments';
        $categories_table = $wpdb->prefix . 'pri_repair_categories';
        
        // Get repair type mappings
        $mappings = array(
            'screen' => 'Screen Damage',
            'battery' => 'Battery Issue',
            'charging' => 'Charging Issue',
            'camera' => 'Camera Issue',
            'water' => 'Water Damage',
            'other' => 'Other Issue'
        );
        
        foreach ($mappings as $repair_type => $category_name) {
            $category = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $categories_table WHERE name = %s",
                $category_name
            ));
            
            if ($category) {
                $wpdb->query($wpdb->prepare("
                    UPDATE $appointments_table 
                    SET repair_category_id = %d 
                    WHERE repair_type = %s AND repair_category_id IS NULL
                ", $category->id, $repair_type));
            }
        }
        
        // Handle any remaining unmapped repair types
        $other_category = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $categories_table WHERE name = %s",
            'Other Issue'
        ));
        
        if ($other_category) {
            $wpdb->query($wpdb->prepare("
                UPDATE $appointments_table 
                SET repair_category_id = %d 
                WHERE repair_category_id IS NULL
            ", $other_category->id));
        }
    }
    
    /**
     * Backfill price snapshots from current pricing
     */
    private function backfill_price_snapshots() {
        global $wpdb;
        
        $appointments_table = $wpdb->prefix . 'pri_appointments';
        $models_table = $wpdb->prefix . 'pri_iphone_models';
        $pricing_table = $wpdb->prefix . 'pri_model_category_pricing';
        
        // Update price_snapshot from model_category_pricing where possible
        $wpdb->query("
            UPDATE $appointments_table a
            JOIN $pricing_table p ON a.iphone_model_id = p.model_id AND a.repair_category_id = p.category_id
            SET a.price_snapshot = p.price
            WHERE a.price_snapshot IS NULL AND p.price > 0
        ");
        
        // Fallback to base model price for remaining rows
        $wpdb->query("
            UPDATE $appointments_table a
            JOIN $models_table m ON a.iphone_model_id = m.id
            SET a.price_snapshot = m.price
            WHERE a.price_snapshot IS NULL AND m.price > 0
        ");
    }
    
    /**
     * Create initial status history entries for existing appointments
     */
    private function create_initial_status_history() {
        global $wpdb;
        
        $appointments_table = $wpdb->prefix . 'pri_appointments';
        $history_table = $wpdb->prefix . 'pri_appointment_status_history';
        
        // Create history entries for existing appointments
        $wpdb->query("
            INSERT INTO $history_table (appointment_id, old_status, new_status, changed_at)
            SELECT 
                id,
                NULL,
                status,
                created_at
            FROM $appointments_table
            WHERE id NOT IN (
                SELECT DISTINCT appointment_id FROM $history_table
            )
        ");
    }
    
    /**
     * Get migration status for admin display
     */
    public function get_migration_status() {
        $current_version = get_option($this->db_version_option, '1.0.0');
        
        return array(
            'current_version' => $current_version,
            'target_version' => $this->db_version,
            'needs_upgrade' => version_compare($current_version, $this->db_version, '<'),
            'tables_exist' => $this->check_analytics_tables_exist()
        );
    }
    
    /**
     * Check if analytics tables exist
     */
    private function check_analytics_tables_exist() {
        global $wpdb;
        
        $tables = array(
            'pri_appointment_status_history',
            'pri_analytics_daily'
        );
        
        $existing = array();
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            $existing[$table] = $exists;
        }
        
        return $existing;
    }
    
    /**
     * Force run migration (for admin tools)
     */
    public function force_migrate() {
        try {
            $this->run_upgrades('1.0.0');
            update_option($this->db_version_option, $this->db_version);
            return array('success' => true, 'message' => 'Migration completed successfully');
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }
}

// Initialize migration system
new PRI_Analytics_Migration();