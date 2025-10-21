<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$models_table = $wpdb->prefix . 'pri_iphone_models';
$categories_table = $wpdb->prefix . 'pri_repair_categories';
$pricing_table = $wpdb->prefix . 'pri_model_category_pricing';

// Get all iPhone models
$models = $wpdb->get_results("
    SELECT * FROM $models_table 
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

// Get all categories
$categories = $wpdb->get_results("
    SELECT * FROM $categories_table 
    WHERE is_active = 1 
    ORDER BY sort_order ASC, name ASC
");

// Get selected model ID for editing
$selected_model_id = isset($_GET['model_id']) ? intval($_GET['model_id']) : (count($models) > 0 ? $models[0]->id : 0);
$selected_model = null;

foreach ($models as $model) {
    if ($model->id == $selected_model_id) {
        $selected_model = $model;
        break;
    }
}

// Get pricing for selected model
$current_pricing = array();
if ($selected_model_id) {
    $pricing_results = $wpdb->get_results($wpdb->prepare("
        SELECT p.*, c.name as category_name, c.slug as category_slug
        FROM $pricing_table p
        LEFT JOIN $categories_table c ON p.category_id = c.id
        WHERE p.model_id = %d
    ", $selected_model_id));
    
    foreach ($pricing_results as $pricing) {
        $current_pricing[$pricing->category_id] = $pricing;
    }
}
?>

<div class="wrap">
    <h1><?php _e('Category Pricing Management', 'phone-repair-intake'); ?></h1>
    
    <div class="pri-pricing-header">
        <p><?php _e('Set repair prices for each iPhone model by category. Screen Damage is always visible to customers, while other categories are only shown if they have pricing set and visibility enabled.', 'phone-repair-intake'); ?></p>
    </div>
    
    <!-- Model Selection -->
    <div class="pri-model-selection">
        <h2><?php _e('Select iPhone Model', 'phone-repair-intake'); ?></h2>
        
        <?php if (empty($models)): ?>
            <div class="notice notice-warning">
                <p><?php _e('No iPhone models found. Please add some models first.', 'phone-repair-intake'); ?></p>
                <p><a href="<?php echo admin_url('admin.php?page=phone-repair-models'); ?>" class="button button-primary"><?php _e('Manage iPhone Models', 'phone-repair-intake'); ?></a></p>
            </div>
        <?php else: ?>
            <div class="pri-model-tabs">
                <?php foreach ($models as $model): ?>
                    <a href="<?php echo admin_url('admin.php?page=phone-repair-category-pricing&model_id=' . $model->id); ?>" 
                       class="pri-model-tab <?php echo ($model->id == $selected_model_id) ? 'active' : ''; ?>">
                        <?php echo esc_html($model->model_name); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($selected_model && !empty($categories)): ?>
    <!-- Pricing Form -->
    <div class="pri-pricing-form">
        <h2><?php printf(__('Pricing for %s', 'phone-repair-intake'), esc_html($selected_model->model_name)); ?></h2>
        
        <form id="pri-category-pricing-form">
            <input type="hidden" name="model_id" value="<?php echo $selected_model->id; ?>">
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="manage-column"><?php _e('Repair Category', 'phone-repair-intake'); ?></th>
                        <th class="manage-column"><?php _e('Price ($)', 'phone-repair-intake'); ?></th>
                        <th class="manage-column"><?php _e('Visible to Customers', 'phone-repair-intake'); ?></th>
                        <th class="manage-column"><?php _e('Notes', 'phone-repair-intake'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <?php 
                        $pricing = isset($current_pricing[$category->id]) ? $current_pricing[$category->id] : null;
                        $price = $pricing ? $pricing->price : '';
                        $is_visible = $pricing ? $pricing->is_visible : 0;
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($category->name); ?></strong>
                                <input type="hidden" name="categories[<?php echo $category->id; ?>][id]" value="<?php echo $category->id; ?>">
                            </td>
                            <td>
                                <input type="number" 
                                       name="categories[<?php echo $category->id; ?>][price]" 
                                       value="<?php echo esc_attr($price); ?>"
                                       step="0.01" 
                                       min="0" 
                                       class="regular-text"
                                       placeholder="0.00">
                            </td>
                            <td>
                                <?php if ($category->is_always_visible): ?>
                                    <span class="pri-always-visible"><?php _e('Always Visible', 'phone-repair-intake'); ?></span>
                                    <input type="hidden" name="categories[<?php echo $category->id; ?>][is_visible]" value="1">
                                <?php else: ?>
                                    <label>
                                        <input type="checkbox" 
                                               name="categories[<?php echo $category->id; ?>][is_visible]" 
                                               value="1"
                                               <?php checked($is_visible, 1); ?>>
                                        <?php _e('Show to customers', 'phone-repair-intake'); ?>
                                    </label>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($category->is_always_visible): ?>
                                    <em><?php _e('Screen damage pricing is always shown to customers', 'phone-repair-intake'); ?></em>
                                <?php else: ?>
                                    <em><?php _e('Only shown if price is set and visibility is enabled', 'phone-repair-intake'); ?></em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Save Pricing', 'phone-repair-intake'); ?></button>
                <a href="<?php echo admin_url('admin.php?page=phone-repair-models'); ?>" class="button button-secondary"><?php _e('Manage Models', 'phone-repair-intake'); ?></a>
            </p>
        </form>
    </div>
    <?php elseif (empty($categories)): ?>
    <div class="notice notice-error">
        <p><?php _e('No repair categories found. The plugin needs to be reactivated to create default categories.', 'phone-repair-intake'); ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Bulk Actions -->
    <?php if (!empty($models) && !empty($categories)): ?>
    <div class="pri-bulk-actions">
        <h2><?php _e('Bulk Actions', 'phone-repair-intake'); ?></h2>
        <p><?php _e('Apply pricing changes to multiple models at once.', 'phone-repair-intake'); ?></p>
        
        <div class="pri-bulk-action-section">
            <h3><?php _e('Copy Pricing from Another Model', 'phone-repair-intake'); ?></h3>
            <form id="pri-copy-pricing-form">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Copy From', 'phone-repair-intake'); ?></th>
                        <td>
                            <select name="source_model_id" required>
                                <option value=""><?php _e('Select source model...', 'phone-repair-intake'); ?></option>
                                <?php foreach ($models as $model): ?>
                                    <option value="<?php echo $model->id; ?>"><?php echo esc_html($model->model_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Copy To', 'phone-repair-intake'); ?></th>
                        <td>
                            <?php foreach ($models as $model): ?>
                                <label>
                                    <input type="checkbox" name="target_model_ids[]" value="<?php echo $model->id; ?>">
                                    <?php echo esc_html($model->model_name); ?>
                                </label><br>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-secondary"><?php _e('Copy Pricing', 'phone-repair-intake'); ?></button>
                </p>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Loading overlay -->
<div id="pri-loading-overlay" style="display: none;">
    <div class="pri-loading-spinner">
        <div class="spinner is-active"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Save category pricing
    $('#pri-category-pricing-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=save_category_pricing&nonce=' + pri_admin_ajax.nonce;
        
        $('#pri-loading-overlay').show();
        
        $.ajax({
            url: pri_admin_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                $('#pri-loading-overlay').hide();
                
                if (response.success) {
                    $('<div class="notice notice-success is-dismissible"><p>' + response.data + '</p></div>')
                        .insertAfter('.wrap h1');
                    
                    // Auto-hide success message
                    setTimeout(function() {
                        $('.notice-success').fadeOut();
                    }, 3000);
                } else {
                    $('<div class="notice notice-error is-dismissible"><p>Error: ' + response.data + '</p></div>')
                        .insertAfter('.wrap h1');
                }
                
                // Scroll to top
                $('html, body').animate({scrollTop: 0}, 300);
            },
            error: function() {
                $('#pri-loading-overlay').hide();
                $('<div class="notice notice-error is-dismissible"><p>Connection error. Please try again.</p></div>')
                    .insertAfter('.wrap h1');
            }
        });
    });
    
    // Copy pricing between models
    $('#pri-copy-pricing-form').on('submit', function(e) {
        e.preventDefault();
        
        var sourceModel = $('select[name="source_model_id"]').val();
        var targetModels = $('input[name="target_model_ids[]"]:checked');
        
        if (!sourceModel) {
            alert('Please select a source model.');
            return;
        }
        
        if (targetModels.length === 0) {
            alert('Please select at least one target model.');
            return;
        }
        
        if (!confirm('This will overwrite existing pricing for the selected models. Continue?')) {
            return;
        }
        
        var formData = $(this).serialize();
        formData += '&action=copy_category_pricing&nonce=' + pri_admin_ajax.nonce;
        
        $('#pri-loading-overlay').show();
        
        $.ajax({
            url: pri_admin_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                $('#pri-loading-overlay').hide();
                
                if (response.success) {
                    $('<div class="notice notice-success is-dismissible"><p>' + response.data + '</p></div>')
                        .insertAfter('.wrap h1');
                    
                    // Reset form
                    $('#pri-copy-pricing-form')[0].reset();
                    
                    // Reload page to show updated pricing
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $('<div class="notice notice-error is-dismissible"><p>Error: ' + response.data + '</p></div>')
                        .insertAfter('.wrap h1');
                }
            },
            error: function() {
                $('#pri-loading-overlay').hide();
                $('<div class="notice notice-error is-dismissible"><p>Connection error. Please try again.</p></div>')
                    .insertAfter('.wrap h1');
            }
        });
    });
    
    // Remove notices on click
    $(document).on('click', '.notice-dismiss', function() {
        $(this).parent().fadeOut();
    });
});
</script>

<style>
.pri-pricing-header {
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    padding: 15px;
    margin: 20px 0;
    border-radius: 5px;
}

.pri-model-selection {
    margin: 20px 0;
}

.pri-model-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 10px;
}

.pri-model-tab {
    padding: 10px 15px;
    background: #f1f1f1;
    border: 1px solid #ccd0d4;
    text-decoration: none;
    color: #2c3e50;
    border-radius: 3px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.pri-model-tab:hover {
    background: #e1e1e1;
    color: #2c3e50;
}

.pri-model-tab.active {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
}

.pri-pricing-form {
    margin-top: 30px;
}

.pri-always-visible {
    color: #0073aa;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
}

.pri-bulk-actions {
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid #e1e1e1;
}

.pri-bulk-action-section {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 5px;
    margin: 15px 0;
}

.pri-bulk-action-section h3 {
    margin-top: 0;
}

.pri-bulk-action-section label {
    display: block;
    margin: 5px 0;
}

#pri-loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.8);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pri-loading-spinner .spinner {
    float: none;
    width: 40px;
    height: 40px;
}

@media (max-width: 768px) {
    .pri-model-tabs {
        flex-direction: column;
    }
    
    .pri-model-tab {
        text-align: center;
    }
}
</style>