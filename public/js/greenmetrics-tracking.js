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
            } else if (performance.timing) {
                // Fallback to older Navigation Timing API
                rawLoadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
                loadSource = __('Legacy Timing API', 'greenmetrics');
            }
            
            // Also try to measure with more direct methods
            const pageLoadTime = performance.now();
            
            // Ensure loadTime is positive and convert to seconds
            loadTime = Math.max(0, rawLoadTime) / 1000;
            
            // Safety check to ensure data is reasonable
            if (isNaN(loadTime) || !isFinite(loadTime) || loadTime === 0) {
                // If we couldn't get a valid load time from Navigation APIs,
                // try to use performance.now() as a fallback
                if (performance.now && typeof performance.now === 'function') {
                    loadTime = performance.now() / 1000; // Convert ms to seconds
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

            // Post the data to the server endpoint
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
                }),
                credentials: 'same-origin'
            })
            .then(response => {
                // Debug logs removed
                return response.json();
            })
            .then(data => {
                // Debug logs removed
                // Success tracking via REST API
            })
            .catch(error => {
                // Handle error silently - don't affect user experience
            });
        }, 500); // Small delay to ensure everything is fully measured
    });
})(); 