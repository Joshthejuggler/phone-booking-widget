<?php
/**
 * Plugin Name: Phone Repair Intake Form
 * Description: A WordPress plugin for phone repair customers to select iPhone models, view prices, and book appointments.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: phone-repair-intake
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PRI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PRI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PRI_VERSION', '1.0.0');

class PhoneRepairIntake {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('phone-repair-intake', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize admin functionality
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
            add_action('wp_ajax_save_iphone_model', array($this, 'save_iphone_model'));
            add_action('wp_ajax_delete_iphone_model', array($this, 'delete_iphone_model'));
        }
        
        // Initialize frontend functionality
        add_shortcode('phone_repair_form', array($this, 'display_repair_form'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        add_action('wp_ajax_get_price', array($this, 'get_price'));
        add_action('wp_ajax_nopriv_get_price', array($this, 'get_price'));
        add_action('wp_ajax_submit_appointment', array($this, 'submit_appointment'));
        add_action('wp_ajax_nopriv_submit_appointment', array($this, 'submit_appointment'));
        add_action('wp_ajax_get_available_slots', array($this, 'get_available_slots'));
        add_action('wp_ajax_nopriv_get_available_slots', array($this, 'get_available_slots'));
    }
    
    public function activate() {
        $this->create_tables();
    }
    
    public function deactivate() {
        // Cleanup if needed
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for iPhone models and prices
        $iphone_models_table = $wpdb->prefix . 'pri_iphone_models';
        $sql = "CREATE TABLE $iphone_models_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            model_name varchar(100) NOT NULL,
            price decimal(10,2) NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Table for appointment bookings
        $appointments_table = $wpdb->prefix . 'pri_appointments';
        $sql2 = "CREATE TABLE $appointments_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            iphone_model_id mediumint(9) NOT NULL,
            repair_type varchar(50) NOT NULL,
            repair_description text,
            customer_name varchar(100) NOT NULL,
            customer_email varchar(100) NOT NULL,
            customer_phone varchar(20) NOT NULL,
            customer_notes text,
            accepts_sms tinyint(1) DEFAULT 0,
            appointment_date date,
            appointment_time time,
            google_event_id varchar(255),
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY iphone_model_id (iphone_model_id)
        ) $charset_collate;";
        
        // Table for availability schedules
        $availability_table = $wpdb->prefix . 'pri_availability';
        $sql3 = "CREATE TABLE $availability_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            day_of_week tinyint(1) NOT NULL,
            start_time time NOT NULL,
            end_time time NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            brand_id varchar(50) DEFAULT 'default',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY day_brand (day_of_week, brand_id)
        ) $charset_collate;";
        
        // Table for brand configurations
        $brands_table = $wpdb->prefix . 'pri_brands';
        $sql4 = "CREATE TABLE $brands_table (
            id varchar(50) NOT NULL,
            name varchar(100) NOT NULL,
            primary_color varchar(7) DEFAULT '#007cba',
            secondary_color varchar(7) DEFAULT '#005a87',
            logo_url varchar(255),
            business_name varchar(100),
            contact_email varchar(100),
            contact_phone varchar(20),
            timezone varchar(50) DEFAULT 'America/Regina',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql2);
        dbDelta($sql3);
        dbDelta($sql4);
        
        // Insert default data
        $this->insert_default_models();
        $this->insert_default_availability();
        $this->insert_default_brand();
    }
    
    private function insert_default_models() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pri_iphone_models';
        
        // Check if models already exist
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($count > 0) {
            return;
        }
        
        $default_models = array(
            // iPhone 16 series (newest) - $280
            array('model_name' => 'iPhone 16', 'price' => 280.00),
            array('model_name' => 'iPhone 16e', 'price' => 170.00),
            
            // iPhone 15 series - $170 to $280
            array('model_name' => 'iPhone 15 Pro Max', 'price' => 280.00),
            array('model_name' => 'iPhone 15 Pro', 'price' => 280.00),
            array('model_name' => 'iPhone 15 Plus', 'price' => 250.00),
            array('model_name' => 'iPhone 15', 'price' => 170.00),
            
            // iPhone 14 series - $130 to $250
            array('model_name' => 'iPhone 14 Pro Max', 'price' => 250.00),
            array('model_name' => 'iPhone 14 Pro', 'price' => 250.00),
            array('model_name' => 'iPhone 14 Plus', 'price' => 130.00),
            array('model_name' => 'iPhone 14', 'price' => 130.00),
            
            // iPhone 13 series - $130 to $150
            array('model_name' => 'iPhone 13 Pro Max', 'price' => 130.00),
            array('model_name' => 'iPhone 13 Pro', 'price' => 150.00),
            array('model_name' => 'iPhone 13', 'price' => 130.00),
            array('model_name' => 'iPhone 13 Mini', 'price' => 150.00),
            
            // iPhone 12 series - $130
            array('model_name' => 'iPhone 12 Pro Max', 'price' => 130.00),
            array('model_name' => 'iPhone 12 Pro', 'price' => 130.00),
            array('model_name' => 'iPhone 12', 'price' => 130.00),
            array('model_name' => 'iPhone 12 Mini', 'price' => 130.00),
            
            // iPhone 11 series - $100 to $130
            array('model_name' => 'iPhone 11 Pro Max', 'price' => 130.00),
            array('model_name' => 'iPhone 11 Pro', 'price' => 130.00),
            array('model_name' => 'iPhone 11', 'price' => 100.00),
            
            // iPhone XS series - $110
            array('model_name' => 'iPhone XS Max', 'price' => 110.00),
            array('model_name' => 'iPhone XS', 'price' => 110.00),
            
            // iPhone XR - $100
            array('model_name' => 'iPhone XR', 'price' => 100.00),
            
            // iPhone X - $110
            array('model_name' => 'iPhone X', 'price' => 110.00),
            
            // iPhone 8 series - $90
            array('model_name' => 'iPhone 8 Plus', 'price' => 90.00),
            array('model_name' => 'iPhone 8', 'price' => 90.00),
            
            // iPhone SE (oldest) - $90
            array('model_name' => 'iPhone SE', 'price' => 90.00),
        );
        
        foreach ($default_models as $model) {
            $wpdb->insert($table_name, $model);
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Phone Repair', 'phone-repair-intake'),
            __('Phone Repair', 'phone-repair-intake'),
            'manage_options',
            'phone-repair-intake',
            array($this, 'admin_page'),
            'dashicons-smartphone',
            30
        );
        
        add_submenu_page(
            'phone-repair-intake',
            __('iPhone Models', 'phone-repair-intake'),
            __('iPhone Models', 'phone-repair-intake'),
            'manage_options',
            'phone-repair-models',
            array($this, 'models_admin_page')
        );
        
        add_submenu_page(
            'phone-repair-intake',
            __('Appointments', 'phone-repair-intake'),
            __('Appointments', 'phone-repair-intake'),
            'manage_options',
            'phone-repair-appointments',
            array($this, 'appointments_admin_page')
        );
        
        add_submenu_page(
            'phone-repair-intake',
            __('Availability', 'phone-repair-intake'),
            __('Availability', 'phone-repair-intake'),
            'manage_options',
            'phone-repair-availability',
            array($this, 'availability_admin_page')
        );
        
        add_submenu_page(
            'phone-repair-intake',
            __('Calendar Settings', 'phone-repair-intake'),
            __('Calendar Settings', 'phone-repair-intake'),
            'manage_options',
            'phone-repair-calendar-settings',
            array($this, 'calendar_settings_admin_page')
        );
        
        add_submenu_page(
            'phone-repair-intake',
            __('Booking Test', 'phone-repair-intake'),
            __('Booking Test', 'phone-repair-intake'),
            'manage_options',
            'phone-repair-booking-test',
            array($this, 'booking_test_admin_page')
        );
        
        add_submenu_page(
            'phone-repair-intake',
            __('Calendar Test', 'phone-repair-intake'),
            __('Calendar Test', 'phone-repair-intake'),
            'manage_options',
            'phone-repair-calendar-test',
            array($this, 'calendar_test_admin_page')
        );
    }
    
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'phone-repair') !== false) {
            wp_enqueue_script('pri-admin-js', PRI_PLUGIN_URL . 'assets/admin.js', array('jquery'), PRI_VERSION, true);
            wp_enqueue_style('pri-admin-css', PRI_PLUGIN_URL . 'assets/admin.css', array(), PRI_VERSION);
            wp_localize_script('pri-admin-js', 'pri_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('pri_admin_nonce')
            ));
        }
    }
    
    public function frontend_enqueue_scripts() {
        wp_enqueue_script('pri-frontend-js', PRI_PLUGIN_URL . 'assets/frontend.js', array('jquery'), PRI_VERSION, true);
        wp_enqueue_style('pri-frontend-css', PRI_PLUGIN_URL . 'assets/frontend.css', array(), PRI_VERSION);
        wp_localize_script('pri-frontend-js', 'pri_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pri_frontend_nonce')
        ));
    }
    
    public function admin_page() {
        include PRI_PLUGIN_PATH . 'templates/admin-dashboard.php';
    }
    
    public function models_admin_page() {
        include PRI_PLUGIN_PATH . 'templates/admin-models.php';
    }
    
    public function appointments_admin_page() {
        include PRI_PLUGIN_PATH . 'templates/admin-appointments.php';
    }
    
    public function availability_admin_page() {
        include PRI_PLUGIN_PATH . 'templates/admin-availability.php';
    }
    
    public function calendar_settings_admin_page() {
        include PRI_PLUGIN_PATH . 'templates/admin-calendar-settings.php';
    }
    
    public function booking_test_admin_page() {
        include PRI_PLUGIN_PATH . 'templates/admin-booking-test.php';
    }
    
    public function calendar_test_admin_page() {
        include PRI_PLUGIN_PATH . 'templates/admin-calendar-test.php';
    }
    
    public function update_prices_to_correct_values() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pri_iphone_models';
        
        // Correct pricing structure (newest to oldest)
        $correct_prices = array(
            // iPhone 16 series (newest)
            'iPhone 16' => 280.00,
            'iPhone 16e' => 170.00,
            
            // iPhone 15 series
            'iPhone 15 Pro Max' => 280.00,
            'iPhone 15 Pro' => 280.00,
            'iPhone 15 Plus' => 250.00,
            'iPhone 15' => 170.00,
            
            // iPhone 14 series
            'iPhone 14 Pro Max' => 250.00,
            'iPhone 14 Pro' => 250.00,
            'iPhone 14 Plus' => 130.00,
            'iPhone 14' => 130.00,
            
            // iPhone 13 series
            'iPhone 13 Pro Max' => 130.00,
            'iPhone 13 Pro' => 150.00,
            'iPhone 13' => 130.00,
            'iPhone 13 Mini' => 150.00,
            
            // iPhone 12 series
            'iPhone 12 Pro Max' => 130.00,
            'iPhone 12 Pro' => 130.00,
            'iPhone 12' => 130.00,
            'iPhone 12 Mini' => 130.00,
            
            // iPhone 11 series
            'iPhone 11 Pro Max' => 130.00,
            'iPhone 11 Pro' => 130.00,
            'iPhone 11' => 100.00,
            
            // iPhone XS series
            'iPhone XS Max' => 110.00,
            'iPhone XS' => 110.00,
            
            // iPhone XR
            'iPhone XR' => 100.00,
            
            // iPhone X
            'iPhone X' => 110.00,
            
            // iPhone 8 series
            'iPhone 8 Plus' => 90.00,
            'iPhone 8' => 90.00,
            
            // iPhone SE (oldest)
            'iPhone SE' => 90.00,
        );
        
        $updated = 0;
        foreach ($correct_prices as $model_name => $correct_price) {
            $result = $wpdb->update(
                $table_name,
                array('price' => $correct_price),
                array('model_name' => $model_name),
                array('%f'),
                array('%s')
            );
            
            if ($result !== false) {
                $updated++;
            }
            
            // Insert if doesn't exist
            if ($result === 0) {
                $wpdb->insert(
                    $table_name,
                    array(
                        'model_name' => $model_name,
                        'price' => $correct_price,
                        'is_active' => 1
                    ),
                    array('%s', '%f', '%d')
                );
            }
        }
        
        return $updated;
    }
    
    private function insert_default_availability() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pri_availability';
        
        // Check if availability slots already exist
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($count > 0) {
            return;
        }
        
        // Default availability: Monday to Friday, 9:30am, 1:00pm, 1:30pm
        $default_availability = array(
            // Monday (1)
            array('day_of_week' => 1, 'start_time' => '09:30:00', 'end_time' => '10:00:00'),
            array('day_of_week' => 1, 'start_time' => '13:00:00', 'end_time' => '13:30:00'),
            array('day_of_week' => 1, 'start_time' => '13:30:00', 'end_time' => '14:00:00'),
            
            // Tuesday (2)
            array('day_of_week' => 2, 'start_time' => '09:30:00', 'end_time' => '10:00:00'),
            array('day_of_week' => 2, 'start_time' => '13:00:00', 'end_time' => '13:30:00'),
            array('day_of_week' => 2, 'start_time' => '13:30:00', 'end_time' => '14:00:00'),
            
            // Wednesday (3)
            array('day_of_week' => 3, 'start_time' => '09:30:00', 'end_time' => '10:00:00'),
            array('day_of_week' => 3, 'start_time' => '13:00:00', 'end_time' => '13:30:00'),
            array('day_of_week' => 3, 'start_time' => '13:30:00', 'end_time' => '14:00:00'),
            
            // Thursday (4)
            array('day_of_week' => 4, 'start_time' => '09:30:00', 'end_time' => '10:00:00'),
            array('day_of_week' => 4, 'start_time' => '13:00:00', 'end_time' => '13:30:00'),
            array('day_of_week' => 4, 'start_time' => '13:30:00', 'end_time' => '14:00:00'),
            
            // Friday (5)
            array('day_of_week' => 5, 'start_time' => '09:30:00', 'end_time' => '10:00:00'),
            array('day_of_week' => 5, 'start_time' => '13:00:00', 'end_time' => '13:30:00'),
            array('day_of_week' => 5, 'start_time' => '13:30:00', 'end_time' => '14:00:00'),
        );
        
        foreach ($default_availability as $slot) {
            $wpdb->insert(
                $table_name,
                array(
                    'day_of_week' => $slot['day_of_week'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'brand_id' => 'default'
                )
            );
        }
    }
    
    private function insert_default_brand() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pri_brands';
        
        // Check if default brand exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE id = %s",
            'default'
        ));
        
        if ($exists > 0) {
            return;
        }
        
        // Insert default brand
        $wpdb->insert(
            $table_name,
            array(
                'id' => 'default',
                'name' => 'Phone Repair',
                'business_name' => 'Phone Repair Service',
                'contact_email' => get_option('admin_email'),
                'primary_color' => '#007cba',
                'secondary_color' => '#005a87',
                'timezone' => 'America/Regina'
            )
        );
    }
    
    public function display_repair_form($atts) {
        ob_start();
        include PRI_PLUGIN_PATH . 'templates/repair-form.php';
        return ob_get_clean();
    }
    
    public function get_price() {
        check_ajax_referer('pri_frontend_nonce', 'nonce');
        
        $model_id = intval($_POST['model_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'pri_iphone_models';
        
        $model = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND is_active = 1",
            $model_id
        ));
        
        if ($model) {
            wp_send_json_success(array(
                'price' => number_format($model->price, 2),
                'model_name' => $model->model_name
            ));
        } else {
            wp_send_json_error('Model not found');
        }
    }
    
    public function check_and_repair_tables() {
        global $wpdb;
        
        $appointments_table = $wpdb->prefix . 'pri_appointments';
        
        // Check if table exists and has all required columns
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$appointments_table'") === $appointments_table;
        
        if (!$table_exists) {
            // Table doesn't exist, create it
            $this->create_tables();
            return;
        }
        
        // Check if all columns exist
        $columns = $wpdb->get_results("DESCRIBE $appointments_table");
        $column_names = array_column($columns, 'Field');
        
        $required_columns = ['id', 'iphone_model_id', 'repair_type', 'repair_description', 'customer_name', 'customer_email', 'customer_phone', 'customer_notes', 'accepts_sms', 'appointment_date', 'appointment_time', 'google_event_id', 'status', 'created_at'];
        
        foreach ($required_columns as $required_column) {
            if (!in_array($required_column, $column_names)) {
                // Missing column, recreate table
                $wpdb->query("DROP TABLE IF EXISTS $appointments_table");
                $this->create_tables();
                return;
            }
        }
    }
    
    public function submit_appointment() {
        check_ajax_referer('pri_frontend_nonce', 'nonce');
        
        // Ensure database tables are properly set up
        $this->check_and_repair_tables();
        
        $model_id = intval($_POST['model_id']);
        $repair_type = sanitize_text_field($_POST['repair_type']);
        $repair_description = sanitize_textarea_field($_POST['repair_description']);
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $customer_notes = sanitize_textarea_field($_POST['customer_notes']);
        $accepts_sms = isset($_POST['accepts_sms']) ? 1 : 0;
        $appointment_date = sanitize_text_field($_POST['appointment_date']);
        $appointment_time = sanitize_text_field($_POST['appointment_time']);
        
        // Validate required fields
        if (empty($name) || empty($email) || empty($phone) || empty($model_id) || empty($repair_type) || empty($appointment_date) || empty($appointment_time)) {
            wp_send_json_error('All fields including appointment date and time are required');
        }
        
        if (!is_email($email)) {
            wp_send_json_error('Invalid email address');
        }
        
        // Get model information for pricing
        global $wpdb;
        $models_table = $wpdb->prefix . 'pri_iphone_models';
        $model = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $models_table WHERE id = %d AND is_active = 1",
            $model_id
        ));
        
        if (!$model) {
            wp_send_json_error('Invalid iPhone model selected');
        }
        
        // Create Google Calendar event
        $google_event_id = null;
        require_once PRI_PLUGIN_PATH . 'google-calendar-integration.php';
        $calendar = new GoogleCalendarIntegration();
        
        if ($calendar) {
            $appointment_data = [
                'customer_name' => $name,
                'customer_email' => $email,
                'customer_phone' => $phone,
                'model_name' => $model->model_name,
                'repair_type' => $repair_type,
                'repair_description' => $repair_description,
                'customer_notes' => $customer_notes,
                'price' => $model->price,
                'start_time' => $appointment_date . 'T' . $appointment_time . ':00',
                'end_time' => date('Y-m-d\TH:i:s', strtotime($appointment_date . ' ' . $appointment_time) + 1800) // 30 minutes later
            ];
            
            $calendar_result = $calendar->create_appointment_event($appointment_data);
            
            if ($calendar_result && $calendar_result['success']) {
                $google_event_id = $calendar_result['event_id'];
            } else {
                error_log('Google Calendar event creation failed: ' . ($calendar_result['error'] ?? 'Unknown error'));
            }
        }
        
        // Save to database
        $appointments_table = $wpdb->prefix . 'pri_appointments';
        
        $result = $wpdb->insert(
            $appointments_table,
            array(
                'iphone_model_id' => $model_id,
                'repair_type' => $repair_type,
                'repair_description' => $repair_description,
                'customer_name' => $name,
                'customer_email' => $email,
                'customer_phone' => $phone,
                'customer_notes' => $customer_notes,
                'accepts_sms' => $accepts_sms,
                'appointment_date' => $appointment_date,
                'appointment_time' => $appointment_time,
                'google_event_id' => $google_event_id,
                'status' => 'pending'
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            $success_message = 'Appointment booked successfully for ' . date('M j, Y \a\t g:i A', strtotime($appointment_date . ' ' . $appointment_time)) . '!';
            if ($google_event_id) {
                $success_message .= ' Calendar event created.';
            }
            wp_send_json_success($success_message);
        } else {
            // Get the last database error for debugging
            $error_message = $wpdb->last_error ? $wpdb->last_error : 'Database insertion failed';
            error_log('Phone Repair Plugin DB Error: ' . $error_message);
            error_log('Form Data: ' . print_r($_POST, true));
            wp_send_json_error('Failed to book appointment: ' . $error_message);
        }
    }
    
    public function save_iphone_model() {
        check_ajax_referer('pri_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $id = intval($_POST['id']);
        $model_name = sanitize_text_field($_POST['model_name']);
        $price = floatval($_POST['price']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'pri_iphone_models';
        
        if ($id > 0) {
            // Update existing model
            $result = $wpdb->update(
                $table_name,
                array(
                    'model_name' => $model_name,
                    'price' => $price,
                    'is_active' => $is_active
                ),
                array('id' => $id),
                array('%s', '%f', '%d'),
                array('%d')
            );
        } else {
            // Insert new model
            $result = $wpdb->insert(
                $table_name,
                array(
                    'model_name' => $model_name,
                    'price' => $price,
                    'is_active' => $is_active
                ),
                array('%s', '%f', '%d')
            );
        }
        
        if ($result !== false) {
            wp_send_json_success('Model saved successfully');
        } else {
            wp_send_json_error('Failed to save model');
        }
    }
    
    public function delete_iphone_model() {
        check_ajax_referer('pri_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $id = intval($_POST['id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'pri_iphone_models';
        
        $result = $wpdb->delete($table_name, array('id' => $id), array('%d'));
        
        if ($result) {
            wp_send_json_success('Model deleted successfully');
        } else {
            wp_send_json_error('Failed to delete model');
        }
    }
    
    public function get_available_slots() {
        check_ajax_referer('pri_frontend_nonce', 'nonce');
        
        $date = sanitize_text_field($_POST['date']);
        $brand_id = sanitize_text_field($_POST['brand_id'] ?? 'default');
        
        if (!$date) {
            wp_send_json_error('Date is required');
        }
        
        require_once PRI_PLUGIN_PATH . 'google-calendar-integration.php';
        $calendar = new GoogleCalendarIntegration();
        $slots = $calendar->get_available_slots($date, $brand_id);
        
        // Format slots for frontend
        $formatted_slots = [];
        foreach ($slots as $slot) {
            $formatted_slots[] = [
                'start_time' => $slot['start'],
                'end_time' => $slot['end'],
                'display_time' => date('g:i A', strtotime($slot['datetime'])),
                'datetime' => $slot['datetime'],
                'available' => true
            ];
        }
        
        wp_send_json_success($formatted_slots);
    }
}

// Initialize the plugin
new PhoneRepairIntake();