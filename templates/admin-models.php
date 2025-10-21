<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'pri_iphone_models';

// Get all iPhone models ordered by release (newest to oldest)
$models = $wpdb->get_results("
    SELECT * FROM $table_name 
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

<div class="wrap">
    <h1><?php _e('iPhone Models & Prices', 'phone-repair-intake'); ?></h1>
    
    <div class="pri-models-header">
        <button id="pri-add-model-btn" class="button button-primary"><?php _e('Add New iPhone Model', 'phone-repair-intake'); ?></button>
    </div>
    
    <!-- Add/Edit Model Modal -->
    <div id="pri-model-modal" class="pri-modal" style="display: none;">
        <div class="pri-modal-content">
            <div class="pri-modal-header">
                <h2 id="pri-modal-title"><?php _e('Add iPhone Model', 'phone-repair-intake'); ?></h2>
                <span class="pri-modal-close">&times;</span>
            </div>
            <div class="pri-modal-body">
                <form id="pri-model-form">
                    <input type="hidden" id="model-id" name="id" value="0">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="model-name"><?php _e('iPhone Model Name', 'phone-repair-intake'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="model-name" name="model_name" class="regular-text" required>
                                <p class="description"><?php _e('e.g., iPhone 15 Pro Max', 'phone-repair-intake'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="model-screen-price"><?php _e('Screen Repair Price', 'phone-repair-intake'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="model-screen-price" name="price" class="regular-text" required>
                                <p class="description"><?php _e('Enter the screen repair price in dollars (e.g., 280.00)', 'phone-repair-intake'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="model-battery-price"><?php _e('Battery Repair Price', 'phone-repair-intake'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="model-battery-price" name="battery_price" class="regular-text" placeholder="0.00">
                                <p class="description"><?php _e('Leave blank to hide battery repair option', 'phone-repair-intake'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="model-charging-price"><?php _e('Charging Repair Price', 'phone-repair-intake'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="model-charging-price" name="charging_price" class="regular-text" placeholder="0.00">
                                <p class="description"><?php _e('Leave blank to hide charging repair option', 'phone-repair-intake'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="model-camera-price"><?php _e('Camera Repair Price', 'phone-repair-intake'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="model-camera-price" name="camera_price" class="regular-text" placeholder="0.00">
                                <p class="description"><?php _e('Leave blank to hide camera repair option', 'phone-repair-intake'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="model-water-price"><?php _e('Water Damage Repair Price', 'phone-repair-intake'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="model-water-price" name="water_price" class="regular-text" placeholder="0.00">
                                <p class="description"><?php _e('Leave blank to hide water damage repair option', 'phone-repair-intake'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="model-active"><?php _e('Status', 'phone-repair-intake'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="model-active" name="is_active" value="1" checked>
                                    <?php _e('Active (visible to customers)', 'phone-repair-intake'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Save Model', 'phone-repair-intake'); ?></button>
                        <button type="button" class="button pri-modal-close"><?php _e('Cancel', 'phone-repair-intake'); ?></button>
                    </p>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Models List -->
    <div class="pri-models-list">
        <?php if (empty($models)): ?>
            <div class="notice notice-info">
                <p><?php _e('No iPhone models found. Add some models to get started.', 'phone-repair-intake'); ?></p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="manage-column"><?php _e('iPhone Model', 'phone-repair-intake'); ?></th>
                        <th class="manage-column"><?php _e('Status', 'phone-repair-intake'); ?></th>
                        <th class="manage-column"><?php _e('Created', 'phone-repair-intake'); ?></th>
                        <th class="manage-column"><?php _e('Actions', 'phone-repair-intake'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($models as $model): ?>
                        <tr data-model-id="<?php echo $model->id; ?>">
                            <td>
                                <strong><?php echo esc_html($model->model_name); ?></strong>
                            </td>
                            <td>
                                <?php if ($model->is_active): ?>
                                    <span class="pri-status-active"><?php _e('Active', 'phone-repair-intake'); ?></span>
                                <?php else: ?>
                                    <span class="pri-status-inactive"><?php _e('Inactive', 'phone-repair-intake'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo date('M j, Y', strtotime($model->created_at)); ?>
                            </td>
                            <td>
                                <button class="button button-small pri-edit-model" 
                                        data-id="<?php echo $model->id; ?>"
                                        data-name="<?php echo esc_attr($model->model_name); ?>"
                                        data-price="<?php echo $model->price; ?>"
                                        data-battery-price="<?php echo $model->battery_price ?? ''; ?>"
                                        data-charging-price="<?php echo $model->charging_price ?? ''; ?>"
                                        data-camera-price="<?php echo $model->camera_price ?? ''; ?>"
                                        data-water-price="<?php echo $model->water_price ?? ''; ?>"
                                        data-active="<?php echo $model->is_active; ?>">
                                    <?php _e('Edit', 'phone-repair-intake'); ?>
                                </button>
                                <button class="button button-small button-link-delete pri-delete-model" 
                                        data-id="<?php echo $model->id; ?>"
                                        data-name="<?php echo esc_attr($model->model_name); ?>">
                                    <?php _e('Delete', 'phone-repair-intake'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Loading overlay -->
<div id="pri-loading-overlay" style="display: none;">
    <div class="pri-loading-spinner">
        <div class="spinner is-active"></div>
    </div>
</div>