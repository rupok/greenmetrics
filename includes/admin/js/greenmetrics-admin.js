;(function($) {
  'use strict';
  $(document).ready(function() {
    // Cached selectors
    var $submitBtn = $('#submit');
    var $checkboxes = $('input[type="checkbox"]');
    var $selects = $('select');
    var $texts = $('input[type="text"]');
    var $iconOptions = $('.icon-option');

    // Debounce helper for badge preview
    function debounce(fn, delay) {
      var timer;
      return function() {
        var context = this, args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function() {
          fn.apply(context, args);
        }, delay);
      };
    }
    var debouncedUpdateBadgePreview = debounce(updateBadgePreview, 300);

    // Enable submit button and update badge preview
    function markDirty() {
      $submitBtn.prop('disabled', false).removeClass('button-disabled');
      updateBadgePreview();
    }

    // Define default colors
    const defaultColors = {
      'badge_background_color': '#4CAF50',
      'badge_text_color': '#ffffff',
      'badge_icon_color': '#ffffff',
      'popover_bg_color': '#ffffff',
      'popover_text_color': '#333333',
      'popover_metrics_color': '#4CAF50',
      'popover_metrics_bg_color': 'rgba(0, 0, 0, 0.05)'
    };
    
    // Initialize color pickers with alpha support
    $('.greenmetrics-color-picker').each(function() {
      const $this = $(this);
      const fieldId = $this.attr('id');
      
      $this.wpColorPicker({
        defaultColor: defaultColors[fieldId] || '#ffffff',
        change: function(event, ui) {
          // Update preview when color changes
          updateBadgePreview();
        },
        clear: function() {
          // Set to default color when clear is clicked
          const defaultColor = defaultColors[fieldId] || '#ffffff';
          setTimeout(function() {
            $this.val(defaultColor).trigger('change');
            $this.wpColorPicker('color', defaultColor);
            updateBadgePreview();
          }, 50);
        }
      });
    });
    
    // Replace all "Clear" buttons with "Set to Default" buttons
    setTimeout(function() {
      $('.wp-picker-clear').each(function() {
        $(this).text('Set to Default');
      });
    }, 100);

    // Update badge and popover preview when settings change
    $('#enable_badge, #badge_position, #badge_size, #badge_text, #badge_background_color, #badge_text_color, #badge_icon_color, ' +
      '#popover_title, #popover_custom_content, #popover_bg_color, #popover_text_color, #popover_metrics_color, #popover_metrics_bg_color, ' +
      '#popover_content_font, #popover_content_font_size, #popover_metrics_font, #popover_metrics_font_size')
    .on('change input', function() {
      updateBadgePreview();
    });
    
    // Listen for checkbox changes in metrics
    $('input[name="greenmetrics_settings[popover_metrics][]"]').on('change', function() {
      updateBadgePreview();
    });
    
    function updatePreview() {
      // Get current badge settings
      const position = $('#badge_position').val();
      const size = $('#badge_size').val();
      const text = $('#badge_text').val();
      const bgColor = $('#badge_background_color').val();
      const textColor = $('#badge_text_color').val();
      const iconColor = $('#badge_icon_color').val();
      
      // Update badge position
      $('#badge-preview-container').attr('class', position);
      
      // Update badge appearance
      const $badge = $('#badge-preview-container .greenmetrics-badge');
      $badge.attr('class', 'greenmetrics-badge ' + size);
      $badge.css({
        'background-color': bgColor,
        'color': textColor
      });
      
      // Update badge text
      $badge.find('span').text(text);
      
      // Update badge icon color
      $badge.find('svg').css('fill', iconColor);
      
      // Get current popover settings
      const popoverTitle = $('#popover_title').val();
      const popoverBgColor = $('#popover_bg_color').val();
      const popoverTextColor = $('#popover_text_color').val();
      const popoverMetricsColor = $('#popover_metrics_color').val();
      const popoverMetricsBgColor = $('#popover_metrics_bg_color').val();
      const popoverContentFont = $('#popover_content_font').val();
      const popoverContentFontSize = $('#popover_content_font_size').val();
      const popoverMetricsFont = $('#popover_metrics_font').val();
      const popoverMetricsFontSize = $('#popover_metrics_font_size').val();
      const popoverCustomContent = $('#popover_custom_content').val();
      
      // Get selected metrics
      const selectedMetrics = [];
      $('input[name="greenmetrics_settings[popover_metrics][]"]:checked').each(function() {
        selectedMetrics.push($(this).val());
      });
      
      // Update popover title
      $('#popover-preview-container h3').text(popoverTitle);
      
      // Update popover container styling
      $('#popover-preview-container').css({
        'background-color': popoverBgColor,
        'color': popoverTextColor,
        'font-family': popoverContentFont,
        'font-size': popoverContentFontSize
      });
      
      // Update popover custom content
      let customContent = '';
      if (popoverCustomContent) {
        customContent = '<div class="greenmetrics-global-badge-custom-content" style="margin-top: 15px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,0.1);">' + popoverCustomContent + '</div>';
      }
      $('#popover-preview-container .greenmetrics-global-badge-custom-content').html(customContent);
      
      // Update metric values styling
      $('#popover-preview-container .greenmetrics-global-badge-metric-value').css({
        'color': popoverMetricsColor,
        'font-family': popoverMetricsFont,
        'font-size': popoverMetricsFontSize,
        'background-color': popoverMetricsBgColor
      });
      
      // Show/hide metrics based on selection
      $('#popover-preview-container .greenmetrics-global-badge-metric').each(function() {
        const metricKey = $(this).data('metric');
        $(this).toggle(selectedMetrics.length === 0 || selectedMetrics.includes(metricKey));
      });
    }
    
    // Set initial preview
    updatePreview();
    
    // Auto-dismiss notice after 5 seconds if present
    setTimeout(function() {
      // Auto-dismiss all success notices
      $('.notice-success.is-dismissible').fadeOut(500, function() {
        $(this).remove();
      });
      
      // For URL parameter specific notices
      if (window.location.search.indexOf('settings-updated=true') > -1 || 
          window.location.search.indexOf('settings-updated=1') > -1) {
        $('.notice').fadeOut(500, function() {
          $(this).remove();
        });
      }
    }, 5000);

    // Only initialize dashboard if we're on the dashboard page
    if ($('#greenmetrics-stats').length) {
      // Function to initialize dashboard
      function initDashboard() {
        // Load initial stats
        getStats();

        // Set up event listeners
        setupEventListeners();
      }

      // Get stats
      function getStats() {
        if (typeof greenmetricsAdmin !== 'undefined' && greenmetricsAdmin.rest_url) {
          $.ajax({
            url: greenmetricsAdmin.rest_url + 'greenmetrics/v1/metrics',
            type: 'GET',
            beforeSend: function(xhr) {
              xhr.setRequestHeader('X-WP-Nonce', greenmetricsAdmin.rest_nonce);
            },
            success: function(response) {
              updateStatsDisplay(response);
            },
            error: function(xhr, status, error) {
              console.error('Error getting stats:', error);
              $('#greenmetrics-stats').html('<p class="error">Error loading stats. Please try again.</p>');
            }
          });
        }
      }

      // Update stats display
      function updateStatsDisplay(stats) {
        if ($('#greenmetrics-stats').length && stats) {
          const html = `
            <div class="stats-grid">
              <div class="stat-card">
                <h3>Total Views</h3>
                <p class="stat-value">${stats.total_views}</p>
              </div>
              <div class="stat-card">
                <h3>Carbon Footprint</h3>
                <p class="stat-value">${stats.carbon_footprint.toFixed(2)} g CO2</p>
              </div>
              <div class="stat-card">
                <h3>Energy Consumption</h3>
                <p class="stat-value">${stats.energy_consumption.toFixed(2)} kWh</p>
              </div>
              <div class="stat-card">
                <h3>Data Transfer</h3>
                <p class="stat-value">${stats.avg_data_transfer.toFixed(2)} KB</p>
              </div>
              <div class="stat-card">
                <h3>Requests</h3>
                <p class="stat-value">${stats.requests}</p>
              </div>
              <div class="stat-card">
                <h3>Performance Score</h3>
                <p class="stat-value">${stats.performance_score.toFixed(2)}%</p>
              </div>
            </div>
          `;
          $('#greenmetrics-stats').html(html);
        }
      }

      // Initialize dashboard
      initDashboard();
    }

    // Function to set up event listeners
    function setupEventListeners() {
      // Handle checkbox changes
      $checkboxes.on('change', function() {
        if ($(this).attr('id') === 'display_icon') {
          toggleIconOptions();
        }
        markDirty();
      });

      // Handle select field changes
      $selects.on('change', function() {
        if ($(this).attr('id') === 'badge_icon_type') {
          const iconType = $(this).val();
          
          // Update visual selection
          $('.icon-option').removeClass('selected');
          $('.icon-option[data-value="' + iconType + '"]').addClass('selected');
          
          if ($('#display_icon').is(':checked')) {
            if (iconType === 'custom') {
              $('#badge_custom_icon').closest('tr').show();
              $('#custom-icon-field-wrapper').show();
            } else {
              $('#badge_custom_icon').closest('tr').hide();
            }
          }
        }

        markDirty();
      });

      // Handle text field changes
      $texts.on('input', function() {
        $submitBtn.prop('disabled', false).removeClass('button-disabled');
        debouncedUpdateBadgePreview();
      });

      // Handle icon selection - completely rewritten
      $('.icon-option').on('click', function() {
        const value = $(this).data('value');
        
        // Update the select field
        $('#badge_icon_type').val(value).trigger('change');
        
        // Update visual selection
        $('.icon-option').removeClass('selected');
        $(this).addClass('selected');
        
        // Only show custom icon upload if display_icon is checked and custom is selected
        if ($('#display_icon').is(':checked') && value === 'custom') {
          $('#badge_custom_icon').closest('tr').show();
          $('#custom-icon-field-wrapper').show();
        } else {
          $('#badge_custom_icon').closest('tr').hide();
        }
        
        // Update preview with the new icon type
        updateBadgePreview();
      });

      // Handle custom icon upload button
      $('.upload-custom-icon').on('click', function(e) {
        e.preventDefault();

        const customIconField = $('#badge_custom_icon');

        // Create a media frame
        const mediaFrame = wp.media({
          title: greenmetricsAdmin.selectIconText || 'Select or Upload Icon',
          button: {
            text: greenmetricsAdmin.selectIconBtnText || 'Use this Icon'
          },
          multiple: false
        });

        // When an image is selected in the media frame
        mediaFrame.on('select', function() {
          const attachment = mediaFrame.state().get('selection').first().toJSON();
          customIconField.val(attachment.url);
          updateBadgePreview();
        });

        // Open the media frame
        mediaFrame.open();
      });

      // Handle refresh stats button
      $('#refresh-stats').on('click', function(e) {
        e.preventDefault();
        $.ajax({
          url: greenmetricsAdmin.ajaxUrl,
          type: 'POST',
          data: {
            action: 'greenmetrics_refresh_stats',
            nonce: greenmetricsAdmin.nonce
          },
          success: function(response) {
            if (response.success) {
              showNotice(greenmetricsAdmin.refreshMessage, 'success');
              getStats();
            } else {
              showNotice(greenmetricsAdmin.refreshError, 'error');
            }
          }
        });
      });
    }

    // Function to toggle icon options based on display_icon checkbox
    function toggleIconOptions() {
      var displayIcon = $('#display_icon').is(':checked');
      var iconType = $('#badge_icon_type').val();

      if (displayIcon) {
        // Show icon type selection and icon color
        $('.greenmetrics-icon-selection').closest('tr').show();
        $('#badge_icon_color').closest('tr').show();

        // Show custom icon field only if custom is selected
        if (iconType === 'custom') {
          $('#badge_custom_icon').closest('tr').show();
          $('#custom-icon-field-wrapper').show();
        } else {
          $('#badge_custom_icon').closest('tr').hide();
        }
      } else {
        // Hide all icon-related fields
        $('.greenmetrics-icon-selection').closest('tr').hide();
        $('#badge_icon_color').closest('tr').hide();
        $('#badge_custom_icon').closest('tr').hide();
      }
    }

    // Set up event listeners for the icon-related fields
    $('#display_icon').on('change', function() {
      toggleIconOptions();
      updateBadgePreview();
    });

    // Update badge preview
    function updateBadgePreview() {
      if (!$('#badge-preview-container').length) return;

      // Get current settings
      const position = $('#badge_position').val();
      const size = $('#badge_size').val();
      const text = $('#badge_text').val();
      const bgColor = $('#badge_background_color').val();
      const textColor = $('#badge_text_color').val();
      const iconColor = $('#badge_icon_color').val();
      const displayIcon = $('#display_icon').is(':checked');
      const iconType = $('#badge_icon_type').val();
      const customIcon = $('#badge_custom_icon').val();

      // Update position
      $('#badge-preview-container').attr('class', position);

      // Update badge appearance
      const $badge = $('#badge-preview-container .greenmetrics-badge');
      $badge.attr('class', 'greenmetrics-badge ' + size);
      $badge.css({
        'background-color': bgColor,
        'color': textColor
      });

      // Update text
      $badge.find('span').text(text);

      // Update icon visibility and type
      if (displayIcon) {
        // Show icon container
        $badge.find('.icon-container').show();

        // Update icon based on type
        let iconSvg = '';

        switch(iconType) {
          case 'leaf':
            iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 8C8 10 5.9 16.17 3.82 21.34l1.89.66.95-2.3c.48.17 1.02.3 1.58.3C17 20 22 13.46 22 6c0-.55-.06-1.09-.14-1.62C20.18 4.15 18.66 4 17 4V2c1.67 0 3.35.12 5 .34V4c-1.67-.22-3.33-.34-5-.34v2zM2 6c0 7.46 5 14 14.5 14 .56 0 1.1-.13 1.58-.3l.95 2.3 1.89-.66C18.1 16.17 16 10 7 8c0 0-5 0-5 0z"/></svg>';
            break;
          case 'tree':
            iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 22c4.97 0 9-4.03 9-9-4.97 0-9 4.03-9 9zm2.44-9.43h-.44v2h.44c2.32 0 2.49 3.23 2.49 3.23 1.52-1.84 2.63-4.43 1.73-7C17.56 8.37 15.5 7 15.5 7S14.8 9.1 13 9.42v.36c1.32-.18 2.44.11 2.44.11s-1.22 1.91-1 3.68z"/><path d="M12.28 10h-.56v2h.56c2.33 0 2.51 3.45 2.51 3.45 1.55-1.89 2.67-4.63 1.77-7.24-.51-1.46-2.18-3.02-2.18-3.02s-.99 2.18-2.1 2.48V8c1.34-.2 2.55.07 2.55.07s-1.34 1.66-1.14 3.44z"/><path d="M12.63 5.33c-.28.47-1.04 1.68-2 1.87V8.8c1.35-.19 2.97.31 2.97.31S12.69 10.3 12.22 12h.33v-2h-.16c.06-.32.2-.65.44-.97.19.38.39.75.58 1.09l.66-.42c-.18-.28-.33-.57-.46-.85 0 0 .99.17 2.22.5-.27-.5-2.47-4.02-3.2-4.02z"/><path d="M10.45 12h-.43v8.17c.34-.14.66-.34.95-.55L10.45 12zm1.66 4.62c.1.21.19.42.27.63-.16-.19-.31-.39-.46-.57.07-.02.12-.04.19-.06zm1.14-4.62L12.1 17.1c.45-.11.88-.29 1.29-.51l-.14-4.59z"/><path d="M9.3 14.13l-.24 7.14c.24.11.48.19.73.26l-.42-7.8c-.02.14-.05.27-.07.4zm3.33 1.7c-.04-.04-.08-.09-.12-.14.03.05.06.09.09.13.01 0 .02.01.03.01zm-.83-3.83l-.32 7.46c.29.05.58.08.88.08.12 0 .24-.01.36-.02L12 12l-.2 0z"/></svg>';
            break;
          case 'globe':
            iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>';
            break;
          case 'recycle':
            iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5.77 7.15L7.2 4.78l1.03-1.71c.39-.65 1.33-.65 1.72 0l1.48 2.46-1.23 2.06-1 1.34-2.43-4.78zm15.95 5.82l-1.6-2.66-3.46 2L18.87 16H21v2l-3.87-7.03zM16 21h1.5l2.05-3.42-3.46-2-1.09 1.84L16 21zm-3.24-3.71l-1.03-1.71-1.43 2.43-2.43 4.78 1.6 2.66 3.46-2 1.03-1.71-1.43-2.45zM13.42 8.5l-1.48-2.46c-.39-.65-1.33-.65-1.72 0L9.22 7.15l-1 1.34 2.43 4.78 1.6-2.66 1.17-2.11zM10.5 14.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>';
            break;
          case 'custom':
            if (customIcon) {
              iconSvg = `<img src="${customIcon}" alt="Custom Icon" style="width: 20px; height: 20px;">`;
            } else {
              iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>';
            }
            break;
          default:
            iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 8C8 10 5.9 16.17 3.82 21.34l1.89.66.95-2.3c.48.17 1.02.3 1.58.3C17 20 22 13.46 22 6c0-.55-.06-1.09-.14-1.62C20.18 4.15 18.66 4 17 4V2c1.67 0 3.35.12 5 .34V4c-1.67-.22-3.33-.34-5-.34v2zM2 6c0 7.46 5 14 14.5 14 .56 0 1.1-.13 1.58-.3l.95 2.3 1.89-.66C18.1 16.17 16 10 7 8c0 0-5 0-5 0z"/></svg>';
        }

        // Check if icon container already exists, if not create it
        if ($badge.find('.icon-container').length === 0) {
          $badge.prepend('<div class="icon-container" style="color:' + iconColor + ';">' + iconSvg + '</div>');
        } else {
          $badge.find('.icon-container').html(iconSvg).css('color', iconColor);
        }
      } else {
        // Remove or hide icon
        $badge.find('.icon-container').hide();
      }

      // Get current popover settings for preview update
      const popoverTitle = $('#popover_title').val();
      const popoverBgColor = $('#popover_bg_color').val();
      const popoverTextColor = $('#popover_text_color').val();
      const popoverMetricsColor = $('#popover_metrics_color').val();
      const popoverMetricsBgColor = $('#popover_metrics_bg_color').val();
      const popoverContentFont = $('#popover_content_font').val();
      const popoverContentFontSize = $('#popover_content_font_size').val();
      const popoverMetricsFont = $('#popover_metrics_font').val();
      const popoverMetricsFontSize = $('#popover_metrics_font_size').val();
      const popoverCustomContent = $('#popover_custom_content').val();
      
      // Get selected metrics
      const selectedMetrics = [];
      $('input[name="greenmetrics_settings[popover_metrics][]"]:checked').each(function() {
        selectedMetrics.push($(this).val());
      });
      
      // Update popover title
      $('#popover-preview-container h3').text(popoverTitle);
      
      // Update popover container styling
      $('#popover-preview-container').css({
        'background-color': popoverBgColor,
        'color': popoverTextColor,
        'font-family': popoverContentFont,
        'font-size': popoverContentFontSize
      });
      
      // Update popover custom content
      let customContent = '';
      if (popoverCustomContent) {
        customContent = '<div class="greenmetrics-global-badge-custom-content" style="margin-top: 15px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,0.1);">' + popoverCustomContent + '</div>';
      }
      $('#popover-preview-container .greenmetrics-global-badge-custom-content').html(customContent);
      
      // Update metric values styling
      $('#popover-preview-container .greenmetrics-global-badge-metric-value').css({
        'color': popoverMetricsColor,
        'font-family': popoverMetricsFont,
        'font-size': popoverMetricsFontSize,
        'background-color': popoverMetricsBgColor
      });
      
      // Show/hide metrics based on selection
      $('#popover-preview-container .greenmetrics-global-badge-metric').each(function() {
        const metricKey = $(this).data('metric');
        $(this).toggle(selectedMetrics.length === 0 || selectedMetrics.includes(metricKey));
      });
    }

    // Show notice - used for displaying success/error messages
    function showNotice(message, type) {
      const notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
      $('.wrap h1').after(notice);
      setTimeout(function() {
        notice.fadeOut(500, function() {
          $(this).remove();
        });
      }, 3000);
    }

    // Initialize UI elements based on current state
    toggleIconOptions();
    
    // Force update the proper selection on load to override database values
    const currentIcon = $('#badge_icon_type').val();
    $('.icon-option').removeClass('selected');
    $('.icon-option[data-value="' + currentIcon + '"]').addClass('selected');
    
    // Make sure custom icon field is visible/hidden appropriately on load
    if ($('#display_icon').is(':checked') && currentIcon === 'custom') {
      $('#badge_custom_icon').closest('tr').show();
      $('#custom-icon-field-wrapper').show();
    } else {
      $('#badge_custom_icon').closest('tr').hide();
    }

    // Update badge preview on page load
    if ($('#badge-preview-container').length) {
      updateBadgePreview();
    }
    
    // Set up event listeners
    setupEventListeners();
  });
})(jQuery);