/**
 * GreenMetrics Admin Preview Module
 * Handles the badge and popover preview functionality
 *
 * @module GreenMetricsAdmin.Preview
 * @requires jQuery
 * @requires GreenMetricsAdmin.Config
 * @requires GreenMetricsAdmin.Utils
 */
// Ensure namespace exists
var GreenMetricsAdmin = GreenMetricsAdmin || {};

// Add preview functionality to namespace
GreenMetricsAdmin.Preview = (function ($) {
	'use strict';

	// Module variables
	var mediaFrame;
	var debouncedUpdateBadgePreview;

	// Cache DOM elements
	var $cache = {};

	/**
	 * Initialize the preview functionality
	 *
	 * @function init
	 * @memberof GreenMetricsAdmin.Preview
	 * @public
	 */
	function init() {
		// Only proceed if we're on a plugin settings page
		if (GreenMetricsAdmin.Config && !GreenMetricsAdmin.Config.isPluginPage) {
			return;
		}

		// Cache core DOM elements
		cacheElements();

		// Check if badge preview elements exist
		if ( ! $cache.badgePreviewContainer.length) {
			return;
		}

		// Setup debounced preview update
		debouncedUpdateBadgePreview = GreenMetricsAdmin.Utils.debounce( updateBadgePreview, 300 );

		// Initialize font size fields
		initFontSizeFields();

		// Initialize icon options
		toggleIconOptions();

		// Setup event listeners using delegation where possible
		setupEventListeners();

		// Initialize color pickers
		setupColorPickers();

		// Run initial preview update
		updateBadgePreview();
	}

	// Cache DOM elements for better performance
	function cacheElements() {
		$cache = {
			// Containers
			badgePreviewContainer: $( '#badge-preview-container' ),
			popoverPreviewContainer: $( '#popover-preview-container' ),
			badge: $( '#badge-preview-container .greenmetrics-badge' ),

			// Form elements
			displayIcon: $( '#display_icon' ),
			badgeIconType: $( '#badge_icon_type' ),
			badgeCustomIcon: $( '#badge_custom_icon' ),
			customIconField: $( '#custom-icon-field-wrapper' ),

			// Badge fields
			badgePosition: $( '#badge_position' ),
			badgeSize: $( '#badge_size' ),
			badgeText: $( '#badge_text' ),
			badgeBgColor: $( '#badge_background_color' ),
			badgeTextColor: $( '#badge_text_color' ),
			badgeIconColor: $( '#badge_icon_color' ),
			badgeIconSize: $( '#badge_icon_size' ),

			// Popover fields
			popoverTitle: $( '#popover_title' ),
			popoverBgColor: $( '#popover_bg_color' ),
			popoverTextColor: $( '#popover_text_color' ),
			popoverMetricsColor: $( '#popover_metrics_color' ),
			popoverMetricsBgColor: $( '#popover_metrics_bg_color' ),
			popoverContentFont: $( '#popover_content_font' ),
			popoverContentFontSize: $( '#popover_content_font_size' ),
			popoverMetricsFont: $( '#popover_metrics_font' ),
			popoverMetricsFontSize: $( '#popover_metrics_font_size' ),
			popoverMetricsLabelFontSize: $( '#popover_metrics_label_font_size' ),
			popoverMetricsListBgColor: $( '#popover_metrics_list_bg_color' ),
			popoverMetricsListHoverBgColor: $( '#popover_metrics_list_hover_bg_color' ),
			popoverCustomContent: $( '#popover_custom_content' ),

			// Font size controls
			fontSizeControls: $( '#popover_content_font_size_number, #popover_metrics_font_size_number, #popover_metrics_label_font_size_number, #badge_icon_size_number' ),

			// Misc elements
			iconOptions: $( '.icon-option' ),
			metricsCheckboxes: $( 'input[name="greenmetrics_settings[popover_metrics][]"]' )
		};

		// Make sure badge element is correctly cached even if it's added dynamically
		if ( ! $cache.badge.length && $cache.badgePreviewContainer.length) {
			$cache.badge = $cache.badgePreviewContainer.find( '.greenmetrics-badge' );
		}
	}

	// Setup all preview-related event listeners
	function setupEventListeners() {
		// Use event delegation for all form inputs
		$( '.form-table' ).on(
			'input change',
			'input[type="text"], select, input[type="checkbox"]',
			function () {
				// Skip the font size controls that have special handling
				if ( ! $( this ).hasClass( 'font-size-number' )) {
					debouncedUpdateBadgePreview();
				}
			}
		);

		// Direct event for color picker changes (can't use delegation for these)
		$( '.greenmetrics-color-picker' ).on( 'change', debouncedUpdateBadgePreview );

		// Handle font size number input changes with delegation
		$( '.form-table' ).on(
			'change input',
			'.font-size-number',
			function () {
				// Update hidden field value
				var targetId = $( this ).attr( 'id' ).replace( '_number', '' );
				$( '#' + targetId ).val( $( this ).val() + 'px' );

				// Update the cached value immediately
				if ($cache[targetId]) {
					$cache[targetId] = $( '#' + targetId );
				}

				// Force an immediate update when changing size
				updateBadgePreview( true );
			}
		);

		// Add direct event for badge icon size changes to ensure it works
		$( '#badge_icon_size_number' ).on(
			'change input',
			function () {
				const size = $( this ).val() + 'px';
				$( '#badge_icon_size' ).val( size );
				updateBadgePreview( true );
			}
		);

		// Use event delegation for icon options
		$( '.icon-options' ).on(
			'click',
			'.icon-option',
			function () {
				const iconType = $( this ).data( 'value' );

				// Update visual selection
				$( '.icon-option' ).removeClass( 'selected' );
				$( this ).addClass( 'selected' );

				// Update select value
				$cache.badgeIconType.val( iconType ).trigger( 'change' );

				// Show/hide custom icon field
				toggleCustomIconField( iconType );

				// Mark as changed and update preview
				GreenMetricsAdmin.Utils.markDirty();
				updateBadgePreview( true );
			}
		);

		// Handle select change for badge icon type
		$cache.badgeIconType.on(
			'change',
			function () {
				const iconType = $( this ).val();

				// Update visual selection
				$( '.icon-option' ).removeClass( 'selected' );
				$( '.icon-option[data-value="' + iconType + '"]' ).addClass( 'selected' );

				// Show/hide custom icon field
				toggleCustomIconField( iconType );

				// Mark as changed and update preview
				GreenMetricsAdmin.Utils.markDirty();
				updateBadgePreview( true );
			}
		);

		// Handle display icon changes
		$cache.displayIcon.on(
			'change',
			function () {
				toggleIconOptions();
				updateBadgePreview();
			}
		);

		// Media uploader for custom icon
		$( '.upload-custom-icon' ).on(
			'click',
			function (e) {
				e.preventDefault();
				openMediaUploader();
			}
		);
	}

	// Open WordPress media uploader
	function openMediaUploader() {
		// Create media frame if it doesn't exist
		if ( ! mediaFrame) {
			mediaFrame = wp.media(
				{
					title: greenmetricsAdmin.selectIconText || 'Select or Upload Icon',
					button: {
						text: greenmetricsAdmin.selectIconBtnText || 'Use this Icon'
					},
					multiple: false,
					library: {
						type: 'image'
					}
				}
			);

			// When image selected, run callback
			mediaFrame.on(
				'select',
				function () {
					const attachment = mediaFrame.state().get( 'selection' ).first().toJSON();
					$cache.badgeCustomIcon.val( attachment.url );
					updateBadgePreview();
				}
			);
		}

		// Open frame
		mediaFrame.open();
	}

	// Toggle custom icon field visibility
	function toggleCustomIconField(iconType) {
		if (iconType === 'custom') {
			$cache.badgeCustomIcon.closest( 'tr' ).show();
			$cache.customIconField.show();
		} else {
			$cache.badgeCustomIcon.closest( 'tr' ).hide();
			$cache.customIconField.hide();
		}
	}

	// Initialize color pickers
	function setupColorPickers() {
		// Initialize color pickers with alpha support
		$( '.greenmetrics-color-picker' ).each(
			function () {
				const $this   = $( this );
				const fieldId = $this.attr( 'id' );

				$this.wpColorPicker(
					{
						defaultColor: GreenMetricsAdmin.Config && GreenMetricsAdmin.Config.defaultColors ?
							GreenMetricsAdmin.Config.defaultColors[fieldId] || '#ffffff' : '#ffffff',
						change: function (_, ui) {
							// Update preview when color changes
							updateBadgePreview();

							// Special handling for hover color
							if (fieldId === 'popover_metrics_list_hover_bg_color') {
								updateHoverStyles( ui.color.toString() );
							}
						},
						clear: function () {
							// Set to default color when clear is clicked
							const defaultColor = GreenMetricsAdmin.Config && GreenMetricsAdmin.Config.defaultColors ?
								GreenMetricsAdmin.Config.defaultColors[fieldId] || '#ffffff' : '#ffffff';
							setTimeout(
								function () {
									$this.val( defaultColor ).trigger( 'change' );
									$this.wpColorPicker( 'color', defaultColor );
									updateBadgePreview();
								},
								50
							);
						}
					}
				);
			}
		);

		// Replace all "Clear" buttons with "Set to Default" buttons
		setTimeout(
			function () {
				$( '.wp-picker-clear' ).each(
					function () {
						$( this ).text( 'Set to Default' );

						// Add special handling for metrics bg color clear button
						if ($( this ).closest( '.wp-picker-container' ).find( '#popover_metrics_bg_color' ).length) {
							$( this ).on(
								'click',
								function () {
									// Add a small delay to let the default clear handler execute first
									setTimeout(
										function () {
												// Force the correct rgba value after the clear operation
												$( '#popover_metrics_bg_color' ).iris( 'color', 'rgba(0, 0, 0, 0.05)' );
												$( '#popover_metrics_bg_color' ).val( 'rgba(0, 0, 0, 0.05)' ).trigger( 'change' );

												// Also update the badge preview
												updateBadgePreview();
										},
										100
									);
								}
							);
						}
					}
				);
			},
			100
		);
	}

	// Update hover styles for metrics items
	function updateHoverStyles(color) {
		const styleId = 'greenmetrics-preview-hover-style';
		if ($( '#' + styleId ).length === 0) {
			$( 'head' ).append( '<style id="' + styleId + '"></style>' );
		}
		$( '#' + styleId ).html( '#popover-preview-container .greenmetrics-global-badge-metric:hover { background-color: ' + color + ' !important; }' );
	}

	// Function to initialize font size fields
	function initFontSizeFields() {
		// Set the number input value from the hidden field
		$( '#popover_content_font_size_number' ).val( parseInt( $( '#popover_content_font_size' ).val() ) );
		$( '#popover_metrics_font_size_number' ).val( parseInt( $( '#popover_metrics_font_size' ).val() ) );
		$( '#popover_metrics_label_font_size_number' ).val( parseInt( $( '#popover_metrics_label_font_size' ).val() ) );
		$( '#badge_icon_size_number' ).val( parseInt( $( '#badge_icon_size' ).val() ) );
	}

	// Function to toggle icon options based on display_icon checkbox
	function toggleIconOptions() {
		var displayIcon = $cache.displayIcon.is( ':checked' );
		var iconType    = $cache.badgeIconType.val();

		if (displayIcon) {
			// Show icon type selection and icon color
			$cache.badgeIconType.closest( 'tr' ).show();
			$cache.badgeIconColor.closest( 'tr' ).show();
			$( '.icon-options' ).closest( 'tr' ).show();

			// Show custom icon field only if custom is selected
			toggleCustomIconField( iconType );
		} else {
			// Hide all icon-related fields
			$cache.badgeIconType.closest( 'tr' ).hide();
			$cache.badgeIconColor.closest( 'tr' ).hide();
			$( '.icon-options' ).closest( 'tr' ).hide();
			$cache.badgeCustomIcon.closest( 'tr' ).hide();
			$cache.customIconField.hide();
		}
	}

	// Get or update icon HTML (reused for consistency)
	function getIconHtml(iconType, callback) {
		// If custom icon, just return the HTML directly
		if (iconType === 'custom' && $cache.badgeCustomIcon.val()) {
			const customIcon = $cache.badgeCustomIcon.val();
			const iconSize   = $cache.badgeIconSize.val();
			const html       = '<img src="' + customIcon + '" alt="Custom Icon" style="width: ' + iconSize + '; height: ' + iconSize + ';">';
			if (callback) {
				callback( html );
			}
			return html;
		}

		// Use AJAX to fetch icon from server
		$.ajax(
			{
				url: ajaxurl,
				method: 'POST',
				data: {
					action: 'greenmetrics_get_icon',
					icon_type: iconType,
					nonce: greenmetricsAdmin.nonce
				},
				success: function (response) {
					if (response.success && response.data) {
						// Ensure proper color inheritance for SVG
						let svg = response.data;
						if ( ! svg.includes( 'fill="currentColor"' )) {
							svg = svg.replace( /<svg/, '<svg fill="currentColor"' );
						}
						if (callback) {
							callback( svg );
						}
					} else {
						// Fallback icon on error
						const fallback = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><title>leaf</title><path d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z" /></svg>';
						if (callback) {
							callback( fallback );
						}
					}
				},
				error: function () {
					// Fallback icon on error
					const fallback = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><title>leaf</title><path d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z" /></svg>';
					if (callback) {
						callback( fallback );
					}
				}
			}
		);
	}

	// Main preview update function
	function updateBadgePreview(force_update) {
		// Refresh cached DOM elements to ensure we're working with current state
		// This is important for dynamically created elements
		if ( ! $cache.badge.length) {
			$cache.badge = $cache.badgePreviewContainer.find( '.greenmetrics-badge' );
		}

		// Always refresh icon size from DOM since it's critical for this function
		const iconSize = $( '#badge_icon_size' ).val();

		// Get current badge settings
		const position    = $cache.badgePosition.val();
		const size        = $cache.badgeSize.val();
		const text        = $cache.badgeText.val();
		const bgColor     = $cache.badgeBgColor.val();
		const textColor   = $cache.badgeTextColor.val();
		const iconColor   = $cache.badgeIconColor.val();
		const displayIcon = $cache.displayIcon.is( ':checked' );
		const iconType    = $cache.badgeIconType.val();
		const customIcon  = $cache.badgeCustomIcon.val();

		// Update badge position
		$cache.badgePreviewContainer.attr( 'class', position );

		// Update badge appearance
		const $badge = $cache.badge;

		// Make sure to properly apply the size class
		$badge.removeClass( 'small medium large' ).addClass( size );

		// Ensure the badge has the greenmetrics-badge base class
		if ( ! $badge.hasClass( 'greenmetrics-badge' )) {
			$badge.addClass( 'greenmetrics-badge' );
		}

		$badge.css(
			{
				'background-color': bgColor,
				'color': textColor
			}
		);

		// Update badge text
		$badge.find( 'span' ).text( text );

		// Update icon visibility and appearance
		updateBadgeIcon( $badge, displayIcon, iconType, iconColor, iconSize, customIcon, force_update );

		// Update popover styling
		updatePopoverPreview();
	}

	// Update badge icon - extract for cleaner code
	function updateBadgeIcon($badge, displayIcon, iconType, iconColor, iconSize, customIcon, force_update) {
		if (displayIcon) {
			// Show icon container
			let $iconContainer = $badge.find( '.icon-container' );

			if ($iconContainer.length === 0) {
				$badge.prepend( '<div class="icon-container" style="color:' + iconColor + ';"></div>' );
				$iconContainer = $badge.find( '.icon-container' );
			} else {
				$iconContainer.show().css( 'color', iconColor );
			}

			// Determine if icon needs to be updated
			const needsIconUpdate =
			$iconContainer.is( ':empty' ) ||
			($iconContainer.find( 'svg' ).length === 0 && $iconContainer.find( 'img' ).length === 0) ||
			(iconType === 'custom' && customIcon && $iconContainer.find( 'img' ).attr( 'src' ) !== customIcon) ||
			force_update === true;

			if (needsIconUpdate) {
				getIconHtml(
					iconType,
					function (iconHtml) {
						$iconContainer.html( iconHtml );

						// Apply icon size - use !important to override any inline styles
						$iconContainer.find( 'svg, img' ).css(
							{
								'width': iconSize + ' !important',
								'height': iconSize + ' !important'
							}
						);

						// For SVGs, also set the width/height attributes
						$iconContainer.find( 'svg' ).attr(
							{
								'width': iconSize,
								'height': iconSize,
								'fill': 'currentColor'
							}
						);
					}
				);
			} else {
				// Just update the icon size for existing icons
				$iconContainer.find( 'svg, img' ).css(
					{
						'width': iconSize + ' !important',
						'height': iconSize + ' !important'
					}
				);

				// For SVGs, also set the width/height attributes
				$iconContainer.find( 'svg' ).attr(
					{
						'width': iconSize,
						'height': iconSize,
						'fill': 'currentColor'
					}
				);
			}
		} else {
			// Hide icon if display icon is unchecked
			$badge.find( '.icon-container' ).hide();
		}
	}

	// Update popover preview - extract for cleaner code
	function updatePopoverPreview() {
		// Get popover settings
		const popoverTitle                   = $cache.popoverTitle.val();
		const popoverBgColor                 = $cache.popoverBgColor.val();
		const popoverTextColor               = $cache.popoverTextColor.val();
		const popoverMetricsColor            = $cache.popoverMetricsColor.val();
		const popoverMetricsBgColor          = $cache.popoverMetricsBgColor.val();
		const popoverContentFont             = $cache.popoverContentFont.val();
		const popoverContentFontSize         = $cache.popoverContentFontSize.val();
		const popoverMetricsFont             = $cache.popoverMetricsFont.val();
		const popoverMetricsFontSize         = $cache.popoverMetricsFontSize.val();
		const popoverMetricsLabelFontSize    = $cache.popoverMetricsLabelFontSize.val();
		const popoverMetricsListBgColor      = $cache.popoverMetricsListBgColor.val();
		const popoverMetricsListHoverBgColor = $cache.popoverMetricsListHoverBgColor.val();
		const popoverCustomContent           = $cache.popoverCustomContent.val();

		// Update popover title
		$cache.popoverPreviewContainer.find( 'h3' ).text( popoverTitle );

		// Update popover container styling
		$cache.popoverPreviewContainer.css(
			{
				'background-color': popoverBgColor,
				'color': popoverTextColor,
				'font-family': popoverContentFont,
				'font-size': popoverContentFontSize
			}
		);

		// Update metric values styling
		$( '.greenmetrics-global-badge-metric-value' ).css(
			{
				'color': popoverMetricsColor,
				'font-family': popoverMetricsFont,
				'font-size': popoverMetricsFontSize,
				'background': popoverMetricsBgColor
			}
		);

		// Update metric labels styling
		$( '.greenmetrics-global-badge-metric-label' ).css(
			{
				'font-size': popoverMetricsLabelFontSize
			}
		);

		// Update metric list item styling
		$( '.greenmetrics-global-badge-metric' ).css(
			{
				'background-color': popoverMetricsListBgColor
			}
		);

		// Get selected metrics
		const selectedMetrics = [];
		$cache.metricsCheckboxes.filter( ':checked' ).each(
			function () {
				selectedMetrics.push( $( this ).val() );
			}
		);

		// Show/hide metrics based on selection
		$( '.greenmetrics-global-badge-metric' ).each(
			function () {
				const metricKey = $( this ).data( 'metric' );
				$( this ).toggle( selectedMetrics.includes( metricKey ) );
			}
		);

		// Apply hover styles
		updateHoverStyles( popoverMetricsListHoverBgColor );

		// Update popover custom content
		if (popoverCustomContent) {
			if ($( '.greenmetrics-global-badge-custom-content' ).length === 0) {
				$cache.popoverPreviewContainer.append( '<div class="greenmetrics-global-badge-custom-content" style="margin-top: 15px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,0.1);"></div>' );
			}
			$( '.greenmetrics-global-badge-custom-content' ).html( popoverCustomContent );
		} else {
			$( '.greenmetrics-global-badge-custom-content' ).remove();
		}
	}

	// Font size control functions
	function incrementFontSize(inputId) {
		const input        = document.getElementById( inputId );
		const currentValue = parseInt( input.value );
		const max          = parseInt( input.getAttribute( 'max' ) );
		if (currentValue < max) {
			input.value = currentValue + 1;
			input.dispatchEvent( new Event( 'change' ) );
		}
	}

	function decrementFontSize(inputId) {
		const input        = document.getElementById( inputId );
		const currentValue = parseInt( input.value );
		const min          = parseInt( input.getAttribute( 'min' ) );
		if (currentValue > min) {
			input.value = currentValue - 1;
			input.dispatchEvent( new Event( 'change' ) );
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
})( jQuery );