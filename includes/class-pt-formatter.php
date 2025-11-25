<?php
/**
 * Data formatter class
 *
 * @package PeerTube_Video_Manager
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PT_Formatter class for formatting video data
 *
 * @package PeerTube_Video_Manager
 * @since 1.0.0
 */
class PT_Formatter {

	/**
	 * Format duration from seconds to HH:MM:SS or MM:SS
	 *
	 * @param int $seconds Duration in seconds.
	 * @return string Formatted duration.
	 * @since 1.0.0
	 */
	public static function format_duration( $seconds ) {
		if ( ! is_numeric( $seconds ) || $seconds < 0 ) {
			return '0:00';
		}
		
		$hours   = floor( $seconds / 3600 );
		$minutes = floor( ( $seconds % 3600 ) / 60 );
		$secs    = $seconds % 60;
		
		if ( $hours > 0 ) {
			return sprintf( '%d:%02d:%02d', $hours, $minutes, $secs );
		}
		
		return sprintf( '%d:%02d', $minutes, $secs );
	}

	/**
	 * Format timestamp to relative time (German)
	 *
	 * @param string $timestamp ISO 8601 timestamp.
	 * @return string Relative time string.
	 * @since 1.0.0
	 */
	public static function format_relative_time( $timestamp ) {
		$time = strtotime( $timestamp );
		if ( false === $time ) {
			return '';
		}
		
		$diff = time() - $time;
		
		if ( $diff < 0 ) {
			return __( 'In der Zukunft', 'peertube-video-manager' );
		}
		
		if ( $diff < MINUTE_IN_SECONDS ) {
			return __( 'Gerade eben', 'peertube-video-manager' );
		}
		
		if ( $diff < HOUR_IN_SECONDS ) {
			$minutes = floor( $diff / MINUTE_IN_SECONDS );
			if ( $minutes === 1 ) {
				return __( 'Vor 1 Minute', 'peertube-video-manager' );
			}
			return sprintf(
				/* translators: %d: number of minutes */
				__( 'Vor %d Minuten', 'peertube-video-manager' ),
				$minutes
			);
		}
		
		if ( $diff < DAY_IN_SECONDS ) {
			$hours = floor( $diff / HOUR_IN_SECONDS );
			if ( $hours === 1 ) {
				return __( 'Vor 1 Stunde', 'peertube-video-manager' );
			}
			return sprintf(
				/* translators: %d: number of hours */
				__( 'Vor %d Stunden', 'peertube-video-manager' ),
				$hours
			);
		}
		
		if ( $diff < WEEK_IN_SECONDS ) {
			$days = floor( $diff / DAY_IN_SECONDS );
			if ( $days === 1 ) {
				return __( 'Vor 1 Tag', 'peertube-video-manager' );
			}
			return sprintf(
				/* translators: %d: number of days */
				__( 'Vor %d Tagen', 'peertube-video-manager' ),
				$days
			);
		}
		
		if ( $diff < MONTH_IN_SECONDS ) {
			$weeks = floor( $diff / WEEK_IN_SECONDS );
			if ( $weeks === 1 ) {
				return __( 'Vor 1 Woche', 'peertube-video-manager' );
			}
			return sprintf(
				/* translators: %d: number of weeks */
				__( 'Vor %d Wochen', 'peertube-video-manager' ),
				$weeks
			);
		}
		
		if ( $diff < YEAR_IN_SECONDS ) {
			$months = floor( $diff / MONTH_IN_SECONDS );
			if ( $months === 1 ) {
				return __( 'Vor 1 Monat', 'peertube-video-manager' );
			}
			return sprintf(
				/* translators: %d: number of months */
				__( 'Vor %d Monaten', 'peertube-video-manager' ),
				$months
			);
		}
		
		$years = floor( $diff / YEAR_IN_SECONDS );
		if ( $years === 1 ) {
			return __( 'Vor 1 Jahr', 'peertube-video-manager' );
		}
		return sprintf(
			/* translators: %d: number of years */
			__( 'Vor %d Jahren', 'peertube-video-manager' ),
			$years
		);
	}

	/**
	 * Format view count
	 *
	 * @param int $count View count.
	 * @return string Formatted view count.
	 * @since 1.0.0
	 */
	public static function format_views( $count ) {
		if ( ! is_numeric( $count ) ) {
			return '0 Aufrufe';
		}
		
		$formatted = number_format_i18n( $count );
		
		if ( $count === 1 ) {
			return sprintf(
				/* translators: %s: formatted number */
				__( '%s Aufruf', 'peertube-video-manager' ),
				$formatted
			);
		}
		
		return sprintf(
			/* translators: %s: formatted number */
			__( '%s Aufrufe', 'peertube-video-manager' ),
			$formatted
		);
	}

