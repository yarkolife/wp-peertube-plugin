<?php
/**
 * PeerTube API wrapper class
 *
 * @package PeerTube_Video_Manager
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PT_API class for interacting with PeerTube API
 *
 * @package PeerTube_Video_Manager
 * @since 1.0.0
 */
class PT_API {

	/**
	 * PeerTube instance base URL
	 *
	 * @var string
	 */
	private $base_url;

	/**
	 * Cache instance
	 *
	 * @var PT_Cache
	 */
	private $cache;

	/**
	 * Category cache
	 *
	 * @var array|null
	 */
	private $categories = null;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->base_url = rtrim( get_option( 'pt_vm_base_url', 'https://lokalmedial.de' ), '/' );
		$this->cache    = new PT_Cache();
	}

	/**
	 * Get videos from instance
	 *
	 * @param array $params Query parameters.
	 * @return array Videos data.
	 * @since 1.0.0
	 */
	public function get_videos( $params = array() ) {
		$cache_key = $this->cache->generate_key( array( 'videos', $params ) );
		$cached    = $this->cache->get( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		$defaults = array(
			'sort'  => '-publishedAt',
			'count' => 10,
			'start' => 0,
		);
		
		$params = wp_parse_args( $params, $defaults );
		
		$result = $this->make_request( '/api/v1/videos', $params );
		
		if ( ! empty( $result ) && isset( $result['data'] ) ) {
			$cache_time = get_option( 'pt_vm_cache_time_videos', 5 ) * MINUTE_IN_SECONDS;
			$this->cache->set( $cache_key, $result, $cache_time );
		}
		
		return $result;
	}

	/**
	 * Get videos from specific channel
	 *
	 * @param string $channel_handle Channel handle.
	 * @param array  $params Query parameters.
	 * @return array Videos data.
	 * @since 1.0.0
	 */
	public function get_channel_videos( $channel_handle, $params = array() ) {
		$cache_key = $this->cache->generate_key( array( 'channel_videos', $channel_handle, $params ) );
		$cached    = $this->cache->get( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		$defaults = array(
			'sort'  => '-publishedAt',
			'count' => 10,
			'start' => 0,
		);
		
		$params = wp_parse_args( $params, $defaults );
		
		$endpoint = '/api/v1/video-channels/' . urlencode( $channel_handle ) . '/videos';
		$result   = $this->make_request( $endpoint, $params );
		
		if ( ! empty( $result ) && isset( $result['data'] ) ) {
			$cache_time = get_option( 'pt_vm_cache_time_videos', 5 ) * MINUTE_IN_SECONDS;
			$this->cache->set( $cache_key, $result, $cache_time );
		}
		
		return $result;
	}

	/**
	 * Get single video details
	 *
	 * @param string $id Video UUID or shortUUID.
	 * @return array|null Video data or null if not found.
	 * @since 1.0.0
	 */
	public function get_video( $id ) {
		$cache_key = $this->cache->generate_key( array( 'video', $id ) );
		$cached    = $this->cache->get( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		$endpoint = '/api/v1/videos/' . urlencode( $id );
		$result   = $this->make_request( $endpoint );
		
		if ( ! empty( $result ) && isset( $result['uuid'] ) ) {
			$cache_time = 10 * MINUTE_IN_SECONDS;
			$this->cache->set( $cache_key, $result, $cache_time );
			return $result;
		}
		
		return null;
	}

	/**
	 * Search videos
	 *
	 * @param string $query Search query.
	 * @param array  $params Query parameters.
	 * @return array Search results.
	 * @since 1.0.0
	 */
	public function search_videos( $query, $params = array() ) {
		$cache_key = $this->cache->generate_key( array( 'search', $query, $params ) );
		$cached    = $this->cache->get( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		$defaults = array(
			'search' => $query,
			'count'  => 12,
			'start'  => 0,
		);
		
		$params = wp_parse_args( $params, $defaults );
		
		$result = $this->make_request( '/api/v1/search/videos', $params );
		
		if ( ! empty( $result ) && isset( $result['data'] ) ) {
			// Cache search results for 2 minutes
			$this->cache->set( $cache_key, $result, 2 * MINUTE_IN_SECONDS );
		}
		
		return $result;
	}

	/**
	 * Get instance configuration
	 *
	 * @return array Configuration data.
	 * @since 1.0.0
	 */
	public function get_config() {
		$cache_key = 'config';
		$cached    = $this->cache->get( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		$result = $this->make_request( '/api/v1/config' );
		
		if ( ! empty( $result ) ) {
			$cache_time = get_option( 'pt_vm_cache_time_config', 24 ) * HOUR_IN_SECONDS;
			$this->cache->set( $cache_key, $result, $cache_time );
		}
		
		return $result;
	}

	/**
	 * Find video by videoNumber from pluginData
	 *
	 * @param string $video_number Video number to search for.
	 * @return array|null Video data or null if not found.
	 * @since 1.0.0
	 */
	public function find_video_by_number( $video_number ) {
		// First try: use search API if available (faster than pagination)
		// Search for video number as string
		$search_result = $this->search_videos( $video_number, array( 'count' => 100, 'start' => 0 ) );
		if ( ! empty( $search_result['data'] ) && is_array( $search_result['data'] ) ) {
			foreach ( $search_result['data'] as $video ) {
				// Get full video data to ensure we have pluginData
				$full_video = $this->get_video( $video['uuid'] );
				if ( $full_video ) {
					$plugin_data = $this->get_plugin_data( $full_video );
					// Compare as strings to handle numeric strings correctly
					if ( (string) $plugin_data['videoNumber'] === (string) $video_number ) {
						return $full_video;
					}
				}
			}
		}
		
		// Second try: search through paginated video lists (up to 50 pages = 5000 videos)
		$start = 0;
		$count = 100;
		$max_pages = 50; // Increased to 50 pages
		
		// Search through paginated lists (cache is handled per request)
		for ( $i = 0; $i < $max_pages; $i++ ) {
			// Use direct API call without cache for search
			$cache_key = $this->cache->generate_key( array( 'videos', array( 'count' => $count, 'start' => $start ) ) );
			$cached = $this->cache->get( $cache_key );
			
			if ( false === $cached ) {
				$result = $this->make_request( '/api/v1/videos', array(
					'count' => $count,
					'start' => $start,
					'sort'  => '-publishedAt',
				) );
			} else {
				$result = $cached;
			}
			
			if ( empty( $result['data'] ) || ! is_array( $result['data'] ) ) {
				break;
			}
			
			foreach ( $result['data'] as $video ) {
				if ( ! isset( $video['uuid'] ) ) {
					continue;
				}
				
				// Check pluginData in list item first (may be present)
				$plugin_data = $this->get_plugin_data( $video );
				if ( (string) $plugin_data['videoNumber'] === (string) $video_number ) {
					// Found! Get full video details
					return $this->get_video( $video['uuid'] );
				}
				
				// If not in list, get full video data (slower but more reliable)
				// Only do this for a small batch to avoid rate limits
				if ( $i < 10 ) { // First 10 pages only
					$full_video = $this->get_video( $video['uuid'] );
					if ( $full_video ) {
						$plugin_data = $this->get_plugin_data( $full_video );
						if ( (string) $plugin_data['videoNumber'] === (string) $video_number ) {
							return $full_video;
						}
					}
				}
			}
			
			// Check if we have more videos
			if ( count( $result['data'] ) < $count ) {
				break;
			}
			
			$start += $count;
		}
		
		return null;
	}

	/**
	 * Get category name by ID
	 *
	 * @param int $category_id Category ID.
	 * @return string Category name or empty string.
	 * @since 1.0.0
	 */
	public function get_category_name( $category_id ) {
		if ( empty( $category_id ) || $category_id === 0 ) {
			return '';
		}
		
		if ( null === $this->categories ) {
			$config = $this->get_config();
			$this->categories = array();
			
			if ( isset( $config['videoCategories'] ) && is_array( $config['videoCategories'] ) ) {
				// Convert PeerTube category format to simple associative array
				// PeerTube returns array of objects/arrays with id and label
				foreach ( $config['videoCategories'] as $key => $category ) {
					$cat_id = null;
					$cat_label = '';
					
					// Handle array format: ['id' => 101, 'label' => 'Kurzfilm']
					if ( is_array( $category ) ) {
						// Check if it's an associative array with id/label
						if ( isset( $category['id'] ) ) {
							$cat_id = (int) $category['id'];
							$cat_label = isset( $category['label'] ) ? $category['label'] : ( isset( $category['name'] ) ? $category['name'] : '' );
						}
						// Check if it's a numeric array where key is ID and value is label
						elseif ( is_numeric( $key ) && ! empty( $category ) && is_string( $category ) ) {
							$cat_id = (int) $key;
							$cat_label = $category;
						}
					}
					// Handle object format: (object)['id' => 101, 'label' => 'Kurzfilm']
					elseif ( is_object( $category ) ) {
						if ( isset( $category->id ) ) {
							$cat_id = (int) $category->id;
							$cat_label = isset( $category->label ) ? $category->label : ( isset( $category->name ) ? $category->name : '' );
						}
						// Check if numeric key with string value
						elseif ( is_numeric( $key ) && is_string( $category ) ) {
							$cat_id = (int) $key;
							$cat_label = $category;
						}
					}
					// Handle simple string value with numeric key: [101 => 'Kurzfilm']
					elseif ( is_numeric( $key ) && is_string( $category ) ) {
						$cat_id = (int) $key;
						$cat_label = $category;
					}
					
					if ( $cat_id !== null && ! empty( $cat_label ) ) {
						$this->categories[ $cat_id ] = $cat_label;
					}
				}
				
				// Debug logging (only if WP_DEBUG is enabled)
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'PT Categories loaded: ' . count( $this->categories ) . ' categories. Sample: ' . print_r( array_slice( $this->categories, 0, 3, true ), true ) );
				}
			} else {
				// Debug logging if categories are not found
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'PT Categories not found in config. Config keys: ' . print_r( array_keys( $config ), true ) );
				}
			}
		}
		
		// Look up category by ID
		$category_id = (int) $category_id;
		if ( isset( $this->categories[ $category_id ] ) ) {
			return $this->categories[ $category_id ];
		}
		
		// Debug logging if category not found
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'PT Category not found for ID: ' . $category_id . '. Available IDs: ' . print_r( array_keys( $this->categories ), true ) );
		}
		
		return '';
	}

	/**
	 * Extract plugin data from video
	 *
	 * @param array $video Video data.
	 * @return array Plugin data with senderResponsible and videoNumber.
	 * @since 1.0.0
	 */
	public function get_plugin_data( $video ) {
		$result = array(
			'senderResponsible' => '',
			'videoNumber'       => '',
		);
		
		if ( isset( $video['pluginData'] ) && is_array( $video['pluginData'] ) ) {
			if ( isset( $video['pluginData']['senderResponsible'] ) ) {
				$result['senderResponsible'] = $video['pluginData']['senderResponsible'];
			}
			if ( isset( $video['pluginData']['videoNumber'] ) ) {
				$result['videoNumber'] = $video['pluginData']['videoNumber'];
			}
		}
		
		return $result;
	}

	/**
	 * Make HTTP request to PeerTube API
	 *
	 * @param string $endpoint API endpoint.
	 * @param array  $params Query parameters.
	 * @return array Response data or empty array on error.
	 * @since 1.0.0
	 */
	private function make_request( $endpoint, $params = array() ) {
		$url = $this->base_url . $endpoint;
		
		if ( ! empty( $params ) ) {
			$url = add_query_arg( $params, $url );
		}
		
		$response = wp_remote_get( $url, array(
			'timeout' => 15,
			'headers' => array(
				'Accept' => 'application/json',
			),
		) );
		
		if ( is_wp_error( $response ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'PT API Error: ' . $response->get_error_message() );
			}
			return array();
		}
		
		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'PT API HTTP Error: ' . $code . ' for ' . $url );
			}
			return array();
		}
		
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		
		if ( null === $data ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'PT API JSON decode error for: ' . $url );
			}
			return array();
		}
		
		return $data;
	}

	/**
	 * Test connection to PeerTube instance
	 *
	 * @return array Response with success status and message.
	 * @since 1.0.0
	 */
	public function test_connection() {
		$config = $this->make_request( '/api/v1/config' );
		
		if ( ! empty( $config ) && isset( $config['instance'] ) ) {
			return array(
				'success' => true,
				'message' => sprintf(
					/* translators: %s: instance name */
					__( 'Verbindung erfolgreich! Instanz: %s', 'peertube-video-manager' ),
					$config['instance']['name']
				),
			);
		}
		
		return array(
			'success' => false,
			'message' => __( 'Verbindung fehlgeschlagen. Bitte überprüfen Sie die URL.', 'peertube-video-manager' ),
		);
	}
}

