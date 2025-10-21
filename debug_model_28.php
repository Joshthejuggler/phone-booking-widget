<?php
// Debug script for model ID 28
require_once('../../../wp-config.php');

global $wpdb;
$table_name = $wpdb->prefix . 'pri_iphone_models';

echo "<h3>Model ID 28 Debug:</h3>";
$result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", 28), ARRAY_A);

if ($result) {
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    echo "<h4>Details:</h4>";
    echo "Model Name: " . $result['model_name'] . "<br>";
    echo "Active Status: " . ($result['is_active'] ? 'ACTIVE (1)' : 'INACTIVE (0)') . "<br>";
    echo "Raw is_active value: " . $result['is_active'] . "<br>";
    echo "Price: $" . $result['price'] . "<br>";
    
    // Test toggle for model 28
    if (isset($_GET['toggle'])) {
        $new_status = $result['is_active'] ? 0 : 1;
        $update_result = $wpdb->update(
            $table_name,
            array('is_active' => $new_status),
            array('id' => 28),
            array('%d'),
            array('%d')
        );
        
        if ($update_result !== false) {
            echo "<p style='color: green;'>Successfully toggled Model 28 status to: " . ($new_status ? 'ACTIVE' : 'INACTIVE') . "</p>";
            echo "<a href='?'>Refresh to see change</a>";
        } else {
            echo "<p style='color: red;'>Failed to update: " . $wpdb->last_error . "</p>";
        }
    } else {
        echo "<p><a href='?toggle=1'>Click to toggle Model 28 active status</a></p>";
    }
} else {
    echo "Model ID 28 not found.";
}
?>