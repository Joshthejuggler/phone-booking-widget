<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$appointments_table = $wpdb->prefix . 'pri_appointments';
$models_table = $wpdb->prefix . 'pri_iphone_models';

// Handle status update
if (isset($_POST['update_status']) && wp_verify_nonce($_POST['_wpnonce'], 'pri_update_status')) {
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
    <h1><?php _e('Appointments', 'phone-repair-intake'); ?></h1>
    
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
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="manage-column"><?php _e('Customer Info', 'phone-repair-intake'); ?></th>
                        <th class="manage-column"><?php _e('iPhone Model', 'phone-repair-intake'); ?></th>
                        <th class="manage-column"><?php _e('Repair Type', 'phone-repair-intake'); ?></th>
                        <th class="manage-column"><?php _e('Price', 'phone-repair-intake'); ?></th>
                        <th class="manage-column"><?php _e('Status', 'phone-repair-intake'); ?></th>
                        <th class="manage-column"><?php _e('Customer Notes', 'phone-repair-intake'); ?></th>
                        <th class="manage-column"><?php _e('Date Submitted', 'phone-repair-intake'); ?></th>
                        <th class="manage-column"><?php _e('Actions', 'phone-repair-intake'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                        <tr data-status="<?php echo esc_attr($appointment->status); ?>">
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
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('pri_update_status'); ?>
                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment->id; ?>">
                                    <select name="status" onchange="this.form.submit()">
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
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
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