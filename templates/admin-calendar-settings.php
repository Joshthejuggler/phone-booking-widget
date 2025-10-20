<?php
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['save_settings']) && wp_verify_nonce($_POST['_wpnonce'], 'pri_calendar_settings')) {
    $service_account_json = stripslashes($_POST['service_account_json']);
    $calendar_id = sanitize_text_field($_POST['calendar_id']);
    
    // Validate JSON
    $json_data = json_decode($service_account_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error_message = 'Invalid JSON format. Please check your service account JSON.';
    } else {
        // Save the JSON to file
        $credentials_path = PRI_PLUGIN_PATH . 'credentials/service-account.json';
        $saved = file_put_contents($credentials_path, $service_account_json);
        
        if ($saved !== false) {
            // Save calendar ID to options
            update_option('pri_google_calendar_id', $calendar_id);
            $success_message = 'Settings saved successfully!';
        } else {
            $error_message = 'Failed to save credentials file. Please check file permissions.';
        }
    }
}

// Load current settings
$current_json = '';
$credentials_path = PRI_PLUGIN_PATH . 'credentials/service-account.json';
if (file_exists($credentials_path)) {
    $current_json = file_get_contents($credentials_path);
}
$current_calendar_id = get_option('pri_google_calendar_id', 'primary');
?>

<div class="wrap">
    <h1><?php _e('Google Calendar Settings', 'phone-repair-intake'); ?></h1>
    
    <?php if (isset($success_message)): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($success_message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error_message); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('pri_calendar_settings'); ?>
        
        <div class="card">
            <h2>Service Account Credentials</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="service_account_json">Service Account JSON</label>
                    </th>
                    <td>
                        <textarea 
                            name="service_account_json" 
                            id="service_account_json" 
                            rows="15" 
                            cols="100" 
                            class="large-text code"
                            placeholder='Paste your service account JSON here...'
                        ><?php echo esc_textarea($current_json); ?></textarea>
                        <p class="description">
                            Paste the complete JSON content from your Google service account credentials file.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="calendar_id">Calendar ID</label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            name="calendar_id" 
                            id="calendar_id" 
                            value="<?php echo esc_attr($current_calendar_id); ?>" 
                            class="regular-text"
                            placeholder="primary"
                        >
                        <p class="description">
                            Use "primary" for your main calendar, or paste a specific calendar ID from Google Calendar settings.
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2>Setup Instructions</h2>
            <ol>
                <li><strong>Create a Service Account:</strong>
                    <ul>
                        <li>Go to <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                        <li>Create or select a project</li>
                        <li>Enable the Google Calendar API</li>
                        <li>Create a service account</li>
                        <li>Generate and download JSON credentials</li>
                    </ul>
                </li>
                <li><strong>Share Your Calendar:</strong>
                    <ul>
                        <li>Open Google Calendar</li>
                        <li>Go to your calendar settings</li>
                        <li>Share with the service account email (from the JSON)</li>
                        <li>Grant "Make changes to events" permission</li>
                    </ul>
                </li>
                <li><strong>Paste JSON Above:</strong>
                    <ul>
                        <li>Open your downloaded JSON file</li>
                        <li>Copy the entire content</li>
                        <li>Paste it in the text area above</li>
                    </ul>
                </li>
            </ol>
        </div>
        
        <?php if (!empty($current_json)): ?>
            <div class="card" style="margin-top: 20px;">
                <h2>Current Status</h2>
                <?php
                $json_data = json_decode($current_json, true);
                if ($json_data && isset($json_data['client_email'])):
                ?>
                    <p><strong>Service Account Email:</strong> <code><?php echo esc_html($json_data['client_email']); ?></code></p>
                    <p><strong>Project ID:</strong> <code><?php echo esc_html($json_data['project_id']); ?></code></p>
                    <p class="description">Make sure your Google Calendar is shared with the service account email above.</p>
                <?php else: ?>
                    <p class="description" style="color: #d63638;">Invalid JSON format detected.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <p class="submit">
            <input type="submit" name="save_settings" class="button-primary" value="Save Settings">
        </p>
    </form>
    
    <?php if (!empty($current_json)): ?>
        <p>
            <a href="<?php echo admin_url('admin.php?page=phone-repair-calendar-test'); ?>" class="button">
                Test Connection →
            </a>
        </p>
    <?php endif; ?>
</div>

<style>
.card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.card h2 {
    margin-top: 0;
}

#service_account_json {
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 12px;
}
</style>