(function($) {
    'use strict';

    // Track page metrics
    function trackPage() {
        const startTime = performance.now();
        const startBytes = performance.memory ? performance.memory.usedJSHeapSize : 0;

        $(window).on('load', function() {
            const endTime = performance.now();
            const endBytes = performance.memory ? performance.memory.usedJSHeapSize : 0;
            const loadTimeMs = endTime - startTime;
            const dataTransfer = endBytes - startBytes;

            // Convert milliseconds to seconds for server-side compatibility
            const loadTimeSeconds = loadTimeMs / 1000;

            // Calculate rough requests count based on performance entries
            let requests = 0;
            if (window.performance && window.performance.getEntriesByType) {
                requests = window.performance.getEntriesByType('resource').length;
            }
            
            console.log('Sending metrics data:', {
                data_transfer: dataTransfer,
                load_time: loadTimeSeconds, // In seconds for the server
                load_time_ms: loadTimeMs, // Original value in ms for reference
                requests: requests,
                page_id: greenmetricsPublic.page_id
            });

            // Use the REST API endpoint instead of AJAX
            fetch(greenmetricsPublic.rest_url + '/track', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': greenmetricsPublic.rest_nonce
                },
                body: JSON.stringify({
                    page_id: greenmetricsPublic.page_id,
                    data_transfer: dataTransfer,
                    load_time: loadTimeSeconds, // Send in seconds
                    requests: requests
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    console.log('Page metrics tracked successfully');
                } else {
                    console.error('Failed to track page metrics:', data.message);
                }
            })
            .catch(error => {
                console.error('Error tracking page metrics:', error);
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