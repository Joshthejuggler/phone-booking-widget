<?php
/**
 * Temporary script to update iPhone model prices to correct values
 * Run this once in WordPress admin or via WP-CLI, then delete this file
 */

// Make sure this is run within WordPress
if (!defined('ABSPATH')) {
    // If running standalone, you can uncomment and modify this path:
    // require_once('/path/to/your/wordpress/wp-config.php');
    exit('This script must be run within WordPress context');
}

function update_iphone_prices() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'pri_iphone_models';
    
    // Price corrections based on your actual pricing
    $price_updates = array(
        // $90 models
        'iPhone 8' => 90.00,
        'iPhone 8 Plus' => 90.00,
        'iPhone SE' => 90.00,
        
        // $100 models  
        'iPhone XR' => 100.00,
        'iPhone 11' => 100.00,
        
        // $110 models
        'iPhone X' => 110.00,
        'iPhone XS' => 110.00,
        'iPhone XS Max' => 110.00,
        
        // $130 models
        'iPhone 11 Pro' => 130.00,
        'iPhone 11 Pro Max' => 130.00,
        'iPhone 12 Mini' => 130.00,
        'iPhone 12' => 130.00,
        'iPhone 12 Pro' => 130.00,
        'iPhone 12 Pro Max' => 130.00,
        'iPhone 13' => 130.00,
        'iPhone 13 Pro Max' => 130.00,
        'iPhone 14' => 130.00,
        'iPhone 14 Plus' => 130.00,
        
        // $150 models
        'iPhone 13 Mini' => 150.00,
        'iPhone 13 Pro' => 150.00,
        
        // $170 models
        'iPhone 15' => 170.00,
        'iPhone 16e' => 170.00,
        
        // $250 models
        'iPhone 14 Pro' => 250.00,
        'iPhone 14 Pro Max' => 250.00,
        'iPhone 15 Plus' => 250.00,
        
        // $280 models
        'iPhone 15 Pro' => 280.00,
        'iPhone 15 Pro Max' => 280.00,
        'iPhone 16' => 280.00,
    );
    
    echo "<h2>Updating iPhone Model Prices</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Model</th><th>Old Price</th><th>New Price</th><th>Status</th></tr>";
    
    $updated_count = 0;
    $not_found_count = 0;
    
    foreach ($price_updates as $model_name => $new_price) {
        // Get current model data
        $current_model = $wpdb->get_row($wpdb->prepare(
            "SELECT id, model_name, price FROM $table_name WHERE model_name = %s",
            $model_name
        ));
        
        if ($current_model) {
            $old_price = $current_model->price;
            
            // Update the price
            $result = $wpdb->update(
                $table_name,
                array('price' => $new_price),
                array('id' => $current_model->id),
                array('%f'),
                array('%d')
            );
            
            if ($result !== false) {
                echo "<tr><td>$model_name</td><td>$" . number_format($old_price, 2) . "</td><td>$" . number_format($new_price, 2) . "</td><td style='color: green;'>Updated</td></tr>";
                $updated_count++;
            } else {
                echo "<tr><td>$model_name</td><td>$" . number_format($old_price, 2) . "</td><td>$" . number_format($new_price, 2) . "</td><td style='color: red;'>Failed</td></tr>";
            }
        } else {
            echo "<tr><td>$model_name</td><td>-</td><td>$" . number_format($new_price, 2) . "</td><td style='color: orange;'>Not Found - Will Insert</td></tr>";
            
            // Insert missing model
            $insert_result = $wpdb->insert(
                $table_name,
                array(
                    'model_name' => $model_name,
                    'price' => $new_price,
                    'is_active' => 1
                ),
                array('%s', '%f', '%d')
            );
            
            if ($insert_result) {
                echo "<tr><td colspan='4' style='color: green;'>✓ Inserted $model_name</td></tr>";
            }
            
            $not_found_count++;
        }
    }
    
    echo "</table>";
    echo "<p><strong>Summary:</strong></p>";
    echo "<ul>";
    echo "<li>Models updated: $updated_count</li>";
    echo "<li>Models inserted: $not_found_count</li>";
    echo "</ul>";
    
    // Show all current models
    echo "<h3>All Current Models in Database:</h3>";
    $all_models = $wpdb->get_results("SELECT model_name, price, is_active FROM $table_name ORDER BY price ASC, model_name ASC");
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Model Name</th><th>Price</th><th>Status</th></tr>";
    
    foreach ($all_models as $model) {
        $status = $model->is_active ? 'Active' : 'Inactive';
        $status_color = $model->is_active ? 'green' : 'red';
        echo "<tr><td>{$model->model_name}</td><td>$" . number_format($model->price, 2) . "</td><td style='color: $status_color;'>$status</td></tr>";
    }
    echo "</table>";
}

// Run the update if this script is being accessed
if (isset($_GET['run_update']) && $_GET['run_update'] === 'yes') {
    update_iphone_prices();
    echo "<p style='color: green; font-weight: bold;'>Price update completed! You can now delete this file.</p>";
} else {
    echo "<h2>iPhone Price Update Script</h2>";
    echo "<p>This script will update your iPhone model prices to match your actual pricing structure.</p>";
    echo "<p style='color: red;'><strong>Important:</strong> Make sure to backup your database before running this update.</p>";
    echo "<p><a href='?run_update=yes' style='background: #0073aa; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;'>Run Price Update</a></p>";
    
    echo "<h3>Current Models in Database:</h3>";
    global $wpdb;
    $table_name = $wpdb->prefix . 'pri_iphone_models';
    $current_models = $wpdb->get_results("SELECT model_name, price, is_active FROM $table_name ORDER BY model_name ASC");
    
    if ($current_models) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Model Name</th><th>Current Price</th><th>Status</th></tr>";
        
        foreach ($current_models as $model) {
            $status = $model->is_active ? 'Active' : 'Inactive';
            $status_color = $model->is_active ? 'green' : 'red';
            echo "<tr><td>{$model->model_name}</td><td>$" . number_format($model->price, 2) . "</td><td style='color: $status_color;'>$status</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No models found in database.</p>";
    }
}
?>