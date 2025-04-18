(function() {
    if (!window.greenmetricsTracking || !window.greenmetricsTracking.enabled) {
        return;
    }

    // Track page load metrics
    window.addEventListener('load', function() {
        const performance = window.performance;
        if (!performance) return;

        // Get resource timing data
        const resources = performance.getEntriesByType('resource');
        let dataTransfer = 0;
        let requests = 0;

        resources.forEach(resource => {
            if (resource.transferSize) {
                dataTransfer += resource.transferSize;
            }
            requests++;
        });

        // Get page load time
        const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;

        // Calculate carbon footprint and energy consumption
        const carbonFootprint = dataTransfer * window.greenmetricsTracking.energyPerByte * window.greenmetricsTracking.carbonIntensity;
        const energyConsumption = dataTransfer * window.greenmetricsTracking.energyPerByte;

        // Send data to server
        const formData = new FormData();
        formData.append('action', 'greenmetrics_tracking');
        formData.append('nonce', window.greenmetricsTracking.nonce);
        formData.append('metrics', JSON.stringify({
            data_transfer: dataTransfer,
            load_time: loadTime / 1000, // Convert to seconds
            requests: requests,
            carbon_footprint: carbonFootprint,
            energy_consumption: energyConsumption,
            page_id: window.greenmetricsTracking.page_id
        }));

        fetch(window.greenmetricsTracking.ajax_url, {
            method: 'POST',
            body: formData
        }).then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        }).catch(error => {
            console.error('Error tracking metrics:', error);
        });
    });
})(); 