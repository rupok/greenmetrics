;(function($) {
  'use strict';
  $(document).ready(function() {
    // Cached selectors
    var $submitBtn = $('#submit');
    var $checkboxes = $('input[type="checkbox"]');
    var $selects = $('select');
    var $texts = $('input[type="text"]');
    var $iconOptions = $('.icon-option');
    var mediaFrame;
    
    // Chart.js components
    var metricsChart = null;
    var chartCanvas = document.getElementById('greenmetrics-chart');
    var chartDatasets = [];
    var chartLabels = [];

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
      'popover_metrics_bg_color': 'rgba(0, 0, 0, 0.05)',
      'popover_metrics_list_bg_color': '#f8f9fa',
      'popover_metrics_list_hover_bg_color': '#f3f4f6'
    };
    
    // Chart color settings
    const chartColors = {
      'carbon_footprint': {
        borderColor: 'rgb(255, 99, 132)',
        backgroundColor: 'rgba(255, 99, 132, 0.5)'
      },
      'energy_consumption': {
        borderColor: 'rgb(54, 162, 235)',
        backgroundColor: 'rgba(54, 162, 235, 0.5)'
      },
      'data_transfer': {
        borderColor: 'rgb(75, 192, 192)',
        backgroundColor: 'rgba(75, 192, 192, 0.5)'
      },
      'http_requests': {
        borderColor: 'rgb(153, 102, 255)',
        backgroundColor: 'rgba(153, 102, 255, 0.5)'
      },
      'page_views': {
        borderColor: 'rgb(255, 159, 64)',
        backgroundColor: 'rgba(255, 159, 64, 0.5)'
      }
    };
    
    // Initialize Chart.js if the canvas exists
    function initChart() {
      if (!chartCanvas) return;
      
      metricsChart = new Chart(chartCanvas, {
        type: 'line',
        data: {
          labels: [],
          datasets: []
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true
            }
          },
          interaction: {
            mode: 'index',
            intersect: false
          },
          plugins: {
            tooltip: {
              enabled: true
            },
            legend: {
              display: false // We're using custom checkboxes for legend
            }
          }
        }
      });
      
      // Load initial data (last 7 days by default)
      loadMetricsByDate();
      
      // Set up event handlers for date range buttons and chart toggles
      setupDateRangeHandlers();
      setupChartToggleHandlers();
    }
    
    // Load metrics data by date range
    function loadMetricsByDate(startDate, endDate) {
      // Show loading state
      if (metricsChart) {
        metricsChart.data.labels = [];
        metricsChart.data.datasets = [];
        metricsChart.update();
      }
      
      // Set default date range if not provided (last 7 days)
      if (!startDate && !endDate) {
        startDate = getDateString(7); // 7 days ago
        endDate = getDateString(0);   // Today
      }
      
      // Make API request
      $.ajax({
        url: greenmetricsAdmin.rest_url + '/metrics-by-date',
        method: 'GET',
        data: {
          start_date: startDate,
          end_date: endDate
        },
        beforeSend: function(xhr) {
          xhr.setRequestHeader('X-WP-Nonce', greenmetricsAdmin.rest_nonce);
        },
        success: function(response) {
          updateChart(response);
        },
        error: function(xhr, status, error) {
          console.error('Error loading metrics data:', error);
          if (metricsChart) {
            metricsChart.data.labels = [];
            metricsChart.data.datasets = [];
            metricsChart.update();
          }
        }
      });
    }
    
    // Update chart with new data
    function updateChart(data) {
      if (!metricsChart) return;
      
      // Clear previous data
      metricsChart.data.labels = [];
      metricsChart.data.datasets = [];
      
      // Check if we have valid data
      if (!data || !data.dates || data.dates.length === 0) {
        metricsChart.update();
        return;
      }
      
      // Update chart labels (dates)
      metricsChart.data.labels = data.dates;
      
      // Create datasets for each metric
      const metrics = [
        { id: 'carbon_footprint', label: 'Carbon Footprint (g)' },
        { id: 'energy_consumption', label: 'Energy Consumption (kWh)' },
        { id: 'data_transfer', label: 'Data Transfer (KB)' },
        { id: 'http_requests', label: 'HTTP Requests' },
        { id: 'page_views', label: 'Page Views' }
      ];
      
      // Add each metric dataset if it has data
      metrics.forEach(function(metric) {
        if (data[metric.id] && data[metric.id].length > 0) {
          const colors = chartColors[metric.id];
          const visible = $('#' + metric.id).prop('checked');
          
          metricsChart.data.datasets.push({
            label: metric.label,
            data: data[metric.id],
            borderColor: colors.borderColor,
            backgroundColor: colors.backgroundColor,
            borderWidth: 2,
            pointRadius: 3,
            hidden: !visible
          });
        }
      });
      
      // Update the chart
      metricsChart.update();
    }
    
    // Set up event handlers for date range buttons
    function setupDateRangeHandlers() {
      // Date range button clicks
      $('.greenmetrics-date-btn').on('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all buttons
        $('.greenmetrics-date-btn').removeClass('active');
        
        // Add active class to clicked button
        $(this).addClass('active');
        
        // Get the date range type
        const range = $(this).data('range');
        let startDate, endDate;
        
        // Set date range based on button clicked
        switch(range) {
          case '7days':
            startDate = getDateString(7); // 7 days ago
            endDate = getDateString(0);   // Today
            break;
            
          case '30days':
            startDate = getDateString(30); // 30 days ago
            endDate = getDateString(0);    // Today
            break;
            
          case 'thisMonth':
            // First day of current month
            const now = new Date();
            startDate = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-01';
            endDate = getDateString(0); // Today
            break;
            
          case 'custom':
            // Use the date inputs for custom range
            startDate = $('#greenmetrics-start-date').val();
            endDate = $('#greenmetrics-end-date').val();
            
            // Validate dates
            if (!startDate || !endDate) {
              return; // Don't proceed if dates are not set
            }
            break;
            
          default:
            return;
        }
        
        // Load metrics for the selected date range
        loadMetricsByDate(startDate, endDate);
      });
      
      // Set default dates for custom date picker
      $('#greenmetrics-start-date').val(getDateString(7)); // 7 days ago by default
      $('#greenmetrics-end-date').val(getDateString(0));   // Today by default
    }
    
    // Set up event handlers for chart metric toggles
    function setupChartToggleHandlers() {
      $('.chart-toggle').on('change', function() {
        if (!metricsChart) return;
        
        const metricId = $(this).attr('id');
        const checked = $(this).prop('checked');
        
        // Find the dataset index for this metric
        const datasetIndex = metricsChart.data.datasets.findIndex(dataset => 
          dataset.label.toLowerCase().includes(metricId.toLowerCase().replace('_', ' '))
        );
        
        // Toggle visibility if dataset found
        if (datasetIndex !== -1) {
          metricsChart.setDatasetVisibility(datasetIndex, checked);
          metricsChart.update();
        }
      });
    }
    
    // Helper function to get date string for N days ago
    function getDateString(daysAgo) {
      const date = new Date();
      date.setDate(date.getDate() - daysAgo);
      return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
    }
    
    // Helper function to pad single digit numbers with a leading zero
    function pad(num) {
      return num < 10 ? '0' + num : num;
    }

    // Event Listeners
    $checkboxes.on('change', markDirty);
    $selects.on('change', markDirty);
    $texts.on('keyup', debouncedUpdateBadgePreview);
    $iconOptions.on('click', function() {
      $iconOptions.removeClass('selected');
      $(this).addClass('selected');
      $('#badge_icon_type').val($(this).data('icon'));
      markDirty();
    });

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
    
    // Special handler for the hover background color to ensure immediate preview updates
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
      });
    }, 100);

    // Update badge and popover preview when settings change
    $('#enable_badge, #badge_position, #badge_size, #badge_text, #badge_background_color, #badge_text_color, #badge_icon_color, ' +
      '#popover_title, #popover_custom_content, #popover_bg_color, #popover_text_color, #popover_metrics_color, #popover_metrics_bg_color, ' +
      '#popover_content_font, #popover_content_font_size, #popover_metrics_font, #popover_metrics_font_size, #popover_metrics_list_bg_color, ' +
      '#popover_metrics_list_hover_bg_color')
    .on('change input', function() {
      updateBadgePreview();
    });
    
    // Handle font size number input changes
    $('#popover_content_font_size_number, #popover_metrics_font_size_number, #popover_metrics_label_font_size_number').on('change input', function() {
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
    
    // Function to initialize font size fields
    function initFontSizeFields() {
      // Set the number input value from the hidden field
      $('#popover_content_font_size_number').val(parseInt($('#popover_content_font_size').val()));
      $('#popover_metrics_font_size_number').val(parseInt($('#popover_metrics_font_size').val()));
      $('#popover_metrics_label_font_size_number').val(parseInt($('#popover_metrics_label_font_size').val()));
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
    function updateBadgePreview() {
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
      
      // Update icon visibility and appearance
      if (displayIcon) {
        // Show icon container
        if ($badge.find('.icon-container').length === 0) {
          $badge.prepend('<div class="icon-container" style="color:' + iconColor + ';"></div>');
        } else {
          $badge.find('.icon-container').show().css('color', iconColor);
        }
        
        // Update icon based on selected type
        let iconSvg = '';
        switch(iconType) {
          case 'leaf':
            iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 8C8 10 5.9 16.17 3.82 21.34l1.89.66.95-2.3c.48.17 1.02.3 1.58.3C17 20 22 13.46 22 6c0-.55-.06-1.09-.14-1.62C20.18 4.15 18.66 4 17 4V2c1.67 0 3.35.12 5 .34V4c-1.67-.22-3.33-.34-5-.34v2zM2 6c0 7.46 5 14 14.5 14 .56 0 1.1-.13 1.58-.3l.95 2.3 1.89-.66C18.1 16.17 16 10 7 8c0 0-5 0-5 0z"/></svg>';
            break;
          case 'tree':
            iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22c4.97 0 9-4.03 9-9-4.97 0-9 4.03-9 9zm2.44-9.43h-.44v2h.44c2.32 0 2.49 3.23 2.49 3.23 1.52-1.84 2.63-4.43 1.73-7C17.56 8.37 15.5 7 15.5 7S14.8 9.1 13 9.42v.36c1.32-.18 2.44.11 2.44.11s-1.22 1.91-1 3.68z"/><path d="M12.28 10h-.56v2h.56c2.33 0 2.51 3.45 2.51 3.45 1.55-1.89 2.67-4.63 1.77-7.24-.51-1.46-2.18-3.02-2.18-3.02s-.99 2.18-2.1 2.48V8c1.34-.2 2.55.07 2.55.07s-1.34 1.66-1.14 3.44z"/><path d="M12.63 5.33c-.28.47-1.04 1.68-2 1.87V8.8c1.35-.19 2.97.31 2.97.31S12.69 10.3 12.22 12h.33v-2h-.16c.06-.32.2-.65.44-.97.19.38.39.75.58 1.09l.66-.42c-.18-.28-.33-.57-.46-.85 0 0 .99.17 2.22.5-.27-.5-2.47-4.02-3.2-4.02z"/><path d="M10.45 12h-.43v8.17c.34-.14.66-.34.95-.55L10.45 12zm1.66 4.62c.1.21.19.42.27.63-.16-.19-.31-.39-.46-.57.07-.02.12-.04.19-.06zm1.14-4.62L12.1 17.1c.45-.11.88-.29 1.29-.51l-.14-4.59z"/><path d="M9.3 14.13l-.24 7.14c.24.11.48.19.73.26l-.42-7.8c-.02.14-.05.27-.07.4zm3.33 1.7c-.04-.04-.08-.09-.12-.14.03.05.06.09.09.13.01 0 .02.01.03.01zm-.83-3.83l-.32 7.46c.29.05.58.08.88.08.12 0 .24-.01.36-.02L12 12l-.2 0z"/></svg>';
            break;
          case 'globe':
            iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>';
            break;
          case 'recycle':
            iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M5.77 7.15L7.2 4.78l1.03-1.71c.39-.65 1.33-.65 1.72 0l1.48 2.46-1.23 2.06-1 1.34-2.43-4.78zm15.95 5.82l-1.6-2.66-3.46 2L18.87 16H21v2l-3.87-7.03zM16 21h1.5l2.05-3.42-3.46-2-1.09 1.84L16 21zm-3.24-3.71l-1.03-1.71-1.43 2.43-2.43 4.78 1.6 2.66 3.46-2 1.03-1.71-1.43-2.45zM13.42 8.5l-1.48-2.46c-.39-.65-1.33-.65-1.72 0L9.22 7.15l-1 1.34 2.43 4.78 1.6-2.66 1.17-2.11zM10.5 14.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>';
            break;
          case 'custom':
            if (customIcon) {
              iconSvg = '<img src="' + customIcon + '" alt="Custom Icon" style="width: 20px; height: 20px;">';
            } else {
              iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>';
            }
            break;
          default:
            iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 8C8 10 5.9 16.17 3.82 21.34l1.89.66.95-2.3c.48.17 1.02.3 1.58.3C17 20 22 13.46 22 6c0-.55-.06-1.09-.14-1.62C20.18 4.15 18.66 4 17 4V2c1.67 0 3.35.12 5 .34V4c-1.67-.22-3.33-.34-5-.34v2zM2 6c0 7.46 5 14 14.5 14 .56 0 1.1-.13 1.58-.3l.95 2.3 1.89-.66C18.1 16.17 16 10 7 8c0 0-5 0-5 0z"/></svg>';
        }
        $badge.find('.icon-container').html(iconSvg);
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
      
      updateBadgePreview();
    });
    
    // Icon option selection (visual clickable icons)
    $('.icon-option').on('click', function() {
      const value = $(this).data('value');
      
      // Update the select field
      $('#badge_icon_type').val(value).trigger('change');
      
      // Update visual selection
      $('.icon-option').removeClass('selected');
      $(this).addClass('selected');
    });
    
    // Custom icon selection
    $('#select_custom_icon').on('click', function(e) {
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
    
    // Setup other admin event listeners if needed
    if ($('.greenmetrics-settings-trigger').length) {
      $('.greenmetrics-settings-trigger').on('click', function(e) {
        e.preventDefault();
        $($(this).data('target')).slideToggle();
      });
    }
    
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
    
    // Initialize the UI
    toggleIconOptions();
    initFontSizeFields();
    updateBadgePreview();
    
    // Only initialize chart if we're on the dashboard page
    if (chartCanvas) {
      initChart();
    }
    
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

      // Set up event listeners
      function setupEventListeners() {
        // Rest of the event listener setup
        // This is kept as-is since we're only modifying display settings
      }
      
      // Call init dashboard if we're on the dashboard page
      initDashboard();
    }
  });
})(jQuery);