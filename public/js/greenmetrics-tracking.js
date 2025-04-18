jQuery(document).ready(function($) {
    // Check if tracking is available and enabled
    if (!window.greenmetricsTracking) {
        console.warn('GreenMetrics tracking data not found');
        return;
    }

    if (!window.greenmetricsTracking.tracking_enabled) {
        console.debug('GreenMetrics tracking is disabled');
        return;
    }

    if (!window.greenmetricsTracking.rest_url) {
        console.warn('GreenMetrics REST URL not found');
        return;
    }

    if (!window.performance || !window.performance.getEntriesByType) {
        console.warn('Performance API not available');
        return;
    }

    // Calculate data transfer
    const resources = performance.getEntriesByType('resource');
    const dataTransfer = resources.reduce((total, resource) => {
        return total + (resource.transferSize || 0);
    }, 0);

    // Calculate page load time
    const loadTime = (window.performance.timing.loadEventEnd - window.performance.timing.navigationStart) / 1000;

    // Prepare tracking data
    const data = {
        page_id: window.greenmetricsTracking.page_id,
        data_transfer: dataTransfer,
        load_time: loadTime
    };

    // Get the REST URL and ensure it ends with a slash
    let restUrl = window.greenmetricsTracking.rest_url;
    if (typeof restUrl === 'string' && !restUrl.endsWith('/')) {
        restUrl += '/';
    }

    // Send tracking data
    fetch(restUrl + 'track', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.greenmetricsTracking.nonce
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(result => {
        console.debug('GreenMetrics tracking successful:', result);
    })
    .catch(error => {
        console.error('GreenMetrics tracking failed:', error, {
            url: restUrl + 'track',
            data
        });
    });
}); 