<?php
// Quick database debugging script
// Visit: http://mi-test-site.local/wp-content/plugins/Phone%20Repair%20Intake%20Form/debug-db.php

define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied - you must be an admin');
}

global $wpdb;
$table_name = $wpdb->prefix . 'pri_iphone_models';

echo "<h1>Database Debug</h1>";

// Show current structure
echo "<h2>Current Table Structure:</h2>";
$columns = $wpdb->get_results("DESCRIBE $table_name");
$column_names = array_column($columns, 'Field');

echo "<ul>";
foreach ($columns as $column) {
    echo "<li><strong>{$column->Field}</strong> ({$column->Type})</li>";
}
echo "</ul>";

// Check for missing columns
echo "<h2>Missing Columns Check:</h2>";
$missing = [];
if (!in_array('battery_price', $column_names)) $missing[] = 'battery_price';
if (!in_array('charging_price', $column_names)) $missing[] = 'charging_price';
if (!in_array('camera_price', $column_names)) $missing[] = 'camera_price';
if (!in_array('water_price', $column_names)) $missing[] = 'water_price';

if ($missing) {
    echo "<p style='color: red;'>Missing columns: " . implode(', ', $missing) . "</p>";
    
    if (isset($_GET['fix']) && $_GET['fix'] == '1') {
        // Add missing columns
        $alter_sql = [];
        if (!in_array('battery_price', $column_names)) $alter_sql[] = 'ADD COLUMN battery_price decimal(10,2) DEFAULT NULL AFTER price';
        if (!in_array('charging_price', $column_names)) $alter_sql[] = 'ADD COLUMN charging_price decimal(10,2) DEFAULT NULL AFTER battery_price';
        if (!in_array('camera_price', $column_names)) $alter_sql[] = 'ADD COLUMN camera_price decimal(10,2) DEFAULT NULL AFTER charging_price';
        if (!in_array('water_price', $column_names)) $alter_sql[] = 'ADD COLUMN water_price decimal(10,2) DEFAULT NULL AFTER camera_price';
        
        $sql = "ALTER TABLE $table_name " . implode(', ', $alter_sql);
        echo "<p><strong>Running SQL:</strong><br><code>$sql</code></p>";
        
        $result = $wpdb->query($sql);
        if ($result !== false) {
            echo "<p style='color: green;'><strong>SUCCESS:</strong> Columns added!</p>";
            echo "<p><a href='" . $_SERVER['PHP_SELF'] . "'>Refresh to see changes</a></p>";
        } else {
            echo "<p style='color: red;'><strong>ERROR:</strong> " . $wpdb->last_error . "</p>";
        }
    } else {
        echo "<p><a href='" . $_SERVER['PHP_SELF'] . "?fix=1' style='background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;'>FIX DATABASE NOW</a></p>";
    }
} else {
    echo "<p style='color: green;'>All required columns exist!</p>";
}

// Show sample data
echo "<h2>Sample Data (Latest iPhone Model):</h2>";
$sample = $wpdb->get_row("SELECT * FROM $table_name ORDER BY id DESC LIMIT 1");
if ($sample) {
    echo "<table border='1' cellpadding='5'>";
    foreach ($sample as $key => $value) {
        echo "<tr><td><strong>$key</strong></td><td>" . ($value ?? 'NULL') . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No data found</p>";
}

// Show iPhone 16 specifically
echo "<h2>iPhone 16 Data:</h2>";
$iphone16 = $wpdb->get_row("SELECT * FROM $table_name WHERE model_name LIKE '%iPhone 16%' ORDER BY id DESC LIMIT 1");
if ($iphone16) {
    echo "<table border='1' cellpadding='5'>";
    foreach ($iphone16 as $key => $value) {
        $display_value = $value ?? 'NULL';
        $color = ($value === null) ? 'color: red;' : '';
        echo "<tr><td><strong>$key</strong></td><td style='$color'>$display_value</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>iPhone 16 not found</p>";
}

echo "<hr>";
echo "<p><a href='" . admin_url('admin.php?page=phone-repair-models') . "'>Go to iPhone Models Admin</a></p>";
?>