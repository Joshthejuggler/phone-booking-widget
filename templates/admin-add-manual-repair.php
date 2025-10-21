<?php
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['add_manual_repair']) && wp_verify_nonce($_POST['_wpnonce'], 'pri_add_manual_repair')) {
    $result = $this->save_manual_repair($_POST);
    if ($result['success']) {
        echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
    }
}

global $wpdb;
$models_table = $wpdb->prefix . 'pri_iphone_models';
$categories_table = $wpdb->prefix . 'pri_repair_categories';

// Get iPhone models and repair categories for dropdowns
$iphone_models = $wpdb->get_results("SELECT * FROM $models_table WHERE is_active = 1 ORDER BY model_name");
$repair_categories = $wpdb->get_results("SELECT * FROM $categories_table WHERE is_active = 1 ORDER BY sort_order, name");
?>

<div class="wrap">
    <h1><?php _e('Add Manual Repair', 'phone-repair-intake'); ?></h1>
    
    <div class="pri-manual-repair-form">
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php _e('Repair Details', 'phone-repair-intake'); ?></h2>
            </div>
            <div class="inside">
                <form method="post" id="manual-repair-form">
                    <?php wp_nonce_field('pri_add_manual_repair'); ?>
                    
                    <table class="form-table">
                        <tbody>
                            <!-- Customer Information -->
                            <tr>
                                <th colspan="2"><h3><?php _e('Customer Information', 'phone-repair-intake'); ?></h3></th>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="customer_name"><?php _e('Customer Name', 'phone-repair-intake'); ?> <span class="required">*</span></label>
                                </th>
                                <td>
                                    <input type="text" id="customer_name" name="customer_name" class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="customer_email"><?php _e('Email Address', 'phone-repair-intake'); ?></label>
                                </th>
                                <td>
                                    <input type="email" id="customer_email" name="customer_email" class="regular-text">
                                    <p class="description"><?php _e('Optional - for receipt and communication', 'phone-repair-intake'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="customer_phone"><?php _e('Phone Number', 'phone-repair-intake'); ?></label>
                                </th>
                                <td>
                                    <input type="tel" id="customer_phone" name="customer_phone" class="regular-text">
                                </td>
                            </tr>
                            
                            <!-- Device and Repair Information -->
                            <tr>
                                <th colspan="2"><h3><?php _e('Device & Repair Information', 'phone-repair-intake'); ?></h3></th>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="iphone_model_id"><?php _e('iPhone Model', 'phone-repair-intake'); ?> <span class="required">*</span></label>
                                </th>
                                <td>
                                    <select id="iphone_model_id" name="iphone_model_id" class="regular-text" required>
                                        <option value=""><?php _e('Select iPhone Model', 'phone-repair-intake'); ?></option>
                                        <?php foreach ($iphone_models as $model): ?>
                                            <option value="<?php echo esc_attr($model->id); ?>" data-price="<?php echo esc_attr($model->price); ?>">
                                                <?php echo esc_html($model->model_name); ?> - $<?php echo number_format($model->price, 2); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="repair_category_id"><?php _e('Repair Category', 'phone-repair-intake'); ?> <span class="required">*</span></label>
                                </th>
                                <td>
                                    <select id="repair_category_id" name="repair_category_id" class="regular-text" required>
                                        <option value=""><?php _e('Select Repair Type', 'phone-repair-intake'); ?></option>
                                        <?php foreach ($repair_categories as $category): ?>
                                            <option value="<?php echo esc_attr($category->id); ?>">
                                                <?php echo esc_html($category->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="repair_description"><?php _e('Repair Description', 'phone-repair-intake'); ?></label>
                                </th>
                                <td>
                                    <textarea id="repair_description" name="repair_description" rows="3" class="large-text" placeholder="<?php _e('Additional details about the repair...', 'phone-repair-intake'); ?>"></textarea>
                                </td>
                            </tr>
                            
                            <!-- Pricing and Status -->
                            <tr>
                                <th colspan="2"><h3><?php _e('Pricing & Status', 'phone-repair-intake'); ?></h3></th>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="price_charged"><?php _e('Price Charged', 'phone-repair-intake'); ?> <span class="required">*</span></label>
                                </th>
                                <td>
                                    <input type="number" id="price_charged" name="price_charged" step="0.01" min="0" class="small-text" required>
                                    <span class="currency">USD</span>
                                    <p class="description"><?php _e('Actual amount charged to customer', 'phone-repair-intake'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="status"><?php _e('Repair Status', 'phone-repair-intake'); ?> <span class="required">*</span></label>
                                </th>
                                <td>
                                    <select id="status" name="status" class="regular-text" required>
                                        <option value="completed"><?php _e('Completed', 'phone-repair-intake'); ?></option>
                                        <option value="in_progress"><?php _e('In Progress', 'phone-repair-intake'); ?></option>
                                        <option value="confirmed"><?php _e('Confirmed', 'phone-repair-intake'); ?></option>
                                        <option value="pending"><?php _e('Pending', 'phone-repair-intake'); ?></option>
                                        <option value="cancelled"><?php _e('Cancelled', 'phone-repair-intake'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr id="completion-date-row">
                                <th scope="row">
                                    <label for="completed_date"><?php _e('Date Completed', 'phone-repair-intake'); ?></label>
                                </th>
                                <td>
                                    <input type="date" id="completed_date" name="completed_date" class="regular-text">
                                    <input type="time" id="completed_time" name="completed_time" class="regular-text" value="<?php echo date('H:i'); ?>">
                                    <p class="description"><?php _e('When was this repair completed? Leave blank to use current date/time.', 'phone-repair-intake'); ?></p>
                                </td>
                            </tr>
                            
                            <!-- Source and Notes -->
                            <tr>
                                <th colspan="2"><h3><?php _e('Additional Information', 'phone-repair-intake'); ?></h3></th>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="source"><?php _e('How did they find you?', 'phone-repair-intake'); ?> <span class="required">*</span></label>
                                </th>
                                <td>
                                    <select id="source" name="source" class="regular-text" required>
                                        <option value="walk-in"><?php _e('Walk-in', 'phone-repair-intake'); ?></option>
                                        <option value="phone"><?php _e('Phone Call', 'phone-repair-intake'); ?></option>
                                        <option value="referral"><?php _e('Referral', 'phone-repair-intake'); ?></option>
                                        <option value="returning"><?php _e('Returning Customer', 'phone-repair-intake'); ?></option>
                                        <option value="social_media"><?php _e('Social Media', 'phone-repair-intake'); ?></option>
                                        <option value="google"><?php _e('Google Search', 'phone-repair-intake'); ?></option>
                                        <option value="other"><?php _e('Other', 'phone-repair-intake'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="customer_notes"><?php _e('Notes', 'phone-repair-intake'); ?></label>
                                </th>
                                <td>
                                    <textarea id="customer_notes" name="customer_notes" rows="3" class="large-text" placeholder="<?php _e('Any additional notes about this repair...', 'phone-repair-intake'); ?>"></textarea>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="submit-section">
                        <p class="submit">
                            <input type="submit" name="add_manual_repair" id="submit" class="button-primary" value="<?php _e('Add Repair', 'phone-repair-intake'); ?>">
                            <a href="<?php echo admin_url('admin.php?page=phone-repair-appointments'); ?>" class="button"><?php _e('Cancel', 'phone-repair-intake'); ?></a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php _e('Quick Tips', 'phone-repair-intake'); ?></h2>
            </div>
            <div class="inside">
                <ul>
                    <li><strong><?php _e('Required fields', 'phone-repair-intake'); ?>:</strong> <?php _e('Customer name, iPhone model, repair category, price, and status', 'phone-repair-intake'); ?></li>
                    <li><strong><?php _e('Pricing', 'phone-repair-intake'); ?>:</strong> <?php _e('Enter the actual amount charged, which may differ from standard pricing', 'phone-repair-intake'); ?></li>
                    <li><strong><?php _e('Source tracking', 'phone-repair-intake'); ?>:</strong> <?php _e('This helps you understand how customers find your business', 'phone-repair-intake'); ?></li>
                    <li><strong><?php _e('Statistics', 'phone-repair-intake'); ?>:</strong> <?php _e('Manual repairs are included in all analytics and reports', 'phone-repair-intake'); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.pri-manual-repair-form .postbox {
    margin-bottom: 20px;
}
.pri-manual-repair-form h3 {
    margin: 0;
    padding: 10px 0;
    color: #23282d;
    border-bottom: 1px solid #ddd;
}
.required {
    color: #d63638;
}
.currency {
    font-weight: bold;
    margin-left: 5px;
}
#completion-date-row {
    display: none;
}
#completion-date-row.show {
    display: table-row;
}
.submit-section {
    border-top: 1px solid #ddd;
    padding-top: 20px;
    margin-top: 20px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Show/hide completion date based on status
    $('#status').on('change', function() {
        const status = $(this).val();
        const completionRow = $('#completion-date-row');
        
        if (status === 'completed') {
            completionRow.addClass('show');
            // Set default completion date to today if empty
            if (!$('#completed_date').val()) {
                $('#completed_date').val(new Date().toISOString().split('T')[0]);
            }
        } else {
            completionRow.removeClass('show');
            $('#completed_date').val('');
        }
    });
    
    // Auto-fill price when iPhone model is selected
    $('#iphone_model_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const modelPrice = selectedOption.data('price');
        
        if (modelPrice && !$('#price_charged').val()) {
            $('#price_charged').val(modelPrice);
        }
    });
    
    // Trigger status change on page load in case 'completed' is pre-selected
    $('#status').trigger('change');
    
    // Form validation
    $('#manual-repair-form').on('submit', function(e) {
        let isValid = true;
        const requiredFields = ['customer_name', 'iphone_model_id', 'repair_category_id', 'price_charged', 'status', 'source'];
        
        // Clear previous errors
        $('.form-invalid').removeClass('form-invalid');
        
        requiredFields.forEach(function(field) {
            const input = $('#' + field);
            if (!input.val().trim()) {
                input.addClass('form-invalid').focus();
                isValid = false;
            }
        });
        
        // Validate email if provided
        const email = $('#customer_email').val().trim();
        if (email && !isValidEmail(email)) {
            $('#customer_email').addClass('form-invalid').focus();
            isValid = false;
        }
        
        // Validate price
        const price = parseFloat($('#price_charged').val());
        if (isNaN(price) || price < 0) {
            $('#price_charged').addClass('form-invalid').focus();
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            alert('<?php _e('Please fill in all required fields correctly.', 'phone-repair-intake'); ?>');
        }
    });
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
});
</script>

<style>
.form-invalid {
    border-color: #d63638 !important;
    box-shadow: 0 0 2px rgba(214, 54, 56, 0.3);
}
</style>