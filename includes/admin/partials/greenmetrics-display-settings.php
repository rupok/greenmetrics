<?php
/**
 * Display settings admin template.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get settings
$settings = get_option(
	'greenmetrics_settings',
	array(
		'enable_badge'           => 1,
		'display_icon'           => 1,
		'badge_position'         => 'bottom-right',
		'badge_size'             => 'medium',
		'badge_text'             => 'Eco-Friendly Site',
		'badge_icon_type'        => 'leaf',
		'badge_custom_icon'      => '',
		'badge_background_color' => '#4CAF50',
		'badge_text_color'       => '#ffffff',
		'badge_icon_color'       => '#ffffff',
	)
);
?>

<div class="wrap">
	<div class="greenmetrics-admin-container">
		<div class="greenmetrics-admin-header">
			<img src="<?php echo esc_url( GREENMETRICS_PLUGIN_URL . 'includes/admin/img/greenmetrics-icon.png' ); ?>" alt="<?php esc_attr_e( 'GreenMetrics Icon', 'greenmetrics' ); ?>" />
			<h1><?php esc_html_e( 'GreenMetrics - Display Settings', 'greenmetrics' ); ?></h1>
		</div>

		<div class="greenmetrics-admin-content">
			<div class="greenmetrics-admin-settings">
				<form method="post" action="options.php">
					<?php
					settings_fields( 'greenmetrics_settings' );
					do_settings_sections( 'greenmetrics_display' );
					submit_button();
					?>
				</form>
			</div>
			
			<!-- Live Preview Section -->
			<div class="greenmetrics-admin-card badge-preview" style="margin-top: 30px;">
				<h2><?php esc_html_e( 'Badge Preview', 'greenmetrics' ); ?></h2>
				<p class="description"><?php esc_html_e( 'This is how your badge will appear on your website:', 'greenmetrics' ); ?></p>
				
				<div class="preview-container" style="position: relative; width: 100%; height: 300px; border: 1px solid #ddd; background-color: #f9f9f9; overflow: hidden; margin-top: 15px;">
					<!-- Badge preview will be displayed here via JavaScript -->
					<div id="badge-preview-container" style="position: absolute;" class="<?php echo esc_attr( $settings['badge_position'] ); ?>">
						<div class="greenmetrics-badge <?php echo esc_attr( $settings['badge_size'] ); ?>" style="
							background-color: <?php echo esc_attr( $settings['badge_background_color'] ); ?>;
							color: <?php echo esc_attr( $settings['badge_text_color'] ); ?>;
						">
							<?php if ( isset( $settings['display_icon'] ) && $settings['display_icon'] ) : ?>
								<div class="icon-container" style="color: <?php echo esc_attr( $settings['badge_icon_color'] ); ?>;">
									<?php
									$icon_type = isset( $settings['badge_icon_type'] ) ? $settings['badge_icon_type'] : 'leaf';
									$custom_icon = isset( $settings['badge_custom_icon'] ) ? $settings['badge_custom_icon'] : '';
									
									switch ( $icon_type ) {
										case 'leaf':
											echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 8C8 10 5.9 16.17 3.82 21.34l1.89.66.95-2.3c.48.17 1.02.3 1.58.3C17 20 22 13.46 22 6c0-.55-.06-1.09-.14-1.62C20.18 4.15 18.66 4 17 4V2c1.67 0 3.35.12 5 .34V4c-1.67-.22-3.33-.34-5-.34v2zM2 6c0 7.46 5 14 14.5 14 .56 0 1.1-.13 1.58-.3l.95 2.3 1.89-.66C18.1 16.17 16 10 7 8c0 0-5 0-5 0z"/></svg>';
											break;
										case 'tree':
											echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22c4.97 0 9-4.03 9-9-4.97 0-9 4.03-9 9zm2.44-9.43h-.44v2h.44c2.32 0 2.49 3.23 2.49 3.23 1.52-1.84 2.63-4.43 1.73-7C17.56 8.37 15.5 7 15.5 7S14.8 9.1 13 9.42v.36c1.32-.18 2.44.11 2.44.11s-1.22 1.91-1 3.68z"/><path d="M12.28 10h-.56v2h.56c2.33 0 2.51 3.45 2.51 3.45 1.55-1.89 2.67-4.63 1.77-7.24-.51-1.46-2.18-3.02-2.18-3.02s-.99 2.18-2.1 2.48V8c1.34-.2 2.55.07 2.55.07s-1.34 1.66-1.14 3.44z"/><path d="M12.63 5.33c-.28.47-1.04 1.68-2 1.87V8.8c1.35-.19 2.97.31 2.97.31S12.69 10.3 12.22 12h.33v-2h-.16c.06-.32.2-.65.44-.97.19.38.39.75.58 1.09l.66-.42c-.18-.28-.33-.57-.46-.85 0 0 .99.17 2.22.5-.27-.5-2.47-4.02-3.2-4.02z"/><path d="M10.45 12h-.43v8.17c.34-.14.66-.34.95-.55L10.45 12zm1.66 4.62c.1.21.19.42.27.63-.16-.19-.31-.39-.46-.57.07-.02.12-.04.19-.06zm1.14-4.62L12.1 17.1c.45-.11.88-.29 1.29-.51l-.14-4.59z"/><path d="M9.3 14.13l-.24 7.14c.24.11.48.19.73.26l-.42-7.8c-.02.14-.05.27-.07.4zm3.33 1.7c-.04-.04-.08-.09-.12-.14.03.05.06.09.09.13.01 0 .02.01.03.01zm-.83-3.83l-.32 7.46c.29.05.58.08.88.08.12 0 .24-.01.36-.02L12 12l-.2 0z"/></svg>';
											break;
										case 'globe':
											echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>';
											break;
										case 'recycle':
											echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M5.77 7.15L7.2 4.78l1.03-1.71c.39-.65 1.33-.65 1.72 0l1.48 2.46-1.23 2.06-1 1.34-2.43-4.78zm15.95 5.82l-1.6-2.66-3.46 2L18.87 16H21v2l-3.87-7.03zM16 21h1.5l2.05-3.42-3.46-2-1.09 1.84L16 21zm-3.24-3.71l-1.03-1.71-1.43 2.43-2.43 4.78 1.6 2.66 3.46-2 1.03-1.71-1.43-2.45zM13.42 8.5l-1.48-2.46c-.39-.65-1.33-.65-1.72 0L9.22 7.15l-1 1.34 2.43 4.78 1.6-2.66 1.17-2.11zM10.5 14.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>';
											break;
										case 'custom':
											if ( $custom_icon ) {
												echo '<img src="' . esc_url( $custom_icon ) . '" alt="Custom Icon" style="width: 20px; height: 20px;">';
											} else {
												echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>';
											}
											break;
										default:
											echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 8C8 10 5.9 16.17 3.82 21.34l1.89.66.95-2.3c.48.17 1.02.3 1.58.3C17 20 22 13.46 22 6c0-.55-.06-1.09-.14-1.62C20.18 4.15 18.66 4 17 4V2c1.67 0 3.35.12 5 .34V4c-1.67-.22-3.33-.34-5-.34v2zM2 6c0 7.46 5 14 14.5 14 .56 0 1.1-.13 1.58-.3l.95 2.3 1.89-.66C18.1 16.17 16 10 7 8c0 0-5 0-5 0z"/></svg>';
									}
									?>
								</div>
							<?php endif; ?>
							<span><?php echo esc_html( $settings['badge_text'] ); ?></span>
						</div>
					</div>
				</div>
			</div>
			
			<!-- Usage Instructions -->
			<div class="greenmetrics-admin-card" style="margin-top: 30px;">
				<h2><?php esc_html_e( 'Usage Instructions', 'greenmetrics' ); ?></h2>
				
				<h3><?php esc_html_e( 'Automatic Badge Display', 'greenmetrics' ); ?></h3>
				<p><?php esc_html_e( 'When enabled in the settings, the badge will automatically appear on all pages of your site in the position you select.', 'greenmetrics' ); ?></p>
				
				<h3><?php esc_html_e( 'Using Shortcode', 'greenmetrics' ); ?></h3>
				<p><?php esc_html_e( 'You can also add the badge to specific locations using the shortcode:', 'greenmetrics' ); ?></p>
				<div class="code-block" style="background: #f5f5f5; padding: 15px; border-radius: 4px;">
					<code>[greenmetrics_badge]</code>
				</div>
				
				<p style="margin-top: 15px;"><?php esc_html_e( 'The shortcode can include custom attributes to override the default settings:', 'greenmetrics' ); ?></p>
				<ul style="list-style-type: disc; margin-left: 20px;">
					<li><code>position="top-left|top-right|bottom-left|bottom-right"</code></li>
					<li><code>size="small|medium|large"</code></li>
					<li><code>text="Your custom badge text"</code></li>
					<li><code>background_color="#hexcolor"</code></li>
					<li><code>text_color="#hexcolor"</code></li>
					<li><code>icon_color="#hexcolor"</code></li>
				</ul>
				
				<p><?php esc_html_e( 'Example with custom attributes:', 'greenmetrics' ); ?></p>
				<div class="code-block" style="background: #f5f5f5; padding: 15px; border-radius: 4px;">
					<code>[greenmetrics_badge position="top-right" size="large" text="Green Website"]</code>
				</div>
				
				<h3><?php esc_html_e( 'Using Block Editor', 'greenmetrics' ); ?></h3>
				<p><?php esc_html_e( 'Add the GreenMetrics badge using the Block Editor in your page or post:', 'greenmetrics' ); ?></p>
				<ol style="margin-left: 20px; line-height: 1.6;">
					<li><?php esc_html_e( 'Edit a page or post using the Block Editor', 'greenmetrics' ); ?></li>
					<li><?php esc_html_e( 'Click the "+" button to add a new block', 'greenmetrics' ); ?></li>
					<li><?php esc_html_e( 'Search for "GreenMetrics"', 'greenmetrics' ); ?></li>
					<li><?php esc_html_e( 'Select the "GreenMetrics Badge" block', 'greenmetrics' ); ?></li>
					<li><?php esc_html_e( 'Customize the badge appearance using the block settings sidebar', 'greenmetrics' ); ?></li>
				</ol>
				
				<div style="background: #f8f9f9; border: 1px solid #ddd; border-left: 4px solid #4CAF50; padding: 15px; margin-top: 15px; border-radius: 4px;">
					<p style="margin: 0;"><strong><?php esc_html_e( 'Tip:', 'greenmetrics' ); ?></strong> <?php esc_html_e( 'The Block Editor provides a visual way to customize the badge with the same options available in the shortcode.', 'greenmetrics' ); ?></p>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// Update badge preview when settings change
	$('#enable_badge, #badge_position, #badge_size, #badge_text, #badge_background_color, #badge_text_color, #badge_icon_color').on('change input', function() {
		updateBadgePreview();
	});
	
	function updateBadgePreview() {
		// Get current settings
		const position = $('#badge_position').val();
		const size = $('#badge_size').val();
		const text = $('#badge_text').val();
		const bgColor = $('#badge_background_color').val();
		const textColor = $('#badge_text_color').val();
		const iconColor = $('#badge_icon_color').val();
		
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
		
		// Update icon color
		$badge.find('svg').css('fill', iconColor);
	}
	
	// Set initial preview
	updateBadgePreview();
	
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
});
</script>

<style>
.preview-container {
	border-radius: 4px;
}

#badge-preview-container {
	padding: 15px;
}

#badge-preview-container.top-left {
	top: 0;
	left: 0;
}

#badge-preview-container.top-right {
	top: 0;
	right: 0;
}

#badge-preview-container.bottom-left {
	bottom: 0;
	left: 0;
}

#badge-preview-container.bottom-right {
	bottom: 0;
	right: 0;
}

.greenmetrics-badge {
	display: inline-flex;
	align-items: center;
	padding: 8px 12px;
	border-radius: 4px;
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
	box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.greenmetrics-badge.small {
	font-size: 12px;
	padding: 6px 10px;
}

.greenmetrics-badge.medium {
	font-size: 14px;
	padding: 8px 12px;
}

.greenmetrics-badge.large {
	font-size: 16px;
	padding: 10px 14px;
}

.greenmetrics-badge svg {
	margin-right: 8px;
}
</style> 