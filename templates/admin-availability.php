<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$availability_table = $wpdb->prefix . 'pri_availability';
$brands_table = $wpdb->prefix . 'pri_brands';

// Handle form submission
if (isset($_POST['save_availability']) && wp_verify_nonce($_POST['_wpnonce'], 'pri_availability')) {
    $brand_id = sanitize_text_field($_POST['brand_id'] ?? 'default');
    
    // Delete existing availability for this brand
    $wpdb->delete($availability_table, array('brand_id' => $brand_id));
    
    // Insert new availability based on checkbox selections
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    foreach ($days as $index => $day) {
        $day_slots = $_POST[$day . '_slots'] ?? [];
        
        foreach ($day_slots as $slot_value) {
            // Parse slot format "09:00-09:30"
            if (strpos($slot_value, '-') !== false) {
                list($start_time, $end_time) = explode('-', $slot_value);
                
                $wpdb->insert(
                    $availability_table,
                    array(
                        'day_of_week' => $index + 1, // 1 = Monday
                        'start_time' => sanitize_text_field($start_time) . ':00',
                        'end_time' => sanitize_text_field($end_time) . ':00',
                        'brand_id' => $brand_id,
                        'is_active' => 1
                    )
                );
            }
        }
    }
    
    $success_message = 'Availability schedule saved successfully!';
}

// Get current availability
$selected_brand = $_GET['brand'] ?? 'default';
$current_availability = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $availability_table WHERE brand_id = %s ORDER BY day_of_week, start_time",
    $selected_brand
));

// Group by day
$availability_by_day = [];
foreach ($current_availability as $slot) {
    $availability_by_day[$slot->day_of_week][] = $slot;
}

// Get brands
$brands = $wpdb->get_results("SELECT * FROM $brands_table ORDER BY name");

$day_names = [
    1 => 'Monday',
    2 => 'Tuesday', 
    3 => 'Wednesday',
    4 => 'Thursday',
    5 => 'Friday',
    6 => 'Saturday',
    7 => 'Sunday'
];
?>

