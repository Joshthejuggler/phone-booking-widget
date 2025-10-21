<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$appointments_table = $wpdb->prefix . 'pri_appointments';
$models_table = $wpdb->prefix . 'pri_iphone_models';


// Handle bulk delete - check this first and exit to prevent other form processing
if (isset($_POST['bulk_delete']) && isset($_POST['bulk_action']) && $_POST['bulk_action'] === 'delete') {
    // Verify nonce for bulk actions using the specific bulk_nonce field
    if (!wp_verify_nonce($_POST['bulk_nonce'], 'pri_bulk_actions')) {
        wp_die('Security check failed');
    }
    
    $appointment_ids = array_map('intval', $_POST['appointment_ids'] ?? []);
    
    if (!empty($appointment_ids)) {
        $placeholders = implode(',', array_fill(0, count($appointment_ids), '%d'));
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $appointments_table WHERE id IN ($placeholders)",
            ...$appointment_ids
        ));
        
        // Also delete from status history if table exists
        $history_table = $wpdb->prefix . 'pri_appointment_status_history';
        if ($wpdb->get_var("SHOW TABLES LIKE '$history_table'") === $history_table) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $history_table WHERE appointment_id IN ($placeholders)",
                ...$appointment_ids
            ));
        }
        
        echo '<div class="notice notice-success"><p>' . sprintf(__('%d appointments deleted successfully.', 'phone-repair-intake'), $deleted) . '</p></div>';
    }
}

// Handle individual delete
if (isset($_POST['delete_appointment']) && wp_verify_nonce($_POST['_wpnonce'], 'pri_delete_appointment')) {
    $appointment_id = intval($_POST['appointment_id']);
    
    $deleted = $wpdb->delete($appointments_table, array('id' => $appointment_id), array('%d'));
    
    // Also delete from status history if table exists
    $history_table = $wpdb->prefix . 'pri_appointment_status_history';
    if ($wpdb->get_var("SHOW TABLES LIKE '$history_table'") === $history_table) {
        $wpdb->delete($history_table, array('appointment_id' => $appointment_id), array('%d'));
    }
    
    if ($deleted) {
        echo '<div class="notice notice-success"><p>Appointment deleted successfully.</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Failed to delete appointment.</p></div>';
    }
}

// Handle delete all test appointments (created today)
if (isset($_POST['delete_all_test']) && wp_verify_nonce($_POST['_wpnonce'], 'pri_delete_all_test')) {
    $today = date('Y-m-d');
    
    // Delete appointments created today (assumed to be test appointments)
    $deleted = $wpdb->query($wpdb->prepare(
        "DELETE FROM $appointments_table WHERE DATE(created_at) = %s",
        $today
    ));
    
    // Also delete from status history if table exists
    $history_table = $wpdb->prefix . 'pri_appointment_status_history';
    if ($wpdb->get_var("SHOW TABLES LIKE '$history_table'") === $history_table) {
        $wpdb->query($wpdb->prepare(
            "DELETE h FROM $history_table h 
             LEFT JOIN $appointments_table a ON h.appointment_id = a.id 
             WHERE a.id IS NULL"
        ));
    }
    
    echo '<div class="notice notice-success"><p>' . sprintf(__('%d test appointments from today deleted successfully.', 'phone-repair-intake'), $deleted) . '</p></div>';
}

// Handle status update
if (isset($_POST['update_status']) && isset($_POST['appointment_id']) && !isset($_POST['bulk_delete']) && wp_verify_nonce($_POST['_wpnonce'], 'pri_update_status')) {
    $appointment_id = intval($_POST['appointment_id']);
    $new_status = sanitize_text_field($_POST['status']);
    
    $wpdb->update(
        $appointments_table,
        array('status' => $new_status),
        array('id' => $appointment_id),
        array('%s'),
        array('%d')
    );
    
    echo '<div class="notice notice-success"><p>Appointment status updated successfully.</p></div>';
}

