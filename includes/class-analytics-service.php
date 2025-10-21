<?php
/**
 * Analytics Service
 * 
 * Handles all statistical queries and calculations for repair data
 */

if (!defined('ABSPATH')) {
    exit;
}

class PRI_Analytics_Service {
    
    private $wpdb;
    private $appointments_table;
    private $models_table;
    private $categories_table;
    private $pricing_table;
    private $daily_table;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->appointments_table = $wpdb->prefix . 'pri_appointments';
        $this->models_table = $wpdb->prefix . 'pri_iphone_models';
        $this->categories_table = $wpdb->prefix . 'pri_repair_categories';
        $this->pricing_table = $wpdb->prefix . 'pri_model_category_pricing';
        $this->daily_table = $wpdb->prefix . 'pri_analytics_daily';
    }
    
    /**
     * Get summary statistics for dashboard
     */
    public function get_summary_stats($filters = array()) {
        $where_clause = $this->build_where_clause($filters);
        
        // Total requests
        $total_requests = $this->wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$this->appointments_table} a 
            {$where_clause}
        ");
        
        // Booked (confirmed, in_progress, completed)
        $booked_count = $this->wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$this->appointments_table} a 
            {$where_clause} 
            AND a.status IN ('confirmed', 'in_progress', 'completed')
        ");
        
        // Completed
        $completed_count = $this->wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$this->appointments_table} a 
            {$where_clause} 
            AND a.status = 'completed'
        ");
        
        // Revenue (completed appointments only)
        $total_revenue = $this->wpdb->get_var("
            SELECT SUM(COALESCE(a.price_snapshot, 0)) 
            FROM {$this->appointments_table} a 
            {$where_clause} 
            AND a.status = 'completed'
        ") ?: 0;
        
        // Calculate rates
        $conversion_rate = $total_requests > 0 ? ($booked_count / $total_requests) * 100 : 0;
        $completion_rate = $booked_count > 0 ? ($completed_count / $booked_count) * 100 : 0;
        $avg_revenue_per_booking = $completed_count > 0 ? $total_revenue / $completed_count : 0;
        
        return array(
            'total_requests' => intval($total_requests),
            'booked_count' => intval($booked_count),
            'completed_count' => intval($completed_count),
            'cancelled_count' => intval($total_requests - $booked_count),
            'conversion_rate' => round($conversion_rate, 1),
            'completion_rate' => round($completion_rate, 1),
            'total_revenue' => floatval($total_revenue),
            'avg_revenue_per_booking' => round($avg_revenue_per_booking, 2)
        );
    }
    
    /**
     * Get requests by repair category
     */
    public function get_requests_by_category($filters = array()) {
        $where_clause = $this->build_where_clause($filters);
        
        $results = $this->wpdb->get_results("
            SELECT 
                c.id,
                c.name,
                c.slug,
                COUNT(*) as requests,
                SUM(CASE WHEN a.status IN ('confirmed', 'in_progress', 'completed') THEN 1 ELSE 0 END) as booked,
                SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN a.status = 'completed' THEN COALESCE(a.price_snapshot, 0) ELSE 0 END) as revenue
            FROM {$this->appointments_table} a
            LEFT JOIN {$this->categories_table} c ON a.repair_category_id = c.id
            {$where_clause}
            GROUP BY c.id, c.name, c.slug
            ORDER BY requests DESC
        ");
        
        $formatted = array();
        $icons = array(
            'screen' => '📱',
            'battery' => '🔋', 
            'charging' => '⚡',
            'camera' => '📸',
            'water' => '💧',
            'other' => '❓'
        );
        
        foreach ($results as $row) {
            $formatted[] = array(
                'id' => intval($row->id),
                'name' => $row->name ?: 'Unknown',
                'slug' => $row->slug ?: 'unknown',
                'icon' => $icons[$row->slug] ?? '❓',
                'requests' => intval($row->requests),
                'booked' => intval($row->booked),
                'completed' => intval($row->completed),
                'revenue' => floatval($row->revenue),
                'conversion_rate' => $row->requests > 0 ? round(($row->booked / $row->requests) * 100, 1) : 0
            );
        }
        
        return $formatted;
    }
    
    /**
     * Get requests by iPhone model
     */
    public function get_requests_by_model($filters = array()) {
        $where_clause = $this->build_where_clause($filters);
        
        $results = $this->wpdb->get_results("
            SELECT 
                m.id,
                m.model_name,
                COUNT(*) as requests,
                SUM(CASE WHEN a.status IN ('confirmed', 'in_progress', 'completed') THEN 1 ELSE 0 END) as booked,
                SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN a.status = 'completed' THEN COALESCE(a.price_snapshot, 0) ELSE 0 END) as revenue
            FROM {$this->appointments_table} a
            LEFT JOIN {$this->models_table} m ON a.iphone_model_id = m.id
            {$where_clause}
            GROUP BY m.id, m.model_name
            ORDER BY requests DESC
        ");
        
        $formatted = array();
        foreach ($results as $row) {
            $formatted[] = array(
                'id' => intval($row->id),
                'model_name' => $row->model_name ?: 'Unknown Model',
                'requests' => intval($row->requests),
                'booked' => intval($row->booked),
                'completed' => intval($row->completed),
                'revenue' => floatval($row->revenue),
                'conversion_rate' => $row->requests > 0 ? round(($row->booked / $row->requests) * 100, 1) : 0
            );
        }
        
        return $formatted;
    }
    
    /**
     * Get trends over time
     */
    public function get_trends($filters = array()) {
        $interval = $filters['interval'] ?? 'day';
        $where_clause = $this->build_where_clause($filters);
        
        $date_format = $this->get_date_format_for_interval($interval);
        $group_by = $this->get_group_by_for_interval($interval);
        
        $results = $this->wpdb->get_results("
            SELECT 
                {$date_format} as period,
                COUNT(*) as requests,
                SUM(CASE WHEN a.status IN ('confirmed', 'in_progress', 'completed') THEN 1 ELSE 0 END) as booked,
                SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN a.status = 'completed' THEN COALESCE(a.price_snapshot, 0) ELSE 0 END) as revenue
            FROM {$this->appointments_table} a
            {$where_clause}
            GROUP BY {$group_by}
            ORDER BY period ASC
        ");
        
        $formatted = array();
        foreach ($results as $row) {
            $formatted[] = array(
                'period' => $row->period,
                'period_label' => $this->format_period_label($row->period, $interval),
                'requests' => intval($row->requests),
                'booked' => intval($row->booked),
                'completed' => intval($row->completed),
                'revenue' => floatval($row->revenue)
            );
        }
        
        return $formatted;
    }
    
    /**
     * Get status funnel data
     */
    public function get_status_funnel($filters = array()) {
        $where_clause = $this->build_where_clause($filters);
        
        $results = $this->wpdb->get_results("
            SELECT 
                a.status,
                COUNT(*) as count
            FROM {$this->appointments_table} a
            {$where_clause}
            GROUP BY a.status
            ORDER BY 
                CASE a.status 
                    WHEN 'pending' THEN 1
                    WHEN 'confirmed' THEN 2
                    WHEN 'in_progress' THEN 3
                    WHEN 'completed' THEN 4
                    WHEN 'cancelled' THEN 5
                    ELSE 6
                END
        ");
        
        $formatted = array();
        $status_labels = array(
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled'
        );
        
        foreach ($results as $row) {
            $formatted[] = array(
                'status' => $row->status,
                'label' => $status_labels[$row->status] ?? ucfirst($row->status),
                'count' => intval($row->count)
            );
        }
        
        return $formatted;
    }
    
    /**
     * Get requests by source
     */
    public function get_requests_by_source($filters = array()) {
        $where_clause = $this->build_where_clause($filters);
        
        $results = $this->wpdb->get_results("
            SELECT 
                COALESCE(a.source, 'online') as source,
                COUNT(*) as requests,
                SUM(CASE WHEN a.status IN ('confirmed', 'in_progress', 'completed') THEN 1 ELSE 0 END) as booked,
                SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN a.status = 'completed' THEN COALESCE(a.price_snapshot, 0) ELSE 0 END) as revenue
            FROM {$this->appointments_table} a
            {$where_clause}
            GROUP BY source
            ORDER BY requests DESC
        ");
        
        $formatted = array();
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
        
        foreach ($results as $row) {
            $formatted[] = array(
                'source' => $row->source,
                'label' => $source_labels[$row->source] ?? ucfirst($row->source),
                'requests' => intval($row->requests),
                'booked' => intval($row->booked),
                'completed' => intval($row->completed),
                'revenue' => floatval($row->revenue),
                'conversion_rate' => $row->requests > 0 ? round(($row->booked / $row->requests) * 100, 1) : 0
            );
        }
        
        return $formatted;
    }
    
    /**
     * Build WHERE clause based on filters
     */
    private function build_where_clause($filters) {
        $conditions = array('1=1'); // Always true base condition
        
        // Date range filter
        if (!empty($filters['date_from'])) {
            $date_from = sanitize_text_field($filters['date_from']);
            $conditions[] = $this->wpdb->prepare("a.created_at >= %s", $date_from);
        }
        
        if (!empty($filters['date_to'])) {
            $date_to = sanitize_text_field($filters['date_to']);
            $conditions[] = $this->wpdb->prepare("a.created_at <= %s", $date_to . ' 23:59:59');
        }
        
        // Default to last 30 days if no date range specified
        if (empty($filters['date_from']) && empty($filters['date_to'])) {
            $conditions[] = "a.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        }
        
        // Category filter
        if (!empty($filters['repair_category_ids']) && is_array($filters['repair_category_ids'])) {
            $category_ids = array_map('intval', $filters['repair_category_ids']);
            $placeholders = implode(',', array_fill(0, count($category_ids), '%d'));
            $conditions[] = $this->wpdb->prepare("a.repair_category_id IN ($placeholders)", ...$category_ids);
        }
        
        // Model filter
        if (!empty($filters['model_ids']) && is_array($filters['model_ids'])) {
            $model_ids = array_map('intval', $filters['model_ids']);
            $placeholders = implode(',', array_fill(0, count($model_ids), '%d'));
            $conditions[] = $this->wpdb->prepare("a.iphone_model_id IN ($placeholders)", ...$model_ids);
        }
        
        // Source filter
        if (!empty($filters['sources']) && is_array($filters['sources'])) {
            $sources = array_map(array($this->wpdb, 'prepare'), array_fill(0, count($filters['sources']), '%s'), $filters['sources']);
            $conditions[] = "a.source IN (" . implode(',', $sources) . ")";
        }
        
        return 'WHERE ' . implode(' AND ', $conditions);
    }
    
    /**
     * Get date format for SQL based on interval
     */
    private function get_date_format_for_interval($interval) {
        switch ($interval) {
            case 'week':
                return "DATE_FORMAT(a.created_at, '%Y-%u')";
            case 'month':
                return "DATE_FORMAT(a.created_at, '%Y-%m')";
            case 'day':
            default:
                return "DATE(a.created_at)";
        }
    }
    
    /**
     * Get GROUP BY clause for interval
     */
    private function get_group_by_for_interval($interval) {
        switch ($interval) {
            case 'week':
                return "YEAR(a.created_at), WEEK(a.created_at)";
            case 'month':
                return "YEAR(a.created_at), MONTH(a.created_at)";
            case 'day':
            default:
                return "DATE(a.created_at)";
        }
    }
    
    /**
     * Format period label for display
     */
    private function format_period_label($period, $interval) {
        switch ($interval) {
            case 'week':
                // Format: 2025-42 -> Week 42, 2025
                list($year, $week) = explode('-', $period);
                return "Week $week, $year";
            case 'month':
                // Format: 2025-10 -> Oct 2025
                list($year, $month) = explode('-', $period);
                return date('M Y', mktime(0, 0, 0, $month, 1, $year));
            case 'day':
            default:
                // Format: 2025-10-21 -> Oct 21, 2025
                return date('M j, Y', strtotime($period));
        }
    }
    
    /**
     * Get top performing metrics
     */
    public function get_top_performers($filters = array()) {
        $categories = $this->get_requests_by_category($filters);
        $models = $this->get_requests_by_model($filters);
        $sources = $this->get_requests_by_source($filters);
        
        return array(
            'top_category' => !empty($categories) ? $categories[0] : null,
            'top_model' => !empty($models) ? $models[0] : null,
            'top_source' => !empty($sources) ? $sources[0] : null
        );
    }
}