	/**
	 * Sanitize video description HTML
	 *
	 * @param string $html Description HTML.
	 * @return string Sanitized HTML.
	 * @since 1.0.0
	 */
	public static function sanitize_description( $html ) {
		if ( empty( $html ) ) {
			return '';
		}
		
		$allowed_tags = array(
			'a'      => array(
				'href'   => true,
				'title'  => true,
				'target' => true,
			),
			'br'     => array(),
			'em'     => array(),
			'strong' => array(),
			'p'      => array(),
			'ul'     => array(),
			'ol'     => array(),
			'li'     => array(),
			'b'      => array(),
			'i'      => array(),
		);
		
		return wp_kses( $html, $allowed_tags );
	}

	/**
	 * Get embed URL for video player
	 *
	 * @param string $base_url PeerTube instance URL.
	 * @param array  $video Video data.
	 * @return string Embed URL.
	 * @since 1.0.0
	 */
	public static function get_embed_url( $base_url, $video ) {
		$base_url = rtrim( $base_url, '/' );
		
		if ( isset( $video['embedPath'] ) ) {
			return $base_url . $video['embedPath'];
		}
		
		if ( isset( $video['shortUUID'] ) ) {
			return $base_url . '/videos/embed/' . $video['shortUUID'];
		}
		
		if ( isset( $video['uuid'] ) ) {
			return $base_url . '/videos/embed/' . $video['uuid'];
		}
		
		return '';
	}

	/**
	 * Get video URL on PeerTube instance
	 *
	 * @param string $base_url PeerTube instance URL.
	 * @param array  $video Video data.
	 * @return string Video URL.
	 * @since 1.0.0
	 */
	public static function get_video_url( $base_url, $video ) {
		$base_url = rtrim( $base_url, '/' );
		
		if ( isset( $video['shortUUID'] ) ) {
			return $base_url . '/w/' . $video['shortUUID'];
		}
		
		if ( isset( $video['uuid'] ) ) {
			return $base_url . '/w/' . $video['uuid'];
		}
		
		return '';
	}

	/**
	 * Get thumbnail URL
	 *
	 * @param string $base_url PeerTube instance URL.
	 * @param array  $video Video data.
	 * @param string $size Optional size parameter (default: 'preview' for better quality).
	 * @return string Thumbnail URL.
	 * @since 1.0.0
	 */
	public static function get_thumbnail_url( $base_url, $video, $size = 'preview' ) {
		$base_url = rtrim( $base_url, '/' );
		
		// Prefer thumbnailUrl if available (full URL)
		if ( isset( $video['thumbnailUrl'] ) && ! empty( $video['thumbnailUrl'] ) ) {
			return $video['thumbnailUrl'];
		}
		
		// Use previewPath for better quality (usually larger than thumbnailPath)
		if ( isset( $video['previewPath'] ) && ! empty( $video['previewPath'] ) ) {
			return $base_url . $video['previewPath'];
		}
		
		// Fallback to thumbnailPath
		if ( isset( $video['thumbnailPath'] ) && ! empty( $video['thumbnailPath'] ) ) {
			return $base_url . $video['thumbnailPath'];
		}
		
		// Try to construct URL from UUID if available
		if ( isset( $video['uuid'] ) && ! empty( $video['uuid'] ) ) {
			// Try different thumbnail sizes
			$uuid = $video['uuid'];
			if ( $size === 'preview' || $size === 'large' ) {
				// Try preview size first (usually 640x360)
				$preview_url = $base_url . '/static/previews/' . $uuid . '.jpg';
				return $preview_url;
			}
		}
		
		return '';
	}
	
	/**
	 * Get thumbnail srcset for responsive images
	 *
	 * @param string $base_url PeerTube instance URL.
	 * @param array  $video Video data.
	 * @return string Srcset string for img tag.
	 * @since 1.1.5
	 */
	public static function get_thumbnail_srcset( $base_url, $video ) {
		$base_url = rtrim( $base_url, '/' );
		$srcset = array();
		
		// Get base thumbnail URL
		$base_thumbnail = self::get_thumbnail_url( $base_url, $video, 'preview' );
		
		if ( empty( $base_thumbnail ) ) {
			return '';
		}
		
		// If we have UUID, try to generate different sizes
		if ( isset( $video['uuid'] ) && ! empty( $video['uuid'] ) ) {
			$uuid = $video['uuid'];
			
			// Try to generate srcset with different sizes
			// PeerTube typically has: thumbnail (128x72), preview (640x360)
			$sizes = array(
				'thumbnail' => '128',
				'preview' => '640',
			);
			
			foreach ( $sizes as $size_name => $width ) {
				$url = '';
				if ( $size_name === 'preview' && isset( $video['previewPath'] ) ) {
					$url = $base_url . $video['previewPath'];
				} elseif ( $size_name === 'thumbnail' && isset( $video['thumbnailPath'] ) ) {
					$url = $base_url . $video['thumbnailPath'];
				} else {
					// Try static path
					if ( $size_name === 'preview' ) {
						$url = $base_url . '/static/previews/' . $uuid . '.jpg';
					} else {
						$url = $base_url . '/static/thumbnails/' . $uuid . '.jpg';
					}
				}
				
				if ( ! empty( $url ) ) {
					$srcset[] = esc_url( $url ) . ' ' . $width . 'w';
				}
			}
		}
		
		// If no srcset generated, use base thumbnail
		if ( empty( $srcset ) && ! empty( $base_thumbnail ) ) {
			return '';
		}
		
		return implode( ', ', $srcset );
	}

