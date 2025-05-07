<?php
/**
 * The GreenMetrics SVG Sanitizer class
 *
 * This class handles sanitization of SVG files for the GreenMetrics plugin.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */

namespace GreenMetrics;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The GreenMetrics SVG Sanitizer class.
 *
 * This class handles sanitization of SVG files for the GreenMetrics plugin.
 *
 * @package    GreenMetrics
 * @subpackage GreenMetrics/includes
 */
class GreenMetrics_SVG_Sanitizer {

	/**
	 * Initialize the class and set its hooks.
	 */
	public function __construct() {
		// Hook into WordPress's upload process
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'sanitize_svg_uploads' ) );

		// Add SVG to allowed mime types
		add_filter( 'upload_mimes', array( $this, 'allow_svg_uploads' ) );

		// Fix SVG dimensions in media library
		add_filter( 'wp_prepare_attachment_for_js', array( $this, 'fix_svg_dimensions' ), 10, 3 );

		// Allow SVG file uploads in WordPress 4.7.1 and 4.7.2
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'check_svg_filetype' ), 10, 4 );
	}

	/**
	 * Check SVG filetype
	 *
	 * This fixes a bug in WordPress 4.7.1 and 4.7.2 that prevents SVG uploads.
	 *
	 * @param array  $data                File data.
	 * @param string $file                Full path to the file.
	 * @param string $filename            The name of the file.
	 * @param array  $mimes               Array of mime types keyed by their file extension regex.
	 * @return array Modified file data.
	 */
	public function check_svg_filetype( $data, $file, $filename, $mimes ) {
		$ext = pathinfo( $filename, PATHINFO_EXTENSION );

		if ( 'svg' === strtolower( $ext ) ) {
			$data['ext']  = 'svg';
			$data['type'] = 'image/svg+xml';
		}

		return $data;
	}

	/**
	 * Allow SVG uploads
	 *
	 * @param array $mimes Allowed mime types.
	 * @return array Modified allowed mime types.
	 */
	public function allow_svg_uploads( $mimes ) {
		// Add SVG mime types
		$mimes['svg']  = 'image/svg+xml';
		$mimes['svgz'] = 'image/svg+xml';

		return $mimes;
	}

	/**
	 * Sanitize SVG uploads
	 *
	 * @param array $file File data.
	 * @return array Modified file data.
	 */
	public function sanitize_svg_uploads( $file ) {
		// Only process SVG files
		if ( $file['type'] !== 'image/svg+xml' ) {
			return $file;
		}

		// Check if file exists
		if ( ! file_exists( $file['tmp_name'] ) ) {
			return $file;
		}

		// Get the SVG content
		$svg_content = file_get_contents( $file['tmp_name'] );
		if ( ! $svg_content ) {
			return $file;
		}

		// Sanitize the SVG content
		$sanitized_svg = $this->sanitize_svg( $svg_content );
		if ( ! $sanitized_svg ) {
			$file['error'] = __( 'Invalid SVG file. Please try another file.', 'greenmetrics' );
			return $file;
		}

		// Write the sanitized SVG back to the file
		file_put_contents( $file['tmp_name'], $sanitized_svg );

		return $file;
	}

	/**
	 * Fix SVG dimensions in media library
	 *
	 * @param array      $response    Response data.
	 * @param WP_Post    $attachment  Attachment object.
	 * @param array|bool $meta        Attachment meta data.
	 * @return array Modified response data.
	 */
	public function fix_svg_dimensions( $response, $attachment, $meta ) {
		if ( $response['mime'] === 'image/svg+xml' && empty( $response['sizes'] ) ) {
			$svg_path = get_attached_file( $attachment->ID );
			if ( ! $svg_path ) {
				return $response;
			}

			$dimensions = $this->get_svg_dimensions( $svg_path );
			if ( $dimensions ) {
				$response['width']  = $dimensions['width'];
				$response['height'] = $dimensions['height'];
				$response['sizes']  = array(
					'full' => array(
						'url'         => $response['url'],
						'width'       => $dimensions['width'],
						'height'      => $dimensions['height'],
						'orientation' => $dimensions['width'] > $dimensions['height'] ? 'landscape' : 'portrait',
					),
				);
			}
		}

		return $response;
	}

	/**
	 * Get SVG dimensions
	 *
	 * @param string $svg_path Path to SVG file.
	 * @return array|false Dimensions or false on failure.
	 */
	private function get_svg_dimensions( $svg_path ) {
		$svg = simplexml_load_file( $svg_path );
		if ( ! $svg ) {
			return false;
		}

		$attributes = $svg->attributes();
		if ( isset( $attributes->width, $attributes->height ) ) {
			return array(
				'width'  => (int) $attributes->width,
				'height' => (int) $attributes->height,
			);
		} elseif ( isset( $attributes->viewBox ) ) {
			$viewbox = explode( ' ', $attributes->viewBox );
			if ( count( $viewbox ) === 4 ) {
				return array(
					'width'  => (int) $viewbox[2],
					'height' => (int) $viewbox[3],
				);
			}
		}

		return false;
	}

	/**
	 * Sanitize SVG content
	 *
	 * @param string $svg_content SVG content.
	 * @return string|false Sanitized SVG content or false on failure.
	 */
	public function sanitize_svg( $svg_content ) {
		// Basic validation
		if ( ! $this->is_valid_svg( $svg_content ) ) {
			return false;
		}

		// Remove potentially harmful elements and attributes
		$sanitized_svg = $this->remove_harmful_elements( $svg_content );

		/**
		 * FUTURE ENHANCEMENT:
		 * For even better security, consider using a dedicated SVG sanitizer library
		 * such as enshrined/svg-sanitize or ml/HTMLSanitizer.
		 *
		 * Example implementation with enshrined/svg-sanitize:
		 *
		 * if ( class_exists( '\enshrined\svgSanitize\Sanitizer' ) ) {
		 *     $sanitizer = new \enshrined\svgSanitize\Sanitizer();
		 *     $clean = $sanitizer->sanitize( $svg_content );
		 *     if ( ! $clean ) {
		 *         return false;
		 *     }
		 *     return $clean;
		 * }
		 */

		return $sanitized_svg;
	}

	/**
	 * Check if SVG content is valid
	 *
	 * @param string $svg_content SVG content.
	 * @return bool Whether the SVG content is valid.
	 */
	private function is_valid_svg( $svg_content ) {
		// Check if content starts with SVG tag
		if ( ! preg_match( '/<svg\s/i', $svg_content ) ) {
			return false;
		}

		// Try to load as XML
		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $svg_content );
		$errors = libxml_get_errors();
		libxml_clear_errors();

		return $xml !== false && empty( $errors );
	}

	/**
	 * Remove harmful elements and attributes from SVG content
	 *
	 * @param string $svg_content SVG content.
	 * @return string Sanitized SVG content.
	 */
	private function remove_harmful_elements( $svg_content ) {
		// Remove scripts
		$svg_content = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $svg_content );

		// Remove specific event handlers (more targeted approach)
		$svg_content = preg_replace( '/\b(onclick|onload|onmouseover|onmouseout|onmousedown|onmouseup|onkeydown|onkeypress|onkeyup|onfocus|onblur|onchange|onsubmit|onreset|onselect|onabort|onerror|ondblclick)\s*=\s*["\'][^"\']*["\']/i', '', $svg_content );

		// Remove external references
		$svg_content = preg_replace( '/\bxlink:href\s*=\s*["\'](?!#)[^"\']*["\']/i', '', $svg_content );

		// Remove data URIs
		$svg_content = preg_replace( '/\bdata:\s*[^;]*;base64/i', '', $svg_content );

		// Remove JavaScript URIs
		$svg_content = preg_replace( '/\bjavascript:/i', '', $svg_content );

		// Use wp_kses to whitelist allowed elements and attributes
		$allowed_svg_tags = array(
			'svg'     => array(
				'xmlns'             => true,
				'width'             => true,
				'height'            => true,
				'viewbox'           => true,
				'preserveaspectratio' => true,
				'fill'              => true,
				'stroke'            => true,
				'stroke-width'      => true,
				'stroke-linecap'    => true,
				'stroke-linejoin'   => true,
				'class'             => true,
				'style'             => true,
			),
			'g'       => array(
				'fill'              => true,
				'stroke'            => true,
				'stroke-width'      => true,
				'transform'         => true,
				'class'             => true,
				'style'             => true,
			),
			'path'    => array(
				'd'                 => true,
				'fill'              => true,
				'stroke'            => true,
				'stroke-width'      => true,
				'transform'         => true,
				'class'             => true,
				'style'             => true,
			),
			'rect'    => array(
				'x'                 => true,
				'y'                 => true,
				'width'             => true,
				'height'            => true,
				'fill'              => true,
				'stroke'            => true,
				'stroke-width'      => true,
				'transform'         => true,
				'class'             => true,
				'style'             => true,
			),
			'circle'  => array(
				'cx'                => true,
				'cy'                => true,
				'r'                 => true,
				'fill'              => true,
				'stroke'            => true,
				'stroke-width'      => true,
				'transform'         => true,
				'class'             => true,
				'style'             => true,
			),
			'ellipse' => array(
				'cx'                => true,
				'cy'                => true,
				'rx'                => true,
				'ry'                => true,
				'fill'              => true,
				'stroke'            => true,
				'stroke-width'      => true,
				'transform'         => true,
				'class'             => true,
				'style'             => true,
			),
			'line'    => array(
				'x1'                => true,
				'y1'                => true,
				'x2'                => true,
				'y2'                => true,
				'fill'              => true,
				'stroke'            => true,
				'stroke-width'      => true,
				'transform'         => true,
				'class'             => true,
				'style'             => true,
			),
			'polyline' => array(
				'points'            => true,
				'fill'              => true,
				'stroke'            => true,
				'stroke-width'      => true,
				'transform'         => true,
				'class'             => true,
				'style'             => true,
			),
			'polygon' => array(
				'points'            => true,
				'fill'              => true,
				'stroke'            => true,
				'stroke-width'      => true,
				'transform'         => true,
				'class'             => true,
				'style'             => true,
			),
			'text'    => array(
				'x'                 => true,
				'y'                 => true,
				'fill'              => true,
				'font-size'         => true,
				'font-family'       => true,
				'text-anchor'       => true,
				'transform'         => true,
				'class'             => true,
				'style'             => true,
			),
			'title'   => array(
				'class'             => true,
				'style'             => true,
			),
			'desc'    => array(
				'class'             => true,
				'style'             => true,
			),
		);

		// Apply wp_kses to filter the SVG content
		$svg_content = wp_kses( $svg_content, $allowed_svg_tags );

		// Preserve viewBox aspect ratio if it was removed
		if ( ! preg_match( '/\bviewbox\s*=\s*["\'][^"\']*["\']/i', $svg_content ) ) {
			// Try to extract width and height
			preg_match( '/\bwidth\s*=\s*["\']([^"\']*)["\']/', $svg_content, $width_matches );
			preg_match( '/\bheight\s*=\s*["\']([^"\']*)["\']/', $svg_content, $height_matches );

			if ( ! empty( $width_matches[1] ) && ! empty( $height_matches[1] ) ) {
				$width = (int) $width_matches[1];
				$height = (int) $height_matches[1];

				if ( $width > 0 && $height > 0 ) {
					// Add viewBox attribute
					$svg_content = preg_replace( '/<svg\b([^>]*)>/i', '<svg$1 viewBox="0 0 ' . $width . ' ' . $height . '">', $svg_content );
				}
			}
		}

		return $svg_content;
	}

	/**
	 * Get the SVG Sanitizer instance.
	 *
	 * @return GreenMetrics_SVG_Sanitizer The SVG Sanitizer instance.
	 */
	public static function get_instance() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new self();
		}
		return $instance;
	}
}
