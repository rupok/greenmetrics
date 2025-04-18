jQuery(document).ready(function($) {
    // Only track if we have the required data and tracking is enabled
    if (typeof greenmetricsTracking === 'undefined' || !greenmetricsTracking.page_id || !greenmetricsTracking.tracking_enabled) {
        return;
    }

    // Track page load
    trackPageLoad();

    function trackPageLoad() {
        window.addEventListener('load', function() {
            // Get page load data
            const performance = window.performance || window.mozPerformance || window.msPerformance || window.webkitPerformance || {};
            const timing = performance.timing || {};
            
            // Calculate data transfer
            const resources = performance.getEntriesByType('resource');
            let totalBytes = 0;
            
            resources.forEach(function(resource) {
                if (resource.transferSize) {
                    totalBytes += resource.transferSize;
                }
            });
            
            // Send data to server
            $.ajax({
                url: greenmetricsTracking.ajaxurl,
                type: 'POST',
                data: {
                    action: 'greenmetrics_track_page',
                    nonce: greenmetricsTracking.nonce,
                    page_id: greenmetricsTracking.page_id,
                    data_transfer: totalBytes,
                    load_time: timing.loadEventEnd - timing.navigationStart
                },
                success: function(response) {
                    if (response.success) {
                        console.log('GreenMetrics: Page tracked successfully');
                    } else {
                        console.error('GreenMetrics: Tracking failed', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    if (xhr.status === 403) {
                        console.error('GreenMetrics: Permission denied. Please check if you are logged in.');
                    } else {
                        console.error('GreenMetrics: Error tracking page', error);
                    }
                }
            });
        });
    }
}); 