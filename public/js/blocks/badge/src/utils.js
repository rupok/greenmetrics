/**
 * Helper function to format metric values
 *
 * @param {string} metric The metric key to format
 * @param {Object|null} data The metrics data object or null for placeholder
 * @return {string} The formatted metric value
 */
export const getMetricValue = (metric, data = null) => {
    if (!data) {
        switch (metric) {
            case 'carbon_footprint':
                return '{{carbon_footprint}}';
            case 'energy_consumption':
                return '{{energy_consumption}}';
            case 'data_transfer':
                return '{{data_transfer}}';
            case 'views':
                return '{{views}}';
            case 'http_requests':
                return '{{http_requests}}';
            case 'performance_score':
                return '{{performance_score}}';
            default:
                return '{{0}}';
        }
    }

    // Use the GreenMetricsFormatter if available, otherwise fallback to basic formatting
    if (typeof GreenMetricsFormatter !== 'undefined') {
        switch (metric) {
            case 'carbon_footprint':
                return GreenMetricsFormatter.formatCarbonEmissions(data.carbon_footprint);
            case 'energy_consumption':
                return GreenMetricsFormatter.formatEnergyConsumption(data.energy_consumption);
            case 'data_transfer':
                return GreenMetricsFormatter.formatDataTransfer(data.data_transfer);
            case 'views':
                return GreenMetricsFormatter.formatViews(data.total_views);
            case 'http_requests':
                return GreenMetricsFormatter.formatRequests(data.requests);
            case 'performance_score':
                return GreenMetricsFormatter.formatPerformanceScore(data.performance_score);
            default:
                return '0';
        }
    } else {
        // Fallback to basic formatting if formatter is not available
        switch (metric) {
            case 'carbon_footprint':
                return data.carbon_footprint ? `${data.carbon_footprint}g CO2` : '0g CO2';
            case 'energy_consumption':
                return data.energy_consumption ? `${data.energy_consumption} kWh` : '0 kWh';
            case 'data_transfer':
                return data.data_transfer ? `${(data.data_transfer / 1024).toFixed(2)} KB` : '0 KB';
            case 'views':
                return data.total_views ? data.total_views.toString() : '0';
            case 'http_requests':
                return data.requests ? data.requests.toString() : '0';
            case 'performance_score':
                return data.performance_score ? `${data.performance_score}%` : '0%';
            default:
                return '0';
        }
    }
};