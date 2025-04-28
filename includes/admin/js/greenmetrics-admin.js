;(function($) {
  'use strict';
  $(document).ready(function() {
    // Move admin notices to top of wrap
    function moveAdminNotices() {
      // Find all WordPress admin notices with various possible classes
      // This includes .notice (standard), .error (legacy), .updated (legacy), and their combinations
      const $notices = $('.notice, .error:not(.notice), .updated:not(.notice), .update-nag');
      if ($notices.length) {
        // Find our custom admin header
        const $adminHeader = $('.greenmetrics-admin-header');
        if ($adminHeader.length) {
          // Move all notices before our admin header
          $notices.detach().insertBefore($adminHeader);
        }
      }
    }
    
    // Execute immediately 
    moveAdminNotices();
    
    // Also run after a short delay to catch notices that might be added dynamically
    setTimeout(moveAdminNotices, 100);
    
    // Run one more time after a longer delay for any notices added by AJAX operations
    setTimeout(moveAdminNotices, 1000);
    
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
      // Don't call updateBadgePreview here as it's already called from events
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
      
      Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif';
      Chart.defaults.font.size = 12;
      
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
              beginAtZero: true,
              grid: {
                color: 'rgba(0, 0, 0, 0.05)',
                lineWidth: 1
              },
              ticks: {
                padding: 10,
                color: '#666'
              },
              border: {
                dash: [4, 4]
              }
            },
            x: {
              grid: {
                color: 'rgba(0, 0, 0, 0.03)',
                lineWidth: 1,
                drawOnChartArea: true
              },
              ticks: {
                padding: 10,
                maxRotation: 45,
                minRotation: 0,
                color: '#666'
              }
            }
          },
          interaction: {
            mode: 'index',
            intersect: false
          },
          plugins: {
            tooltip: {
              enabled: true,
              backgroundColor: 'rgba(255, 255, 255, 0.95)',
              titleColor: '#333',
              bodyColor: '#666',
              titleFont: {
                weight: 'bold',
                size: 14
              },
              bodyFont: {
                size: 13
              },
              padding: 12,
              cornerRadius: 6,
              boxPadding: 6,
              borderColor: 'rgba(0, 0, 0, 0.1)',
              borderWidth: 1,
              usePointStyle: true,
              callbacks: {
                labelPointStyle: function(context) {
                  return {
                    pointStyle: 'circle',
                    rotation: 0
                  };
                }
              }
            },
            legend: {
              display: false // We're using custom checkboxes for legend
            }
          },
          animation: {
            duration: 750,
            easing: 'easeOutQuart'
          },
          elements: {
            line: {
              tension: 0.3 // Smoother curves
            },
            point: {
              radius: 3,
              hoverRadius: 5,
              hitRadius: 30
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
      
      // Add loading class to chart container
      $('.greenmetrics-chart-container').addClass('loading');
      
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
        },
        complete: function() {
          // Remove loading states
          $('.greenmetrics-date-btn').removeClass('loading');
          $('.greenmetrics-chart-container').removeClass('loading');
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
      
      // Format dates for display (e.g., "Jan 15" instead of "2023-01-15")
      const formattedDates = data.dates.map(dateStr => {
        const date = new Date(dateStr);
        return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
      });
      
      // Update chart labels (dates)
      metricsChart.data.labels = formattedDates;
      
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
            pointRadius: 4,
            pointStyle: 'circle',
            pointBackgroundColor: colors.borderColor,
            pointBorderColor: 'rgba(255, 255, 255, 0.8)',
            pointBorderWidth: 2,
            pointHoverRadius: 6,
            pointHoverBackgroundColor: colors.borderColor,
            pointHoverBorderColor: 'white',
            pointHoverBorderWidth: 2,
            hidden: !visible,
            cubicInterpolationMode: 'monotone',
            tension: 0.4,
            fill: false
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
            
            // Update date inputs to match selected range
            $('#greenmetrics-start-date').val(startDate);
            $('#greenmetrics-end-date').val(endDate);
            break;
            
          case '30days':
            startDate = getDateString(30); // 30 days ago
            endDate = getDateString(0);    // Today
            
            // Update date inputs to match selected range
            $('#greenmetrics-start-date').val(startDate);
            $('#greenmetrics-end-date').val(endDate);
            break;
            
          case 'thisMonth':
            // First day of current month
            const now = new Date();
            startDate = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-01';
            endDate = getDateString(0); // Today
            
            // Update date inputs to match selected range
            $('#greenmetrics-start-date').val(startDate);
            $('#greenmetrics-end-date').val(endDate);
            break;
            
          case 'custom':
            // Use the date inputs for custom range
            startDate = $('#greenmetrics-start-date').val();
            endDate = $('#greenmetrics-end-date').val();
            
            // Validate dates
            if (!startDate || !endDate) {
              // Show feedback to user
              alert('Please select both start and end dates');
              return; // Don't proceed if dates are not set
            }
            
            // Check if end date is before start date
            if (new Date(endDate) < new Date(startDate)) {
              // Swap dates
              const temp = startDate;
              startDate = endDate;
              endDate = temp;
              
              // Update inputs
              $('#greenmetrics-start-date').val(startDate);
              $('#greenmetrics-end-date').val(endDate);
            }
            break;
            
          default:
            return;
        }
        
        // Show loading state
        $(this).addClass('loading');
        
        // Load metrics for the selected date range
        loadMetricsByDate(startDate, endDate);
      });
      
      // Date input changes
      $('.greenmetrics-date-input').on('change', function() {
        // When date inputs change, highlight the custom button
        $('.greenmetrics-date-btn').removeClass('active');
        $('.greenmetrics-date-btn[data-range="custom"]').addClass('active');
      });
      
      // Set default dates for custom date picker
      const today = new Date();
      const sevenDaysAgo = new Date();
      sevenDaysAgo.setDate(today.getDate() - 7);
      
      $('#greenmetrics-start-date').val(getDateString(7)); // 7 days ago by default
      $('#greenmetrics-end-date').val(getDateString(0));   // Today by default
      
      // Set max date for both date pickers to today
      const maxDate = getDateString(0);
      $('#greenmetrics-start-date, #greenmetrics-end-date').attr('max', maxDate);
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
        // This ensures we don't reset to default when other settings change
        const $iconContainer = $badge.find('.icon-container');
        const needsIconUpdate = 
          $iconContainer.is(':empty') || 
          ($iconContainer.find('svg').length === 0 && $iconContainer.find('img').length === 0) ||
          (iconType === 'custom' && customIcon && $iconContainer.find('img').attr('src') !== customIcon) ||
          force_update === true;
        
        if (needsIconUpdate) {
          // Update icon based on selected type
          let iconSvg = '';
          switch(iconType) {
            case 'leaf':
              iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><title>leaf</title><path d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z" /></svg>';
              break;
            case 'tree':
              iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><title>sprout-outline</title><path d="M23,4.1V2.3L21.2,2.1C21.1,2.1 20.5,2 19.5,2C15.4,2 12.4,3.2 10.7,5.3C9.4,4.5 7.6,4 5.5,4C4.5,4 3.8,4.1 3.8,4.1L1.9,4.4L2,6.1C2.1,9.1 3.6,14.8 8.8,14.8C8.9,14.8 8.9,14.8 9,14.8V18.2C5.2,18.7 2,20 2,20V22H22V20C22,20 18.8,18.7 15,18.2V15C21.3,14.9 23,7.8 23,4.1M12,18C11.7,18 11.3,18 11,18V12.4C11,12.4 10.8,9 8,9C8,9 9.5,9.8 9.9,12.7C9.5,12.8 9.1,12.8 8.8,12.8C4.2,12.8 4,6.1 4,6.1C4,6.1 4.6,6 5.5,6C7.4,6 10.5,6.4 11.4,9.1C11.9,4.6 17,4 19.5,4C20.4,4 21,4.1 21,4.1C21,4.1 21,13.1 14.7,13.1C14.5,13.1 14.2,13.1 14,13.1C14,11.1 16,8.1 16,8.1C13,9.1 13,13 13,13V18C12.7,18 12.3,18 12,18Z" /></svg>';
              break;
            case 'globe':
              iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>';
              break;
            case 'recycle':
              iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><title>recycle</title><path d="M21.82,15.42L19.32,19.75C18.83,20.61 17.92,21.06 17,21H15V23L12.5,18.5L15,14V16H17.82L15.6,12.15L19.93,9.65L21.73,12.77C22.25,13.54 22.32,14.57 21.82,15.42M9.21,3.06H14.21C15.19,3.06 16.04,3.63 16.45,4.45L17.45,6.19L19.18,5.19L16.54,9.6L11.39,9.69L13.12,8.69L11.71,6.24L9.5,10.09L5.16,7.59L6.96,4.47C7.37,3.64 8.22,3.06 9.21,3.06M5.05,19.76L2.55,15.43C2.06,14.58 2.13,13.56 2.64,12.79L3.64,11.06L1.91,10.06L7.05,10.14L9.7,14.56L7.97,13.56L6.56,16H11V21H7.4C6.47,21.07 5.55,20.61 5.05,19.76Z" /></svg>';
              break;
            case 'custom':
              if (customIcon) {
                iconSvg = '<img src="' + customIcon + '" alt="Custom Icon" style="width: ' + iconSize + '; height: ' + iconSize + ';">';
              } else {
                // Use a pencil icon as placeholder for custom icon
                iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>';
              }
              break;
            default:
              // Using AJAX to get the icon SVG
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
                    // Replace fill attribute if present
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
                  // Fallback to leaf icon using same SVG as in PHP class
                  $iconContainer.html('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><title>leaf</title><path d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z" /></svg>');
                }
              });
              // Skip the rest of the icon update
              iconSvg = null;
          }
          
          if (iconSvg !== null) {
            $iconContainer.html(iconSvg);
            
            // Apply icon size to all SVG and image icons
            $iconContainer.find('svg, img').css({
              'width': iconSize,
              'height': iconSize
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
    
    // Icon option selection
    $('.icon-option').on('click', function() {
        const iconType = $(this).data('value');
        console.log('Icon clicked:', iconType);
        
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
        markDirty();
        updateBadgePreview(true);
    });
    
    // Badge icon type selection
    $('#badge_icon_type').on('change', function() {
        const iconType = $(this).val();
        console.log('Icon type changed to:', iconType);
        
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
        markDirty();
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
  });
})(jQuery);