<div class="wrap">
    <h1><?php _e('Manage Availability', 'phone-repair-intake'); ?></h1>
    
    <?php if (isset($success_message)): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($success_message); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Brand Selector -->
    <div class="card" style="margin-bottom: 20px;">
        <h2>Select Brand</h2>
        <form method="get">
            <input type="hidden" name="page" value="phone-repair-availability">
            <select name="brand" onchange="this.form.submit()">
                <?php foreach ($brands as $brand): ?>
                    <option value="<?php echo esc_attr($brand->id); ?>" <?php selected($selected_brand, $brand->id); ?>>
                        <?php echo esc_html($brand->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    
    <form method="post">
        <?php wp_nonce_field('pri_availability'); ?>
        <input type="hidden" name="brand_id" value="<?php echo esc_attr($selected_brand); ?>">
        
        <div class="card">
            <h2>Weekly Schedule</h2>
            <p>Set your available appointment times for each day of the week.</p>
            
            <div style="background: #f9f9f9; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                <h4>Quick Actions</h4>
                <button type="button" id="copy-to-weekdays" class="button">📋 Apply Monday's Schedule to All Weekdays (Tue-Fri)</button>
                <button type="button" id="clear-all" class="button" style="margin-left: 10px;">🗑️ Clear All</button>
                <button type="button" id="toggle-weekends" class="button" style="margin-left: 10px;">📅 Show/Hide Weekends</button>
            </div>
            
            <?php 
            // Generate 30-minute time slots
            $morning_slots = [];
            $afternoon_slots = [];
            
            // Morning: 9:00-12:00 (30-minute slots)
            $current = strtotime('09:00');
            $end = strtotime('12:00');
            while ($current < $end) {
                $start_time = date('H:i', $current);
                $end_time = date('H:i', $current + 1800); // 30 minutes
                $morning_slots[] = ['start' => $start_time, 'end' => $end_time];
                $current += 1800;
            }
            
            // Afternoon: 1:15-3:15 (30-minute slots)
            $current = strtotime('13:15');
            $end = strtotime('15:15');
            while ($current < $end) {
                $start_time = date('H:i', $current);
                $end_time = date('H:i', $current + 1800); // 30 minutes
                $afternoon_slots[] = ['start' => $start_time, 'end' => $end_time];
                $current += 1800;
            }
            
            $all_slots = array_merge($morning_slots, $afternoon_slots);
            ?>
            
            <?php foreach ($day_names as $day_num => $day_name): 
                $is_weekend = ($day_num == 6 || $day_num == 7); // Saturday or Sunday
                $weekend_class = $is_weekend ? 'weekend-day' : 'weekday';
                $weekend_style = $is_weekend ? 'display: none;' : '';
            ?>
                <div class="day-schedule <?php echo $weekend_class; ?>" 
                     style="margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 4px; <?php echo $weekend_style; ?>">
                    <h3><?php echo $day_name; ?></h3>
                    
                    <div class="time-slots-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                        <?php 
                        // Get existing slots for this day
                        $existing_slots = [];
                        if (isset($availability_by_day[$day_num])) {
                            foreach ($availability_by_day[$day_num] as $slot) {
                                $existing_slots[] = substr($slot->start_time, 0, 5) . '-' . substr($slot->end_time, 0, 5);
                            }
                        }
                        ?>
                        
                        <div>
                            <strong>Morning (9:00 AM - 12:00 PM)</strong>
                            <?php foreach ($morning_slots as $slot): 
                                $slot_key = $slot['start'] . '-' . $slot['end'];
                                $is_checked = in_array($slot_key, $existing_slots);
                            ?>
                                <label style="display: block; margin: 5px 0;">
                                    <input type="checkbox" 
                                           name="<?php echo strtolower($day_name); ?>_slots[]" 
                                           value="<?php echo $slot['start'] . '-' . $slot['end']; ?>"
                                           <?php checked($is_checked); ?>>
                                    <?php echo date('g:i A', strtotime($slot['start'])) . ' - ' . date('g:i A', strtotime($slot['end'])); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        
                        <div>
                            <strong>Afternoon (1:15 PM - 3:15 PM)</strong>
                            <?php foreach ($afternoon_slots as $slot): 
                                $slot_key = $slot['start'] . '-' . $slot['end'];
                                $is_checked = in_array($slot_key, $existing_slots);
                            ?>
                                <label style="display: block; margin: 5px 0;">
                                    <input type="checkbox" 
                                           name="<?php echo strtolower($day_name); ?>_slots[]" 
                                           value="<?php echo $slot['start'] . '-' . $slot['end']; ?>"
                                           <?php checked($is_checked); ?>>
                                    <?php echo date('g:i A', strtotime($slot['start'])) . ' - ' . date('g:i A', strtotime($slot['end'])); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <p class="submit">
            <input type="submit" name="save_availability" class="button-primary" value="Save Schedule">
        </p>
    </form>
</div>

<script>
const weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
const weekendDays = ['saturday', 'sunday'];

// Apply Monday's schedule to all weekdays
document.getElementById('copy-to-weekdays').addEventListener('click', function() {
    const mondayCheckboxes = document.querySelectorAll('input[name="monday_slots[]"]:checked');
    const mondayValues = Array.from(mondayCheckboxes).map(cb => cb.value);
    
    // Apply to Tuesday through Friday
    ['tuesday', 'wednesday', 'thursday', 'friday'].forEach(day => {
        // First uncheck all checkboxes for this day
        document.querySelectorAll(`input[name="${day}_slots[]"]`).forEach(cb => {
            cb.checked = false;
        });
        
        // Then check the ones that match Monday's selection
        mondayValues.forEach(value => {
            const checkbox = document.querySelector(`input[name="${day}_slots[]"][value="${value}"]`);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
    });
    
    alert('Applied Monday\'s schedule to Tuesday through Friday!');
});

// Clear all selections
document.getElementById('clear-all').addEventListener('click', function() {
    if (confirm('Are you sure you want to clear all selected time slots?')) {
        document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            cb.checked = false;
        });
    }
});

// Toggle weekend visibility
let weekendsVisible = false;
document.getElementById('toggle-weekends').addEventListener('click', function() {
    weekendsVisible = !weekendsVisible;
    
    document.querySelectorAll('.weekend-day').forEach(day => {
        day.style.display = weekendsVisible ? 'block' : 'none';
    });
    
    this.textContent = weekendsVisible ? '📅 Hide Weekends' : '📅 Show Weekends';
});
</script>

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

.day-schedule h3 {
    margin-top: 0;
    color: #23282d;
}

.time-slots-grid label {
    cursor: pointer;
    padding: 3px 0;
    border-radius: 3px;
    transition: background-color 0.2s;
}

.time-slots-grid label:hover {
    background-color: #f0f0f0;
}

.time-slots-grid input[type="checkbox"] {
    margin-right: 8px;
}
</style>