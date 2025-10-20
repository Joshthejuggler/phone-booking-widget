<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'pri_iphone_models';

// Get active iPhone models ordered by release (newest to oldest)
$models = $wpdb->get_results("
    SELECT * FROM $table_name 
    WHERE is_active = 1 
    ORDER BY 
        CASE 
            WHEN model_name LIKE '%17%' THEN 1
            WHEN model_name LIKE '%16%' THEN 2
            WHEN model_name LIKE '%15%' THEN 3
            WHEN model_name LIKE '%14%' THEN 4
            WHEN model_name LIKE '%13%' THEN 5
            WHEN model_name LIKE '%12%' THEN 6
            WHEN model_name LIKE '%11%' THEN 7
            WHEN model_name LIKE '%XS%' THEN 8
            WHEN model_name LIKE '%XR%' THEN 9
            WHEN model_name LIKE '%X%' AND model_name NOT LIKE '%XS%' AND model_name NOT LIKE '%XR%' THEN 10
            WHEN model_name LIKE '%SE%' AND model_name LIKE '%2022%' THEN 11
            WHEN model_name LIKE '%SE%' AND model_name LIKE '%2020%' THEN 12
            WHEN model_name LIKE '%8%' THEN 13
            WHEN model_name LIKE '%SE%' AND model_name NOT LIKE '%2020%' AND model_name NOT LIKE '%2022%' THEN 14
            ELSE 15
        END ASC,
        CASE 
            WHEN model_name LIKE '%Pro Max%' THEN 1
            WHEN model_name LIKE '%Pro%' AND model_name NOT LIKE '%Pro Max%' THEN 2
            WHEN model_name LIKE '%Plus%' THEN 3
            WHEN model_name LIKE '%Mini%' THEN 5
            ELSE 4
        END ASC,
        model_name ASC
");
?>

<div id="pri-repair-form-container" class="pri-form-container">
    <!-- Step 1: iPhone Model Selection -->
    <div id="pri-step1" class="pri-form-step pri-active">
        <div class="pri-form-header">
            <h2><?php _e('Select Your iPhone Model', 'phone-repair-intake'); ?></h2>
            <p><?php _e('Choose your iPhone model to see the repair price and continue with booking.', 'phone-repair-intake'); ?></p>
        </div>
        
        <form id="pri-model-selection-form" class="pri-form">
            <?php if (empty($models)): ?>
                <div class="pri-notice pri-notice-info">
                    <p><?php _e('No iPhone models are currently available for repair. Please contact us directly.', 'phone-repair-intake'); ?></p>
                </div>
            <?php else: ?>
                <div class="pri-search-container">
                    <label for="pri-model-search"><?php _e('Search for your iPhone model:', 'phone-repair-intake'); ?></label>
                    <input type="text" id="pri-model-search" placeholder="<?php _e('Type to search (e.g., iPhone 14 Pro)', 'phone-repair-intake'); ?>" class="pri-search-input">
                </div>
                
                <div class="pri-model-grid">
                    <?php foreach ($models as $model): ?>
                        <div class="pri-model-option" data-model-name="<?php echo esc_attr(strtolower($model->model_name)); ?>">
                            <input type="radio" 
                                   id="model-<?php echo $model->id; ?>" 
                                   name="iphone_model" 
                                   value="<?php echo $model->id; ?>"
                                   data-price="<?php echo $model->price; ?>"
                                   data-name="<?php echo esc_attr($model->model_name); ?>">
                            <label for="model-<?php echo $model->id; ?>" class="pri-model-label">
                                <div class="pri-model-name"><?php echo esc_html($model->model_name); ?></div>
                                <div class="pri-model-price">$<?php echo number_format($model->price, 2); ?></div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div id="pri-no-results" class="pri-no-results" style="display: none;">
                    <p><?php _e('No models found matching your search. Try different keywords or scroll through all models above.', 'phone-repair-intake'); ?></p>
                </div>
                
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Step 2: Repair Type Selection -->
    <div id="pri-step2" class="pri-form-step">
        <div class="pri-form-header">
            <h2><?php _e('What needs to be repaired?', 'phone-repair-intake'); ?></h2>
            <p><?php _e('Select the type of repair your phone needs.', 'phone-repair-intake'); ?></p>
        </div>
        
        <form id="pri-repair-type-form" class="pri-form">
            <div class="pri-repair-types-grid">
                <div class="pri-repair-type-option">
                    <input type="radio" id="repair-screen" name="repair_type" value="screen" data-label="Screen Damage">
                    <label for="repair-screen" class="pri-repair-type-label">
                        <div class="pri-repair-icon">📱</div>
                        <div class="pri-repair-name"><?php _e('Screen Damage', 'phone-repair-intake'); ?></div>
                        <div class="pri-repair-desc"><?php _e('Cracked, broken, or unresponsive screen', 'phone-repair-intake'); ?></div>
                    </label>
                </div>
                
                <div class="pri-repair-type-option">
                    <input type="radio" id="repair-battery" name="repair_type" value="battery" data-label="Battery Issue">
                    <label for="repair-battery" class="pri-repair-type-label">
                        <div class="pri-repair-icon">🔋</div>
                        <div class="pri-repair-name"><?php _e('Battery Issue', 'phone-repair-intake'); ?></div>
                        <div class="pri-repair-desc"><?php _e('Battery drains fast or won\'t charge', 'phone-repair-intake'); ?></div>
                    </label>
                </div>
                
                <div class="pri-repair-type-option">
                    <input type="radio" id="repair-charging" name="repair_type" value="charging" data-label="Charging Issue">
                    <label for="repair-charging" class="pri-repair-type-label">
                        <div class="pri-repair-icon">⚡</div>
                        <div class="pri-repair-name"><?php _e('Charging Issue', 'phone-repair-intake'); ?></div>
                        <div class="pri-repair-desc"><?php _e('Won\'t charge or charging port problems', 'phone-repair-intake'); ?></div>
                    </label>
                </div>
                
                <div class="pri-repair-type-option">
                    <input type="radio" id="repair-camera" name="repair_type" value="camera" data-label="Camera Issue">
                    <label for="repair-camera" class="pri-repair-type-label">
                        <div class="pri-repair-icon">📸</div>
                        <div class="pri-repair-name"><?php _e('Camera Issue', 'phone-repair-intake'); ?></div>
                        <div class="pri-repair-desc"><?php _e('Front or rear camera not working', 'phone-repair-intake'); ?></div>
                    </label>
                </div>
                
                <div class="pri-repair-type-option">
                    <input type="radio" id="repair-water" name="repair_type" value="water" data-label="Water Damage">
                    <label for="repair-water" class="pri-repair-type-label">
                        <div class="pri-repair-icon">💧</div>
                        <div class="pri-repair-name"><?php _e('Water Damage', 'phone-repair-intake'); ?></div>
                        <div class="pri-repair-desc"><?php _e('Phone got wet or liquid damage', 'phone-repair-intake'); ?></div>
                    </label>
                </div>
                
                <div class="pri-repair-type-option">
                    <input type="radio" id="repair-other" name="repair_type" value="other" data-label="Other Issue">
                    <label for="repair-other" class="pri-repair-type-label">
                        <div class="pri-repair-icon">❓</div>
                        <div class="pri-repair-name"><?php _e('Other Issue', 'phone-repair-intake'); ?></div>
                        <div class="pri-repair-desc"><?php _e('Describe your specific problem', 'phone-repair-intake'); ?></div>
                    </label>
                </div>
            </div>
            
            <!-- Custom description for "Other" option -->
            <div id="pri-other-description" class="pri-other-description" style="display: none;">
                <label for="other-description"><?php _e('Please describe the issue with your phone:', 'phone-repair-intake'); ?></label>
                <textarea id="other-description" name="other_description" rows="4" placeholder="<?php _e('Tell us what\'s wrong with your phone...', 'phone-repair-intake'); ?>"></textarea>
            </div>
            
            <div class="pri-form-actions">
                <button type="button" id="pri-repair-back-btn" class="pri-btn pri-btn-secondary">
                    <?php _e('Back', 'phone-repair-intake'); ?>
                </button>
                <button type="button" id="pri-other-continue-btn" class="pri-btn pri-btn-primary" style="display: none;">
                    <?php _e('Continue', 'phone-repair-intake'); ?>
                </button>
            </div>
        </form>
    </div>
    
    <!-- Step 3: Appointment Scheduling -->
    <div id="pri-step3" class="pri-form-step">
        <div class="pri-form-header">
            <h2><?php _e('Select Appointment Time', 'phone-repair-intake'); ?></h2>
            <p><?php _e('Choose your preferred date and time for the repair appointment.', 'phone-repair-intake'); ?></p>
        </div>
        
        <form id="pri-scheduling-form" class="pri-form">
            <div class="pri-form-row">
                <div class="pri-form-group">
                    <label for="appointment-date"><?php _e('Preferred Date', 'phone-repair-intake'); ?> <span class="pri-required">*</span></label>
                    <input type="date" id="appointment-date" name="appointment_date" min="<?php echo date('Y-m-d'); ?>" required>
                    <small class="pri-field-help"><?php _e('Select a date to see available appointment times', 'phone-repair-intake'); ?></small>
                </div>
            </div>
            
            <div id="pri-time-slots-container" class="pri-time-slots-container" style="display: none;">
                <div class="pri-form-group">
                    <label><?php _e('Available Times', 'phone-repair-intake'); ?> <span class="pri-required">*</span></label>
                    <div id="pri-loading-slots" class="pri-loading-slots" style="display: none;">
                        <div class="pri-spinner-small"></div>
                        <span><?php _e('Loading available times...', 'phone-repair-intake'); ?></span>
                    </div>
                    <div id="pri-time-slots" class="pri-time-slots-grid"></div>
                    <div id="pri-no-slots" class="pri-no-slots" style="display: none;">
                        <p><?php _e('No appointment times are available for this date. Please choose a different date.', 'phone-repair-intake'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="pri-form-actions">
                <button type="button" id="pri-scheduling-back-btn" class="pri-btn pri-btn-secondary">
                    <?php _e('Back', 'phone-repair-intake'); ?>
                </button>
                <button type="button" id="pri-scheduling-continue-btn" class="pri-btn pri-btn-primary" disabled>
                    <?php _e('Continue', 'phone-repair-intake'); ?>
                </button>
            </div>
        </form>
    </div>
    
    <!-- Step 4: Customer Information -->
    <div id="pri-step4" class="pri-form-step">
        <div class="pri-form-header">
            <h2><?php _e('Your Information', 'phone-repair-intake'); ?></h2>
            <p><?php _e('Please provide your contact information to complete the appointment booking.', 'phone-repair-intake'); ?></p>
        </div>
        
        <form id="pri-appointment-form" class="pri-form">
            <div class="pri-form-row">
                <div class="pri-form-group">
                    <label for="customer-name"><?php _e('Full Name', 'phone-repair-intake'); ?> <span class="pri-required">*</span></label>
                    <input type="text" id="customer-name" name="customer_name" required>
                </div>
            </div>
            
            <div class="pri-form-row">
                <div class="pri-form-group">
                    <label for="customer-email"><?php _e('Email Address', 'phone-repair-intake'); ?> <span class="pri-required">*</span></label>
                    <input type="email" id="customer-email" name="customer_email" required>
                </div>
            </div>
            
            <div class="pri-form-row">
                <div class="pri-form-group">
                    <label for="customer-phone"><?php _e('Phone Number', 'phone-repair-intake'); ?> <span class="pri-required">*</span></label>
                    <input type="tel" id="customer-phone" name="customer_phone" required>
                </div>
            </div>
            
            <div class="pri-form-row">
                <div class="pri-form-group">
                    <label class="pri-checkbox-label">
                        <input type="checkbox" id="accepts-sms" name="accepts_sms" value="1">
                        <span class="pri-checkmark"></span>
                        <?php _e('I agree to receive text messages about my repair status', 'phone-repair-intake'); ?>
                    </label>
                </div>
            </div>
            
            <div class="pri-form-row">
                <div class="pri-form-group">
                    <label for="customer-notes"><?php _e('Additional Notes', 'phone-repair-intake'); ?> <span class="pri-optional"><?php _e('(Optional)', 'phone-repair-intake'); ?></span></label>
                    <textarea id="customer-notes" name="customer_notes" rows="3" placeholder="<?php _e('Any additional information about your repair, special instructions, or questions...', 'phone-repair-intake'); ?>"></textarea>
                    <small class="pri-field-help"><?php _e('Let us know if you have any special requests or additional details about your device.', 'phone-repair-intake'); ?></small>
                </div>
            </div>
            
            <!-- Selected model summary -->
            <div class="pri-booking-summary">
                <h3><?php _e('Booking Summary', 'phone-repair-intake'); ?></h3>
                <div class="pri-summary-content">
                        <div class="pri-summary-row">
                            <span class="pri-summary-label"><?php _e('iPhone Model:', 'phone-repair-intake'); ?></span>
                            <span id="pri-booking-model" class="pri-summary-value"></span>
                        </div>
                        <div class="pri-summary-row">
                            <span class="pri-summary-label"><?php _e('Repair Type:', 'phone-repair-intake'); ?></span>
                            <span id="pri-booking-repair-type" class="pri-summary-value"></span>
                        </div>
                        <div class="pri-summary-row">
                            <span class="pri-summary-label"><?php _e('Repair Price:', 'phone-repair-intake'); ?></span>
                            <span id="pri-booking-price" class="pri-summary-value"></span>
                        </div>
                        <div class="pri-summary-row">
                            <span class="pri-summary-label"><?php _e('Appointment:', 'phone-repair-intake'); ?></span>
                            <span id="pri-booking-appointment" class="pri-summary-value"></span>
                        </div>
                </div>
            </div>
            
            <div class="pri-form-actions">
                <button type="button" id="pri-back-btn" class="pri-btn pri-btn-secondary">
                    <?php _e('Back', 'phone-repair-intake'); ?>
                </button>
                <button type="submit" class="pri-btn pri-btn-primary">
                    <?php _e('Book Appointment', 'phone-repair-intake'); ?>
                </button>
            </div>
        </form>
    </div>
    
    <!-- Step 5: Confirmation -->
    <div id="pri-step5" class="pri-form-step">
        <div class="pri-form-header">
            <div class="pri-success-icon">✓</div>
            <h2><?php _e('Appointment Request Submitted!', 'phone-repair-intake'); ?></h2>
            <p><?php _e('Thank you for your appointment request. We will contact you shortly to confirm your repair appointment.', 'phone-repair-intake'); ?></p>
        </div>
        
        <div class="pri-confirmation-details">
            <h3><?php _e('What happens next?', 'phone-repair-intake'); ?></h3>
            <ul class="pri-next-steps">
                <li><?php _e('We will review your appointment request within 24 hours', 'phone-repair-intake'); ?></li>
                <li><?php _e('You will receive a confirmation email with appointment details', 'phone-repair-intake'); ?></li>
                <li><?php _e('If you provided your phone number, we may call to confirm', 'phone-repair-intake'); ?></li>
                <li><?php _e('Bring your iPhone to our location at the scheduled time', 'phone-repair-intake'); ?></li>
            </ul>
        </div>
        
        <div class="pri-form-actions">
            <button type="button" id="pri-new-appointment-btn" class="pri-btn pri-btn-primary">
                <?php _e('Book Another Appointment', 'phone-repair-intake'); ?>
            </button>
        </div>
    </div>
    
    <!-- Loading overlay -->
    <div id="pri-loading" class="pri-loading" style="display: none;">
        <div class="pri-spinner"></div>
        <p><?php _e('Processing...', 'phone-repair-intake'); ?></p>
    </div>
    
    <!-- Messages -->
    <div id="pri-messages" class="pri-messages"></div>
</div>
