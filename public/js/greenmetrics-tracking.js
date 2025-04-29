(function() {
    if (!window.greenmetricsTracking || !window.greenmetricsTracking.enabled) {
        return;
    }

    // Make sure we have the translation functions
    const __ = window.wp && window.wp.i18n && window.wp.i18n.__ ? window.wp.i18n.__ : function(text) { return text; };
    const _x = window.wp && window.wp.i18n && window.wp.i18n._x ? window.wp.i18n._x : function(text) { return text; };

    // Track page load metrics
    window.addEventListener('load', function() {
        // Small delay to ensure all resources are fully loaded
        setTimeout(function() {
            const performance = window.performance;
            if (!performance) return;

            // Get resource timing data
            const resources = performance.getEntriesByType('resource');
            let dataTransfer = 0;
            let requests = 0;

            resources.forEach(resource => {
                if (resource.transferSize && resource.transferSize > 0) {
                    dataTransfer += resource.transferSize;
                }
                requests++;
            });

            // Get page load time - use modern Navigation Timing API if available
            let loadTime = 0;
            let rawLoadTime = 0;
            let loadSource = '';
            
            if (performance.getEntriesByType && performance.getEntriesByType('navigation').length) {
                // Use newer Navigation Timing API (more accurate)
                const navTiming = performance.getEntriesByType('navigation')[0];
                rawLoadTime = navTiming.loadEventEnd - navTiming.startTime;
                loadSource = __('Navigation API', 'greenmetrics');
                console.log(__('GreenMetrics Debug - Navigation API Timing:', 'greenmetrics'), {
                    startTime: navTiming.startTime,
                    loadEventEnd: navTiming.loadEventEnd,
                    rawDifference: rawLoadTime
                });
            } else if (performance.timing) {
                // Fallback to older Navigation Timing API
                rawLoadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
                loadSource = __('Legacy Timing API', 'greenmetrics');
                console.log(__('GreenMetrics Debug - Legacy Timing API:', 'greenmetrics'), {
                    navigationStart: performance.timing.navigationStart,
                    loadEventEnd: performance.timing.loadEventEnd,
                    rawDifference: rawLoadTime
                });
            }
            
            // Also try to measure with more direct methods
            const pageLoadTime = performance.now();
            console.log(__('GreenMetrics Debug - performance.now():', 'greenmetrics'), pageLoadTime);
            
            // Ensure loadTime is positive and convert to seconds
            loadTime = Math.max(0, rawLoadTime) / 1000;
            
            // Safety check to ensure data is reasonable
            if (isNaN(loadTime) || !isFinite(loadTime) || loadTime === 0) {
                // If we couldn't get a valid load time from Navigation APIs,
                // try to use performance.now() as a fallback
                if (performance.now && typeof performance.now === 'function') {
                    loadTime = performance.now() / 1000; // Convert ms to seconds
                    console.log(__('GreenMetrics Debug - Using performance.now() as fallback:', 'greenmetrics'), loadTime);
                } else {
                    loadTime = 0.1; // Default minimal value if all else fails
                }
            }
            
            // Safety check to ensure data is reasonable
            if (isNaN(loadTime) || !isFinite(loadTime)) {
                loadTime = 0.1; // Default minimal value if all else fails
            }
            
            if (isNaN(dataTransfer) || !isFinite(dataTransfer) || dataTransfer < 0) {
                dataTransfer = 0;
            }
            
            if (isNaN(requests) || !isFinite(requests) || requests < 0) {
                requests = 0;
            }

            // Calculate carbon footprint and energy consumption
            const carbonFootprint = dataTransfer * window.greenmetricsTracking.energyPerByte * window.greenmetricsTracking.carbonIntensity;
            const energyConsumption = dataTransfer * window.greenmetricsTracking.energyPerByte;

            // Log for debugging
            console.log(__('GreenMetrics tracking:', 'greenmetrics'), {
                data_transfer: dataTransfer,
                load_time: loadTime,
                requests: requests
            });

            // Send data to server using REST API
            fetch(window.greenmetricsTracking.rest_url + '/track', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.greenmetricsTracking.rest_nonce
                },
                body: JSON.stringify({
                    page_id: window.greenmetricsTracking.page_id,
                    data_transfer: dataTransfer,
                    load_time: loadTime,
                    requests: requests
                })
            })
            .then(response => {
                console.log(__('GreenMetrics tracking response status:', 'greenmetrics'), response.status);
                if (!response.ok) {
                    throw new Error(__('Network response was not ok', 'greenmetrics'));
                }
                return response.json();
            })
            .then(data => {
                console.log(__('GreenMetrics tracking response data:', 'greenmetrics'), data);
                if (data.success) {
                    console.log(__('Metrics tracked successfully via REST API', 'greenmetrics'));
                }
            })
            .catch(error => {
                console.error(__('Error tracking metrics:', 'greenmetrics'), error);
            });
        }, 500); // Small delay to ensure everything is fully measured
    });
})(); 