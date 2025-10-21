<?php
// Debug script to check iPhone 16 data
require_once('../../../wp-config.php');

global $wpdb;
$table_name = $wpdb->prefix . 'pri_iphone_models';

echo "<h3>iPhone 16 Model Data:</h3>";
$result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE model_name LIKE %s", '%iPhone 16%'), ARRAY_A);

if ($result) {
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    echo "<h4>Current pricing fields:</h4>";
    echo "Screen Price: " . ($result['price'] ?? 'NULL') . "<br>";
    echo "Battery Price: " . ($result['battery_price'] ?? 'NULL') . "<br>";
    echo "Charging Price: " . ($result['charging_price'] ?? 'NULL') . "<br>";
    echo "Camera Price: " . ($result['camera_price'] ?? 'NULL') . "<br>";
    echo "Water Price: " . ($result['water_price'] ?? 'NULL') . "<br>";
} else {
    echo "No iPhone 16 model found in database.";
}
?>