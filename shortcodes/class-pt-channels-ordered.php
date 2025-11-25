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
			// Get channel info first to get avatar
			$channel_info = $api->get_channel( $channel_handle );
			
			$result = $api->get_channel_videos( $channel_handle, array(
				'count' => 1,
				'start' => 0,
				'sort'  => '-publishedAt',
			) );

			$channel_name = $channel_handle;
			$channel_url = '';
			$channel_avatar = '';
			$video = null;

			// Extract channel data from channel info (preferred source)
			if ( ! empty( $channel_info ) ) {
				if ( isset( $channel_info['displayName'] ) ) {
					$channel_name = $channel_info['displayName'];
				} elseif ( isset( $channel_info['name'] ) ) {
					$channel_name = $channel_info['name'];
				}
				
				// Get channel URL
				if ( isset( $channel_info['url'] ) ) {
					$channel_url = $channel_info['url'];
				} else {
					// Build channel URL from handle
					$channel_url = $base_url . '/c/' . urlencode( $channel_handle );
				}
				
				// Get channel avatar from channel info
				// Try avatars array first (newer API format)
				if ( isset( $channel_info['avatars'] ) && is_array( $channel_info['avatars'] ) && ! empty( $channel_info['avatars'] ) ) {
					// Use the first available avatar (usually the largest)
					$avatar_data = $channel_info['avatars'][0];
					if ( isset( $avatar_data['fileUrl'] ) && ! empty( $avatar_data['fileUrl'] ) ) {
						$channel_avatar = $avatar_data['fileUrl'];
					} elseif ( isset( $avatar_data['path'] ) && ! empty( $avatar_data['path'] ) ) {
						$channel_avatar = $base_url . $avatar_data['path'];
					}
				}
				// Fallback to single avatar field (older API format)
				elseif ( isset( $channel_info['avatar'] ) && is_array( $channel_info['avatar'] ) ) {
					if ( isset( $channel_info['avatar']['fileUrl'] ) && ! empty( $channel_info['avatar']['fileUrl'] ) ) {
						$channel_avatar = $channel_info['avatar']['fileUrl'];
					} elseif ( isset( $channel_info['avatar']['path'] ) && ! empty( $channel_info['avatar']['path'] ) ) {
						$channel_avatar = $base_url . $channel_info['avatar']['path'];
					}
				}
			}

			if ( ! empty( $result['data'] ) ) {
				$video = $result['data'][0];
				// Get full video data only if pluginData is missing
				if ( ! isset( $video['pluginData'] ) || empty( $video['pluginData'] ) ) {
					$full_video = $api->get_video( $video['uuid'] );
					if ( $full_video ) {
						$video = $full_video;
					}
				}
				
				// Fallback: Extract channel data from video if channel info not available
				if ( empty( $channel_info ) && isset( $video['channel'] ) ) {
					if ( isset( $video['channel']['displayName'] ) ) {
						$channel_name = $video['channel']['displayName'];
					} elseif ( isset( $video['channel']['name'] ) ) {
						$channel_name = $video['channel']['name'];
					}
					
					// Get channel URL
					if ( empty( $channel_url ) ) {
						if ( isset( $video['channel']['url'] ) ) {
							$channel_url = $video['channel']['url'];
						} else {
							// Build channel URL from handle
							$channel_url = $base_url . '/c/' . urlencode( $channel_handle );
						}
					}
					
					// Get channel avatar from video data (fallback)
					if ( empty( $channel_avatar ) ) {
						// Try avatars array
						if ( isset( $video['channel']['avatars'] ) && is_array( $video['channel']['avatars'] ) && ! empty( $video['channel']['avatars'] ) ) {
							$avatar_data = $video['channel']['avatars'][0];
							if ( isset( $avatar_data['fileUrl'] ) && ! empty( $avatar_data['fileUrl'] ) ) {
								$channel_avatar = $avatar_data['fileUrl'];
							} elseif ( isset( $avatar_data['path'] ) && ! empty( $avatar_data['path'] ) ) {
								$channel_avatar = $base_url . $avatar_data['path'];
							}
						}
						// Try single avatar field
						elseif ( isset( $video['channel']['avatar'] ) && is_array( $video['channel']['avatar'] ) ) {
							if ( isset( $video['channel']['avatar']['fileUrl'] ) && ! empty( $video['channel']['avatar']['fileUrl'] ) ) {
								$channel_avatar = $video['channel']['avatar']['fileUrl'];
							} elseif ( isset( $video['channel']['avatar']['path'] ) && ! empty( $video['channel']['avatar']['path'] ) ) {
								$channel_avatar = $base_url . $video['channel']['avatar']['path'];
							}
						}
						// Try avatarPath (legacy)
						elseif ( isset( $video['channel']['avatarPath'] ) && ! empty( $video['channel']['avatarPath'] ) ) {
							$channel_avatar = $base_url . $video['channel']['avatarPath'];
						}
					}
				}
			}

			$channels_data[] = array(
				'handle' => $channel_handle,
				'name'   => $channel_name,
				'url'    => $channel_url,
				'avatar' => $channel_avatar,
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

		// Render each channel with its video in the original order
		foreach ( $channels_with_videos as $channel_data ) {
			// Output channel card wrapper
			echo '<div class="pt-channel-card-wrapper">';
			
			// Output channel header with logo and name
			echo '<div class="pt-channel-header">';
			
			// Channel avatar/logo
			if ( ! empty( $channel_data['avatar'] ) ) {
				echo '<a href="' . esc_url( $channel_data['url'] ) . '" class="pt-channel-avatar-link" target="_blank" rel="noopener noreferrer">';
				echo '<img src="' . esc_url( $channel_data['avatar'] ) . '" alt="' . esc_attr( $channel_data['name'] ) . '" class="pt-channel-avatar">';
				echo '</a>';
			}
			
			// Channel name with link
			if ( ! empty( $channel_data['url'] ) ) {
				echo '<h3 class="pt-channel-name"><a href="' . esc_url( $channel_data['url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $channel_data['name'] ) . '</a></h3>';
			} else {
				echo '<h3 class="pt-channel-name">' . esc_html( $channel_data['name'] ) . '</h3>';
			}
			
			echo '</div>'; // Close channel header
			
			// Output video card
			if ( ! empty( $channel_data['video'] ) ) {
				self::render_video_card( $channel_data['video'], $base_url, $api );
			}
			
			echo '</div>'; // Close channel card wrapper
		}

		echo '</div>'; // Close grid
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

