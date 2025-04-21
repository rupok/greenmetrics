jQuery(document).ready(function($) {
    // Initialize the dashboard
    initDashboard();

    // Function to initialize dashboard
    function initDashboard() {
        // Load initial stats
        getStats();
        
        // Set up event listeners
        setupEventListeners();
    }

    // Get stats
    function getStats() {
        $.ajax({
            url: greenmetricsAdmin.rest_url + 'greenmetrics/v1/metrics',
            type: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', greenmetricsAdmin.rest_nonce);
            },
            success: function(response) {
                updateStatsDisplay(response);
            },
            error: function(xhr, status, error) {
                console.error('Error getting stats:', error);
                $('#greenmetrics-stats').html('<p class="error">Error loading stats. Please try again.</p>');
            }
        });
    }

    // Update stats display
    function updateStatsDisplay(stats) {
        const html = `
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Views</h3>
                    <p class="stat-value">${stats.total_views}</p>
                </div>
                <div class="stat-card">
                    <h3>Carbon Footprint</h3>
                    <p class="stat-value">${stats.co2_emissions.toFixed(2)} g CO2</p>
                </div>
                <div class="stat-card">
                    <h3>Energy Consumption</h3>
                    <p class="stat-value">${stats.energy_consumption.toFixed(2)} kWh</p>
                </div>
                <div class="stat-card">
                    <h3>Data Transfer</h3>
                    <p class="stat-value">${stats.avg_data_transfer.toFixed(2)} KB</p>
                </div>
                <div class="stat-card">
                    <h3>Requests</h3>
                    <p class="stat-value">${stats.requests}</p>
                </div>
                <div class="stat-card">
                    <h3>Performance Score</h3>
                    <p class="stat-value">${stats.performance_score.toFixed(2)}%</p>
                </div>
            </div>
        `;
        $('#greenmetrics-stats').html(html);
    }

    // Function to update optimization suggestions
    function updateOptimizationSuggestions(data) {
        const suggestionsList = $('#optimization-suggestions');
        suggestionsList.empty();

        // Get suggestions based on data
        const suggestions = getOptimizationSuggestions(data);

        // Add each suggestion to the list
        suggestions.forEach(suggestion => {
            const suggestionItem = $('<div class="suggestion-item ' + suggestion.priority + '">');
            suggestionItem.append('<h4>' + suggestion.title + '</h4>');
            suggestionItem.append('<p>' + suggestion.description + '</p>');
            suggestionsList.append(suggestionItem);
        });
    }

    // Function to get optimization suggestions based on metrics
    function getOptimizationSuggestions(data) {
        const suggestions = [];

        // Check for high data transfer
        if (data.data_transfer > 1024 * 1024 * 1024) { // More than 1GB
            suggestions.push({
                title: 'High Data Transfer',
                description: 'Your website is transferring a large amount of data. Consider optimizing images and implementing caching.',
                priority: 'high'
            });
        }

        // Check for high CO2 emissions
        if (data.co2_emissions > 1000) { // More than 1kg
            suggestions.push({
                title: 'High Carbon Emissions',
                description: 'Your website is generating significant carbon emissions. Consider using a green hosting provider.',
                priority: 'high'
            });
        }

        // Check for missing caching
        if (!data.caching_enabled) {
            suggestions.push({
                title: 'Enable Caching',
                description: 'Caching is not enabled. Enable caching to reduce server load and data transfer.',
                priority: 'medium'
            });
        }

        // Check for missing lazy loading
        if (!data.lazy_loading_enabled) {
            suggestions.push({
                title: 'Enable Lazy Loading',
                description: 'Lazy loading is not enabled. Enable it to reduce initial page load size.',
                priority: 'medium'
            });
        }

        return suggestions;
    }

    // Function to set up event listeners
    function setupEventListeners() {
        // Handle checkbox changes
        $('input[type="checkbox"]').on('change', function() {
            $('#submit').prop('disabled', false).removeClass('button-disabled');
        });
    }

    // Show notice - used for displaying success/error messages
    function showNotice(message, type) {
        const notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after(notice);
        setTimeout(function() {
            notice.fadeOut(500, function() {
                $(this).remove();
            });
        }, 3000);
    }

    // Update stats periodically
    function updateStats() {
        $.ajax({
            url: greenmetricsAdmin.rest_url + 'greenmetrics/v1/metrics',
            type: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', greenmetricsAdmin.rest_nonce);
            },
            success: function(response) {
                $('#total-views').text(response.total_views.toLocaleString());
                $('#carbon-footprint').text(response.co2_emissions.toFixed(2) + ' g CO2');
                $('#energy-consumption').text(response.energy_consumption.toFixed(2) + ' kWh');
                $('#data-transfer').text(response.avg_data_transfer.toFixed(2) + ' KB');
                $('#requests').text(response.requests);
                $('#performance-score').text(response.performance_score.toFixed(2) + '%');
            }
        });
    }

    // Update stats on page load and every 30 seconds
    updateStats();
    setInterval(updateStats, 30000);
}); 