	/**
	 * Get WordPress URL for video detail page
	 *
	 * @param array $video Video data.
	 * @param PT_API $api API instance.
	 * @return string WordPress URL with video parameter.
	 * @since 1.0.0
	 */
	public static function get_wp_video_url( $video, $api = null ) {
		// Get video page from settings
		$video_page = get_option( 'pt_vm_video_page_id', 0 );
		
		// If no video page is set, return empty (don't create links to current page)
		if ( $video_page <= 0 ) {
			return '';
		}
		
		$video_page_url = get_permalink( $video_page );
		if ( ! $video_page_url ) {
			return '';
		}
		
		// Get channel name from video if available
		$channel_name = '';
		if ( isset( $video['channel'] ) ) {
			if ( is_array( $video['channel'] ) && isset( $video['channel']['name'] ) ) {
				$channel_name = $video['channel']['name'];
			} elseif ( is_string( $video['channel'] ) ) {
				$channel_name = $video['channel'];
			}
		}
		
		// Try to get video number first (more user-friendly)
		if ( null !== $api ) {
			$plugin_data = $api->get_plugin_data( $video );
			if ( ! empty( $plugin_data['videoNumber'] ) ) {
				$url = add_query_arg( 'pt_video', urlencode( $plugin_data['videoNumber'] ), $video_page_url );
				// Add channel to URL if available
				if ( ! empty( $channel_name ) ) {
					$url = add_query_arg( 'pt_channel', urlencode( $channel_name ), $url );
				}
				return $url;
			}
		}
		
		// Fallback to video ID
		$video_id = isset( $video['uuid'] ) ? $video['uuid'] : ( isset( $video['shortUUID'] ) ? $video['shortUUID'] : '' );
		if ( ! empty( $video_id ) ) {
			$url = add_query_arg( 'pt_video_id', urlencode( $video_id ), $video_page_url );
			// Add channel to URL if available
			if ( ! empty( $channel_name ) ) {
				$url = add_query_arg( 'pt_channel', urlencode( $channel_name ), $url );
			}
			return $url;
		}
		
		return '';
	}

	/**
	 * Get SVG icon
	 *
	 * @param string $icon_name Icon name (calendar, eye, folder, tag, person, clock).
	 * @param int    $size Icon size in pixels.
	 * @return string SVG icon HTML.
	 * @since 1.0.5
	 */
	public static function get_svg_icon( $icon_name, $size = 16 ) {
		$icons = array(
			'calendar' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="pt-icon pt-icon-calendar"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z"></path></svg>',
			'eye' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="pt-icon pt-icon-eye"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path></svg>',
			'folder' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="pt-icon pt-icon-folder"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776"></path></svg>',
			'tag' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="pt-icon pt-icon-tag"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"></path></svg>',
			'person' => '<svg class="pt-icon pt-icon-person" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" stroke="currentColor" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"></path></svg>',
			'clock' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="pt-icon pt-icon-clock"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path></svg>',
		);

		if ( isset( $icons[ $icon_name ] ) ) {
			return $icons[ $icon_name ];
		}

		return '';
	}

	/**
	 * Get search URL for tag
	 *
	 * @param string $tag Tag name (without #).
	 * @return string Search URL with pt_search parameter.
	 * @since 1.0.6
	 */
	public static function get_search_url( $tag ) {
		// Get search page from settings
		$search_page_id = get_option( 'pt_vm_search_page_id', 0 );
		
		if ( $search_page_id > 0 ) {
			// Use dedicated search page
			$base_url = get_permalink( $search_page_id );
			if ( ! $base_url ) {
				// Fallback if page was deleted
				$base_url = home_url( '/' );
			}
		} else {
			// Fallback: use current page or home URL
			if ( isset( $_SERVER['REQUEST_URI'] ) ) {
				// Remove query string from current URL to get clean base
				$request_uri = strtok( $_SERVER['REQUEST_URI'], '?' );
				$base_url = home_url( $request_uri );
			} else {
				$base_url = home_url( '/' );
			}
		}
		
		// Add search parameter (replace existing pt_search if present)
		$base_url = remove_query_arg( array( 'pt_search', 'pt_page' ), $base_url );
		return add_query_arg( 'pt_search', urlencode( $tag ), $base_url );
	}
}

