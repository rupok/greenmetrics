(function($) {
    'use strict';

    // Track page metrics
    function trackPage() {
        const startTime = performance.now();
        const startBytes = performance.memory ? performance.memory.usedJSHeapSize : 0;

        $(window).on('load', function() {
            const endTime = performance.now();
            const endBytes = performance.memory ? performance.memory.usedJSHeapSize : 0;
            const loadTime = endTime - startTime;
            const dataTransfer = endBytes - startBytes;

            // Calculate rough requests count based on performance entries
            let requests = 0;
            if (window.performance && window.performance.getEntriesByType) {
                requests = window.performance.getEntriesByType('resource').length;
            }

            // Create metrics object
            const metrics = {
                data_transfer: dataTransfer,
                load_time: loadTime,
                requests: requests,
                page_id: greenmetricsPublic.page_id
            };
            
            console.log('Sending metrics data:', metrics);

            $.ajax({
                url: greenmetricsPublic.ajax_url,
                type: 'POST',
                data: {
                    action: 'greenmetrics_tracking',
                    nonce: greenmetricsPublic.nonce,
                    metrics: JSON.stringify(metrics)
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Page metrics tracked successfully');
                    } else {
                        console.error('Failed to track page metrics:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error tracking page metrics:', error);
                }
            });
        });
    }

    // Initialize tracking if enabled
    if (greenmetricsPublic.tracking_enabled) {
        trackPage();
    }

    // Handle badge hover
    $('.greenmetrics-badge-wrapper').hover(
        function() {
            $(this).find('.greenmetrics-content').addClass('visible');
        },
        function() {
            $(this).find('.greenmetrics-content').removeClass('visible');
        }
    );

})(jQuery); 