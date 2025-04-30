/**
 * GreenMetrics Public JavaScript
 */
(function($) {
    'use strict';

    // Polyfill for requestIdleCallback
    const requestIdleCallback = window.requestIdleCallback ||
        function(callback) {
            const start = Date.now();
            return setTimeout(function() {
                callback({
                    didTimeout: false,
                    timeRemaining: function() {
                        return Math.max(0, 50 - (Date.now() - start));
                    }
                });
            }, 1);
        };

    // Track page metrics
    function trackPage() {
        const startTime = performance.now();
        let loadTimeMs = 0;

        // First, just record the load time without doing heavy calculations
        $(window).on('load', function() {
            const endTime = performance.now();
            loadTimeMs = endTime - startTime;

            // Schedule the heavy calculations for when the browser is idle
            // This ensures the page is fully interactive before we do our work
            requestIdleCallback(function(deadline) {
                calculateAndSendMetrics(loadTimeMs);
            }, { timeout: 2000 }); // 2 second timeout to ensure it runs even if the browser is busy
        });

        // Function to calculate and send metrics when the browser is idle
        function calculateAndSendMetrics(loadTimeMs) {
            // Use performance API to measure actual network transfer
            let totalTransferSize = 0;

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
                    // Get more detailed error information
                    return response.json().then(errorData => {
                        // Create a more detailed error object
                        const error = new Error(errorData.message || 'Network response was not ok');
                        error.status = response.status;
                        error.statusText = response.statusText;
                        error.code = errorData.code || 'unknown_error';
                        error.data = errorData.data || {};
                        throw error;
                    }).catch(() => {
                        // If parsing JSON fails, throw the original error with status info
                        const error = new Error('Network response was not ok');
                        error.status = response.status;
                        error.statusText = response.statusText;
                        throw error;
                    });
                }
                return response.json();
            })
            .then(() => {
                // Success handling without console logs
                if (greenmetricsPublic.debug) {
                    console.log('GreenMetrics: Tracking data sent successfully');
                }
            })
            .catch(error => {
                // Enhanced error handling with more context
                if (greenmetricsPublic.debug) {
                    // Create a more detailed error log
                    const errorContext = {
                        message: error.message,
                        status: error.status,
                        statusText: error.statusText,
                        code: error.code || 'unknown_error',
                        data: error.data || {}
                    };

                    console.error('GreenMetrics: Error sending tracking data', errorContext);

                    // Add specific guidance based on error type
                    if (error.status === 403) {
                        console.info('GreenMetrics: Tracking may be disabled in the plugin settings.');
                    } else if (error.status === 500) {
                        console.info('GreenMetrics: Server error occurred. This may be temporary.');
                    } else if (error.status === 404) {
                        console.info('GreenMetrics: API endpoint not found. The plugin may be misconfigured.');
                    }
                }

                // If this is a security error, we might want to refresh the nonce
                if (error.message && (
                    error.message.includes('Security verification failed') ||
                    error.message.includes('Nonce') ||
                    error.message.includes('rest_cookie_invalid_nonce')
                )) {
                    // In a real implementation, we might want to refresh the nonce here
                    // or notify the user to refresh the page
                    if (greenmetricsPublic.debug) {
                        console.warn('GreenMetrics: Security verification failed. The page may need to be refreshed.');
                    }

                    // For tracking errors, we don't want to disrupt the user experience,
                    // so we silently fail without showing any visible errors
                }
            });
        }
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
                error: function() {
                    // Error handling without console logs
                    if (greenmetricsPublic.debug) {
                        console.warn('GreenMetrics: Failed to load icon');
                    }
                }
            });
        });

        // Apply hover behavior for badge blocks as well
        $('.wp-block-greenmetrics-badge-wrapper').hover(
            function() {
                // Show content
                const $content = $(this).find('.wp-block-greenmetrics-content');
                $content.css({
                    'opacity': '1',
                    'visibility': 'visible',
                    'transform': 'translateY(0)'
                });
            },
            function() {
                // Hide content
                const $content = $(this).find('.wp-block-greenmetrics-content');
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