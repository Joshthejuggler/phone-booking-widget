<?php
if (!defined('ABSPATH')) {
    exit;
}

// Handle database setup request
$setup_result = null;
if (isset($_POST['setup_database'])) {
    global $wpdb;
    
    // Get the plugin instance to run table creation
    $plugin_instance = new PhoneRepairIntake();
    $plugin_instance->activate(); // This creates the tables
    
    // Check if tables exist
    $availability_table = $wpdb->prefix . 'pri_availability';
    $brands_table = $wpdb->prefix . 'pri_brands';
    
    $av_exists = $wpdb->get_var("SHOW TABLES LIKE '$availability_table'") === $availability_table;
    $br_exists = $wpdb->get_var("SHOW TABLES LIKE '$brands_table'") === $brands_table;
    
    if ($av_exists && $br_exists) {
        $av_count = $wpdb->get_var("SELECT COUNT(*) FROM $availability_table");
        $br_count = $wpdb->get_var("SELECT COUNT(*) FROM $brands_table");
        $setup_result = "Database setup complete! Availability slots: $av_count, Brands: $br_count";
    } else {
        $setup_result = "Database setup failed. Tables not created.";
    }
}

// Handle test connection request
$test_result = null;
if (isset($_POST['test_connection'])) {
    require_once PRI_PLUGIN_PATH . 'google-calendar-integration.php';
    $calendar = new GoogleCalendarIntegration();
    $test_result = $calendar->test_connection();
}

// Handle get available slots request
$slots_result = null;
$debug_info = [];
if (isset($_POST['get_slots'])) {
    $date = sanitize_text_field($_POST['test_date']);
    if ($date) {
        $debug_info[] = 'Testing date: ' . $date;
        
        require_once PRI_PLUGIN_PATH . 'google-calendar-integration.php';
        $calendar = new GoogleCalendarIntegration();
        
        $debug_info[] = 'Calendar object created';
        
        $slots_result = $calendar->get_available_slots($date);
        
        $debug_info[] = 'Found ' . count($slots_result) . ' slots';
    }
}
?>

<div class="wrap">
    <h1><?php _e('Google Calendar Test', 'phone-repair-intake'); ?></h1>
    
    <?php if (isset($setup_result)): ?>
        <div class="notice notice-<?php echo strpos($setup_result, 'complete') !== false ? 'success' : 'error'; ?>">
            <p><strong>Database Setup:</strong> <?php echo esc_html($setup_result); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <h2>Database Setup</h2>
        <p>If you're seeing "0 available slots", run this first to create the database tables:</p>
        <form method="post">
            <?php wp_nonce_field('pri_calendar_test'); ?>
            <input type="submit" name="setup_database" class="button-primary" value="Setup Database Tables">
        </form>
    </div>
    
    <div class="card">
        <h2>Connection Test</h2>
        <form method="post">
            <p>Test your Google Calendar service account connection:</p>
            <?php wp_nonce_field('pri_calendar_test'); ?>
            <input type="submit" name="test_connection" class="button-primary" value="Test Connection">
        </form>
        
        <?php if ($test_result): ?>
            <div class="notice notice-<?php echo $test_result['success'] ? 'success' : 'error'; ?>">
                <p><strong>Result:</strong> <?php echo esc_html($test_result['message']); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card" style="margin-top: 20px;">
        <h2>Available Slots Test</h2>
        <form method="post">
            <p>Test getting available slots for a specific date:</p>
            <?php wp_nonce_field('pri_calendar_test'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Test Date</th>
                    <td>
                        <input type="date" name="test_date" value="<?php echo date('Y-m-d'); ?>" required>
                        <p class="description">Select a date to check available time slots</p>
                    </td>
                </tr>
            </table>
            <input type="submit" name="get_slots" class="button-primary" value="Get Available Slots">
        </form>
        
        <?php if (!empty($debug_info)): ?>
            <div class="notice notice-warning">
                <p><strong>Debug Info:</strong></p>
                <ul>
                    <?php foreach ($debug_info as $info): ?>
                        <li><?php echo esc_html($info); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($slots_result !== null): ?>
            <div class="notice notice-info">
                <p><strong>Available slots:</strong></p>
                <?php if (empty($slots_result)): ?>
                    <p>No available slots found for this date.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($slots_result as $slot): ?>
                            <li><?php echo esc_html($slot['start'] . ' - ' . $slot['end']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card" style="margin-top: 20px;">
        <h2>Setup Instructions</h2>
        <ol>
            <li><strong>Add your service account JSON file:</strong>
                <br>Copy your service account credentials to: <code>credentials/service-account.json</code>
            </li>
            <li><strong>Share your calendar:</strong>
                <br>Make sure your Google Calendar is shared with the service account email
            </li>
            <li><strong>Set permissions:</strong>
                <br>Grant "Make changes to events" permission to the service account
            </li>
        </ol>
    </div>
</div>