<?php
if (!defined('ABSPATH')) {
    exit;
}

// Scripts are enqueued in the main plugin file
?>

<div class="wrap pri-analytics-dashboard">
    <h1><?php _e('📊 Repair Statistics Dashboard', 'phone-repair-intake'); ?></h1>
    
    <!-- Filters Section -->
    <div class="pri-filters-section">
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php _e('Filters', 'phone-repair-intake'); ?></h2>
                <div class="postbox-toolbar">
                    <button type="button" class="button" id="reset-filters"><?php _e('Reset', 'phone-repair-intake'); ?></button>
                    <button type="button" class="button button-primary" id="apply-filters"><?php _e('Apply Filters', 'phone-repair-intake'); ?></button>
                </div>
            </div>
            <div class="inside">
                <div class="pri-filter-grid">
                    <!-- Date Range -->
                    <div class="pri-filter-group">
                        <label><?php _e('Date Range', 'phone-repair-intake'); ?></label>
                        <div class="pri-date-presets">
                            <button type="button" class="button date-preset" data-days="0"><?php _e('Today', 'phone-repair-intake'); ?></button>
                            <button type="button" class="button date-preset active" data-days="7"><?php _e('7 Days', 'phone-repair-intake'); ?></button>
                            <button type="button" class="button date-preset" data-days="30"><?php _e('30 Days', 'phone-repair-intake'); ?></button>
                            <button type="button" class="button date-preset" data-days="90"><?php _e('90 Days', 'phone-repair-intake'); ?></button>
                            <button type="button" class="button date-preset" data-custom="true"><?php _e('Custom', 'phone-repair-intake'); ?></button>
                        </div>
                        <div class="pri-date-inputs" style="display: none;">
                            <input type="date" id="date_from" class="regular-text">
                            <span style="margin: 0 10px;"><?php _e('to', 'phone-repair-intake'); ?></span>
                            <input type="date" id="date_to" class="regular-text">
                        </div>
                    </div>
                    
                    <!-- Repair Categories -->
                    <div class="pri-filter-group">
                        <label for="repair_categories"><?php _e('Repair Categories', 'phone-repair-intake'); ?></label>
                        <select id="repair_categories" multiple class="pri-multiselect">
                            <option value=""><?php _e('Loading...', 'phone-repair-intake'); ?></option>
                        </select>
                    </div>
                    
                    <!-- iPhone Models -->
                    <div class="pri-filter-group">
                        <label for="iphone_models"><?php _e('iPhone Models', 'phone-repair-intake'); ?></label>
                        <select id="iphone_models" multiple class="pri-multiselect">
                            <option value=""><?php _e('Loading...', 'phone-repair-intake'); ?></option>
                        </select>
                    </div>
                    
                    <!-- Customer Sources -->
                    <div class="pri-filter-group">
                        <label for="customer_sources"><?php _e('Customer Sources', 'phone-repair-intake'); ?></label>
                        <select id="customer_sources" multiple class="pri-multiselect">
                            <option value=""><?php _e('Loading...', 'phone-repair-intake'); ?></option>
                        </select>
                    </div>
                    
                    <!-- Time Interval -->
                    <div class="pri-filter-group">
                        <label for="time_interval"><?php _e('Group By', 'phone-repair-intake'); ?></label>
                        <select id="time_interval" class="regular-text">
                            <option value="day"><?php _e('Daily', 'phone-repair-intake'); ?></option>
                            <option value="week"><?php _e('Weekly', 'phone-repair-intake'); ?></option>
                            <option value="month"><?php _e('Monthly', 'phone-repair-intake'); ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loading Indicator -->
    <div id="pri-loading" class="pri-loading" style="display: none;">
        <div class="spinner is-active"></div>
        <p><?php _e('Loading analytics data...', 'phone-repair-intake'); ?></p>
    </div>
    
    <!-- KPI Cards -->
    <div class="pri-kpi-section">
        <div class="pri-kpi-grid">
            <div class="pri-kpi-card">
                <div class="pri-kpi-icon">📊</div>
                <div class="pri-kpi-content">
                    <div class="pri-kpi-value" id="total-requests">-</div>
                    <div class="pri-kpi-label"><?php _e('Total Requests', 'phone-repair-intake'); ?></div>
                </div>
            </div>
            
            <div class="pri-kpi-card">
                <div class="pri-kpi-icon">✅</div>
                <div class="pri-kpi-content">
                    <div class="pri-kpi-value" id="booked-count">-</div>
                    <div class="pri-kpi-label"><?php _e('Successfully Booked', 'phone-repair-intake'); ?></div>
                </div>
            </div>
            
            <div class="pri-kpi-card">
                <div class="pri-kpi-icon">🏁</div>
                <div class="pri-kpi-content">
                    <div class="pri-kpi-value" id="completed-count">-</div>
                    <div class="pri-kpi-label"><?php _e('Completed Repairs', 'phone-repair-intake'); ?></div>
                </div>
            </div>
            
            <div class="pri-kpi-card">
                <div class="pri-kpi-icon">💰</div>
                <div class="pri-kpi-content">
                    <div class="pri-kpi-value" id="total-revenue">-</div>
                    <div class="pri-kpi-label"><?php _e('Total Revenue', 'phone-repair-intake'); ?></div>
                </div>
            </div>
            
            <div class="pri-kpi-card">
                <div class="pri-kpi-icon">📈</div>
                <div class="pri-kpi-content">
                    <div class="pri-kpi-value" id="conversion-rate">-</div>
                    <div class="pri-kpi-label"><?php _e('Conversion Rate', 'phone-repair-intake'); ?></div>
                </div>
            </div>
            
            <div class="pri-kpi-card">
                <div class="pri-kpi-icon">🎯</div>
                <div class="pri-kpi-content">
                    <div class="pri-kpi-value" id="completion-rate">-</div>
                    <div class="pri-kpi-label"><?php _e('Completion Rate', 'phone-repair-intake'); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Section -->
    <div class="pri-charts-section">
        <div class="pri-charts-grid">
            
            <!-- Trends Chart -->
            <div class="pri-chart-container full-width">
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php _e('Trends Over Time', 'phone-repair-intake'); ?></h2>
                        <div class="postbox-toolbar">
                            <button type="button" class="button button-secondary" id="export-trends"><?php _e('Export CSV', 'phone-repair-intake'); ?></button>
                        </div>
                    </div>
                    <div class="inside">
                        <canvas id="trends-chart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Requests by Category -->
            <div class="pri-chart-container">
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php _e('Requests by Repair Type', 'phone-repair-intake'); ?></h2>
                        <div class="postbox-toolbar">
                            <button type="button" class="button button-secondary" id="export-categories"><?php _e('Export CSV', 'phone-repair-intake'); ?></button>
                        </div>
                    </div>
                    <div class="inside">
                        <canvas id="categories-chart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Status Funnel -->
            <div class="pri-chart-container">
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php _e('Status Distribution', 'phone-repair-intake'); ?></h2>
                    </div>
                    <div class="inside">
                        <canvas id="status-chart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Top iPhone Models -->
            <div class="pri-chart-container">
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php _e('Top iPhone Models', 'phone-repair-intake'); ?></h2>
                        <div class="postbox-toolbar">
                            <button type="button" class="button button-secondary" id="export-models"><?php _e('Export CSV', 'phone-repair-intake'); ?></button>
                        </div>
                    </div>
                    <div class="inside">
                        <canvas id="models-chart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Customer Sources -->
            <div class="pri-chart-container">
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php _e('Customer Sources', 'phone-repair-intake'); ?></h2>
                        <div class="postbox-toolbar">
                            <button type="button" class="button button-secondary" id="export-sources"><?php _e('Export CSV', 'phone-repair-intake'); ?></button>
                        </div>
                    </div>
                    <div class="inside">
                        <canvas id="sources-chart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Revenue by Category -->
            <div class="pri-chart-container">
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php _e('Revenue by Repair Type', 'phone-repair-intake'); ?></h2>
                        <div class="postbox-toolbar">
                            <button type="button" class="button button-secondary" id="export-revenue"><?php _e('Export CSV', 'phone-repair-intake'); ?></button>
                        </div>
                    </div>
                    <div class="inside">
                        <canvas id="revenue-chart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top Performers Section -->
    <div class="pri-performers-section">
        <div class="pri-performers-grid">
            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php _e('Top Performers', 'phone-repair-intake'); ?></h2>
                </div>
                <div class="inside">
                    <div class="pri-performers-content">
                        <div class="pri-performer-card">
                            <h4><?php _e('Most Requested Repair', 'phone-repair-intake'); ?></h4>
                            <div id="top-category" class="pri-performer-item">
                                <span class="icon">📱</span>
                                <div class="details">
                                    <div class="name">-</div>
                                    <div class="count">- requests</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="pri-performer-card">
                            <h4><?php _e('Most Popular iPhone Model', 'phone-repair-intake'); ?></h4>
                            <div id="top-model" class="pri-performer-item">
                                <span class="icon">📱</span>
                                <div class="details">
                                    <div class="name">-</div>
                                    <div class="count">- requests</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="pri-performer-card">
                            <h4><?php _e('Top Customer Source', 'phone-repair-intake'); ?></h4>
                            <div id="top-source" class="pri-performer-item">
                                <span class="icon">👥</span>
                                <div class="details">
                                    <div class="name">-</div>
                                    <div class="count">- requests</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Data Tables Section -->
    <div class="pri-tables-section" style="display: none;">
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php _e('Detailed Data Tables', 'phone-repair-intake'); ?></h2>
                <div class="postbox-toolbar">
                    <button type="button" class="button" id="toggle-tables"><?php _e('Show Tables', 'phone-repair-intake'); ?></button>
                </div>
            </div>
            <div class="inside">
                <div class="pri-table-tabs">
                    <button type="button" class="pri-tab-button active" data-tab="categories"><?php _e('By Repair Type', 'phone-repair-intake'); ?></button>
                    <button type="button" class="pri-tab-button" data-tab="models"><?php _e('By iPhone Model', 'phone-repair-intake'); ?></button>
                    <button type="button" class="pri-tab-button" data-tab="sources"><?php _e('By Source', 'phone-repair-intake'); ?></button>
                </div>
                
                <div id="table-categories" class="pri-table-content active">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Repair Type', 'phone-repair-intake'); ?></th>
                                <th><?php _e('Requests', 'phone-repair-intake'); ?></th>
                                <th><?php _e('Booked', 'phone-repair-intake'); ?></th>
                                <th><?php _e('Completed', 'phone-repair-intake'); ?></th>
                                <th><?php _e('Conversion Rate', 'phone-repair-intake'); ?></th>
                                <th><?php _e('Revenue', 'phone-repair-intake'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="categories-table-body">
                            <tr><td colspan="6"><?php _e('Loading...', 'phone-repair-intake'); ?></td></tr>
                        </tbody>
                    </table>
                </div>
                
                <div id="table-models" class="pri-table-content">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('iPhone Model', 'phone-repair-intake'); ?></th>
                                <th><?php _e('Requests', 'phone-repair-intake'); ?></th>
                                <th><?php _e('Booked', 'phone-repair-intake'); ?></th>
                                <th><?php _e('Completed', 'phone-repair-intake'); ?></th>
                                <th><?php _e('Conversion Rate', 'phone-repair-intake'); ?></th>
                                <th><?php _e('Revenue', 'phone-repair-intake'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="models-table-body">
                            <tr><td colspan="6"><?php _e('Loading...', 'phone-repair-intake'); ?></td></tr>
                        </tbody>
                    </table>
                </div>
                
                <div id="table-sources" class="pri-table-content">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Customer Source', 'phone-repair-intake'); ?></th>
                                <th><?php _e('Requests', 'phone-repair-intake'); ?></th>
                                <th><?php _e('Booked', 'phone-repair-intake'); ?></th>
                                <th><?php _e('Completed', 'phone-repair-intake'); ?></th>
                                <th><?php _e('Conversion Rate', 'phone-repair-intake'); ?></th>
                                <th><?php _e('Revenue', 'phone-repair-intake'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="sources-table-body">
                            <tr><td colspan="6"><?php _e('Loading...', 'phone-repair-intake'); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Export Modal -->
<div id="export-modal" class="pri-modal" style="display: none;">
    <div class="pri-modal-content">
        <div class="pri-modal-header">
            <h3><?php _e('Export Data', 'phone-repair-intake'); ?></h3>
            <button type="button" class="pri-modal-close">&times;</button>
        </div>
        <div class="pri-modal-body">
            <p><?php _e('Your CSV file is being prepared for download...', 'phone-repair-intake'); ?></p>
            <div class="spinner is-active"></div>
        </div>
    </div>
</div>