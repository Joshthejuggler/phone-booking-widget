<?php
/**
 * Analytics REST API Endpoints
 * 
 * Provides secure API access to analytics data for the dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class PRI_Analytics_API {
    
    private $namespace = 'pri/v1';
    private $analytics_service;
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
        require_once PRI_PLUGIN_PATH . 'includes/class-analytics-service.php';
        $this->analytics_service = new PRI_Analytics_Service();
    }
    
    /**
     * Register all REST API routes
     */
    public function register_routes() {
        
        // Summary statistics
        register_rest_route($this->namespace, '/stats/summary', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_summary_stats'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_filter_args()
        ));
        
        // Requests by repair category
        register_rest_route($this->namespace, '/stats/requests-by-category', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_requests_by_category'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_filter_args()
        ));
        
        // Requests by iPhone model
        register_rest_route($this->namespace, '/stats/requests-by-model', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_requests_by_model'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_filter_args()
        ));
        
        // Trends over time
        register_rest_route($this->namespace, '/stats/trends', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_trends'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => array_merge($this->get_filter_args(), array(
                'interval' => array(
                    'description' => 'Time interval for grouping (day, week, month)',
                    'type' => 'string',
                    'default' => 'day',
                    'enum' => array('day', 'week', 'month')
                )
            ))
        ));
        
        // Status funnel
        register_rest_route($this->namespace, '/stats/funnel', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_status_funnel'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_filter_args()
        ));
        
        // Requests by source
        register_rest_route($this->namespace, '/stats/requests-by-source', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_requests_by_source'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_filter_args()
        ));
        
        // Revenue analytics
        register_rest_route($this->namespace, '/stats/revenue', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_revenue_stats'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_filter_args()
        ));
        
        // Top performers
        register_rest_route($this->namespace, '/stats/top-performers', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_top_performers'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_filter_args()
        ));
        
        // Filter options (for populating dropdowns)
        register_rest_route($this->namespace, '/stats/filter-options', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_filter_options'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));
    }
    
    /**
     * Get summary statistics
     */
    public function get_summary_stats($request) {
        $filters = $this->sanitize_filters($request->get_params());
        $stats = $this->analytics_service->get_summary_stats($filters);
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $stats,
            'filters_applied' => $filters
        ));
    }
    
    /**
     * Get requests by repair category
     */
    public function get_requests_by_category($request) {
        $filters = $this->sanitize_filters($request->get_params());
        $data = $this->analytics_service->get_requests_by_category($filters);
        
        // Format for Chart.js
        $chart_data = array(
            'labels' => array_map(function($item) { return $item['name']; }, $data),
            'datasets' => array(
                array(
                    'label' => 'Total Requests',
                    'data' => array_map(function($item) { return $item['requests']; }, $data),
                    'backgroundColor' => array('#007cba', '#00a32a', '#d63638', '#f56e28', '#8b5a8c', '#666')
                ),
                array(
                    'label' => 'Completed',
                    'data' => array_map(function($item) { return $item['completed']; }, $data),
                    'backgroundColor' => array('#005a87', '#007a1f', '#a02620', '#c54e1e', '#6a4269', '#444')
                )
            )
        );
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $data,
            'chart_data' => $chart_data,
            'filters_applied' => $filters
        ));
    }
    
    /**
     * Get requests by iPhone model
     */
    public function get_requests_by_model($request) {
        $filters = $this->sanitize_filters($request->get_params());
        $data = $this->analytics_service->get_requests_by_model($filters);
        
        // Format for Chart.js (top 10 models)
        $top_models = array_slice($data, 0, 10);
        $chart_data = array(
            'labels' => array_map(function($item) { return $item['model_name']; }, $top_models),
            'datasets' => array(
                array(
                    'label' => 'Requests',
                    'data' => array_map(function($item) { return $item['requests']; }, $top_models),
                    'backgroundColor' => '#007cba'
                )
            )
        );
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $data,
            'chart_data' => $chart_data,
            'filters_applied' => $filters
        ));
    }
    
    /**
     * Get trends over time
     */
    public function get_trends($request) {
        $filters = $this->sanitize_filters($request->get_params());
        $data = $this->analytics_service->get_trends($filters);
        
        // Format for Chart.js line chart
        $chart_data = array(
            'labels' => array_map(function($item) { return $item['period_label']; }, $data),
            'datasets' => array(
                array(
                    'label' => 'Requests',
                    'data' => array_map(function($item) { return $item['requests']; }, $data),
                    'borderColor' => '#007cba',
                    'backgroundColor' => 'rgba(0, 124, 186, 0.1)',
                    'fill' => true
                ),
                array(
                    'label' => 'Completed',
                    'data' => array_map(function($item) { return $item['completed']; }, $data),
                    'borderColor' => '#00a32a',
                    'backgroundColor' => 'rgba(0, 163, 42, 0.1)',
                    'fill' => false
                ),
                array(
                    'label' => 'Revenue ($)',
                    'data' => array_map(function($item) { return $item['revenue']; }, $data),
                    'borderColor' => '#d63638',
                    'backgroundColor' => 'rgba(214, 54, 56, 0.1)',
                    'fill' => false,
                    'yAxisID' => 'revenue'
                )
            )
        );
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $data,
            'chart_data' => $chart_data,
            'filters_applied' => $filters
        ));
    }
    
    /**
     * Get status funnel
     */
    public function get_status_funnel($request) {
        $filters = $this->sanitize_filters($request->get_params());
        $data = $this->analytics_service->get_status_funnel($filters);
        
        // Format for Chart.js doughnut chart
        $chart_data = array(
            'labels' => array_map(function($item) { return $item['label']; }, $data),
            'datasets' => array(
                array(
                    'data' => array_map(function($item) { return $item['count']; }, $data),
                    'backgroundColor' => array('#007cba', '#00a32a', '#f56e28', '#00a32a', '#d63638')
                )
            )
        );
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $data,
            'chart_data' => $chart_data,
            'filters_applied' => $filters
        ));
    }
    
    /**
     * Get requests by source
     */
    public function get_requests_by_source($request) {
        $filters = $this->sanitize_filters($request->get_params());
        $data = $this->analytics_service->get_requests_by_source($filters);
        
        // Format for Chart.js
        $chart_data = array(
            'labels' => array_map(function($item) { return $item['label']; }, $data),
            'datasets' => array(
                array(
                    'label' => 'Requests',
                    'data' => array_map(function($item) { return $item['requests']; }, $data),
                    'backgroundColor' => array('#007cba', '#00a32a', '#996633', '#8b5a8c', '#c92c2c', '#ff6900', '#4285f4', '#666')
                )
            )
        );
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $data,
            'chart_data' => $chart_data,
            'filters_applied' => $filters
        ));
    }
    
    /**
     * Get revenue statistics
     */
    public function get_revenue_stats($request) {
        $filters = $this->sanitize_filters($request->get_params());
        
        // Get category revenue data
        $category_data = $this->analytics_service->get_requests_by_category($filters);
        $model_data = $this->analytics_service->get_requests_by_model($filters);
        
        // Format revenue by category for charts
        $revenue_by_category = array(
            'labels' => array_map(function($item) { return $item['name']; }, $category_data),
            'datasets' => array(
                array(
                    'label' => 'Revenue ($)',
                    'data' => array_map(function($item) { return $item['revenue']; }, $category_data),
                    'backgroundColor' => array('#007cba', '#00a32a', '#d63638', '#f56e28', '#8b5a8c', '#666')
                )
            )
        );
        
        // Top revenue-generating models
        $top_revenue_models = array_slice($model_data, 0, 5);
        $revenue_by_model = array(
            'labels' => array_map(function($item) { return $item['model_name']; }, $top_revenue_models),
            'datasets' => array(
                array(
                    'label' => 'Revenue ($)',
                    'data' => array_map(function($item) { return $item['revenue']; }, $top_revenue_models),
                    'backgroundColor' => '#007cba'
                )
            )
        );
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => array(
                'by_category' => $category_data,
                'by_model' => $model_data
            ),
            'chart_data' => array(
                'by_category' => $revenue_by_category,
                'by_model' => $revenue_by_model
            ),
            'filters_applied' => $filters
        ));
    }
    
    /**
     * Get top performers
     */
    public function get_top_performers($request) {
        $filters = $this->sanitize_filters($request->get_params());
        $data = $this->analytics_service->get_top_performers($filters);
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $data,
            'filters_applied' => $filters
        ));
    }
    
    /**
     * Get filter options for dropdowns
     */
    public function get_filter_options($request) {
        global $wpdb;
        
        // Get repair categories
        $categories_table = $wpdb->prefix . 'pri_repair_categories';
        $categories = $wpdb->get_results("
            SELECT id, name 
            FROM $categories_table 
            WHERE is_active = 1 
            ORDER BY sort_order, name
        ");
        
        // Get iPhone models
        $models_table = $wpdb->prefix . 'pri_iphone_models';
        $models = $wpdb->get_results("
            SELECT id, model_name 
            FROM $models_table 
            WHERE is_active = 1 
            ORDER BY model_name
        ");
        
        // Get available sources
        $appointments_table = $wpdb->prefix . 'pri_appointments';
        $sources = $wpdb->get_results("
            SELECT DISTINCT COALESCE(source, 'online') as source
            FROM $appointments_table 
            WHERE source IS NOT NULL
            ORDER BY source
        ");
        
        $source_labels = array(
            'online' => 'Online Form',
            'walk-in' => 'Walk-in',
            'phone' => 'Phone Call',
            'referral' => 'Referral',
            'returning' => 'Returning Customer',
            'social_media' => 'Social Media',
            'google' => 'Google Search',
            'other' => 'Other'
        );
        
        $formatted_sources = array();
        foreach ($sources as $source) {
            $formatted_sources[] = array(
                'value' => $source->source,
                'label' => $source_labels[$source->source] ?? ucfirst($source->source)
            );
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => array(
                'repair_categories' => $categories,
                'iphone_models' => $models,
                'sources' => $formatted_sources
            )
        ));
    }
    
    /**
     * Check if user has admin permissions
     */
    public function check_admin_permissions() {
        return current_user_can('manage_options');
    }
    
    /**
     * Get common filter arguments for REST endpoints
     */
    private function get_filter_args() {
        return array(
            'date_from' => array(
                'description' => 'Start date for filtering (YYYY-MM-DD)',
                'type' => 'string',
                'format' => 'date'
            ),
            'date_to' => array(
                'description' => 'End date for filtering (YYYY-MM-DD)',
                'type' => 'string',
                'format' => 'date'
            ),
            'repair_category_ids' => array(
                'description' => 'Array of repair category IDs to filter by',
                'type' => 'array',
                'items' => array('type' => 'integer')
            ),
            'model_ids' => array(
                'description' => 'Array of iPhone model IDs to filter by',
                'type' => 'array',
                'items' => array('type' => 'integer')
            ),
            'sources' => array(
                'description' => 'Array of customer sources to filter by',
                'type' => 'array',
                'items' => array('type' => 'string')
            )
        );
    }
    
    /**
     * Sanitize and validate filter parameters
     */
    private function sanitize_filters($params) {
        $filters = array();
        
        if (!empty($params['date_from'])) {
            $filters['date_from'] = sanitize_text_field($params['date_from']);
        }
        
        if (!empty($params['date_to'])) {
            $filters['date_to'] = sanitize_text_field($params['date_to']);
        }
        
        if (!empty($params['repair_category_ids']) && is_array($params['repair_category_ids'])) {
            $filters['repair_category_ids'] = array_map('intval', $params['repair_category_ids']);
        }
        
        if (!empty($params['model_ids']) && is_array($params['model_ids'])) {
            $filters['model_ids'] = array_map('intval', $params['model_ids']);
        }
        
        if (!empty($params['sources']) && is_array($params['sources'])) {
            $filters['sources'] = array_map('sanitize_text_field', $params['sources']);
        }
        
        if (!empty($params['interval'])) {
            $valid_intervals = array('day', 'week', 'month');
            $interval = sanitize_text_field($params['interval']);
            $filters['interval'] = in_array($interval, $valid_intervals) ? $interval : 'day';
        }
        
        return $filters;
    }
}

// Initialize the API
new PRI_Analytics_API();