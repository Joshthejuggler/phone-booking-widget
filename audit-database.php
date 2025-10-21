<?php
/**
 * Database Schema Audit Script
 * Visit: http://mi-test-site.local/wp-content/plugins/Phone%20Repair%20Intake%20Form/audit-database.php
 */

define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied - you must be an admin');
}

global $wpdb;

echo "<h1>Phone Repair Plugin - Database Schema Audit</h1>";
echo "<style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Proxima, Helvetica, Arial; margin: 20px; }
    h1, h2, h3 { color: #23282d; }
    .table-info { background: #f1f1f1; padding: 15px; margin: 10px 0; border-radius: 4px; }
    .missing { color: #d63638; font-weight: bold; }
    .exists { color: #00a32a; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f9f9f9; }
    .gap { background-color: #ffffe0; }
</style>";

$tables_to_audit = [
    'pri_appointments',
    'pri_iphone_models', 
    'pri_repair_categories',
    'pri_model_category_pricing',
    'pri_availability',
    'pri_brands'
];

echo "<h2>📊 Current Database Tables</h2>";

foreach ($tables_to_audit as $table_suffix) {
    $table_name = $wpdb->prefix . $table_suffix;
    
    echo "<div class='table-info'>";
    echo "<h3>$table_name</h3>";
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if ($table_exists) {
        echo "<p class='exists'>✅ Table exists</p>";
        
        // Get table structure
        $columns = $wpdb->get_results("DESCRIBE $table_name");
        
        echo "<table>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td><strong>{$column->Field}</strong></td>";
            echo "<td>{$column->Type}</td>";
            echo "<td>{$column->Null}</td>";
            echo "<td>{$column->Key}</td>";
            echo "<td>{$column->Default}</td>";
            echo "<td>{$column->Extra}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Get row count
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        echo "<p><strong>Rows:</strong> $count</p>";
        
        // Show sample data if exists
        if ($count > 0) {
            $sample = $wpdb->get_row("SELECT * FROM $table_name ORDER BY id DESC LIMIT 1");
            if ($sample) {
                echo "<h4>Latest Record:</h4>";
                echo "<table>";
                foreach ($sample as $key => $value) {
                    echo "<tr><td><strong>$key</strong></td><td>" . ($value ?? '<em>NULL</em>') . "</td></tr>";
                }
                echo "</table>";
            }
        }
        
    } else {
        echo "<p class='missing'>❌ Table does not exist</p>";
    }
    
    echo "</div>";
}

echo "<h2>🔍 Schema Analysis for Analytics</h2>";

// Check appointments table specifically for analytics needs
$appointments_table = $wpdb->prefix . 'pri_appointments';
if ($wpdb->get_var("SHOW TABLES LIKE '$appointments_table'") === $appointments_table) {
    $columns = $wpdb->get_results("DESCRIBE $appointments_table");
    $column_names = array_column($columns, 'Field');
    
    echo "<div class='table-info'>";
    echo "<h3>Analytics Readiness - Appointments Table</h3>";
    
    $required_for_analytics = [
        'id' => 'Primary key',
        'iphone_model_id' => 'Link to iPhone models',
        'repair_type' => 'Repair category (current field)',
        'customer_name' => 'Customer info',
        'customer_email' => 'Customer info',
        'status' => 'Lifecycle tracking',
        'created_at' => 'Request timestamp',
        'appointment_date' => 'Scheduled date',
        'appointment_time' => 'Scheduled time'
    ];
    
    $missing_fields = [];
    $existing_fields = [];
    
    foreach ($required_for_analytics as $field => $description) {
        if (in_array($field, $column_names)) {
            $existing_fields[] = $field;
        } else {
            $missing_fields[] = $field;
        }
    }
    
    echo "<h4>✅ Existing Fields for Analytics:</h4>";
    echo "<ul>";
    foreach ($existing_fields as $field) {
        echo "<li><strong>$field</strong> - {$required_for_analytics[$field]}</li>";
    }
    echo "</ul>";
    
    if (!empty($missing_fields)) {
        echo "<h4 class='missing'>❌ Missing Fields for Analytics:</h4>";
        echo "<ul>";
        foreach ($missing_fields as $field) {
            echo "<li class='missing'><strong>$field</strong> - {$required_for_analytics[$field]}</li>";
        }
        echo "</ul>";
    }
    
    // Check for enhanced analytics fields
    $enhanced_fields = [
        'repair_category_id' => 'Link to repair categories table',
        'confirmed_at' => 'Confirmation timestamp',
        'completed_at' => 'Completion timestamp',
        'cancelled_at' => 'Cancellation timestamp',
        'price_snapshot' => 'Price at time of booking',
        'source' => 'How repair was booked (online, walk-in, phone, etc.)',
        'cancellation_reason' => 'Why cancelled'
    ];
    
    echo "<div class='gap'>";
    echo "<h4>🎯 Recommended Enhancements:</h4>";
    echo "<ul>";
    foreach ($enhanced_fields as $field => $description) {
        $exists = in_array($field, $column_names);
        $status = $exists ? "✅" : "➕";
        echo "<li>$status <strong>$field</strong> - $description</li>";
    }
    echo "</ul>";
    echo "</div>";
    
} else {
    echo "<div class='table-info'>";
    echo "<h3 class='missing'>❌ Appointments table not found</h3>";
    echo "</div>";
}

// Check repair categories integration
echo "<div class='table-info'>";
echo "<h3>Repair Categories Integration</h3>";

$categories_table = $wpdb->prefix . 'pri_repair_categories';
if ($wpdb->get_var("SHOW TABLES LIKE '$categories_table'") === $categories_table) {
    $categories = $wpdb->get_results("SELECT * FROM $categories_table ORDER BY sort_order");
    echo "<p class='exists'>✅ Repair categories table exists</p>";
    
    echo "<h4>Available Categories:</h4>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Slug</th><th>Always Visible</th><th>Active</th></tr>";
    foreach ($categories as $cat) {
        echo "<tr>";
        echo "<td>{$cat->id}</td>";
        echo "<td>{$cat->name}</td>";
        echo "<td>{$cat->slug}</td>";
        echo "<td>" . ($cat->is_always_visible ? 'Yes' : 'No') . "</td>";
        echo "<td>" . ($cat->is_active ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if appointments use repair_type (text) vs repair_category_id (foreign key)
    if ($wpdb->get_var("SHOW TABLES LIKE '$appointments_table'") === $appointments_table) {
        $sample_appointments = $wpdb->get_results("
            SELECT repair_type, COUNT(*) as count 
            FROM $appointments_table 
            GROUP BY repair_type 
            ORDER BY count DESC 
            LIMIT 10
        ");
        
        if (!empty($sample_appointments)) {
            echo "<h4>Current Repair Types in Appointments:</h4>";
            echo "<table>";
            echo "<tr><th>Repair Type</th><th>Count</th></tr>";
            foreach ($sample_appointments as $repair) {
                echo "<tr><td>{$repair->repair_type}</td><td>{$repair->count}</td></tr>";
            }
            echo "</table>";
        }
    }
    
} else {
    echo "<p class='missing'>❌ Repair categories table not found</p>";
}
echo "</div>";

echo "<h2>📋 Recommendations Summary</h2>";
echo "<div class='table-info'>";
echo "<h3>Priority Actions for Analytics Implementation:</h3>";
echo "<ol>";
echo "<li><strong>Extend appointments table</strong> with analytics fields (source, timestamps, price_snapshot)</li>";
echo "<li><strong>Create status history table</strong> for tracking lifecycle changes</li>";
echo "<li><strong>Link repair_type to repair categories</strong> for consistent reporting</li>";
echo "<li><strong>Add manual entry interface</strong> for non-online bookings</li>";
echo "<li><strong>Create analytics aggregation tables</strong> for performance</li>";
echo "</ol>";

echo "<h3>Data Migration Considerations:</h3>";
echo "<ul>";
echo "<li>Backfill existing appointments with default source = 'online'</li>";
echo "<li>Map current repair_type text to repair_category_id</li>";
echo "<li>Set price_snapshot from current model/category pricing</li>";
echo "<li>Populate missing timestamps based on current status</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><strong>Generated:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>