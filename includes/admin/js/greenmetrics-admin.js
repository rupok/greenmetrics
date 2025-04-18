jQuery(document).ready(function($) {
    'use strict';

    // Refresh stats periodically
    function refreshStats() {
        $.ajax({
            url: greenmetricsAdmin.rest_url + 'greenmetrics/v1/stats',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', greenmetricsAdmin.rest_nonce);
            },
            success: function(response) {
                if (response) {
                    // Update stats display
                    $('.greenmetrics-stat-box').each(function() {
                        const $box = $(this);
                        const $value = $box.find('.stat-value');
                        
                        if ($box.find('h3').text().includes('Data Transfer')) {
                            $value.text((response.avg_data_transfer / 1024).toFixed(2) + ' KB');
                        } else if ($box.find('h3').text().includes('Load Time')) {
                            $value.text(response.avg_load_time.toFixed(2) + ' s');
                        } else if ($box.find('h3').text().includes('Page Views')) {
                            $value.text(response.total_views.toLocaleString());
                        }
                    });
                }
            },
            error: function(error) {
                console.error('Error fetching stats:', error);
            }
        });
    }

    // Refresh stats every 5 minutes
    setInterval(refreshStats, 300000);

    // Handle settings form submission
    $('form').on('submit', function() {
        // Clear any existing messages
        $('.notice').remove();
        
        // Show saving indicator
        const $submitButton = $(this).find(':submit');
        const originalText = $submitButton.val();
        $submitButton.val('Saving...').prop('disabled', true);
        
        // Form will be submitted normally through WordPress settings API
        setTimeout(function() {
            $submitButton.val(originalText).prop('disabled', false);
        }, 1000);
    });
}); 