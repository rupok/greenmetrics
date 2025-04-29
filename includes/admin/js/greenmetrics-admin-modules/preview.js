/**
 * GreenMetrics Admin Preview Module
 * Handles the badge and popover preview functionality
 */
// Ensure namespace exists
var GreenMetricsAdmin = GreenMetricsAdmin || {};

// Add preview functionality to namespace
GreenMetricsAdmin.Preview = (function($) {
  'use strict';
  
  // Module variables
  var mediaFrame;
  var debouncedUpdateBadgePreview;
  
  // Initialize the preview functionality
  function init() {
    // Only proceed if we're on a plugin settings page
    if (!greenmetricsAdmin.is_plugin_page) {
      return;
    }
    
    // Check if badge preview elements exist
    if (!$('#badge-preview-container, #popover-preview-container').length) {
      return;
    }
    
    // Setup debounced preview update
    debouncedUpdateBadgePreview = GreenMetricsAdmin.Utils.debounce(updateBadgePreview, 300);
    
    // Initialize font size fields
    initFontSizeFields();
    
    // Initialize icon options
    toggleIconOptions();
    
    // Run initial preview update
    updateBadgePreview();
    
    // Setup event listeners
    setupEventListeners();
    
    // Initialize color pickers
    setupColorPickers();
  }
  
  // Setup all preview-related event listeners
  function setupEventListeners() {
    // Text inputs for debounced preview update
    $('input[type="text"]').on('keyup', debouncedUpdateBadgePreview);
    
    // Icon options click handler
    $('.icon-option').on('click', function() {
      $('.icon-option').removeClass('selected');
      $(this).addClass('selected');
      $('#badge_icon_type').val($(this).data('icon'));
      GreenMetricsAdmin.Utils.markDirty();
    });
    
    // Update badge and popover preview when settings change
    $('#enable_badge, #badge_position, #badge_size, #badge_text, #badge_background_color, #badge_text_color, #badge_icon_color, ' +
      '#popover_title, #popover_custom_content, #popover_bg_color, #popover_text_color, #popover_metrics_color, #popover_metrics_bg_color, ' +
      '#popover_content_font, #popover_content_font_size, #popover_metrics_font, #popover_metrics_font_size, #popover_metrics_list_bg_color, ' +
      '#popover_metrics_list_hover_bg_color, #badge_icon_size, #badge_icon_type')
    .on('change input', function() {
      updateBadgePreview();
    });
    
    // Handle font size number input changes
    $('#popover_content_font_size_number, #popover_metrics_font_size_number, #popover_metrics_label_font_size_number, #badge_icon_size_number').on('change input', function() {
      // Update hidden field value
      var targetId = $(this).attr('id').replace('_number', '');
      $('#' + targetId).val($(this).val() + 'px');
      updateBadgePreview();
    });
    
    // Listen for checkbox changes in metrics
    $('input[name="greenmetrics_settings[popover_metrics][]"]').on('change', function() {
      updateBadgePreview();
    });
    
    // Handle display icon changes
    $('#display_icon').on('change', function() {
      toggleIconOptions();
      updateBadgePreview();
    });
    
    // Icon option selection
    $('.icon-option').on('click', function() {
      const iconType = $(this).data('value');
      
      // Update select value
      $('#badge_icon_type').val(iconType).trigger('change');
      
      // Update visual selection
      $('.icon-option').removeClass('selected');
      $(this).addClass('selected');
      
      // If custom is selected, show custom icon field
      if (iconType === 'custom') {
        $('#badge_custom_icon').closest('tr').show();
        $('#custom-icon-field-wrapper').show();
      } else {
        $('#badge_custom_icon').closest('tr').hide();
        $('#custom-icon-field-wrapper').hide();
      }
      
      // Mark as changed and update preview with force update
      GreenMetricsAdmin.Utils.markDirty();
      updateBadgePreview(true);
    });
    
    // Badge icon type selection
    $('#badge_icon_type').on('change', function() {
      const iconType = $(this).val();
      
      // Update visual selection of icon options if they exist
      if ($('.icon-option').length) {
        $('.icon-option').removeClass('selected');
        $('.icon-option[data-value="' + iconType + '"]').addClass('selected');
      }
      
      // Show/hide custom icon field
      if (iconType === 'custom') {
        $('#badge_custom_icon').closest('tr').show();
        $('#custom-icon-field-wrapper').show();
      } else {
        $('#badge_custom_icon').closest('tr').hide();
        $('#custom-icon-field-wrapper').hide();
      }
      
      // Mark as changed and update preview with force update
      GreenMetricsAdmin.Utils.markDirty();
      updateBadgePreview(true);
    });
    
    // Custom icon selection
    $('.upload-custom-icon').on('click', function(e) {
      e.preventDefault();
      
      // Create media frame
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
          $('#badge_custom_icon').val(attachment.url);
          updateBadgePreview();
        });
      }
      
      // Open frame
      mediaFrame.open();
    });
  }
  
  // Initialize color pickers
  function setupColorPickers() {
    // Initialize color pickers with alpha support
    $('.greenmetrics-color-picker').each(function() {
      const $this = $(this);
      const fieldId = $this.attr('id');
      
      $this.wpColorPicker({
        defaultColor: GreenMetricsAdmin.core.defaultColors[fieldId] || '#ffffff',
        change: function(event, ui) {
          // Update preview when color changes
          updateBadgePreview();
        },
        clear: function() {
          // Set to default color when clear is clicked
          const defaultColor = GreenMetricsAdmin.core.defaultColors[fieldId] || '#ffffff';
          setTimeout(function() {
            $this.val(defaultColor).trigger('change');
            $this.wpColorPicker('color', defaultColor);
            updateBadgePreview();
          }, 50);
        }
      });
    });
    
    // Special handler for the hover background color
    $('#popover_metrics_list_hover_bg_color').wpColorPicker({
      change: function(event, ui) {
        // Get the color from the UI
        const color = ui.color.toString();
        
        // Directly update the hover style
        const styleId = 'greenmetrics-preview-hover-style';
        if ($('#' + styleId).length === 0) {
          $('head').append('<style id="' + styleId + '"></style>');
        }
        $('#' + styleId).html('#popover-preview-container .greenmetrics-global-badge-metric:hover { background-color: ' + color + ' !important; }');
      }
    });
    
    // Replace all "Clear" buttons with "Set to Default" buttons
    setTimeout(function() {
      $('.wp-picker-clear').each(function() {
        $(this).text('Set to Default');
        
        // Add special handling for metrics bg color clear button
        if ($(this).closest('.wp-picker-container').find('#popover_metrics_bg_color').length) {
          $(this).on('click', function(e) {
            // Add a small delay to let the default clear handler execute first
            setTimeout(function() {
              // Force the correct rgba value after the clear operation
              $('#popover_metrics_bg_color').iris('color', 'rgba(0, 0, 0, 0.05)');
              $('#popover_metrics_bg_color').val('rgba(0, 0, 0, 0.05)').trigger('change');
              
              // Also update the badge preview
              updateBadgePreview();
            }, 100);
          });
        }
      });
    }, 100);
  }
  
  // Function to initialize font size fields
  function initFontSizeFields() {
    // Set the number input value from the hidden field
    $('#popover_content_font_size_number').val(parseInt($('#popover_content_font_size').val()));
    $('#popover_metrics_font_size_number').val(parseInt($('#popover_metrics_font_size').val()));
    $('#popover_metrics_label_font_size_number').val(parseInt($('#popover_metrics_label_font_size').val()));
    $('#badge_icon_size_number').val(parseInt($('#badge_icon_size').val()));
  }
  
  // Function to toggle icon options based on display_icon checkbox
  function toggleIconOptions() {
    var displayIcon = $('#display_icon').is(':checked');
    var iconType = $('#badge_icon_type').val();

    if (displayIcon) {
      // Show icon type selection and icon color
      $('#badge_icon_type').closest('tr').show();
      $('#badge_icon_color').closest('tr').show();
      $('.icon-options').closest('tr').show();

      // Show custom icon field only if custom is selected
      if (iconType === 'custom') {
        $('#badge_custom_icon').closest('tr').show();
        $('#custom-icon-field-wrapper').show();
      } else {
        $('#badge_custom_icon').closest('tr').hide();
        $('#custom-icon-field-wrapper').hide();
      }
    } else {
      // Hide all icon-related fields
      $('#badge_icon_type').closest('tr').hide();
      $('#badge_icon_color').closest('tr').hide();
      $('.icon-options').closest('tr').hide();
      $('#badge_custom_icon').closest('tr').hide();
      $('#custom-icon-field-wrapper').hide();
    }
  }
  
  // Main preview update function
  function updateBadgePreview(force_update) {
    // Get current badge settings
    const position = $('#badge_position').val();
    const size = $('#badge_size').val();
    const text = $('#badge_text').val();
    const bgColor = $('#badge_background_color').val();
    const textColor = $('#badge_text_color').val();
    const iconColor = $('#badge_icon_color').val();
    const displayIcon = $('#display_icon').is(':checked');
    const iconType = $('#badge_icon_type').val();
    const customIcon = $('#badge_custom_icon').val();
    const iconSize = $('#badge_icon_size').val();
    
    // Update badge position
    $('#badge-preview-container').attr('class', position);
    
    // Update badge appearance
    const $badge = $('#badge-preview-container .greenmetrics-badge');
    
    // Make sure to properly apply the size class
    $badge.removeClass('small medium large').addClass(size);
    
    // Ensure the badge has the greenmetrics-badge base class
    if (!$badge.hasClass('greenmetrics-badge')) {
      $badge.addClass('greenmetrics-badge');
    }
    
    $badge.css({
      'background-color': bgColor,
      'color': textColor
    });
    
    // Update badge text
    $badge.find('span').text(text);
    
    // Update icon visibility and appearance
    if (displayIcon) {
      // Show icon container
      if ($badge.find('.icon-container').length === 0) {
        $badge.prepend('<div class="icon-container" style="color:' + iconColor + ';"></div>');
      } else {
        $badge.find('.icon-container').show().css('color', iconColor);
      }
      
      // Only update the icon if it doesn't exist yet or if explicitly changing icon type
      const $iconContainer = $badge.find('.icon-container');
      const needsIconUpdate = 
        $iconContainer.is(':empty') || 
        ($iconContainer.find('svg').length === 0 && $iconContainer.find('img').length === 0) ||
        (iconType === 'custom' && customIcon && $iconContainer.find('img').attr('src') !== customIcon) ||
        force_update === true;
      
      if (needsIconUpdate) {
        // Special case for custom image uploads
        if (iconType === 'custom' && customIcon) {
          $iconContainer.html('<img src="' + customIcon + '" alt="Custom Icon" style="width: ' + iconSize + '; height: ' + iconSize + ';">');
          
          // Apply icon size
          $iconContainer.find('img').css({
            'width': iconSize,
            'height': iconSize
          });
        } else {
          // Use AJAX to fetch all icons from the server for consistency
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
                // Replace fill attribute if present to ensure proper color inheritance
                let svg = response.data;
                if (!svg.includes('fill="currentColor"')) {
                  svg = svg.replace(/<svg/, '<svg fill="currentColor"');
                }
                $iconContainer.html(svg);
                
                // Apply icon size
                $iconContainer.find('svg, img').css({
                  'width': iconSize,
                  'height': iconSize
                });
              }
            },
            error: function() {
              // Fallback to leaf icon as a default
              $iconContainer.html('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><title>leaf</title><path d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z" /></svg>');
              
              // Apply icon size to fallback
              $iconContainer.find('svg').css({
                'width': iconSize,
                'height': iconSize
              });
            }
          });
        }
      } else {
        // Just update the icon size for existing icons and ensure the color is applied
        $iconContainer.find('svg, img').css({
          'width': iconSize,
          'height': iconSize
        });
        
        // Make sure the SVG fill is set to currentColor for proper color inheritance
        $iconContainer.find('svg').attr('fill', 'currentColor');
      }
    } else {
      // Hide icon if display icon is unchecked
      $badge.find('.icon-container').hide();
    }
    
    // Get popover settings
    const popoverTitle = $('#popover_title').val();
    const popoverBgColor = $('#popover_bg_color').val();
    const popoverTextColor = $('#popover_text_color').val();
    const popoverMetricsColor = $('#popover_metrics_color').val();
    const popoverMetricsBgColor = $('#popover_metrics_bg_color').val();
    const popoverContentFont = $('#popover_content_font').val();
    const popoverContentFontSize = $('#popover_content_font_size').val();
    const popoverMetricsFont = $('#popover_metrics_font').val();
    const popoverMetricsFontSize = $('#popover_metrics_font_size').val();
    const popoverMetricsLabelFontSize = $('#popover_metrics_label_font_size').val();
    const popoverMetricsListBgColor = $('#popover_metrics_list_bg_color').val();
    const popoverMetricsListHoverBgColor = $('#popover_metrics_list_hover_bg_color').val();
    const popoverCustomContent = $('#popover_custom_content').val();
    
    // Update popover title
    $('#popover-preview-container h3').text(popoverTitle);
    
    // Update popover container styling
    $('#popover-preview-container').css({
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
    $('input[name="greenmetrics_settings[popover_metrics][]"]:checked').each(function() {
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
        $('#popover-preview-container').append('<div class="greenmetrics-global-badge-custom-content" style="margin-top: 15px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,0.1);"></div>');
      }
      $('.greenmetrics-global-badge-custom-content').html(popoverCustomContent);
    } else {
      $('.greenmetrics-global-badge-custom-content').remove();
    }
  }
  
  // Font size control functions
  function incrementFontSize(inputId) {
    const input = document.getElementById(inputId);
    const currentValue = parseInt(input.value);
    const max = parseInt(input.getAttribute('max'));
    if (currentValue < max) {
      input.value = currentValue + 1;
      input.dispatchEvent(new Event('change'));
    }
  }

  function decrementFontSize(inputId) {
    const input = document.getElementById(inputId);
    const currentValue = parseInt(input.value);
    const min = parseInt(input.getAttribute('min'));
    if (currentValue > min) {
      input.value = currentValue - 1;
      input.dispatchEvent(new Event('change'));
    }
  }
  
  // Public API
  return {
    init: init,
    updateBadgePreview: updateBadgePreview,
    toggleIconOptions: toggleIconOptions,
    incrementFontSize: incrementFontSize,
    decrementFontSize: decrementFontSize
  };
})(jQuery); 