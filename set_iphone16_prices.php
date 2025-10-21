<?php
// Script to set iPhone 16 pricing
require_once('../../../wp-config.php');

global $wpdb;
$table_name = $wpdb->prefix . 'pri_iphone_models';

// Update iPhone 16 with pricing
$result = $wpdb->update(
    $table_name,
    array(
        'battery_price' => '6.77',
        'charging_price' => '6.70',
        'camera_price' => '7.66',
        'water_price' => '15.00'
    ),
    array('model_name' => 'iPhone 16e'),
    array('%s', '%s', '%s', '%s'),
    array('%s')
);

if ($result !== false) {
    echo "Successfully updated iPhone 16e pricing:<br>";
    echo "Battery: $6.77<br>";
    echo "Charging: $6.70<br>";
    echo "Camera: $7.66<br>";
    echo "Water Damage: $15.00<br>";
    
    // Verify the update
    $updated_model = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE model_name = %s", 'iPhone 16e'), ARRAY_A);
    echo "<br><h3>Updated data:</h3><pre>";
    print_r($updated_model);
    echo "</pre>";
} else {
    echo "Error updating pricing: " . $wpdb->last_error;
}
?>