<?php
/**
 * Database Update Script
 * Run this once to add the missing pricing columns to your existing table
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // If not in WordPress, define the WordPress path
    $wp_path = dirname(dirname(dirname(dirname(__FILE__))));
    require_once($wp_path . '/wp-load.php');
}

global $wpdb;

$table_name = $wpdb->prefix . 'pri_iphone_models';

// Check if columns exist
$columns = $wpdb->get_results("DESCRIBE $table_name");
$column_names = array_column($columns, 'Field');

$columns_to_add = array();

if (!in_array('battery_price', $column_names)) {
    $columns_to_add[] = "ADD COLUMN battery_price decimal(10,2) DEFAULT NULL AFTER price";
}

if (!in_array('charging_price', $column_names)) {
    $columns_to_add[] = "ADD COLUMN charging_price decimal(10,2) DEFAULT NULL AFTER battery_price";
}

if (!in_array('camera_price', $column_names)) {
    $columns_to_add[] = "ADD COLUMN camera_price decimal(10,2) DEFAULT NULL AFTER charging_price";
}

if (!in_array('water_price', $column_names)) {
    $columns_to_add[] = "ADD COLUMN water_price decimal(10,2) DEFAULT NULL AFTER camera_price";
}

if (!empty($columns_to_add)) {
    $sql = "ALTER TABLE $table_name " . implode(', ', $columns_to_add);
    
    echo "<h2>Database Update</h2>";
    echo "<p>Adding missing columns to table: <code>$table_name</code></p>";
    echo "<p>SQL: <code>$sql</code></p>";
    
    $result = $wpdb->query($sql);
    
    if ($result !== false) {
        echo "<p style='color: green;'><strong>Success!</strong> Database updated successfully.</p>";
        echo "<p>Added columns: " . implode(', ', ['battery_price', 'charging_price', 'camera_price', 'water_price']) . "</p>";
    } else {
        echo "<p style='color: red;'><strong>Error:</strong> " . $wpdb->last_error . "</p>";
    }
} else {
    echo "<h2>Database Update</h2>";
    echo "<p style='color: blue;'>All required columns already exist. No update needed.</p>";
}

// Show current table structure
echo "<h3>Current Table Structure:</h3>";
$columns = $wpdb->get_results("DESCRIBE $table_name");
echo "<ul>";
foreach ($columns as $column) {
    echo "<li><strong>{$column->Field}</strong> - {$column->Type}</li>";
}
echo "</ul>";

echo "<p><a href='" . admin_url('admin.php?page=phone-repair-models') . "'>Go to iPhone Models Admin</a></p>";
?>