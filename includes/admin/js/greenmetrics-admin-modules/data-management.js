/**
 * GreenMetrics Admin Data Management Module
 * Handles the data management page functionality including tabs
 *
 * @module GreenMetricsAdmin.DataManagement
 * @requires jQuery
 * @requires GreenMetricsAdmin.Config
 * @requires GreenMetricsAdmin.Utils
 */
// Ensure namespace exists
var GreenMetricsAdmin = GreenMetricsAdmin || {};

// Add data management functionality to namespace
GreenMetricsAdmin.DataManagement = (function ($) {
    'use strict';

    // Module variables
    var STORAGE_KEY_TAB = 'greenmetrics_data_management_active_tab';
    var ANIMATION_SPEED = 300;

    // Cache DOM elements
    var $cache = {};

    /**
     * Initialize the data management functionality
     *
     * @function init
     * @memberof GreenMetricsAdmin.DataManagement
     * @public
     */
    function init() {
        // Only proceed if we're on the data management page
        if (!isDataManagementPage()) {
            return;
        }

        // Cache DOM elements
        cacheElements();

        // Initialize tabs
        initTabs();

        // Setup event listeners
        setupEventListeners();
    }

    /**
     * Check if we're on the data management page
     *
     * @function isDataManagementPage
     * @private
     * @returns {boolean} True if on data management page
     */
    function isDataManagementPage() {
        // First check the config flag
        if (GreenMetricsAdmin.Config && GreenMetricsAdmin.Config.isDataManagementPage) {
            return true;
        }

        // Fallback: Check URL for data management page
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('page') && urlParams.get('page') === 'greenmetrics_data_management') {
            return true;
        }

        // Fallback: Check for data management elements on the page
        if ($('.greenmetrics-tab-item').length > 0 && $('.greenmetrics-data-management-page').length > 0) {
            return true;
        }

        return false;
    }

    /**
     * Cache DOM elements for better performance
     *
     * @function cacheElements
     * @private
     */
    function cacheElements() {
        $cache = {
            // Tab elements
            tabItems: $('.greenmetrics-tab-item'),
            tabContents: $('.greenmetrics-tab-content')
        };
    }

    /**
     * Initialize tabs functionality
     *
     * @function initTabs
     * @private
     */
    function initTabs() {
        // Restore active tab from localStorage if available
        var activeTab = localStorage.getItem(STORAGE_KEY_TAB);
        if (activeTab && $cache.tabItems.filter('[data-tab="' + activeTab + '"]').length) {
            switchToTab(activeTab);
        } else {
            // Default to first tab
            var firstTabId = $cache.tabItems.first().data('tab');
            switchToTab(firstTabId);
        }
    }

    /**
     * Switch to a specific tab
     *
     * @function switchToTab
     * @private
     * @param {string} tabId - The ID of the tab to activate
     */
    function switchToTab(tabId) {
        // Update active tab
        $cache.tabItems.removeClass('active');
        $cache.tabItems.filter('[data-tab="' + tabId + '"]').addClass('active');

        // Show selected tab content
        $cache.tabContents.removeClass('active');
        $('#tab-' + tabId).addClass('active');

        // Store the active tab in localStorage
        localStorage.setItem(STORAGE_KEY_TAB, tabId);
    }

    /**
     * Setup event listeners
     *
     * @function setupEventListeners
     * @private
     */
    function setupEventListeners() {
        // Tab click handler
        $cache.tabItems.on('click', function() {
            var tabId = $(this).data('tab');
            switchToTab(tabId);
        });
    }

    // Return public API
    return {
        init: init
    };

})(jQuery);
