<?php
// Debug script to check and test active status
require_once('../../../wp-config.php');

global $wpdb;
$table_name = $wpdb->prefix . 'pri_iphone_models';

echo "<h3>Current iPhone 16 Status:</h3>";
$result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE model_name LIKE %s", '%iPhone 16%'), ARRAY_A);

if ($result) {
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    echo "<h4>Active Status: " . ($result['is_active'] ? 'ACTIVE' : 'INACTIVE') . "</h4>";
    
    // Test toggle
    if (isset($_GET['toggle'])) {
        $new_status = $result['is_active'] ? 0 : 1;
        $update_result = $wpdb->update(
            $table_name,
            array('is_active' => $new_status),
            array('id' => $result['id']),
            array('%d'),
            array('%d')
        );
        
        if ($update_result !== false) {
            echo "<p style='color: green;'>Successfully toggled status to: " . ($new_status ? 'ACTIVE' : 'INACTIVE') . "</p>";
            echo "<a href='?'>Refresh to see change</a>";
        } else {
            echo "<p style='color: red;'>Failed to update: " . $wpdb->last_error . "</p>";
        }
    } else {
        echo "<p><a href='?toggle=1'>Click to toggle active status</a></p>";
    }
} else {
    echo "No iPhone 16 model found.";
}
?>