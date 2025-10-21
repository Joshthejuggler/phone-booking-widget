<?php
// Debug script to check all models
require_once('../../../wp-config.php');

global $wpdb;
$table_name = $wpdb->prefix . 'pri_iphone_models';

echo "<h3>All iPhone Models:</h3>";
$results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id", ARRAY_A);

foreach ($results as $model) {
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
    echo "<strong>ID: " . $model['id'] . " - " . $model['model_name'] . "</strong><br>";
    echo "Active Status: " . ($model['is_active'] ? 'ACTIVE' : 'INACTIVE') . "<br>";
    echo "Price: $" . $model['price'] . "<br>";
    echo "Created: " . $model['created_at'] . "<br>";
    if (strpos($model['model_name'], '16') !== false) {
        echo "<strong style='color: red;'>*** THIS IS AN IPHONE 16 MODEL ***</strong><br>";
    }
    echo "</div>";
}
?>