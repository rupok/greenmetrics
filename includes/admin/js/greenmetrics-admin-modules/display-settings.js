/**
 * GreenMetrics Admin Display Settings Module
 * Handles the display settings page functionality including tabs, accordions, and preview
 *
 * @module GreenMetricsAdmin.DisplaySettings
 * @requires jQuery
 * @requires GreenMetricsAdmin.Config
 * @requires GreenMetricsAdmin.Utils
 */
// Ensure namespace exists
var GreenMetricsAdmin = GreenMetricsAdmin || {};

// Add display settings functionality to namespace
GreenMetricsAdmin.DisplaySettings = (function ($) {
    'use strict';

    // Module variables
    var STORAGE_KEY_TAB = 'greenmetrics_display_settings_active_tab';
    var STORAGE_KEY_ACCORDION_PREFIX = 'greenmetrics_display_settings_active_accordion_';
    var ANIMATION_SPEED = 300;
    var mediaFrame;

    // Cache DOM elements
    var $cache = {};

    /**
     * Initialize the display settings functionality
     *
     * @function init
     * @memberof GreenMetricsAdmin.DisplaySettings
     * @public
     */
    function init() {
        // Only proceed if we're on the display settings page
        if (!isDisplaySettingsPage()) {
            return;
        }

        // Cache DOM elements
        cacheElements();

        // Initialize tabs
        initTabs();

        // Initialize accordions
        initAccordions();

        // Initialize form submission handling
        initFormSubmission();

        // Initialize preview functionality
        initPreview();

        // Setup event listeners
        setupEventListeners();
    }

    /**
     * Check if we're on the display settings page
     *
     * @function isDisplaySettingsPage
     * @private
     * @returns {boolean} True if on display settings page
     */
    function isDisplaySettingsPage() {
        // First check the config flag
        if (GreenMetricsAdmin.Config && GreenMetricsAdmin.Config.isDisplaySettingsPage) {
            return true;
        }

        // Fallback: Check URL for display settings page
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('page') && urlParams.get('page') === 'greenmetrics_display') {
            return true;
        }

        // Fallback: Check for display settings elements on the page
        if ($('.greenmetrics-tab-item').length > 0 && $('#greenmetrics-display-settings-form').length > 0) {
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
            // Tab and accordion elements
            tabItems: $('.greenmetrics-tab-item'),
            tabContents: $('.greenmetrics-tab-content'),
            accordionTriggers: $('.accordion-trigger'),
            accordionContents: $('.accordion-content'),

            // Form elements
            form: $('#greenmetrics-display-settings-form'),

            // Badge preview elements
            badgePreviewContainer: $('#badge-preview-container'),
            badge: $('.greenmetrics-badge'),
            iconContainer: $('.icon-container'),

            // Badge settings fields
            enableBadge: $('#enable_badge'),
            badgePosition: $('#badge_position'),
            badgeSize: $('#badge_size'),
            badgeText: $('#badge_text'),
            badgeBgColor: $('#badge_background_color'),
            badgeTextColor: $('#badge_text_color'),
            displayIcon: $('#display_icon'),
            badgeIconType: $('#badge_icon_type'),
            badgeIconColor: $('#badge_icon_color'),
            badgeIconSize: $('#badge_icon_size'),
            badgeCustomIcon: $('#badge_custom_icon'),

            // Popover preview elements
            popoverPreviewContainer: $('#popover-preview-container'),

            // Popover settings fields
            popoverTitle: $('#popover_title'),
            popoverBgColor: $('#popover_bg_color'),
            popoverTextColor: $('#popover_text_color'),
            popoverMetricsColor: $('#popover_metrics_color'),
            popoverMetricsBgColor: $('#popover_metrics_bg_color'),
            popoverContentFont: $('#popover_content_font'),
            popoverContentFontSize: $('#popover_content_font_size'),
            popoverMetricsFont: $('#popover_metrics_font'),
            popoverMetricsFontSize: $('#popover_metrics_font_size'),
            popoverMetricsLabelFontSize: $('#popover_metrics_label_font_size'),
            popoverMetricsListBgColor: $('#popover_metrics_list_bg_color'),
            popoverMetricsListHoverBgColor: $('#popover_metrics_list_hover_bg_color'),
            popoverCustomContent: $('#popover_custom_content'),

            // Font size number inputs
            contentFontSizeNumber: $('#popover_content_font_size_number'),
            metricsFontSizeNumber: $('#popover_metrics_font_size_number'),
            metricsLabelFontSizeNumber: $('#popover_metrics_label_font_size_number'),

            // Metrics checkboxes
            metricsCheckboxes: $('input[name="greenmetrics_settings[popover_metrics][]"]')
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

        // Initialize accordions for this tab
        initAccordionsForTab(tabId);
    }

    /**
     * Initialize accordions functionality
     *
     * @function initAccordions
     * @private
     */
    function initAccordions() {
        // Get active tab
        var activeTab = $cache.tabItems.filter('.active').data('tab');

        // Initialize accordions for the active tab
        initAccordionsForTab(activeTab);
    }

    /**
     * Initialize accordions for a specific tab
     *
     * @function initAccordionsForTab
     * @private
     * @param {string} tabId - The ID of the tab
     */
    function initAccordionsForTab(tabId) {
        // Try to restore accordion state from localStorage
        var activeAccordion = localStorage.getItem(STORAGE_KEY_ACCORDION_PREFIX + tabId);
        var $currentTabContent = $('#tab-' + tabId);
        var $tabAccordionTriggers = $currentTabContent.find('.accordion-trigger');

        if (activeAccordion && $tabAccordionTriggers.filter('[data-card="' + activeAccordion + '"]').length) {
            // Open the previously active accordion
            var $trigger = $tabAccordionTriggers.filter('[data-card="' + activeAccordion + '"]');
            $trigger.addClass('active');
            $trigger.next('.accordion-content').show();
        } else {
            // Open the first accordion in the tab by default
            var $firstTrigger = $tabAccordionTriggers.first();
            if ($firstTrigger.length) {
                $firstTrigger.addClass('active');
                $firstTrigger.next('.accordion-content').show();
            }
        }
    }

    /**
     * Initialize form submission handling
     *
     * @function initFormSubmission
     * @private
     */
    function initFormSubmission() {
        // Handle form submission
        $cache.form.on('submit', function() {
            // Ensure all form fields are included in the submission
            // by temporarily showing all accordion contents
            $cache.accordionContents.each(function() {
                if (!$(this).is(':visible')) {
                    $(this).addClass('temp-visible').css('display', 'block').css('height', '0').css('overflow', 'hidden');
                }
            });

            // Store the form data in localStorage before submitting
            localStorage.setItem('greenmetrics_display_settings_submitted', 'true');

            // Use setTimeout to ensure the form is submitted after the DOM is updated
            setTimeout(function() {
                // Remove temporary visibility after form is submitted
                $('.accordion-content.temp-visible').removeClass('temp-visible').css('display', '').css('height', '').css('overflow', '');
            }, 0);
        });

        // Check if we need to show a notice after form submission
        if (localStorage.getItem('greenmetrics_display_settings_submitted') === 'true') {
            // Display a success notice
            if (typeof GreenMetricsErrorHandler !== 'undefined' &&
                typeof GreenMetricsErrorHandler.displayAdminNotice === 'function') {
                GreenMetricsErrorHandler.displayAdminNotice(
                    'Display settings saved successfully!',
                    'success',
                    true
                );
            }

            // Clear the flag
            localStorage.removeItem('greenmetrics_display_settings_submitted');
        }
    }

    /**
     * Initialize preview functionality
     *
     * @function initPreview
     * @private
     */
    function initPreview() {
        // Initialize font size fields
        initFontSizeFields();

        // Initialize color pickers
        initColorPickers();

        // Set initial state of icon fields
        toggleIconOptions();

        // Update preview
        updatePreview();
    }

    /**
     * Initialize font size fields
     *
     * @function initFontSizeFields
     * @private
     */
    function initFontSizeFields() {
        // Set the number input value from the hidden field
        $cache.contentFontSizeNumber.val(parseInt($cache.popoverContentFontSize.val()));
        $cache.metricsFontSizeNumber.val(parseInt($cache.popoverMetricsFontSize.val()));
        $cache.metricsLabelFontSizeNumber.val(parseInt($cache.popoverMetricsLabelFontSize.val()));
    }

    /**
     * Initialize color pickers
     *
     * @function initColorPickers
     * @private
     */
    function initColorPickers() {
        // Initialize color pickers properly
        $('.greenmetrics-color-picker').wpColorPicker({
            change: function(event, ui) {
                // Trigger change event after color is picked
                setTimeout(function() {
                    $(event.target).val(ui.color.toString()).trigger('change');
                    updatePreview();
                }, 100);
            },
            clear: function() {
                // Update preview after color is cleared
                setTimeout(function() {
                    updatePreview();
                }, 100);
            }
        });
    }

    /**
     * Toggle icon options based on display_icon checkbox
     *
     * @function toggleIconOptions
     * @private
     */
    function toggleIconOptions() {
        const isChecked = $cache.displayIcon.is(':checked');
        // Toggle visibility of icon-related settings
        if (isChecked) {
            $('.form-field.icon-settings').show();
            // Show custom icon field only if "custom" is selected
            if ($cache.badgeIconType.val() === 'custom') {
                $('.form-field.custom-icon-field').addClass('visible');
            }
        } else {
            $('.form-field.icon-settings').hide();
            $('.form-field.custom-icon-field').removeClass('visible');
        }
    }

    /**
     * Update preview
     *
     * @function updatePreview
     * @private
     */
    function updatePreview() {
        const badgeText = $cache.badgeText.val();
        const backgroundColor = $cache.badgeBgColor.val();
        const textColor = $cache.badgeTextColor.val();
        const displayIcon = $cache.displayIcon.is(':checked');
        const iconType = $cache.badgeIconType.val();
        const iconColor = $cache.badgeIconColor.val();
        const iconSize = $cache.badgeIconSize.val();
        const customIcon = $cache.badgeCustomIcon.val();
        const badgePosition = $cache.badgePosition.val();
        const badgeSize = $cache.badgeSize.val();

        // Update badge position
        $cache.badgePreviewContainer.attr('class', badgePosition);

        // Update badge size
        $cache.badge.attr('class', 'greenmetrics-badge ' + badgeSize);

        // Update the badge text and colors
        $cache.badge.find('span').text(badgeText);
        $cache.badge.css({
            'background-color': backgroundColor,
            'color': textColor
        });

        // Update icon
        if (displayIcon) {
            $cache.iconContainer.show();
            $cache.iconContainer.css('color', iconColor);

            if (iconType === 'custom' && customIcon) {
                // For custom icons, use the uploaded image
                $cache.iconContainer.html('<img src="' + customIcon + '" alt="' + customIconText + '" style="width: ' + iconSize + '; height: ' + iconSize + ';">');
            } else {
                // For predefined icons, get them from the server
                getIconSvg(iconType, function(svgContent) {
                    // Make sure SVG uses currentColor for proper color inheritance
                    if (!svgContent.includes('fill="currentColor"')) {
                        svgContent = svgContent.replace(/<svg/, '<svg fill="currentColor"');
                    }
                    $cache.iconContainer.html('<div style="width: ' + iconSize + '; height: ' + iconSize + ';">' + svgContent + '</div>');
                });
            }
        } else {
            $cache.iconContainer.hide();
        }

        // Get popover settings
        const popoverTitle = $cache.popoverTitle.val();
        const popoverBgColor = $cache.popoverBgColor.val();
        const popoverTextColor = $cache.popoverTextColor.val();
        const popoverMetricsColor = $cache.popoverMetricsColor.val();
        const popoverMetricsBgColor = $cache.popoverMetricsBgColor.val();
        const popoverContentFont = $cache.popoverContentFont.val();
        const popoverContentFontSize = $cache.popoverContentFontSize.val();
        const popoverMetricsFont = $cache.popoverMetricsFont.val();
        const popoverMetricsFontSize = $cache.popoverMetricsFontSize.val();
        const popoverMetricsLabelFontSize = $cache.popoverMetricsLabelFontSize.val();
        const popoverMetricsListBgColor = $cache.popoverMetricsListBgColor.val();
        const popoverMetricsListHoverBgColor = $cache.popoverMetricsListHoverBgColor.val();
        const popoverCustomContent = $cache.popoverCustomContent.val();

        // Update popover title
        $cache.popoverPreviewContainer.find('h3').text(popoverTitle);

        // Update popover container styling
        $cache.popoverPreviewContainer.css({
            'background-color': popoverBgColor,
            'color': popoverTextColor,
            'font-family': popoverContentFont,
            'font-size': popoverContentFontSize
        });

        // Update metric values styling
        $('.greenmetrics-global-badge-metric-value').css({
            'color': popoverMetricsColor,
            'font-family': popoverMetricsFont,
            'font-size': popoverMetricsFontSize,
            'background': popoverMetricsBgColor
        });

        // Update metric labels styling
        $('.greenmetrics-global-badge-metric-label').css({
            'font-size': popoverMetricsLabelFontSize
        });

        // Update metric list item styling
        $('.greenmetrics-global-badge-metric').css({
            'background-color': popoverMetricsListBgColor
        });

        // Get selected metrics
        const selectedMetrics = [];
        $cache.metricsCheckboxes.filter(':checked').each(function() {
            selectedMetrics.push($(this).val());
        });

        // Show/hide metrics based on selection
        $('.greenmetrics-global-badge-metric').each(function() {
            const metricKey = $(this).data('metric');
            $(this).toggle(selectedMetrics.includes(metricKey));
        });

        // Apply hover styles
        // Add hover style dynamically for better preview
        const styleId = 'greenmetrics-preview-hover-style';
        if ($('#' + styleId).length === 0) {
            $('head').append('<style id="' + styleId + '"></style>');
        }
        $('#' + styleId).html('.greenmetrics-global-badge-metric:hover { background-color: ' + popoverMetricsListHoverBgColor + ' !important; }');

        // Update popover custom content
        if (popoverCustomContent) {
            if ($('.greenmetrics-global-badge-custom-content').length === 0) {
                $cache.popoverPreviewContainer.append('<div class="greenmetrics-global-badge-custom-content" style="margin-top: 15px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,0.1);"></div>');
            }
            $('.greenmetrics-global-badge-custom-content').html(popoverCustomContent);
        } else {
            $('.greenmetrics-global-badge-custom-content').remove();
        }
    }

    /**
     * Get icon SVG using the backend GreenMetrics_Icons class
     *
     * @function getIconSvg
     * @private
     * @param {string} iconType - The type of icon to get
     * @param {function} callback - Callback function to handle the SVG content
     */
    function getIconSvg(iconType, callback) {
        // Call our endpoint to get the SVG content
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'greenmetrics_get_icon',
                icon_type: iconType,
                nonce: greenmetricsAdmin.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    callback(response.data);
                } else {
                    console.error('Failed to get icon SVG:', response);
                    callback(''); // Return empty string on error
                }
            },
            error: function(error) {
                console.error('AJAX error when getting icon:', error);
                callback(''); // Return empty string on error
            }
        });
    }

    /**
     * Open media uploader for custom icon
     *
     * @function openMediaUploader
     * @private
     */
    function openMediaUploader() {
        // Create media frame if it doesn't exist
        if (!mediaFrame) {
            mediaFrame = wp.media({
                title: greenmetricsAdmin.selectIconText || 'Select or Upload Icon',
                button: {
                    text: greenmetricsAdmin.selectIconBtnText || 'Use this Icon'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            // When image selected, run callback
            mediaFrame.on('select', function() {
                const attachment = mediaFrame.state().get('selection').first().toJSON();
                $cache.badgeCustomIcon.val(attachment.url);
                updatePreview();
            });
        }

        // Open frame
        mediaFrame.open();
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

        // Accordion click handler
        $cache.accordionTriggers.on('click', function() {
            var cardId = $(this).data('card');
            var $content = $(this).next('.accordion-content');
            var isActive = $(this).hasClass('active');
            var currentTab = $cache.tabItems.filter('.active').data('tab');
            var $currentTab = $(this).closest('.greenmetrics-tab-content');

            // Close all accordions in the current tab
            $currentTab.find('.accordion-trigger').removeClass('active');
            $currentTab.find('.accordion-content').slideUp(ANIMATION_SPEED);

            // Toggle the clicked accordion
            if (!isActive) {
                $(this).addClass('active');
                $content.slideDown(ANIMATION_SPEED);

                // Store the active accordion in localStorage
                localStorage.setItem(STORAGE_KEY_ACCORDION_PREFIX + currentTab, cardId);
            } else {
                // This branch won't execute in normal circumstances since we're closing all accordions above
                localStorage.removeItem(STORAGE_KEY_ACCORDION_PREFIX + currentTab);
            }
        });

        // Update preview when form fields change
        $('#enable_badge, #badge_position, #badge_size, #badge_text, #badge_background_color, #badge_text_color, ' +
            '#display_icon, #badge_icon_type, #badge_icon_color, #badge_icon_size, #badge_custom_icon, ' +
            '#popover_title, #popover_custom_content, #popover_bg_color, #popover_text_color, #popover_metrics_color, ' +
            '#popover_metrics_bg_color, #popover_content_font, #popover_metrics_font, #popover_metrics_list_bg_color, ' +
            '#popover_content_font_size_number, #popover_metrics_font_size_number, #popover_metrics_label_font_size_number, ' +
            '#popover_metrics_list_hover_bg_color')
        .on('change input', function() {
            updatePreview();
        });

        // Toggle icon-related fields based on Display Icon checkbox
        $cache.displayIcon.on('change', function() {
            toggleIconOptions();
            updatePreview();
        });

        // Toggle custom icon field based on icon type selection
        $cache.badgeIconType.on('change', function() {
            if ($(this).val() === 'custom') {
                $('.form-field.custom-icon-field').addClass('visible');
            } else {
                $('.form-field.custom-icon-field').removeClass('visible');
            }
            updatePreview();
        });

        // Handle font size number input changes
        $('#popover_content_font_size_number, #popover_metrics_font_size_number, #popover_metrics_label_font_size_number').on('change input', function() {
            // Update hidden field value
            var targetId = $(this).attr('id').replace('_number', '');
            $('#' + targetId).val($(this).val() + 'px');
            updatePreview();
        });

        // Listen for checkbox changes in metrics
        $cache.metricsCheckboxes.on('change', function() {
            updatePreview();
        });

        // Media uploader for custom icon
        $('.upload-custom-icon').on('click', function(e) {
            e.preventDefault();
            openMediaUploader();
        });
    }

    // Return public API
    return {
        init: init
    };

})(jQuery);
