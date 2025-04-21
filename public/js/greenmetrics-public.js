(function($) {
    'use strict';

    // Track page metrics
    function trackPage() {
        const startTime = performance.now();
        
        // Use performance API to measure actual network transfer
        let totalTransferSize = 0;
        
        $(window).on('load', function() {
            const endTime = performance.now();
            const loadTimeMs = endTime - startTime;
            
            // Calculate real data transfer using performance entries
            if (window.performance && window.performance.getEntriesByType) {
                const resources = window.performance.getEntriesByType('resource');
                resources.forEach(resource => {
                    // Some browsers don't provide transferSize, so fallback to encodedBodySize
                    if (resource.transferSize) {
                        totalTransferSize += resource.transferSize;
                    } else if (resource.encodedBodySize) {
                        totalTransferSize += resource.encodedBodySize;
                    }
                });
                
                // Add estimated HTML size (not included in resource entries)
                totalTransferSize += document.documentElement.outerHTML.length;
            }

            // Convert milliseconds to seconds for server-side compatibility
            const loadTimeSeconds = loadTimeMs / 1000;

            // Calculate requests count based on performance entries
            let requests = 0;
            if (window.performance && window.performance.getEntriesByType) {
                requests = window.performance.getEntriesByType('resource').length;
                // Add 1 for the initial HTML document
                requests += 1;
            }
            
            console.log('Sending metrics data:', {
                data_transfer: totalTransferSize,
                load_time: loadTimeSeconds, // In seconds for the server
                load_time_ms: loadTimeMs, // Original value in ms for reference
                requests: requests,
                page_id: greenmetricsPublic.page_id
            });

            console.log('REST URL:', greenmetricsPublic.rest_url);
            console.log('REST nonce:', greenmetricsPublic.rest_nonce ? 'Available' : 'Missing');
            
            const data = {
                page_id: greenmetricsPublic.page_id,
                data_transfer: totalTransferSize,
                load_time: loadTimeSeconds, // Send in seconds
                requests: requests
            };
            
            console.log('Request payload:', JSON.stringify(data));
            
            // Use the REST API endpoint instead of AJAX
            fetch(greenmetricsPublic.rest_url + '/track', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': greenmetricsPublic.rest_nonce
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', 
                    Array.from(response.headers.entries())
                        .map(pair => pair[0] + ': ' + pair[1])
                        .join(', ')
                );
                
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    console.log('Page metrics tracked successfully');
                } else {
                    console.error('Failed to track page metrics:', data.message);
                }
            })
            .catch(error => {
                console.error('Error tracking page metrics:', error);
                // Try to log more details about the error
                if (error instanceof TypeError) {
                    console.error('Network error - check if the server is reachable');
                } else if (error instanceof SyntaxError) {
                    console.error('Invalid JSON response from server');
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