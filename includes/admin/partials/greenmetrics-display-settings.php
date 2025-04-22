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
		'badge_position'         => 'bottom-right',
		'badge_size'             => 'medium',
		'badge_text'             => 'Eco-Friendly Site',
		'badge_background_color' => '#4CAF50',
		'badge_text_color'       => '#ffffff',
		'badge_icon_color'       => '#ffffff',
	)
);
?>

<div class="wrap">
	<h1><?php esc_html_e( 'GreenMetrics - Display Settings', 'greenmetrics' ); ?></h1>

	<div class="greenmetrics-admin-container">
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
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" style="fill: <?php echo esc_attr( $settings['badge_icon_color'] ); ?>;">
								<path d="M12,3C10.73,3 9.6,3.8 9.18,5H3V7H4.95L2,14C1.53,16 3,17 5.5,17C8,17 9.56,16 9,14L6.05,7H9.17C9.5,7.85 10.15,8.5 11,8.83V20H2V22H22V20H13V8.82C13.85,8.5 14.5,7.85 14.82,7H17.95L15,14C14.53,16 16,17 18.5,17C21,17 22.56,16 22,14L19.05,7H21V5H14.83C14.4,3.8 13.27,3 12,3M12,5A1,1 0 0,1 13,6A1,1 0 0,1 12,7A1,1 0 0,1 11,6A1,1 0 0,1 12,5M5.5,10.25L7,14H4L5.5,10.25M18.5,10.25L20,14H17L18.5,10.25Z" />
							</svg>
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