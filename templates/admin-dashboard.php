<?php
if (!defined('ABSPATH')) {
    exit;
}

// Handle price update request
if (isset($_POST['update_prices']) && wp_verify_nonce($_POST['_wpnonce'], 'pri_update_prices')) {
    $plugin = new PhoneRepairIntake();
    $updated_count = $plugin->update_prices_to_correct_values();
    echo '<div class="notice notice-success"><p>Prices updated successfully! ' . $updated_count . ' models processed.</p></div>';
}

// Handle database repair request
if (isset($_POST['repair_database']) && wp_verify_nonce($_POST['_wpnonce'], 'pri_repair_database')) {
    $plugin = new PhoneRepairIntake();
    $plugin->check_and_repair_tables();
    echo '<div class="notice notice-success"><p>Database tables checked and repaired if needed!</p></div>';
}

global $wpdb;
$appointments_table = $wpdb->prefix . 'pri_appointments';
$models_table = $wpdb->prefix . 'pri_iphone_models';

// Get statistics
$total_appointments = $wpdb->get_var("SELECT COUNT(*) FROM $appointments_table");
$pending_appointments = $wpdb->get_var("SELECT COUNT(*) FROM $appointments_table WHERE status = 'pending'");
$total_models = $wpdb->get_var("SELECT COUNT(*) FROM $models_table WHERE is_active = 1");
$recent_appointments = $wpdb->get_results("
    SELECT a.*, m.model_name, m.price 
    FROM $appointments_table a 
    LEFT JOIN $models_table m ON a.iphone_model_id = m.id 
    ORDER BY a.created_at DESC 
    LIMIT 10
");
?>

<div class="wrap">
    <h1><?php _e('Phone Repair Dashboard', 'phone-repair-intake'); ?></h1>
    
    <div class="pri-dashboard-stats">
        <div class="postbox">
            <h2><?php _e('Quick Stats', 'phone-repair-intake'); ?></h2>
            <div class="inside">
                <div class="pri-stats-grid">
                    <div class="pri-stat-item">
                        <div class="pri-stat-number"><?php echo $total_appointments; ?></div>
                        <div class="pri-stat-label"><?php _e('Total Appointments', 'phone-repair-intake'); ?></div>
                    </div>
                    <div class="pri-stat-item">
                        <div class="pri-stat-number"><?php echo $pending_appointments; ?></div>
                        <div class="pri-stat-label"><?php _e('Pending Appointments', 'phone-repair-intake'); ?></div>
                    </div>
                    <div class="pri-stat-item">
                        <div class="pri-stat-number"><?php echo $total_models; ?></div>
                        <div class="pri-stat-label"><?php _e('Active iPhone Models', 'phone-repair-intake'); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="pri-dashboard-content">
        <div class="postbox">
            <h2><?php _e('Recent Appointments', 'phone-repair-intake'); ?></h2>
            <div class="inside">
                <?php if (empty($recent_appointments)): ?>
                    <p><?php _e('No appointments yet.', 'phone-repair-intake'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Customer', 'phone-repair-intake'); ?></th>
                                <th><?php _e('iPhone Model', 'phone-repair-intake'); ?></th>
                                <th><?php _e('Price', 'phone-repair-intake'); ?></th>
                                <th><?php _e('Status', 'phone-repair-intake'); ?></th>
                                <th><?php _e('Date', 'phone-repair-intake'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_appointments as $appointment): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($appointment->customer_name); ?></strong><br>
                                        <small>
                                            <?php echo esc_html($appointment->customer_email); ?><br>
                                            <?php echo esc_html($appointment->customer_phone); ?>
                                            <?php if ($appointment->accepts_sms): ?>
                                                <span class="pri-sms-badge"><?php _e('SMS OK', 'phone-repair-intake'); ?></span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td><?php echo esc_html($appointment->model_name); ?></td>
                                    <td>$<?php echo number_format($appointment->price, 2); ?></td>
                                    <td>
                                        <span class="pri-status-<?php echo esc_attr($appointment->status); ?>">
                                            <?php echo esc_html(ucfirst($appointment->status)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($appointment->created_at)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p><a href="<?php echo admin_url('admin.php?page=phone-repair-appointments'); ?>" class="button"><?php _e('View All Appointments', 'phone-repair-intake'); ?></a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="pri-dashboard-sidebar">
        <div class="postbox">
            <h2><?php _e('Quick Actions', 'phone-repair-intake'); ?></h2>
            <div class="inside">
                <p><a href="<?php echo admin_url('admin.php?page=phone-repair-models'); ?>" class="button button-primary"><?php _e('Manage iPhone Models', 'phone-repair-intake'); ?></a></p>
                <p><a href="<?php echo admin_url('admin.php?page=phone-repair-appointments'); ?>" class="button"><?php _e('View All Appointments', 'phone-repair-intake'); ?></a></p>
                
                <form method="post" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                    <?php wp_nonce_field('pri_update_prices'); ?>
                    <p><strong><?php _e('Fix Pricing:', 'phone-repair-intake'); ?></strong></p>
                    <p><small><?php _e('Click this button to update all iPhone model prices to the correct values ($90-$280 based on your pricing chart).', 'phone-repair-intake'); ?></small></p>
                    <p>
                        <input type="submit" name="update_prices" value="<?php _e('Update All Prices', 'phone-repair-intake'); ?>" class="button" 
                               onclick="return confirm('Are you sure you want to update all iPhone model prices? This will overwrite existing prices with the correct pricing structure.');">
                    </p>
                </form>
                
                <form method="post" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                    <?php wp_nonce_field('pri_repair_database'); ?>
                    <p><strong><?php _e('Fix Database:', 'phone-repair-intake'); ?></strong></p>
                    <p><small><?php _e('Click this button if appointment submissions are failing. This will check and repair database tables.', 'phone-repair-intake'); ?></small></p>
                    <p>
                        <input type="submit" name="repair_database" value="<?php _e('Repair Database Tables', 'phone-repair-intake'); ?>" class="button button-secondary">
                    </p>
                </form>
            </div>
        </div>
        
        <div class="postbox">
            <h2><?php _e('Shortcode', 'phone-repair-intake'); ?></h2>
            <div class="inside">
                <p><?php _e('Use this shortcode to display the repair form on any page or post:', 'phone-repair-intake'); ?></p>
                <code>[phone_repair_form]</code>
                <p><small><?php _e('Copy and paste this shortcode into any page or post where you want the repair form to appear.', 'phone-repair-intake'); ?></small></p>
            </div>
        </div>
        
        <div class="postbox">
            <h2><?php _e('Important Note', 'phone-repair-intake'); ?></h2>
            <div class="inside">
                <p style="color: #d63638; font-weight: 600;"><?php _e('Not Currently Repairing:', 'phone-repair-intake'); ?></p>
                <p><?php _e('iPhone 17, 16 Plus, Pro, or Pro Max models are not currently available for repair.', 'phone-repair-intake'); ?></p>
                <p><small><?php _e('This message is for your reference. Customers will only see the models you have marked as active.', 'phone-repair-intake'); ?></small></p>
            </div>
        </div>
    </div>
</div>