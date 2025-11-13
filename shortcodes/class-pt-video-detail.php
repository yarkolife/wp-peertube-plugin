<?php
/**
 * Video detail shortcode
 *
 * @package PeerTube_Video_Manager
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PT_Video_Detail shortcode class
 *
 * @package PeerTube_Video_Manager
 * @since 1.0.0
 */
class PT_Video_Detail {

	/**
	 * Register shortcode
	 *
	 * @since 1.0.0
	 */
	public static function register() {
		add_shortcode( 'pt-video', array( __CLASS__, 'render' ) );
	}

	/**
	 * Render shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered output.
	 * @since 1.0.0
	 */
	public static function render( $atts ) {
		$atts = shortcode_atts( array(
			'id'     => '',
			'number' => '',
		), $atts, 'pt-video' );

		$id     = sanitize_text_field( $atts['id'] );
		$number = sanitize_text_field( $atts['number'] );

		// At least one parameter is required
		if ( empty( $id ) && empty( $number ) ) {
			return '<p class="pt-error">' . esc_html__( 'Bitte geben Sie eine Video-ID (id) oder Video-Nummer (number) an.', 'peertube-video-manager' ) . '</p>';
		}

		$api   = new PT_API();
		$video = null;

		// Search by videoNumber if provided
		if ( ! empty( $number ) ) {
			$video = $api->find_video_by_number( $number );
			
			if ( null === $video ) {
				return '<p class="pt-error">' . sprintf(
					/* translators: %s: video number */
					esc_html__( 'Video mit Video-Nummer "%s" nicht gefunden.', 'peertube-video-manager' ),
					esc_html( $number )
				) . '</p>';
			}
		} elseif ( ! empty( $id ) ) {
			// Search by ID
			$video = $api->get_video( $id );
			
			if ( null === $video ) {
				return '<p class="pt-error">' . sprintf(
					/* translators: %s: video ID */
					esc_html__( 'Video mit ID "%s" nicht gefunden.', 'peertube-video-manager' ),
					esc_html( $id )
				) . '</p>';
			}
		}

		$base_url = get_option( 'pt_vm_base_url', 'https://lokalmedial.de' );

		ob_start();
		$template_path = PT_VM_PLUGIN_DIR . 'templates/video-detail.php';
		
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}

		return ob_get_clean();
	}
}

