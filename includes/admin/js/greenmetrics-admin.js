/**
 * GreenMetrics Admin JavaScript
 * This is the main entry point for the admin JavaScript functionality
 * 
 * The actual implementation is split into modules for better maintainability:
 * - core.js: Core functionality and namespace initialization
 * - preview.js: Badge and popover preview functionality
 * - chart.js: Chart visualization functionality
 * - dashboard.js: Dashboard statistics functionality
 * 
 * The GreenMetricsAdmin namespace is used to organize all functionality
 */

// Initialize all modules when document is ready
jQuery(document).ready(function($) {
  // First initialize core
  if (typeof GreenMetricsAdmin.core !== 'undefined') {
    GreenMetricsAdmin.core.init();
  }
  
  // Then initialize feature modules
  if (typeof GreenMetricsAdmin.Preview !== 'undefined') {
    GreenMetricsAdmin.Preview.init();
  }
  
  if (typeof GreenMetricsAdmin.Chart !== 'undefined') {
    GreenMetricsAdmin.Chart.init();
  }
  
  if (typeof GreenMetricsAdmin.Dashboard !== 'undefined') {
    GreenMetricsAdmin.Dashboard.init();
  }
});