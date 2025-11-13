<?php
/**
 * Latest per channel shortcode
 *
 * @package PeerTube_Video_Manager
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PT_Latest_Per_Channel shortcode class
 *
 * @package PeerTube_Video_Manager
 * @since 1.0.0
 */
class PT_Latest_Per_Channel {

	/**
	 * Register shortcode
	 *
	 * @since 1.0.0
	 */
	public static function register() {
		add_shortcode( 'pt-latest-per-channel', array( __CLASS__, 'render' ) );
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
			'channels' => '',
			'columns'  => 'auto',
		), $atts, 'pt-latest-per-channel' );

		$channels = $atts['channels'];
		$columns  = sanitize_text_field( $atts['columns'] );

		// If no channels specified, try to get from settings
		if ( empty( $channels ) ) {
			$channels = get_option( 'pt_vm_default_channels', '' );
		}

		if ( empty( $channels ) ) {
			return '<p class="pt-no-videos">' . esc_html__( 'Keine Kanäle angegeben. Bitte verwenden Sie das channels-Attribut oder konfigurieren Sie Standard-Kanäle in den Einstellungen.', 'peertube-video-manager' ) . '</p>';
		}

		// Parse channels (comma-separated or newline-separated)
		$channels = preg_split( '/[\r\n,]+/', $channels );
		$channels = array_map( 'trim', $channels );
		$channels = array_filter( $channels );

		if ( empty( $channels ) ) {
			return '<p class="pt-no-videos">' . esc_html__( 'Keine gültigen Kanäle gefunden.', 'peertube-video-manager' ) . '</p>';
		}

		$api      = new PT_API();
		$base_url = get_option( 'pt_vm_base_url', 'https://lokalmedial.de' );
		$videos   = array();

		// Get latest video from each channel
		foreach ( $channels as $channel ) {
			$result = $api->get_channel_videos( $channel, array(
				'count' => 1,
				'start' => 0,
				'sort'  => '-publishedAt',
			) );

			if ( ! empty( $result['data'] ) ) {
				$video = $result['data'][0];
				// Get full video data only if pluginData is missing
				if ( ! isset( $video['pluginData'] ) || empty( $video['pluginData'] ) ) {
					$full_video = $api->get_video( $video['uuid'] );
					if ( $full_video ) {
						$video = $full_video;
					}
				}
				$videos[] = $video;
			}
		}

		if ( empty( $videos ) ) {
			return '<p class="pt-no-videos">' . esc_html__( 'Keine Videos gefunden.', 'peertube-video-manager' ) . '</p>';
		}

		// Sort videos by publication date (newest first)
		usort( $videos, function( $a, $b ) {
			$time_a = strtotime( $a['publishedAt'] );
			$time_b = strtotime( $b['publishedAt'] );
			return $time_b - $time_a;
		} );

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

		foreach ( $videos as $video ) {
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

