/**
 * Phone Repair Analytics Dashboard
 * Handles charts, filters, and data visualization
 */

(function($) {
    'use strict';

    // Global variables
    let charts = {};
    let currentFilters = {};
    let filterOptions = {};

    // Initialize dashboard when DOM is ready
    $(document).ready(function() {
        initializeDashboard();
        loadFilterOptions();
        setupEventListeners();
        applyDefaultFilters();
    });

    /**
     * Initialize the dashboard
     */
    function initializeDashboard() {
        currentFilters = priAnalytics.defaultFilters;
        showLoading(true);
    }

    /**
     * Load filter options for dropdowns
     */
    function loadFilterOptions() {
        $.ajax({
            url: priAnalytics.apiUrl + 'filter-options',
            method: 'GET',
            headers: {
                'X-WP-Nonce': priAnalytics.nonce
            },
            success: function(response) {
                if (response.success) {
                    filterOptions = response.data;
                    populateFilterDropdowns();
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to load filter options:', error);
                showNotification('Failed to load filter options', 'error');
            }
        });
    }

    /**
     * Populate filter dropdown options
     */
    function populateFilterDropdowns() {
        // Populate repair categories
        const $categories = $('#repair_categories').empty();
        filterOptions.repair_categories.forEach(function(category) {
            $categories.append(`<option value="${category.id}">${category.name}</option>`);
        });

        // Populate iPhone models
        const $models = $('#iphone_models').empty();
        filterOptions.iphone_models.forEach(function(model) {
            $models.append(`<option value="${model.id}">${model.model_name}</option>`);
        });

        // Populate customer sources
        const $sources = $('#customer_sources').empty();
        filterOptions.sources.forEach(function(source) {
            $sources.append(`<option value="${source.value}">${source.label}</option>`);
        });
    }

    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Filter controls
        $('.date-preset').on('click', handleDatePreset);
        $('#apply-filters').on('click', applyFilters);
        $('#reset-filters').on('click', resetFilters);
        
        // Export buttons
        $('#export-trends').on('click', () => exportData('trends'));
        $('#export-categories').on('click', () => exportData('requests-by-category'));
        $('#export-models').on('click', () => exportData('requests-by-model'));
        $('#export-sources').on('click', () => exportData('requests-by-source'));
        $('#export-revenue').on('click', () => exportData('revenue'));
        
        // Table toggle
        $('#toggle-tables').on('click', toggleTables);
        $('.pri-tab-button').on('click', switchTab);
        
        // Modal close
        $('.pri-modal-close').on('click', closeModal);
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('pri-modal')) {
                closeModal();
            }
        });
    }

    /**
     * Handle date preset selection
     */
    function handleDatePreset(e) {
        e.preventDefault();
        const $btn = $(this);
        const days = $btn.data('days');
        const isCustom = $btn.data('custom');
        
        $('.date-preset').removeClass('active');
        $btn.addClass('active');
        
        if (isCustom) {
            $('.pri-date-inputs').show();
        } else {
            $('.pri-date-inputs').hide();
            
            const today = new Date();
            const fromDate = new Date();
            
            if (days === 0) {
                // Today only
                fromDate.setDate(today.getDate());
            } else {
                fromDate.setDate(today.getDate() - days);
            }
            
            $('#date_from').val(formatDate(fromDate));
            $('#date_to').val(formatDate(today));
        }
    }

    /**
     * Apply default filters on page load
     */
    function applyDefaultFilters() {
        // Set default date range (last 7 days)
        const today = new Date();
        const fromDate = new Date();
        fromDate.setDate(today.getDate() - 7);
        
        $('#date_from').val(formatDate(fromDate));
        $('#date_to').val(formatDate(today));
        
        // Apply filters
        setTimeout(applyFilters, 1000); // Wait for filter options to load
    }

    /**
     * Apply current filters and refresh data
     */
    function applyFilters() {
        // Collect filter values
        currentFilters = {
            date_from: $('#date_from').val() || currentFilters.date_from,
            date_to: $('#date_to').val() || currentFilters.date_to,
            interval: $('#time_interval').val(),
            repair_category_ids: $('#repair_categories').val() || [],
            model_ids: $('#iphone_models').val() || [],
            sources: $('#customer_sources').val() || []
        };

        // Show loading and refresh all data
        showLoading(true);
        loadDashboardData();
    }

    /**
     * Reset filters to defaults
     */
    function resetFilters() {
        // Reset form controls
        $('.date-preset').removeClass('active');
        $('.date-preset[data-days="7"]').addClass('active');
        $('.pri-date-inputs').hide();
        $('#time_interval').val('day');
        $('#repair_categories').val([]);
        $('#iphone_models').val([]);
        $('#customer_sources').val([]);
        
        // Apply default filters
        applyDefaultFilters();
    }

    /**
     * Load all dashboard data
     */
    function loadDashboardData() {
        const requests = [
            loadSummaryStats(),
            loadTrends(),
            loadCategoryStats(),
            loadModelStats(),
            loadSourceStats(),
            loadStatusFunnel(),
            loadRevenueStats(),
            loadTopPerformers()
        ];

        Promise.all(requests).then(() => {
            showLoading(false);
            showNotification('Dashboard updated successfully', 'success');
        }).catch((error) => {
            console.error('Failed to load dashboard data:', error);
            showLoading(false);
            showNotification('Failed to load some dashboard data', 'error');
        });
    }

    /**
     * Load summary statistics (KPIs)
     */
    function loadSummaryStats() {
        return apiRequest('summary', currentFilters).then(response => {
            if (response.success) {
                updateKPICards(response.data);
            }
        });
    }

    /**
     * Update KPI cards with data
     */
    function updateKPICards(data) {
        $('#total-requests').text(data.total_requests.toLocaleString());
        $('#booked-count').text(data.booked_count.toLocaleString());
        $('#completed-count').text(data.completed_count.toLocaleString());
        $('#total-revenue').text('$' + data.total_revenue.toLocaleString(undefined, {minimumFractionDigits: 2}));
        $('#conversion-rate').text(data.conversion_rate.toFixed(1) + '%');
        $('#completion-rate').text(data.completion_rate.toFixed(1) + '%');
    }

    /**
     * Load trends data and update chart
     */
    function loadTrends() {
        return apiRequest('trends', currentFilters).then(response => {
            if (response.success) {
                updateTrendsChart(response.chart_data);
            }
        });
    }

    /**
     * Update trends line chart
     */
    function updateTrendsChart(chartData) {
        const ctx = document.getElementById('trends-chart').getContext('2d');
        
        if (charts.trends) {
            charts.trends.destroy();
        }

        charts.trends = new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left'
                    },
                    revenue: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.dataset.label === 'Revenue ($)') {
                                    label += '$' + context.parsed.y.toLocaleString();
                                } else {
                                    label += context.parsed.y.toLocaleString();
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Load category statistics and update chart
     */
    function loadCategoryStats() {
        return apiRequest('requests-by-category', currentFilters).then(response => {
            if (response.success) {
                updateCategoriesChart(response.chart_data);
                updateCategoriesTable(response.data);
            }
        });
    }

    /**
     * Update categories bar chart
     */
    function updateCategoriesChart(chartData) {
        const ctx = document.getElementById('categories-chart').getContext('2d');
        
        if (charts.categories) {
            charts.categories.destroy();
        }

        charts.categories = new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: true
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Update categories data table
     */
    function updateCategoriesTable(data) {
        const tbody = $('#categories-table-body').empty();
        
        data.forEach(function(item) {
            tbody.append(`
                <tr>
                    <td>${item.icon} ${item.name}</td>
                    <td>${item.requests.toLocaleString()}</td>
                    <td>${item.booked.toLocaleString()}</td>
                    <td>${item.completed.toLocaleString()}</td>
                    <td>${item.conversion_rate.toFixed(1)}%</td>
                    <td>$${item.revenue.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                </tr>
            `);
        });
    }

    /**
     * Load model statistics and update chart
     */
    function loadModelStats() {
        return apiRequest('requests-by-model', currentFilters).then(response => {
            if (response.success) {
                updateModelsChart(response.chart_data);
                updateModelsTable(response.data);
            }
        });
    }

    /**
     * Update models horizontal bar chart
     */
    function updateModelsChart(chartData) {
        const ctx = document.getElementById('models-chart').getContext('2d');
        
        if (charts.models) {
            charts.models.destroy();
        }

        charts.models = new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    /**
     * Update models data table
     */
    function updateModelsTable(data) {
        const tbody = $('#models-table-body').empty();
        
        data.slice(0, 20).forEach(function(item) { // Show top 20
            tbody.append(`
                <tr>
                    <td>${item.model_name}</td>
                    <td>${item.requests.toLocaleString()}</td>
                    <td>${item.booked.toLocaleString()}</td>
                    <td>${item.completed.toLocaleString()}</td>
                    <td>${item.conversion_rate.toFixed(1)}%</td>
                    <td>$${item.revenue.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                </tr>
            `);
        });
    }

    /**
     * Load source statistics and update chart
     */
    function loadSourceStats() {
        return apiRequest('requests-by-source', currentFilters).then(response => {
            if (response.success) {
                updateSourcesChart(response.chart_data);
                updateSourcesTable(response.data);
            }
        });
    }

    /**
     * Update sources pie chart
     */
    function updateSourcesChart(chartData) {
        const ctx = document.getElementById('sources-chart').getContext('2d');
        
        if (charts.sources) {
            charts.sources.destroy();
        }

        charts.sources = new Chart(ctx, {
            type: 'pie',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed.toLocaleString() + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Update sources data table
     */
    function updateSourcesTable(data) {
        const tbody = $('#sources-table-body').empty();
        
        data.forEach(function(item) {
            tbody.append(`
                <tr>
                    <td>${item.label}</td>
                    <td>${item.requests.toLocaleString()}</td>
                    <td>${item.booked.toLocaleString()}</td>
                    <td>${item.completed.toLocaleString()}</td>
                    <td>${item.conversion_rate.toFixed(1)}%</td>
                    <td>$${item.revenue.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                </tr>
            `);
        });
    }

    /**
     * Load status funnel and update chart
     */
    function loadStatusFunnel() {
        return apiRequest('funnel', currentFilters).then(response => {
            if (response.success) {
                updateStatusChart(response.chart_data);
            }
        });
    }

    /**
     * Update status doughnut chart
     */
    function updateStatusChart(chartData) {
        const ctx = document.getElementById('status-chart').getContext('2d');
        
        if (charts.status) {
            charts.status.destroy();
        }

        charts.status = new Chart(ctx, {
            type: 'doughnut',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed.toLocaleString() + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Load revenue statistics and update chart
     */
    function loadRevenueStats() {
        return apiRequest('revenue', currentFilters).then(response => {
            if (response.success) {
                updateRevenueChart(response.chart_data.by_category);
            }
        });
    }

    /**
     * Update revenue bar chart
     */
    function updateRevenueChart(chartData) {
        const ctx = document.getElementById('revenue-chart').getContext('2d');
        
        if (charts.revenue) {
            charts.revenue.destroy();
        }

        charts.revenue = new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: $' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Load top performers
     */
    function loadTopPerformers() {
        return apiRequest('top-performers', currentFilters).then(response => {
            if (response.success) {
                updateTopPerformers(response.data);
            }
        });
    }

    /**
     * Update top performers section
     */
    function updateTopPerformers(data) {
        // Top category
        if (data.top_category) {
            $('#top-category .name').text(data.top_category.name);
            $('#top-category .count').text(data.top_category.requests.toLocaleString() + ' requests');
            $('#top-category .icon').text(data.top_category.icon);
        }

        // Top model
        if (data.top_model) {
            $('#top-model .name').text(data.top_model.model_name);
            $('#top-model .count').text(data.top_model.requests.toLocaleString() + ' requests');
        }

        // Top source
        if (data.top_source) {
            $('#top-source .name').text(data.top_source.label);
            $('#top-source .count').text(data.top_source.requests.toLocaleString() + ' requests');
        }
    }

    /**
     * Export data as CSV
     */
    function exportData(endpoint) {
        showModal();
        
        apiRequest(endpoint, currentFilters).then(response => {
            if (response.success) {
                const csvContent = convertToCSV(response.data, endpoint);
                downloadCSV(csvContent, endpoint + '.csv');
                closeModal();
                showNotification('Data exported successfully', 'success');
            } else {
                closeModal();
                showNotification('Export failed', 'error');
            }
        }).catch(error => {
            closeModal();
            showNotification('Export failed', 'error');
        });
    }

    /**
     * Convert data to CSV format
     */
    function convertToCSV(data, type) {
        if (!data || data.length === 0) {
            return 'No data available';
        }

        const headers = Object.keys(data[0]);
        const csvRows = [headers.join(',')];

        data.forEach(row => {
            const values = headers.map(header => {
                const value = row[header];
                return typeof value === 'string' ? `"${value}"` : value;
            });
            csvRows.push(values.join(','));
        });

        return csvRows.join('\n');
    }

    /**
     * Download CSV file
     */
    function downloadCSV(content, filename) {
        const blob = new Blob([content], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.setAttribute('hidden', '');
        a.setAttribute('href', url);
        a.setAttribute('download', filename);
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }

    /**
     * Toggle data tables visibility
     */
    function toggleTables() {
        const $section = $('.pri-tables-section');
        const $btn = $('#toggle-tables');
        
        if ($section.is(':visible')) {
            $section.hide();
            $btn.text('Show Tables');
        } else {
            $section.show();
            $btn.text('Hide Tables');
        }
    }

    /**
     * Switch between data table tabs
     */
    function switchTab(e) {
        e.preventDefault();
        const $btn = $(this);
        const tab = $btn.data('tab');
        
        $('.pri-tab-button').removeClass('active');
        $btn.addClass('active');
        
        $('.pri-table-content').removeClass('active');
        $('#table-' + tab).addClass('active');
    }

    /**
     * Show/hide loading indicator
     */
    function showLoading(show) {
        if (show) {
            $('#pri-loading').show();
        } else {
            $('#pri-loading').hide();
        }
    }

    /**
     * Show modal
     */
    function showModal() {
        $('#export-modal').show();
    }

    /**
     * Close modal
     */
    function closeModal() {
        $('#export-modal').hide();
    }

    /**
     * Show notification
     */
    function showNotification(message, type) {
        // Simple notification - could be enhanced with a proper notification system
        const className = type === 'success' ? 'notice-success' : 'notice-error';
        const $notice = $(`<div class="notice ${className} is-dismissible"><p>${message}</p></div>`);
        
        $('.wrap').prepend($notice);
        
        setTimeout(() => {
            $notice.fadeOut(() => $notice.remove());
        }, 3000);
    }

    /**
     * Make API request
     */
    function apiRequest(endpoint, params) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: priAnalytics.apiUrl + endpoint,
                method: 'GET',
                data: params,
                headers: {
                    'X-WP-Nonce': priAnalytics.nonce
                },
                success: resolve,
                error: reject
            });
        });
    }

    /**
     * Format date for input fields
     */
    function formatDate(date) {
        return date.toISOString().split('T')[0];
    }

})(jQuery);