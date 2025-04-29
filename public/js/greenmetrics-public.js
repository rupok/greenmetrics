/**
 * GreenMetrics Public JavaScript
 */
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
            
            const data = {
                page_id: greenmetricsPublic.page_id,
                data_transfer: totalTransferSize,
                load_time: loadTimeSeconds, // Send in seconds
                requests: requests
            };
            
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
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Success handling without console logs
            })
            .catch(error => {
                // Error handling without console logs
            });
        });
    }

    // Initialize tracking if enabled
    if (greenmetricsPublic && greenmetricsPublic.tracking_enabled) {
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

    /**
     * Initialize badges
     */
    function initBadges() {
        // Initialize both new SVG icons and legacy data-icon-name elements
        
        // First handle existing direct SVGs (make sure they have proper styling)
        $('.wp-block-greenmetrics-badge__icon div svg').each(function() {
            $(this).css({
                'width': '100%',
                'height': '100%',
                'fill': 'currentColor'
            });
        });
        
        // Then handle data-icon-name elements that need SVG loading
        $('.wp-block-greenmetrics-badge__icon div[data-icon-name]').each(function() {
            const $icon = $(this);
            const iconName = $icon.data('icon-name') || 'leaf';
            
            // Load SVG icons through AJAX
            $.ajax({
                url: greenmetricsPublic.ajax_url,
                type: 'POST',
                data: {
                    action: 'greenmetrics_get_icon',
                    nonce: greenmetricsPublic.nonce,
                    icon_type: iconName
                },
                success: function(response) {
                    if (response.success && response.data) {
                        $icon.html(response.data);
                    }
                },
                error: function(xhr, status, error) {
                    // Error handling without console logs
                }
            });
        });

        // Apply hover behavior for badge blocks as well
        $('.wp-block-greenmetrics-badge-wrapper').hover(
            function() {
                // Preserve any custom properties when showing content
                const $content = $(this).find('.wp-block-greenmetrics-content');
                const currentStyle = $content.attr('style') || '';
                
                $content.css({
                    'opacity': '1',
                    'visibility': 'visible',
                    'transform': 'translateY(0)'
                });
            },
            function() {
                // Preserve any custom properties when hiding content
                const $content = $(this).find('.wp-block-greenmetrics-content');
                const currentStyle = $content.attr('style') || '';
                
                $content.css({
                    'opacity': '0',
                    'visibility': 'hidden',
                    'transform': 'translateY(-10px)'
                });
            }
        );
        
        // Ensure font styles are applied
        ensureFontStyles();
    }
    
    /**
     * Ensure font styles are applied
     */
    function ensureFontStyles() {
        // No need to process CSS variables since we've switched to direct inline styles
        // Our approach now uses the class-based and inline font-family approach
        
        // Ensure SVG icons are properly styled
        $('.wp-block-greenmetrics-badge__icon div svg').each(function() {
            $(this).css({
                'width': '100%',
                'height': '100%',
                'fill': 'currentColor'
            });
        });
    }

    /**
     * Initialize on DOM ready
     */
    $(function() {
        initBadges();
    });

})(jQuery); 