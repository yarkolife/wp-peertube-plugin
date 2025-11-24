<?php
/**
 * Channels with videos in order shortcode
 *
 * @package PeerTube_Video_Manager
 * @since 1.1.7
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PT_Channels_Ordered shortcode class
 *
 * @package PeerTube_Video_Manager
 * @since 1.1.7
 */
class PT_Channels_Ordered {

	/**
	 * Register shortcode
	 *
	 * @since 1.1.7
	 */
	public static function register() {
		add_shortcode( 'pt-channels-ordered', array( __CLASS__, 'render' ) );
	}

	/**
	 * Render shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered output.
	 * @since 1.1.7
	 */
	public static function render( $atts ) {
		$atts = shortcode_atts( array(
			'channels' => '',
			'columns'  => 'auto',
		), $atts, 'pt-channels-ordered' );

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
		$channels_data = array();

		// Get latest video from each channel and preserve order
		foreach ( $channels as $channel_handle ) {
			$result = $api->get_channel_videos( $channel_handle, array(
				'count' => 1,
				'start' => 0,
				'sort'  => '-publishedAt',
			) );

			$channel_name = $channel_handle;
			$video = null;

			if ( ! empty( $result['data'] ) ) {
				$video = $result['data'][0];
				// Get full video data only if pluginData is missing
				if ( ! isset( $video['pluginData'] ) || empty( $video['pluginData'] ) ) {
					$full_video = $api->get_video( $video['uuid'] );
					if ( $full_video ) {
						$video = $full_video;
					}
				}
				
				// Extract channel name from video data
				if ( isset( $video['channel'] ) ) {
					if ( isset( $video['channel']['displayName'] ) ) {
						$channel_name = $video['channel']['displayName'];
					} elseif ( isset( $video['channel']['name'] ) ) {
						$channel_name = $video['channel']['name'];
					}
				}
			}

			$channels_data[] = array(
				'handle' => $channel_handle,
				'name'   => $channel_name,
				'video'  => $video,
			);
		}

		// Filter out channels without videos
		$channels_with_videos = array_filter( $channels_data, function( $item ) {
			return ! empty( $item['video'] );
		} );

		if ( empty( $channels_with_videos ) ) {
			return '<p class="pt-no-videos">' . esc_html__( 'Keine Videos gefunden.', 'peertube-video-manager' ) . '</p>';
		}

		ob_start();

		// Render each channel with its video in the original order
		foreach ( $channels_with_videos as $channel_data ) {
			// Output channel section
			echo '<div class="pt-channel-section">';
			
			// Output channel name
			echo '<h3 class="pt-channel-name">' . esc_html( $channel_data['name'] ) . '</h3>';
			
			// Output video card in a grid container (single column for one video)
			echo '<div class="pt-video-grid pt-video-grid-cols-1">';
			
			if ( ! empty( $channel_data['video'] ) ) {
				self::render_video_card( $channel_data['video'], $base_url, $api );
			}
			
			echo '</div>'; // Close grid
			echo '</div>'; // Close channel section
		}

		return ob_get_clean();
	}

	/**
	 * Render video card
	 *
	 * @param array  $video Video data.
	 * @param string $base_url Base URL.
	 * @param PT_API $api API instance.
	 * @since 1.1.7
	 */
	private static function render_video_card( $video, $base_url, $api ) {
		$template_path = PT_VM_PLUGIN_DIR . 'templates/video-card.php';
		
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}
}