// Get all appointments with model information
$appointments = $wpdb->get_results("
    SELECT a.*, m.model_name, m.price 
    FROM $appointments_table a 
    LEFT JOIN $models_table m ON a.iphone_model_id = m.id 
    ORDER BY a.created_at DESC
");

// Get status counts
$status_counts = $wpdb->get_results("
    SELECT status, COUNT(*) as count 
    FROM $appointments_table 
    GROUP BY status
", OBJECT_K);
?>

<div class="wrap">
    <h1><?php _e('Appointments', 'phone-repair-intake'); ?>
        <a href="<?php echo admin_url('admin.php?page=phone-repair-add-manual'); ?>" class="page-title-action">
            <?php _e('Add Manual Repair', 'phone-repair-intake'); ?>
        </a>
    </h1>
    
    <?php
    // Count today's appointments for the delete button
    $today_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $appointments_table WHERE DATE(created_at) = %s",
        date('Y-m-d')
    ));
    
    if ($today_count > 0):
    ?>
    <div class="notice notice-info" style="margin-top: 10px; position: relative;">
        <p style="display: inline;">
            <strong><?php _e('Testing Cleanup:', 'phone-repair-intake'); ?></strong> 
            <?php printf(__('Found %d appointment(s) created today that may be test data.', 'phone-repair-intake'), $today_count); ?>
        </p>
        <form method="post" style="display: inline; margin-left: 15px;" onsubmit="return confirm('<?php _e('Are you sure you want to delete all appointments created today? This cannot be undone.', 'phone-repair-intake'); ?>')">
            <?php wp_nonce_field('pri_delete_all_test'); ?>
            <input type="submit" name="delete_all_test" value="<?php printf(__('Delete Today\'s %d Appointments', 'phone-repair-intake'), $today_count); ?>" class="button button-secondary" style="background: #d63638; border-color: #d63638; color: #fff;">
        </form>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($status_counts)): ?>
        <div class="pri-status-filter">
            <ul class="subsubsub">
                <li class="all">
                    <a href="#" data-status="all" class="current">
                        <?php _e('All', 'phone-repair-intake'); ?> 
                        <span class="count">(<?php echo array_sum(wp_list_pluck($status_counts, 'count')); ?>)</span>
                    </a> |
                </li>
                <?php foreach ($status_counts as $status => $data): ?>
                    <li class="<?php echo esc_attr($status); ?>">
                        <a href="#" data-status="<?php echo esc_attr($status); ?>">
                            <?php echo esc_html(ucfirst($status)); ?> 
                            <span class="count">(<?php echo $data->count; ?>)</span>
                        </a>
                        <?php if ($status !== array_key_last($status_counts)): ?>|<?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="pri-appointments-list">
        <?php if (empty($appointments)): ?>
            <div class="notice notice-info">
                <p><?php _e('No appointments found yet.', 'phone-repair-intake'); ?></p>
            </div>
        <?php else: ?>
            <form method="post" id="appointments-form">
                <?php wp_nonce_field('pri_bulk_actions', 'bulk_nonce'); ?>
                
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Select bulk action', 'phone-repair-intake'); ?></label>
                        <select name="bulk_action" id="bulk-action-selector-top">
                            <option value="-1"><?php _e('Bulk Actions', 'phone-repair-intake'); ?></option>
                            <option value="delete"><?php _e('Delete', 'phone-repair-intake'); ?></option>
                        </select>
                        <input type="submit" name="bulk_delete" id="doaction" class="button action" value="<?php _e('Apply', 'phone-repair-intake'); ?>" onclick="return confirm('<?php _e('Are you sure you want to delete the selected appointments? This action cannot be undone.', 'phone-repair-intake'); ?>')">
                    </div>
                    <div class="alignright">
                        <span class="displaying-num"><?php printf(__('%s items', 'phone-repair-intake'), count($appointments)); ?></span>
                    </div>
                    <br class="clear">
                </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select All', 'phone-repair-intake'); ?></label>
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th class="manage-column"><?php _e('Customer Info', 'phone-repair-intake'); ?></th>
                        <th class="manage-column"><?php _e('iPhone Model', 'phone-repair-intake'); ?></th>
                        <th class="manage-column"><?php _e('Repair Type', 'phone-repair-intake'); ?></th>
                        <th class="manage-column"><?php _e('Price', 'phone-repair-intake'); ?></th>
                        <th class="manage-column"><?php _e('Source', 'phone-repair-intake'); ?></th>
                        <th class="manage-column"><?php _e('Status', 'phone-repair-intake'); ?></th>
                        <th class="manage-column"><?php _e('Customer Notes', 'phone-repair-intake'); ?></th>
                        <th class="manage-column"><?php _e('Date Submitted', 'phone-repair-intake'); ?></th>
                        <th class="manage-column"><?php _e('Actions', 'phone-repair-intake'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                        <tr data-status="<?php echo esc_attr($appointment->status); ?>">
                            <th scope="row" class="check-column">
                                <label class="screen-reader-text" for="cb-select-<?php echo $appointment->id; ?>"><?php printf(__('Select %s', 'phone-repair-intake'), $appointment->customer_name); ?></label>
                                <input id="cb-select-<?php echo $appointment->id; ?>" type="checkbox" name="appointment_ids[]" value="<?php echo $appointment->id; ?>">
                            </th>
                            <td>
                                <div class="pri-customer-info">
                                    <strong><?php echo esc_html($appointment->customer_name); ?></strong><br>
                                    <a href="mailto:<?php echo esc_attr($appointment->customer_email); ?>">
                                        <?php echo esc_html($appointment->customer_email); ?>
                                    </a><br>
                                    <a href="tel:<?php echo esc_attr($appointment->customer_phone); ?>">
                                        <?php echo esc_html($appointment->customer_phone); ?>
                                    </a>
                                    <?php if ($appointment->accepts_sms): ?>
                                        <br><span class="pri-sms-badge"><?php _e('SMS OK', 'phone-repair-intake'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php echo esc_html($appointment->model_name ?: 'Unknown Model'); ?>
                            </td>
                            <td>
                                <div class="pri-repair-type">
                                    <strong><?php echo esc_html(ucfirst(str_replace('_', ' ', $appointment->repair_type ?: 'Not specified'))); ?></strong>
                                    <?php if (!empty($appointment->repair_description)): ?>
                                        <br><small class="pri-repair-desc"><?php echo esc_html($appointment->repair_description); ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                $<?php echo number_format($appointment->price ?: 0, 2); ?>
                            </td>
                            <td>
                                <span class="pri-source-badge pri-source-<?php echo esc_attr($appointment->source ?: 'online'); ?>">
                                    <?php 
                                    $source_labels = array(
                                        'online' => 'Online',
                                        'walk-in' => 'Walk-in', 
                                        'phone' => 'Phone',
                                        'referral' => 'Referral',
                                        'returning' => 'Returning',
                                        'social_media' => 'Social Media',
                                        'google' => 'Google',
                                        'other' => 'Other'
                                    );
                                    $source = $appointment->source ?: 'online';
                                    echo esc_html($source_labels[$source] ?? ucfirst($source));
                                    ?>
                                </span>
                            </td>
                            <td>
                                <span class="pri-status-<?php echo esc_attr($appointment->status); ?>">
                                    <?php echo esc_html(ucfirst($appointment->status)); ?>
                                </span>
                            </td>
                            <td>
                                <div class="pri-customer-notes">
                                    <?php if (!empty($appointment->customer_notes)): ?>
                                        <div class="pri-notes-preview">
                                            <?php 
                                            $notes = esc_html($appointment->customer_notes);
                                            echo strlen($notes) > 100 ? substr($notes, 0, 100) . '...' : $notes;
                                            ?>
                                        </div>
                                        <?php if (strlen($appointment->customer_notes) > 100): ?>
                                            <a href="#" class="pri-show-full-notes" data-notes="<?php echo esc_attr($appointment->customer_notes); ?>"><?php _e('Show more', 'phone-repair-intake'); ?></a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <em class="pri-no-notes"><?php _e('No notes', 'phone-repair-intake'); ?></em>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php echo date('M j, Y g:i A', strtotime($appointment->created_at)); ?>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <form method="post" style="display: inline; margin-right: 10px;">
                                        <?php wp_nonce_field('pri_update_status'); ?>
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment->id; ?>">
                                        <select name="status" onchange="this.form.submit()" style="margin-bottom: 5px;">
                                            <option value="pending" <?php selected($appointment->status, 'pending'); ?>>
                                                <?php _e('Pending', 'phone-repair-intake'); ?>
                                            </option>
                                            <option value="confirmed" <?php selected($appointment->status, 'confirmed'); ?>>
                                                <?php _e('Confirmed', 'phone-repair-intake'); ?>
                                            </option>
                                            <option value="in_progress" <?php selected($appointment->status, 'in_progress'); ?>>
                                                <?php _e('In Progress', 'phone-repair-intake'); ?>
                                            </option>
                                            <option value="completed" <?php selected($appointment->status, 'completed'); ?>>
                                                <?php _e('Completed', 'phone-repair-intake'); ?>
                                            </option>
                                            <option value="cancelled" <?php selected($appointment->status, 'cancelled'); ?>>
                                                <?php _e('Cancelled', 'phone-repair-intake'); ?>
                                            </option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                    <br>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('<?php _e('Are you sure you want to delete this appointment? This action cannot be undone.', 'phone-repair-intake'); ?>')">
                                        <?php wp_nonce_field('pri_delete_appointment'); ?>
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment->id; ?>">
                                        <input type="submit" name="delete_appointment" value="<?php _e('Delete', 'phone-repair-intake'); ?>" class="button button-small button-link-delete" style="color: #d63638;">
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Select All functionality
    $('#cb-select-all-1').on('change', function() {
        var checked = $(this).is(':checked');
        $('input[name="appointment_ids[]"]').prop('checked', checked);
    });
    
    // Update Select All checkbox when individual checkboxes change
    $('input[name="appointment_ids[]"]').on('change', function() {
        var totalCheckboxes = $('input[name="appointment_ids[]"]').length;
        var checkedCheckboxes = $('input[name="appointment_ids[]"]:checked').length;
        
        $('#cb-select-all-1').prop('checked', totalCheckboxes === checkedCheckboxes);
    });
    
    // Bulk actions validation
    $('#doaction').on('click', function(e) {
        var bulkAction = $('#bulk-action-selector-top').val();
        
        if (bulkAction === 'delete') {
            var checkedBoxes = $('input[name="appointment_ids[]"]:checked');
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert('<?php _e('Please select at least one appointment to delete.', 'phone-repair-intake'); ?>');
                return false;
            }
        }
    });
    
    // Status filter functionality
    $('.subsubsub a').on('click', function(e) {
        e.preventDefault();
        
        var status = $(this).data('status');
        var $rows = $('.wp-list-table tbody tr');
        
        // Update active filter
        $('.subsubsub a').removeClass('current');
        $(this).addClass('current');
        
        // Show/hide rows based on status
        if (status === 'all') {
            $rows.show();
        } else {
            $rows.hide();
            $rows.filter('[data-status="' + status + '"]').show();
        }
    });
    
    // Show full notes functionality
    $('.pri-show-full-notes').on('click', function(e) {
        e.preventDefault();
        
        var fullNotes = $(this).data('notes');
        var $preview = $(this).siblings('.pri-notes-preview');
        var $link = $(this);
        
        if ($link.text() === 'Show more') {
            $preview.html(fullNotes);
            $link.text('Show less');
        } else {
            var truncated = fullNotes.length > 100 ? fullNotes.substring(0, 100) + '...' : fullNotes;
            $preview.html(truncated);
            $link.text('Show more');
        }
    });
});
</script>

<style>
.pri-source-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.pri-source-online {
    background-color: #007cba;
    color: white;
}
.pri-source-walk-in {
    background-color: #00a32a;
    color: white;
}
.pri-source-phone {
    background-color: #996633;
    color: white;
}
.pri-source-referral {
    background-color: #8b5a8c;
    color: white;
}
.pri-source-returning {
    background-color: #c92c2c;
    color: white;
}
.pri-source-social_media {
    background-color: #ff6900;
    color: white;
}
.pri-source-google {
    background-color: #4285f4;
    color: white;
}
.pri-source-other {
    background-color: #666;
    color: white;
}
.page-title-action {
    font-size: 13px;
    padding: 4px 12px;
    margin-left: 10px;
}
</style>
