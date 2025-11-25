<?php
/**
 * Last videos shortcode
 *
 * @package PeerTube_Video_Manager
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PT_Last_Videos shortcode class
 *
 * @package PeerTube_Video_Manager
 * @since 1.0.0
 */
class PT_Last_Videos {

	/**
	 * Register shortcode
	 *
	 * @since 1.0.0
	 */
	public static function register() {
		add_shortcode( 'pt-last-videos', array( __CLASS__, 'render' ) );
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
			'count'     => get_option( 'pt_vm_videos_per_page', 8 ),
			'host_only' => 'true',
			'columns'   => 'auto',
		), $atts, 'pt-last-videos' );

		$count     = absint( $atts['count'] );
		$host_only = filter_var( $atts['host_only'], FILTER_VALIDATE_BOOLEAN );
		$columns   = sanitize_text_field( $atts['columns'] );

		$api    = new PT_API();
		$params = array(
			'count' => $count,
			'start' => 0,
			'sort'  => '-publishedAt',
		);

		if ( $host_only ) {
			$params['isLocal'] = true;
		}

		$result = $api->get_videos( $params );

		if ( empty( $result['data'] ) ) {
			return '<p class="pt-no-videos">' . esc_html__( 'Keine Videos gefunden.', 'peertube-video-manager' ) . '</p>';
		}

		$base_url = get_option( 'pt_vm_base_url', 'https://lokalmedial.de' );

		ob_start();
		$grid_class = 'pt-video-grid';
		
		// Handle columns parameter
		if ( 'auto' !== $columns && is_numeric( $columns ) ) {
			$columns_num = absint( $columns );
			if ( $columns_num >= 1 && $columns_num <= 6 ) {
				$grid_class .= ' pt-video-grid-cols-' . $columns_num;
			}
		} elseif ( '1' === $columns || 'single' === strtolower( $columns ) ) {
			$grid_class .= ' pt-video-grid-cols-1';
		}

		echo '<div class="' . esc_attr( $grid_class ) . '">';

		foreach ( $result['data'] as $video ) {
			// Get full video data only if pluginData is missing
			if ( ! isset( $video['pluginData'] ) || empty( $video['pluginData'] ) ) {
				$full_video = $api->get_video( $video['uuid'] );
				if ( $full_video ) {
					$video = $full_video;
				}
			}
			self::render_video_card( $video, $base_url, $api );
		}

		echo '</div>';
		return ob_get_clean();
	}

	/**
	 * Render video card
	 *
	 * @param array  $video Video data.
	 * @param string $base_url Base URL.
	 * @param PT_API $api API instance.
	 * @since 1.0.0
	 */
	private static function render_video_card( $video, $base_url, $api ) {
		$template_path = PT_VM_PLUGIN_DIR . 'templates/video-card.php';
		
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}
}

